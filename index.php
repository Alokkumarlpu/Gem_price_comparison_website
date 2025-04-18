<?php
// File: index.php (Corrected)
session_start(); // Start session to access session data for logged-in status
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/functions.php';
?>
<!DOCTYPE html>
<html lang="en" class=""> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse and compare prices for products on the Government e-Marketplace (GeM) with other major e-commerce platforms like Amazon and Flipkart.">
    <title><?php echo escape_html(SITE_NAME); ?> - Browse Product Comparisons</title>
    <script src="https://cdn.tailwindcss.com"></script> 
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" href="images/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <meta property="og:title" content="<?php echo escape_html(SITE_NAME); ?>">
    <meta property="og:description" content="Browse and compare GeM product prices with other e-marketplaces.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo escape_html(SITE_URL); ?>"> 
</head>
<body data-logged-in="<?php echo (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) ? 'true' : 'false'; ?>">

    <?php include __DIR__ . '/includes/header.php';  ?>


        <!-- Simplified Hero Section (HTML Unchanged) -->
        <header class="bg-purple-500 text-white transition-colors duration-300">
            <div class="container mx-auto"> <?php /* Added mx-auto for centering */ ?>
                <div class="flex flex-col items-center md:flex-row md:justify-between">
                    <div class="m-10 md:ml-20 text-center md:text-left"> <?php /* Centered text on mobile */ ?>
                        <h1 class="text-4xl font-bold mb-4">Compare GeM Prices with Other E-Marketplaces</h1>
                        <p class="text-xl mb-6">Find the best deals across platforms and make informed purchasing decisions.</p>
                        <a href="compare_price.php" class="inline-block bg-white text-purple-500 px-6 py-3 rounded-lg font-bold hover:bg-pink-100 transition-colors duration-300">Start Comparing</a> <?php /* Changed button to link */ ?>
                    </div>
                    <div class="md:mt-20 mt-10 md:mr-10"> <?php /* Added margin right */ ?>
                        <img src="assets/happyman.png" alt="Price comparison illustration" class="w-64 md:w-96"> <?php /* Adjusted size */ ?>
                    </div>
                </div>
            </div>
        </header>


        <!-- Search/Comparison Section (HTML Unchanged) -->
        <section id="comparison" class="py-12 transition-colors duration-300">
            <h2 class="text-3xl font-bold text-center mb-8"> Compare Prices</h2>
            <div class="container mx-auto px-6 rounded-lg p-8 transition-colors duration-300">
                <!-- Sample Product Card 1 (HTML Unchanged) -->
                <div class="product-card bg-white rounded-lg shadow-md p-4 mb-4 transition-colors duration-300">
                    <div class="flex flex-col md:flex-row">
                        <div class="w-full md:w-1/6 flex justify-center mb-4 md:mb-0">
                            <img src="assets/lenevolqo.png" alt="Lenovo LOQ Laptop" class="object-contain h-24 md:h-32">
                        </div>
                        <div class="w-full md:w-5/6 pl-0 md:pl-6">
                            <h4 class="text-lg font-semibold">Lenovo LOQ Intel Core i5 12th Gen 12450HX </h4>
                            <div class="text-yellow-500 mt-1">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                <span class="text-gray-600 ml-2 transition-colors duration-300">(124 reviews)</span>
                            </div>
                            <div class="mt-4 overflow-x-auto"> 
                                <table class="w-full border-collapse min-w-[600px]"> 
                                    <thead><tr class="bg-gray-100 transition-colors duration-300"><th class="p-2 text-left">Marketplace</th><th class="p-2 text-left">Price</th><th class="p-2 text-left">Delivery Time</th><th class="p-2 text-left">Actions</th></tr></thead>
                                    <tbody>
                                        <tr class="border-b transition-colors duration-300"><td class="p-2"><span class="font-semibold text-blue-600">GeM</span></td><td class="p-2">₹65,190</td><td class="p-2">7-9 days</td><td class="p-2"><a href="https://gem.gov.in/" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">View Deal</a></td></tr>
                                        <tr class="border-b transition-colors duration-300"><td class="p-2"><span class="font-semibold text-green-600 dark:text-green-400">Amazon</span></td><td class="p-2">₹63,590</td><td class="p-2">3-5 days</td><td class="p-2"><a href="https://www.amazon.in/Lenovo-i5-12450HX-300Nits-Graphics-83GS003NIN/dp/B0D49RN3X6/" target="_blank" rel="noopener noreferrer" class="text-green-600 hover:underline">View Deal</a></td></tr>
                                        <tr class="border-b bg-purple-100 dark:bg-purple-900 transition-colors duration-300"><td class="p-2"><span class="font-semibold text-purple-600 dark:text-purple-300">Flipkart</span> <span class="bg-green-600 text-white text-xs px-2 py-1 rounded-full ml-1">Best Deal</span></td><td class="p-2 font-semibold text-purple-700 dark:text-purple-200">₹62,190</td><td class="p-2">4-6 days</td><td class="p-2"><a href="https://www.flipkart.com/lenovo-loq-intel-core-i5-12th-gen-12450hx-12-gb-512-gb-ssd-windows-11-home-4-graphics-nvidia-geforce-rtx-2050-15iax9d1-gaming-laptop/p/itm4a19730d181b4" target="_blank" rel="noopener noreferrer" class="text-purple-600 dark:text-purple-300 hover:underline">View Deal</a></td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-sm text-gray-600 transition-colors duration-300">Last updated: 2 days ago</span>
                                <button type="button" class="text-purple-600 hover:text-pink-400 transition-colors duration-300 save-later-btn" data-product-id="123"> <?php /* Added type and class */ ?>
                                    <i class="far fa-bookmark mr-1"></i> Save for later
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sample Product Card 2 (HTML Unchanged) -->
                <div class="product-card bg-white rounded-lg shadow-md p-4 mb-4 transition-colors duration-300">
                    <div class="flex flex-col md:flex-row">
                         <div class="w-full md:w-1/6 flex justify-center mb-4 md:mb-0">
                            <img src="assets/lgtv.png" alt="LG Smart TV" class="object-contain h-24 md:h-32">
                        </div>
                       <div class="w-full md:w-5/6 pl-0 md:pl-6">
                            <h4 class="text-lg font-semibold">LG 80 cm (32 inch) HD Ready LED Smart WebOS TV 2025 Edition</h4>
                            <div class="text-yellow-500 mt-1">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                                <span class="text-gray-600 ml-2 transition-colors duration-300">(30k+ reviews)</span>
                            </div>
                             <div class="mt-4 overflow-x-auto"> <?php /* Added overflow for small screens */ ?>
                                <table class="w-full border-collapse min-w-[600px]"> <?php /* Added min-width */ ?>
                                    <thead><tr class="bg-gray-100 transition-colors duration-300"><th class="p-2 text-left">Marketplace</th><th class="p-2 text-left">Price</th><th class="p-2 text-left">Delivery Time</th><th class="p-2 text-left">Actions</th></tr></thead>
                                    <tbody>
                                        <tr class="border-b bg-purple-100 dark:bg-purple-900 transition-colors duration-300"><td class="p-2"><span class="font-semibold text-blue-600">GeM</span> <span class="bg-green-600 text-white text-xs px-2 py-1 rounded-full ml-1">Best Deal</span></td><td class="p-2 font-semibold text-purple-700 dark:text-purple-200">₹12,800</td><td class="p-2">5-7 days</td><td class="p-2"><a href="https://mkp.gem.gov.in/television-tv-v2/32-hd-led-display-with-3-years-warranty/p-5116877-60807785997-cat.html#variant_id=5116877-60807785997" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">View Deal</a></td></tr>
                                        <tr class="border-b transition-colors duration-300"><td class="p-2"><span class="font-semibold text-green-600 dark:text-green-400">Amazon</span></td><td class="p-2">₹15,990</td><td class="p-2">2-4 days</td><td class="p-2"><a href="https://www.amazon.in/LG-inches-Smart-webOS-32LR600B6LC/dp/B0DVGBNVM2/" target="_blank" rel="noopener noreferrer" class="text-green-600 hover:underline">View Deal</a></td></tr>
                                        <tr class="border-b transition-colors duration-300"><td class="p-2"><span class="font-semibold text-purple-600 dark:text-purple-300">Flipkart</span></td><td class="p-2">₹15,990</td><td class="p-2">3-5 days</td><td class="p-2"><a href="https://www.flipkart.com/lg-80-cm-32-inch-hd-ready-led-smart-webos-tv-2025-alpha5-gen-6-ai-processor-100-free-channels-functions-hdr-10-magic-remote-compatible-60hz-refresh-rate-satellite-connectivity-wi-fi-built-in/p/itmff06ea1253208" target="_blank" rel="noopener noreferrer" class="text-purple-600 dark:text-purple-300 hover:underline">View Deal</a></td></tr>
                                    </tbody>
                                </table>
                            </div>
                             <div class="mt-4 flex justify-between items-center">
                                <span class="text-sm text-gray-600 transition-colors duration-300">Last updated: 1 day ago</span>
                                <button type="button" class="text-purple-600 hover:text-pink-400 transition-colors duration-300 save-later-btn" data-product-id="456"> <?php /* Added type and class */ ?>
                                    <i class="far fa-bookmark mr-1"></i> Save for later
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-center">
                    <button id="load-more" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-pink-400 transition-colors duration-300">
                    
                    <a href="compare_price.php" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors duration-150">
                        Load More Results</a>
                    </button>
                </div>
            </div>
        </section>

        <!-- Market Insights Section (HTML Unchanged) -->
        <section id="insights" class="py-12 bg-gray-100 transition-colors duration-300">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl font-bold text-center mb-8">Market Insights</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Price trends chart -->
                    <div class="bg-white rounded-lg shadow-lg p-6 transition-colors duration-300">
                        <h3 class="text-xl font-semibold mb-4">Price Trends: GeM vs Other Marketplaces</h3>
                        <div class="m-4 relative h-64 p-4">
                            <?php /* Using static image as chart JS is commented out */ ?>
                            <img src="assets/graph.png" alt="Price Trends Chart" class="block w-full h-full object-contain mb-4 rounded-lg">
                            <?php /* Add this canvas IF you implement Chart.js later
                             <canvas id="price-trends-chart"></canvas>
                             */ ?>
                        </div>
                        <div class="mt-8 text-sm text-gray-600 transition-colors duration-300 p-2">
                            <p>Based on average prices for most commonly purchased items in each category over the past 6 months.</p>
                        </div>
                    </div>

                    <!-- Categories comparison (HTML Unchanged) -->
                    <div class="bg-white rounded-lg shadow-lg p-6 transition-colors duration-300">
                        <h3 class="text-xl font-semibold mb-4">Categories with Biggest Price Differences</h3>
                        <div class="space-y-4">
                            <div class="flex items-center"> <div class="w-1/3 font-medium">Electronics</div> <div class="w-2/3 relative"> <div class="bg-blue-100 dark:bg-blue-900 h-6 w-full rounded-full transition-colors duration-300 overflow-hidden"><div class="bg-blue-600 h-6 rounded-full text-xs text-white flex items-center justify-end pr-2" style="width: 22%">22%</div></div> <span class="absolute right-0 top-0 -mt-4 mr-2 text-xs font-semibold text-blue-700 dark:text-blue-300 hidden sm:inline">cheaper on GeM</span> </div> </div>
                            <div class="flex items-center"> <div class="w-1/3 font-medium">IT Equipment</div> <div class="w-2/3 relative"> <div class="bg-blue-100 dark:bg-blue-900 h-6 w-full rounded-full transition-colors duration-300 overflow-hidden"><div class="bg-blue-600 h-6 rounded-full text-xs text-white flex items-center justify-end pr-2" style="width: 18%">18%</div></div> <span class="absolute right-0 top-0 -mt-4 mr-2 text-xs font-semibold text-blue-700 dark:text-blue-300 hidden sm:inline">cheaper on GeM</span> </div> </div>
                            <div class="flex items-center"> <div class="w-1/3 font-medium">Office Supplies</div> <div class="w-2/3 relative"> <div class="bg-blue-100 dark:bg-blue-900 h-6 w-full rounded-full transition-colors duration-300 overflow-hidden"><div class="bg-blue-600 h-6 rounded-full text-xs text-white flex items-center justify-end pr-2" style="width: 15%">15%</div></div> <span class="absolute right-0 top-0 -mt-4 mr-2 text-xs font-semibold text-blue-700 dark:text-blue-300 hidden sm:inline">cheaper on GeM</span> </div> </div>
                            <div class="flex items-center"> <div class="w-1/3 font-medium">Furniture</div> <div class="w-2/3 relative"> <div class="bg-red-100 dark:bg-red-900 h-6 w-full rounded-full transition-colors duration-300 overflow-hidden"><div class="bg-red-600 h-6 rounded-full text-xs text-white flex items-center justify-end pr-2" style="width: 8%">8%</div></div> <span class="absolute right-0 top-0 -mt-4 mr-2 text-xs font-semibold text-red-700 dark:text-red-300 hidden sm:inline">more expensive</span> </div> </div>
                            <div class="flex items-center"> <div class="w-1/3 font-medium">Stationery</div> <div class="w-2/3 relative"> <div class="bg-red-100 dark:bg-red-900 h-6 w-full rounded-full transition-colors duration-300 overflow-hidden"><div class="bg-red-600 h-6 rounded-full text-xs text-white flex items-center justify-end pr-2" style="width: 5%">5%</div></div> <span class="absolute right-0 top-0 -mt-4 mr-2 text-xs font-semibold text-red-700 dark:text-red-300 hidden sm:inline">more expensive</span> </div> </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section (HTML Unchanged) -->
        <section class="py-12 bg-white transition-colors duration-300">
             <div class="container mx-auto px-6">
                <h2 class="text-3xl font-bold text-center mb-12">Why Use Our Price Comparison Tool?</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center p-6"> <div class="bg-pink-100 dark:bg-purple-800 p-4 rounded-full h-20 w-20 flex items-center justify-center mx-auto mb-4 transition-colors duration-300"> <i class="fas fa-search-dollar text-3xl text-purple-600 dark:text-pink-300"></i> </div> <h3 class="text-xl font-semibold mb-2">Find the Best Deals</h3> <p class="text-gray-600 transition-colors duration-300">Compare prices across GeM and other major e-commerce platforms to ensure you're getting the best value.</p> </div>
                    <div class="text-center p-6"> <div class="bg-pink-100 dark:bg-purple-800 p-4 rounded-full h-20 w-20 flex items-center justify-center mx-auto mb-4 transition-colors duration-300"> <i class="fas fa-chart-line text-3xl text-purple-600 dark:text-pink-300"></i> </div> <h3 class="text-xl font-semibold mb-2">Track Price History</h3> <p class="text-gray-600 transition-colors duration-300">View price trends and historical data to make smarter purchasing decisions.</p> </div>
                    <div class="text-center p-6"> <div class="bg-pink-100 dark:bg-purple-800 p-4 rounded-full h-20 w-20 flex items-center justify-center mx-auto mb-4 transition-colors duration-300"> <i class="fas fa-bell text-3xl text-purple-600 dark:text-pink-300"></i> </div> <h3 class="text-xl font-semibold mb-2">Price Drop Alerts</h3> <p class="text-gray-600 transition-colors duration-300">Set alerts for products you're interested in and get notified when prices drop.</p> </div>
                </div>
            </div>
        </section>

        <!-- About Section (HTML Unchanged) -->
        <section id="about" class="py-12 bg-gray-50 transition-colors duration-300">
             <div class="container mx-auto px-6">
                <h2 class="text-3xl font-bold text-center mb-8">About Price Comparison</h2>
                <div class="max-w-3xl mx-auto text-center">
                    <p class="text-lg mb-6">Price comparison is a specialized tool designed to help government agencies, businesses, and individual consumers compare product prices between the Government e-Marketplace (GeM) and other popular e-commerce platforms.</p>
                    <p class="text-lg mb-6">Our mission is to promote transparency in pricing, help buyers make informed decisions, and ultimately ensure better value for every purchase.</p>
                    <p class="text-lg">With real-time data and comprehensive analysis tools, we make it easy to compare prices, track trends, and identify the best deals across multiple marketplaces.</p>
                </div>
                <div class="mt-12 text-center">
                    <h3 class="text-xl font-semibold mb-4">Supported Marketplaces</h3>
                    <div class="flex flex-wrap justify-center items-center gap-8"> <?php /* Added items-center */ ?>
                        <div class="bg-white p-4 rounded-lg shadow transition-colors duration-300 text-center"> <div class="text-blue-600 font-bold text-xl mb-1">GeM</div> <div class="text-sm text-gray-600 transition-colors duration-300">Government e-Marketplace</div> </div>
                        <div class="bg-white p-4 rounded-lg shadow transition-colors duration-300 text-center"> <div class="text-green-600 dark:text-green-400 font-bold text-xl mb-1">Amazon</div> <div class="text-sm text-gray-600 transition-colors duration-300">Amazon India</div> </div>
                        <div class="bg-white p-4 rounded-lg shadow transition-colors duration-300 text-center"> <div class="text-purple-600 font-bold text-xl mb-1">Flipkart</div> <div class="text-sm text-gray-600 transition-colors duration-300">Flipkart</div> </div>
                    </div>
                </div>
            </div>
        </section>

    <!-- </main> <?php // Moved main closing tag here ?> -->

    <?php include __DIR__ . '/includes/footer.php'; // Include Footer ?>


    <script src="js/script.js?v=<?php echo filemtime(__DIR__ . '/js/script.js'); ?>"></script>

    <script>
        
        // Add event listeners for other interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Button event listeners (placeholders)
            const searchBtn = document.getElementById('search-btn'); // Ensure an element with this ID exists if needed
            if (searchBtn) {
                searchBtn.addEventListener('click', function() {
                    console.log('Search button clicked - Implement search logic.');
                    // Example: window.location.href = '/search?query=...';
                });
            }

            const loadMoreBtn = document.getElementById('load-more');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    console.log('Load More button clicked - Implement loading logic (e.g., AJAX).');
                    alert('Loading more results...'); // Placeholder
                });
            }

            // "Save for later" buttons - Example using event delegation
            const comparisonSection = document.getElementById('comparison');
            if (comparisonSection) {
                comparisonSection.addEventListener('click', function(event) {
                    if (event.target.closest('.save-later-btn')) {
                        const button = event.target.closest('.save-later-btn');
                        const productId = button.dataset.productId; // Get product ID if needed
                        const icon = button.querySelector('i');

                        // Check login status (using data attribute set in body)
                        const isLoggedIn = document.body.dataset.loggedIn === 'true';

                        if (!isLoggedIn) {
                            alert('Please log in to save items to your watchlist.');
                            // Optional: Redirect to login
                            // window.location.href = 'login.php';
                            return;
                        }

                        // Example toggle behavior (replace with AJAX call to watchlist handler)
                        if (icon.classList.contains('far')) { // If it's currently 'not saved'
                            icon.classList.remove('far');
                            icon.classList.add('fas'); // Change to solid bookmark
                            button.childNodes[2].nodeValue = ' Saved'; // Change text node
                             console.log(`Attempting to ADD product ID: ${productId} to watchlist via AJAX...`);
                             // alert(`Product ${productId} added to watchlist (simulation).`); // Replace with actual AJAX
                        } else { // If it's currently 'saved'
                            icon.classList.remove('fas');
                            icon.classList.add('far'); // Change back to regular bookmark
                            button.childNodes[2].nodeValue = ' Save for later'; // Change text node back
                             console.log(`Attempting to REMOVE product ID: ${productId} from watchlist via AJAX...`);
                             // alert(`Product ${productId} removed from watchlist (simulation).`); // Replace with actual AJAX
                        }
                         // Implement actual AJAX call here to add/remove from watchlist using watchlist_handler.php
                    }
                });
            }

            // Trigger initial theme setup from header's script if needed
            // (Header script already handles initial theme based on localStorage/preference)
            // initializeCharts(); // Call if Chart.js is implemented
        });
    </script>

</body>
</html>