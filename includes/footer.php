<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$current_year = date("Y");
$is_subdir_page_footer = (stripos($_SERVER['PHP_SELF'], '/pages/') !== false);
$path_prefix_footer = $is_subdir_page_footer ? '../' : './'; 

// Get Site Name (ensure config.php where SITE_NAME is defined is included *before* this footer)
$site_name_footer = defined('SITE_NAME') ? htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') : 'GeM Compare';

$is_logged_in_footer = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0;

?>
<footer class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 pt-12 pb-8 mt-16 transition-colors duration-300">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12"> 

            <!-- Column 1: About -->
            <div class="md:col-span-1">
                <h3 class="text-lg font-semibold text-purple-700 dark:text-purple-300 mb-4"><?php echo $site_name_footer; ?></h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Your tool for transparent price comparison between Government e-Marketplace (GeM)
                    and other popular e-commerce platforms. Empowering informed decisions.
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500 italic">
                    Disclaimer: This is an independent tool and not officially affiliated with GeM or any specific marketplace. Always verify data on official sites.
                </p>
            </div>

            <!-- Column 2: Quick Links -->
            <div class="md:col-span-1">
                <h3 class="text-lg font-semibold text-purple-700 dark:text-purple-300 mb-4">Quick Links</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?php echo $path_prefix_footer; ?>index.php" class="text-purple-600 dark:text-purple-400 hover:text-pink-500 dark:hover:text-pink-300 hover:underline transition duration-150 ease-in-out">Home</a></li>
                    <li><a href="<?php echo $path_prefix_footer; ?>compare_price.php" class="text-purple-600 dark:text-purple-400 hover:text-pink-500 dark:hover:text-pink-300 hover:underline transition duration-150 ease-in-out">Compare Prices</a></li>
                    <?php if ($is_logged_in_footer): ?>
                        <li><a href="<?php echo $path_prefix_footer; ?>watchlist.php" class="text-purple-600 dark:text-purple-400 hover:text-pink-500 dark:hover:text-pink-300 hover:underline transition duration-150 ease-in-out">My Watchlist</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $path_prefix_footer; ?>pages/how-it-works.php" class="text-purple-600 dark:text-purple-400 hover:text-pink-500 dark:hover:text-pink-300 hover:underline transition duration-150 ease-in-out">How It Works</a></li>
                    <li><a href="<?php echo $path_prefix_footer; ?>pages/about.php" class="text-purple-600 dark:text-purple-400 hover:text-pink-500 dark:hover:text-pink-300 hover:underline transition duration-150 ease-in-out">About Us</a></li>
                    <li><a href="<?php echo $path_prefix_footer; ?>pages/contact.php" class="text-purple-600 dark:text-purple-400 hover:text-pink-500 dark:hover:text-pink-300 hover:underline transition duration-150 ease-in-out">Contact</a></li>
                    <li><a href="https://gem.gov.in/" target="_blank" rel="noopener noreferrer" class="text-purple-600 dark:text-purple-400 hover:text-pink-500 dark:hover:text-pink-300 hover:underline transition duration-150 ease-in-out">Official GeM Portal <span class="text-xs opacity-75">↗</span></a></li>
                </ul>
            </div>

            <!-- Column 3: Contact & Follow -->
            <div class="md:col-span-1">
                <h3 class="text-lg font-semibold text-purple-700 dark:text-purple-300 mb-4">Contact & Follow</h3>
                 <div class="mb-5">
                    <a href="<?php echo $path_prefix_footer; ?>pages/contact.php" class="inline-flex items-center text-sm text-purple-600 dark:text-purple-400 hover:text-pink-500 dark:hover:text-pink-300 hover:underline transition duration-150 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        Get in Touch
                    </a>
                </div>
                <div class="flex space-x-4">
                    <a href="#" aria-label="Link to our Twitter profile" target="_blank" rel="noopener noreferrer" class="text-gray-500 dark:text-gray-400 hover:text-pink-500 dark:hover:text-pink-300 transition duration-150 ease-in-out">
                        <span class="sr-only">Twitter</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 16 16" fill="currentColor"><path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865l8.875 11.633Z"/></svg>
                    </a>
                    <a href="#" aria-label="Link to our LinkedIn profile" target="_blank" rel="noopener noreferrer" class="text-gray-500 dark:text-gray-400 hover:text-pink-500 dark:hover:text-pink-300 transition duration-150 ease-in-out">
                         <span class="sr-only">LinkedIn</span>
                         <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                    </a>
                    <a href="#" aria-label="Link to our Facebook profile" target="_blank" rel="noopener noreferrer" class="text-gray-500 dark:text-gray-400 hover:text-pink-500 dark:hover:text-pink-300 transition duration-150 ease-in-out">
                         <span class="sr-only">Facebook</span>
                         <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>
                    </a>
                </div>
            </div>

        </div>

        <!-- Footer Bottom Separator and Copyright -->
        <div class="mt-10 border-t border-gray-300 dark:border-gray-800 pt-8 text-center">
             <p class="text-sm text-gray-500 dark:text-gray-400">
                © <?php echo $current_year; ?> <?php echo $site_name_footer; ?>. All Rights Reserved.
                
             </p>
        </div>

    </div>
</footer>