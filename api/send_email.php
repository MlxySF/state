<?php
// Email Notification System with PHPMailer
// Sends emails for payment approval/rejection notifications using SMTP
// Works without Composer - uses manual PHPMailer loading

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Try to load PHPMailer - check both Composer and manual installation
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // Composer installation
    require __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../phpmailer_autoload.php')) {
    // Manual installation
    require __DIR__ . '/../phpmailer_autoload.php';
} else {
    die('PHPMailer not found. Please install PHPMailer. Download from: https://github.com/PHPMailer/PHPMailer/releases');
}

// Load email configuration
$emailConfig = require __DIR__ . '/../email_config.php';

function sendPaymentEmail($to, $name, $registrationNumber, $status, $paymentAmount) {
    global $emailConfig;
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = $emailConfig['smtp_debug'];                      // Debug output
        $mail->isSMTP();                                                    // Send using SMTP
        $mail->Host       = $emailConfig['smtp_host'];                     // SMTP server
        $mail->SMTPAuth   = $emailConfig['smtp_auth'];                     // Enable authentication
        $mail->Username   = $emailConfig['smtp_username'];                 // SMTP username
        $mail->Password   = $emailConfig['smtp_password'];                 // SMTP password
        $mail->SMTPSecure = $emailConfig['smtp_secure'];                   // Enable encryption
        $mail->Port       = $emailConfig['smtp_port'];                     // TCP port
        $mail->CharSet    = $emailConfig['charset'];                       // Character set
        $mail->Timeout    = $emailConfig['timeout'];                       // SMTP timeout
        
        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($to, $name);                                     // Add recipient
        $mail->addReplyTo($emailConfig['reply_to'], $emailConfig['from_name']);
        
        // Content
        $mail->isHTML(true);                                               // Set email format to HTML
        
        if ($status === 'approved') {
            $mail->Subject = 'Payment Approved - Registration Confirmed';
            $mail->Body    = getApprovedEmailTemplate($name, $registrationNumber, $paymentAmount);
        } else if ($status === 'rejected') {
            $mail->Subject = 'Payment Verification Required - Action Needed';
            $mail->Body    = getRejectedEmailTemplate($name, $registrationNumber);
        } else {
            throw new Exception('Invalid email status');
        }
        
        // Plain text alternative
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $mail->Body));
        
        // Send email
        $mail->send();
        
        error_log("PHPMailer: Email sent successfully to {$to} - Status: {$status}");
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: Could not send email to {$to}. Error: {$mail->ErrorInfo}");
        return false;
    }
}

function getApprovedEmailTemplate($name, $registrationNumber, $paymentAmount) {
    return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #27ae60; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .checkmark { font-size: 48px; color: white; }
        h1 { margin: 10px 0 0 0; }
        ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="checkmark">✓</div>
            <h1>Payment Approved!</h1>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($name) . ',</p>
            
            <p>Great news! Your payment has been verified and approved. Your registration with Wushu Sport Academy is now confirmed.</p>
            
            <div class="info-box">
                <strong>Registration Details:</strong><br><br>
                Registration Number: <strong>' . htmlspecialchars($registrationNumber) . '</strong><br>
                Payment Amount: <strong>RM ' . number_format($paymentAmount, 2) . '</strong><br>
                Status: <strong style="color: #27ae60;">APPROVED ✓</strong>
            </div>
            
            <p><strong>What happens next?</strong></p>
            <ul>
                <li>Your registration is now active in our system</li>
                <li>You will receive class schedule details via email within 1-2 business days</li>
                <li>Please keep your registration number for future reference</li>
                <li>Our team will contact you if any additional information is needed</li>
            </ul>
            
            <p>Thank you for choosing Wushu Sport Academy. We look forward to seeing you in class!</p>
            
            <p>If you have any questions, please don\'t hesitate to contact us.</p>
            
            <p>Best regards,<br>
            <strong>Wushu Sport Academy Team</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; ' . date('Y') . ' Wushu Sport Academy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
    ';
}

function getRejectedEmailTemplate($name, $registrationNumber) {
    return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #e74c3c; border-radius: 5px; }
        .warning-box { background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .icon { font-size: 48px; color: white; }
        h1 { margin: 10px 0 0 0; }
        ul, ol { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">⚠️</div>
            <h1>Payment Verification Required</h1>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($name) . ',</p>
            
            <p>We have reviewed your payment submission for registration <strong>' . htmlspecialchars($registrationNumber) . '</strong>, and we need you to resubmit your payment receipt.</p>
            
            <div class="info-box">
                <strong>Registration Number:</strong> ' . htmlspecialchars($registrationNumber) . '<br><br>
                <strong>Status:</strong> <span style="color: #e74c3c;">Payment Verification Required</span>
            </div>
            
            <div class="warning-box">
                <strong>Possible reasons for rejection:</strong>
                <ul style="margin: 10px 0;">
                    <li>Payment receipt is unclear or unreadable</li>
                    <li>Payment amount does not match the registration fee</li>
                    <li>Payment receipt appears to be incomplete</li>
                    <li>Wrong payment method or account details</li>
                </ul>
            </div>
            
            <p><strong>What you need to do:</strong></p>
            <ol>
                <li>Check that your payment receipt is clear and shows all necessary details</li>
                <li>Verify that the payment amount matches your registration fee</li>
                <li>Take a new photo or screenshot if the original was unclear</li>
                <li>Contact us for assistance or to upload a new payment receipt</li>
            </ol>
            
            <p><strong>Need help?</strong> Please reply to this email or contact us directly with your registration number.</p>
            
            <p>We apologize for any inconvenience and are here to help you complete your registration.</p>
            
            <p>Best regards,<br>
            <strong>Wushu Sport Academy Team</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>For assistance, please contact us with your registration number: ' . htmlspecialchars($registrationNumber) . '</p>
            <p>&copy; ' . date('Y') . ' Wushu Sport Academy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
    ';
}

// Return functions for external use
return [
    'sendPaymentEmail' => 'sendPaymentEmail'
];
?>