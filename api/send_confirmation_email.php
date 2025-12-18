<?php
// Registration Confirmation Email
// Sends confirmation email to users after successful registration submission

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Try to load PHPMailer - check both Composer and manual installation
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../phpmailer_autoload.php')) {
    require __DIR__ . '/../phpmailer_autoload.php';
} else {
    error_log('PHPMailer not found for confirmation email');
    return false;
}

// Load email configuration
$emailConfig = require __DIR__ . '/../email_config.php';

function sendRegistrationConfirmationEmail($to, $name, $registrationNumber, $events, $schedule, $paymentAmount) {
    global $emailConfig;
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = $emailConfig['smtp_debug'];
        $mail->isSMTP();
        $mail->Host       = $emailConfig['smtp_host'];
        $mail->SMTPAuth   = $emailConfig['smtp_auth'];
        $mail->Username   = $emailConfig['smtp_username'];
        $mail->Password   = $emailConfig['smtp_password'];
        $mail->SMTPSecure = $emailConfig['smtp_secure'];
        $mail->Port       = $emailConfig['smtp_port'];
        $mail->CharSet    = $emailConfig['charset'];
        $mail->Timeout    = $emailConfig['timeout'];
        
        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($to, $name);
        $mail->addReplyTo($emailConfig['reply_to'], $emailConfig['from_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Registration Received - Pending Verification';
        $mail->Body    = getConfirmationEmailTemplate($name, $registrationNumber, $events, $schedule, $paymentAmount);
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $mail->Body));
        
        // Send email
        $mail->send();
        
        error_log("Confirmation email sent successfully to {$to}");
        return true;
        
    } catch (Exception $e) {
        error_log("Confirmation email error for {$to}: {$mail->ErrorInfo}");
        return false;
    }
}

function getConfirmationEmailTemplate($name, $registrationNumber, $events, $schedule, $paymentAmount) {
    return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #3498db; border-radius: 5px; }
        .status-box { background: #fff8e1; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .icon { font-size: 48px; color: white; }
        h1 { margin: 10px 0 0 0; }
        ul { margin: 10px 0; padding-left: 20px; }
        .highlight { background: #e3f2fd; padding: 2px 6px; border-radius: 3px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">✉️</div>
            <h1>Registration Received!</h1>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($name) . ',</p>
            
            <p>Thank you for registering with <strong>Wushu Sport Academy</strong>! We have successfully received your registration and payment receipt.</p>
            
            <div class="info-box">
                <strong>🎯 Registration Details:</strong><br><br>
                <strong>Registration Number:</strong> <span class="highlight">' . htmlspecialchars($registrationNumber) . '</span><br>
                <strong>Name:</strong> ' . htmlspecialchars($name) . '<br>
                <strong>Selected Classes:</strong> ' . htmlspecialchars($events) . '<br>
                <strong>Schedule:</strong> ' . htmlspecialchars($schedule) . '<br>
                <strong>Payment Amount:</strong> RM ' . number_format($paymentAmount, 2) . '
            </div>
            
            <div class="status-box">
                <strong>⏳ Current Status: Pending Verification</strong><br><br>
                Your registration is currently under review by our admin team. We are verifying your payment receipt and registration details.
            </div>
            
            <p><strong>📝 What happens next?</strong></p>
            <ul>
                <li><strong>Payment Verification:</strong> Our admin will review your payment receipt within 1-2 business days</li>
                <li><strong>Email Notification:</strong> You will receive an email once your payment is approved or if any additional information is needed</li>
                <li><strong>Class Details:</strong> After approval, you will receive detailed class schedule and start date information</li>
                <li><strong>Keep Your Registration Number:</strong> Please save <strong>' . htmlspecialchars($registrationNumber) . '</strong> for future reference</li>
            </ul>
            
            <p><strong>ℹ️ Important Information:</strong></p>
            <ul>
                <li>Please check your email regularly (including spam/junk folder)</li>
                <li>If you need to update any information, contact us with your registration number</li>
                <li>For urgent inquiries, please contact our support team</li>
            </ul>
            
            <p>If you have any questions or concerns, please don\'t hesitate to contact us.</p>
            
            <p>Best regards,<br>
            <strong>Wushu Sport Academy Team</strong></p>
        </div>
        <div class="footer">
            <p><strong>Your Registration Number: ' . htmlspecialchars($registrationNumber) . '</strong></p>
            <p>This is an automated confirmation message. Please do not reply to this email.</p>
            <p>&copy; ' . date('Y') . ' Wushu Sport Academy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
    ';
}

// Return functions for external use
return [
    'sendRegistrationConfirmationEmail' => 'sendRegistrationConfirmationEmail'
];
?>