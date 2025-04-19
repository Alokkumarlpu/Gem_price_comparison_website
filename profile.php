<?php
// File: profile.php

declare(strict_types=1);
ini_set('display_errors', '0'); // Production: 0, Development: 1
error_reporting(E_ALL);

session_start();

// --- Authentication Check ---
// Redirect to login if user_id is not set or invalid
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Save intended page
    $_SESSION['login_message'] = 'Please log in to view your profile.';
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/functions.php';

$user_id = (int) $_SESSION['user_id'];
$username = escape_html($_SESSION['username'] ?? 'N/A'); // Get from session
$email = escape_html($_SESSION['email'] ?? 'N/A');       // Get from session
$join_date_formatted = 'N/A';
$errors = $_SESSION['profile_errors'] ?? [];    // Errors from update attempt
$success_message = $_SESSION['profile_success'] ?? null; // Success message from update

unset($_SESSION['profile_errors'], $_SESSION['profile_success']); // Clear messages

// --- Fetch Additional User Data (e.g., Join Date) ---
$conn = null;
try {
    $conn = connect_db();
    if ($conn) {
        $sql = "SELECT created_at FROM users WHERE user_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($user_data = $result->fetch_assoc()) {
                if (!empty($user_data['created_at'])) {
                    try {
                        $date = new DateTime($user_data['created_at']);
                        // More user-friendly format
                        $join_date_formatted = $date->format('F j, Y'); // e.g., August 16, 2023
                    } catch (Exception $e) {
                        error_log("Profile Page: Invalid date format for user {$user_id}: " . $user_data['created_at']);
                        $join_date_formatted = 'Invalid Date';
                    }
                }
            }
            $stmt->close();
        } else {
            error_log("Profile Page: Failed to prepare statement: " . $conn->error);
        }
        $conn->close();
    } else {
         error_log("Profile Page: DB Connection failed for user {$user_id}.");
         // Don't show DB error to user, just 'N/A' for join date
    }
} catch (Exception $e) {
    error_log("Profile Page: Error fetching join date for user {$user_id}: " . $e->getMessage());
    if ($conn && $conn->ping()) { $conn->close(); }
    // Don't show error to user
}

$page_title = "My Profile - " . escape_html(SITE_NAME);

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
        /* Custom styles for profile display */
        .profile-info dt { font-weight: 600; color: #4a5568; /* Tailwind gray-700 */ }
        .profile-info dd { color: #2d3748; /* Tailwind gray-800 */ }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen" data-logged-in="true">

    <?php include __DIR__ . '/includes/header.php'; ?>

    <main id="main-content" class="flex-grow container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-lg border border-gray-200">
            <h1 class="text-center text-2xl sm:text-3xl font-bold text-blue-700 mb-8">Your Profile</h1>

            <?php // Display Success Message (from update) ?>
            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md relative mb-6" role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline"><?php echo escape_html($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php // Display Errors (from update attempt) ?>
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


            <div class="profile-info space-y-4">
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-4">
                    <div class="sm:col-span-1">
                        <dt>Username:</dt>
                    </div>
                    <div class="sm:col-span-2">
                        <dd><?php echo $username; ?></dd>
                    </div>

                    <div class="sm:col-span-1">
                        <dt>Email Address:</dt>
                    </div>
                    <div class="sm:col-span-2">
                        <dd><?php echo $email; ?></dd>
                    </div>

                     <div class="sm:col-span-1">
                        <dt>Member Since:</dt>
                    </div>
                    <div class="sm:col-span-2">
                        <dd><?php echo $join_date_formatted; ?></dd>
                    </div>
                </dl>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <a href="update_profile.php"
                   class="inline-flex justify-center py-2 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    Update Profile or Password
                </a>
            </div>

        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>