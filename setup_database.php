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

/**
 * Database Setup Script - gem-price-comparison
 *
 * WARNING: This script creates/recreates database tables and inserts data.
 * DO NOT RUN ON A PRODUCTION SERVER WITH LIVE DATA unless you know exactly what you are doing.
 * It's intended for initial development setup. Consider deleting or renaming after use.
 */

// Start output buffering to prevent premature output interfering with potential headers (though unlikely here)
ob_start();

// Include configuration for DB credentials
require_once __DIR__ . '/php/config.php';

// --- Configuration ---
$db_host = DB_HOST;
$db_user = DB_USERNAME;
$db_pass = DB_PASSWORD;
$db_name = DB_NAME; // The target database name from config

// --- HTML Output Helper ---
function echo_status(string $message, bool $is_error = false): void {
    $color = $is_error ? 'red' : 'green';
    echo "<p style='color: $color; margin: 5px 0; font-family: sans-serif;'>$message</p>";
    flush(); // Try to flush output buffer
    ob_flush();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Setup</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.5; }
        h1 { border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .warning { color: orange; font-weight: bold; border: 1px solid orange; padding: 10px; margin-bottom: 15px; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        code { background-color: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>GeM Price Comparison - Database Setup</h1>
    <p class="warning"><strong>Warning:</strong> This script will attempt to create the database and tables, potentially overwriting existing ones if run carelessly. Use only for initial setup.</p>
    <hr>

<?php

// --- Stage 1: Connect to MySQL Server & Create Database ---
echo_status("Attempting to connect to MySQL server at <code>{$db_host}</code>...");
// Use error suppression for connect, check error manually
$conn = @new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    echo_status("Connection Failed: (" . $conn->connect_errno . ") " . $conn->connect_error, true);
    echo "<p class='error'>Setup aborted. Please check database credentials in <code>php/config.php</code> and ensure the MySQL server is running.</p>";
    exit; // Stop execution
}
echo_status("Successfully connected to MySQL server.");

echo_status("Attempting to create database <code>{$db_name}</code> if it doesn't exist...");
$sql_create_db = "CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql_create_db) === TRUE) {
    echo_status("Database <code>{$db_name}</code> checked/created successfully.");
} else {
    echo_status("Error creating database: " . $conn->error, true);
    $conn->close();
    exit;
}

// Close initial connection
$conn->close();
echo_status("Initial server connection closed.");

// --- Stage 2: Connect to the Specific Database ---
echo_status("Attempting to connect to database <code>{$db_name}</code>...");
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo_status("Connection to database <code>{$db_name}</code> Failed: (" . $conn->connect_errno . ") " . $conn->connect_error, true);
    echo "<p class='error'>Setup aborted. Unable to connect to the specific database even after creation attempt.</p>";
    exit; // Stop execution
}
echo_status("Successfully connected to database <code>{$db_name}</code>.");

// --- Stage 3: Create Tables ---
echo_status("Attempting to create tables...");

// SQL for creating tables (Copied from schema.sql - better to read file, but embedding for simplicity here)
$sql_create_tables = "
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

// Execute the create table statements
if ($conn->multi_query($sql_create_tables)) {
    do {
        // Store first result set handled automatically by multi_query success check
        if ($conn->more_results()) {
             // Check if there are more results and advance pointer
            if (!$conn->next_result()) {
                echo_status("Error advancing to next result set: " . $conn->error, true);
                break; // Exit loop on error
            }
        } else {
            break; // Exit loop if no more results
        }
    } while (true);
    echo_status("Tables <code>products</code> and <code>prices</code> checked/created successfully.");
} else {
    echo_status("Error creating tables: " . $conn->error, true);
    $conn->close();
    exit;
}

// --- Stage 4: Clear Existing Data (Optional but recommended for rerunning setup) ---
echo_status("Attempting to clear existing data from tables (if any)...");
$errors_clearing = false;
if ($conn->query("DELETE FROM prices") === TRUE) {
    echo_status("Cleared data from <code>prices</code>.");
} else {
    echo_status("Error clearing <code>prices</code>: " . $conn->error, true); $errors_clearing = true;
}
if ($conn->query("DELETE FROM products") === TRUE) {
     echo_status("Cleared data from <code>products</code>.");
} else {
    echo_status("Error clearing <code>products</code>: " . $conn->error, true); $errors_clearing = true;
}
// Reset AUTO_INCREMENT counters
$conn->query("ALTER TABLE products AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE prices AUTO_INCREMENT = 1");
echo_status("Reset AUTO_INCREMENT counters.");

if ($errors_clearing) {
    echo "<p class='error'>Errors occurred during data clearing. Please check manually.</p>";
    $conn->close();
    exit;
}

// --- Stage 5: Insert Sample Data ---
echo_status("Attempting to insert sample data...");

// SQL for inserting data (Copied from previous response)
$sql_insert_data = "
INSERT INTO `products` (`product_id`, `name`, `description`, `category`, `base_image_url`) VALUES
(1, 'Fjallraven - Foldsack No. 1 Backpack, Fits 15 Laptops', 'Your perfect pack for everyday use and walks in the forest. Stash your laptop (up to 15 inches) in the padded sleeve, your everyday', 'men\'s clothing', 'https://fakestoreapi.com/img/81fPKd-2AYL._AC_SL1500_.jpg'),
(2, 'Mens Casual Premium Slim Fit T-Shirts', 'Slim-fitting style, contrast raglan long sleeve, three-button henley placket, light weight & soft fabric for breathable and comfortable wearing.', 'men\'s clothing', 'https://fakestoreapi.com/img/71-3HjGNDUL._AC_SY879._SX._UX._SY._UY_.jpg'),
(3, 'Mens Cotton Jacket', 'great outerwear jackets for Spring/Autumn/Winter, suitable for many occasions, such as working, hiking, camping, mountain/rock climbing, cycling, traveling or other outdoors.', 'men\'s clothing', 'https://fakestoreapi.com/img/71li-ujtlUL._AC_UX679_.jpg'),
(4, 'Mens Casual Slim Fit', 'The color could be slightly different between on the screen and in practice. / Please note that body builds vary by person.', 'men\'s clothing', 'https://fakestoreapi.com/img/71YXzeOuslL._AC_UY879_.jpg'),
(5, 'John Hardy Women\'s Legends Naga Gold & Silver Dragon Station Chain Bracelet', 'From our Legends Collection, the Naga was inspired by the mythical water dragon that protects the ocean\'s pearl.', 'jewelery', 'https://fakestoreapi.com/img/71pWzhdJNwL._AC_UL640_QL65_ML3_.jpg'),
(6, 'Solid Gold Petite Micropave', 'Satisfaction Guaranteed. Return or exchange any order within 30 days.Designed and sold by Hafeez Center in the United States.', 'jewelery', 'https://fakestoreapi.com/img/61sbMiUnoGL._AC_UL640_QL65_ML3_.jpg'),
(7, 'White Gold Plated Princess', 'Classic Created Wedding Engagement Solitaire Diamond Promise Ring for Her. Gifts to spoil your love more for Engagement, Wedding, Anniversary.', 'jewelery', 'https://fakestoreapi.com/img/71YAIFU48IL._AC_UL640_QL65_ML3_.jpg'),
(8, 'Pierced Owl Rose Gold Plated Stainless Steel Double', 'Rose Gold Plated Double Flared Tunnel Plug Earrings. Made of 316L Stainless Steel', 'jewelery', 'https://fakestoreapi.com/img/51UDEzMJVpL._AC_UL640_QL65_ML3_.jpg'),
(9, 'WD 2TB Elements Portable External Hard Drive - USB 3.0', 'USB 3.0 and USB 2.0 Compatibility Fast data transfers Improve PC Performance High Capacity; Compatibility Formatted NTFS for Windows.', 'electronics', 'https://fakestoreapi.com/img/61IBBVJvSDL._AC_SY879_.jpg'),
(10, 'SanDisk SSD PLUS 1TB Internal SSD - SATA III 6 Gb/s', 'Easy upgrade for faster boot up, shutdown, application load and response. SATA III 6 Gb/s interface.', 'electronics', 'https://fakestoreapi.com/img/61U7T1koQqL._AC_SX679_.jpg'),
(11, 'Silicon Power 256GB SSD 3D NAND A55 SLC Cache Performance Boost SATA III 2.5', '3D NAND flash are applied to deliver high transfer speeds Remarkable transfer speeds that enable faster bootup.', 'electronics', 'https://fakestoreapi.com/img/71kWymZ+c+L._AC_SX679_.jpg'),
(12, 'WD 4TB Gaming Drive Works with Playstation 4 Portable External Hard Drive', 'Expand your PS4 gaming experience, Play anywhere Fast and easy, setup Sleek design with high capacity.', 'electronics', 'https://fakestoreapi.com/img/61mtL65D4cL._AC_SX679_.jpg'),
(13, 'Acer SB220Q bi 21.5 inches Full HD (1920 x 1080) IPS Ultra-Thin', '21. 5 inches Full HD (1920 x 1080) widescreen IPS display And Radeon free Sync technology. No compatibility for VESA Mount.', 'electronics', 'https://fakestoreapi.com/img/81QpkIctqPL._AC_SX679_.jpg'),
(14, 'Samsung 49-Inch CHG90 144Hz Curved Gaming Monitor (LC49HG90DMNXZA) – Super Ultrawide Screen QLED', '49 INCH SUPER ULTRAWIDE 32:9 CURVED GAMING MONITOR with dual 27 inch screen side by side QUANTUM DOT (QLED) TECHNOLOGY.', 'electronics', 'https://fakestoreapi.com/img/81Zt42ioCgL._AC_SX679_.jpg'),
(15, 'BIYLACLESEN Women\'s 3-in-1 Snowboard Jacket Winter Coats', 'Note:The Jackets is US standard size, Please choose size as your usual wear Material: 100% Polyester; Detachable Liner Fabric: Warm Fleece.', 'women\'s clothing', 'https://fakestoreapi.com/img/51Y5NI-I5jL._AC_UX679_.jpg'),
(16, 'Lock and Love Women\'s Removable Hooded Faux Leather Moto Biker Jacket', '100% POLYURETHANE(shell) 100% POLYESTER(lining) 75% POLYESTER 25% COTTON (SWEATER), Faux leather material for style and comfort.', 'women\'s clothing', 'https://fakestoreapi.com/img/81XH0e8fefL._AC_UY879_.jpg'),
(17, 'Rain Jacket Women Windbreaker Striped Climbing Raincoats', 'Lightweight perfet for trip or casual wear---Long sleeve with hooded, adjustable drawstring waist design. Button and zipper front closure raincoat.', 'women\'s clothing', 'https://fakestoreapi.com/img/71HblAHs5xL._AC_UY879_-2.jpg'),
(18, 'MBJ Women\'s Solid Short Sleeve Boat Neck V', '95% RAYON 5% SPANDEX, Made in USA or Imported, Do Not Bleach, Lightweight fabric with great stretch for comfort.', 'women\'s clothing', 'https://fakestoreapi.com/img/71z3kpMAYsL._AC_UY879_.jpg'),
(19, 'Opna Women\'s Short Sleeve Moisture', '100% Polyester, Machine wash, 100% cationic polyester interlock, Machine Wash & Pre Shrunk for a Great Fit.', 'women\'s clothing', 'https://fakestoreapi.com/img/51eg55uWmdL._AC_UX679_.jpg'),
(20, 'DANVOUY Womens T Shirt Casual Cotton Short', '95%Cotton,5%Spandex, Features: Casual, Short Sleeve, Letter Print,V-Neck,Fashion Tees, The fabric is soft and has some stretch.', 'women\'s clothing', 'https://fakestoreapi.com/img/61pHAEJ4NML._AC_UX679_.jpg');

INSERT INTO `prices` (`product_id`, `source`, `price`, `currency`, `is_available`, `fetched_at`) VALUES
(1, 'GeM', 8650.00, 'INR', TRUE, NOW() - INTERVAL 2 HOUR),(1, 'Amazon', 8899.00, 'INR', TRUE, NOW() - INTERVAL 3 HOUR),(1, 'Flipkart', 8750.50, 'INR', TRUE, NOW() - INTERVAL 1 HOUR),
(2, 'GeM', 1850.00, 'INR', TRUE, NOW() - INTERVAL 1 DAY),(2, 'Amazon', 1750.00, 'INR', TRUE, NOW() - INTERVAL 1 DAY),(2, 'Flipkart', 1799.00, 'INR', FALSE, NOW() - INTERVAL 23 HOUR),(2, 'Myntra', 1775.00, 'INR', TRUE, NOW() - INTERVAL 1 DAY),
(3, 'GeM', 4399.00, 'INR', TRUE, NOW() - INTERVAL 5 HOUR),(3, 'Amazon', 4599.00, 'INR', TRUE, NOW() - INTERVAL 6 HOUR),(3, 'Flipkart', 4490.00, 'INR', TRUE, NOW() - INTERVAL 4 HOUR),(3, 'Myntra', 4550.00, 'INR', TRUE, NOW() - INTERVAL 5 HOUR),
(4, 'GeM', 1350.00, 'INR', FALSE, NOW() - INTERVAL 7 HOUR),(4, 'Amazon', 1299.00, 'INR', TRUE, NOW() - INTERVAL 8 HOUR),(4, 'Flipkart', 1250.00, 'INR', TRUE, NOW() - INTERVAL 6 HOUR),
(5, 'GeM', 56000.00, 'INR', TRUE, NOW() - INTERVAL 10 HOUR),(5, 'Amazon', 54990.00, 'INR', TRUE, NOW() - INTERVAL 11 HOUR),(5, 'Flipkart', NULL, 'INR', FALSE, NOW() - INTERVAL 9 HOUR),
(6, 'GeM', 13200.00, 'INR', TRUE, NOW() - INTERVAL 12 HOUR),(6, 'Amazon', 13650.00, 'INR', TRUE, NOW() - INTERVAL 13 HOUR),(6, 'Flipkart', 13500.00, 'INR', TRUE, NOW() - INTERVAL 11 HOUR),
(7, 'GeM', 850.00, 'INR', TRUE, NOW() - INTERVAL 14 HOUR),(7, 'Amazon', 799.00, 'INR', TRUE, NOW() - INTERVAL 15 HOUR),(7, 'Flipkart', 780.00, 'INR', TRUE, NOW() - INTERVAL 13 HOUR),
(8, 'GeM', 899.00, 'INR', TRUE, NOW() - INTERVAL 16 HOUR),(8, 'Amazon', 875.00, 'INR', FALSE, NOW() - INTERVAL 17 HOUR),(8, 'Flipkart', 910.00, 'INR', TRUE, NOW() - INTERVAL 15 HOUR),
(9, 'GeM', 5050.00, 'INR', TRUE, NOW() - INTERVAL 18 HOUR),(9, 'Amazon', 5199.00, 'INR', TRUE, NOW() - INTERVAL 19 HOUR),(9, 'Flipkart', 5150.00, 'INR', TRUE, NOW() - INTERVAL 17 HOUR),
(10, 'GeM', 8800.00, 'INR', TRUE, NOW() - INTERVAL 20 HOUR),(10, 'Amazon', 8699.00, 'INR', TRUE, NOW() - INTERVAL 21 HOUR),(10, 'Flipkart', 8750.00, 'INR', TRUE, NOW() - INTERVAL 19 HOUR),
(11, 'GeM', 8600.00, 'INR', FALSE, NOW() - INTERVAL 22 HOUR),(11, 'Amazon', 8790.00, 'INR', TRUE, NOW() - INTERVAL 23 HOUR),(11, 'Flipkart', 8710.00, 'INR', TRUE, NOW() - INTERVAL 21 HOUR),
(12, 'GeM', 9200.00, 'INR', TRUE, NOW() - INTERVAL 1 DAY),(12, 'Amazon', 9050.00, 'INR', TRUE, NOW() - INTERVAL 1 DAY),(12, 'Flipkart', 9150.00, 'INR', TRUE, NOW() - INTERVAL 23 HOUR),
(13, 'GeM', 48500.00, 'INR', TRUE, NOW() - INTERVAL 1 DAY),(13, 'Amazon', 47800.00, 'INR', TRUE, NOW() - INTERVAL 1 DAY),(13, 'Flipkart', 47500.00, 'INR', TRUE, NOW() - INTERVAL 1 DAY),
(14, 'GeM', 79500.00, 'INR', TRUE, NOW() - INTERVAL 2 DAY),(14, 'Amazon', 81000.00, 'INR', TRUE, NOW() - INTERVAL 2 DAY),(14, 'Flipkart', 80500.00, 'INR', FALSE, NOW() - INTERVAL 2 DAY),
(15, 'GeM', 4600.00, 'INR', TRUE, NOW() - INTERVAL 2 DAY),(15, 'Amazon', 4550.00, 'INR', TRUE, NOW() - INTERVAL 2 DAY),(15, 'Flipkart', NULL, 'INR', FALSE, NOW() - INTERVAL 2 DAY),(15, 'Myntra', 4499.00, 'INR', TRUE, NOW() - INTERVAL 2 DAY),
(16, 'GeM', 2350.00, 'INR', TRUE, NOW() - INTERVAL 3 DAY),(16, 'Amazon', 2450.00, 'INR', TRUE, NOW() - INTERVAL 3 DAY),(16, 'Flipkart', 2400.00, 'INR', TRUE, NOW() - INTERVAL 3 DAY),(16, 'Myntra', 2399.00, 'INR', TRUE, NOW() - INTERVAL 3 DAY),
(17, 'GeM', 3250.00, 'INR', TRUE, NOW() - INTERVAL 3 DAY),(17, 'Amazon', 3150.00, 'INR', TRUE, NOW() - INTERVAL 3 DAY),(17, 'Flipkart', 3199.00, 'INR', TRUE, NOW() - INTERVAL 3 DAY),
(18, 'GeM', 800.00, 'INR', TRUE, NOW() - INTERVAL 4 DAY),(18, 'Amazon', 780.00, 'INR', TRUE, NOW() - INTERVAL 4 DAY),(18, 'Flipkart', 775.00, 'INR', TRUE, NOW() - INTERVAL 4 DAY),
(19, 'GeM', 620.00, 'INR', TRUE, NOW() - INTERVAL 4 DAY),(19, 'Amazon', 650.00, 'INR', TRUE, NOW() - INTERVAL 4 DAY),(19, 'Flipkart', 640.00, 'INR', TRUE, NOW() - INTERVAL 4 DAY),(19, 'Myntra', 630.00, 'INR', FALSE, NOW() - INTERVAL 4 DAY),
(20, 'GeM', 1050.00, 'INR', TRUE, NOW() - INTERVAL 5 DAY),(20, 'Amazon', 1020.00, 'INR', TRUE, NOW() - INTERVAL 5 DAY),(20, 'Flipkart', 1030.00, 'INR', TRUE, NOW() - INTERVAL 5 DAY);
";

// Execute the insert statements using multi_query
if ($conn->multi_query($sql_insert_data)) {
     do {
        // Consume results to allow next query
        if ($result = $conn->store_result()) {
            $result->free();
        }
     } while ($conn->more_results() && $conn->next_result());
    echo_status("Sample data inserted successfully.");
} else {
    // Provide more specific error if possible
    echo_status("Error inserting sample data: " . $conn->error, true);
    echo_status("Problematic SQL might be near statement starting with: " . substr($conn->error, strpos($conn->error, 'near \'') + 6, 30) . "... Please check SQL syntax.", true);
}


// --- Finish ---
echo "<hr>";
echo "<p class='success'>Database setup process completed.</p>";
echo "<p>You can now visit the <a href='index.php'>homepage</a>.</p>";
echo "<p><strong>Remember to delete or rename this <code>setup_database.php</code> file from your server, especially in production!</strong></p>";

// Close final connection
$conn->close();

?>
</body>
</html>
<?php
// End output buffering and flush
ob_end_flush();
?>
