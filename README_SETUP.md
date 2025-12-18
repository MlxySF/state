# Wushu Registration System - Setup Guide

## Overview
This is a complete registration management system for Wushu Sport Academy with admin panel, monthly analytics, and smart reporting features.

## System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser (Chrome, Firefox, Safari, Edge)

## Installation Steps

### 1. Database Setup

**Step 1:** Create the database (if not exists)
```sql
CREATE DATABASE mlxysf_state CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Step 2:** Run the database setup script
```bash
mysql -u your_username -p mlxysf_state < database_setup.sql
```

Or manually import via phpMyAdmin:
1. Open phpMyAdmin
2. Select `mlxysf_state` database
3. Click "Import" tab
4. Upload `database_setup.sql` file
5. Click "Go"

### 2. Configure Database Connection

Edit `config.php` and update your database credentials:

```php
$servername = "localhost";
$username = "your_database_username";  // CHANGE THIS
$password = "your_database_password";  // CHANGE THIS
$dbname = "mlxysf_state";
```

**IMPORTANT SECURITY NOTE:**
- Never commit `config.php` with real credentials to public repositories
- Use environment variables for production deployments
- Create a `.gitignore` file and add `config.php` to it

### 3. Set Directory Permissions

Ensure the web server can write to necessary directories:
```bash
chmod 755 api/
chmod 755 register-parts/
```

### 4. Admin Panel Access

**Default Admin Password:** `RandyloveFanny`

**To change the admin password:**
1. Open `admin.html`
2. Find line: `const ADMIN_PASSWORD = "RandyloveFanny";`
3. Change the password
4. Save the file

**Access admin panel:**
- Navigate to: `https://yourdomain.com/admin.html`
- Enter the admin password

### 5. File Structure

```
state/
├── api/
│   ├── process_registration.php  # Form submission handler
│   ├── list_registrations.php    # Fetch all registrations
│   ├── get_registration.php      # Fetch single registration
│   ├── delete_registration.php   # Delete registration
│   └── get_monthly_stats.php     # Monthly analytics
├── register-parts/
│   ├── body.php                  # Registration form HTML
│   ├── head.php                  # Form header
│   ├── footer.php                # Form footer
│   ├── register.css              # Form styles
│   └── register.js               # Form JavaScript
├── admin.html                    # Admin panel (password protected)
├── index.php                     # Public registration form
├── config.php                    # Database configuration
├── database_setup.sql            # Database schema
└── README_SETUP.md              # This file
```

## Features

### Registration Form
- Multi-step registration process
- Real-time IC-based age calculation
- Event and schedule selection
- Digital signature capture
- PDF generation with signature
- Payment receipt upload
- Mobile responsive design

### Admin Panel
- Password-protected access
- View all registrations in table/card format
- Search and filter by name, IC, school, status, level
- Export registrations to Excel
- Download signed PDF agreements
- Delete registrations
- Mobile responsive design

### Smart Analytics
- Automatic monthly statistics tracking
- Registration counts by status (Student/State Team/Backup)
- Revenue tracking per month
- Database triggers auto-update stats

## API Endpoints

### Public Endpoints
- `POST /api/process_registration.php` - Submit new registration

### Admin Endpoints (Protected)
- `GET /api/list_registrations.php` - Get all registrations
- `GET /api/get_registration.php?id={id}` - Get single registration
- `POST /api/delete_registration.php` - Delete registration
- `GET /api/get_monthly_stats.php?year={year}` - Get monthly statistics

## Database Schema

### `registrations` Table
Stores all student registration data including:
- Student information (name, IC, age, school, status)
- Contact details (phone, email)
- Training details (events, schedule, level)
- Parent/guardian information
- Payment information
- Base64-encoded documents (signature, PDF, receipt)

### `monthly_stats` Table
Automatically tracked monthly statistics:
- Total registrations per month
- Total revenue per month
- Student count by status (State Team/Backup/Regular)
- Year-over-year comparison data

## Security Considerations

1. **Admin Password Protection**
   - Change default password immediately
   - Use strong, unique password
   - Consider implementing IP whitelist

2. **Database Security**
   - Never commit real credentials to version control
   - Use strong database passwords
   - Restrict database user permissions

3. **File Permissions**
   - Ensure proper file permissions (755 for directories, 644 for files)
   - Restrict write access to necessary directories only

4. **Input Validation**
   - All form inputs are validated server-side
   - SQL injection protected via prepared statements
   - XSS protection via proper escaping

## Troubleshooting

### Database Connection Errors
- Verify credentials in `config.php`
- Ensure MySQL service is running
- Check if database exists
- Verify user has proper permissions

### Admin Panel Not Loading Data
- Open browser console (F12) to check for errors
- Verify API endpoints exist in `/api/` directory
- Check file permissions
- Verify database tables are created

### Registration Form Not Submitting
- Check browser console for JavaScript errors
- Verify `api/process_registration.php` is accessible
- Check database connection
- Ensure all required fields are filled

### PDF Generation Fails
- Ensure jsPDF and html2canvas libraries are loaded
- Check browser console for errors
- Verify signature image is captured
- Clear browser cache and try again

## Monthly Analytics Usage

The system automatically tracks monthly statistics using database triggers. No manual intervention required.

**Access analytics:**
```javascript
// Example: Fetch 2026 statistics
fetch('api/get_monthly_stats.php?year=2026')
  .then(response => response.json())
  .then(data => console.log(data));
```

**Response includes:**
- Monthly breakdown (registrations, revenue per month)
- Year-to-date totals
- Comparison with previous year
- Growth percentages

## Backup Recommendations

### Database Backup
```bash
# Full database backup
mysqldump -u username -p mlxysf_state > backup_$(date +%Y%m%d).sql

# Backup with compression
mysqldump -u username -p mlxysf_state | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Automated Backup (Cron Job)
```bash
# Add to crontab (runs daily at 2 AM)
0 2 * * * mysqldump -u username -p'password' mlxysf_state | gzip > /path/to/backups/backup_$(date +\%Y\%m\%d).sql.gz
```

## Future Enhancements

- Email notifications for new registrations
- SMS integration for payment reminders
- Advanced analytics dashboard with charts
- Payment gateway integration
- Multi-admin support with role-based access
- Attendance tracking module
- Student performance reports

## Support

For issues or questions:
1. Check the Troubleshooting section above
2. Review browser console for errors
3. Check server error logs
4. Verify database connectivity

## License

Proprietary - Wushu Sport Academy © 2026

## Version History

### v2.0.0 (December 2025)
- Complete database migration from JSON to MySQL
- Added monthly statistics tracking
- Implemented smart analytics with triggers
- Added Excel export functionality
- Improved mobile responsive design
- Enhanced security with admin authentication
- Removed student portal (registration-only system)

### v1.0.0 (Previous)
- Initial JSON-based registration system
- Basic admin viewing
- PDF generation

---

**Last Updated:** December 18, 2025