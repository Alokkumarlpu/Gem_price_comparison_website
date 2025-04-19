<?php

define('IS_DEVELOPMENT', true); // Force development mode for now, change manually for production



if (IS_DEVELOPMENT) {
    // Development Database Settings
    define('DB_HOST', 'localhost');       // Or '127.0.0.1'
    define('DB_USERNAME', 'root');        // Default XAMPP/MAMP username, or 'gem_user' if created
    define('DB_PASSWORD', '');            // Default XAMPP/MAMP password, or 'your_strong_password'
    define('DB_NAME', 'gem_compare_db');  // Database name defined in schema.sql
    define('DB_PORT', 3306);              // Default MySQL port
} else {
    // Production Database Settings (Replace with actual production values)
    define('DB_HOST', 'your_production_db_host');
    define('DB_USERNAME', 'your_production_db_user');
    define('DB_PASSWORD', 'your_production_db_password');
    define('DB_NAME', 'your_production_db_name');
    define('DB_PORT', 3306);
}

// --- Site Configuration ---

define('SITE_NAME', 'CartClash');
// define('SITE_URL', IS_DEVELOPMENT ? 'http://localhost/gem-price-comparison' : 'https://your_production_domain.com'); // Adjust paths if needed
// define('ADMIN_EMAIL', 'admin@yourdomain.com'); // For error notifications or contact form destination

define('SITE_URL', 'http://localhost/gem_price-comparison'); 
define('EMAIL_FROM', 'no-reply@yourdomain.com'); 
define('PASSWORD_RESET_EXPIRY', 3600); // Default: 1 hour


// --- Error Reporting ---

if (IS_DEVELOPMENT) {
    // Show all errors during development for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1); 
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '/path/outside/webroot/logs/php-prod-errors.log'); // Replace with your actual log path
}

// --- Other Settings ---

define('DEFAULT_TIMEZONE', 'Asia/Kolkata'); // Set default timezone for date/time functions
date_default_timezone_set(DEFAULT_TIMEZONE);

define('RESULTS_PER_PAGE', 10); // Example setting for pagination, if implemented later


?>