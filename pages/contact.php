<?php
// File: pages/contact.php

// --- Error Handling FIRST ---
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
// -----------------------------

require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/functions.php';
require_once __DIR__ . '/../php/db_connect.php'; // Defines $conn (mysqli)

$page_title = "Contact Us - " . escape_html(SITE_NAME);

// Handle form submission (POST request from JavaScript)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/plain; charset=utf-8');
    if (!isset($conn) || !$conn instanceof mysqli) {
         error_log("Contact.php Error: Database connection variable \$conn is not available or not a mysqli object.");
         echo "Server configuration error. Cannot connect to database.";
         exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        echo "All fields are required."; exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format."; exit;
    }

    // Use 'messages' table
    $sql = "INSERT INTO messages (name, email, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Contact.php Prepare Error: " . $conn->error);
        echo "Failed to prepare database statement."; exit;
    }

    $stmt->bind_param("sss", $name, $email, $message);
    if ($stmt->execute()) {
        echo "success";
    } else {
        error_log("Contact.php Execute Error: " . $stmt->error);
        echo "Failed to save message to database.";
    }
    $stmt->close();
    exit; // Stop script execution after handling POST
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact information and form for the <?php echo escape_html(SITE_NAME); ?> team.">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="../favicon.ico" sizes="any">
    <link rel="icon" href="../images/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../images/apple-touch-icon.png">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        /* Minimal custom style if needed */
    </style>
</head>
<body class="bg-gray-100 text-gray-800" data-logged-in="<?php echo (isset($_SESSION['user_id']) || isset($_SESSION['account_loggedin'])) ? 'true' : 'false'; ?>">

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container mx-auto px-4 py-12 max-w-3xl"> 

        <h1 class="text-3xl md:text-4xl font-bold mt-10 mb-10 text-center text-blue-700">Contact Us</h1>

        <!-- Section 1: Contact Form -->
        <section class="bg-white p-6 sm:p-8 rounded-lg shadow-md mb-10">
            <h2 class="text-2xl font-semibold mb-6 text-center text-blue-600">Send Us a Message</h2>
            <form id="contactForm" method="POST" action="contact.php" class="space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                    <input type="text" id="name" name="name" placeholder="e.g., John Doe" class="w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" required />
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
                    <input type="email" id="email" name="email" placeholder="e.g., john.doe@example.com" class="w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" required />
                </div>
                 <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Your Message</label>
                    <textarea id="message" name="message" rows="5" placeholder="Type your inquiry or feedback here..." class="w-full p-3 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" required></textarea>
                </div>
                <div>
                    <button type="submit" id="submitButton" class="w-full bg-blue-600 text-white px-5 py-3 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out font-semibold">
                        Send Message
                    </button>
                </div>
                <p id="response" class="text-sm text-center h-5 mt-3"></p> <!-- Feedback area -->
            </form>
        </section>

         <!-- Section 2: Contact Details -->
        <section class="bg-white p-6 sm:p-8 rounded-lg shadow-md mb-10">
            <h2 class="text-2xl font-semibold mb-6 text-center text-blue-600">Get in Touch Directly</h2>
            <p class="text-center mb-8 text-gray-600">
                For inquiries, feedback, or support regarding the <?php echo escape_html(SITE_NAME); ?>, please reach out to us using the details below.
            </p>
            <address class="not-italic space-y-6 max-w-md mx-auto text-gray-700"> 
                <p class="flex items-start">
                    <span class="text-blue-600 mr-4 mt-1 flex-shrink-0 w-5"> 
                        <!-- Email Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <span>
                        <strong>Email:</strong><br>
                        <a href="mailto:info@gemcompare.example.com" class="text-blue-600 hover:underline hover:text-blue-800 break-all">info@gemcompare.example.com</a>
                    </span>
                </p>
                <p class="flex items-start">
                     <span class="text-blue-600 mr-4 mt-1 flex-shrink-0 w-5"> 
                        <!-- Phone Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                     </span>
                     <span>
                        <strong>Phone:</strong><br>
                        +91 123 456 7890 <br>
                        <span class="text-xs text-gray-500">(Mon-Fri, 10 AM - 5 PM IST)</span>
                     </span>
                </p>
                <p class="flex items-start">
                     <span class="text-blue-600 mr-4 mt-1 flex-shrink-0 w-5"> 
                        <!-- Location Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                     </span>
                     <span>
                        <strong>Address:</strong><br>
                        123 Tech Park Road,<br> Innovation City, IN 560001, India
                     </span>
                </p>
            </address>
        </section>

        <!-- Section 3: Follow Us -->
        <section class="bg-white p-6 sm:p-8 rounded-lg shadow-md text-center">
             <h2 class="text-2xl font-semibold mb-6 text-blue-600">Follow Us</h2>
             <div class="flex justify-center gap-8"> 
                 <a href="https://instagram.com/your_insta" target="_blank" title="Instagram" class="text-gray-600 hover:text-[#E1306C] transition duration-150 ease-in-out">
                     <span class="sr-only">Instagram</span>
                     <!-- Instagram SVG -->
                     <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                 </a>
                 <a href="mailto:alok.work85@gmail.com" title="Email" class="text-gray-600 hover:text-red-600 transition duration-150 ease-in-out">
                      <span class="sr-only">Email</span>
                      <!-- Email SVG -->
                     <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M0 3v18h24v-18h-24zm21.518 2l-9.518 7.713-9.518-7.713h19.036zm-19.518 14v-11.817l10 8.104 10-8.104v11.817h-20z"/></svg>
                 </a>
                 <a href="https://twitter.com/your_twitter" target="_blank" title="Twitter / X" class="text-gray-600 hover:text-black transition duration-150 ease-in-out">
                      <span class="sr-only">Twitter</span>
                      <!-- Twitter / X SVG -->
                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 16 16" fill="currentColor" class="w-7 h-7"><path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865l8.875 11.633Z"/></svg>
                 </a>
             </div>
         </section>

    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="../js/script.js?v=<?php echo filemtime(__DIR__ . '/../js/script.js'); ?>"></script>
    <script>
        // Enhanced JavaScript for better feedback (same as previous version)
        document.getElementById("contactForm").addEventListener("submit", function (e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            const responseElement = document.getElementById("response");
            const submitButton = document.getElementById("submitButton");
            const originalButtonText = submitButton.innerHTML; // Store original text

            responseElement.textContent = ""; // Clear previous message
            responseElement.className = "text-sm text-center h-5 mt-3"; // Reset classes
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <span class="inline-flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sending...
                </span>`;

            fetch(form.action, { method: "POST", body: formData })
            .then(response => {
                if (!response.ok) {
                     return response.text().then(text => { throw new Error(text || `HTTP error ${response.status}`) });
                }
                 return response.text();
            })
            .then(data => {
                const message = data.trim();
                if (message.toLowerCase() === "success") {
                    responseElement.textContent = "Message sent successfully!";
                    responseElement.className = "text-sm text-center h-5 mt-3 text-green-600 font-medium";
                    form.reset();
                } else {
                    responseElement.textContent = "Error: " + message;
                    responseElement.className = "text-sm text-center h-5 mt-3 text-red-600 font-medium";
                }
            })
            .catch(error => {
                console.error("Fetch Error:", error);
                responseElement.textContent = "Something went wrong. Please try again.";
                 responseElement.className = "text-sm text-center h-5 mt-3 text-red-600 font-medium";
            })
            .finally(() => {
                 submitButton.disabled = false;
                 submitButton.innerHTML = originalButtonText; // Restore original button text
                 setTimeout(() => { responseElement.textContent = ''; }, 6000);
            });
        });
    </script>
</body>
</html>