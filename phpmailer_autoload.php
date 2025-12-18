<?php
/**
 * PHPMailer Manual Autoloader
 * Use this if you don't have Composer installed
 * 
 * To install PHPMailer manually:
 * 1. Download: https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip
 * 2. Extract the ZIP file
 * 3. Copy the 'src' folder and rename it to 'PHPMailer'
 * 4. Upload 'PHPMailer' folder to your project root
 */

// Define the base path for PHPMailer
$phpmailerPath = __DIR__ . '/PHPMailer/';

// Check if PHPMailer directory exists
if (!file_exists($phpmailerPath)) {
    die('PHPMailer not found. Please install PHPMailer manually. See INSTALL_PHPMAILER_MANUAL.md');
}

// Manually include PHPMailer files
require_once $phpmailerPath . 'Exception.php';
require_once $phpmailerPath . 'PHPMailer.php';
require_once $phpmailerPath . 'SMTP.php';

// Optional files (uncomment if needed)
// require_once $phpmailerPath . 'OAuth.php';
// require_once $phpmailerPath . 'POP3.php';

// Set up class aliases to match Composer structure
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    class_alias('PHPMailer', 'PHPMailer\PHPMailer\PHPMailer');
    class_alias('PHPMailer\Exception', 'PHPMailer\PHPMailer\Exception');
    class_alias('PHPMailer\SMTP', 'PHPMailer\PHPMailer\SMTP');
}
?>