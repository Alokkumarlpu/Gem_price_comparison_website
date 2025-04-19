<?php
// File: php/update_profile_handler.php

declare(strict_types=1);
ini_set('display_errors', '0'); // Production: 0, Development: 1
error_reporting(E_ALL);

session_start();

// --- Authentication Check ---
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    // Not logged in, cannot process update
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied. You must be logged in."; // Simple message, or redirect
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// --- 1. Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../update_profile.php'); // Redirect back if accessed incorrectly
    exit;
}

// --- 2. CSRF Token Validation ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['update_errors'] = ['Invalid security token. Please try submitting the form again.'];
    unset($_SESSION['csrf_token']); // Invalidate the used token
    redirect('../update_profile.php');
    exit;
}
// Invalidate the token after successful check to prevent reuse
unset($_SESSION['csrf_token']);

// --- 3. Retrieve Input ---
$user_id = (int) $_SESSION['user_id'];
$current_session_username = $_SESSION['username'] ?? '';
$current_session_email = $_SESSION['email'] ?? '';

// Get potentially updated values
$new_username = trim($_POST['username'] ?? '');
$new_email = trim($_POST['email'] ?? '');

// Get password fields
$current_password = $_POST['current_password'] ?? ''; // Don't trim passwords
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$errors = [];
$update_fields = []; // Store fields that actually need updating
$update_types = '';  // Store types for bind_param
$update_values = []; // Store values for bind_param

// --- 4. Input Validation ---

// Basic required fields
if (empty($new_username)) {
    $errors[] = 'Username is required.';
} elseif (strlen($new_username) > 50) {
     $errors[] = 'Username cannot exceed 50 characters.';
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
     $errors[] = 'Username can only contain letters, numbers, and underscores.';
}

if (empty($new_email)) {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
} elseif (strlen($new_email) > 255) {
     $errors[] = 'Email address cannot exceed 255 characters.';
}


if (empty($current_password)) {
    $errors[] = 'Current password is required to save changes.';
}

// Password change validation (only if new password fields are not empty)
$change_password = (!empty($new_password) || !empty($confirm_password));
if ($change_password) {
    if (empty($new_password)) {
        $errors[] = 'New password cannot be empty if you intend to change it.';
    } elseif (strlen($new_password) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }
    // Add more checks? (e.g., complexity requirements)

    if ($new_password !== $confirm_password) {
        $errors[] = 'New password and confirmation password do not match.';
    }
}

// --- 5. Proceed if Basic Validation Passes ---
if (empty($errors)) {
    $conn = null;
    try {
        $conn = connect_db();
        if (!$conn) {
            throw new Exception("Database connection failed.");
        }
        $conn->set_charset('utf8mb4');

        // --- Verify Current Password ---
        $sql_verify = "SELECT password_hash, username, email FROM users WHERE user_id = ? LIMIT 1";
        $stmt_verify = $conn->prepare($sql_verify);
        if (!$stmt_verify) throw new Exception("Failed to prepare password verification query: " . $conn->error);

        $stmt_verify->bind_param('i', $user_id);
        $stmt_verify->execute();
        $result_verify = $stmt_verify->get_result();

        if ($result_verify->num_rows !== 1) {
            // Should not happen if user_id from session is valid, but check anyway
            throw new Exception("User not found during verification.");
        }

        $user_data = $result_verify->fetch_assoc();
        $stmt_verify->close();

        if (!password_verify($current_password, $user_data['password_hash'])) {
            $errors[] = 'Incorrect current password.';
            // Log failed attempt
            error_log("Profile Update Failed: Incorrect current password for user ID {$user_id}");
        } else {
            // Current password is correct, proceed with checks and updates

            // --- Check for Username/Email Conflicts ---
            // Check if new username is different AND already taken by *another* user
            if ($new_username !== $user_data['username']) {
                 $sql_check_user = "SELECT user_id FROM users WHERE username = ? AND user_id != ? LIMIT 1";
                 $stmt_check_user = $conn->prepare($sql_check_user);
                 if($stmt_check_user){
                     $stmt_check_user->bind_param('si', $new_username, $user_id);
                     $stmt_check_user->execute();
                     if ($stmt_check_user->get_result()->num_rows > 0) {
                         $errors[] = 'Username is already taken. Please choose another.';
                     }
                     $stmt_check_user->close();
                 } else {
                     throw new Exception("Failed to prepare username check query.");
                 }
                 // Mark username for update if no error
                 if(!in_array('Username is already taken. Please choose another.', $errors)){
                    $update_fields[] = "username = ?";
                    $update_types .= 's';
                    $update_values[] = $new_username;
                 }
            }

            // Check if new email is different AND already taken by *another* user
            if ($new_email !== $user_data['email']) {
                 $sql_check_email = "SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1";
                 $stmt_check_email = $conn->prepare($sql_check_email);
                  if($stmt_check_email){
                     $stmt_check_email->bind_param('si', $new_email, $user_id);
                     $stmt_check_email->execute();
                     if ($stmt_check_email->get_result()->num_rows > 0) {
                         $errors[] = 'Email address is already registered to another account.';
                     }
                     $stmt_check_email->close();
                  } else {
                       throw new Exception("Failed to prepare email check query.");
                  }
                  // Mark email for update if no error
                 if(!in_array('Email address is already registered to another account.', $errors)){
                    $update_fields[] = "email = ?";
                    $update_types .= 's';
                    $update_values[] = $new_email;
                 }
            }

             // --- Prepare Password Update (if requested and valid) ---
             if ($change_password && empty(array_filter($errors, fn($e) => str_contains($e, 'password')))) { // Check if password specific errors occurred
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT); // Use default hashing
                if ($new_password_hash === false) {
                    throw new Exception("Failed to hash new password.");
                }
                $update_fields[] = "password_hash = ?";
                $update_types .= 's';
                $update_values[] = $new_password_hash;
            }

            // --- Execute Update Query (if there are fields to update and no new errors) ---
            if (!empty($update_fields) && empty($errors)) {
                $sql_update = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE user_id = ?";
                $update_types .= 'i'; // Add type for user_id
                $update_values[] = $user_id; // Add user_id value

                $stmt_update = $conn->prepare($sql_update);
                if (!$stmt_update) {
                    throw new Exception("Failed to prepare update statement: " . $conn->error);
                }

                // Dynamically bind parameters
                $stmt_update->bind_param($update_types, ...$update_values);

                if (!$stmt_update->execute()) {
                    // Check for duplicate entry errors specifically (just in case checks missed something)
                    if ($conn->errno === 1062) { // Error code for duplicate entry
                         if (str_contains($conn->error, 'username')) {
                              $errors[] = 'Username is already taken. Please choose another.';
                         } elseif (str_contains($conn->error, 'email')) {
                               $errors[] = 'Email address is already registered.';
                         } else {
                              $errors[] = 'A conflict occurred while saving. Please review your changes.';
                         }
                    } else {
                       throw new Exception("Failed to execute update statement: " . $stmt_update->error);
                    }
                } elseif ($stmt_update->affected_rows >= 0) { // Check if 0 or more rows affected (0 is ok if data was same)
                    // Update successful! Update session variables
                    $_SESSION['username'] = $new_username; // Update with the submitted username
                    $_SESSION['email'] = $new_email;     // Update with the submitted email
                    $_SESSION['profile_success'] = 'Profile updated successfully!';
                    // No errors, redirect to profile page
                }
                $stmt_update->close();
            } elseif(empty($update_fields) && empty($errors)) {
                 // No actual changes were submitted, but password was correct
                 $_SESSION['profile_success'] = 'No changes detected, but verification successful.';
            }
        } // End: if password_verify succeeded

        if ($conn) $conn->close();

    } catch (mysqli_sql_exception $e_sql) {
        error_log("SQL Error during Profile Update for user {$user_id}: [{$e_sql->getCode()}] {$e_sql->getMessage()}");
        $errors[] = "A database error occurred ({$e_sql->getCode()}). Please try again later.";
        if ($conn && $conn->ping()) $conn->close();
    } catch (Exception $e) {
        error_log("General Error during Profile Update for user {$user_id}: " . $e->getMessage());
        // Avoid exposing detailed internal errors to the user
        if (empty($errors)) { // Add generic error if no specific one was set
            $errors[] = 'An unexpected error occurred. Please try again later.';
        }
        if ($conn && $conn->ping()) $conn->close();
    }
} // End: if basic validation passes

// --- 6. Handle Outcome ---
if (!empty($errors)) {
    // Store errors and submitted data (for repopulating form) in session
    $_SESSION['update_errors'] = $errors;
    $_SESSION['update_form_data'] = [ // Store potentially invalid data, will be escaped on form page
        'username' => $_POST['username'] ?? '', // Use raw POST data
        'email' => $_POST['email'] ?? ''
    ];
    session_write_close();
    redirect('../update_profile.php'); // Redirect back to the update form
    exit;
} else {
    // Success! Redirect to the profile page (success message already set in session)
    session_write_close();
    redirect('../profile.php');
    exit;
}
?>