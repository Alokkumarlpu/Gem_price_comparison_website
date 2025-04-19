<?php
// File: forgot_password.php

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
$page_title = "Forgot Password - " . $site_name_escaped;

// --- Retrieve Data from Session (after potential redirect/error) ---
// Use specific keys for this page
$errors = $_SESSION['forgot_password_errors'] ?? [];
$success_message = $_SESSION['forgot_password_success'] ?? null;

// --- Clear Session Data used by this page ---
unset($_SESSION['forgot_password_errors'], $_SESSION['forgot_password_success']);

// --- CSRF Protection ---
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        error_log("CSRF Token Generation Failed on forgot_password.php: " . $e->getMessage());
        $errors[] = "A security token could not be generated. Please try refreshing the page.";
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
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen" data-logged-in="false">

    <?php include __DIR__ . '/includes/header.php'; ?>

    <main id="main-content" class="flex-grow container mx-auto px-4 py-12 flex items-center justify-center">
        <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-lg border border-gray-200">
            <h1 class="text-center text-2xl sm:text-3xl font-bold text-blue-700 mb-6">Reset Your Password</h1>

            <p class="text-center text-gray-600 text-sm mb-8">
                Enter the email address associated with your account, and we'll send you a link to reset your password.
            </p>

            <?php // Display Success Message ?>
            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md relative mb-6" role="alert">
                    <strong class="font-bold block sm:inline">Success!</strong>
                    <span class="block sm:inline"><?php echo escape_html($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php // Display Errors ?>
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative mb-6" role="alert">
                    <strong class="font-bold block sm:inline">Request Failed:</strong>
                    <ul class="mt-2 list-disc list-inside text-sm">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo escape_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php // Hide form if success message is shown ?>
            <?php if (!$success_message): ?>
                <form action="php/forgot_pass_handler.php" method="POST" id="forgot-password-form" class="space-y-6">

                    <?php // CSRF Token ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email Address
                        </label>
                        <input type="email" id="email" name="email"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                               required
                               autocomplete="email"
                               aria-describedby="email_error">
                        <p id="email_error" class="text-xs text-red-600 mt-1" aria-live="assertive"></p> <?php // For JS validation ?>
                    </div>

                    <div>
                        <button type="submit"
                                <?php if ($csrf_token === 'token_error') echo 'disabled'; // Disable if CSRF failed ?>
                                class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                            Send Password Reset Link
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="text-center mt-8 text-sm text-gray-600">
                Remember your password?
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">
                    Login here
                </a>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <?php // Basic client-side validation ?>
    <script>
        const forgotPasswordForm = document.getElementById('forgot-password-form');
        if (forgotPasswordForm) {
            forgotPasswordForm.addEventListener('submit', function(event) {
                const emailInput = document.getElementById('email');
                const emailError = document.getElementById('email_error');
                let isValid = true;

                emailError.textContent = ''; // Reset error

                if (!emailInput.checkValidity()) { // Leverages type="email" validation
                    emailError.textContent = emailInput.validationMessage || 'Please enter a valid email address.';
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault(); // Stop submission
                }
            });
        }
    </script>

</body>
</html>