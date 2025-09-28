<?php
session_start();
include '../config/database.php';
check_login('teacher');

// Get teacher information
$teacher_query = "SELECT * FROM teachers WHERE user_id = ?";
$teacher_stmt = $conn->prepare($teacher_query);
$teacher_stmt->bind_param("i", $_SESSION['user_id']);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
$teacher_data = $teacher_result->fetch_assoc();

// Get assigned classes
$classes_query = "
    SELECT DISTINCT c.id, c.class_name, c.section, COUNT(s.id) as student_count
    FROM teacher_classes tc
    JOIN classes c ON tc.class_id = c.id
    LEFT JOIN students s ON c.id = s.class_id
    WHERE tc.teacher_id = ?
    GROUP BY c.id
";
$classes_stmt = $conn->prepare($classes_query);
$classes_stmt->bind_param("i", $teacher_data['id']);
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();

// Get assigned subjects
$subjects_query = "
    SELECT DISTINCT sub.id, sub.subject_name, c.class_name, c.section
    FROM teacher_classes tc
    JOIN subjects sub ON tc.subject_id = sub.id
    JOIN classes c ON tc.class_id = c.id
    WHERE tc.teacher_id = ?
";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->bind_param("i", $teacher_data['id']);
$subjects_stmt->execute();
$subjects_result = $subjects_stmt->get_result();

// Get recent results entered by this teacher
$recent_results_query = "
    SELECT s.name, s.roll_no, sub.subject_name, r.marks_obtained, r.total_marks, r.created_at
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN subjects sub ON r.subject_id = sub.id
    JOIN teacher_classes tc ON tc.subject_id = sub.id AND tc.class_id = s.class_id
    WHERE tc.teacher_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
";
$recent_stmt = $conn->prepare($recent_results_query);
$recent_stmt->bind_param("i", $teacher_data['id']);
$recent_stmt->execute();
$recent_results = $recent_stmt->get_result();

// Get today's attendance stats
$today = date('Y-m-d');
$attendance_query = "
    SELECT 
        COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present,
        COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent,
        COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN teacher_classes tc ON tc.class_id = s.class_id
    WHERE tc.teacher_id = ? AND a.date = ?
";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("is", $teacher_data['id'], $today);
$attendance_stmt->execute();
$attendance_stats = $attendance_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - School Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Teacher Dashboard</h1>
                    <p>Welcome back, <?php echo $teacher_data['name']; ?>!</p>
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
                <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Mark Attendance</a></li>
                <li><a href="results.php"><i class="fas fa-chart-bar"></i> Enter Results</a></li>
                <li><a href="students.php"><i class="fas fa-graduation-cap"></i> My Students</a></li>
                <li><a href="reports.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
            </ul>
        </nav>
        
        <div class="stats-cards">
            <div class="stat-card">
                <i class="fas fa-chalkboard" style="font-size: 2rem; color: #667eea; margin-bottom: 10px;"></i>
                <h3><?php echo $classes_result->num_rows; ?></h3>
                <p>Assigned Classes</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-book" style="font-size: 2rem; color: #667eea; margin-bottom: 10px;"></i>
                <h3><?php echo $subjects_result->num_rows; ?></h3>
                <p>Teaching Subjects</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-user-check" style="font-size: 2rem; color: #28a745; margin-bottom: 10px;"></i>
                <h3><?php echo $attendance_stats['present'] ?? 0; ?></h3>
                <p>Present Today</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-user-times" style="font-size: 2rem; color: #dc3545; margin-bottom: 10px;"></i>
                <h3><?php echo $attendance_stats['absent'] ?? 0; ?></h3>
                <p>Absent Today</p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- Assigned Classes -->
            <div class="form-container">
                <h3><i class="fas fa-chalkboard"></i> My Classes</h3>
                
                <?php if ($classes_result->num_rows > 0): ?>
                    <?php $classes_result->data_seek(0); ?>
                    <?php while ($class = $classes_result->fetch_assoc()): ?>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #667eea;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="margin: 0; color: #2c3e50;"><?php echo $class['class_name'] . ' - ' . $class['section']; ?></h4>
                                    <p style="margin: 5px 0 0 0; color: #7f8c8d;"><?php echo $class['student_count']; ?> Students</p>
                                </div>
                                <div>
                                    <a href="attendance.php?class_id=<?php echo $class['id']; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 0.9rem;">
                                        <i class="fas fa-calendar-check"></i> Attendance
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No classes assigned yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Teaching Subjects -->
            <div class="form-container">
                <h3><i class="fas fa-book"></i> Teaching Subjects</h3>
                
                <?php if ($subjects_result->num_rows > 0): ?>
                    <?php $subjects_result->data_seek(0); ?>
                    <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #28a745;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="margin: 0; color: #2c3e50;"><?php echo $subject['subject_name']; ?></h4>
                                    <p style="margin: 5px 0 0 0; color: #7f8c8d;"><?php echo $subject['class_name'] . ' - ' . $subject['section']; ?></p>
                                </div>
                                <div>
                                    <a href="results.php?subject_id=<?php echo $subject['id']; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 0.9rem;">
                                        <i class="fas fa-plus"></i> Add Marks
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No subjects assigned yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Results -->
        <div class="table-container" style="margin-top: 30px;">
            <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
                <h3><i class="fas fa-clock"></i> Recent Results Entered</h3>
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
        
        <!-- Quick Actions -->
        <div class="form-container" style="margin-top: 30px;">
            <h3><i class="fas fa-tasks"></i> Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                <a href="attendance.php" class="btn btn-primary">
                    <i class="fas fa-calendar-check"></i> Mark Attendance
                </a>
                <a href="results.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Enter Results
                </a>
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-graduation-cap"></i> View Students
                </a>
                <a href="reports.php" class="btn btn-secondary">
                    <i class="fas fa-file-pdf"></i> Generate Reports
                </a>
            </div>
        </div>
    </div>
</body>
</html>
