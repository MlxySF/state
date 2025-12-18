# PHPMailer Setup Guide

Complete guide to set up PHPMailer for email notifications in the Wushu Sport Academy Registration System.

## 🚀 Quick Start

### Step 1: Install Composer (if not already installed)

**For cPanel:**
```bash
cd ~/public_html/your-project-folder
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

**For VPS/Linux:**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**For Windows:**
Download and install from: https://getcomposer.org/download/

### Step 2: Install PHPMailer

Navigate to your project directory and run:

```bash
composer install
```

This will install PHPMailer and create the `vendor/` directory.

### Step 3: Configure Email Settings

Edit `email_config.php` with your SMTP credentials:

```php
return [
    'smtp_host' => 'smtp.gmail.com',           // Your SMTP server
    'smtp_port' => 587,                         // SMTP port
    'smtp_secure' => 'tls',                     // tls or ssl
    'smtp_auth' => true,
    
    'smtp_username' => 'your-email@gmail.com',  // Your email
    'smtp_password' => 'your-app-password',     // App password
    
    'from_email' => 'noreply@yourdomain.com',
    'from_name' => 'Wushu Sport Academy',
    'reply_to' => 'support@yourdomain.com',
    
    'smtp_debug' => 0,  // Set to 2 for debugging
];
```

---

## 📧 Provider-Specific Setup

### Gmail Setup (Recommended for Testing)

#### 1. Enable 2-Step Verification
1. Go to your Google Account: https://myaccount.google.com
2. Click **Security**
3. Enable **2-Step Verification**

#### 2. Generate App Password
1. Go to: https://myaccount.google.com/apppasswords
2. Select **Mail** and **Other (Custom name)**
3. Type "Wushu Academy" as the name
4. Click **Generate**
5. Copy the 16-character password

#### 3. Configure email_config.php
```php
'smtp_host' => 'smtp.gmail.com',
'smtp_port' => 587,
'smtp_secure' => 'tls',
'smtp_username' => 'youremail@gmail.com',
'smtp_password' => 'abcd efgh ijkl mnop',  // The 16-char app password
```

---

### Office 365 / Outlook Setup

```php
'smtp_host' => 'smtp.office365.com',
'smtp_port' => 587,
'smtp_secure' => 'tls',
'smtp_username' => 'youremail@outlook.com',
'smtp_password' => 'your-password',
```

---

### Yahoo Mail Setup

```php
'smtp_host' => 'smtp.mail.yahoo.com',
'smtp_port' => 465,
'smtp_secure' => 'ssl',
'smtp_username' => 'youremail@yahoo.com',
'smtp_password' => 'your-app-password',  // Generate at Yahoo Account Security
```

---

### Custom Domain / cPanel Email

```php
'smtp_host' => 'mail.yourdomain.com',  // Or your server hostname
'smtp_port' => 587,
'smtp_secure' => 'tls',
'smtp_username' => 'noreply@yourdomain.com',
'smtp_password' => 'your-email-password',
```

**Find your SMTP settings in cPanel:**
1. Login to cPanel
2. Go to **Email Accounts**
3. Click **Configure Email Client**
4. Use the SMTP settings shown

---

## 🧪 Testing

### Enable Debug Mode

In `email_config.php`, set:
```php
'smtp_debug' => 2,  // Shows SMTP communication
```

Debug levels:
- `0` = Off (production)
- `1` = Client messages
- `2` = Client and server messages (recommended for testing)
- `3` = Plus connection status
- `4` = Low-level data output

### Test Email Sending

1. Go to admin dashboard
2. Find any registration
3. Click "Approve Payment" or "Reject Payment"
4. Check the user's email inbox
5. Check server error logs:

```bash
tail -f /path/to/error_log
```

### Check Logs

All email attempts are logged:
```
PHPMailer: Email sent successfully to user@example.com - Status: approved
PHPMailer Error: Could not send email to user@example.com. Error: [error details]
```

---

## 🔧 Troubleshooting

### Error: "SMTP connect() failed"

**Solutions:**
1. Check SMTP host and port are correct
2. Verify firewall allows outbound connections on port 587/465
3. Check if your hosting provider blocks SMTP
4. Try using SSL (port 465) instead of TLS (port 587)

```php
'smtp_port' => 465,
'smtp_secure' => 'ssl',
```

### Error: "SMTP AUTH failed"

**Solutions:**
1. Verify username and password are correct
2. For Gmail: Use App Password, not regular password
3. Check if "Less secure app access" is enabled (if not using App Password)
4. Try re-generating App Password

### Error: "Could not authenticate"

**Solutions:**
1. Check 2-Step Verification is enabled (Gmail)
2. Verify App Password is entered correctly (no spaces)
3. Make sure username includes full email address
4. Check account is not locked or suspended

### Emails Going to Spam

**Solutions:**
1. Set up SPF record for your domain
2. Set up DKIM authentication
3. Use a verified sender email address
4. Avoid spam trigger words in subject/body
5. Consider using a dedicated email service

### Vendor Directory Not Found

**Solutions:**
```bash
# Make sure you're in the project root
cd /path/to/your/project

# Install dependencies
composer install

# If composer command not found, use:
php composer.phar install
```

---

## 🔒 Security Best Practices

### 1. Never Commit Credentials

```bash
# Make sure email_config.php is in .gitignore
echo "email_config.php" >> .gitignore
git rm --cached email_config.php  # If already committed
```

### 2. Use Environment Variables (Production)

Create `.env` file:
```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your@email.com
SMTP_PASSWORD=your-app-password
```

Update `email_config.php`:
```php
return [
    'smtp_host' => getenv('SMTP_HOST'),
    'smtp_username' => getenv('SMTP_USERNAME'),
    'smtp_password' => getenv('SMTP_PASSWORD'),
    // ...
];
```

### 3. Restrict File Permissions

```bash
chmod 600 email_config.php  # Only owner can read/write
chmod 600 .env              # Protect environment file
```

### 4. Use Different Credentials for Dev/Production

- **Development**: Use Gmail with App Password
- **Production**: Use dedicated SMTP service or domain email

---

## 📊 Production Email Services

For high volume or better deliverability, consider:

### SendGrid (Recommended)
```php
'smtp_host' => 'smtp.sendgrid.net',
'smtp_port' => 587,
'smtp_username' => 'apikey',
'smtp_password' => 'your-sendgrid-api-key',
```
- Free: 100 emails/day
- Reliable delivery
- Analytics dashboard

### Amazon SES
```php
'smtp_host' => 'email-smtp.us-east-1.amazonaws.com',
'smtp_port' => 587,
'smtp_username' => 'your-ses-username',
'smtp_password' => 'your-ses-password',
```
- $0.10 per 1,000 emails
- High volume support
- AWS infrastructure

### Mailgun
```php
'smtp_host' => 'smtp.mailgun.org',
'smtp_port' => 587,
'smtp_username' => 'postmaster@your-domain.mailgun.org',
'smtp_password' => 'your-mailgun-password',
```
- Free: 5,000 emails/month
- Good API support
- Email validation features

---

## 📝 Email Templates

Email templates are in `api/send_email.php`:

- `getApprovedEmailTemplate()` - Green themed confirmation
- `getRejectedEmailTemplate()` - Red themed resubmission request

To customize:
1. Edit the HTML in these functions
2. Test with debug mode enabled
3. Check mobile responsiveness

---

## ✅ Quick Checklist

- [ ] Composer installed
- [ ] PHPMailer installed (`composer install`)
- [ ] `email_config.php` configured with SMTP settings
- [ ] Gmail App Password generated (if using Gmail)
- [ ] `smtp_debug` set to 2 for testing
- [ ] Test email sent successfully
- [ ] Check email in inbox (not spam)
- [ ] `smtp_debug` set to 0 for production
- [ ] `email_config.php` in `.gitignore`
- [ ] Server error logs accessible

---

## 🆘 Support

If you encounter issues:

1. **Check error logs** first
2. **Enable debug mode** (smtp_debug = 2)
3. **Test SMTP connection** manually
4. **Verify credentials** are correct
5. **Check firewall/port access**

**Common Commands:**
```bash
# Check if port 587 is open
telnet smtp.gmail.com 587

# Check PHP mail configuration
php -i | grep mail

# View real-time logs
tail -f /var/log/apache2/error.log
```

---

**Last Updated**: December 18, 2025  
**Version**: 2.0.0 (PHPMailer)  
**Documentation**: Full setup guide for email notifications