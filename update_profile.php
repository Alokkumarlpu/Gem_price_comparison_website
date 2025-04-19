<?php
// File: update_profile.php

declare(strict_types=1);
ini_set('display_errors', '0'); // Production: 0, Development: 1
error_reporting(E_ALL);

session_start();

// --- Authentication Check ---
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    $_SESSION['login_message'] = 'Please log in to update your profile.';
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/functions.php';

// --- User Data & Form Pre-fill ---
$user_id = (int) $_SESSION['user_id'];
// Get current data from session to pre-fill form
// Use values potentially passed back from handler on error, otherwise use session
$current_username = escape_html($_SESSION['update_form_data']['username'] ?? $_SESSION['username'] ?? '');
$current_email = escape_html($_SESSION['update_form_data']['email'] ?? $_SESSION['email'] ?? '');

// --- Error/Success Handling ---
$errors = $_SESSION['update_errors'] ?? [];
$success_message = $_SESSION['update_success'] ?? null; // Although usually redirect to profile on success

// Clear session data used by this form after retrieving it
unset($_SESSION['update_errors'], $_SESSION['update_success'], $_SESSION['update_form_data']);

// --- CSRF Protection ---
// Generate CSRF token if one doesn't exist for the session
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        error_log("CSRF Token Generation Failed on update_profile.php: " . $e->getMessage());
        $errors[] = "A security token could not be generated. Please try refreshing the page.";
        $_SESSION['csrf_token'] = 'token_error'; // Set placeholder to avoid undefined errors
    }
}
$csrf_token = $_SESSION['csrf_token'];

$page_title = "Update Profile - " . escape_html(SITE_NAME);

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
        body { font-family: 'Poppins', sans-serif; }
        /* Add specific styles if needed */
        .form-section-title {
            font-size: 1.125rem; /* text-lg */
            font-weight: 600;
            margin-bottom: 1rem; /* mb-4 */
            padding-bottom: 0.5rem; /* pb-2 */
            border-bottom: 1px solid #e5e7eb; /* border-gray-200 */
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen" data-logged-in="true">

    <?php include __DIR__ . '/includes/header.php'; ?>

    <main id="main-content" class="flex-grow container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-lg border border-gray-200">
            <h1 class="text-center text-2xl sm:text-3xl font-bold text-blue-700 mb-8">Update Your Profile</h1>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative mb-6" role="alert">
                    <strong class="font-bold block sm:inline">Update Failed:</strong>
                    <ul class="mt-2 list-disc list-inside text-sm">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo escape_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success_message): // Less likely here, but just in case ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md relative mb-6" role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline"><?php echo escape_html($success_message); ?></span>
                </div>
            <?php endif; ?>

            <form action="php/update_profile_handler.php" method="POST" id="update-profile-form" class="space-y-6">

                <?php // --- CSRF Token --- ?>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <?php // --- Account Details Section --- ?>
                <div>
                    <h2 class="form-section-title text-gray-800">Account Details</h2>
                    <div class="space-y-4">
                         <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                Username <span class="text-red-600">*</span>
                            </label>
                            <input type="text" id="username" name="username" value="<?php echo $current_username; ?>"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                                   required maxlength="50" pattern="^[a-zA-Z0-9_]+$" title="Username can only contain letters, numbers, and underscores.">
                            <p class="mt-1 text-xs text-gray-500">Letters, numbers, and underscores only. Max 50 characters.</p>
                         </div>
                         <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address <span class="text-red-600">*</span>
                            </label>
                            <input type="email" id="email" name="email" value="<?php echo $current_email; ?>"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                                   required maxlength="255">
                         </div>
                    </div>
                </div>

                 <?php // --- Change Password Section --- ?>
                 <div>
                    <h2 class="form-section-title text-gray-800">Change Password (Optional)</h2>
                     <p class="text-sm text-gray-600 mb-4">Leave these fields blank if you do not want to change your password.</p>
                    <div class="space-y-4">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                                New Password
                            </label>
                            <input type="password" id="new_password" name="new_password" autocomplete="new-password"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                                   minlength="8"> <?php // Basic client-side check ?>
                             <p class="mt-1 text-xs text-gray-500">Minimum 8 characters.</p>
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm New Password
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out">
                        </div>
                    </div>
                </div>

                <?php // --- Current Password Verification (Required for any change) --- ?>
                <div>
                    <h2 class="form-section-title text-gray-800">Verify Changes</h2>
                    <div class="space-y-4">
                         <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                                Current Password <span class="text-red-600">*</span>
                            </label>
                            <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                                   required>
                             <p class="mt-1 text-xs text-gray-500">Required to save any changes.</p>
                         </div>
                    </div>
                </div>


                <?php // --- Submit Button --- ?>
                <div class="pt-5">
                    <div class="flex justify-end space-x-3">
                         <a href="profile.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                             Cancel
                         </a>
                        <button type="submit" <?php if ($csrf_token === 'token_error') echo 'disabled'; ?>
                                class="inline-flex justify-center py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out <?php if ($csrf_token === 'token_error') echo 'opacity-50 cursor-not-allowed'; ?>">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>