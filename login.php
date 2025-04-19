<?php
// File: login.php

// Start session at the very top, before any output
// Note: session_start() might be called within functions.php or config.php in some frameworks,
// but calling it explicitly here ensures it happens first.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Redirect if Already Logged In ---
// Check both user_id and a dedicated logged-in flag for robustness
if (!empty($_SESSION['user_id']) && !empty($_SESSION['account_loggedin']) && $_SESSION['account_loggedin'] === true) {
    // Redirect to the main index page or user dashboard
    header('Location: index.php');
    exit; // Important: Stop script execution after redirect
}

// --- Includes ---
// Now include config and functions as we're sure we're staying on this page
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/functions.php'; // Should contain escape_html()

// --- Page Setup ---
$site_name_escaped = defined('SITE_NAME') ? escape_html(SITE_NAME) : 'Your Site';
$page_title = "Login - " . $site_name_escaped;

// --- Retrieve Data from Session (after potential redirect/error) ---
$errors = $_SESSION['login_errors'] ?? [];
$login_message = $_SESSION['login_message'] ?? null; // Message from protected pages
$submitted_identifier = $_SESSION['login_identifier'] ?? ''; // Pre-fill identifier on error

// --- Clear Session Data used by this page ---
unset($_SESSION['login_errors'], $_SESSION['login_message'], $_SESSION['login_identifier']);

// --- CSRF Protection ---
// Generate a CSRF token if one doesn't exist for the session
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Handle error if random_bytes fails (rare but possible)
        error_log("CSRF Token Generation Failed on login.php: " . $e->getMessage());
        // Add an error message for the user
        $errors[] = "A security token could not be generated. Please try refreshing the page.";
        $_SESSION['csrf_token'] = 'token_error'; // Indicate error state
    }
}
$csrf_token = $_SESSION['csrf_token'] ?? 'token_error'; // Ensure it's always defined

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php /* Favicon Links - use absolute paths */ ?>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/images/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
    <?php /* CSS with Cache Busting - use absolute path */ ?>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
    <?php /* Google Fonts */ ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        /* Add focus styles explicitly if Tailwind defaults aren't sufficient */
        input:focus {
            /* Example: ring-offset-2 ensures ring is visible over other elements */
            /* Tailwind handles this well, but you can customize here */
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen" data-logged-in="false">

    <?php include __DIR__ . '/includes/header.php'; // Ensure header doesn't start session again ?>

    <main id="main-content" class="flex-grow container mx-auto px-4 py-12 flex items-center justify-center">
        <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-lg border border-gray-200">
            <h1 class="text-center text-2xl sm:text-3xl font-bold text-blue-700 mb-8">Login to Your Account</h1>

            <?php // Display general login message (e.g., from redirects) ?>
            <?php if ($login_message): ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-md relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo escape_html($login_message); ?></span>
                </div>
            <?php endif; ?>

            <?php // Display specific login errors (e.g., wrong password) ?>
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative mb-6" role="alert">
                    <strong class="font-bold block sm:inline">Login Failed:</strong>
                    <ul class="mt-2 list-disc list-inside text-sm">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo escape_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php // The Login Form ?>
            <form action="php/login_handler.php" method="POST" id="login-form" class="space-y-6">

                <?php // CSRF Token - Essential for security ?>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div>
                    <label for="email_or_username" class="block text-sm font-medium text-gray-700 mb-1">
                        Username or Email
                    </label>
                    <input type="text" id="email_or_username" name="email_or_username"
                           value="<?php echo escape_html($submitted_identifier); // Pre-fill on error ?>"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                           required
                           autocomplete="username" <?php // Helps browsers auto-fill ?>
                           aria-describedby="email_or_username_error"
                           maxlength="255"> <?php // Sensible max length ?>
                    <p id="email_or_username_error" class="text-xs text-red-600 mt-1" aria-live="assertive"></p> <?php // For potential JS validation ?>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>
                    <input type="password" id="password" name="password"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                           required
                           autocomplete="current-password" <?php // Helps browsers auto-fill ?>
                           aria-describedby="password_error">
                     <p id="password_error" class="text-xs text-red-600 mt-1" aria-live="assertive"></p> <?php // For potential JS validation ?>
                </div>

                <div class="flex items-center justify-between flex-wrap gap-y-2"> <?php // Added flex-wrap and gap-y ?>
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox" value="1" <?php // Added value="1" ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-900 cursor-pointer"> Remember me </label>
                    </div>

                    <div class="text-sm">
                        <?php // Point to a real (even if not yet created) page ?>
                        <a href="forgot_password.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline"> Forgot your password? </a>
                    </div>
                </div>

                <div>
                    <button type="submit"
                            <?php if ($csrf_token === 'token_error') echo 'disabled'; // Disable submit if CSRF failed ?>
                            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                        Sign in
                    </button>
                </div>
            </form>

            <div class="text-center mt-8 text-sm text-gray-600">
                Don't have an account?
                <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">
                    Register here
                </a>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <?php /* Optional: Add client-side validation script here if desired */ ?>
    <script>
        // Basic example: You might add more sophisticated validation
        const form = document.getElementById('login-form');
        if (form) {
            form.addEventListener('submit', function(event) {
                // Perform basic checks, though server-side validation is the authority
                const identifier = document.getElementById('email_or_username');
                const password = document.getElementById('password');
                let isValid = true;

                // Reset errors
                document.getElementById('email_or_username_error').textContent = '';
                document.getElementById('password_error').textContent = '';

                if (!identifier || identifier.value.trim() === '') {
                    document.getElementById('email_or_username_error').textContent = 'Username or Email is required.';
                    isValid = false;
                }
                if (!password || password.value.trim() === '') {
                     document.getElementById('password_error').textContent = 'Password is required.';
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault(); // Stop submission if client-side validation fails
                }
            });
        }
    </script>

</body>
</html>