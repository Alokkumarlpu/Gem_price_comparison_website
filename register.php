<?php
// File: register.php

// Start session at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Redirect if Already Logged In ---
if (!empty($_SESSION['user_id']) && !empty($_SESSION['account_loggedin']) && $_SESSION['account_loggedin'] === true) {
    header('Location: index.php');
    exit;
}

// --- Includes ---
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/functions.php'; // Should contain escape_html()

// --- Page Setup ---
$site_name_escaped = defined('SITE_NAME') ? escape_html(SITE_NAME) : 'Your Site';
$page_title = "Register - " . $site_name_escaped;

// --- Retrieve Data from Session (after potential error) ---
$errors = $_SESSION['register_errors'] ?? [];
$old_input = $_SESSION['register_old_input'] ?? []; // Use a specific key for register

// --- Clear Session Data used by this page ---
unset($_SESSION['register_errors'], $_SESSION['register_old_input']);

// --- CSRF Protection ---
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        error_log("CSRF Token Generation Failed on register.php: " . $e->getMessage());
        $errors[] = "A security token could not be generated. Please refresh the page.";
        $_SESSION['csrf_token'] = 'token_error'; // Indicate error state
    }
}
$csrf_token = $_SESSION['csrf_token'] ?? 'token_error';

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
        /* Reuse Tailwind focus styles primarily */
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen" data-logged-in="false">

    <?php include __DIR__ . '/includes/header.php'; ?>

    <main id="main-content" class="flex-grow container mx-auto px-4 py-12 flex items-center justify-center">
        <?php // Consistent container styling with login.php ?>
        <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-lg border border-gray-200">
            <h1 class="text-center text-2xl sm:text-3xl font-bold text-blue-700 mb-8">Create Your Account</h1>

            <?php // Display registration errors ?>
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative mb-6" role="alert">
                    <strong class="font-bold block sm:inline">Registration Failed:</strong>
                    <ul class="mt-2 list-disc list-inside text-sm">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo escape_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php // The Registration Form ?>
            <form action="php/register_handler.php" method="POST" id="register-form" class="space-y-6">

                <?php // CSRF Token ?>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                        Username
                    </label>
                    <input type="text" id="username" name="username"
                           value="<?php echo escape_html($old_input['username'] ?? ''); ?>"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                           required
                           autocomplete="username"
                           maxlength="50" <?php // Matches DB schema ?>
                           pattern="^[a-zA-Z0-9_]+$" <?php // Client-side hint ?>
                           aria-describedby="username_help username_error">
                    <p id="username_help" class="mt-1 text-xs text-gray-500">Letters, numbers, and underscores only. Max 50 characters.</p>
                    <p id="username_error" class="text-xs text-red-600 mt-1" aria-live="assertive"></p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email Address
                    </label>
                    <input type="email" id="email" name="email"
                           value="<?php echo escape_html($old_input['email'] ?? ''); ?>"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                           required
                           autocomplete="email"
                           maxlength="255" <?php // Matches DB schema ?>
                           aria-describedby="email_error">
                     <p id="email_error" class="text-xs text-red-600 mt-1" aria-live="assertive"></p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>
                    <input type="password" id="password" name="password"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                           required
                           autocomplete="new-password" <?php // Hint for password managers ?>
                           minlength="8" <?php // Client-side check ?>
                           aria-describedby="password_help password_error">
                    <p id="password_help" class="mt-1 text-xs text-gray-500">Minimum 8 characters. A mix of letters, numbers, and symbols is recommended.</p>
                    <p id="password_error" class="text-xs text-red-600 mt-1" aria-live="assertive"></p>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                        Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                           required
                           autocomplete="new-password"
                           aria-describedby="confirm_password_error">
                    <p id="confirm_password_error" class="text-xs text-red-600 mt-1" aria-live="assertive"></p>
                </div>

                 <div>
                    <button type="submit"
                            <?php if ($csrf_token === 'token_error') echo 'disabled'; // Disable if CSRF failed ?>
                            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                        Register
                    </button>
                </div>
            </form>

            <div class="text-center mt-8 text-sm text-gray-600">
                Already have an account?
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">
                    Login here
                </a>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <?php /* Optional: Client-side validation script */ ?>
    <script>
        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordError = document.getElementById('password_error');
            const confirmPasswordError = document.getElementById('confirm_password_error');
            const usernameInput = document.getElementById('username');
            const usernameError = document.getElementById('username_error');
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('email_error');

            registerForm.addEventListener('submit', function(event) {
                let isValid = true;
                // Reset previous errors
                usernameError.textContent = '';
                emailError.textContent = '';
                passwordError.textContent = '';
                confirmPasswordError.textContent = '';

                // Username validation (basic)
                if (!usernameInput.checkValidity()) {
                    // Use built-in pattern message or custom one
                    usernameError.textContent = usernameInput.validationMessage || 'Invalid username format (letters, numbers, underscores only).';
                    isValid = false;
                }

                 // Email validation (basic)
                 if (!emailInput.checkValidity()) {
                    emailError.textContent = emailInput.validationMessage || 'Please enter a valid email address.';
                    isValid = false;
                }

                // Password length
                if (passwordInput.value.length < 8) {
                    passwordError.textContent = 'Password must be at least 8 characters long.';
                    isValid = false;
                }

                // Password match
                if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordError.textContent = 'Passwords do not match.';
                    isValid = false;
                    // Also might add error to the first password field for visibility
                     if (!passwordError.textContent) { // Don't overwrite length error
                         passwordError.textContent = 'Passwords do not match.';
                     }
                }

                if (!isValid) {
                    event.preventDefault(); // Stop form submission
                }
            });
        }
    </script>

</body>
</html>