<?php
include 'config/database.php';

$roll_no = isset($_POST['roll_no']) ? sanitize_input($_POST['roll_no']) : (isset($_GET['roll_no']) ? sanitize_input($_GET['roll_no']) : '');
$student_data = null;
$results_data = [];
$error = '';

if (!empty($roll_no)) {
    // Get student information
    $student_query = "
        SELECT s.*, c.class_name, c.section 
        FROM students s 
        JOIN classes c ON s.class_id = c.id 
        WHERE s.roll_no = ?
    ";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("s", $roll_no);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    if ($student_result->num_rows > 0) {
        $student_data = $student_result->fetch_assoc();
        
        // Get recent results
        $results_query = "
            SELECT r.*, s.subject_name, e.exam_name, e.exam_type, e.exam_date
            FROM results r
            JOIN subjects s ON r.subject_id = s.id
            JOIN exams e ON r.exam_id = e.id
            WHERE r.student_id = ?
            ORDER BY e.exam_date DESC, s.subject_name
        ";
        $results_stmt = $conn->prepare($results_query);
        $results_stmt->bind_param("i", $student_data['id']);
        $results_stmt->execute();
        $results_result = $results_stmt->get_result();
        
        while ($row = $results_result->fetch_assoc()) {
            $results_data[] = $row;
        }
        
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
        
    } else {
        $error = "No student found with roll number: " . $roll_no;
    }
}

$school_settings = get_school_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Check - School Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="hero-section" style="min-height: auto; padding: 50px 0;">
        <div class="container">
            <div class="hero-content">
                <div class="school-info">
                    <?php if ($school_settings && $school_settings['logo']): ?>
                        <img src="uploads/school/<?php echo $school_settings['logo']; ?>" alt="School Logo" class="school-logo">
                    <?php endif; ?>
                    <h1><?php echo $school_settings ? $school_settings['school_name'] : 'School Management System'; ?></h1>
                    <p class="motto"><?php echo $school_settings ? $school_settings['motto'] : 'Excellence in Education'; ?></p>
                </div>
                
                <div class="quick-result-check">
                    <h3><i class="fas fa-search"></i> Quick Result Check</h3>
                    <form action="result-check.php" method="POST" class="result-form">
                        <input type="text" name="roll_no" placeholder="Enter Roll Number" value="<?php echo htmlspecialchars($roll_no); ?>" required>
                        <button type="submit" class="btn btn-secondary">Check Result</button>
                    </form>
                    
                    <div style="margin-top: 15px;">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-top: 30px;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($student_data): ?>
                    <div style="background: white; padding: 30px; border-radius: 15px; margin-top: 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
                        <h2 style="text-align: center; color: #2c3e50; margin-bottom: 30px;">
                            <i class="fas fa-user-graduate"></i> Student Information
                        </h2>
                        
                        <!-- Student Profile -->
                        <div style="display: flex; align-items: center; background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                            <?php if ($student_data['photo']): ?>
                                <img src="uploads/students/<?php echo $student_data['photo']; ?>" 
                                     alt="Student Photo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-right: 20px; border: 3px solid #667eea;">
                            <?php else: ?>
                                <div style="width: 100px; height: 100px; background: #667eea; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                                    <i class="fas fa-user" style="font-size: 2rem; color: white;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <h3 style="margin: 0 0 10px 0; color: #2c3e50;"><?php echo htmlspecialchars($student_data['name']); ?></h3>
                                <p style="margin: 5px 0; color: #7f8c8d;"><strong>Roll Number:</strong> <?php echo htmlspecialchars($student_data['roll_no']); ?></p>
                                <p style="margin: 5px 0; color: #7f8c8d;"><strong>Class:</strong> <?php echo htmlspecialchars($student_data['class_name'] . ' - ' . $student_data['section']); ?></p>
                                <p style="margin: 5px 0; color: #7f8c8d;"><strong>Parent:</strong> <?php echo htmlspecialchars($student_data['parent_name'] ?: 'Not Available'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Attendance Summary -->
                        <?php if (isset($attendance_stats)): ?>
                            <div class="stats-cards" style="margin-bottom: 30px;">
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
                        <?php endif; ?>
                        
                        <!-- Results -->
                        <?php if (!empty($results_data)): ?>
                            <h3 style="color: #2c3e50; margin-bottom: 20px;">
                                <i class="fas fa-chart-bar"></i> Academic Results
                            </h3>
                            
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Exam</th>
                                            <th>Subject</th>
                                            <th>Marks</th>
                                            <th>Percentage</th>
                                            <th>Grade</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results_data as $result): ?>
                                            <?php 
                                            $percentage = round(($result['marks_obtained'] / $result['total_marks']) * 100, 1);
                                            if ($percentage >= 90) $grade = 'A+';
                                            elseif ($percentage >= 80) $grade = 'A';
                                            elseif ($percentage >= 70) $grade = 'B+';
                                            elseif ($percentage >= 60) $grade = 'B';
                                            elseif ($percentage >= 50) $grade = 'C+';
                                            elseif ($percentage >= 40) $grade = 'C';
                                            elseif ($percentage >= 33) $grade = 'D';
                                            else $grade = 'F';
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($result['exam_name'] . ' (' . $result['exam_type'] . ')'); ?></td>
                                                <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                                <td><?php echo $result['marks_obtained'] . '/' . $result['total_marks']; ?></td>
                                                <td>
                                                    <span style="color: <?php echo $percentage >= 60 ? '#28a745' : ($percentage >= 40 ? '#ffc107' : '#dc3545'); ?>">
                                                        <?php echo $percentage; ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span style="font-weight: bold; color: <?php echo $percentage >= 60 ? '#28a745' : ($percentage >= 40 ? '#ffc107' : '#dc3545'); ?>">
                                                        <?php echo $grade; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($result['exam_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                                <i class="fas fa-info-circle" style="font-size: 3rem; margin-bottom: 15px;"></i>
                                <h3>No Results Available</h3>
                                <p>No examination results found for this student yet.</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Login Suggestion -->
                        <div style="background: #e8f5e8; padding: 20px; border-radius: 10px; margin-top: 30px; text-align: center;">
                            <h4 style="color: #28a745; margin: 0 0 10px 0;">
                                <i class="fas fa-key"></i> Want to see more details?
                            </h4>
                            <p style="margin: 0 0 15px 0; color: #155724;">
                                Login to your student portal to view detailed results, download result cards, and access more features.
                            </p>
                            <a href="student/login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Student Login
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer style="position: relative; margin-top: 50px;">
        <div class="container">
            <p>&copy; 2024 School Result & Attendance Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
