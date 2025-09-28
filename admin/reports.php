<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle report generation
$report_data = [];
$selected_class = '';
$selected_exam = '';

if ($_POST) {
    $class_id = $_POST['class_id'] ?? '';
    $exam_id = $_POST['exam_id'] ?? '';
    
    if (!empty($class_id) && !empty($exam_id)) {
        $selected_class = $class_id;
        $selected_exam = $exam_id;
        
        // Get class and exam info
        $class_query = "SELECT class_name, section FROM classes WHERE id = ?";
        $class_stmt = $conn->prepare($class_query);
        $class_stmt->bind_param("i", $class_id);
        $class_stmt->execute();
        $class_info = $class_stmt->get_result()->fetch_assoc();
        
        $exam_query = "SELECT exam_name, exam_type FROM exams WHERE id = ?";
        $exam_stmt = $conn->prepare($exam_query);
        $exam_stmt->bind_param("i", $exam_id);
        $exam_stmt->execute();
        $exam_info = $exam_stmt->get_result()->fetch_assoc();
        
        // Get student results
        $query = "SELECT 
                    s.name as student_name,
                    s.roll_no,
                    sub.subject_name,
                    r.marks_obtained,
                    r.total_marks,
                    ROUND((r.marks_obtained / r.total_marks) * 100, 2) as percentage
                  FROM results r
                  JOIN students s ON r.student_id = s.id
                  JOIN subjects sub ON r.subject_id = sub.id
                  WHERE s.class_id = ? AND r.exam_id = ?
                  ORDER BY s.roll_no, sub.subject_name";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $class_id, $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $report_data[$row['roll_no']]['student_name'] = $row['student_name'];
            $report_data[$row['roll_no']]['subjects'][] = $row;
        }
    }
}

// Get classes for dropdown
$classes_query = "SELECT id, class_name, section FROM classes ORDER BY class_name, section";
$classes_result = $conn->query($classes_query);

// Get exams for dropdown
$exams_query = "SELECT id, exam_name, exam_type FROM exams ORDER BY exam_date DESC";
$exams_result = $conn->query($exams_query);

// Get school settings
$school_query = "SELECT * FROM school_settings WHERE id = 1";
$school_result = $conn->query($school_query);
$school_settings = $school_result->fetch_assoc();

// Get class teacher information if report is generated
$class_teacher = null;
if (!empty($report_data) && isset($class_info)) {
    $teacher_query = "SELECT t.name as teacher_name, t.qualification 
                      FROM teacher_classes tc 
                      JOIN teachers t ON tc.teacher_id = t.id 
                      WHERE tc.class_id = ? AND tc.is_class_teacher = 1 
                      LIMIT 1";
    $teacher_stmt = $conn->prepare($teacher_query);
    $teacher_stmt->bind_param("i", $class_id);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();
    $class_teacher = $teacher_result->fetch_assoc();
    
    // If no class teacher found, get any teacher assigned to this class
    if (!$class_teacher) {
        $teacher_query = "SELECT DISTINCT t.name as teacher_name, t.qualification 
                          FROM teacher_classes tc 
                          JOIN teachers t ON tc.teacher_id = t.id 
                          WHERE tc.class_id = ? 
                          LIMIT 1";
        $teacher_stmt = $conn->prepare($teacher_query);
        $teacher_stmt->bind_param("i", $class_id);
        $teacher_stmt->execute();
        $teacher_result = $teacher_stmt->get_result();
        $class_teacher = $teacher_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn-primary {
            background: #667eea;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .table-responsive {
            overflow-x: auto;
            margin: 20px 0;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .report-info {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .report-info h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .report-info p {
            margin: 0;
            color: #666;
        }
        
        .print-header {
            display: none;
        }
        
        .print-footer {
            display: none;
        }
        
        @media print {
            .btn, .form-container:first-child, .dashboard-header, .dashboard-nav {
                display: none !important;
            }
            
            body {
                margin: 0;
                padding: 20px;
                font-family: Arial, sans-serif;
            }
            
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #333;
                padding-bottom: 20px;
            }
            
            .print-header .school-logo {
                width: 80px;
                height: 80px;
                margin: 0 auto 15px;
                display: block;
            }
            
            .print-header h1 {
                margin: 0 0 5px 0;
                font-size: 24px;
                color: #333;
            }
            
            .print-header .school-info {
                margin-bottom: 15px;
                font-size: 14px;
                color: #666;
            }
            
            .print-header .report-title {
                font-size: 18px;
                font-weight: bold;
                color: #333;
                margin: 15px 0 5px 0;
            }
            
            .print-header .class-info {
                font-size: 16px;
                color: #333;
                margin-bottom: 10px;
            }
            
            .print-footer {
                display: block !important;
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #333;
            }
            
            .signature-section {
                display: flex;
                justify-content: space-between;
                margin-top: 60px;
            }
            
            .signature-box {
                text-align: center;
                width: 200px;
            }
            
            .signature-line {
                border-top: 1px solid #333;
                margin-bottom: 5px;
                height: 50px;
            }
            
            .signature-label {
                font-size: 12px;
                color: #666;
            }
            
            .data-table {
                font-size: 12px;
            }
            
            .data-table th,
            .data-table td {
                padding: 8px 6px;
            }
            
            .report-info {
                display: none;
            }
        }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="fas fa-file-alt"></i> Reports</h1>
                    <p>Generate and view student performance reports</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <nav class="dashboard-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="school-settings.php"><i class="fas fa-cog"></i> School Settings</a></li>
                <li><a href="classes.php"><i class="fas fa-chalkboard"></i> Classes</a></li>
                <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
                <li><a href="students.php"><i class="fas fa-graduation-cap"></i> Students</a></li>
                <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
                <li><a href="exams.php"><i class="fas fa-clipboard-list"></i> Exams</a></li>
                <li><a href="results.php"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-file-pdf"></i> Reports</a></li>
                <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
            </ul>
        </nav>
        
        <!-- Report Generation Form -->
        <div class="form-container">
            <h3><i class="fas fa-chart-bar"></i> Generate Report</h3>
                        <form method="POST" class="form-grid">
                            <div class="form-group">
                                <label for="class_id">Select Class:</label>
                                <select name="class_id" id="class_id" required>
                                    <option value="">Choose Class</option>
                                    <?php while ($class = $classes_result->fetch_assoc()): ?>
                                        <option value="<?= $class['id'] ?>" <?= $selected_class == $class['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($class['class_name'] . ' - ' . $class['section']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="exam_id">Select Exam:</label>
                                <select name="exam_id" id="exam_id" required>
                                    <option value="">Choose Exam</option>
                                    <?php while ($exam = $exams_result->fetch_assoc()): ?>
                                        <option value="<?= $exam['id'] ?>" <?= $selected_exam == $exam['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($exam['exam_name'] . ' (' . $exam['exam_type'] . ')') ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

        <!-- Report Results -->
        <?php if (!empty($report_data)): ?>
        <div class="form-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3><i class="fas fa-chart-line"></i> Report Results</h3>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
            
            <!-- Print Header (only visible when printing) -->
            <div class="print-header">
                <?php if (!empty($school_settings['logo']) && file_exists("../uploads/school/" . $school_settings['logo'])): ?>
                    <img src="../uploads/school/<?= htmlspecialchars($school_settings['logo']) ?>" alt="School Logo" class="school-logo">
                <?php else: ?>
                    <div style="width: 80px; height: 80px; margin: 0 auto 15px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-school" style="font-size: 30px; color: #666;"></i>
                    </div>
                <?php endif; ?>
                
                <h1><?= htmlspecialchars($school_settings['school_name'] ?? 'School Name') ?></h1>
                <div class="school-info">
                    <?php if (!empty($school_settings['address'])): ?>
                        <p><?= htmlspecialchars($school_settings['address']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($school_settings['phone']) || !empty($school_settings['email'])): ?>
                        <p>
                            <?= !empty($school_settings['phone']) ? 'Phone: ' . htmlspecialchars($school_settings['phone']) : '' ?>
                            <?= !empty($school_settings['phone']) && !empty($school_settings['email']) ? ' | ' : '' ?>
                            <?= !empty($school_settings['email']) ? 'Email: ' . htmlspecialchars($school_settings['email']) : '' ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="report-title">EXAMINATION REPORT</div>
                <div class="class-info">
                    <strong>Class:</strong> <?= htmlspecialchars($class_info['class_name'] . ' - ' . $class_info['section']) ?> | 
                    <strong>Exam:</strong> <?= htmlspecialchars($exam_info['exam_name'] . ' (' . $exam_info['exam_type'] . ')') ?>
                </div>
                <div style="font-size: 14px; color: #666;">
                    <strong>Academic Year:</strong> <?= htmlspecialchars($school_settings['academic_year'] ?? date('Y')) ?> | 
                    <strong>Date:</strong> <?= date('F j, Y') ?>
                </div>
            </div>
            
            <div class="report-info">
                <h4><?= htmlspecialchars($class_info['class_name'] . ' - ' . $class_info['section']) ?></h4>
                <p><strong>Exam:</strong> <?= htmlspecialchars($exam_info['exam_name'] . ' (' . $exam_info['exam_type'] . ')') ?></p>
            </div>
                        
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Roll No</th>
                                        <th>Student Name</th>
                                        <?php 
                                        // Get unique subjects for header
                                        $subjects = [];
                                        foreach ($report_data as $student) {
                                            foreach ($student['subjects'] as $subject) {
                                                $subjects[$subject['subject_name']] = true;
                                            }
                                        }
                                        foreach (array_keys($subjects) as $subject): ?>
                                            <th><?= htmlspecialchars($subject) ?></th>
                                        <?php endforeach; ?>
                                        <th>Total</th>
                                        <th>Average</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data as $roll_no => $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($roll_no) ?></td>
                                            <td><?= htmlspecialchars($student['student_name']) ?></td>
                                            <?php 
                                            $total_marks = 0;
                                            $total_obtained = 0;
                                            $subject_marks = [];
                                            
                                            // Organize marks by subject
                                            foreach ($student['subjects'] as $subject) {
                                                $subject_marks[$subject['subject_name']] = $subject;
                                                $total_marks += $subject['total_marks'];
                                                $total_obtained += $subject['marks_obtained'];
                                            }
                                            
                                            // Display marks for each subject
                                            foreach (array_keys($subjects) as $subject_name): ?>
                                                <td>
                                                    <?php if (isset($subject_marks[$subject_name])): ?>
                                                        <?= $subject_marks[$subject_name]['marks_obtained'] ?>/<?= $subject_marks[$subject_name]['total_marks'] ?>
                                                        <small>(<?= $subject_marks[$subject_name]['percentage'] ?>%)</small>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td><strong><?= $total_obtained ?>/<?= $total_marks ?></strong></td>
                                            <td><strong><?= $total_marks > 0 ? round(($total_obtained / $total_marks) * 100, 2) : 0 ?>%</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Print Footer (only visible when printing) -->
                        <div class="print-footer">
                            <div class="signature-section">
                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <div class="signature-label">
                                        <strong>Class Teacher</strong><br>
                                        <?= $class_teacher ? htmlspecialchars($class_teacher['teacher_name']) : 'Teacher Name' ?><br>
                                        <?= $class_teacher && !empty($class_teacher['qualification']) ? htmlspecialchars($class_teacher['qualification']) : '' ?>
                                    </div>
                                </div>
                                
                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <div class="signature-label">
                                        <strong>Principal</strong><br>
                                        <?= !empty($school_settings['principal_name']) ? htmlspecialchars($school_settings['principal_name']) : 'Principal Name' ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
                                <p>This is a computer-generated report. For any queries, please contact the school administration.</p>
                                <p><strong>Generated on:</strong> <?= date('F j, Y \a\t g:i A') ?></p>
                            </div>
                        </div>
        </div>
        <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
