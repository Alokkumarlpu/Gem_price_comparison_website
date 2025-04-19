<?php
// File: php/watchlist_handler.php

// Strict types and error reporting (disable display_errors in production)
declare(strict_types=1);
ini_set('display_errors', '0'); // Set to 1 for detailed errors during development ONLY
error_reporting(E_ALL);

ob_start(); // Use output buffering for header flexibility
session_start(); // Start or resume session

// Set headers *before* any potential output
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff'); // Prevent MIME type sniffing
header('X-Frame-Options: DENY'); // Prevent clickjacking

// Includes
// Adjust paths if these files are located elsewhere relative to watchlist_handler.php
require_once __DIR__ . '/config.php';          // Site configuration (DB constants, SITE_NAME, etc.)
require_once __DIR__ . '/functions.php';        // Helper functions (like output_json_and_exit if defined there)
// --- Response Setup ---
$response = ['success' => false];
$http_status_code = 200; // Default success code

// --- Authentication Check ---
// We NEED user_id for DB operations. Check it primarily.
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    // Check for inconsistency: header session set, but user_id missing (login script issue?)
    if (isset($_SESSION['account_loggedin']) && $_SESSION['account_loggedin'] === true) {
         error_log("Watchlist Handler Auth Error: User logged in (account_loggedin=true) but user_id is missing/invalid in session.");
         $response['error'] = 'Session synchronization error. Please log out and log back in.';
         $http_status_code = 409; // Conflict - session state inconsistency
    } else {
        // Standard authentication required error
        $response['error'] = 'Authentication required. Please log in.';
        $http_status_code = 401; // Unauthorized
    }
    output_json_and_exit($response, $http_status_code);
}
// If we reach here, $_SESSION['user_id'] is considered valid
$user_id = (int) $_SESSION['user_id']; // Cast to integer

// --- Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST', true, 405); // Indicate allowed method
    $response['error'] = 'Invalid request method. Only POST is accepted.';
    $http_status_code = 405; // Method Not Allowed
    output_json_and_exit($response, $http_status_code);
}

// --- Input Validation & Sanitization ---
// Use filter_input for better security and validation
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
$source_raw = $_POST['source'] ?? ''; // Get raw source input
$action_raw = $_POST['action'] ?? ''; // Get raw action input

$source = trim($source_raw);
$action = trim($action_raw);

// Validate inputs
$input_errors = [];
if ($product_id === false || $product_id === null) { // filter_input returns false on failure, null if not set
    $input_errors[] = 'Invalid or missing Product ID.';
}
if (empty($source)) {
    $input_errors[] = 'Source parameter is required.';
} elseif (strlen($source) > 50) { // Check against DB column size
     $input_errors[] = 'Source name is too long.';
}
// Optional: Add validation against a list of known sources if applicable
// $allowed_sources = ['Amazon', 'Flipkart', 'GeM'];
// if (!empty($source) && !in_array($source, $allowed_sources, true)) {
//     $input_errors[] = 'Invalid source specified.';
// }

if (empty($action) || ($action !== 'add' && $action !== 'remove')) {
    $input_errors[] = 'Invalid or missing action parameter (must be "add" or "remove").';
}

if (!empty($input_errors)) {
    $response['error'] = implode(' ', $input_errors);
    $http_status_code = 400; // Bad Request
    output_json_and_exit($response, $http_status_code);
}

// Sanitize source *after* validation. Using htmlspecialchars is generally safer.
$sanitized_source = htmlspecialchars($source, ENT_QUOTES, 'UTF-8');

// --- Database Operations ---
$conn = null;
try {
    $conn = connect_db();
    if ($conn === null) {
        error_log("Watchlist Handler: DB Connection failed using connect_db().");
        throw new Exception('Database service is currently unavailable.');
    }
    // Set charset to prevent SQL injection issues with some multi-byte chars bypasses
    $conn->set_charset('utf8mb4');

    if ($action === 'add') {
        // 1. Check if product exists
        $sql_check_prod = "SELECT product_id FROM products WHERE product_id = ? LIMIT 1";
        $stmt_check_prod = $conn->prepare($sql_check_prod);
        if ($stmt_check_prod === false) {
             throw new mysqli_sql_exception('Prepare Check Product failed: '.$conn->error);
        }
        $stmt_check_prod->bind_param('i', $product_id);
        if (!$stmt_check_prod->execute()) {
             $stmt_check_prod->close(); // Close on failure too
             throw new mysqli_sql_exception('Execute Check Product failed: '.$stmt_check_prod->error);
        }
        $result_check = $stmt_check_prod->get_result();
        $product_exists = ($result_check->num_rows === 1);
        $stmt_check_prod->close();

        if (!$product_exists) {
            $response['error'] = 'The specified product could not be found.';
            $http_status_code = 404; // Not Found
            output_json_and_exit($response, $http_status_code);
        }

        // 2. Add to watchlist using INSERT IGNORE
        $sql_insert = "INSERT IGNORE INTO watchlist_items (user_id, product_id, source, added_at) VALUES (?, ?, ?, NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        if ($stmt_insert === false) {
            throw new mysqli_sql_exception('Prepare INSERT failed: '.$conn->error);
        }
        // Bind parameters: user_id (int), product_id (int), source (string)
        $stmt_insert->bind_param('iis', $user_id, $product_id, $sanitized_source);

        if (!$stmt_insert->execute()) {
            $err_msg = $stmt_insert->error;
            $stmt_insert->close();
            error_log("Execute INSERT failed: ".$err_msg . " | UserID: $user_id, ProdID: $product_id, Source: $sanitized_source");
            throw new mysqli_sql_exception('Could not add item to watchlist due to a database issue.');
        }

        $rows_affected = $stmt_insert->affected_rows;
        $stmt_insert->close();

        $response['success'] = true;
        $response['action'] = 'added';
        if ($rows_affected > 0) {
            $response['message'] = 'Item added to your watchlist.';
            $http_status_code = 201; // Created
        } else {
            $response['message'] = 'Item is already in your watchlist.';
            $http_status_code = 200; // OK (already exists is not an error here)
        }

    } elseif ($action === 'remove') {
        $sql_delete = "DELETE FROM watchlist_items WHERE user_id = ? AND product_id = ? AND source = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        if ($stmt_delete === false) {
            throw new mysqli_sql_exception('Prepare DELETE failed: '.$conn->error);
        }
        // Bind parameters: user_id (int), product_id (int), source (string)
        $stmt_delete->bind_param('iis', $user_id, $product_id, $sanitized_source);

        if (!$stmt_delete->execute()) {
            $err_msg = $stmt_delete->error;
            $stmt_delete->close();
            error_log("Execute DELETE failed: ".$err_msg . " | UserID: $user_id, ProdID: $product_id, Source: $sanitized_source");
            throw new mysqli_sql_exception('Could not remove item from watchlist due to a database issue.');
        }

        $rows_affected = $stmt_delete->affected_rows;
        $stmt_delete->close();

        $response['success'] = true;
        $response['action'] = 'removed';
        if ($rows_affected > 0) {
            $response['message'] = 'Item removed from your watchlist.';
            $http_status_code = 200; // OK - resource deleted or state achieved
        } else {
            $response['message'] = 'Item was not found in your watchlist (perhaps already removed).';
            $http_status_code = 200; // OK - state is achieved (item is not there)
            // Alternative for strictness: $http_status_code = 404; $response['success']=false; $response['error']= 'Item not found...';
        }
    }
    // No else needed due to prior action validation

} catch (mysqli_sql_exception $e_sql) {
    error_log("SQL Error Watchlist Handler: [{$e_sql->getCode()}] {$e_sql->getMessage()}");
    $response['error'] = 'A database error occurred processing your request. Please try again later.'; // User-friendly message
    $http_status_code = 500; // Internal Server Error
} catch (Exception $e) {
    error_log("General Error Watchlist Handler: " . $e->getMessage());
    $response['error'] = $e->getMessage(); // Use the specific message if it's user-friendly
    $http_status_code = ($e->getMessage() === 'Database service is currently unavailable.') ? 503 : 500; // 503 Service Unavailable vs 500 Generic
} finally {
    // Ensure connection is always closed if it was opened and active
    if ($conn instanceof mysqli && $conn->ping()) {
        $conn->close();
    }
}

// --- Output Response ---
output_json_and_exit($response, $http_status_code);


/**
 * Outputs JSON response and terminates the script.
 * Ensures 'success' key reflects the status code if not explicitly set.
 * Cleans output buffer before sending.
 *
 * @param array $data The data array to encode as JSON.
 * @param int $statusCode The HTTP status code to set. Defaults to 200.
 * @return void This function exits.
 */
function output_json_and_exit(array $data, int $statusCode = 200): void {
    if (ob_get_level() > 0) {
        ob_end_clean(); // Clean buffer before output
    }
    http_response_code($statusCode);
    // Automatically set 'success' based on status code if not already set
    if (!isset($data['success'])) {
        $data['success'] = ($statusCode >= 200 && $statusCode < 300);
    }
    // Ensure success is false if an error message exists
    if (isset($data['error']) && $data['success'] !== false) {
         $data['success'] = false;
    }

    // Prevent caching of API responses, especially errors
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

    // Encode and output
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); // Pretty print for easier debugging if needed
    exit;
}
?>