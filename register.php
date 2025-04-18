<?php
session_start();
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/functions.php';

// If user is already logged in, redirect
if (isset($_SESSION['user_id'])) {
    redirect('index.php');
    exit;
}

$page_title = "Register - " . escape_html(SITE_NAME);
$errors = $_SESSION['register_errors'] ?? [];
$old_input = $_SESSION['old_input'] ?? []; // Preserve input on error
unset($_SESSION['register_errors']);
unset($_SESSION['old_input']);

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
        .auth-container { max-width: 450px; margin: 60px auto; padding: 30px; background-color: #fff; border-radius: var(--border-radius); box-shadow: var(--box-shadow-medium); border: 1px solid var(--medium-gray); }
        .auth-container h1 { text-align: center; margin-bottom: 1.5em; color: var(--primary-color); }
        .error-message { background-color: var(--danger-bg-light); color: var(--danger-color); border: 1px solid darken(var(--danger-bg-light), 10%); padding: 10px; border-radius: var(--border-radius); margin-bottom: 1em; font-size: 0.9rem; }
        .error-message ul { list-style: none; padding: 0; margin: 0; }
        .form-switch-link { text-align: center; margin-top: 20px; font-size: 0.9rem; }
        .password-rules { font-size: 0.85rem; color: var(--text-color-muted); margin-top: -10px; margin-bottom: 15px; padding-left: 5px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main id="main-content" class="container">
        <div class="auth-container">
            <h1>Create Account</h1>

             <?php if (!empty($errors)): ?>
                <div class="error-message" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo escape_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="php/register_handler.php" method="POST" id="register-form">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo escape_html($old_input['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo escape_html($old_input['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    <p class="password-rules">Minimum 8 characters.</p> <!-- Add more rules as needed -->
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <!-- Optional: Add CSRF token here -->
                 <div class="form-group">
                    <button type="submit" class="button button-primary" style="width: 100%;">Register</button>
                </div>
            </form>

             <div class="form-switch-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </main>
    <body data-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <!-- <script src="js/script.js?v=<?php //echo filemtime(__DIR__ . '/js/script.js'); ?>"></script> -->
</body>
</html>