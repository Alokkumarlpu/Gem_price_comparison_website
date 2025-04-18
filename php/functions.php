<?php

function connect_db(): ?mysqli {
    mysqli_report(MYSQLI_REPORT_OFF); // Turn off default exception reporting for mysqli

    $conn = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, defined('DB_PORT') ? DB_PORT : 3306);

    // Check for connection errors
    if ($conn->connect_error) {
        error_log("Database Connection Failed: (" . $conn->connect_errno . ") " . $conn->connect_error);

        // Return null to indicate failure. The calling script must check for null.
        return null;
    }

    // Set character set to utf8mb4 for proper handling of diverse characters (including emojis)
    if (!$conn->set_charset("utf8mb4")) {
         // Log error if setting charset fails
         error_log("Error loading character set utf8mb4: " . $conn->error);
    }

    // Return the connection object
    return $conn;
}

function escape_html(?string $data): string {
    // If data is null, treat it as an empty string
    $data = $data ?? '';
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect(string $url, int $statusCode = 302): void {
    if (headers_sent()) {
        // Optional: Log an error or display a fallback message
        error_log("Redirect failed: Headers already sent before redirecting to " . $url);
        echo "Redirecting... If you are not redirected automatically, <a href=\"" . escape_html($url) . "\">click here</a>.";
    } else {
        // Set the appropriate redirect header
        header('Location: ' . $url, true, $statusCode);
    }
    // Stop script execution immediately after sending the header
    exit;
}

function format_inr($amount, bool $showSymbol = true, string $na_text = 'N/A'): string {
    if ($amount === null || !is_numeric($amount)) {
        return $na_text;
    }

    $formatted_amount = number_format((float)$amount, 2, '.', ','); // Format with 2 decimal places, comma separators

    return $showSymbol ? '₹' . $formatted_amount : $formatted_amount;
}


?>