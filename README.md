# Wushu State Team - Admin Only Registration System

This is a standalone admin portal for managing Wushu student registrations for 2026. Unlike the full student portal, this version has **no student login** - it's purely for admin management.

## 🎯 Key Features

- ✅ **Public Registration Form** - Students can register online
- ✅ **Admin Portal** - View, manage, and export all registrations
- ✅ **Password Protected** - Secure admin access
- ✅ **PDF Generation** - Signed agreement forms
- ✅ **Excel Export** - Export all data to spreadsheet
- ✅ **Mobile Responsive** - Works on all devices
- ✅ **No Student Portal** - Admin-only view of registrations

## 📁 File Structure

```
state/
├── admin.html                    # Admin dashboard (password protected)
├── register.html                 # Public registration form
├── register-parts/
│   ├── head.php                 # HTML head section
│   ├── body.html                # Registration form body
│   ├── footer.php               # Closing tags
│   ├── register.css             # Form styling
│   └── register.js              # Form functionality
├── api/
│   └── process_registration.php # Backend API for submissions
├── list_registrations.php       # Get all registrations
├── get_registration.php         # Get single registration
├── delete_registration.php      # Delete registration
└── data/
    └── registrations.json       # Registration data storage
```

## 🔐 Admin Access

**Admin Portal:** `admin.html`
**Password:** `RandyloveFanny`

The admin can:
- View all registrations in a table
- Filter by status, level, or search
- View/download signed PDF agreements
- Delete registrations
- Export all data to Excel

## 📝 Registration Process

### For Parents/Students:

1. Visit `register.html`
2. Fill out the 6-step registration form:
   - Step 1: Basic student details
   - Step 2: Contact information
   - Step 3: Event selection
   - Step 4: Training schedule
   - Step 5: Agreement & signature
   - Step 6: Payment receipt upload
3. Submit and receive registration number
4. Download signed agreement PDF

### For Admin:

1. Login to `admin.html` with password
2. View all submissions in dashboard
3. Review payment receipts
4. Download student agreements
5. Export data to Excel
6. Manage registrations

## 🎨 Features

### Registration Form
- **Multi-step wizard** - 6 easy steps
- **Auto-age calculation** - From IC number
- **Event selection** - Multiple martial arts events
- **Schedule filtering** - Based on student status
- **Digital signature** - Touch/mouse signing
- **Payment upload** - Receipt image support
- **PDF generation** - 2-page signed agreement

### Admin Dashboard
- **Statistics cards** - Total, State Team, Backup, Students
- **Search & filter** - By name, IC, status, level
- **Mobile responsive** - Table & card views
- **Excel export** - Full data export
- **PDF viewing** - Download student agreements
- **Delete function** - Remove registrations

## 💾 Data Storage

All registrations are saved to `data/registrations.json` in this format:

```json
{
  "registration_number": "WSA2026-1234",
  "timestamp": "2026-01-15 14:30:00",
  "student_info": {
    "name_en": "John Tan",
    "name_cn": "陈明",
    "ic": "051234-12-5678",
    "age": "21",
    "school": "SJK(C) PUAY CHAI 2",
    "status": "State Team 州队"
  },
  "contact": {
    "phone": "012-345 6789",
    "email": "parent@example.com"
  },
  "training": {
    "events": "初级-长拳, 初级-剑",
    "schedule": "Wushu Sport Academy: Sun 10am-12pm",
    "class_count": 1
  },
  "parent": {
    "name": "Tan Ah Kow",
    "ic": "701234-12-5678",
    "signature_base64": "data:image/png;base64,..."
  },
  "payment": {
    "amount": 120,
    "date": "2026-01-15",
    "receipt_base64": "data:image/png;base64,...",
    "status": "pending"
  },
  "documents": {
    "signed_pdf_base64": "..."
  },
  "admin_status": "pending",
  "admin_notes": ""
}
```

## 🚀 Setup Instructions

1. **Upload files** to your web server
2. **Ensure PHP is enabled** (required for backend)
3. **Set permissions** on `/data` folder to writable (755)
4. **Access registration** at `yoursite.com/register.html`
5. **Access admin** at `yoursite.com/admin.html`

## 🔧 Configuration

### Change Admin Password

Edit `admin.html` line 536:
```javascript
const ADMIN_PASSWORD = "YourNewPassword";
```

### Change Bank Details

Edit `register-parts/body.html` in the Step 6 payment section.

### Modify Training Schedules

Edit `register-parts/body.html` in the Step 4 schedule section.

## 📊 Excel Export Columns

- No, Name, English Name, Chinese Name
- IC Number, Age, School, Status, Level
- Events (combined), Event 1-5 (individual)
- Schedule, Phone, Email
- Parent Name, Parent IC
- Form Date, Registered Date

## 🎯 Differences from Student Portal Version

| Feature | Student Portal | Admin Only |
|---------|---------------|------------|
| Student Login | ✅ Yes | ❌ No |
| Student Dashboard | ✅ Yes | ❌ No |
| Attendance Tracking | ✅ Yes | ❌ No |
| Password Generation | ✅ Yes | ❌ No |
| Email Notifications | ✅ Yes | ⚠️ Optional |
| Registration Form | ✅ Yes | ✅ Yes |
| Admin Portal | ✅ Yes | ✅ Yes |
| PDF Generation | ✅ Yes | ✅ Yes |
| Data Export | ✅ Yes | ✅ Yes |

## 📱 Mobile Support

- **Registration form** - Fully responsive
- **Admin table** - Switches to card view on mobile
- **Touch signature** - Works on tablets/phones
- **PDF generation** - Works on all devices

## 🔄 Future Enhancements

Potential additions:
- Email confirmation to parents
- Payment status tracking
- Admin notes/comments
- Registration approval workflow
- SMS notifications
- QR code for check-in

## 📞 Support

For issues or questions:
1. Check `data/registrations.json` exists and is writable
2. Verify PHP is enabled on server
3. Check browser console for JavaScript errors
4. Ensure all files are uploaded correctly

## 📄 License

Wushu Sports Academy © 2026

---

**Note:** This is the admin-only version. Students do not get login credentials - they only submit registrations which admins can view and manage.