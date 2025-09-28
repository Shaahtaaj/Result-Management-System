<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Get student information
$student_query = "SELECT s.*, c.class_name, c.section FROM students s 
                  JOIN classes c ON s.class_id = c.id 
                  WHERE s.user_id = ?";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("i", $_SESSION['user_id']);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

if (!$student) {
    header("Location: login.php");
    exit();
}

// Get student results grouped by exam
$results_query = "SELECT 
                    e.id as exam_id,
                    e.exam_name,
                    e.exam_type,
                    e.exam_date,
                    sub.subject_name,
                    r.marks_obtained,
                    r.total_marks,
                    ROUND((r.marks_obtained / r.total_marks) * 100, 2) as percentage
                  FROM results r
                  JOIN exams e ON r.exam_id = e.id
                  JOIN subjects sub ON r.subject_id = sub.id
                  WHERE r.student_id = ?
                  ORDER BY e.exam_date DESC, sub.subject_name";

$results_stmt = $conn->prepare($results_query);
$results_stmt->bind_param("i", $student['id']);
$results_stmt->execute();
$results_result = $results_stmt->get_result();

$exam_results = [];
while ($row = $results_result->fetch_assoc()) {
    $exam_results[$row['exam_id']]['exam_info'] = [
        'exam_name' => $row['exam_name'],
        'exam_type' => $row['exam_type'],
        'exam_date' => $row['exam_date']
    ];
    $exam_results[$row['exam_id']]['subjects'][] = $row;
}

// Calculate overall statistics
$total_exams = count($exam_results);
$overall_average = 0;
$total_subjects = 0;
$total_percentage = 0;

foreach ($exam_results as $exam) {
    foreach ($exam['subjects'] as $subject) {
        $total_percentage += $subject['percentage'];
        $total_subjects++;
    }
}

if ($total_subjects > 0) {
    $overall_average = round($total_percentage / $total_subjects, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/student.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="student-container">
        <!-- Header -->
        <header class="student-header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Student Portal</span>
                </div>
                <nav class="header-nav">
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="results.php" class="active"><i class="fas fa-chart-line"></i> Results</a>
                    <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
                <div class="user-info">
                    <span>Welcome, <?= htmlspecialchars($student['name']) ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1><i class="fas fa-chart-line"></i> My Results</h1>
                <p>View your examination results and performance</p>
            </div>

            <!-- Student Info Card -->
            <div class="card student-info-card">
                <div class="card-body">
                    <div class="student-details">
                        <h3><?= htmlspecialchars($student['name']) ?></h3>
                        <p><strong>Roll Number:</strong> <?= htmlspecialchars($student['roll_no']) ?></p>
                        <p><strong>Class:</strong> <?= htmlspecialchars($student['class_name'] . ' - ' . $student['section']) ?></p>
                    </div>
                    <div class="performance-stats">
                        <div class="stat">
                            <h4><?= $total_exams ?></h4>
                            <p>Exams Taken</p>
                        </div>
                        <div class="stat">
                            <h4><?= $overall_average ?>%</h4>
                            <p>Overall Average</p>
                        </div>
                        <div class="stat">
                            <h4><?= $total_subjects ?></h4>
                            <p>Total Subjects</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results by Exam -->
            <?php if (!empty($exam_results)): ?>
                <?php foreach ($exam_results as $exam_id => $exam_data): ?>
                    <div class="card exam-card">
                        <div class="card-header">
                            <h3><?= htmlspecialchars($exam_data['exam_info']['exam_name']) ?></h3>
                            <div class="exam-meta">
                                <span class="exam-type"><?= htmlspecialchars($exam_data['exam_info']['exam_type']) ?></span>
                                <span class="exam-date"><?= date('M j, Y', strtotime($exam_data['exam_info']['exam_date'])) ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="results-table-container">
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Marks Obtained</th>
                                            <th>Total Marks</th>
                                            <th>Percentage</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $exam_total_obtained = 0;
                                        $exam_total_marks = 0;
                                        foreach ($exam_data['subjects'] as $subject): 
                                            $exam_total_obtained += $subject['marks_obtained'];
                                            $exam_total_marks += $subject['total_marks'];
                                            
                                            // Calculate grade
                                            $percentage = $subject['percentage'];
                                            if ($percentage >= 90) $grade = 'A+';
                                            elseif ($percentage >= 80) $grade = 'A';
                                            elseif ($percentage >= 70) $grade = 'B+';
                                            elseif ($percentage >= 60) $grade = 'B';
                                            elseif ($percentage >= 50) $grade = 'C';
                                            elseif ($percentage >= 40) $grade = 'D';
                                            else $grade = 'F';
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                                                <td><?= $subject['marks_obtained'] ?></td>
                                                <td><?= $subject['total_marks'] ?></td>
                                                <td>
                                                    <span class="percentage <?= $percentage >= 40 ? 'pass' : 'fail' ?>">
                                                        <?= $subject['percentage'] ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="grade grade-<?= strtolower($grade) ?>">
                                                        <?= $grade ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="total-row">
                                            <td><strong>Total</strong></td>
                                            <td><strong><?= $exam_total_obtained ?></strong></td>
                                            <td><strong><?= $exam_total_marks ?></strong></td>
                                            <td>
                                                <strong class="percentage <?= ($exam_total_obtained / $exam_total_marks * 100) >= 40 ? 'pass' : 'fail' ?>">
                                                    <?= round($exam_total_obtained / $exam_total_marks * 100, 2) ?>%
                                                </strong>
                                            </td>
                                            <td>
                                                <?php 
                                                $overall_percentage = round($exam_total_obtained / $exam_total_marks * 100, 2);
                                                if ($overall_percentage >= 90) $overall_grade = 'A+';
                                                elseif ($overall_percentage >= 80) $overall_grade = 'A';
                                                elseif ($overall_percentage >= 70) $overall_grade = 'B+';
                                                elseif ($overall_percentage >= 60) $overall_grade = 'B';
                                                elseif ($overall_percentage >= 50) $overall_grade = 'C';
                                                elseif ($overall_percentage >= 40) $overall_grade = 'D';
                                                else $overall_grade = 'F';
                                                ?>
                                                <strong class="grade grade-<?= strtolower($overall_grade) ?>">
                                                    <?= $overall_grade ?>
                                                </strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <div class="exam-actions">
                                <a href="result-card.php?exam_id=<?= $exam_id ?>" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-download"></i> Download Result Card
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-clipboard-list fa-3x text-muted"></i>
                        <h3>No Results Available</h3>
                        <p>Your examination results will appear here once they are published.</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <style>
        .student-container {
            min-height: 100vh;
            background: #f5f5f5;
        }
        
        .student-header {
            background: #2c3e50;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .header-nav {
            display: flex;
            gap: 20px;
        }
        
        .header-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .header-nav a:hover,
        .header-nav a.active {
            background: #34495e;
        }
        
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .content-header {
            margin-bottom: 30px;
        }
        
        .content-header h1 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .student-info-card .card-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .performance-stats {
            display: flex;
            gap: 30px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat h4 {
            margin: 0;
            font-size: 2rem;
            color: #3498db;
        }
        
        .stat p {
            margin: 5px 0 0 0;
            color: #666;
        }
        
        .exam-meta {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .exam-type {
            background: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
        }
        
        .exam-date {
            color: #666;
        }
        
        .results-table-container {
            overflow-x: auto;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .results-table th,
        .results-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .results-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .total-row {
            background: #f8f9fa;
        }
        
        .percentage.pass {
            color: #27ae60;
            font-weight: bold;
        }
        
        .percentage.fail {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .grade {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .grade-a\+ { background: #27ae60; color: white; }
        .grade-a { background: #2ecc71; color: white; }
        .grade-b\+ { background: #f39c12; color: white; }
        .grade-b { background: #e67e22; color: white; }
        .grade-c { background: #f1c40f; color: #333; }
        .grade-d { background: #e74c3c; color: white; }
        .grade-f { background: #c0392b; color: white; }
        
        .exam-actions {
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #999;
        }
        
        .fa-3x {
            font-size: 3rem;
            margin-bottom: 20px;
        }
    </style>
</body>
</html>
