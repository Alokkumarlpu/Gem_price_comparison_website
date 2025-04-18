<?php
// File: watchlist.php

// Strict types and error reporting (disable display_errors in production)
declare(strict_types=1);
ini_set('display_errors', '0'); // Set to 1 for detailed errors during development ONLY
error_reporting(E_ALL);

session_start(); // Start or resume session

// Includes (adjust paths if needed)
require_once __DIR__ . '/php/config.php';          // Site configuration constants (like SITE_NAME)
require_once __DIR__ . '/php/functions.php';        // Helper functions (like escape_html, redirect, number_format?)
require_once __DIR__ . '/php/db_connect.php';       // Database connection function `connect_db()`
// require_once __DIR__ . '/gem/gem_db.php';        // GeM specific functions (if any needed)
// --- Authentication Check ---
// We NEED user_id for DB queries. Check it primarily.
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Save intended destination

    // Check for inconsistency: header session set, but user_id missing (login script issue?)
    if (isset($_SESSION['account_loggedin']) && $_SESSION['account_loggedin'] === true) {
         error_log("Watchlist Page Auth Error: User logged in (account_loggedin=true) but user_id is missing/invalid in session.");
         $_SESSION['login_message'] = 'Your session may have expired or is invalid. Please log in again.';
    } else {
        // Standard message if not logged in at all
        $_SESSION['login_message'] = 'Please log in to view your watchlist.';
    }
    // Use the redirect function (ensure it handles exit)
    redirect('login.php'); // Redirect to login page (adjust path if needed)
    exit; // Explicit exit just in case redirect doesn't
}

// --- User Data ---
$user_id = (int) $_SESSION['user_id']; // Cast to integer
// Fetch username consistently with header.php (prefer 'account_name')
$username = 'User'; // Default
if (!empty($_SESSION['account_name'])) { // Use !empty for check
    $username = escape_html($_SESSION['account_name']);
} elseif (!empty($_SESSION['username'])) { // Fallback to 'username' if 'account_name' isn't set
    $username = escape_html($_SESSION['username']);
}

// --- Page Setup ---
$site_name_escaped = defined('SITE_NAME') ? escape_html(SITE_NAME) : 'GeM Compare';
$page_title = "My Watchlist - " . $site_name_escaped;
$watchlist_items = [];
$errors = [];
$conn = null;

// --- Database Interaction: Fetch Watchlist Items ---
try {
    $conn = connect_db();
    if ($conn === null) {
        error_log("Watchlist Fetch: DB connection failed using connect_db().");
        throw new Exception('Could not connect to the database service.');
    }
    // Set charset
    $conn->set_charset('utf8mb4');

    // --- SQL Query (Verified - uses user_id which links to accounts.id) ---
    $sql = "
        SELECT
            wl.item_id, wl.product_id, wl.source AS watched_source, wl.added_at,
            p.name, p.description, p.base_image_url,
            lp_watched.price AS watched_price,
            lp_watched.product_url AS watched_url,
            lp_other.source AS other_source,
            lp_other.price AS other_price,
            lp_other.product_url AS other_url
        FROM watchlist_items wl
        JOIN products p ON wl.product_id = p.product_id
        LEFT JOIN (
            SELECT price_id, product_id, source, price, product_url
            FROM (
                SELECT pr.*, ROW_NUMBER() OVER(PARTITION BY pr.product_id, pr.source ORDER BY pr.fetched_at DESC) as rn
                FROM prices pr
            ) ranked_prices_watched
            WHERE rn = 1
        ) lp_watched ON wl.product_id = lp_watched.product_id AND wl.source = lp_watched.source
        LEFT JOIN (
             SELECT price_id, product_id, source, price, product_url
            FROM (
                SELECT pr.*, ROW_NUMBER() OVER(PARTITION BY pr.product_id, pr.source ORDER BY pr.fetched_at DESC) as rn
                FROM prices pr
            ) ranked_prices_other
            WHERE rn = 1
        ) lp_other ON wl.product_id = lp_other.product_id
                  AND wl.source != lp_other.source
                  AND lp_other.source = (
                        SELECT ps.source
                        FROM prices ps
                        WHERE ps.product_id = wl.product_id AND ps.source != wl.source
                        ORDER BY FIELD(ps.source, 'Amazon', 'Flipkart'), ps.fetched_at DESC -- Prioritize known stores
                        LIMIT 1
                  )
        WHERE wl.user_id = ? -- Filter by the logged-in user's ID
        ORDER BY wl.added_at DESC;
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Watchlist Fetch Prepare failed: " . $conn->error);
        throw new Exception('Error preparing watchlist query.');
    }

    $stmt->bind_param('i', $user_id); // Bind the integer user ID

    if (!$stmt->execute()) {
        $err_msg = $stmt->error;
        $stmt->close();
        error_log("Watchlist Fetch Execute failed: " . $err_msg);
        throw new Exception('Error executing watchlist query.');
    }

    $result = $stmt->get_result();
    // Fetch all results into the array
    $watchlist_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (mysqli_sql_exception $e_sql) {
    error_log("SQL Error Fetching Watchlist: [{$e_sql->getCode()}] {$e_sql->getMessage()}");
    $errors[] = "A database error occurred while retrieving your watchlist. Please try again later.";
} catch (Exception $e) {
    error_log("General Error Fetching Watchlist: " . $e->getMessage());
    // Give user-friendly messages based on the exception message
    $errors[] = match ($e->getMessage()) {
        'Could not connect to the database service.' => 'The watchlist service is temporarily unavailable. Please try again later.',
        'Error preparing watchlist query.', 'Error executing watchlist query.' => 'Could not retrieve your watchlist data at this time.',
        default => 'An unexpected error occurred while loading your watchlist.'
    };
} finally {
    // Ensure connection is always closed if it was opened and active
    if ($conn instanceof mysqli && $conn->ping()) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php /* Favicon links - Ensure paths are correct */ ?>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <?php /* Cache busting for CSS - Ensure path is correct */ ?>
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
    <?php /* Google Fonts */ ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <?php /* Inline styles for watchlist specific elements */ ?>
    <style>
        :root { /* Define CSS variables if not globally defined in style.css */
            --text-color-muted: #6c757d;
            --secondary-color: #ffc107;
            --primary-color: #007bff;
            --danger-color: #dc3545;
            --link-color: #0056b3;
            --card-bg: #ffffff;
            --card-border: #eeeeee;
            --card-subtle-bg: #f9f9f9;
            --body-bg: #f8f9fa; /* Example body background */
        }
        /* Basic watchlist styles */
        body { background-color: var(--body-bg); } /* Example */
        .watchlist-container { margin-top: 30px; }
        .watchlist-empty { text-align: center; margin-top: 40px; color: var(--text-color-muted); font-size: 1.1em; padding: 20px; }
        .watchlist-item { margin-bottom: 20px; }
        .product-card {
            border-left: 5px solid var(--secondary-color);
            background: var(--card-bg);
            padding: 15px 20px;
            border-radius: 5px;
            display: flex;
            flex-wrap: wrap; /* Allow wrapping on small screens */
            gap: 20px; /* Spacing between image and details */
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--card-border);
        }
        .product-image {
             flex: 0 0 100px; /* Fixed width for image container */
             align-self: flex-start; /* Align image to top */
        }
        .product-image img {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: 4px;
            border: 1px solid var(--card-border);
        }
        .product-details {
            flex: 1 1 300px; /* Allow details to grow and shrink */
            min-width: 250px; /* Prevent details from becoming too narrow */
        }
        .product-details h3 {
            margin: 0 0 8px 0;
            font-size: 1.2em;
            font-weight: 600;
            line-height: 1.3;
        }
        .watched-source-label {
            display: inline-block; /* Make it inline block */
            font-weight: bold;
            color: var(--secondary-color);
            margin-bottom: 12px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #fff3cd; /* Light yellow background */
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid var(--secondary-color);
        }
        .price-comparison {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .price-source {
            border: 1px solid var(--card-border);
            padding: 12px;
            border-radius: 4px;
            flex: 1 1 200px; /* Allow price boxes to grow/shrink */
            background-color: var(--card-subtle-bg);
             min-width: 180px; /* Minimum width for price boxes */
        }
        .price-source .platform-label {
            font-weight: bold;
            display: block;
            margin-bottom: 6px;
            font-size: 0.9em;
            color: #555;
        }
        .price-source .price-value {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--primary-color);
            display: block; /* Ensure price is on its own line */
            margin-bottom: 8px; /* Space below price */
        }
        .price-source .price-unavailable {
            font-style: italic;
            color: var(--text-color-muted);
             display: block;
            margin-bottom: 8px;
        }
        .price-actions {
            margin-top: auto; /* Push actions to bottom if price source has variable height */
            padding-top: 5px; /* Space above actions */
        }
        .visit-link {
            font-size: 0.85em;
            margin-right: 15px;
            color: var(--link-color);
            text-decoration: none;
            vertical-align: middle;
        }
        .visit-link:hover { text-decoration: underline; }
        /* Adjusted remove button style */
        .watchlist-btn.remove {
            background-color: var(--danger-color);
            color: white;
            border: 1px solid var(--danger-color);
            padding: 5px 10px; /* Slightly larger padding */
            font-size: 0.85em;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease, opacity 0.2s ease;
            vertical-align: middle; /* Align with text/links */
            line-height: 1; /* Ensure consistent height */
        }
        .watchlist-btn.remove:hover {
            background-color: #c82333; /* Darken danger color */
            border-color: #bd2130;
        }
         .watchlist-btn.remove:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        /* Removing animation */
        .watchlist-item.removing {
            transition: opacity 0.4s ease-out, max-height 0.5s ease-out, margin-top 0.4s ease-out, margin-bottom 0.4s ease-out, padding-top 0.4s ease-out, padding-bottom 0.4s ease-out, transform 0.4s ease-out;
            opacity: 0;
            max-height: 0px !important; /* Animate max-height to 0 */
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            border-width: 0 !important; /* Animate border away */
            transform: scaleY(0);
            transform-origin: top;
            overflow: hidden; /* Crucial for height animation */
        }
        /* Error message styling */
        .error-message {
            background-color: #f8d7da; color: #721c24;
            border: 1px solid #f5c6cb; padding: 15px 20px;
            border-radius: 5px; margin: 20px 0;
        }
         .error-message p:last-child { margin-bottom: 0; }

         /* Alert for JS failures */
         .js-alert {
             position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
             background-color: #dc3545; color: white; padding: 10px 20px;
             border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
             z-index: 1050; font-size: 0.9em; display: none; /* Hidden by default */
         }
    </style>
</head>
<body data-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; // Use user_id for functional check ?>">
    <?php include __DIR__ . '/includes/header.php'; // Include header - Ensure path is correct ?>

    <main id="main-content">
        <header class="page-header" style="background-color: #e9ecef; padding: 2rem 0; margin-bottom: 30px;"> <!-- Example styling -->
            <div class="container" style="max-width: 1140px; margin: 0 auto; padding: 0 15px;"> <!-- Basic container -->
                <h1>My Watchlist</h1>
                <p class="lead" style="color: #6c757d;">Products you are currently tracking from <?php echo $site_name_escaped; ?></p>
            </div>
        </header>

        <div class="container page-content-wrapper" style="max-width: 1140px; margin: 0 auto; padding: 0 15px;">
            <div class="watchlist-container" id="watchlist-listing-container"> <!-- Container for items -->

                <?php if (!empty($errors)): ?>
                    <div class="error-message" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo escape_html($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($errors) && empty($watchlist_items)): ?>
                    <p class="watchlist-empty">Your watchlist is currently empty. Start tracking products to see them here!</p>
                <?php elseif (!empty($watchlist_items)): ?>
                    <?php foreach ($watchlist_items as $item):
                        // Sanitize and prepare data for display within the loop
                        $productId = (int) $item['product_id'];
                        $watchedSource = escape_html($item['watched_source'] ?? 'N/A'); // Handle potential null
                        $productName = escape_html($item['name'] ?? 'Unnamed Product');
                        $imageUrl = escape_html($item['base_image_url'] ?? 'images/placeholder.png'); // Ensure placeholder path is valid
                        // Validate and format prices
                        $watchedPrice = ($item['watched_price'] !== null && is_numeric($item['watched_price'])) ? (float)$item['watched_price'] : null;
                        $watchedUrl = filter_var($item['watched_url'], FILTER_VALIDATE_URL) ? escape_html($item['watched_url']) : null;
                        // Other source details
                        $otherSource = (!empty($item['other_source'])) ? escape_html($item['other_source']) : null; // Use !empty check
                        $otherPrice = ($item['other_price'] !== null && is_numeric($item['other_price'])) ? (float)$item['other_price'] : null;
                        $otherUrl = filter_var($item['other_url'], FILTER_VALIDATE_URL) ? escape_html($item['other_url']) : null;

                        $watchedPriceValid = ($watchedPrice !== null);
                        $otherPriceValid = ($otherPrice !== null);
                        $hasOtherSource = ($otherSource !== null); // Check if a valid other source was found

                        // Create a safe, unique ID for the DOM element
                        $itemDomId = 'watchlist-item-' . $productId . '-' . preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', $watchedSource));
                    ?>
                    <div class="watchlist-item" id="<?php echo $itemDomId; ?>">
                        <article class="product-card"> <?php // Use article for semantic meaning ?>
                            <div class="product-image">
                                <img src="<?php echo $imageUrl; ?>" alt="Product image for <?php echo $productName; ?>" loading="lazy">
                            </div>
                            <div class="product-details">
                                <h3><?php echo $productName; ?></h3>
                                <span class="watched-source-label">Watching: <?php echo $watchedSource; ?></span>

                                <div class="price-comparison">
                                    <div class="price-source watched-price">
                                        <span class="platform-label"><?php echo $watchedSource; ?> Price:</span>
                                        <?php if ($watchedPriceValid): ?>
                                            <span class="price-value">₹<?php echo number_format($watchedPrice, 2); ?></span>
                                        <?php else: ?>
                                            <span class="price-unavailable">Price not available</span>
                                        <?php endif; ?>
                                        <div class="price-actions">
                                            <?php if ($watchedUrl): ?>
                                                <a href="<?php echo $watchedUrl; ?>" target="_blank" rel="noopener noreferrer nofollow" class="visit-link" title="Visit product page on <?php echo $watchedSource; ?>">
                                                    Visit <?php echo $watchedSource; ?>
                                                </a>
                                            <?php endif; ?>
                                            <button class="button button-small watchlist-btn remove"
                                                    data-product-id="<?php echo $productId; ?>"
                                                    data-source="<?php echo $watchedSource; // Use original non-sanitized for data attr if handler expects it, but sanitized is safer ?>"
                                                    data-action="remove"
                                                    aria-label="Remove <?php echo $productName; ?> (<?php echo $watchedSource; ?> listing) from your watchlist">
                                                Remove
                                            </button>
                                        </div>
                                    </div>

                                    <?php if ($hasOtherSource): // Only show if a valid other source/price was found ?>
                                    <div class="price-source other-price">
                                        <span class="platform-label"><?php echo $otherSource; ?> Price (Latest):</span>
                                        <?php if ($otherPriceValid): ?>
                                            <span class="price-value">₹<?php echo number_format($otherPrice, 2); ?></span>
                                        <?php else: ?>
                                            <span class="price-unavailable">Price not available</span>
                                        <?php endif; ?>
                                        <div class="price-actions">
                                            <?php if ($otherUrl): ?>
                                                <a href="<?php echo $otherUrl; ?>" target="_blank" rel="noopener noreferrer nofollow" class="visit-link" title="Visit product page on <?php echo $otherSource; ?>">
                                                    Visit <?php echo $otherSource; ?>
                                                </a>
                                            <?php endif; ?>
                                             <?php /* Optional: Add button to switch watching to this source? */ ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div> <?php // End price-comparison ?>
                            </div> <?php // End product-details ?>
                        </article> <?php // End product-card article ?>
                    </div> <?php // End watchlist-item ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div> <!-- /#watchlist-listing-container -->
        </div> <!-- /.container -->
    </main>

    <?php include __DIR__ . '/includes/footer.php'; // Include footer - Ensure path is correct ?>

    <?php // JavaScript for AJAX remove functionality ?>
    <div id="js-error-alert" class="js-alert" role="alert"></div> <?php // Element for showing JS errors ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const watchlistContainer = document.getElementById('watchlist-listing-container');
            const errorAlert = document.getElementById('js-error-alert');

            function showAlert(message) {
                if (!errorAlert) return;
                errorAlert.textContent = message;
                errorAlert.style.display = 'block';
                // Hide after a few seconds
                setTimeout(() => {
                    errorAlert.style.display = 'none';
                }, 5000); // Hide after 5 seconds
            }

            if (watchlistContainer) {
                watchlistContainer.addEventListener('click', function(event) {
                    // Delegate click handling to the container, check if a remove button was clicked
                    if (event.target.matches('.watchlist-btn.remove')) {
                        const button = event.target;
                        const productId = button.dataset.productId;
                        const source = button.dataset.source; // Get source from data attribute
                        const action = button.dataset.action; // Should be 'remove'

                        // Basic validation of data attributes
                        if (!productId || !source || action !== 'remove') {
                            console.error('Watchlist remove error: Missing or invalid data attributes on button.', button.dataset);
                            showAlert('Could not process request: invalid item data.');
                            return;
                        }

                        // Prevent multiple clicks while processing
                        button.disabled = true;
                        button.textContent = 'Removing...';
                        button.style.opacity = '0.7'; // Visual feedback

                        // Find the parent watchlist item element to remove later
                        const watchlistItem = button.closest('.watchlist-item');
                        if (!watchlistItem) {
                             console.error('Watchlist remove error: Could not find parent .watchlist-item element.');
                             showAlert('Could not remove item: UI element not found.');
                             button.disabled = false; // Re-enable button
                             button.textContent = 'Remove';
                             button.style.opacity = '1';
                             return;
                        }

                        // Prepare data for the request
                        const formData = new FormData();
                        formData.append('product_id', productId);
                        formData.append('source', source);
                        formData.append('action', action);

                        // --- Perform the AJAX request using Fetch API ---
                        // IMPORTANT: Adjust the URL to the correct path for your watchlist_handler.php
                        const handlerUrl = 'php/watchlist_handler.php';

                        fetch(handlerUrl, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json' // Indicate we expect JSON back
                            }
                        })
                        .then(response => {
                            // Check if response is ok (status in the range 200-299)
                            if (!response.ok) {
                                // Try to parse error JSON from server if possible
                                return response.json().then(errData => {
                                    throw new Error(errData.error || `HTTP error ${response.status}`);
                                }).catch(() => {
                                     // Fallback if JSON parsing fails or no error message provided
                                     throw new Error(`HTTP error ${response.status}`);
                                });
                            }
                            // If response is OK, parse the JSON body
                            return response.json();
                        })
                        .then(data => {
                            // Check the 'success' flag from the server's JSON response
                            if (data.success) {
                                console.log('Watchlist item removed successfully:', data.message);
                                // Add 'removing' class to trigger CSS animation
                                watchlistItem.classList.add('removing');

                                // Set a timeout slightly shorter than the animation duration
                                // or use 'transitionend' event for more robustness
                                watchlistItem.addEventListener('transitionend', () => {
                                    if (watchlistItem.parentNode) { // Check if it hasn't been removed already
                                        watchlistItem.remove();
                                    }
                                    // Check if the watchlist is now empty
                                    if (watchlistContainer.querySelectorAll('.watchlist-item').length === 0) {
                                       // Add the empty message dynamically
                                       const emptyMessage = document.createElement('p');
                                       emptyMessage.className = 'watchlist-empty';
                                       emptyMessage.textContent = 'Your watchlist is now empty.';
                                       watchlistContainer.appendChild(emptyMessage);
                                    }
                                }, { once: true }); // Important: Remove listener after it runs once

                                // Fallback removal if transitionend doesn't fire (e.g., display: none)
                                setTimeout(() => {
                                     if (watchlistItem.parentNode) {
                                          watchlistItem.remove();
                                          // Re-check if empty after fallback removal
                                           if (watchlistContainer.querySelectorAll('.watchlist-item').length === 0 && !watchlistContainer.querySelector('.watchlist-empty')) {
                                               const emptyMessage = document.createElement('p');
                                               emptyMessage.className = 'watchlist-empty';
                                               emptyMessage.textContent = 'Your watchlist is now empty.';
                                               watchlistContainer.appendChild(emptyMessage);
                                           }
                                     }
                                }, 600); // Slightly longer than animation (0.5s)

                            } else {
                                // Handle failure reported by the server
                                console.error('Failed to remove watchlist item:', data.error || 'Unknown server error');
                                showAlert('Error: ' + (data.error || 'Could not remove item. Please try again.'));
                                // Re-enable the button on failure
                                button.disabled = false;
                                button.textContent = 'Remove';
                                button.style.opacity = '1';
                            }
                        })
                        .catch(error => {
                            // Handle network errors or other fetch-related issues
                            console.error('Watchlist remove fetch error:', error);
                            showAlert('Network error: Could not reach server to remove item. Please check connection.');
                            // Re-enable the button on network error
                            button.disabled = false;
                            button.textContent = 'Remove';
                            button.style.opacity = '1';
                        });
                    }
                });
            } else {
                console.warn('Watchlist container element (#watchlist-listing-container) not found.');
            }
        });
    </script>

    <?php /* Include global script file if it exists and handles other things like theme toggle */ ?>
    <?php if (file_exists(__DIR__ . '/js/script.js')): ?>
    <script src="js/script.js?v=<?php echo filemtime(__DIR__ . '/js/script.js'); ?>"></script>
    <?php endif; ?>

</body>
</html>