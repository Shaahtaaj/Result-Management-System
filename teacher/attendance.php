<?php
session_start();
include '../config/database.php';
check_login('teacher');

// Fetch teacher data
$teacher_stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$teacher_stmt->bind_param("i", $_SESSION['user_id']);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
$teacher = $teacher_result->fetch_assoc();

if (!$teacher) {
    header("Location: login.php");
    exit();
}

// Fetch classes assigned to this teacher
$classes_query = "
    SELECT DISTINCT c.id, c.class_name, c.section
    FROM teacher_classes tc
    JOIN classes c ON tc.class_id = c.id
    WHERE tc.teacher_id = ?
    ORDER BY c.class_name, c.section
";
$classes_stmt = $conn->prepare($classes_query);
$classes_stmt->bind_param("i", $teacher['id']);
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();

$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[$row['id']] = $row;
}

$message = '';
$error = '';
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
    $selected_class = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $selected_date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');

    if (!isset($classes[$selected_class])) {
        $error = 'Invalid class selection.';
    } else {
        $attendance_inputs = $_POST['attendance'] ?? [];

        // Fetch students of this class for validation
        $students_stmt = $conn->prepare("SELECT id FROM students WHERE class_id = ?");
        $students_stmt->bind_param("i", $selected_class);
        $students_stmt->execute();
        $students_result = $students_stmt->get_result();

        $valid_student_ids = [];
        while ($student = $students_result->fetch_assoc()) {
            $valid_student_ids[] = (int)$student['id'];
        }

        if (empty($valid_student_ids)) {
            $error = 'No students found for the selected class.';
        } else {
            $allowed_statuses = ['Present', 'Absent', 'Late'];
            foreach ($attendance_inputs as $student_id => $status) {
                $student_id = (int)$student_id;
                if (!in_array($student_id, $valid_student_ids, true)) {
                    continue;
                }
                if (!in_array($status, $allowed_statuses, true)) {
                    $status = 'Present';
                }

                // Check if attendance already exists
                $check_stmt = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND date = ?");
                $check_stmt->bind_param("is", $student_id, $selected_date);
                $check_stmt->execute();
                $existing = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if ($existing) {
                    $update_stmt = $conn->prepare("UPDATE attendance SET status = ?, marked_by = ? WHERE student_id = ? AND date = ?");
                    $update_stmt->bind_param("siis", $status, $_SESSION['user_id'], $student_id, $selected_date);
                    $update_stmt->execute();
                    $update_stmt->close();
                } else {
                    $insert_stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status, marked_by) VALUES (?, ?, ?, ?)");
                    $insert_stmt->bind_param("issi", $student_id, $selected_date, $status, $_SESSION['user_id']);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                }
            }

            $message = 'Attendance saved successfully for ' . date('F j, Y', strtotime($selected_date)) . '.';
        }
    }

    // Redirect to avoid form re-submission
    header("Location: attendance.php?class_id=" . $selected_class . "&date=" . urlencode($selected_date) . ($error ? "&error=" . urlencode($error) : "&message=" . urlencode($message)));
    exit();
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

$students = [];
$attendance_data = [];

if ($selected_class && isset($classes[$selected_class])) {
    $students_stmt = $conn->prepare("SELECT id, name, roll_no FROM students WHERE class_id = ? ORDER BY roll_no");
    $students_stmt->bind_param("i", $selected_class);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();

    while ($student = $students_result->fetch_assoc()) {
        $students[$student['id']] = $student;
        $attendance_data[$student['id']] = 'Present';

        $att_stmt = $conn->prepare("SELECT status FROM attendance WHERE student_id = ? AND date = ?");
        $att_stmt->bind_param("is", $student['id'], $selected_date);
        $att_stmt->execute();
        $existing = $att_stmt->get_result()->fetch_assoc();
        if ($existing) {
            $attendance_data[$student['id']] = $existing['status'];
        }
        $att_stmt->close();
    }
}

// Attendance summary for recent days
$attendance_summary = [];
if ($selected_class && isset($classes[$selected_class])) {
    $summary_stmt = $conn->prepare("SELECT DATE(a.date) AS attendance_date,
        COUNT(CASE WHEN a.status = 'Present' THEN 1 END) AS present_count,
        COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) AS absent_count,
        COUNT(CASE WHEN a.status = 'Late' THEN 1 END) AS late_count,
        COUNT(*) AS total_marked
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE s.class_id = ? AND a.date <= ?
        GROUP BY DATE(a.date)
        ORDER BY attendance_date DESC
        LIMIT 10");
    $summary_stmt->bind_param("is", $selected_class, $selected_date);
    $summary_stmt->execute();
    $summary_result = $summary_stmt->get_result();
    while ($row = $summary_result->fetch_assoc()) {
        $attendance_summary[] = $row;
    }
    $summary_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Teacher Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .attendance-controls { margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap; }
        .attendance-table { width: 100%; border-collapse: collapse; }
        .attendance-table th, .attendance-table td { padding: 12px; border-bottom: 1px solid #e9ecef; }
        .attendance-table th { background: #f8f9fa; text-align: left; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; }
        .badge-success { background: #28a745; color: #fff; }
        .badge-danger { background: #dc3545; color: #fff; }
        .badge-warning { background: #ffc107; color: #212529; }
        .alert { padding: 15px; border-radius: 6px; margin: 20px 0; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .btn-sm { padding: 6px 12px; font-size: 0.9rem; }
        .table-container { overflow-x: auto; }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="fas fa-calendar-check"></i> Mark Attendance</h1>
                    <p>Manage daily attendance for your classes</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>

        <nav class="dashboard-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="attendance.php" class="active"><i class="fas fa-calendar-check"></i> Mark Attendance</a></li>
                <li><a href="results.php"><i class="fas fa-chart-bar"></i> Enter Results</a></li>
                <li><a href="students.php"><i class="fas fa-graduation-cap"></i> My Students</a></li>
                <li><a href="reports.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
            </ul>
        </nav>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3><i class="fas fa-filter"></i> Select Class & Date</h3>
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <label for="class_id">Class</label>
                    <select name="class_id" id="class_id" onchange="this.form.submit()">
                        <option value="">Choose Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class['id'] ?>" <?= $selected_class === (int)$class['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class['class_name'] . ' - ' . $class['section']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>

        <?php if (!empty($students)): ?>
            <div class="form-container">
                <h3><i class="fas fa-users"></i> Attendance for <?= htmlspecialchars($classes[$selected_class]['class_name'] . ' - ' . $classes[$selected_class]['section']) ?> (<?= date('F j, Y', strtotime($selected_date)) ?>)</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="mark_attendance">
                    <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                    <input type="hidden" name="date" value="<?= htmlspecialchars($selected_date) ?>">
                    <div class="attendance-controls">
                        <button type="button" class="btn btn-success btn-sm" onclick="markAll('Present')"><i class="fas fa-check"></i> Mark All Present</button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="markAll('Absent')"><i class="fas fa-times"></i> Mark All Absent</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="markAll('Late')"><i class="fas fa-clock"></i> Mark All Late</button>
                    </div>
                    <div class="table-container">
                        <table class="attendance-table">
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
                                <?php foreach ($students as $student_id => $student): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student['roll_no']) ?></td>
                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                        <td><input type="radio" name="attendance[<?= $student_id ?>]" value="Present" <?= $attendance_data[$student_id] === 'Present' ? 'checked' : '' ?>></td>
                                        <td><input type="radio" name="attendance[<?= $student_id ?>]" value="Absent" <?= $attendance_data[$student_id] === 'Absent' ? 'checked' : '' ?>></td>
                                        <td><input type="radio" name="attendance[<?= $student_id ?>]" value="Late" <?= $attendance_data[$student_id] === 'Late' ? 'checked' : '' ?>></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Attendance</button>
                    </div>
                </form>
            </div>
        <?php elseif ($selected_class): ?>
            <div class="form-container">
                <p style="margin: 0; color: #7f8c8d;">No students found in the selected class.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($attendance_summary)): ?>
            <div class="form-container">
                <h3><i class="fas fa-chart-bar"></i> Recent Attendance Summary</h3>
                <div class="table-container">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Total Marked</th>
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
                                        $percentage = $summary['total_marked'] > 0
                                            ? round(($summary['present_count'] + $summary['late_count']) / $summary['total_marked'] * 100, 1)
                                            : 0;
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
            document.querySelectorAll('input[type="radio"][value="Present"]').forEach(radio => {
                if (status === 'Present') radio.checked = true;
            });
            document.querySelectorAll('input[type="radio"][value="Absent"]').forEach(radio => {
                if (status === 'Absent') radio.checked = true;
            });
            document.querySelectorAll('input[type="radio"][value="Late"]').forEach(radio => {
                if (status === 'Late') radio.checked = true;
            });
        }
    </script>
</body>
</html>
