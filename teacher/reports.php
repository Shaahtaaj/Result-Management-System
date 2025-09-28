<?php
session_start();
include '../config/database.php';
check_login('teacher');

// Fetch teacher profile
$teacher_stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$teacher_stmt->bind_param("i", $_SESSION['user_id']);
$teacher_stmt->execute();
$teacher = $teacher_stmt->get_result()->fetch_assoc();

if (!$teacher) {
    header('Location: login.php');
    exit();
}

// Fetch classes assigned to this teacher (optionally class teacher flag)
$class_query = "
    SELECT DISTINCT c.id, c.class_name, c.section,
           MAX(CASE WHEN tc.is_class_teacher = 1 THEN 1 ELSE 0 END) AS is_class_teacher
    FROM teacher_classes tc
    JOIN classes c ON tc.class_id = c.id
    WHERE tc.teacher_id = ?
    GROUP BY c.id, c.class_name, c.section
    ORDER BY c.class_name, c.section
";
$class_stmt = $conn->prepare($class_query);
$class_stmt->bind_param('i', $teacher['id']);
$class_stmt->execute();
$class_result = $class_stmt->get_result();

$classes = [];
while ($row = $class_result->fetch_assoc()) {
    $classes[(int)$row['id']] = $row;
}

$selected_class = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$report_data = [];
$class_info = null;

if ($selected_class && isset($classes[$selected_class])) {
    // Get class info
    $class_info = $classes[$selected_class];

    // Get subjects for this class that the teacher teaches
    $subjects_stmt = $conn->prepare('
        SELECT DISTINCT sub.id, sub.subject_name, sub.total_marks
        FROM teacher_classes tc
        JOIN subjects sub ON tc.subject_id = sub.id
        WHERE tc.teacher_id = ? AND tc.class_id = ?
        ORDER BY sub.subject_name
    ');
    $subjects_stmt->bind_param('ii', $teacher['id'], $selected_class);
    $subjects_stmt->execute();
    $subjects_result = $subjects_stmt->get_result();

    $subjects = [];
    while ($subject = $subjects_result->fetch_assoc()) {
        $subjects[(int)$subject['id']] = $subject;
    }
    $subjects_stmt->close();

    // Get students and their attendance stats
    $students_stmt = $conn->prepare('
        SELECT s.id, s.name, s.roll_no
        FROM students s
        WHERE s.class_id = ?
        ORDER BY s.roll_no
    ');
    $students_stmt->bind_param('i', $selected_class);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();

    $students = [];
    while ($student = $students_result->fetch_assoc()) {
        $students[(int)$student['id']] = $student;
        $report_data[(int)$student['id']] = [
            'student' => $student,
            'attendance' => [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'total' => 0,
            ],
            'results' => [],
        ];
    }
    $students_stmt->close();

    if (!empty($students)) {
        // Attendance stats for current month
        $attendance_stmt = $conn->prepare('
            SELECT student_id,
                   COUNT(*) AS total,
                   COUNT(CASE WHEN status = "Present" THEN 1 END) AS present,
                   COUNT(CASE WHEN status = "Absent" THEN 1 END) AS absent,
                   COUNT(CASE WHEN status = "Late" THEN 1 END) AS late
            FROM attendance
            WHERE student_id IN (' . implode(',', array_keys($students)) . " )
              AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
            GROUP BY student_id
        ");
        $attendance_stmt->execute();
        $attendance_result = $attendance_stmt->get_result();
        while ($row = $attendance_result->fetch_assoc()) {
            $sid = (int)$row['student_id'];
            $report_data[$sid]['attendance'] = [
                'present' => (int)$row['present'],
                'absent' => (int)$row['absent'],
                'late' => (int)$row['late'],
                'total' => (int)$row['total'],
            ];
        }
        $attendance_stmt->close();

        // Latest results for subjects taught by teacher (latest exam per subject)
        if (!empty($subjects)) {
            $subject_ids = implode(',', array_keys($subjects));
            $results_stmt = $conn->prepare('
                SELECT r.student_id, r.subject_id, r.marks_obtained, r.total_marks,
                       e.exam_name, e.exam_date
                FROM results r
                JOIN exams e ON r.exam_id = e.id
                WHERE r.student_id IN (' . implode(',', array_keys($students)) . ")
                  AND r.subject_id IN ($subject_ids)
                ORDER BY e.exam_date DESC
            ");
            $results_stmt->execute();
            $results_result = $results_stmt->get_result();
            while ($row = $results_result->fetch_assoc()) {
                $sid = (int)$row['student_id'];
                $subid = (int)$row['subject_id'];
                if (!isset($report_data[$sid]['results'][$subid])) {
                    $report_data[$sid]['results'][$subid] = $row;
                }
            }
            $results_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Teacher Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .report-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
        .summary-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .summary-card h3 { margin: 0 0 10px; color: #2c3e50; }
        .summary-card p { margin: 0; color: #7f8c8d; }
        .table-container { overflow-x: auto; margin-top: 25px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #e9ecef; text-align: left; }
        th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; display: inline-block; }
        .badge-present { background: #d4edda; color: #155724; }
        .badge-absent { background: #f8d7da; color: #721c24; }
        .badge-late { background: #fff3cd; color: #856404; }
        .badge-info { background: #17a2b8; color: #fff; }
        .badge-success { background: #28a745; color: #fff; }
        .badge-warning { background: #ffc107; color: #212529; }
        .print-button { margin-top: 20px; text-align: right; }
        .alert { padding: 15px; border-radius: 6px; margin: 20px 0; }
        .alert-info { background: #e9f7ff; color: #0c5460; border-left: 4px solid #17a2b8; }
        @media print {
            .btn, .dashboard-nav, .dashboard-header form, .print-button { display: none !important; }
            body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
            .summary-card { box-shadow: none; border: 1px solid #ced4da; }
            th, td { padding: 6px 8px; }
        }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="fas fa-file-pdf"></i> Reports</h1>
                    <p>Review class performance and attendance summaries</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>

        <nav class="dashboard-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Mark Attendance</a></li>
                <li><a href="results.php"><i class="fas fa-chart-bar"></i> Enter Results</a></li>
                <li><a href="students.php"><i class="fas a-graduation-cap"></i> My Students</a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-file-pdf"></i> Reports</a></li>
            </ul>
        </nav>

        <div class="form-container">
            <h3><i class="fas fa-filter"></i> Select Class</h3>
            <form method="POST">
                <div class="form-group" style="max-width: 320px;">
                    <label for="class_id">Class</label>
                    <select name="class_id" id="class_id" required>
                        <option value="">Choose Class</option>
                        <?php foreach ($classes as $class_id => $info): ?>
                            <option value="<?= $class_id ?>" <?= $class_id === $selected_class ? 'selected' : '' ?>>
                                <?= htmlspecialchars($info['class_name'] . ' - ' . $info['section']) ?>
                                <?= $info['is_class_teacher'] ? ' (Class Teacher)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> View Report</button>
            </form>
        </div>

        <?php if ($selected_class && isset($classes[$selected_class])): ?>
            <div class="form-container">
                <h3><i class="fas fa-clipboard-list"></i> Class Summary</h3>
                <?php if (empty($report_data)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No student data found for this class.
                    </div>
                <?php else: ?>
                    <div class="report-summary">
                        <div class="summary-card">
                            <h3><?= count($report_data) ?></h3>
                            <p>Total Students</p>
                        </div>
                        <div class="summary-card">
                            <?php
                            $total_sessions = 0;
                            $present_sessions = 0;
                            foreach ($report_data as $data) {
                                $total_sessions += $data['attendance']['total'];
                                $present_sessions += $data['attendance']['present'] + $data['attendance']['late'];
                            }
                            $attendance_percentage = $total_sessions > 0 ? round(($present_sessions / $total_sessions) * 100, 1) : 0;
                            ?>
                            <h3><?= $attendance_percentage ?>%</h3>
                            <p>Average Attendance (current month)</p>
                        </div>
                        <div class="summary-card">
                            <?php
                            $subjects_taught = count($subjects ?? []);
                            ?>
                            <h3><?= $subjects_taught ?></h3>
                            <p>Subjects Covered</p>
                        </div>
                    </div>

                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Attendance (Present / Total)</th>
                                    <th>Attendance %</th>
                                    <?php if (!empty($subjects)): ?>
                                        <?php foreach ($subjects as $subject): ?>
                                            <th><?= htmlspecialchars($subject['subject_name']) ?> (Latest Marks)</th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $sid => $data): ?>
                                    <?php
                                    $attendance = $data['attendance'];
                                    $attendance_percent = $attendance['total'] > 0
                                        ? round((($attendance['present'] + $attendance['late']) / $attendance['total']) * 100, 1)
                                        : 0;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($data['student']['roll_no']) ?></td>
                                        <td><?= htmlspecialchars($data['student']['name']) ?></td>
                                        <td>
                                            <span class="status-badge badge-present">Present: <?= $attendance['present'] ?></span>
                                            <span class="status-badge badge-late" style="margin-left: 5px;">Late: <?= $attendance['late'] ?></span>
                                            <span class="status-badge badge-absent" style="margin-left: 5px;">Absent: <?= $attendance['absent'] ?></span>
                                        </td>
                                        <td><strong><?= $attendance_percent ?>%</strong></td>
                                        <?php if (!empty($subjects)): ?>
                                            <?php foreach ($subjects as $subject_id => $subject): ?>
                                                <td>
                                                    <?php if (isset($data['results'][$subject_id])): ?>
                                                        <?php $result = $data['results'][$subject_id]; ?>
                                                        <span class="status-badge badge-success">
                                                            <?= $result['marks_obtained'] ?>/<?= $result['total_marks'] ?>
                                                        </span>
                                                        <div style="font-size: 0.8rem; color: #7f8c8d;">
                                                            <?= htmlspecialchars($result['exam_name']) ?><br>
                                                            <?= date('M j, Y', strtotime($result['exam_date'])) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="status-badge badge-warning">No Marks</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="print-button">
                        <button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i> Print Report</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
