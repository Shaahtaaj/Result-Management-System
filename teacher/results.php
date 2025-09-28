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

// Fetch class & subject assignments for this teacher
$assignments_query = "
    SELECT tc.class_id, tc.subject_id, c.class_name, c.section, s.subject_name, s.total_marks
    FROM teacher_classes tc
    JOIN classes c ON tc.class_id = c.id
    JOIN subjects s ON tc.subject_id = s.id
    WHERE tc.teacher_id = ?
    ORDER BY c.class_name, c.section, s.subject_name
";
$assignments_stmt = $conn->prepare($assignments_query);
$assignments_stmt->bind_param('i', $teacher['id']);
$assignments_stmt->execute();
$assignments_result = $assignments_stmt->get_result();

$class_subjects = [];
$class_details = [];

while ($row = $assignments_result->fetch_assoc()) {
    $class_id = (int)$row['class_id'];
    $subject_id = (int)$row['subject_id'];
    if (!isset($class_subjects[$class_id])) {
        $class_subjects[$class_id] = [];
        $class_details[$class_id] = [
            'class_name' => $row['class_name'],
            'section'    => $row['section'],
        ];
    }
    $class_subjects[$class_id][$subject_id] = [
        'subject_name' => $row['subject_name'],
        'total_marks'  => $row['total_marks'] ?? 100,
    ];
}

$selected_class  = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selected_subject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$selected_exam   = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$message = '';
$error = '';

// Handle result submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_results'])) {
    $selected_class = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $selected_subject = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $selected_exam = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;

    if (!isset($class_subjects[$selected_class]) || !isset($class_subjects[$selected_class][$selected_subject])) {
        $error = 'Invalid class or subject selection.';
    } elseif ($selected_exam <= 0) {
        $error = 'Please select an exam to save results.';
    } else {
        $marks_input = $_POST['marks'] ?? [];
        $success = 0;
        $failed = 0;
        $total_marks = $class_subjects[$selected_class][$selected_subject]['total_marks'];

        foreach ($marks_input as $student_id => $marks_value) {
            $student_id = (int)$student_id;
            $marks_value = trim($marks_value);
            if ($marks_value === '' || !is_numeric($marks_value)) {
                continue;
            }
            $marks_value = (float)$marks_value;
            if ($marks_value < 0) {
                $marks_value = 0;
            }
            if ($marks_value > $total_marks) {
                $marks_value = $total_marks;
            }

            // Ensure student belongs to class
            $check_student_stmt = $conn->prepare('SELECT id FROM students WHERE id = ? AND class_id = ?');
            $check_student_stmt->bind_param('ii', $student_id, $selected_class);
            $check_student_stmt->execute();
            $student_exists = $check_student_stmt->get_result()->num_rows > 0;
            $check_student_stmt->close();

            if (!$student_exists) {
                continue;
            }

            // Upsert result
            $select_stmt = $conn->prepare('SELECT id FROM results WHERE student_id = ? AND subject_id = ? AND exam_id = ?');
            $select_stmt->bind_param('iii', $student_id, $selected_subject, $selected_exam);
            $select_stmt->execute();
            $existing = $select_stmt->get_result()->fetch_assoc();
            $select_stmt->close();

            if ($existing) {
                $update_stmt = $conn->prepare('UPDATE results SET marks_obtained = ?, total_marks = ? WHERE id = ?');
                $update_stmt->bind_param('dii', $marks_value, $total_marks, $existing['id']);
                $success += $update_stmt->execute() ? 1 : 0;
                $failed += $update_stmt->errno ? 1 : 0;
                $update_stmt->close();
            } else {
                $insert_stmt = $conn->prepare('INSERT INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks) VALUES (?, ?, ?, ?, ?)');
                $insert_stmt->bind_param('iiidd', $student_id, $selected_subject, $selected_exam, $marks_value, $total_marks);
                $success += $insert_stmt->execute() ? 1 : 0;
                $failed += $insert_stmt->errno ? 1 : 0;
                $insert_stmt->close();
            }
        }

        if ($success > 0) {
            $message = $success . ' result(s) saved successfully.';
        }
        if ($failed > 0) {
            $error = $failed . ' result(s) could not be saved.';
        }
    }

    $redirect_url = 'results.php?class_id=' . $selected_class . '&subject_id=' . $selected_subject . '&exam_id=' . $selected_exam;
    if ($message) {
        $redirect_url .= '&message=' . urlencode($message);
    }
    if ($error) {
        $redirect_url .= '&error=' . urlencode($error);
    }
    header('Location: ' . $redirect_url);
    exit();
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// If selection invalid, reset
if (!isset($class_subjects[$selected_class])) {
    $selected_class = 0;
    $selected_subject = 0;
    $selected_exam = 0;
}
if ($selected_class && !isset($class_subjects[$selected_class][$selected_subject])) {
    $selected_subject = 0;
    $selected_exam = 0;
}

$students = [];
$marks_existing = [];
$subject_total_marks = 100;
if ($selected_class && $selected_subject) {
    $subject_total_marks = $class_subjects[$selected_class][$selected_subject]['total_marks'];

    // Fetch students for the class
    $students_stmt = $conn->prepare('SELECT id, name, roll_no FROM students WHERE class_id = ? ORDER BY roll_no');
    $students_stmt->bind_param('i', $selected_class);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    while ($row = $students_result->fetch_assoc()) {
        $students[$row['id']] = $row;
    }
    $students_stmt->close();

    if ($selected_exam && !empty($students)) {
        $marks_stmt = $conn->prepare('SELECT student_id, marks_obtained FROM results WHERE exam_id = ? AND subject_id = ?');
        $marks_stmt->bind_param('ii', $selected_exam, $selected_subject);
        $marks_stmt->execute();
        $marks_res = $marks_stmt->get_result();
        while ($row = $marks_res->fetch_assoc()) {
            $marks_existing[(int)$row['student_id']] = $row['marks_obtained'];
        }
        $marks_stmt->close();
    }
}

// Fetch exams for selected class
$exams = [];
if ($selected_class) {
    $exams_stmt = $conn->prepare('SELECT id, exam_name, exam_date FROM exams WHERE class_id = ? ORDER BY exam_date DESC');
    $exams_stmt->bind_param('i', $selected_class);
    $exams_stmt->execute();
    $exams_result = $exams_stmt->get_result();
    while ($row = $exams_result->fetch_assoc()) {
        $exams[] = $row;
    }
    $exams_stmt->close();
    if ($selected_exam && !array_filter($exams, fn($exam) => (int)$exam['id'] === $selected_exam)) {
        $selected_exam = 0;
    }
}

// Recent results submitted by this teacher
$recent_results_stmt = $conn->prepare('
    SELECT r.created_at, st.name as student_name, st.roll_no, sub.subject_name, r.marks_obtained, r.total_marks, e.exam_name
    FROM results r
    JOIN students st ON r.student_id = st.id
    JOIN subjects sub ON r.subject_id = sub.id
    JOIN exams e ON r.exam_id = e.id
    JOIN teacher_classes tc ON tc.class_id = st.class_id AND tc.subject_id = sub.id
    WHERE tc.teacher_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
');
$recent_results_stmt->bind_param('i', $teacher['id']);
$recent_results_stmt->execute();
$recent_results = $recent_results_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Results - Teacher Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .alert { padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid; }
        .alert-success { background: #d4edda; color: #155724; border-color: #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-color: #dc3545; }
        .results-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .results-table th, .results-table td { padding: 10px; border-bottom: 1px solid #e9ecef; }
        .results-table th { background: #f8f9fa; text-align: left; }
        .table-container { overflow-x: auto; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; }
        .badge-primary { background: #667eea; color: #fff; }
        .badge-success { background: #28a745; color: #fff; }
        .badge-secondary { background: #6c757d; color: #fff; }
        .recent-table tr:hover { background: #f8f9fa; }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="fas fa-chart-bar"></i> Enter Results</h1>
                    <p>Record and manage exam scores for your students</p>
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
                <li><a href="results.php" class="active"><i class="fas fa-chart-bar"></i> Enter Results</a></li>
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
            <h3><i class="fas fa-filter"></i> Select Class, Subject & Exam</h3>
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <label for="class_id">Class</label>
                    <select name="class_id" id="class_id" onchange="this.form.submit()">
                        <option value="">Choose Class</option>
                        <?php foreach ($class_details as $class_id => $info): ?>
                            <option value="<?= $class_id ?>" <?= $class_id === $selected_class ? 'selected' : '' ?>>
                                <?= htmlspecialchars($info['class_name'] . ' - ' . $info['section']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subject_id">Subject</label>
                    <select name="subject_id" id="subject_id" onchange="this.form.submit()">
                        <option value="">Choose Subject</option>
                        <?php if ($selected_class && isset($class_subjects[$selected_class])): ?>
                            <?php foreach ($class_subjects[$selected_class] as $subject_id => $subject): ?>
                                <option value="<?= $subject_id ?>" <?= $subject_id === $selected_subject ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="exam_id">Exam</label>
                    <select name="exam_id" id="exam_id" onchange="this.form.submit()">
                        <option value="">Choose Exam</option>
                        <?php foreach ($exams as $exam): ?>
                            <option value="<?= $exam['id'] ?>" <?= (int)$exam['id'] === $selected_exam ? 'selected' : '' ?>>
                                <?= htmlspecialchars($exam['exam_name']) ?> (<?= date('M j, Y', strtotime($exam['exam_date'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($selected_class && $selected_subject && $selected_exam && !empty($students)): ?>
            <div class="form-container">
                <h3>
                    <i class="fas fa-pen"></i> Enter Marks for
                    <?= htmlspecialchars($class_details[$selected_class]['class_name'] . ' - ' . $class_details[$selected_class]['section']) ?>
                    (<?= htmlspecialchars($class_subjects[$selected_class][$selected_subject]['subject_name']) ?>)
                </h3>
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    Total Marks: <strong><?= $subject_total_marks ?></strong> | Exam Date:
                    <?php
                    $selected_exam_data = array_values(array_filter($exams, fn($exam) => (int)$exam['id'] === $selected_exam));
                    echo $selected_exam_data ? date('F j, Y', strtotime($selected_exam_data[0]['exam_date'])) : 'N/A';
                    ?>
                </p>
                <form method="POST">
                    <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                    <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
                    <input type="hidden" name="exam_id" value="<?= $selected_exam ?>">
                    <div class="table-container">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th style="width: 180px;">Marks Obtained</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student_id => $student): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student['roll_no']) ?></td>
                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                        <td>
                                            <input
                                                type="number"
                                                name="marks[<?= $student_id ?>]"
                                                value="<?= isset($marks_existing[$student_id]) ? htmlspecialchars($marks_existing[$student_id]) : '' ?>"
                                                min="0"
                                                max="<?= $subject_total_marks ?>"
                                                step="0.5"
                                                style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;"
                                            >
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" name="save_results" class="btn btn-primary"><i class="fas fa-save"></i> Save Results</button>
                    </div>
                </form>
            </div>
        <?php elseif ($selected_class && $selected_subject && $selected_exam && empty($students)): ?>
            <div class="form-container">
                <p style="color: #7f8c8d;">No students found in the selected class.</p>
            </div>
        <?php elseif ($selected_class && $selected_subject && empty($exams)): ?>
            <div class="form-container">
                <div class="alert alert-error" style="margin: 0;">
                    <i class="fas fa-exclamation-circle"></i> No exams found for the selected class. Please ask the administrator to create exams first.
                </div>
            </div>
        <?php endif; ?>

        <div class="form-container" style="margin-top: 30px;">
            <h3><i class="fas fa-history"></i> Recent Results Submitted</h3>
            <div class="table-container">
                <table class="results-table recent-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Roll No</th>
                            <th>Subject</th>
                            <th>Exam</th>
                            <th>Marks</th>
                            <th>Submitted On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_results->num_rows > 0): ?>
                            <?php while ($row = $recent_results->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                    <td><?= htmlspecialchars($row['roll_no']) ?></td>
                                    <td><span class="badge badge-primary"><?= htmlspecialchars($row['subject_name']) ?></span></td>
                                    <td><?= htmlspecialchars($row['exam_name']) ?></td>
                                    <td><span class="badge badge-success"><?= htmlspecialchars($row['marks_obtained']) ?>/<?= htmlspecialchars($row['total_marks']) ?></span></td>
                                    <td><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No recent results found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
