# School Result & Attendance Management System

A comprehensive web-based application built using HTML, CSS, JavaScript, PHP, and MySQL for managing school results, attendance, and student data online.

## Features

### Admin Panel
- **School Settings Management**: Configure school name, logo, motto, address, and principal signature
- **Classes & Subjects Management**: Create and manage classes, sections, and subjects
- **Student Management**: Add/edit students with photo upload and parent information
- **Teacher Management**: Manage teacher accounts and assign classes/subjects
- **Exam Management**: Create and schedule different types of exams
- **Results Management**: View and manage student results
- **Attendance Reports**: Generate attendance reports and statistics
- **PDF Report Generation**: Generate official result cards with QR codes

### Teacher Panel
- **Mark Attendance**: Daily attendance marking for assigned classes
- **Enter Results**: Input student marks for assigned subjects and exams
- **View Students**: Access student information for assigned classes
- **Performance Reports**: View class performance statistics

### Student/Parent Portal
- **View Results**: Access detailed examination results and grades
- **Download Result Cards**: Generate and download official PDF result cards
- **Attendance History**: View attendance records and statistics
- **Profile Management**: View and update student profile information
- **QR Code Verification**: Verify result authenticity using QR codes

### Additional Features
- **Quick Result Check**: Public result checking without login
- **QR Code Integration**: Each result card includes a QR code for online verification
- **Role-based Authentication**: Secure login system with different access levels
- **Responsive Design**: Mobile-friendly interface
- **Grade Calculation**: Automatic grade calculation based on percentage
- **Attendance Tracking**: Comprehensive attendance management system

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, Font Awesome Icons
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache (XAMPP recommended)
- **PDF Generation**: Custom HTML-to-PDF solution
- **QR Code**: Google Charts API integration

## Installation Instructions

### Prerequisites
- XAMPP (or similar LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Safari, Edge)

### Step 1: Setup XAMPP
1. Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Start Apache and MySQL services from XAMPP Control Panel

### Step 2: Database Setup
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `school_management`
3. Import the database schema:
   - Navigate to the `database` folder
   - Import `schema.sql` file
   - Import `sample_data.sql` file for demo data

### Step 3: File Setup
1. Copy the entire project folder to `C:\xampp\htdocs\ResultManagementSystem`
2. Create upload directories:
   ```
   mkdir uploads/school
   mkdir uploads/students
   ```
3. Set proper permissions (755) for upload directories

### Step 4: Configuration
1. Open `config/database.php`
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'school_management');
   ```

### Step 5: Access the Application
1. Open web browser
2. Navigate to `http://localhost/ResultManagementSystem`
3. Use the following demo credentials:

## Demo Login Credentials

### Admin Access
- **URL**: `http://localhost/ResultManagementSystem/admin/login.php`
- **Username**: `admin`
- **Password**: `admin123`

### Teacher Access
- **URL**: `http://localhost/ResultManagementSystem/teacher/login.php`
- **Username**: `teacher1`
- **Password**: `teacher123`

### Student Access
- **URL**: `http://localhost/ResultManagementSystem/student/login.php`
- **Roll Number**: `STU001`
- **Password**: `STU001`

### Quick Result Check
- **URL**: `http://localhost/ResultManagementSystem/result-check.php`
- **Roll Number**: `STU001` (or STU002, STU003)

## File Structure

```
ResultManagementSystem/
├── admin/                  # Admin panel files
│   ├── login.php
│   ├── dashboard.php
│   ├── school-settings.php
│   ├── classes.php
│   ├── students.php
│   ├── teachers.php
│   └── ...
├── teacher/               # Teacher panel files
│   ├── login.php
│   ├── dashboard.php
│   └── ...
├── student/               # Student portal files
│   ├── login.php
│   ├── dashboard.php
│   ├── result-card.php
│   └── ...
├── assets/                # Static assets
│   └── css/
│       └── style.css
├── config/                # Configuration files
│   └── database.php
├── database/              # Database files
│   ├── schema.sql
│   └── sample_data.sql
├── libs/                  # Custom libraries
│   ├── SimplePDF.php
│   └── SimpleQR.php
├── uploads/               # Upload directories
│   ├── school/
│   └── students/
├── index.php              # Home page
├── result-check.php       # Public result checking
├── verify.php             # QR code verification
└── README.md
```

## Key Features Explained

### PDF Result Card Generation
- Automatically generates professional result cards
- Includes school logo, student photo, and principal signature
- QR code for online verification
- Downloadable in HTML format (can be printed as PDF)

### QR Code Verification
- Each result card contains a unique QR code
- Scanning redirects to online verification page
- Displays authentic result information
- Prevents result card forgery

### Attendance System
- Daily attendance marking by teachers
- Multiple status options (Present, Absent, Late, Excused)
- Monthly and yearly attendance reports
- Attendance percentage calculation

### Grade Calculation
- Automatic grade assignment based on percentage
- Configurable grade scale (A+, A, B+, B, C+, C, D, F)
- Subject-wise and overall grade calculation

### Role-based Access Control
- Admin: Full system access and management
- Teacher: Class and subject-specific access
- Student: Personal data and results access only

## Customization

### Adding New Grade Scale
Edit the `grades` table in the database or modify the grade calculation logic in `libs/SimplePDF.php`.

### Changing School Information
Use the Admin Panel → School Settings to update school information, logo, and principal signature.

### Adding New Subjects
Use the Admin Panel → Subjects to add subjects for different classes.

### Customizing Result Card Design
Modify the HTML template in `libs/SimplePDF.php` to change the result card layout and styling.

## Security Features

- SQL injection prevention using prepared statements
- XSS protection with input sanitization
- Session-based authentication
- Role-based access control
- Password hashing (ready for implementation)

## Browser Compatibility

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Internet Explorer 11+ (limited support)

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check XAMPP MySQL service is running
   - Verify database credentials in `config/database.php`
   - Ensure database `school_management` exists

2. **File Upload Issues**
   - Check upload directory permissions
   - Ensure `uploads/school` and `uploads/students` directories exist
   - Verify PHP upload settings in `php.ini`

3. **QR Code Not Displaying**
   - Check internet connection (uses Google Charts API)
   - Verify QR code generation in `libs/SimpleQR.php`

4. **PDF Generation Issues**
   - Check HTML output in `libs/SimplePDF.php`
   - Verify image paths for logos and photos

### Support

For technical support or feature requests, please check the code comments and documentation within the PHP files.

## License

This project is developed for educational purposes. Feel free to modify and use according to your needs.

## Version History

- **v1.0** - Initial release with core functionality
- Complete admin, teacher, and student panels
- PDF result card generation
- QR code verification system
- Attendance management
- Responsive design

---

**Note**: This system is designed for educational institutions and can be customized according to specific requirements. The demo data includes sample students, teachers, and results for testing purposes.
