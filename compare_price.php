<?php
// File: compare_price.php (Modified)
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse and compare prices for products on the Government e-Marketplace (GeM) with other major e-commerce platforms like Amazon and Flipkart.">
    <title><?php echo escape_html(SITE_NAME); ?> - Browse Product Comparisons</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="favicon.ico" sizes="any"><link rel="icon" href="images/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <meta property="og:title" content="<?php echo escape_html(SITE_NAME); ?>">
    <meta property="og:description" content="Browse and compare GeM product prices with other e-marketplaces.">
    <meta property="og:type" content="website"><meta property="og:url" content="<?php echo escape_html(SITE_URL); ?>">
    <style>
        /* Basic Styling for initial state */
        .product-card .price-comparison-area { display: none; } /* Hide price area initially */
        .product-card.loading .compare-button { display: none; } /* Hide button when loading */
        .product-card.loading .price-comparison-area.loading-state { display: block; text-align: center; padding: 20px;} /* Show loading state */
        .product-card.loaded .compare-button { display: none; } /* Hide button when loaded */
        .product-card.loaded .price-comparison-area { display: block; } /* Show price area when loaded */

        #product-listing-section h2 { margin-bottom: 1.5em; text-align: center; }
        #initial-loader { margin-top: 40px; }
    </style>
</head>
<body>
    
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main id="main-content">
    <!-- Product Listing Section -->
    <section id="product-listing-section" class="content-section">
            <div class="container">
                <h2 id="listing-heading">Product Comparisons</h2>
                <div id="product-listing-container">
                    <!-- Initial Loading Indicator -->
                    <div id="initial-loader" class="loader" aria-label="Loading initial products"></div>
                    <!-- Products will be dynamically loaded here -->
                </div>
            </div>
        </section>
        </main>

        <body data-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="js/script.js?v=<?php echo filemtime(__DIR__ . '/js/script.js'); ?>"></script>
</body>
</html>