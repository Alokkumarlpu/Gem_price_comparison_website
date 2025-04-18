<?php
/**
 * Logout Handler - gem-price-comparison
 *
 * Destroys the user's session, effectively logging them out.
 */

session_start(); // Must start the session to access and modify it

// 1. Unset all session variables related to the user
// It's good practice to unset specific variables rather than destroying everything
// in case other parts of the application use the session for non-auth purposes.
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['email']);
// Unset any other session variables you might have set during login or elsewhere:
// unset($_SESSION['user_role']);
// unset($_SESSION['cart_items']); // Example if you had a cart

// 2. Destroy the session itself
// This removes the session data from the server storage.
session_destroy();

// 3. Optional: Clear session cookie
// While session_destroy() often handles this, explicitly clearing the cookie
// ensures the browser doesn't send an invalid session ID on the next request.
// Note: Parameters must match those used to set the cookie (usually defaults).
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Set expiration time in the past
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Redirect the user to the homepage (or login page)
// Using the redirect function from functions.php if available, otherwise use header()
require_once __DIR__ . '/functions.php'; // Ensure redirect function is loaded

// Optional: Set a message for the landing page (use session *before* destroying if needed, or pass via GET parameter)
// session_start(); // Need to restart session briefly if setting a message after destroy
// $_SESSION['logout_message'] = "You have been logged out successfully.";
// session_write_close();

redirect('../index.php'); // Redirect to homepage
// Or: redirect('../login.php?logged_out=1'); // Redirect to login page with a flag
exit; // Ensure no further code execution

?>