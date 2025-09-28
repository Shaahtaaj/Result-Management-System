<?php
session_start();
include '../config/database.php';
check_login('teacher');

$teacher_stmt = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$teacher_stmt->bind_param("i", $_SESSION['user_id']);
$teacher_stmt->execute();
$teacher = $teacher_stmt->get_result()->fetch_assoc();

if (!$teacher) {
    header('Location: login.php');
    exit();
}

$class_query = "
    SELECT DISTINCT c.id, c.class_name, c.section
    FROM teacher_classes tc
    JOIN classes c ON tc.class_id = c.id
    WHERE tc.teacher_id = ?
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

$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($selected_class && !isset($classes[$selected_class])) {
    $selected_class = 0;
}

$search_name = isset($_GET['name']) ? trim($_GET['name']) : '';
$search_roll = isset($_GET['roll_no']) ? trim($_GET['roll_no']) : '';

$students = [];
if ($selected_class) {
    $query = "
        SELECT s.*, c.class_name, c.section
        FROM students s
        JOIN classes c ON s.class_id = c.id
        WHERE s.class_id = ?
    ";
    $types = 'i';
    $params = [$selected_class];

    if ($search_name !== '') {
        $query .= " AND s.name LIKE ?";
        $types .= 's';
        $params[] = '%' . $search_name . '%';
    }
    if ($search_roll !== '') {
        $query .= " AND s.roll_no LIKE ?";
        $types .= 's';
        $params[] = '%' . $search_roll . '%';
    }

    $query .= ' ORDER BY s.roll_no';
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$class_stats = [];
if ($selected_class) {
    $stats_stmt = $conn->prepare('
        SELECT 
            COUNT(*) as total_students,
            COUNT(CASE WHEN gender = "Male" THEN 1 END) as male_students,
            COUNT(CASE WHEN gender = "Female" THEN 1 END) as female_students,
            COUNT(CASE WHEN gender = "Other" THEN 1 END) as other_students,
            COUNT(CASE WHEN DATE(admission_date) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 1 END) as recent_admissions
        FROM students
        WHERE class_id = ?
    ');
    $stats_stmt->bind_param('i', $selected_class);
    $stats_stmt->execute();
    $class_stats = $stats_stmt->get_result()->fetch_assoc();
    $stats_stmt->close();
}

$recent_attendance = [];
if ($selected_class) {
    $attendance_stmt = $conn->prepare('
        SELECT s.name, s.roll_no, a.date, a.status
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE s.class_id = ?
        ORDER BY a.date DESC
        LIMIT 10
    ');
    $attendance_stmt->bind_param('i', $selected_class);
    $attendance_stmt->execute();
    $recent_attendance = $attendance_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $attendance_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Teacher Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .students-table { width: 100%; border-collapse: collapse; }
        .students-table th, .students-table td { padding: 12px; border-bottom: 1px solid #e9ecef; }
        .students-table th { background: #f8f9fa; text-align: left; }
        .badge { padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 0.85rem; }
        .badge-info { background: #17a2b8; color: #fff; }
        .badge-success { background: #28a745; color: #fff; }
        .badge-warning { background: #ffc107; color: #212529; }
        .profile-card { display: flex; align-items: center; gap: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px; margin-bottom: 15px; }
        .profile-card img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid #667eea; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        .stat-card h3 { margin: 0; font-size: 1.8rem; color: #2c3e50; }
        .stat-card p { margin: 5px 0 0; color: #7f8c8d; }
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-late { background: #fff3cd; color: #856404; }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="fas fa-graduation-cap"></i> My Students</h1>
                    <p>Review student profiles and academic information</p>
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
                <li><a href="students.php" class="active"><i class="fas fa-graduation-cap"></i> My Students</a></li>
                <li><a href="reports.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
            </ul>
        </nav>

        <div class="form-container">
            <h3><i class="fas fa-filter"></i> Filter Students</h3>
            <form method="GET">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="class_id">Class</label>
                        <select name="class_id" id="class_id">
                            <option value="">Choose Class</option>
                            <?php foreach ($classes as $class_id => $info): ?>
                                <option value="<?= $class_id ?>" <?= $selected_class === $class_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($info['class_name'] . ' - ' . $info['section']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="name">Student Name</label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($search_name) ?>" placeholder="Search by name">
                    </div>
                    <div class="form-group">
                        <label for="roll_no">Roll Number</label>
                        <input type="text" name="roll_no" id="roll_no" value="<?= htmlspecialchars($search_roll) ?>" placeholder="Search by roll no">
                    </div>
                </div>
                <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filters</button>
                    <a href="students.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </form>
        </div>

        <?php if ($selected_class && isset($classes[$selected_class])): ?>
            <div class="form-container">
                <div class="profile-card">
                    <div>
                        <h3 style="margin: 0;">
                            <?= htmlspecialchars($classes[$selected_class]['class_name'] . ' - ' . $classes[$selected_class]['section']) ?>
                        </h3>
                        <p style="margin: 5px 0; color: #7f8c8d;">Assigned Class</p>
                        <p style="margin: 0;">
                            <strong>Total Students:</strong> <?= $class_stats['total_students'] ?? 0 ?>
                        </p>
                    </div>
                </div>

                <?php if (!empty($class_stats)): ?>
                    <div class="stat-grid">
                        <div class="stat-card">
                            <h3><?= $class_stats['total_students'] ?? 0 ?></h3>
                            <p>Total Students</p>
                        </div>
                        <div class="stat-card">
                            <h3><?= $class_stats['male_students'] ?? 0 ?></h3>
                            <p>Male Students</p>
                        </div>
                        <div class="stat-card">
                            <h3><?= $class_stats['female_students'] ?? 0 ?></h3>
                            <p>Female Students</p>
                        </div>
                        <div class="stat-card">
                            <h3><?= $class_stats['recent_admissions'] ?? 0 ?></h3>
                            <p>New Admissions (Last 12 months)</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($students)): ?>
            <div class="form-container">
                <h3><i class="fas fa-users"></i> Students List</h3>
                <div class="table-container">
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Date of Birth</th>
                                <th>Parent Contact</th>
                                <th>Admission Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['roll_no']) ?></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td>
                                        <?php if ($student['gender'] === 'Male'): ?>
                                            <span class="badge badge-info">Male</span>
                                        <?php elseif ($student['gender'] === 'Female'): ?>
                                            <span class="badge badge-warning">Female</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Other</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $student['date_of_birth'] ? date('M j, Y', strtotime($student['date_of_birth'])) : '—' ?></td>
                                    <td><?= htmlspecialchars($student['parent_contact'] ?: '—') ?></td>
                                    <td><?= $student['admission_date'] ? date('M j, Y', strtotime($student['admission_date'])) : '—' ?></td>
                                    <td>
                                        <a href="../student/profile.php?student_id=<?= $student['id'] ?>" class="btn btn-secondary btn-sm" target="_blank">
                                            <i class="fas fa-eye"></i> View Profile
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($selected_class): ?>
            <div class="form-container">
                <p style="margin: 0; color: #7f8c8d;">No students found matching the selected criteria.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($recent_attendance)): ?>
            <div class="form-container" style="margin-top: 30px;">
                <h3><i class="fas fa-history"></i> Recent Attendance Activity</h3>
                <div class="table-container">
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_attendance as $record): ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($record['date'])) ?></td>
                                    <td><?= htmlspecialchars($record['roll_no']) ?></td>
                                    <td><?= htmlspecialchars($record['name']) ?></td>
                                    <td>
                                        <span class="badge status-<?= strtolower($record['status']) ?>">
                                            <?= htmlspecialchars($record['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
