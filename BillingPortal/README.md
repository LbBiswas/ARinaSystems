# Billing Portal - Complete System Documentation

## 📋 System Overview

This is a comprehensive billing portal system built with PHP, MySQL, HTML, CSS, and JavaScript. The system provides role-based access with separate interfaces for administrators and customers, featuring document management with email notifications.

## 🔧 Technology Stack

- **Backend**: PHP 7.4+ with PDO MySQL
- **Database**: MySQL (hosted on Hostinger)
- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **Authentication**: Session-based with bcrypt password hashing
- **Email**: PHP mail() function with custom EmailHelper class
- **Security**: Role-based access control, file validation, activity logging

## 📁 File Structure

```
d:\NewBilling\
├── admin.html                     # Admin dashboard interface
├── customer.html                  # Customer dashboard interface
├── login.html                     # Login page
├── document_management.php        # Admin document management interface
├── test_email.php                 # Email system testing page
├── css/
│   └── styles.css                 # Main stylesheet
├── js/
│   ├── admin.js                   # Admin dashboard functionality
│   ├── customer.js                # Customer dashboard functionality
│   └── login.js                   # Login page functionality
├── api/
│   ├── login.php                  # Authentication API
│   ├── logout.php                 # Logout functionality
│   ├── users.php                  # User management API
│   ├── upload.php                 # Customer file upload API
│   ├── upload_documents.php       # Admin document assignment API
│   ├── documents.php              # Document retrieval API
│   └── download.php               # File download handler
├── config/
│   └── database.php               # Database configuration
├── includes/
│   └── email_helper.php           # Email notification system
└── uploads/
    └── documents/                 # Document storage directory
```

## 🗄️ Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    user_type ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Documents Table
```sql
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    description TEXT,
    original_name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    category VARCHAR(100) DEFAULT 'general',
    uploaded_by INT NOT NULL,
    assigned_to INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);
```

### Activity Logs Table
```sql
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## 🚀 Key Features

### 1. Authentication System
- **Login**: Username/password authentication with session management
- **Role-based Access**: Separate interfaces for admins and customers
- **Security**: Bcrypt password hashing, session validation
- **Password Reset**: (Ready for implementation)

### 2. Admin Features
- **Dashboard**: User statistics, document overview, recent activity
- **User Management**: Create, edit, view customer accounts
- **Document Management**: Upload documents and assign to specific customers
- **Customer Selection**: Dropdown showing only customers (not admins)
- **Email Notifications**: Automatic notifications when documents are assigned
- **File Categories**: Organize documents by category
- **Activity Monitoring**: Track system usage and document uploads

### 3. Customer Features
- **Dashboard**: Personal document overview, account information
- **Document Access**: View and download assigned documents
- **File Upload**: Upload personal documents with categories
- **Email Notifications**: Receive notifications for new document assignments
- **Profile Management**: (Ready for implementation)

### 4. Document Management
- **File Types**: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF
- **File Size Limits**: 10MB for customers, 50MB for admins
- **Storage**: Secure file storage with unique naming
- **Categories**: General, Invoice, Receipt, Contract, Report, Other
- **Assignment**: Admin can assign documents to specific customers
- **Access Control**: Customers only see their assigned documents

### 5. Email Notification System
- **Document Upload**: Notifications when documents are assigned
- **Welcome Emails**: (Ready for implementation)
- **Password Reset**: (Ready for implementation)
- **Customizable Templates**: Easy to modify email content
- **Error Handling**: Graceful failure handling for email issues

## ⚙️ Configuration

### Database Configuration
File: `config/database.php`
```php
class Database {
    private $host = "localhost";
    private $db_name = "your_database_name";
    private $username = "your_username";
    private $password = "your_password";
    // ...
}
```

### Email Configuration
File: `includes/email_helper.php`
```php
class EmailHelper {
    private $fromEmail = 'noreply@yourdomain.com';
    private $fromName = 'Billing Portal';
    // ...
}
```

## 🛠️ Installation Instructions

### 1. Hostinger Setup
1. Upload all files to your domain's `public_html` directory
2. Create a MySQL database in Hostinger cPanel
3. Note down database credentials (host, database name, username, password)

### 2. Database Configuration
1. Edit `config/database.php` with your Hostinger database credentials
2. Import the SQL schema to create required tables
3. Create an admin user account

### 3. File Permissions
1. Set `uploads/documents/` directory to 755 permissions
2. Ensure PHP has write access to the uploads directory

### 4. Email Configuration
1. Update `includes/email_helper.php` with your domain email
2. Test email functionality using `test_email.php`

### 5. Initial Admin Account
Create an admin user directly in the database:
```sql
INSERT INTO users (username, email, password, first_name, last_name, user_type) 
VALUES ('admin', 'your-email@domain.com', '$2y$10$hash_password_here', 'Admin', 'User', 'admin');
```

## 🧪 Testing

### Email System Test
1. Access `test_email.php` (admin login required)
2. Enter a test email address
3. Check email delivery and content formatting

### Document Upload Test
1. Login as admin
2. Go to Document Management
3. Select a customer and upload a test document
4. Verify customer receives email notification
5. Login as customer to verify document access

### Customer Upload Test
1. Login as customer
2. Upload a document through customer dashboard
3. Verify self-notification email is received

## 🔐 Security Features

### File Upload Security
- File type validation (whitelist approach)
- File size limits
- Unique filename generation
- Secure file storage outside web root (recommended)

### Access Control
- Session-based authentication
- Role-based page access
- User-specific document filtering
- CSRF protection (recommended for forms)

### Data Validation
- Input sanitization
- SQL injection prevention (PDO prepared statements)
- Email validation
- File extension validation

## 📧 Email Templates

### Document Upload Notification
- **Subject**: "New Document(s) Assigned to You - Billing Portal"
- **Content**: Document details, upload date, uploader information
- **Call-to-action**: Link to customer portal

### Welcome Email (Template Ready)
- **Subject**: "Welcome to Billing Portal"
- **Content**: Account details, login instructions
- **Call-to-action**: First login link

### Password Reset (Template Ready)
- **Subject**: "Password Reset Request"
- **Content**: Reset instructions and secure link
- **Security**: Time-limited reset tokens

## 🚀 Future Enhancements

### Immediate Improvements
1. **HTTPS Configuration**: Secure all data transmission
2. **Email SMTP**: Configure proper SMTP for better deliverability
3. **File Encryption**: Encrypt sensitive documents at rest
4. **Audit Trail**: Enhanced activity logging

### Advanced Features
1. **Document Versioning**: Track document revisions
2. **Bulk Operations**: Bulk user creation, document assignments
3. **API Integration**: RESTful API for mobile apps
4. **Advanced Search**: Full-text document search
5. **Document Expiry**: Automatic document archival
6. **Two-Factor Authentication**: Enhanced security
7. **Dashboard Analytics**: Usage statistics and reporting

### UI/UX Improvements
1. **Responsive Design**: Mobile-optimized interface
2. **Dark Mode**: Theme switching capability
3. **Drag & Drop**: Enhanced file upload experience
4. **Progressive Web App**: Offline capability
5. **Real-time Notifications**: WebSocket implementation

## 🆘 Troubleshooting

### Common Issues

#### Email Not Sending
1. Check Hostinger mail() function is enabled
2. Verify from email domain matches hosting domain
3. Check spam folders for test emails
4. Consider SMTP configuration for production

#### File Upload Failures
1. Check directory permissions (755 for uploads/)
2. Verify PHP upload_max_filesize setting
3. Ensure sufficient disk space
4. Check file type restrictions

#### Database Connection Issues
1. Verify database credentials in config/database.php
2. Check database server status in Hostinger
3. Ensure database user has proper permissions
4. Test connection using MySQL client

#### Login Problems
1. Check session configuration
2. Verify password hashing consistency
3. Clear browser cache and cookies
4. Check database user table structure

### Debug Mode
Enable error reporting in development:
```php
// Add to top of PHP files for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Log Files
Check Hostinger error logs:
- cPanel → Error Logs
- Look for PHP errors and warnings
- Monitor file upload errors

## 📞 Support & Maintenance

### Regular Maintenance
1. **Database Backup**: Weekly automated backups
2. **File Cleanup**: Remove orphaned files
3. **Log Rotation**: Archive old activity logs
4. **Security Updates**: Keep PHP and dependencies updated

### Monitoring
1. **Disk Usage**: Monitor uploads directory size
2. **Database Size**: Track database growth
3. **Email Delivery**: Monitor bounce rates
4. **Error Rates**: Track PHP errors and failures

### Contact Information
- **Development**: System developed for Hostinger deployment
- **Email Testing**: Use test_email.php for diagnostics
- **Database**: Check Hostinger cPanel for database management

---

## 📝 Version History

**v1.0.0** - Complete System
- ✅ User authentication and role management
- ✅ Document upload and assignment system
- ✅ Email notification system
- ✅ Admin and customer interfaces
- ✅ Security and validation features
- ✅ Customer-only assignment dropdown
- ✅ Email integration for document assignments
- ✅ Comprehensive testing tools

**Deployment Status**: Ready for Hostinger production deployment

---

*This documentation covers the complete billing portal system. For specific implementation questions or customizations, refer to the individual file comments and API documentation within the codebase.*