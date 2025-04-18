<?php
// File: login.php
session_start(); // Start session at the very top

// It's generally better to include config/functions *after* potential redirects
// require_once __DIR__ . '/php/config.php';
// require_once __DIR__ . '/php/functions.php';

// If user is already logged in, redirect to homepage or dashboard
if (isset($_SESSION['user_id']) || isset($_SESSION['account_loggedin'])) { // Check common session keys
    // Use a simple header redirect before any output
    header('Location: index.php'); // Redirect to the main index page
    exit; // Important: Stop script execution after redirect
}

// Now include config and functions as we're sure we're staying on this page
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/functions.php';


$page_title = "Login - " . escape_html(SITE_NAME);
$errors = $_SESSION['login_errors'] ?? []; // Get errors from session (if redirected)
unset($_SESSION['login_errors']); // Clear errors after retrieving

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script> 
    <link rel="icon" href="favicon.ico" sizes="any"><link rel="icon" href="images/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>"> 
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        /* Minimal custom styles if needed, but prefer Tailwind */
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen" data-logged-in="false"> 

    <?php include __DIR__ . '/includes/header.php'; ?>

    <main id="main-content" class="flex-grow container mx-auto px-4 py-12 flex items-center justify-center">
        <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-lg border border-gray-200">
            <h1 class="text-center text-2xl sm:text-3xl font-bold text-blue-700 mb-8">Login to Your Account</h1>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative mb-6" role="alert">
                    <strong class="font-bold block sm:inline">Oops!</strong>
                    <ul class="mt-2 list-disc list-inside text-sm">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo escape_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="php/login_handler.php" method="POST" id="login-form" class="space-y-6">
                
                <div>
                    <label for="email_or_username" class="block text-sm font-medium text-gray-700 mb-1">
                        Username or Email
                    </label>
                    <input type="text" id="email_or_username" name="email_or_username"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                           required
                           aria-describedby="email_or_username_error"> 
                    <!-- {/* Add <p id="email_or_username_error"> if using JS validation */} -->
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>
                    <input type="password" id="password" name="password"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                           required
                           aria-describedby="password_error">
                     <!-- {/* Add <p id="password_error"> if using JS validation */} -->
                </div>

                <!-- <input type="hidden" name="csrf_token" value="<?php // echo generate_csrf_token(); ?>">  -->

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-900"> Remember me </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500 hover:underline"> Forgot your password? </a>
                    </div>
                </div>


                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
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
</body>
</html>