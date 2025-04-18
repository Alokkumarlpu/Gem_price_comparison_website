<?php
// File: php/get_product_prices.php (New File)

/**
 * Get Product Prices Handler
 *
 * Fetches the latest price information for a *single* product ID
 * from different sources stored in the database.
 * Expects 'id' parameter via GET request.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// --- Input Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method. Please use GET.']);
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || (int)$_GET['id'] <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Valid Product ID is required.']);
    exit;
}

$product_id = (int)$_GET['id'];
$prices_data = []; // Array to hold price info for this product
$conn = null;

try {
    $conn = connect_db();
    if ($conn === null) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed.']);
        exit;
    }

    // --- Fetch Latest Prices for the specific product ID ---
    // Using ROW_NUMBER() window function to get the latest price per source
    $sql_prices = "SELECT source, price, product_url, is_available
                   FROM (
                       SELECT
                           p.source, p.price, p.product_url, p.is_available,
                           ROW_NUMBER() OVER(PARTITION BY p.source ORDER BY p.fetched_at DESC) as rn
                       FROM prices p
                       WHERE p.product_id = ?  -- Filter by the specific product ID
                   ) ranked_prices
                   WHERE rn = 1
                   ORDER BY FIELD(source, 'GeM', 'Amazon', 'Flipkart', 'Myntra'), source"; // Order sources predictably

    $stmt_prices = $conn->prepare($sql_prices);
    if ($stmt_prices === false) {
         throw new Exception("Failed to prepare price fetching statement: " . $conn->error);
    }

    $stmt_prices->bind_param('i', $product_id);
    $stmt_prices->execute();
    $result_prices = $stmt_prices->get_result();

    while ($price_row = $result_prices->fetch_assoc()) {
        $prices_data[] = [
            'source' => $price_row['source'],
            'price' => ($price_row['price'] !== null) ? (float)$price_row['price'] : null, // Ensure float or null
            'is_available' => (bool)$price_row['is_available'], // Ensure boolean
             // URL is intentionally omitted as requested
            // 'url' => $price_row['product_url']
        ];
    }
    $stmt_prices->close();

    if ($conn) {
        $conn->close();
    }

    // Return the array of price data for this product
    echo json_encode(['prices' => $prices_data], JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    error_log("Get Product Prices Error (ID: $product_id): " . $e->getMessage());
    if ($conn && $conn->ping()) { $conn->close(); }
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred fetching prices.']);
    exit;
} catch (mysqli_sql_exception $e_sql) {
     error_log("SQL Error in Get Product Prices (ID: $product_id): " . $e_sql->getMessage() . " (Code: " . $e_sql->getCode() . ")");
    if ($conn && $conn->ping()) { $conn->close(); }
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred fetching prices.']);
    exit;
}
?>