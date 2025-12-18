<?php
// Email Configuration
// Configure your SMTP settings here

return [
    // SMTP Server Settings
    'smtp_host' => 'smtp.gmail.com',              // SMTP server (e.g., smtp.gmail.com, smtp.office365.com)
    'smtp_port' => 587,                            // SMTP port (587 for TLS, 465 for SSL)
    'smtp_secure' => 'tls',                        // Encryption: 'tls' or 'ssl'
    'smtp_auth' => true,                           // Enable SMTP authentication
    
    // SMTP Credentials
    'smtp_username' => 'your-email@gmail.com',     // Your email address
    'smtp_password' => 'your-app-password',        // Your email password or App Password
    
    // Email Sender Information
    'from_email' => 'noreply@wushusportacademy.com',
    'from_name' => 'Wushu Sport Academy',
    'reply_to' => 'support@wushusportacademy.com', // Reply-to email address
    
    // Email Settings
    'charset' => 'UTF-8',
    'timeout' => 30,                               // Connection timeout in seconds
    
    // Debug Mode (0 = off, 1 = client messages, 2 = client and server messages)
    'smtp_debug' => 0,
];

/* 
 * IMPORTANT NOTES:
 * 
 * 1. For Gmail:
 *    - Enable 2-Step Verification
 *    - Generate App Password: https://myaccount.google.com/apppasswords
 *    - Use the App Password, not your regular password
 * 
 * 2. For Office 365/Outlook:
 *    - Host: smtp.office365.com
 *    - Port: 587
 *    - Use your full email as username
 * 
 * 3. For other providers:
 *    - Check your email provider's SMTP settings
 *    - Common hosts: smtp.mail.yahoo.com, smtp.zoho.com, etc.
 * 
 * 4. Security:
 *    - Never commit this file with real credentials to public repositories
 *    - Consider using environment variables for production
 *    - Keep this file in .gitignore
 */
?>