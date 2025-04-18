<?php
// Include necessary files relative to the '/pages' directory
require_once __DIR__ . '/../php/config.php'; // Go up one level for php folder
require_once __DIR__ . '/../php/functions.php';

// Page specific variables
$page_title = "How It Works - " . escape_html(SITE_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Understand the simple steps to use the GeM Price Comparison tool: search for products, compare prices fetched from GeM and other platforms, and make informed decisions.">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Favicon -->
    <link rel="icon" href="../favicon.ico" sizes="any">
    <link rel="icon" href="../images/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../images/apple-touch-icon.png">

    <!-- Main Stylesheet - Adjust path -->
    <link rel="stylesheet" href="../css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Optional: Add page-specific styles here or in a separate CSS file -->
    <style>
        /* Styles for .step-detail, .step-number-inline, .note etc. */
        /* should be defined in the main style.css under */
        /* --- 12. Page Specific Styles (Shared) --- or similar section */

        /* Example of adding an extra style if needed */
        .data-sources-section {
            background-color: var(--light-gray);
            padding: 40px 20px;
            margin-top: 40px;
            border-radius: var(--border-radius);
            border: 1px solid var(--medium-gray);
        }
         .data-sources-section h2 {
             text-align: center;
             margin-bottom: 1.5em;
         }
         .data-sources-section p {
             text-align: center;
             max-width: 750px;
             margin: 0 auto 1em;
             color: var(--text-color-muted);
         }
          .data-sources-section .disclaimer { /* Reuse from style.css but ensure context */
            font-size: 0.9rem;
            color: #6c757d;
            text-align: center;
            margin-top: 25px;
            font-style: italic;
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/../includes/header.php'; // Adjust path for include ?>

    <main id="main-content">

        <header class="page-header">
            <div class="container">
                <h1>How <?php echo escape_html(SITE_NAME); ?> Works</h1>
            </div>
        </header>

        <!-- Use .page-content-wrapper for consistent max-width and padding -->
        <!-- Use .how-it-works-content class for specific styles if needed -->
        <div class="page-content-wrapper how-it-works-content container">

            <p style="text-align: center; font-size: 1.1em; margin-bottom: 2.5em;">Using our tool is straightforward. Follow these simple steps to compare prices effectively:</p>

            <div class="step-detail">
                <h3><span class="step-number-inline">1</span>Enter Your Search Query</h3>
                <p>
                    Start by typing the name, keyword, model number, or relevant identifier of the product you are interested in into the search bar located prominently on our homepage. You can be specific (e.g., "Dell Latitude 7400") or more general (e.g., "office printer").
                </p>
                <p>
                    The more specific your query, the more targeted the results are likely to be. However, broader terms can also help you discover related products.
                </p>
            </div>

            <div class="step-detail">
                 <h3><span class="step-number-inline">2</span>We Fetch and Compare Prices</h3>
                 <p>
                    Once you click the 'Search Prices' button, our system processes your query and searches our database for matching or similar products. Our database aims to contain information from:
                </p>
                <ul>
                    <li>The Government e-Marketplace (GeM)</li>
                    <li>Major commercial e-commerce platforms (such as Amazon.in, Flipkart, etc.)</li>
                </ul>
                 <p>
                    For each product match, we retrieve the latest available price information stored in our system from these different sources, along with other relevant details like product names, images, and direct links.
                 </p>
                 <div class="note">
                     <strong>Data Source & Freshness:</strong> Our comparison relies on the data currently stored in our database. This data is updated periodically. We do not perform live, real-time scraping of external websites with every search due to technical complexities and ethical considerations. Therefore, price and availability data may have some latency compared to the live marketplace.
                 </div>
            </div>

             <div class="step-detail">
                 <h3><span class="step-number-inline">3</span>View Comparison Results</h3>
                <p>
                    The search results are presented clearly on the page below the search bar. For each product found with comparable data, you'll typically see:
                </p>
                 <ul>
                    <li>A product image, name, and brief description (if available).</li>
                    <li>The price found on <strong>GeM</strong>, with a direct link to the GeM product page (if available).</li>
                    <li>The price found on another major <strong>e-commerce platform</strong> (e.g., Amazon, Flipkart), with a direct link to that product page (if available).</li>
                    <li>The lower price between the sources is often highlighted visually (e.g., with a green border or background).</li>
                    <li>The calculated price difference, helping you quickly identify potential savings.</li>
                </ul>
                 <p>
                    This side-by-side format allows you to quickly assess the price landscape for the product across the compared marketplaces based on our collected data.
                 </p>
            </div>

            <div class="step-detail">
                <h3><span class="step-number-inline">4</span>Make Informed Decisions</h3>
                 <p>
                    Use the comparison results as a starting point for your purchasing decision or analysis. Crucially, you should:
                 </p>
                 <ul>
                    <li><strong>Click the provided links</strong> to visit the official product pages on GeM and the other marketplaces.</li>
                    <li><strong>Verify the current price,</strong> product specifications, seller information, delivery timelines, and stock availability directly on the source websites.</li>
                    <li>Read seller reviews and product ratings on the marketplaces.</li>
                    <li>Consider factors beyond price, such as warranty, return policies, and specific configurations required.</li>
                 </ul>
                 <p>
                    Our goal is to provide transparency and save you time in the initial comparison phase, empowering you to make well-informed choices.
                 </p>
                  <div class="note">
                     <strong>Verification is Key:</strong> Remember that online prices and availability can change very rapidly. Always confirm all details on the vendor's official website before completing any transaction or finalizing any report based on this tool's data.
                 </div>
            </div>

             <div class="data-sources-section">
                 <h2>Data Sources & Accuracy Commitment</h2>
                 <p>
                     We strive to maintain an accurate and up-to-date database by regularly fetching information from public sources. However, the dynamic nature of e-commerce means discrepancies can occur. We continuously work to improve our data collection methods and accuracy within the bounds of technical feasibility and ethical guidelines.
                 </p>
                 <p class="disclaimer">
                    This tool is provided for informational purposes only and does not constitute financial or purchasing advice. We are not liable for any decisions made based on the data presented here.
                 </p>
             </div>

        </div> <!-- End .page-content-wrapper -->

    </main>
    <body data-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <?php include __DIR__ . '/../includes/footer.php'; // Adjust path for include ?>

    <!-- Main JavaScript File - Adjust path -->
    <script src="../js/script.js?v=<?php echo filemtime(__DIR__ . '/../js/script.js'); ?>"></script>

    <!-- Optional: Add page-specific JS here -->

</body>
</html>