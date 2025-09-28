<?php
session_start();
include '../config/database.php';
check_login('admin');

// Get statistics
$stats = [];

// Total students
$result = $conn->query("SELECT COUNT(*) as count FROM students");
$stats['students'] = $result->fetch_assoc()['count'];

// Total teachers
$result = $conn->query("SELECT COUNT(*) as count FROM teachers");
$stats['teachers'] = $result->fetch_assoc()['count'];

// Total classes
$result = $conn->query("SELECT COUNT(*) as count FROM classes");
$stats['classes'] = $result->fetch_assoc()['count'];

// Today's attendance
$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND status = 'Present'");
$stats['present_today'] = $result->fetch_assoc()['count'];

// Get recent activities
$recent_results = $conn->query("
    SELECT s.name, s.roll_no, sub.subject_name, r.marks_obtained, r.total_marks, r.created_at
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN subjects sub ON r.subject_id = sub.id
    ORDER BY r.created_at DESC
    LIMIT 5
");

$school_settings = get_school_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, <?php echo $_SESSION['username']; ?>!</p>
                </div>
                <div>
                    <span><?php echo date('F j, Y'); ?></span>
                    <a href="logout.php" class="btn btn-secondary" style="margin-left: 15px;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <nav class="dashboard-nav">
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="school-settings.php"><i class="fas fa-cog"></i> School Settings</a></li>
                <li><a href="classes.php"><i class="fas fa-chalkboard"></i> Classes</a></li>
                <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
                <li><a href="students.php"><i class="fas fa-graduation-cap"></i> Students</a></li>
                <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
                <li><a href="exams.php"><i class="fas fa-clipboard-list"></i> Exams</a></li>
                <li><a href="results.php"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="reports.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
            </ul>
        </nav>
        
        <div class="stats-cards">
            <div class="stat-card">
                <i class="fas fa-graduation-cap" style="font-size: 2rem; color: #667eea; margin-bottom: 10px;"></i>
                <h3><?php echo $stats['students']; ?></h3>
                <p>Total Students</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-chalkboard-teacher" style="font-size: 2rem; color: #667eea; margin-bottom: 10px;"></i>
                <h3><?php echo $stats['teachers']; ?></h3>
                <p>Total Teachers</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-chalkboard" style="font-size: 2rem; color: #667eea; margin-bottom: 10px;"></i>
                <h3><?php echo $stats['classes']; ?></h3>
                <p>Total Classes</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-calendar-check" style="font-size: 2rem; color: #667eea; margin-bottom: 10px;"></i>
                <h3><?php echo $stats['present_today']; ?></h3>
                <p>Present Today</p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <div class="table-container">
                <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
                    <h3><i class="fas fa-clock"></i> Recent Results</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Roll No</th>
                            <th>Subject</th>
                            <th>Marks</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_results->num_rows > 0): ?>
                            <?php while ($row = $recent_results->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['roll_no']; ?></td>
                                    <td><?php echo $row['subject_name']; ?></td>
                                    <td><?php echo $row['marks_obtained'] . '/' . $row['total_marks']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No recent results found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="form-container">
                <h3><i class="fas fa-info-circle"></i> School Information</h3>
                <div style="text-align: center; margin-bottom: 20px;">
                    <?php if ($school_settings && $school_settings['logo']): ?>
                        <img src="../uploads/school/<?php echo $school_settings['logo']; ?>" alt="School Logo" style="width: 80px; height: 80px; border-radius: 50%;">
                    <?php else: ?>
                        <div style="width: 80px; height: 80px; background: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <i class="fas fa-school" style="font-size: 2rem; color: #667eea;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($school_settings): ?>
                    <p><strong>Name:</strong> <?php echo $school_settings['school_name']; ?></p>
                    <p><strong>Motto:</strong> <?php echo $school_settings['motto']; ?></p>
                    <p><strong>Academic Year:</strong> <?php echo $school_settings['academic_year']; ?></p>
                    <p><strong>Phone:</strong> <?php echo $school_settings['phone']; ?></p>
                    <p><strong>Email:</strong> <?php echo $school_settings['email']; ?></p>
                <?php else: ?>
                    <p>No school settings configured.</p>
                <?php endif; ?>
                
                <a href="school-settings.php" class="btn btn-primary" style="margin-top: 15px; width: 100%;">
                    <i class="fas fa-edit"></i> Edit Settings
                </a>
            </div>
        </div>
        
        <div class="form-container" style="margin-top: 30px;">
            <h3><i class="fas fa-tasks"></i> Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                <a href="students.php?action=add" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add Student
                </a>
                <a href="teachers.php?action=add" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add Teacher
                </a>
                <a href="exams.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Exam
                </a>
                <a href="reports.php" class="btn btn-secondary">
                    <i class="fas fa-file-pdf"></i> Generate Reports
                </a>
            </div>
        </div>
    </div>
</body>
</html>
