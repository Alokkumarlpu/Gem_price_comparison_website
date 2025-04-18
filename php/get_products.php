<?php
// File: php/get_products.php (Modified)

/**
 * Fetches a default list of basic product information (ID, name, description, image)
 * to display on the homepage initially. Prices are fetched separately on demand.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$limit = 20; // Number of products to fetch by default
$results = [];
$conn = null;

try {
    $conn = connect_db();
    if ($conn === null) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed.']);
        exit;
    }

    // --- Fetch Default Products (Basic Info Only) ---
    $sql_products = "SELECT product_id, name, description, base_image_url
                     FROM products
                     ORDER BY product_id ASC -- Or created_at DESC, depending on desired order
                     LIMIT ?"; // Fetching first 20 by ID based on sample data

    $stmt_products = $conn->prepare($sql_products);
     if ($stmt_products === false) {
        throw new Exception("Failed to prepare default product fetch statement: " . $conn->error);
    }
    $stmt_products->bind_param('i', $limit);
    $stmt_products->execute();
    $result_products = $stmt_products->get_result();

    while ($product = $result_products->fetch_assoc()) {
        $results[] = [
            'id' => $product['product_id'],
            'name' => $product['name'],
            'description' => $product['description'] ?? 'No description.',
            'image_url' => $product['base_image_url'] ?? 'images/placeholder.png',
        ];
    }
    $stmt_products->close();

    if ($conn) {
        $conn->close();
    }

    echo json_encode(['results' => $results], JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    error_log("Get Products Error: " . $e->getMessage());
    if ($conn && $conn->ping()) { $conn->close(); }
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred fetching products.']);
    exit;
} catch (mysqli_sql_exception $e_sql) {
     error_log("SQL Error in Get Products: " . $e_sql->getMessage() . " (Code: " . $e_sql->getCode() . ")");
    if ($conn && $conn->ping()) { $conn->close(); }
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred fetching products.']);
    exit;
}
?>