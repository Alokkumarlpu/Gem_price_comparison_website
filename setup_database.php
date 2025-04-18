<?php

set_time_limit(300); // 5 minutes

// --- Configuration and Output Buffer ---
ob_start(); // Start output buffering to capture messages nicely
echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Database Setup</title></head><body>";
echo "<h1>Database Setup Script</h1>";
echo "<pre style='background-color: #f0f0f0; border: 1px solid #ccc; padding: 10px; font-family: monospace; white-space: pre-wrap; word-wrap: break-word;'>"; // Preformatted block for output

// Include configuration (defines DB constants)
require_once __DIR__ . '/php/config.php';

// Check if DB constants are defined
if (!defined('DB_HOST') || !defined('DB_USERNAME') || !defined('DB_PASSWORD') || !defined('DB_NAME')) {
    output_message("ERROR: Database configuration constants (DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) are not defined in php/config.php.", 'error');
    finish_output();
    exit;
}

$db_host = DB_HOST;
$db_user = DB_USERNAME;
$db_pass = DB_PASSWORD;
$db_name = DB_NAME;
$db_port = defined('DB_PORT') ? DB_PORT : 3306; // Use port if defined

// --- Helper Function for Output ---
function output_message(string $message, string $type = 'info') {
    $color = match($type) {
        'success' => 'green',
        'error' => 'red',
        'warning' => 'orange',
        default => 'blue',
    };
    echo "<span style='color: $color; font-weight: bold;'>[" . strtoupper($type) . "]</span> " . htmlspecialchars($message) . "\n";
    ob_flush(); // Flush buffer incrementally
    flush();    // Send output to browser
}

// --- Step 1: Connect to MySQL Server (without selecting DB) ---
output_message("Attempting to connect to MySQL server at $db_host:$db_port...");
mysqli_report(MYSQLI_REPORT_OFF); // Disable default reporting, handle manually
$conn_server = @new mysqli($db_host, $db_user, $db_pass, '', $db_port);

if ($conn_server->connect_error) {
    output_message("ERROR: MySQL Server Connection Failed: (" . $conn_server->connect_errno . ") " . $conn_server->connect_error, 'error');
    finish_output();
    exit;
}
output_message("Successfully connected to MySQL server.", 'success');

// --- Step 2: Create Database if it doesn't exist ---
output_message("Checking if database '$db_name' exists...");
// Use backticks around database name in query in case it contains special characters
$create_db_sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

if ($conn_server->query($create_db_sql) === TRUE) {
    if ($conn_server->warning_count > 0) {
         $warnings = $conn_server->get_warnings(); do { output_message("Warning: " . $warnings->message, 'warning'); } while ($warnings->next());
         output_message("Database '$db_name' already exists or created with warnings.", 'warning');
    } else { output_message("Database '$db_name' created successfully or already exists.", 'success'); }
} else {
    output_message("ERROR: Could not create database '$db_name': " . $conn_server->error, 'error');
    $conn_server->close(); finish_output(); exit;
}
$conn_server->close();
output_message("Closed initial server connection.");

// --- Step 3: Connect to the Specific Database ---
output_message("Attempting to connect to database '$db_name'...");
$conn_db = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn_db->connect_error) {
    output_message("ERROR: Database Connection Failed: (" . $conn_db->connect_errno . ") " . $conn_db->connect_error, 'error');
    finish_output(); exit;
}
output_message("Successfully connected to database '$db_name'.", 'success');
if (!$conn_db->set_charset("utf8mb4")) { output_message("Warning: Error loading character set utf8mb4: " . $conn_db->error, 'warning');
} else { output_message("Database connection character set set to utf8mb4."); }

// --- Step 4: Define SQL Statements for Tables ---
$sql_statements = [];

// SQL for `products` table
$sql_statements['products'] = "
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the product',
  `name` VARCHAR(255) NOT NULL COMMENT 'Primary product name',
  `description` TEXT NULL COMMENT 'Detailed product description',
  `category` VARCHAR(100) NULL COMMENT 'Product category (e.g., Electronics, Furniture)',
  `brand` VARCHAR(100) NULL COMMENT 'Product brand',
  `model_number` VARCHAR(100) NULL COMMENT 'Product model number (if applicable)',
  `specifications` JSON NULL COMMENT 'Store product specifications as a JSON object for flexibility',
  `base_image_url` VARCHAR(2048) NULL COMMENT 'URL to a representative product image',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when the product record was created',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp when the product record was last updated',
  PRIMARY KEY (`product_id`),
  INDEX `idx_product_name` (`name`),
  INDEX `idx_product_category` (`category`),
  INDEX `idx_product_brand` (`brand`),
  FULLTEXT KEY `ft_name_desc` (`name`, `description`) COMMENT 'Full-text index for searching name and description'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores core product details';
";

// SQL for `prices` table (Foreign key depends on products)
$sql_statements['prices'] = "
CREATE TABLE IF NOT EXISTS `prices` (
  `price_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for this price entry',
  `product_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key linking to the products table',
  `source` VARCHAR(50) NOT NULL COMMENT 'Source marketplace (e.g., GeM, Amazon, Flipkart)',
  `price` DECIMAL(12, 2) NULL COMMENT 'Price of the product on this source (NULL if unavailable)',
  `currency` VARCHAR(3) NOT NULL DEFAULT 'INR' COMMENT 'Currency code (e.g., INR)',
  `product_url` VARCHAR(2048) NULL COMMENT 'Direct URL to the product page on the source marketplace',
  `seller_name` VARCHAR(255) NULL COMMENT 'Seller name on the marketplace',
  `rating` DECIMAL(3, 2) NULL COMMENT 'Product rating on the source (e.g., 4.5)',
  `rating_count` INT UNSIGNED NULL COMMENT 'Number of ratings/reviews',
  `is_available` BOOLEAN NULL COMMENT 'Flag indicating if the product is currently available/in stock (NULL if unknown)',
  `fetched_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when this price data was fetched/updated',
  PRIMARY KEY (`price_id`),
  INDEX `idx_price_product_id` (`product_id`),
  INDEX `idx_price_source` (`source`),
  INDEX `idx_fetched_at` (`fetched_at`),
  CONSTRAINT `fk_price_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `products` (`product_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores product prices from various sources and times';
";

// SQL for `users` table (Added)
$sql_statements['users'] = "
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the user',
  `username` VARCHAR(50) NOT NULL COMMENT 'User chosen username',
  `email` VARCHAR(255) NOT NULL COMMENT 'User email address, used for login/recovery',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed user password (use password_hash() in PHP)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when the user registered',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp when user info was last updated',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User account information';
";

// SQL for `watchlist_items` table (Added - Foreign keys depend on users and products)
$sql_statements['watchlist_items'] = "
CREATE TABLE IF NOT EXISTS `watchlist_items` (
  `item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the watchlist entry',
  `user_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key linking to the users table',
  `product_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key linking to the products table',
  `source` VARCHAR(50) NOT NULL COMMENT 'Which source listing is being watched (e.g., GeM, Amazon)',
  `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when the item was added',
  PRIMARY KEY (`item_id`),
  INDEX `idx_watchlist_user_id` (`user_id`),
  INDEX `idx_watchlist_product_id` (`product_id`),
  UNIQUE KEY `uq_user_product_source` (`user_id`, `product_id`, `source`),
  CONSTRAINT `fk_watchlist_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_watchlist_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `products` (`product_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Items saved by users to their watchlist';
";


// --- Step 5: Execute SQL Statements ---
output_message("Executing CREATE TABLE statements...");
$errors_occurred = false;
// Define execution order carefully due to foreign key constraints
$execution_order = ['products', 'users', 'prices', 'watchlist_items'];

foreach ($execution_order as $table_key) {
    if (!isset($sql_statements[$table_key])) {
        output_message("Warning: SQL definition for '$table_key' not found, skipping.", 'warning');
        continue;
    }
    $sql = $sql_statements[$table_key];
    output_message("Executing schema for table '$table_key'...");
    if ($conn_db->query($sql) === TRUE) {
        output_message("   -> Schema for '$table_key' executed successfully.", 'success');
    } else {
        output_message("   -> ERROR executing schema for '$table_key': " . $conn_db->error, 'error');
        output_message("   -> Failed SQL (snippet): " . htmlspecialchars(substr(trim($sql), 0, 150)) . "...", 'error');
        $errors_occurred = true;
        // Optional: break on first error, or continue trying other tables
        // break;
    }
}

// --- Step 6: Final Feedback ---
if ($errors_occurred) {
     output_message("Database setup completed with errors. Please review the messages above.", 'error');
} else {
     output_message("Database setup completed successfully!", 'success');
     output_message("!! IMPORTANT: Please REMOVE or RENAME this setup_database.php file now for security reasons !!", 'warning');
}

// Close the final database connection
$conn_db->close();
output_message("Database connection closed.");


// --- Finish Output ---
function finish_output() {
    echo "</pre>";
    echo "</body></html>";
    ob_end_flush(); // Send the buffered output
}
finish_output();
?>