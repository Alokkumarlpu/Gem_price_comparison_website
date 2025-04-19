<?php
// File: php/forgot_password_handler.php

declare(strict_types=1);
ini_set('display_errors', '0'); // Production: 0, Development: 1
error_reporting(E_ALL);

// Start session handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// --- Constants Check (Basic) ---
if (!defined('SITE_URL') || !defined('EMAIL_FROM') || !defined('PASSWORD_RESET_EXPIRY')) {
     error_log("Forgot Password Handler Error: Required constants (SITE_URL, EMAIL_FROM, PASSWORD_RESET_EXPIRY) not defined in config.php");
     // Set a generic error for the user, don't expose config issues
     $_SESSION['forgot_password_errors'] = ['An internal configuration error occurred. Please contact support.'];
     redirect('../forgot_password.php');
     exit;
}

// --- 1. Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../forgot_password.php'); // Redirect back if accessed incorrectly
    exit;
}

// --- 2. CSRF Token Validation ---
$submitted_token = $_POST['csrf_token'] ?? null;
if (empty($submitted_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted_token)) {
    $_SESSION['forgot_password_errors'] = ['Invalid security token. Please try submitting the form again.'];
    unset($_SESSION['csrf_token']); // Invalidate potentially compromised token
    redirect('../forgot_password.php');
    exit;
}
// Invalidate the token after successful check
unset($_SESSION['csrf_token']);

// --- 3. Retrieve and Validate Input ---
$email = trim($_POST['email'] ?? '');
$errors = [];

if (empty($email)) {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address format.';
} elseif (strlen($email) > 255) { // Match users.email length
     $errors[] = 'Email address is too long.';
}

// If validation errors, redirect back immediately
if (!empty($errors)) {
    $_SESSION['forgot_password_errors'] = $errors;
    // Don't store email in session for forgot password
    session_write_close();
    redirect('../forgot_password.php');
    exit;
}

// --- 4. Process Request (Database Interaction & Email) ---
$conn = null;
$user_id = null;
$token_sent = false; // Flag to track if we attempted to send

try {
    $conn = connect_db();
    if (!$conn) {
        throw new Exception("Database connection failed.");
    }
    $conn->set_charset('utf8mb4');

    // --- Find User by Email ---
    $sql_find_user = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
    $stmt_find = $conn->prepare($sql_find_user);
    if (!$stmt_find) throw new Exception("Failed to prepare user lookup query: " . $conn->error);

    $stmt_find->bind_param('s', $email);
    $stmt_find->execute();
    $result_find = $stmt_find->get_result();

    if ($user_data = $result_find->fetch_assoc()) {
        $user_id = (int) $user_data['user_id'];
    }
    $stmt_find->close();

    // --- Proceed ONLY if user exists ---
    if ($user_id !== null) {

        // --- Generate Secure Token ---
        // Create a cryptographically secure token
        $token_bytes = random_bytes(32); // 32 bytes = 256 bits of entropy

        // Convert to hex for sending in the email URL (easier to handle)
        $token_hex = bin2hex($token_bytes);

        // Hash the *raw bytes* of the token for database storage
        // Using password_hash is good as it includes salt and algorithm info
        $token_hash = password_hash($token_bytes, PASSWORD_DEFAULT);
        if ($token_hash === false) {
             throw new Exception("Failed to hash the password reset token.");
        }

        // --- Calculate Expiry Time ---
        $expires_at_timestamp = time() + PASSWORD_RESET_EXPIRY; // Use constant from config
        $expires_at_datetime = date('Y-m-d H:i:s', $expires_at_timestamp);

        // --- Store Token Hash in Database ---
        // Consider deleting old tokens for the same user first for cleanliness (optional)
        // $sql_delete_old = "DELETE FROM password_resets WHERE user_id = ?";
        // $stmt_delete = $conn->prepare($sql_delete_old); ... execute ... $stmt_delete->close();

        $sql_insert_token = "INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert_token);
        if (!$stmt_insert) throw new Exception("Failed to prepare token insert query: " . $conn->error);

        $stmt_insert->bind_param('iss', $user_id, $token_hash, $expires_at_datetime);

        if (!$stmt_insert->execute()) {
            // Don't reveal DB errors to user, just log them
            throw new mysqli_sql_exception("Failed to store password reset token: " . $stmt_insert->error);
        }
        $stmt_insert->close();

        // --- Send Password Reset Email ---
        $reset_link = rtrim(SITE_URL, '/') . '/reset_password.php?token=' . $token_hex; // Use constant

        $subject = "Password Reset Request - " . SITE_NAME; // Use constant

        // Basic Email Body (consider HTML email for better formatting)
        $body = "Hello,\n\n";
        $body .= "You (or someone else) requested a password reset for your account on " . SITE_NAME . ".\n\n";
        $body .= "If this was you, please click the link below to set a new password:\n";
        $body .= $reset_link . "\n\n";
        $body .= "This link will expire in " . (PASSWORD_RESET_EXPIRY / 60) . " minutes.\n\n";
        $body .= "If you did not request this reset, please ignore this email. Your password will remain unchanged.\n\n";
        $body .= "Regards,\nThe " . SITE_NAME . " Team";

        $headers = 'From: ' . EMAIL_FROM . "\r\n" . // Use constant
                   'Reply-To: ' . EMAIL_FROM . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        // Use PHP's mail() function (ensure server is configured)
        if (mail($email, $subject, $body, $headers)) {
            $token_sent = true;
        } else {
            // Log the error, but don't tell the user sending failed directly
            error_log("Forgot Password Handler: Failed to send password reset email to {$email} for user ID {$user_id}.");
            // We still show the generic success message below for security.
        }
    } else {
        // User not found - Do nothing, log internally
        error_log("Forgot Password Handler: Reset requested for non-existent email: {$email}");
        // We proceed to the generic success message anyway.
    }

    if ($conn) $conn->close();

} catch (mysqli_sql_exception $e_sql) {
    error_log("SQL Error during Forgot Password for email '{$email}': [{$e_sql->getCode()}] {$e_sql->getMessage()}");
    // Don't expose DB errors. Generic message handled below.
    // $errors[] = "A database error occurred. Please try again later."; // Avoid if possible
    if ($conn && $conn->ping()) $conn->close();
} catch (Exception $e) {
    error_log("General Error during Forgot Password for email '{$email}': " . $e->getMessage());
    // Don't expose internal errors. Generic message handled below.
    // $errors[] = 'An unexpected error occurred. Please try again later.'; // Avoid if possible
    if ($conn && $conn->ping()) $conn->close();
}

// --- 5. Set Feedback and Redirect ---

// IMPORTANT: Always show a generic success message for security,
// regardless of whether the email was found or the mail() call succeeded.
// This prevents attackers from determining which emails are registered.
if (empty($errors)) { // Only set success if no *validation* or critical setup errors occurred earlier
     $_SESSION['forgot_password_success'] = 'If an account with that email address exists, instructions for resetting your password have been sent.';
} else {
    // If we caught an error above AND didn't set a specific user-facing one, set a generic one now.
     if (!isset($_SESSION['forgot_password_errors'])) {
         $_SESSION['forgot_password_errors'] = ['An error occurred while processing your request. Please try again later.'];
     }
     // Note: If validation errors happened at step 3, they are already in the session.
}

session_write_close(); // Save session data before redirect
redirect('../forgot_password.php');
exit;
?>