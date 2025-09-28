<?php
session_start();
include 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Result & Attendance Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div class="school-info">
                    <img src="assets/images/school-logo.jpg" alt="School Logo" class="school-logo">
                    <h1>Govt Boys High School Gujjo</h1>
                    <p class="motto">"Excellence in Education, Character in Life"</p>
                </div>
                
                <div class="login-options">
                    <h2>Login Portal</h2>
                    <div class="login-cards">
                        <div class="login-card">
                            <i class="fas fa-user-shield"></i>
                            <h3>Admin</h3>
                            <p>Manage school settings, students, teachers, and generate reports</p>
                            <a href="admin/login.php" class="btn btn-primary">Admin Login</a>
                        </div>
                        
                        <div class="login-card">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <h3>Teacher</h3>
                            <p>Enter marks, mark attendance, and view class performance</p>
                            <a href="teacher/login.php" class="btn btn-primary">Teacher Login</a>
                        </div>
                        
                        <div class="login-card">
                            <i class="fas fa-graduation-cap"></i>
                            <h3>Student/Parent</h3>
                            <p>View results, attendance, and download report cards</p>
                            <a href="student/login.php" class="btn btn-primary">Student Login</a>
                        </div>
                    </div>
                </div>
                
                <div class="quick-result-check">
                    <h3>Quick Result Check</h3>
                    <form action="result-check.php" method="POST" class="result-form">
                        <input type="text" name="roll_no" placeholder="Enter Roll Number" required>
                        <button type="submit" class="btn btn-secondary">Check Result</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; 2024 School Result & Attendance Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
