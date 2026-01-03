# HRMS - Employee Management System

A complete, secure, and scalable Employee Management System built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

### Authentication Module
- ✅ Secure user registration with email verification
- ✅ Password strength validation (8+ chars, uppercase, lowercase, number, special char)
- ✅ Email verification system
- ✅ Session-based authentication
- ✅ Role-based access control (Employee, HR, Admin)
- ✅ Password hashing with `password_hash()`

### Employee Dashboard
- ✅ Quick access cards for all modules
- ✅ Recent attendance summary
- ✅ Leave request status
- ✅ Personal profile view

### Admin/HR Dashboard
- ✅ Total employees overview
- ✅ Attendance statistics
- ✅ Pending leave requests
- ✅ Employee management
- ✅ Comprehensive reporting

### Profile Management
- ✅ View and edit personal details
- ✅ Profile picture upload
- ✅ Job information display
- ✅ Document management
- ✅ Admin can edit all employee fields

### Attendance Management
- ✅ Daily check-in/check-out
- ✅ Automatic timestamp recording
- ✅ One check-in per day validation
- ✅ Attendance history (daily/weekly view)
- ✅ Status tracking (Present, Absent, Half-Day, Leave)

### Leave & Time-Off Management
- ✅ Apply for leave (Paid, Sick, Unpaid, Casual)
- ✅ Leave approval workflow
- ✅ Admin comments on leave requests
- ✅ Automatic attendance update on approval
- ✅ Leave request history

### Payroll Management
- ✅ Salary structure management
- ✅ Earnings (Basic, HRA, Transport, Medical, Other)
- ✅ Deductions (PF, Professional Tax, Income Tax, Other)
- ✅ Auto-calculated gross and net salary
- ✅ Read-only view for employees
- ✅ Full edit access for HR/Admin

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: XAMPP / Apache
- **Authentication**: Session-based with CSRF protection
- **Security**: Prepared statements, password hashing, input sanitization

## Installation Instructions

### Prerequisites
- XAMPP (or similar with PHP 7.4+ and MySQL 5.7+)
- Web browser

### Step-by-Step Setup

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Install and start Apache and MySQL services

2. **Setup Project Files**
   ```bash
   # Copy all project files to XAMPP htdocs folder
   C:\xampp\htdocs\hrms\
   ```

3. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create a new database named `hrms_db`
   - Import the SQL file: `database/database.sql`

4. **Configure Database Connection**
   - Open `config/database.php`
   - Update database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'hrms_db');
     ```

5. **Configure Base URL**
   - Open `config/config.php`
   - Update BASE_URL to match your setup:
     ```php
     define('BASE_URL', 'http://localhost/hrms/');
     ```

6. **Set File Permissions**
   - Ensure `uploads/` directory is writable
   - Create subdirectories if they don't exist:
     - `uploads/profile_pictures/`
     - `uploads/documents/`

7. **Access the Application**
   - Open browser and navigate to: http://localhost/hrms/

## Folder Structure

```
hrms/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       └── validation.js      # Client-side validation
├── auth/
│   ├── login.php              # Login page
│   ├── signup.php             # Registration page
│   ├── verify_email.php       # Email verification
│   └── logout.php             # Logout handler
├── config/
│   ├── config.php             # App configuration
│   └── database.php           # Database connection
├── database/
│   └── database.sql           # Database schema
├── admin/
│   ├── dashboard.php          # Admin dashboard
│   ├── employees.php          # Employee list
│   ├── employee_profile.php   # Edit employee
│   ├── attendance.php         # Attendance reports
│   ├── leave_requests.php     # Leave management
│   ├── leave_action.php       # Approve/reject leave
│   ├── payroll.php            # Payroll list
│   └── payroll_edit.php       # Edit payroll
├── employee/
│   ├── dashboard.php          # Employee dashboard
│   ├── profile.php            # View/edit profile
│   ├── attendance.php         # Mark attendance
│   ├── leave.php              # Apply for leave
│   └── payroll.php            # View salary
├── includes/
│   ├── auth_check.php         # Authentication middleware
│   ├── employee_header.php    # Employee navigation
│   ├── admin_header.php       # Admin navigation
│   └── footer.php             # Common footer
├── uploads/                   # File uploads directory
│   ├── profile_pictures/
│   └── documents/
├── index.php                  # Main entry point
└── README.md                  # This file
```

## Default Test Accounts

After setting up, you can create accounts through the signup page.

**Create HR Account:**
1. Go to: http://localhost/hrms/auth/signup.php
2. Use role: HR/Admin
3. Complete email verification (link shown on page for testing)

**Create Employee Account:**
1. Go to: http://localhost/hrms/auth/signup.php
2. Use role: Employee
3. Complete email verification

## Security Features

- ✅ CSRF token protection on all forms
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (input sanitization)
- ✅ Password hashing with bcrypt
- ✅ Session timeout and management
- ✅ Role-based access control
- ✅ Secure file upload validation
- ✅ Activity logging

## Database Schema

### Main Tables
- **users** - User accounts and authentication
- **roles** - User roles (Employee, HR, Admin)
- **employee_profiles** - Employee personal information
- **attendance** - Daily attendance records
- **leave_requests** - Leave applications
- **payroll** - Salary structures
- **documents** - Employee documents
- **sessions** - Active user sessions
- **activity_logs** - System activity tracking

## Key Features Explained

### Email Verification
- Users must verify email before login
- Verification token expires in 24 hours
- Token can only be used once

### Attendance System
- One check-in and one check-out per day
- Automatic timestamp recording
- Cannot check-out without check-in
- Leave approval automatically marks attendance

### Leave Management
- Calculates total days automatically
- Admin can approve/reject with comments
- Approved leaves update attendance table
- Cannot apply for past dates

### Payroll System
- Supports multiple salary revisions
- Auto-calculates gross and net salary
- Tracks effective dates
- Read-only for employees, editable by HR

## Customization

### Adding New Features
1. Create new PHP files in appropriate folders
2. Use `auth_check.php` for authentication
3. Follow existing code patterns
4. Update navigation in header files

### Modifying Styles
- Edit `assets/css/style.css`
- CSS variables available for easy theming
- Mobile-responsive design included

## Troubleshooting

### Database Connection Error
- Verify MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Ensure database `hrms_db` exists

### Page Not Found (404)
- Check BASE_URL in `config/config.php`
- Ensure all files are in correct folders
- Verify Apache is running

### Cannot Upload Files
- Check folder permissions on `uploads/`
- Ensure PHP upload settings in php.ini:
  ```ini
  upload_max_filesize = 5M
  post_max_size = 10M
  ```

### Session Issues
- Clear browser cookies
- Check PHP session configuration
- Ensure `session_start()` is working

## Production Deployment

Before deploying to production:

1. **Security**
   - Change all default passwords
   - Update database credentials
   - Set `display_errors = 0` in PHP
   - Enable HTTPS
   - Configure proper email SMTP settings

2. **Configuration**
   - Update BASE_URL to production domain
   - Set proper timezone
   - Configure email settings in `config/config.php`

3. **Database**
   - Use strong database password
   - Restrict database user privileges
   - Regular backups

4. **Files**
   - Set proper file permissions (755 for directories, 644 for files)
   - Move uploads outside web root if possible
   - Enable .htaccess protection

## Support & Contribution

This is a complete, production-ready HRMS system with all required features implemented.

For issues or questions:
- Review the code documentation
- Check database schema
- Verify configuration settings

## License

This project is provided as-is for educational and commercial use.

---

**Version**: 1.0.0  
**Last Updated**: January 2026  
**Tested On**: XAMPP 8.2.12, PHP 8.2, MySQL 8.0