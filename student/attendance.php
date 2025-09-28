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

// Get attendance records with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Filter parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters
$where_conditions = ["a.student_id = ?"];
$params = [$student['id']];
$param_types = "i";

if (!empty($month)) {
    $where_conditions[] = "DATE_FORMAT(a.date, '%Y-%m') = ?";
    $params[] = $month;
    $param_types .= "s";
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get attendance records
$attendance_query = "SELECT a.date, a.status, a.created_at, u.username as marked_by_user
                     FROM attendance a
                     LEFT JOIN users u ON a.marked_by = u.id
                     WHERE $where_clause
                     ORDER BY a.date DESC
                     LIMIT $records_per_page OFFSET $offset";

$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param($param_types, ...$params);
$attendance_stmt->execute();
$attendance_records = $attendance_stmt->get_result();

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM attendance a WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($param_types, ...$params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get attendance statistics
$stats_query = "SELECT 
                  COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_days,
                  COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_days,
                  COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_days,
                  COUNT(*) as total_days
                FROM attendance 
                WHERE student_id = ?";

if (!empty($month)) {
    $stats_query .= " AND DATE_FORMAT(date, '%Y-%m') = ?";
}

$stats_stmt = $conn->prepare($stats_query);
if (!empty($month)) {
    $stats_stmt->bind_param("is", $student['id'], $month);
} else {
    $stats_stmt->bind_param("i", $student['id']);
}
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$attendance_percentage = $stats['total_days'] > 0 ? 
    round(($stats['present_days'] + $stats['late_days']) / $stats['total_days'] * 100, 2) : 0;

// Get monthly attendance summary for chart
$monthly_query = "SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
                    COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent,
                    COUNT(CASE WHEN status = 'Late' THEN 1 END) as late,
                    COUNT(*) as total
                  FROM attendance 
                  WHERE student_id = ?
                  GROUP BY DATE_FORMAT(date, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 6";
$monthly_stmt = $conn->prepare($monthly_query);
$monthly_stmt->bind_param("i", $student['id']);
$monthly_stmt->execute();
$monthly_data = $monthly_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Student Portal</title>
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
                    <a href="results.php"><i class="fas fa-chart-line"></i> Results</a>
                    <a href="attendance.php" class="active"><i class="fas fa-calendar-check"></i> Attendance</a>
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
                <h1><i class="fas fa-calendar-check"></i> My Attendance</h1>
                <p>View your attendance records and statistics</p>
            </div>

            <!-- Student Info and Stats -->
            <div class="attendance-overview">
                <div class="card student-info-card">
                    <div class="card-body">
                        <div class="student-details">
                            <h3><?= htmlspecialchars($student['name']) ?></h3>
                            <p><strong>Roll Number:</strong> <?= htmlspecialchars($student['roll_no']) ?></p>
                            <p><strong>Class:</strong> <?= htmlspecialchars($student['class_name'] . ' - ' . $student['section']) ?></p>
                        </div>
                        <div class="attendance-stats">
                            <div class="stat-circle">
                                <div class="circle-progress" data-percentage="<?= $attendance_percentage ?>">
                                    <span><?= $attendance_percentage ?>%</span>
                                </div>
                                <p>Overall Attendance</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stats-cards">
                    <div class="stat-card present">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['present_days'] ?></h3>
                            <p>Present Days</p>
                        </div>
                    </div>
                    
                    <div class="stat-card absent">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['absent_days'] ?></h3>
                            <p>Absent Days</p>
                        </div>
                    </div>
                    
                    <div class="stat-card late">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['late_days'] ?></h3>
                            <p>Late Days</p>
                        </div>
                    </div>
                    
                    <div class="stat-card total">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['total_days'] ?></h3>
                            <p>Total Days</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Summary -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Monthly Summary</h3>
                </div>
                <div class="card-body">
                    <div class="monthly-chart">
                        <?php while ($month_data = $monthly_data->fetch_assoc()): ?>
                            <?php 
                            $month_percentage = $month_data['total'] > 0 ? 
                                round(($month_data['present'] + $month_data['late']) / $month_data['total'] * 100, 1) : 0;
                            ?>
                            <div class="month-bar">
                                <div class="bar-container">
                                    <div class="bar present" style="height: <?= ($month_data['present'] / max($month_data['total'], 1)) * 100 ?>%"></div>
                                    <div class="bar late" style="height: <?= ($month_data['late'] / max($month_data['total'], 1)) * 100 ?>%"></div>
                                    <div class="bar absent" style="height: <?= ($month_data['absent'] / max($month_data['total'], 1)) * 100 ?>%"></div>
                                </div>
                                <div class="month-label">
                                    <strong><?= date('M Y', strtotime($month_data['month'] . '-01')) ?></strong>
                                    <small><?= $month_percentage ?>%</small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-filter"></i> Filter Attendance Records</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <label for="month">Month:</label>
                            <input type="month" name="month" id="month" value="<?= htmlspecialchars($month) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select name="status" id="status">
                                <option value="">All Status</option>
                                <option value="Present" <?= $status_filter === 'Present' ? 'selected' : '' ?>>Present</option>
                                <option value="Absent" <?= $status_filter === 'Absent' ? 'selected' : '' ?>>Absent</option>
                                <option value="Late" <?= $status_filter === 'Late' ? 'selected' : '' ?>>Late</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="attendance.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Attendance Records -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Attendance Records</h3>
                    <div class="card-actions">
                        <span class="record-count">
                            Showing <?= min($offset + 1, $total_records) ?>-<?= min($offset + $records_per_page, $total_records) ?> 
                            of <?= $total_records ?> records
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($attendance_records->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Status</th>
                                        <th>Marked By</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = $attendance_records->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('M j, Y', strtotime($record['date'])) ?></td>
                                            <td><?= date('l', strtotime($record['date'])) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= strtolower($record['status']) ?>">
                                                    <?php if ($record['status'] === 'Present'): ?>
                                                        <i class="fas fa-check"></i>
                                                    <?php elseif ($record['status'] === 'Absent'): ?>
                                                        <i class="fas fa-times"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-clock"></i>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($record['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($record['marked_by_user'] ?? 'System') ?></td>
                                            <td><?= date('g:i A', strtotime($record['created_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>&month=<?= urlencode($month) ?>&status=<?= urlencode($status_filter) ?>" class="btn btn-sm">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?= $i ?>&month=<?= urlencode($month) ?>&status=<?= urlencode($status_filter) ?>" 
                                       class="btn btn-sm <?= $i === $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?>&month=<?= urlencode($month) ?>&status=<?= urlencode($status_filter) ?>" class="btn btn-sm">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-calendar-times fa-3x"></i>
                            <h3>No Attendance Records</h3>
                            <p>No attendance records found for the selected criteria.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
        
        .attendance-overview {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 20px;
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
        
        .circle-progress {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: conic-gradient(#27ae60 0deg, #27ae60 calc(var(--percentage) * 3.6deg), #ecf0f1 calc(var(--percentage) * 3.6deg));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .circle-progress::before {
            content: '';
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            position: absolute;
        }
        
        .circle-progress span {
            position: relative;
            z-index: 1;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card.present { border-left: 4px solid #27ae60; }
        .stat-card.absent { border-left: 4px solid #e74c3c; }
        .stat-card.late { border-left: 4px solid #f39c12; }
        .stat-card.total { border-left: 4px solid #3498db; }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .stat-card.present .stat-icon { background: #27ae60; }
        .stat-card.absent .stat-icon { background: #e74c3c; }
        .stat-card.late .stat-icon { background: #f39c12; }
        .stat-card.total .stat-icon { background: #3498db; }
        
        .stat-info h3 {
            margin: 0;
            font-size: 1.8rem;
            color: #2c3e50;
        }
        
        .stat-info p {
            margin: 0;
            color: #666;
        }
        
        .monthly-chart {
            display: flex;
            gap: 20px;
            justify-content: space-around;
            align-items: end;
            height: 200px;
            padding: 20px 0;
        }
        
        .month-bar {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .bar-container {
            width: 40px;
            height: 150px;
            background: #ecf0f1;
            border-radius: 4px;
            position: relative;
            display: flex;
            flex-direction: column-reverse;
        }
        
        .bar {
            width: 100%;
            border-radius: 2px;
        }
        
        .bar.present { background: #27ae60; }
        .bar.late { background: #f39c12; }
        .bar.absent { background: #e74c3c; }
        
        .month-label {
            text-align: center;
        }
        
        .month-label strong {
            display: block;
            color: #2c3e50;
        }
        
        .month-label small {
            color: #666;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9rem;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .attendance-table th,
        .attendance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .attendance-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-present {
            background: #d4edda;
            color: #155724;
        }
        
        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-late {
            background: #fff3cd;
            color: #856404;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination .btn.active {
            background: #2c3e50;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-data i {
            margin-bottom: 20px;
        }
        
        .record-count {
            color: #666;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .attendance-overview {
                grid-template-columns: 1fr;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .monthly-chart {
                overflow-x: auto;
            }
        }
    </style>

    <script>
        // Set circle progress percentage
        document.addEventListener('DOMContentLoaded', function() {
            const circles = document.querySelectorAll('.circle-progress');
            circles.forEach(circle => {
                const percentage = circle.dataset.percentage;
                circle.style.setProperty('--percentage', percentage);
            });
        });
    </script>
</body>
</html>
