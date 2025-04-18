<?php
session_start();

// Include configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// --- 1. Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../register.php'); // Redirect back if accessed incorrectly
    exit;
}

// --- 2. Retrieve and Trim Input ---
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? ''; // Don't trim password yet
$confirm_password = $_POST['confirm_password'] ?? '';

$errors = [];
$old_input = [ // Store input to repopulate form on error
    'username' => $username,
    'email' => $email,
];

// --- 3. Validation Rules ---

// Required fields
if (empty($username)) { $errors[] = 'Username is required.'; }
if (empty($email)) { $errors[] = 'Email is required.'; }
if (empty($password)) { $errors[] = 'Password is required.'; }
if (empty($confirm_password)) { $errors[] = 'Password confirmation is required.'; }

// Username format (example: alphanumeric + underscore, 3-20 chars)
if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    $errors[] = 'Username must be 3-20 characters long and contain only letters, numbers, and underscores.';
}

// Email format
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}

// Password minimum length (example: 8 characters)
if (!empty($password) && strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
    // Add more complex rules here if needed (e.g., require numbers, uppercase, symbols)
}

// Password confirmation match
if (!empty($password) && $password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}


// --- 4. Database Interaction (if validation passes) ---
if (empty($errors)) {
    $conn = null; // Initialize connection variable
    try {
        $conn = connect_db();
        if ($conn === null) {
            $errors[] = 'Database connection error. Please try again later.';
            // Go straight to error handling below
            throw new Exception('DB Connection failed'); // Trigger catch block for session setting
        }

        // Check if username already exists
        $sql_check_username = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
        $stmt_check_username = $conn->prepare($sql_check_username);
        $stmt_check_username->bind_param('s', $username);
        $stmt_check_username->execute();
        $result_check_username = $stmt_check_username->get_result();
        if ($result_check_username->num_rows > 0) {
            $errors[] = 'Username is already taken. Please choose another.';
        }
        $stmt_check_username->close();

        // Check if email already exists
        $sql_check_email = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param('s', $email);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();
        if ($result_check_email->num_rows > 0) {
            $errors[] = 'An account with this email address already exists.';
        }
        $stmt_check_email->close();

        // Proceed with insertion if no existence errors were added
        if (empty($errors)) {
            // Hash the password securely
            $password_hash = password_hash($password, PASSWORD_DEFAULT); // Use default algorithm (currently bcrypt)

            if ($password_hash === false) {
                throw new Exception('Failed to hash password.'); // Should not happen normally
            }

            // Prepare INSERT statement
            $sql_insert = "INSERT INTO users (username, email, password_hash, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert === false) {
                 throw new Exception("Failed to prepare user insert statement: " . $conn->error);
            }

            $stmt_insert->bind_param('sss', $username, $email, $password_hash);

            if (!$stmt_insert->execute()) {
                 // Check for specific errors like duplicate entry just in case (though checked above)
                if ($conn->errno == 1062) { // Duplicate entry error code
                     $errors[] = 'Username or email might already be registered (concurrent registration).';
                } else {
                     throw new Exception("Failed to execute user insert statement: " . $stmt_insert->error);
                }
            }
            $stmt_insert->close();
        }

        // Close connection if opened
        if ($conn) $conn->close();

    } catch (Exception $e) {
        // Log the detailed error
        error_log("Registration Error: " . $e->getMessage());
        // Add a generic error for the user session if not already set
        if (empty($errors)) { // Avoid duplicate generic messages
            $errors[] = 'An unexpected error occurred during registration. Please try again.';
        }
        // Close connection if it was opened and an error occurred
        if ($conn && $conn->ping()) {
             $conn->close();
        }
    } catch (mysqli_sql_exception $e_sql) {
         error_log("SQL Error during Registration: " . $e_sql->getMessage() . " (Code: " . $e_sql->getCode() . ")");
         if (empty($errors)) {
            $errors[] = 'A database error occurred during registration.';
         }
         if ($conn && $conn->ping()) { $conn->close(); }
    }
} // End if(empty($errors)) outer validation check

// --- 5. Feedback and Redirect ---

if (empty($errors)) {
    // Registration successful!
    // Option 1: Redirect to login page with success message
    $_SESSION['login_success_message'] = 'Registration successful! Please log in.';
    session_write_close();
    redirect('../login.php');
    exit;

    // Option 2: Automatically log the user in (requires fetching user ID after insert)
    /*
    // Need to get the user ID that was just inserted
    $user_id = $conn->insert_id; // Get last inserted ID (if connection wasn't closed yet)
    // Or query by email/username again to get ID
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['success_message'] = 'Welcome! Registration successful and you are now logged in.';
    session_write_close();
    redirect('../index.php'); // Redirect to homepage
    exit;
    */

} else {
    // Registration failed - Store errors and old input in session
    $_SESSION['register_errors'] = $errors;
    $_SESSION['old_input'] = $old_input;
    session_write_close();
    // Redirect back to the registration page
    redirect('../register.php');
    exit;
}

?>