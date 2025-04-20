<?php
// File: includes/header.php (Modified to match index.php Theme - Purple/Pink Accents)

// Use @session_start() defensively if the header might be included
// before session_start() is called in the main script.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// --- Path Prefix Logic --- (Unchanged)
$is_subdir_page = (stripos($_SERVER['PHP_SELF'], '/pages/') !== false);
$path_prefix = $is_subdir_page ? '../' : './';
$current_page = basename($_SERVER['PHP_SELF']);

// --- Login Status & User Info --- (Unchanged)
$is_logged_in = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
$username = 'User';
$avatar_svg_placeholder = '<svg class="h-8 w-8 rounded-full text-gray-400 bg-gray-200 dark:bg-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>';
$avatar_html = $avatar_svg_placeholder;
if ($is_logged_in) {
    if (!empty($_SESSION['account_name'])) {
        $username = htmlspecialchars($_SESSION['account_name'], ENT_QUOTES, 'UTF-8');
    } elseif (!empty($_SESSION['username'])) {
        $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
    }
}

// Site Name (Using Purple from index theme)
$site_name_header = defined('SITE_NAME') ? htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') : 'GeM Compare'; // Changed default name for clarity

// --- URLs --- (Unchanged)
$logout_page_url = $path_prefix . 'php/logout.php';
$login_page_url = $path_prefix . 'login.php';
$register_page_url = $path_prefix . 'register.php';

// --- Helper Function --- (Unchanged)
function calculate_relative_path(string $target_path, bool $current_is_subdir): string {
    $target_is_subdir = (strpos($target_path, 'pages/') === 0);
    if ($current_is_subdir) {
        return $target_is_subdir ? substr($target_path, strlen('pages/')) : '../' . $target_path;
    } else {
        return './' . $target_path;
    }
}

?>
<?php // Header with Purple/Pink Theme Applied ?>
<header class="bg-white dark:bg-gray-800 shadow-md sticky top-0 z-50 text-gray-700 dark:text-gray-300">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <!-- Logo (Purple Theme) -->
            <div class="flex-shrink-0 flex items-center">
                
                <i class="fas fa-balance-scale text-2xl mr-3"></i>
                <a href="<?php echo calculate_relative_path('index.php', $is_subdir_page); ?>" class="flex items-center">
                    <span class="text-xl font-bold text-purple-600 dark:text-purple-400 hover:text-pink-500 dark:hover:text-pink-300 transition duration-150 ease-in-out">
                        <?php echo $site_name_header; ?>
                    </span>
                </a>
            </div>

            <!-- Desktop Navigation (Purple/Pink Theme) -->
            <nav class="hidden md:flex md:items-center md:space-x-2 lg:space-x-4" aria-label="Main Navigation">
                <?php
                    $nav_items = [
                        ['href' => 'index.php',           'label' => 'Home',         'page' => 'index.php'],
                        ['href' => 'compare_price.php',   'label' => 'Compare Price','page' => 'compare_price.php'],
                        ['href' => 'pages/how-it-works.php','label' => 'How It Works', 'page' => 'how-it-works.php'],
                        ['href' => 'pages/about.php',       'label' => 'About',        'page' => 'about.php'],
                        ['href' => 'pages/contact.php',     'label' => 'Contact',      'page' => 'contact.php'],
                    ];
                    if ($is_logged_in) {
                        $nav_items[] = ['href' => 'watchlist.php', 'label' => 'My Watchlist', 'page' => 'watchlist.php'];
                    }

                    foreach ($nav_items as $item):
                        $is_active = ($current_page === $item['page']);
                        $link_href = calculate_relative_path($item['href'], $is_subdir_page);

                        $active_class = 'bg-pink-100 dark:bg-purple-900 text-purple-700 dark:text-pink-300';
                        $inactive_class = 'text-gray-600 dark:text-gray-300 hover:bg-pink-50 dark:hover:bg-purple-700 hover:text-purple-800 dark:hover:text-white';
                        $common_class = 'px-3 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out';
                        $aria_current = $is_active ? 'aria-current="page"' : '';
                ?>
                    <a href="<?php echo $link_href; ?>"
                       class="<?php echo $is_active ? $active_class : $inactive_class; ?> <?php echo $common_class; ?>"
                       <?php echo $aria_current; ?>>
                        <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="flex items-center space-x-3 md:space-x-4">

                 <button id="theme-toggle" type="button" class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 h
                 over:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800 focus:ring-purple-500" aria-label="Toggle dark mode">
                     
                 </button>
                 <!-- End Theme Toggle Button -->

                 <?php if ($is_logged_in): // User is logged in ?>
                    <!-- User Dropdown (Using Purple Focus Ring) -->
                    <div class="relative" id="user-menu-container">
                        <div>
                            <button type="button" class="flex items-center max-w-xs rounded-full bg-white dark:bg-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-600 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open user menu</span>
                                <?php echo $avatar_html; // Uses dark:bg-gray-600 for placeholder ?>
                                <svg class="ml-1 -mr-1 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.23 8.29a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                        <div id="user-menu-dropdown" class="hidden absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white dark:bg-gray-700 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-600">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate"><?php echo $username; ?></p>
                            </div>
                            <div role="none">
                                
                                <a href="<?php echo calculate_relative_path('profile.php', $is_subdir_page); ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem" tabindex="-1" id="user-menu-item-0">Your Profile</a>
                                
                                <a href="<?php echo $logout_page_url; ?>" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-600 dark:hover:text-red-300" role="menuitem" tabindex="-1" id="user-menu-item-1">Logout</a>
                            </div>
                        </div>
                    </div>
                    <!-- End User Dropdown -->

                 <?php else: // Not logged in ?>
                    <a href="<?php echo $login_page_url; ?>" class="hidden sm:inline-block bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-md text-sm font-medium shadow-sm transition duration-150 ease-in-out <?php echo ($current_page == 'login.php') ? 'ring-2 ring-offset-2 ring-purple-500' : ''; ?>">Login</a>
                    <a href="<?php echo $register_page_url; ?>" class="hidden sm:inline-block bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-gray-100 px-3 py-1.5 rounded-md text-sm font-medium shadow-sm transition duration-150 ease-in-out <?php echo ($current_page == 'register.php') ? 'ring-2 ring-offset-2 ring-gray-400 dark:ring-gray-500' : ''; ?>">Register</a>
                 <?php endif; ?>

                 <!-- Mobile Menu Toggle Button (Using Purple Focus Ring) -->
                 <button id="mobile-menu-toggle" type="button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-purple-500" aria-controls="mobile-menu" aria-expanded="false">
                     <span class="sr-only">Open main menu</span>
                     <svg class="block h-6 w-6" id="icon-menu-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                     <svg class="hidden h-6 w-6" id="icon-menu-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                 </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu (Purple/Pink Theme) -->
    <div class="hidden md:hidden mobile-menu-overlay" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg">
             <?php
                // Reuse nav items array for mobile menu
                foreach ($nav_items as $item):
                    $is_active = ($current_page === $item['page']);
                    $link_href = calculate_relative_path($item['href'], $is_subdir_page);

                    // --- CSS classes for mobile (Same theme as desktop) ---
                    $active_class = 'bg-pink-100 dark:bg-purple-900 text-purple-700 dark:text-pink-300';
                    $inactive_class = 'text-gray-600 dark:text-gray-300 hover:bg-pink-50 dark:hover:bg-purple-700 hover:text-purple-800 dark:hover:text-white';
                    $common_class = 'block px-3 py-2 rounded-md text-base font-medium transition duration-150 ease-in-out'; // text-base for mobile
                    $aria_current = $is_active ? 'aria-current="page"' : '';
            ?>
                <a href="<?php echo $link_href; ?>"
                   class="<?php echo $is_active ? $active_class : $inactive_class; ?> <?php echo $common_class; ?>"
                   <?php echo $aria_current; ?>>
                    <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>

            <!-- Mobile Account Actions -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 pb-3 mt-3">
                 <?php if ($is_logged_in): ?>
                     <div class="flex items-center px-3 mb-3">
                         <div class="flex-shrink-0"><?php echo $avatar_html; ?></div>
                         <div class="ml-3">
                             <div class="text-base font-medium text-gray-800 dark:text-white"><?php echo $username; ?></div>
                             
                         </div>
                     </div>
                     <div class="space-y-1">
                          
                          <a href="<?php echo calculate_relative_path('profile.php', $is_subdir_page); ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">Your Profile</a>
                            
                        <a href="<?php echo $logout_page_url; ?>" class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700 hover:text-red-300">Logout</a>
                     </div>
                 <?php else: // --- User is Logged Out (Mobile) --- ?>
                     <div class="space-y-1">
                         <?php // Mobile Login uses Purple, Register uses Gray hover ?>
                         <a href="<?php echo $login_page_url; ?>" class="block px-3 py-2 rounded-md text-base font-medium text-purple-700 dark:text-purple-300 hover:bg-pink-50 dark:hover:bg-purple-700 hover:text-purple-800 dark:hover:text-white">Login</a>
                         <a href="<?php echo $register_page_url; ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">Register</a>
                     </div>
                 <?php endif; ?>
            </div>
        </div>
    </div>
</header>


<script>
    (function() {
        // Theme Toggle Logic (Unchanged)
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        const preferDarkQuery = window.matchMedia('(prefers-color-scheme: dark)');
        function applyTheme(theme) { 
            if (theme === 'dark') { 
                document.documentElement.classList.add('dark'); 
                if(themeToggleLightIcon) themeToggleLightIcon.classList.add('hidden'); 
                if(themeToggleDarkIcon) themeToggleDarkIcon.classList.remove('hidden'); 
            } else { 
                document.documentElement.classList.remove('dark'); 
                if(themeToggleDarkIcon) themeToggleDarkIcon.classList.add('hidden'); 
                if(themeToggleLightIcon) themeToggleLightIcon.classList.remove('hidden'); 
            } 
        }
        
        // User Menu Dropdown Logic (Unchanged)
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenuDropdown = document.getElementById('user-menu-dropdown');
        const userMenuContainer = document.getElementById('user-menu-container');
        if (userMenuButton && userMenuDropdown && userMenuContainer) {
             userMenuButton.addEventListener('click', function(event) { 
                event.stopPropagation(); 
                const isExpanded = userMenuButton.getAttribute('aria-expanded') === 'true'; userMenuDropdown.classList.toggle('hidden'); 
                userMenuButton.setAttribute('aria-expanded', String(!isExpanded)); 
            }); 
            document.addEventListener('click', function(event) { 
                if (!userMenuContainer.contains(event.target) && !userMenuDropdown.classList.contains('hidden')) { 
                    userMenuDropdown.classList.add('hidden'); userMenuButton.setAttribute('aria-expanded', 'false'); 
                } 
            }); 
            document.addEventListener('keydown', function(event) { 
                if (event.key === 'Escape' && !userMenuDropdown.classList.contains('hidden')) {
                     userMenuDropdown.classList.add('hidden'); 
                     userMenuButton.setAttribute('aria-expanded', 'false'); 
                     userMenuButton.focus(); 
                    } 
                }); 
            } else { 
                if (<?php echo json_encode($is_logged_in); ?> && !userMenuButton) { 
                    console.warn('User menu button (#user-menu-button) not found, but user appears logged in.'); 
                } 
            } 

        // Mobile Menu Toggle Logic (Unchanged)
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        const iconOpen = document.getElementById('icon-menu-open');
        const iconClosed = document.getElementById('icon-menu-closed');
        if (mobileMenuToggle && mobileMenu && iconOpen && iconClosed) { 
            mobileMenuToggle.addEventListener('click', function() { 
                const isExpanded = mobileMenuToggle.getAttribute('aria-expanded') === 'true'; mobileMenu.classList.toggle('hidden'); 
                iconOpen.classList.toggle('hidden'); iconClosed.classList.toggle('hidden'); mobileMenuToggle.setAttribute('aria-expanded', String(!isExpanded)); }); 
            }
            else { 
                console.warn('Mobile menu elements (toggle, menu, icons) not all found.'); 
            }
    })(); // End IIFE
</script>