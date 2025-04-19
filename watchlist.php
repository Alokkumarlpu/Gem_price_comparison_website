<?php
// File: watchlist.php

// Strict types and error reporting (disable display_errors in production)
declare(strict_types=1);
ini_set('display_errors', '0'); // Production: 0, Development: 1
error_reporting(E_ALL);

session_start(); // Start or resume session

// Includes (adjust paths if needed)
require_once __DIR__ . '/php/config.php';          // Site configuration constants (like SITE_NAME, DB details)
require_once __DIR__ . '/php/functions.php';        // General helper functions (escape_html, redirect, connect_db)

// --- Authentication Check ---
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Save intended destination
    if (isset($_SESSION['account_loggedin']) && $_SESSION['account_loggedin'] === true) {
         error_log("Watchlist Page Auth Error: User logged in (account_loggedin=true) but user_id is missing/invalid in session. Session Data: " . print_r($_SESSION, true));
         $_SESSION['login_message'] = 'Your session appears to be invalid. Please log in again.';
    } else {
        $_SESSION['login_message'] = 'Please log in to view your watchlist.';
    }
    redirect('login.php');
    exit; // Ensure script stops after redirect
}

// --- User Data ---
$user_id = (int) $_SESSION['user_id'];
$username = 'User'; // Default
// Prefer 'username' from session if it exists, align with login_handler.php standard
if (!empty($_SESSION['username'])) {
    $username = escape_html($_SESSION['username']);
} elseif (!empty($_SESSION['account_name'])) { // Fallback if needed
    $username = escape_html($_SESSION['account_name']);
}

// --- Page Setup ---
$site_name_escaped = defined('SITE_NAME') ? escape_html(SITE_NAME) : 'GeM Compare';
$page_title = "My Watchlist - " . $site_name_escaped;
$watchlist_items = [];
$errors = [];
$conn = null;

// --- CSRF Protection ---
// Generate CSRF token if one doesn't exist for the session
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        error_log("CSRF Token Generation Failed on watchlist.php: " . $e->getMessage());
        // Display an error or prevent form submission if token cannot be generated
        $errors[] = "A security token could not be generated. Please refresh the page.";
        $_SESSION['csrf_token'] = 'token_error'; // Use a placeholder to signal an error state
    }
}
$csrf_token = $_SESSION['csrf_token'] ?? 'token_error'; // Ensure variable is always set

// --- Database Interaction: Fetch Watchlist Items ---
// The SQL query structure is compatible with the provided schema.
try {
    $conn = connect_db();
    if (!$conn) {
        error_log("Watchlist Fetch: DB connection failed using connect_db(). User ID: {$user_id}");
        throw new Exception('Could not connect to the database service.');
    }
    if (!$conn->set_charset('utf8mb4')) {
         error_log("Watchlist Fetch: Error loading character set utf8mb4: {$conn->error}");
    }

    // SQL Query (Joins watchlist_items, products, and prices - verified compatible with schema)
    // Uses aliases for clarity, ensures correct table/column names are referenced.
    $sql = "
        SELECT
            wl.item_id,           -- From watchlist_items
            wl.product_id,        -- From watchlist_items
            wl.source AS watched_source, -- From watchlist_items
            wl.added_at,          -- From watchlist_items
            p.name AS product_name, -- From products
            p.description AS product_description, -- From products
            p.base_image_url,     -- From products
            lp_watched.price AS watched_price,   -- From prices (latest for watched source)
            lp_watched.product_url AS watched_url, -- From prices (latest for watched source)
            lp_other.source AS other_source,     -- From prices (latest for other source)
            lp_other.price AS other_price,       -- From prices (latest for other source)
            lp_other.product_url AS other_url    -- From prices (latest for other source)
        FROM watchlist_items wl
        JOIN products p ON wl.product_id = p.product_id -- Join based on product_id
        -- Subquery to get the latest price for the *watched* source
        LEFT JOIN (
            SELECT price_id, product_id, source, price, product_url
            FROM (
                SELECT pr.*, ROW_NUMBER() OVER(PARTITION BY pr.product_id, pr.source ORDER BY pr.fetched_at DESC) as rn
                FROM prices pr
            ) ranked_prices_watched
            WHERE rn = 1
        ) lp_watched ON wl.product_id = lp_watched.product_id AND wl.source = lp_watched.source
        -- Subquery to get the latest price for *one other prioritized* source
        LEFT JOIN (
             SELECT price_id, product_id, source, price, product_url
            FROM (
                SELECT pr.*, ROW_NUMBER() OVER(PARTITION BY pr.product_id, pr.source ORDER BY pr.fetched_at DESC) as rn
                FROM prices pr
            ) ranked_prices_other
            WHERE rn = 1
        ) lp_other ON wl.product_id = lp_other.product_id       -- Match product
                  AND wl.source != lp_other.source               -- Different source
                  AND lp_other.source = (                      -- Select the best *other* source
                        SELECT ps.source
                        FROM prices ps
                        WHERE ps.product_id = wl.product_id AND ps.source != wl.source
                        -- Prioritize known stores, then latest fetch (adjust FIELD() list as needed)
                        ORDER BY FIELD(ps.source, 'Amazon', 'Flipkart', 'GeM') DESC, ps.fetched_at DESC
                        LIMIT 1
                  )
        WHERE wl.user_id = ? -- Filter for the logged-in user (matches users.user_id via fk_watchlist_user)
        ORDER BY wl.added_at DESC; -- Show newest watchlist items first
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Watchlist Fetch Prepare failed for User ID {$user_id}: (" . $conn->errno . ") " . $conn->error);
        throw new Exception('Error preparing watchlist query.');
    }

    $stmt->bind_param('i', $user_id); // Bind the integer user ID

    if (!$stmt->execute()) {
        $err_msg = $stmt->error;
        $stmt_errno = $stmt->errno;
        $stmt->close();
        error_log("Watchlist Fetch Execute failed for User ID {$user_id}: ({$stmt_errno}) {$err_msg}");
        throw new Exception('Error executing watchlist query.');
    }

    $result = $stmt->get_result();
    $watchlist_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (mysqli_sql_exception $e_sql) {
    error_log("SQL Error Fetching Watchlist for User ID {$user_id}: [{$e_sql->getCode()}] {$e_sql->getMessage()}");
    $errors[] = "A database error occurred ({$e_sql->getCode()}) while retrieving your watchlist. Please try again later.";
} catch (Exception $e) {
    error_log("General Error Fetching Watchlist for User ID {$user_id}: " . $e->getMessage());
    $user_message = match ($e->getMessage()) {
        'Could not connect to the database service.' => 'The watchlist service is temporarily unavailable. Please try again later.',
        'Error preparing watchlist query.', 'Error executing watchlist query.' => 'Could not retrieve your watchlist data at this time.',
        default => 'An unexpected error occurred while loading your watchlist.'
    };
    $errors[] = $user_message;
} finally {
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
    <script src="https://cdn.tailwindcss.com"></script> <?php // Assumes Tailwind is used ?>
    <?php /* Favicon links - Use absolute paths from web root */ ?>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/images/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
    <?php /* Cache busting CSS - Use absolute path */ ?>
    <link rel="stylesheet" href="/css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
    <?php /* Google Fonts */ ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php /* Inline styles for watchlist - Reusing enhanced styles */ ?>
    <style>
        :root {
            --text-color-muted: #6c757d; --secondary-color: #ffc107;
            --primary-color: #0d6efd; --success-color: #198754;
            --danger-color: #dc3545; --link-color: #0a58ca;
            --card-bg: #ffffff; --card-border: #dee2e6;
            --card-subtle-bg: #f8f9fa; --body-bg: #f8f9fa;
        }
        body { background-color: var(--body-bg); font-family: 'Poppins', sans-serif; }
        .container { max-width: 1140px; margin: 0 auto; padding: 0 15px; }
        .page-header { background-color: #e9ecef; padding: 2rem 0; margin-bottom: 30px; text-align: center; }
        .page-header h1 { font-weight: 600; }
        .page-header .lead { color: var(--text-color-muted); font-size: 1.1rem; }
        .watchlist-container { margin-top: 30px; }
        .watchlist-empty { text-align: center; margin-top: 40px; color: var(--text-color-muted); font-size: 1.1em; padding: 30px 20px; background-color: var(--card-bg); border: 1px dashed var(--card-border); border-radius: 5px; }
        .watchlist-item { margin-bottom: 25px; }
        .product-card {
            border-left: 5px solid var(--secondary-color); background: var(--card-bg);
            padding: 20px 25px; border-radius: 8px; display: flex;
            flex-wrap: wrap; gap: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            border: 1px solid var(--card-border); transition: box-shadow 0.2s ease-in-out;
        }
        .product-card:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.12); }
        .product-image { flex: 0 0 120px; align-self: flex-start; }
        .product-image img { max-width: 100%; height: auto; display: block; border-radius: 6px; border: 1px solid var(--card-border); }
        .product-details { flex: 1 1 300px; min-width: 250px; }
        .product-details h3 { margin: 0 0 5px 0; font-size: 1.25em; font-weight: 600; line-height: 1.3; }
        .product-meta { margin-bottom: 15px; font-size: 0.8rem; color: var(--text-color-muted); display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .watched-source-label { display: inline-block; font-weight: 500; color: #664d03; background-color: #fff3cd; padding: 3px 8px; border-radius: 4px; border: 1px solid #ffecb5; text-transform: uppercase; letter-spacing: 0.5px; }
        .price-comparison { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 15px; }
        .price-source { border: 1px solid var(--card-border); padding: 15px; border-radius: 6px; flex: 1 1 220px; background-color: var(--card-subtle-bg); min-width: 180px; position: relative; transition: border-color 0.2s ease; }
        .price-source.is-cheaper { border-left: 4px solid var(--success-color); }
        .price-source .platform-label { font-weight: 600; display: block; margin-bottom: 8px; font-size: 0.95em; color: #495057; }
        .price-source .price-value { font-size: 1.3em; font-weight: 700; color: var(--primary-color); display: block; margin-bottom: 10px; }
        .price-source.is-cheaper .price-value { color: var(--success-color); }
        .price-source .price-unavailable { font-style: italic; color: var(--text-color-muted); display: block; margin-bottom: 10px; font-size: 0.9em; }
        .price-actions { margin-top: auto; padding-top: 8px; display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
        .visit-link { font-size: 0.9em; color: var(--link-color); text-decoration: none; transition: color 0.2s ease; }
        .visit-link:hover { color: #084298; text-decoration: underline; }
        .watchlist-btn.remove { background-color: var(--danger-color); color: white; border: 1px solid var(--danger-color); padding: 6px 12px; font-size: 0.85em; font-weight: 500; border-radius: 4px; cursor: pointer; transition: background-color 0.2s ease, border-color 0.2s ease, opacity 0.2s ease; line-height: 1; margin-left: auto; }
        .watchlist-btn.remove:hover:not(:disabled) { background-color: #bb2d3b; border-color: #b02a37; }
        .watchlist-btn.remove:disabled { background-color: #e17983; border-color: #e17983; opacity: 0.65; cursor: not-allowed; }
        .watchlist-btn.remove .spinner { display: inline-block; width: 0.8em; height: 0.8em; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s ease-in-out infinite; margin-right: 5px; vertical-align: middle; position: relative; top: -1px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .watchlist-item.removing { transition: opacity 0.4s ease-out, max-height 0.5s ease-out, margin-top 0.4s ease-out, margin-bottom 0.4s ease-out, padding-top 0.4s ease-out, padding-bottom 0.4s ease-out, transform 0.4s ease-out, border-width 0.4s ease-out; opacity: 0; max-height: 0px !important; margin-top: 0 !important; margin-bottom: 0 !important; padding-top: 0 !important; padding-bottom: 0 !important; border-width: 0 !important; transform: scaleY(0.8); transform-origin: top; overflow: hidden; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px 20px; border-radius: 5px; margin: 20px 0; }
        .error-message p:last-child { margin-bottom: 0; }
        .js-alert { position: fixed; top: 80px; left: 50%; transform: translateX(-50%); background-color: #dc3545; color: white; padding: 12px 25px; border-radius: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1050; font-size: 0.95em; display: none; text-align: center; min-width: 250px; max-width: 90%; }
    </style>
</head>
<body data-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <?php include __DIR__ . '/includes/header.php'; // Include header ?>

    <main id="main-content">
        <header class="page-header">
            <div class="container">
                <h1>My Watchlist</h1>
                <p class="lead">Products you are tracking on <?php echo $site_name_escaped; ?>.</p>
            </div>
        </header>

        <div class="container page-content-wrapper">
            <div class="watchlist-container" id="watchlist-listing-container">

                <?php if (!empty($errors)): ?>
                    <div class="error-message" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo escape_html($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($errors) && empty($watchlist_items)): ?>
                    <?php // Empty message is handled by JS now, but kept here as fallback/no-JS ?>
                     <p class="watchlist-empty" id="watchlist-empty-message" style="<?php echo !empty($watchlist_items) ? 'display: none;' : ''; ?>">
                        Your watchlist is currently empty. Find products you're interested in and add them to track their prices!
                    </p>
                <?php elseif (!empty($watchlist_items)): ?>
                    <?php foreach ($watchlist_items as $item):
                        // --- Sanitize and Prepare Data for Display ---
                        $productId = (int) ($item['product_id'] ?? 0); // Use null coalesce before cast
                        $watchedSource = escape_html($item['watched_source'] ?? 'N/A');
                        // Use product_name alias from SQL query
                        $productName = escape_html($item['product_name'] ?? 'Unnamed Product');
                        $imageUrl = escape_html($item['base_image_url'] ?? '/images/placeholder.png'); // Default placeholder path
                        // Validate and format prices
                        $watchedPrice = ($item['watched_price'] !== null && is_numeric($item['watched_price'])) ? (float)$item['watched_price'] : null;
                        $watchedUrl = filter_var($item['watched_url'] ?? '', FILTER_VALIDATE_URL) ? escape_html($item['watched_url']) : null;
                        // Other source details
                        $otherSource = (!empty($item['other_source']) && is_string($item['other_source'])) ? escape_html($item['other_source']) : null;
                        $otherPrice = ($item['other_price'] !== null && is_numeric($item['other_price'])) ? (float)$item['other_price'] : null;
                        $otherUrl = filter_var($item['other_url'] ?? '', FILTER_VALIDATE_URL) ? escape_html($item['other_url']) : null;
                        // Added date (from watchlist_items.added_at)
                        $addedDate = 'N/A';
                        if (!empty($item['added_at'])) {
                            try {
                                $date = new DateTimeImmutable($item['added_at']); // Use Immutable for safety
                                $addedDate = $date->format('M j, Y'); // e.g., Aug 16, 2023
                            } catch (Exception $e) {
                                $addedDate = 'Invalid Date';
                                error_log("Invalid date format in watchlist item ID {$item['item_id']}: {$item['added_at']}");
                            }
                        }

                        $watchedPriceValid = ($watchedPrice !== null);
                        $otherPriceValid = ($otherPrice !== null);
                        $hasOtherSource = ($otherSource !== null);

                        // Calculate price difference for highlighting
                        $otherIsCheaper = false;
                        if ($watchedPriceValid && $otherPriceValid) {
                            $otherIsCheaper = ($otherPrice < $watchedPrice);
                        }

                        // Create a safe, unique ID for the DOM element
                        $itemDomId = 'watchlist-item-' . $productId . '-' . preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', $watchedSource));
                    ?>
                    <div class="watchlist-item" id="<?php echo $itemDomId; ?>">
                        <article class="product-card">
                            <div class="product-image">
                                <img src="<?php echo $imageUrl; ?>" alt="Product image for <?php echo $productName; ?>" loading="lazy">
                            </div>
                            <div class="product-details">
                                <h3><?php echo $productName; ?></h3>
                                <div class="product-meta">
                                    <span class="watched-source-label">Watching: <?php echo $watchedSource; ?></span>
                                    <span class="added-date">Added: <?php echo $addedDate; ?></span>
                                </div>

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
                                                    data-source="<?php echo $watchedSource; ?>"
                                                    data-action="remove"
                                                    data-csrf-token="<?php echo $csrf_token; // ** Pass CSRF token here ** ?>"
                                                    aria-label="Remove <?php echo $productName; ?> (<?php echo $watchedSource; ?> listing) from your watchlist"
                                                    <?php if ($csrf_token === 'token_error') echo 'disabled title="Cannot remove item due to security token error."'; ?>>
                                                Remove
                                            </button>
                                        </div>
                                    </div>

                                    <?php if ($hasOtherSource): // Only show if other source/price found ?>
                                    <div class="price-source other-price <?php echo $otherIsCheaper ? 'is-cheaper' : ''; ?>">
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
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div> 
                            </div> 
                        </article> 
                    </div> 
                    <?php endforeach; ?>
                <?php endif; ?>
                 
                <p class="watchlist-empty" id="watchlist-empty-message" style="<?php echo !empty($watchlist_items) ? 'display: none;' : ''; ?>">
                    Your watchlist is currently empty. Find products you're interested in and add them to track their prices!
                </p>

            </div> <!-- /#watchlist-listing-container -->
        </div> <!-- /.container -->
    </main>

    <?php include __DIR__ . '/includes/footer.php'; // Include footer ?>

    <?php // JavaScript for AJAX remove functionality & UI updates ?>
    <div id="js-error-alert" class="js-alert" role="alert" aria-live="assertive"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const watchlistContainer = document.getElementById('watchlist-listing-container');
            const errorAlert = document.getElementById('js-error-alert');
            const emptyMessageElement = document.getElementById('watchlist-empty-message');

            // Function to show JS-related alerts
            function showAlert(message, isError = true) {
                if (!errorAlert) return;
                errorAlert.textContent = message;
                errorAlert.style.backgroundColor = isError ? 'var(--danger-color)' : 'var(--success-color)';
                errorAlert.style.display = 'block';
                setTimeout(() => {
                    if (errorAlert) errorAlert.style.display = 'none';
                }, 5000);
            }

            // Function to check and show/hide the empty message
            function checkEmptyWatchlist() {
                 if (!watchlistContainer || !emptyMessageElement) return;
                 // Select items that are not currently being animated out
                 const remainingItems = watchlistContainer.querySelectorAll('.watchlist-item:not(.removing)').length;
                 emptyMessageElement.style.display = remainingItems === 0 ? 'block' : 'none';
             }

            if (watchlistContainer) {
                watchlistContainer.addEventListener('click', function(event) {
                    const button = event.target.closest('.watchlist-btn.remove');
                    if (button) {
                        event.preventDefault();

                        const productId = button.dataset.productId;
                        const source = button.dataset.source;
                        const action = button.dataset.action;
                        const csrfToken = button.dataset.csrfToken; // ** Get CSRF token from button **

                        // ** Validation: Check if all required data attributes are present **
                        if (!productId || !source || action !== 'remove' || !csrfToken || csrfToken === 'token_error') {
                            console.error('Watchlist remove error: Missing/invalid data attributes or CSRF token.', button.dataset);
                            showAlert('Could not process request: Invalid item data or security issue.');
                            return;
                        }

                        // Prevent multiple clicks
                        if (button.disabled) return;
                        button.disabled = true;
                        button.innerHTML = '<span class="spinner" aria-hidden="true"></span> Removing...';
                        button.style.opacity = '0.7';

                        const watchlistItem = button.closest('.watchlist-item');
                        if (!watchlistItem) {
                             console.error('Watchlist remove error: Could not find parent .watchlist-item element.');
                             showAlert('Could not remove item: UI element not found.');
                             button.disabled = false;
                             button.innerHTML = 'Remove';
                             button.style.opacity = '1';
                             return;
                        }

                        // ** Prepare FormData including the CSRF token **
                        const formData = new FormData();
                        formData.append('product_id', productId);
                        formData.append('source', source);
                        formData.append('action', action);
                        formData.append('csrf_token', csrfToken); // Include CSRF token

                        const handlerUrl = 'php/watchlist_handler.php'; // Ensure path is correct

                        // --- Perform Fetch Request ---
                        fetch(handlerUrl, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().catch(() => {
                                    // If JSON parsing fails, create a generic error
                                    return { success: false, error: `HTTP error ${response.status} - ${response.statusText}` };
                                }).then(errData => {
                                    throw new Error(errData.error || `HTTP error ${response.status}`);
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                console.log('Watchlist item removed:', data.message || `Item ${productId}`);
                                watchlistItem.classList.add('removing');

                                // Use transitionend for removal after animation
                                watchlistItem.addEventListener('transitionend', () => {
                                    if (watchlistItem.parentNode) {
                                        watchlistItem.remove();
                                        checkEmptyWatchlist(); // Update empty message state
                                    }
                                }, { once: true });

                                // Fallback removal timer
                                setTimeout(() => {
                                     if (watchlistItem.parentNode) {
                                          console.warn('Watchlist remove fallback timer executed for item:', watchlistItem.id);
                                          watchlistItem.remove();
                                          checkEmptyWatchlist();
                                     }
                                }, 600); // > animation duration

                            } else {
                                // Handle failure reported by the server (e.g., CSRF error, DB error)
                                console.error('Failed to remove watchlist item:', data.error || 'Unknown server error');
                                showAlert('Error: ' + (data.error || 'Could not remove item. Please try again.'));
                                button.disabled = false;
                                button.innerHTML = 'Remove';
                                button.style.opacity = '1';
                            }
                        })
                        .catch(error => {
                            // Handle network errors or JS errors
                            console.error('Watchlist remove fetch/JS error:', error);
                            showAlert(`Network or system error: ${error.message}. Please check connection or try again later.`);
                            button.disabled = false;
                            button.innerHTML = 'Remove';
                            button.style.opacity = '1';
                        });
                    }
                });

                // Initial check for empty watchlist on page load
                 checkEmptyWatchlist();

            } else {
                console.warn('Watchlist container element (#watchlist-listing-container) not found.');
            }
        });
    </script>

    <?php /* Include global script file if it exists - Use absolute path */ ?>
    <?php if (file_exists(__DIR__ . '/js/script.js')): ?>
    <script src="/js/script.js?v=<?php echo filemtime(__DIR__ . '/js/script.js'); ?>"></script>
    <?php endif; ?>

</body>
</html>