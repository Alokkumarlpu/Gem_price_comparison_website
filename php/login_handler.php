<?php
session_start(); // Start session handling

// Include configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// --- 1. Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../login.php'); // Redirect back if accessed incorrectly
    exit;
}

// --- 2. Retrieve and Trim Input ---
$identifier = trim($_POST['email_or_username'] ?? ''); // User can enter email or username
$password = $_POST['password'] ?? ''; // Don't trim password

$errors = [];

// --- 3. Basic Validation ---
if (empty($identifier)) {
    $errors[] = 'Username or Email is required.';
}
if (empty($password)) {
    $errors[] = 'Password is required.';
}

// --- 4. Database Interaction (if basic validation passes) ---
if (empty($errors)) {
    $conn = null;
    try {
        $conn = connect_db();
        if ($conn === null) {
            $errors[] = 'Database connection error. Please try again later.';
            throw new Exception('DB Connection failed'); // Trigger catch for session setting
        }

        // Prepare statement to find user by username OR email
        $sql = "SELECT user_id, username, email, password_hash FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
             throw new Exception("Failed to prepare login statement: " . $conn->error);
        }

        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // User found, fetch data
            $user = $result->fetch_assoc();

            // Verify the password against the stored hash
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct! Login successful.

                // Regenerate session ID for security (prevents session fixation)
                session_regenerate_id(true);

                // Store essential user info in the session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email']; // Store email if needed elsewhere

                // Optional: Set a success message if needed on the destination page
                // $_SESSION['success_message'] = 'Login successful! Welcome back, ' . escape_html($user['username']) . '.';

                // Redirect to the main page (or a user dashboard if you create one)
                if ($conn) $conn->close(); // Close connection before redirect
                session_write_close();     // Write session data before redirect
                redirect('../index.php');
                exit; // Stop script execution

            } else {
                // Password does not match
                $errors[] = 'Invalid username/email or password.';
                 // Log failed login attempt (optional)
                 error_log("Login Failed: Incorrect password for identifier '{$identifier}' from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
            }
        } else {
            // No user found with that username or email
            $errors[] = 'Invalid username/email or password.';
            // Log failed login attempt (optional)
            error_log("Login Failed: No user found for identifier '{$identifier}' from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
            // Note: Use the same error message as incorrect password to prevent username/email enumeration.
        }
        $stmt->close();

        if ($conn) $conn->close();

    } catch (Exception $e) {
        error_log("Login Handler Error: " . $e->getMessage());
        if (empty($errors)) {
            $errors[] = 'An unexpected error occurred during login. Please try again.';
        }
        if ($conn && $conn->ping()) { $conn->close(); }
    } catch (mysqli_sql_exception $e_sql) {
         error_log("SQL Error during Login: " . $e_sql->getMessage() . " (Code: " . $e_sql->getCode() . ")");
         if (empty($errors)) {
            $errors[] = 'A database error occurred during login.';
         }
         if ($conn && $conn->ping()) { $conn->close(); }
    }
} // End if(empty($errors)) basic validation check

// --- 5. Handle Login Failure ---
if (!empty($errors)) {
    // Store errors in session
    $_SESSION['login_errors'] = $errors;
    // Optional: Store the identifier entered so the form can be repopulated
    // $_SESSION['login_identifier'] = $identifier;
    session_write_close();
    // Redirect back to the login page
    redirect('../login.php');
    exit;
}
?>