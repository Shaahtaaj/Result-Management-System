# Quick Setup Instructions

## Step 1: Database Setup
1. Start XAMPP and ensure Apache and MySQL are running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database named `school_management`
4. Import the following files in order:
   - `database/schema.sql` (creates tables and structure)
   - `database/sample_data.sql` (adds demo data)

## Step 2: Create Upload Directories
Create these directories in the project root:
```
uploads/
├── school/
└── students/
```

## Step 3: Set Permissions
Ensure the upload directories have write permissions (755 or 777).

## Step 4: Access the System
Open your browser and navigate to: `http://localhost/ResultManagementSystem`

## Demo Login Credentials

### Admin Panel
- URL: `http://localhost/ResultManagementSystem/admin/login.php`
- Username: `admin`
- Password: `admin123`

### Teacher Panel  
- URL: `http://localhost/ResultManagementSystem/teacher/login.php`
- Username: `teacher1`
- Password: `teacher123`

### Student Portal
- URL: `http://localhost/ResultManagementSystem/student/login.php`
- Roll Number: `STU001`
- Password: `STU001`

### Quick Result Check
- URL: `http://localhost/ResultManagementSystem/result-check.php`
- Try Roll Numbers: `STU001`, `STU002`, `STU003`

## Features to Test

### Admin Features
1. **School Settings**: Upload logo, set school information
2. **Student Management**: Add students with photos
3. **Teacher Management**: Create teacher accounts and assign classes
4. **Classes & Subjects**: Manage academic structure
5. **Exams**: Create examinations
6. **Results**: Enter and manage student results
7. **Reports**: Generate result cards with QR codes

### Teacher Features
1. **Dashboard**: View assigned classes and subjects
2. **Attendance**: Mark daily attendance
3. **Results**: Enter marks for assigned subjects
4. **Students**: View class information

### Student Features
1. **Dashboard**: View results and attendance
2. **Result Cards**: Download PDF result cards
3. **Profile**: View student information
4. **Verification**: QR code result verification

## System Requirements
- PHP 7.4+
- MySQL 5.7+
- Apache Web Server
- Modern web browser
- Internet connection (for QR code generation)

## Troubleshooting

### Common Issues
1. **Database Connection Error**: Check MySQL service and credentials
2. **File Upload Issues**: Verify upload directory permissions
3. **QR Code Not Loading**: Check internet connection
4. **PDF Generation Issues**: Verify image paths and permissions

### Default Passwords
- All demo accounts use simple passwords for testing
- In production, implement proper password hashing
- Change default credentials before deployment

## Next Steps
1. Customize school information in Admin → School Settings
2. Add real student and teacher data
3. Upload school logo and principal signature
4. Configure grade scales if needed
5. Test all features with real data

## Security Notes
- Change all default passwords
- Implement proper password hashing
- Set appropriate file permissions
- Use HTTPS in production
- Regular database backups

For detailed documentation, see README.md
