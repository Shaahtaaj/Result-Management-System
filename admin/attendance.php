<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$selected_class = '';
$selected_date = date('Y-m-d');

// Handle attendance operations
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_attendance':
                $class_id = $_POST['class_id'];
                $date = $_POST['date'];
                $attendance_data = $_POST['attendance'] ?? [];
                
                foreach ($attendance_data as $student_id => $status) {
                    // Check if attendance already exists
                    $check_query = "SELECT id FROM attendance WHERE student_id = ? AND date = ?";
                    $check_stmt = $conn->prepare($check_query);
                    $check_stmt->bind_param("is", $student_id, $date);
                    $check_stmt->execute();
                    $exists = $check_stmt->get_result()->fetch_assoc();
                    
                    if ($exists) {
                        // Update existing attendance
                        $update_query = "UPDATE attendance SET status = ?, marked_by = ? WHERE student_id = ? AND date = ?";
                        $update_stmt = $conn->prepare($update_query);
                        $update_stmt->bind_param("siis", $status, $_SESSION['user_id'], $student_id, $date);
                        $update_stmt->execute();
                    } else {
                        // Insert new attendance
                        $insert_query = "INSERT INTO attendance (student_id, date, status, marked_by) VALUES (?, ?, ?, ?)";
                        $insert_stmt = $conn->prepare($insert_query);
                        $insert_stmt->bind_param("issi", $student_id, $date, $status, $_SESSION['user_id']);
                        $insert_stmt->execute();
                    }
                }
                
                $message = "Attendance marked successfully!";
                $selected_class = $class_id;
                break;
        }
    }
}

// Get attendance data if class and date are selected
$attendance_data = [];
$students = [];

if (isset($_GET['class_id']) || $selected_class) {
    $class_id = $_GET['class_id'] ?? $selected_class;
    $date = $_GET['date'] ?? $selected_date;
    $selected_class = $class_id;
    $selected_date = $date;
    
    // Get students in the class
    $students_query = "SELECT s.id, s.name, s.roll_no FROM students s WHERE s.class_id = ? ORDER BY s.roll_no";
    $students_stmt = $conn->prepare($students_query);
    $students_stmt->bind_param("i", $class_id);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    
    while ($student = $students_result->fetch_assoc()) {
        $students[] = $student;
        
        // Get existing attendance for this date
        $att_query = "SELECT status FROM attendance WHERE student_id = ? AND date = ?";
        $att_stmt = $conn->prepare($att_query);
        $att_stmt->bind_param("is", $student['id'], $date);
        $att_stmt->execute();
        $att_result = $att_stmt->get_result()->fetch_assoc();
        
        $attendance_data[$student['id']] = $att_result['status'] ?? 'Present';
    }
}

// Get classes for dropdown
$classes_query = "SELECT id, class_name, section FROM classes ORDER BY class_name, section";
$classes_result = $conn->query($classes_query);

// Get attendance summary for the selected class
$attendance_summary = [];
if ($selected_class) {
    $summary_query = "SELECT 
                        DATE(a.date) as attendance_date,
                        COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
                        COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent_count,
                        COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late_count,
                        COUNT(*) as total_marked
                      FROM attendance a
                      JOIN students s ON a.student_id = s.id
                      WHERE s.class_id = ?
                      GROUP BY DATE(a.date)
                      ORDER BY attendance_date DESC
                      LIMIT 10";
    $summary_stmt = $conn->prepare($summary_query);
    $summary_stmt->bind_param("i", $selected_class);
    $summary_stmt->execute();
    $summary_result = $summary_stmt->get_result();
    
    while ($row = $summary_result->fetch_assoc()) {
        $attendance_summary[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .attendance-controls {
            margin-bottom: 20px;
        }
        
        .attendance-controls .btn {
            margin-right: 10px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #212529; }
        
        .form-actions {
            margin-top: 20px;
            text-align: center;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .table-container {
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
        
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="fas fa-calendar-check"></i> Attendance Management</h1>
                    <p>Mark and manage student attendance</p>
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
                <li><a href="attendance.php" class="active"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="reports.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
            </ul>
        </nav>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Class and Date Selection -->
        <div class="form-container">
            <h3><i class="fas fa-calendar-alt"></i> Select Class and Date</h3>
                        <form method="GET" class="form-grid">
                            <div class="form-group">
                                <label for="class_id">Select Class:</label>
                                <select name="class_id" id="class_id" onchange="this.form.submit()">
                                    <option value="">Choose Class</option>
                                    <?php 
                                    $classes_result->data_seek(0);
                                    while ($class = $classes_result->fetch_assoc()): ?>
                                        <option value="<?= $class['id'] ?>" <?= $selected_class == $class['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($class['class_name'] . ' - ' . $class['section']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date">Select Date:</label>
                                <input type="date" name="date" id="date" value="<?= $selected_date ?>" onchange="this.form.submit()">
                            </div>
                        </form>
        </div>

        <!-- Attendance Marking -->
        <?php if (!empty($students)): ?>
        <div class="form-container">
            <h3><i class="fas fa-check-circle"></i> Mark Attendance - <?= date('F j, Y', strtotime($selected_date)) ?></h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="mark_attendance">
                            <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                            <input type="hidden" name="date" value="<?= $selected_date ?>">
                            
                            <div class="attendance-controls">
                                <button type="button" onclick="markAll('Present')" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Mark All Present
                                </button>
                                <button type="button" onclick="markAll('Absent')" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times"></i> Mark All Absent
                                </button>
                            </div>
                            
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Roll No</th>
                                            <th>Student Name</th>
                                            <th>Present</th>
                                            <th>Absent</th>
                                            <th>Late</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($student['roll_no']) ?></td>
                                                <td><?= htmlspecialchars($student['name']) ?></td>
                                                <td>
                                                    <input type="radio" name="attendance[<?= $student['id'] ?>]" value="Present" 
                                                           <?= $attendance_data[$student['id']] === 'Present' ? 'checked' : '' ?>>
                                                </td>
                                                <td>
                                                    <input type="radio" name="attendance[<?= $student['id'] ?>]" value="Absent" 
                                                           <?= $attendance_data[$student['id']] === 'Absent' ? 'checked' : '' ?>>
                                                </td>
                                                <td>
                                                    <input type="radio" name="attendance[<?= $student['id'] ?>]" value="Late" 
                                                           <?= $attendance_data[$student['id']] === 'Late' ? 'checked' : '' ?>>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Attendance
                                </button>
                            </div>
                        </form>
        </div>
        <?php endif; ?>

        <!-- Attendance Summary -->
        <?php if (!empty($attendance_summary)): ?>
        <div class="form-container">
            <h3><i class="fas fa-chart-bar"></i> Recent Attendance Summary</h3>
            <div class="table-container">
                <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Late</th>
                                        <th>Total</th>
                                        <th>Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_summary as $summary): ?>
                                        <tr>
                                            <td><?= date('M j, Y', strtotime($summary['attendance_date'])) ?></td>
                                            <td><span class="badge badge-success"><?= $summary['present_count'] ?></span></td>
                                            <td><span class="badge badge-danger"><?= $summary['absent_count'] ?></span></td>
                                            <td><span class="badge badge-warning"><?= $summary['late_count'] ?></span></td>
                                            <td><?= $summary['total_marked'] ?></td>
                                            <td>
                                                <?php 
                                                $percentage = $summary['total_marked'] > 0 ? 
                                                    round(($summary['present_count'] + $summary['late_count']) / $summary['total_marked'] * 100, 1) : 0;
                                                ?>
                                                <strong><?= $percentage ?>%</strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function markAll(status) {
            const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
            radios.forEach(radio => {
                radio.checked = true;
            });
        }
    </script>
</body>
</html>
