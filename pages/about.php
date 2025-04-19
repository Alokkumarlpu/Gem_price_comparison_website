<?php
// Include necessary files relative to the '/pages' directory
require_once __DIR__ . '/../php/config.php'; // Go up one level for php folder
require_once __DIR__ . '/../php/functions.php';

// Page specific variables
$page_title = "About Us - " . escape_html(SITE_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Learn more about the GeM Price Comparison tool, our mission to enhance transparency in public procurement, and how we compare prices between GeM and other marketplaces.">
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
        /* Ensure shared page styles defined in style.css apply */
        /* Example: .page-header, .page-content-wrapper */
        .team-section { /* Example placeholder styling */
            text-align: center;
            padding: 30px;
            background-color: var(--light-gray);
            border-radius: var(--border-radius);
            margin-top: 2em;
            border: 1px solid var(--medium-gray);
         }
         .team-section h3 {
            margin-bottom: 1.5em;
            color: var(--primary-color);
         }
         .team-member {
            margin-bottom: 1em;
         }
    </style>
</head>
<body>

    <?php include __DIR__ . '/../includes/header.php'; // Adjust path for include ?>

        
  <!-- Hero Section - Matched with main page styling -->
  <header class="bg-purple-500 text-white transition-colors duration-300">
    <div class="container mx-auto px-6 py-12">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-4xl font-bold mb-4">About CartClash</h1>
        <p class="text-xl mb-6">We offer real-time price tracking across GeM and other trusted online stores, so you always get the best deal without the extra effort. Our clean, ad-free interface keeps your comparison experience smooth. Plus, with price drop alerts and tracking tools coming soon, saving money is about to get even easier.</p>
      </div>
    </div>
  </header>

  <!-- Mission Section -->
  <section id="mission" class="py-12 bg-white transition-colors duration-300">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl font-bold text-center mb-8">Our Goal</h2>
      <div class="max-w-3xl mx-auto text-center">
        <p class="text-lg text-gray-600 transition-colors duration-300">At CartClash, we're on a mission to make shopping smarter, easier, and more transparent. Tired of bouncing between tabs just to find the best price? So were we. That's why we built a one-stop platform that compares prices across GeM and other top online stores—helping you save time, money, and frustration. Whether you're looking for the latest tech, office equipment, or everyday essentials, we've got your back.</p>
      </div>
    </div>
  </section>

  <!-- Team Section - Styled to match main page aesthetic -->
  <section class="py-12 bg-gray-100 transition-colors duration-300">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl font-bold text-center mb-8">Meet the Team</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
        <div class="team-member bg-white rounded-lg shadow-lg p-6 text-center transition-colors duration-300">
          <img src="../images/anvesh.jpeg" alt="Anvesh" class="ml-16 w-40 h-40 rounded-full mb-4 border-4 border-purple-200">
          <h4 class="text-xl font-semibold">Anvesh</h4>
          <p class="text-gray-600 transition-colors duration-300">Founder & Developer</p>
        </div>
        <div class="team-member bg-white rounded-lg shadow-lg p-6 text-center transition-colors duration-300">
          <img src="../images/pallavi.jpeg" alt="Pallavi" class="ml-16 w-40 h-40 rounded-full mb-4 border-4 border-purple-200">
          <h4 class="text-xl font-semibold">Pallavi</h4>
          <p class="text-gray-600 transition-colors duration-300">UI Designer</p>
        </div>
        <div class="team-member bg-white rounded-lg shadow-lg p-6 text-center transition-colors duration-300">
          <img src="../images/devesh.jpeg" alt="Devesh" class="ml-16 w-40 h-40 flex justify-center item-center rounded-full mb-4 border-4 border-purple-200">
          <h4 class="text-xl font-semibold">Devesh</h4>
          <p class="text-gray-600 transition-colors duration-300">Data Analyst</p>
        </div>
        <div class="team-member bg-white rounded-lg shadow-lg p-6 text-center transition-colors duration-300">
          <img src="../images/alok.jpeg" alt="Alok" class="ml-16 w-40 h-40 rounded-full mb-4 border-4 border-purple-200">
          <h4 class="text-xl font-semibold">Alok</h4>
          <p class="text-gray-600 transition-colors duration-300">Backend Developer</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Vision Section - Added to match the structure of main page -->
  <section class="py-12 bg-white transition-colors duration-300">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl font-bold text-center mb-8">Our Vision</h2>
      <div class="max-w-3xl mx-auto text-center">
        <p class="text-lg text-gray-600 transition-colors duration-300 mb-6">CartClash aims to revolutionize how government agencies, businesses, and consumers make purchasing decisions by providing transparent price comparisons between GeM and major e-marketplaces.</p>
        <p class="text-lg text-gray-600 transition-colors duration-300">Our goal is to ensure every purchase made is informed, optimized, and delivers the best value possible. By bridging the gap between government procurement and commercial marketplaces, we help buyers make smarter choices.</p>
      </div>
    </div>
  </section>

  <!-- Supported Marketplaces Section -->
  <section class="py-12 bg-gray-50 transition-colors duration-300">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl font-bold text-center mb-8">Supported Marketplaces</h2>
      <div class="flex flex-wrap justify-center gap-8">
        <div class="bg-white p-4 rounded-lg shadow transition-colors duration-300">
          <div class="text-blue-600 font-bold text-xl">GeM</div>
          <div class="text-sm text-gray-600 transition-colors duration-300">Government e-Marketplace</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow transition-colors duration-300">
          <div class="text-green-600 dark:text-green-400 font-bold text-xl">Amazon</div>
          <div class="text-sm text-gray-600 transition-colors duration-300">Amazon India</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow transition-colors duration-300">
          <div class="text-purple-600 font-bold text-xl">Flipkart</div>
          <div class="text-sm text-gray-600 transition-colors duration-300">Flipkart</div>
        </div>
      </div>
    </div>
  </section>

        

    
    <?php include __DIR__ . '/../includes/footer.php'; // Adjust path for include ?>
    <body data-logged-in="<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>">
    <!-- Main JavaScript File - Adjust path -->
    <script src="../js/script.js?v=<?php echo filemtime(__DIR__ . '/../js/script.js'); ?>"></script>

    <!-- Optional: Add page-specific JS here -->

</body>
</html>