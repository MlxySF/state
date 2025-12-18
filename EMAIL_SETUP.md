# Email Notification System Setup

This document explains how to configure and use the email notification system for the Wushu Sport Academy registration system.

## Overview

The system automatically sends email notifications to users when their payment status is updated:
- **Approved**: Confirmation email with registration details
- **Rejected**: Notification asking them to resubmit payment receipt

## Configuration

### 1. Email Sender Address

Edit `api/send_email.php` and change the sender email:

```php
$from = "noreply@wushusportacademy.com"; // Change to your domain
$fromName = "Wushu Sport Academy";
```

### 2. Server Email Setup

#### Option A: Using PHP mail() (Default)

The system uses PHP's built-in `mail()` function. Your server must have a mail server configured:

**For cPanel/WHM:**
- Email should work automatically
- Check PHP mail logs in cPanel

**For VPS/Dedicated Server:**
```bash
# Install sendmail or postfix
sudo apt-get install sendmail
# OR
sudo apt-get install postfix

# Start the service
sudo service sendmail start
# OR
sudo service postfix start
```

#### Option B: Using SMTP (Recommended for Gmail, Outlook, etc.)

For better deliverability, use SMTP instead of `mail()`. Install PHPMailer:

```bash
composer require phpmailer/phpmailer
```

Then modify `api/send_email.php` to use PHPMailer:

```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendPaymentEmail($to, $name, $registrationNumber, $status, $paymentAmount) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Or your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password'; // Use App Password for Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Email settings
        $mail->setFrom('noreply@wushusportacademy.com', 'Wushu Sport Academy');
        $mail->addAddress($to, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $status === 'approved' ? 'Payment Approved' : 'Payment Verification Required';
        $mail->Body = getEmailTemplate($status, $name, $registrationNumber, $paymentAmount);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
```

### 3. Gmail Configuration (If using Gmail SMTP)

1. Enable 2-Step Verification in your Google Account
2. Generate an App Password:
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Copy the generated password
3. Use this App Password in the SMTP configuration

## Email Templates

### Approval Email Includes:
- ✅ Green themed professional design
- Registration number
- Payment amount
- Confirmation message
- Next steps information
- Academy contact details

### Rejection Email Includes:
- ⚠️ Red/orange themed design
- Registration number
- Possible reasons for rejection
- Instructions to resubmit
- Contact information for assistance

## Testing

### Test Email Functionality

1. Create a test registration
2. Go to admin dashboard
3. Approve or reject the payment
4. Check if email is received
5. Check server logs for any errors:

```bash
# Check PHP error log
tail -f /var/log/apache2/error.log
# OR
tail -f /var/log/php_errors.log
```

### Troubleshooting

**Email not sending:**
1. Check PHP mail configuration: `php -i | grep mail`
2. Check server mail logs
3. Verify sender email domain is valid
4. Try using SMTP instead of mail()

**Emails going to spam:**
1. Set up SPF record for your domain
2. Set up DKIM authentication
3. Use a valid reply-to address
4. Consider using a dedicated email service (SendGrid, AWS SES, Mailgun)

**SMTP Authentication Failed:**
1. Verify username and password
2. Check if 2FA is enabled (use App Password)
3. Verify SMTP host and port
4. Check firewall settings

## Email Logs

All email attempts are logged to PHP error log:
- Success: `Email sent to {email} - Status: {status} - Success: Yes`
- Failure: `Email sent to {email} - Status: {status} - Success: No`

## Production Recommendations

### For High Volume:
1. Use a dedicated email service:
   - **SendGrid**: Free tier includes 100 emails/day
   - **AWS SES**: $0.10 per 1000 emails
   - **Mailgun**: Free tier includes 5000 emails/month
   - **Postmark**: Reliable transactional email service

2. Implement email queue system
3. Add email retry logic
4. Monitor delivery rates
5. Set up bounce handling

### Security Best Practices:
1. Never commit email credentials to git
2. Use environment variables for sensitive data
3. Implement rate limiting
4. Add unsubscribe links (for marketing emails)
5. Comply with email regulations (CAN-SPAM, GDPR)

## Features

✅ **Automatic Notifications**: Emails sent automatically when status changes
✅ **Professional Templates**: HTML emails with brand styling
✅ **Error Handling**: Graceful failure handling
✅ **Logging**: All email attempts logged
✅ **Responsive Design**: Mobile-friendly email templates
✅ **Status-Specific Content**: Different messages for approval/rejection

## Support

If you encounter issues with email delivery, check:
1. Server email configuration
2. PHP mail function enabled
3. Firewall/port restrictions
4. Email logs for error messages
5. Spam folder in recipient inbox

For advanced email setup, consider hiring a system administrator or using a managed email service.

---

**Last Updated**: December 18, 2025
**Version**: 1.0.0