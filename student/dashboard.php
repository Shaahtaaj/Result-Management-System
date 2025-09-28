<?php
session_start();
include '../config/database.php';
check_login('student');

// Get student information
$student_query = "
    SELECT s.*, c.class_name, c.section 
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    WHERE s.user_id = ?
";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("i", $_SESSION['user_id']);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student_data = $student_result->fetch_assoc();

// Get recent results
$results_query = "
    SELECT r.*, s.subject_name, e.exam_name, e.exam_type
    FROM results r
    JOIN subjects s ON r.subject_id = s.id
    JOIN exams e ON r.exam_id = e.id
    WHERE r.student_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
";
$results_stmt = $conn->prepare($results_query);
$results_stmt->bind_param("i", $student_data['id']);
$results_stmt->execute();
$recent_results = $results_stmt->get_result();

// Get attendance summary for current month
$current_month = date('Y-m');
$attendance_query = "
    SELECT 
        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
        COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent,
        COUNT(CASE WHEN status = 'Late' THEN 1 END) as late,
        COUNT(*) as total
    FROM attendance 
    WHERE student_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("is", $student_data['id'], $current_month);
$attendance_stmt->execute();
$attendance_stats = $attendance_stmt->get_result()->fetch_assoc();

// Calculate attendance percentage
$attendance_percentage = $attendance_stats['total'] > 0 ? 
    round(($attendance_stats['present'] / $attendance_stats['total']) * 100, 1) : 0;

// Get available exams for result viewing
$exams_query = "
    SELECT DISTINCT e.id, e.exam_name, e.exam_type, e.exam_date
    FROM exams e
    JOIN results r ON e.id = r.exam_id
    WHERE r.student_id = ?
    ORDER BY e.exam_date DESC
";
$exams_stmt = $conn->prepare($exams_query);
$exams_stmt->bind_param("i", $student_data['id']);
$exams_stmt->execute();
$exams_result = $exams_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - School Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Student Dashboard</h1>
                    <p>Welcome, <?php echo $student_data['name']; ?>! (<?php echo $student_data['roll_no']; ?>)</p>
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
                <li><a href="results.php"><i class="fas fa-chart-bar"></i> View Results</a></li>
                <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
        </nav>
        
        <div class="stats-cards">
            <div class="stat-card">
                <i class="fas fa-percentage" style="font-size: 2rem; color: #667eea; margin-bottom: 10px;"></i>
                <h3><?php echo $attendance_percentage; ?>%</h3>
                <p>Attendance This Month</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-calendar-check" style="font-size: 2rem; color: #28a745; margin-bottom: 10px;"></i>
                <h3><?php echo $attendance_stats['present']; ?></h3>
                <p>Days Present</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-calendar-times" style="font-size: 2rem; color: #dc3545; margin-bottom: 10px;"></i>
                <h3><?php echo $attendance_stats['absent']; ?></h3>
                <p>Days Absent</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-clock" style="font-size: 2rem; color: #ffc107; margin-bottom: 10px;"></i>
                <h3><?php echo $attendance_stats['late']; ?></h3>
                <p>Days Late</p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Recent Results -->
            <div class="table-container">
                <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
                    <h3><i class="fas fa-chart-line"></i> Recent Results</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Exam</th>
                            <th>Marks</th>
                            <th>Percentage</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_results->num_rows > 0): ?>
                            <?php while ($row = $recent_results->fetch_assoc()): ?>
                                <?php $percentage = round(($row['marks_obtained'] / $row['total_marks']) * 100, 1); ?>
                                <tr>
                                    <td><?php echo $row['subject_name']; ?></td>
                                    <td><?php echo $row['exam_name']; ?></td>
                                    <td><?php echo $row['marks_obtained'] . '/' . $row['total_marks']; ?></td>
                                    <td>
                                        <span style="color: <?php echo $percentage >= 60 ? '#28a745' : ($percentage >= 40 ? '#ffc107' : '#dc3545'); ?>">
                                            <?php echo $percentage; ?>%
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No results available yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Student Profile -->
            <div class="form-container">
                <h3><i class="fas fa-user"></i> Student Profile</h3>
                
                <div style="text-align: center; margin-bottom: 20px;">
                    <?php if ($student_data['photo']): ?>
                        <img src="../uploads/students/<?php echo $student_data['photo']; ?>" 
                             alt="Student Photo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100px; height: 100px; background: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <i class="fas fa-user" style="font-size: 2rem; color: #667eea;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: left;">
                    <p><strong>Name:</strong> <?php echo $student_data['name']; ?></p>
                    <p><strong>Roll No:</strong> <?php echo $student_data['roll_no']; ?></p>
                    <p><strong>Class:</strong> <?php echo $student_data['class_name'] . ' - ' . $student_data['section']; ?></p>
                    <p><strong>Date of Birth:</strong> <?php echo $student_data['date_of_birth'] ? date('M j, Y', strtotime($student_data['date_of_birth'])) : 'Not set'; ?></p>
                    <p><strong>Gender:</strong> <?php echo $student_data['gender'] ?: 'Not set'; ?></p>
                    <p><strong>Parent:</strong> <?php echo $student_data['parent_name'] ?: 'Not set'; ?></p>
                    <p><strong>Contact:</strong> <?php echo $student_data['parent_contact'] ?: 'Not set'; ?></p>
                </div>
                
                <a href="profile.php" class="btn btn-primary" style="width: 100%; margin-top: 15px;">
                    <i class="fas fa-edit"></i> View Full Profile
                </a>
            </div>
        </div>
        
        <!-- Available Result Cards -->
        <div class="form-container" style="margin-top: 30px;">
            <h3><i class="fas fa-file-pdf"></i> Download Result Cards</h3>
            
            <?php if ($exams_result->num_rows > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px;">
                    <?php while ($exam = $exams_result->fetch_assoc()): ?>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #667eea;">
                            <h4 style="margin: 0 0 10px 0; color: #2c3e50;"><?php echo $exam['exam_name']; ?></h4>
                            <p style="margin: 0 0 15px 0; color: #7f8c8d; font-size: 0.9rem;">
                                <?php echo $exam['exam_type']; ?> • <?php echo date('M j, Y', strtotime($exam['exam_date'])); ?>
                            </p>
                            <div style="display: flex; gap: 10px;">
                                <a href="result-card.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary" style="flex: 1; font-size: 0.9rem;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="result-card.php?exam_id=<?php echo $exam['id']; ?>&download=1" class="btn btn-secondary" style="flex: 1; font-size: 0.9rem;">
                                    <i class="fas fa-download"></i> PDF
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">No result cards available yet.</p>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="form-container" style="margin-top: 30px;">
            <h3><i class="fas fa-tasks"></i> Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                <a href="results.php" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i> View All Results
                </a>
                <a href="attendance.php" class="btn btn-primary">
                    <i class="fas fa-calendar-check"></i> Attendance History
                </a>
                <a href="profile.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i> Update Profile
                </a>
                <a href="../result-check.php?roll_no=<?php echo $student_data['roll_no']; ?>" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Quick Result Check
                </a>
            </div>
        </div>
    </div>
</body>
</html>
