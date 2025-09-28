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

// Get attendance statistics
$attendance_stats_query = "SELECT 
                            COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_days,
                            COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_days,
                            COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_days,
                            COUNT(*) as total_days
                          FROM attendance 
                          WHERE student_id = ?";
$attendance_stmt = $conn->prepare($attendance_stats_query);
$attendance_stmt->bind_param("i", $student['id']);
$attendance_stmt->execute();
$attendance_stats = $attendance_stmt->get_result()->fetch_assoc();

$attendance_percentage = $attendance_stats['total_days'] > 0 ? 
    round(($attendance_stats['present_days'] + $attendance_stats['late_days']) / $attendance_stats['total_days'] * 100, 2) : 0;

// Get recent results
$recent_results_query = "SELECT 
                          e.exam_name,
                          e.exam_type,
                          e.exam_date,
                          COUNT(r.id) as subjects_count,
                          AVG(ROUND((r.marks_obtained / r.total_marks) * 100, 2)) as average_percentage
                        FROM results r
                        JOIN exams e ON r.exam_id = e.id
                        WHERE r.student_id = ?
                        GROUP BY e.id
                        ORDER BY e.exam_date DESC
                        LIMIT 5";
$recent_stmt = $conn->prepare($recent_results_query);
$recent_stmt->bind_param("i", $student['id']);
$recent_stmt->execute();
$recent_results = $recent_stmt->get_result();

// Get class subjects
$subjects_query = "SELECT subject_name, total_marks FROM subjects WHERE class_id = ? ORDER BY subject_name";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->bind_param("i", $student['class_id']);
$subjects_stmt->execute();
$subjects = $subjects_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Portal</title>
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
                    <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
                    <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
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
                <h1><i class="fas fa-user"></i> My Profile</h1>
                <p>View your personal information and academic details</p>
            </div>

            <div class="profile-grid">
                <!-- Personal Information -->
                <div class="card profile-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-circle"></i> Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="profile-photo">
                            <?php if (!empty($student['photo']) && file_exists("../uploads/students/" . $student['photo'])): ?>
                                <img src="../uploads/students/<?= htmlspecialchars($student['photo']) ?>" alt="Student Photo">
                            <?php else: ?>
                                <div class="photo-placeholder">
                                    <i class="fas fa-user fa-3x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="profile-details">
                            <div class="detail-row">
                                <label>Full Name:</label>
                                <span><?= htmlspecialchars($student['name']) ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Roll Number:</label>
                                <span><?= htmlspecialchars($student['roll_no']) ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Class:</label>
                                <span><?= htmlspecialchars($student['class_name'] . ' - ' . $student['section']) ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Date of Birth:</label>
                                <span><?= date('F j, Y', strtotime($student['date_of_birth'])) ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Gender:</label>
                                <span><?= htmlspecialchars($student['gender']) ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Admission Date:</label>
                                <span><?= date('F j, Y', strtotime($student['admission_date'])) ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Address:</label>
                                <span><?= htmlspecialchars($student['address']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Parent Information -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users"></i> Parent/Guardian Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <label>Parent/Guardian Name:</label>
                            <span><?= htmlspecialchars($student['parent_name']) ?></span>
                        </div>
                        <div class="detail-row">
                            <label>Contact Number:</label>
                            <span><?= htmlspecialchars($student['parent_contact']) ?></span>
                        </div>
                        <div class="detail-row">
                            <label>Email Address:</label>
                            <span><?= htmlspecialchars($student['parent_email']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Academic Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Academic Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $attendance_percentage ?>%</h4>
                                    <p>Attendance Rate</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $attendance_stats['present_days'] ?></h4>
                                    <p>Days Present</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $attendance_stats['absent_days'] ?></h4>
                                    <p>Days Absent</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-info">
                                    <h4><?= $attendance_stats['late_days'] ?></h4>
                                    <p>Days Late</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Exam Results -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-trophy"></i> Recent Exam Performance</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_results->num_rows > 0): ?>
                            <div class="results-list">
                                <?php while ($result = $recent_results->fetch_assoc()): ?>
                                    <div class="result-item">
                                        <div class="result-info">
                                            <h4><?= htmlspecialchars($result['exam_name']) ?></h4>
                                            <p><?= htmlspecialchars($result['exam_type']) ?> - <?= date('M j, Y', strtotime($result['exam_date'])) ?></p>
                                        </div>
                                        <div class="result-score">
                                            <span class="percentage <?= $result['average_percentage'] >= 40 ? 'pass' : 'fail' ?>">
                                                <?= round($result['average_percentage'], 1) ?>%
                                            </span>
                                            <small><?= $result['subjects_count'] ?> subjects</small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-clipboard-list fa-2x"></i>
                                <p>No exam results available yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Class Subjects -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> Class Subjects</h3>
                    </div>
                    <div class="card-body">
                        <div class="subjects-grid">
                            <?php while ($subject = $subjects->fetch_assoc()): ?>
                                <div class="subject-item">
                                    <i class="fas fa-book-open"></i>
                                    <div class="subject-info">
                                        <h4><?= htmlspecialchars($subject['subject_name']) ?></h4>
                                        <p>Total Marks: <?= $subject['total_marks'] ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
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
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .profile-card .card-body {
            display: flex;
            gap: 20px;
        }
        
        .profile-photo {
            flex-shrink: 0;
        }
        
        .profile-photo img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
        }
        
        .photo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #95a5a6;
        }
        
        .profile-details {
            flex: 1;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-row label {
            font-weight: 600;
            width: 150px;
            color: #555;
        }
        
        .detail-row span {
            color: #333;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .stat-info h4 {
            margin: 0;
            font-size: 1.5rem;
            color: #2c3e50;
        }
        
        .stat-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .results-list {
            margin-bottom: 15px;
        }
        
        .result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .result-info h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .result-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .result-score {
            text-align: right;
        }
        
        .percentage {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .percentage.pass {
            color: #27ae60;
        }
        
        .percentage.fail {
            color: #e74c3c;
        }
        
        .result-score small {
            display: block;
            color: #666;
            margin-top: 5px;
        }
        
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .subject-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .subject-item i {
            color: #3498db;
            font-size: 1.5rem;
        }
        
        .subject-info h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .subject-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .no-data {
            text-align: center;
            color: #999;
            padding: 40px 20px;
        }
        
        .no-data i {
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-card .card-body {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .subjects-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
