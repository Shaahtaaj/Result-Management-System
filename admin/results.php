<?php
session_start();
include '../config/database.php';
check_login('admin');

$message = '';
$error = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Handle bulk result entry
if ($_POST && isset($_POST['save_results'])) {
    $exam_id = (int)$_POST['exam_id'];
    $results = $_POST['results'] ?? [];
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($results as $student_id => $subjects) {
        foreach ($subjects as $subject_id => $marks) {
            if (!empty($marks) && is_numeric($marks)) {
                // Check if result already exists
                $check_query = "SELECT id FROM results WHERE student_id = ? AND subject_id = ? AND exam_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("iii", $student_id, $subject_id, $exam_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Update existing result
                    $update_query = "UPDATE results SET marks_obtained = ? WHERE student_id = ? AND subject_id = ? AND exam_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("diii", $marks, $student_id, $subject_id, $exam_id);
                    
                    if ($update_stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    // Get total marks for the subject
                    $total_marks_query = "SELECT total_marks FROM subjects WHERE id = ?";
                    $total_marks_stmt = $conn->prepare($total_marks_query);
                    $total_marks_stmt->bind_param("i", $subject_id);
                    $total_marks_stmt->execute();
                    $total_marks_result = $total_marks_stmt->get_result();
                    $total_marks_row = $total_marks_result->fetch_assoc();
                    $total_marks = $total_marks_row['total_marks'] ?? 100;
                    
                    // Insert new result
                    $insert_query = "INSERT INTO results (student_id, subject_id, exam_id, marks_obtained, total_marks) VALUES (?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("iiidd", $student_id, $subject_id, $exam_id, $marks, $total_marks);
                    
                    if ($insert_stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
            }
        }
    }
    
    if ($success_count > 0) {
        $message = "$success_count results saved successfully!";
    }
    if ($error_count > 0) {
        $error = "$error_count results failed to save.";
    }
}

// Get exams for dropdown
$exams_query = "
    SELECT e.*, c.class_name, c.section 
    FROM exams e 
    JOIN classes c ON e.class_id = c.id 
    ORDER BY e.exam_date DESC
";
$exams_result = $conn->query($exams_query);

// Get results overview
if ($action === 'list') {
    $results_query = "
        SELECT 
            e.id as exam_id,
            e.exam_name, e.exam_type, e.exam_date,
            c.class_name, c.section,
            COUNT(DISTINCT r.student_id) as students_with_results,
            COUNT(r.id) as total_results,
            AVG(r.marks_obtained) as avg_marks
        FROM exams e
        JOIN classes c ON e.class_id = c.id
        LEFT JOIN results r ON e.id = r.exam_id
        GROUP BY e.id
        ORDER BY e.exam_date DESC
    ";
    $results_overview = $conn->query($results_query);
}

// Get detailed results for specific exam
if ($action === 'enter' && $exam_id > 0) {
    // Get exam details
    $exam_query = "
        SELECT e.*, c.class_name, c.section, c.id as class_id
        FROM exams e 
        JOIN classes c ON e.class_id = c.id 
        WHERE e.id = ?
    ";
    $exam_stmt = $conn->prepare($exam_query);
    $exam_stmt->bind_param("i", $exam_id);
    $exam_stmt->execute();
    $exam_result = $exam_stmt->get_result();
    $exam_data = $exam_result->fetch_assoc();
    
    if ($exam_data) {
        $class_id = $exam_data['class_id'];
        
        // Get students in this class
        $students_query = "SELECT * FROM students WHERE class_id = ? ORDER BY roll_no";
        $students_stmt = $conn->prepare($students_query);
        $students_stmt->bind_param("i", $class_id);
        $students_stmt->execute();
        $students_result = $students_stmt->get_result();
        
        // Get subjects for this class
        $subjects_query = "SELECT * FROM subjects WHERE class_id = ? ORDER BY subject_name";
        $subjects_stmt = $conn->prepare($subjects_query);
        $subjects_stmt->bind_param("i", $class_id);
        $subjects_stmt->execute();
        $subjects_result = $subjects_stmt->get_result();
        
        // Get existing results
        $existing_results_query = "
            SELECT r.*, s.subject_name, st.name as student_name
            FROM results r
            JOIN subjects s ON r.subject_id = s.id
            JOIN students st ON r.student_id = st.id
            WHERE r.exam_id = ?
        ";
        $existing_stmt = $conn->prepare($existing_results_query);
        $existing_stmt->bind_param("i", $exam_id);
        $existing_stmt->execute();
        $existing_results = $existing_stmt->get_result();
        
        $existing_marks = [];
        while ($row = $existing_results->fetch_assoc()) {
            $existing_marks[$row['student_id']][$row['subject_id']] = $row['marks_obtained'];
        }
    }
}

// Get results for viewing
if ($action === 'view' && $exam_id > 0) {
    // Get exam details
    $exam_query = "
        SELECT e.*, c.class_name, c.section
        FROM exams e 
        JOIN classes c ON e.class_id = c.id 
        WHERE e.id = ?
    ";
    $exam_stmt = $conn->prepare($exam_query);
    $exam_stmt->bind_param("i", $exam_id);
    $exam_stmt->execute();
    $exam_result = $exam_stmt->get_result();
    $exam_data = $exam_result->fetch_assoc();
    
    if ($exam_data) {
        // Get detailed results
        $detailed_results_query = "
            SELECT 
                s.name, s.roll_no,
                sub.subject_name, sub.total_marks,
                r.marks_obtained,
                ROUND((r.marks_obtained / sub.total_marks) * 100, 2) as percentage
            FROM results r
            JOIN students s ON r.student_id = s.id
            JOIN subjects sub ON r.subject_id = sub.id
            WHERE r.exam_id = ?
            ORDER BY s.roll_no, sub.subject_name
        ";
        $detailed_stmt = $conn->prepare($detailed_results_query);
        $detailed_stmt->bind_param("i", $exam_id);
        $detailed_stmt->execute();
        $detailed_results = $detailed_stmt->get_result();
        
        // Organize results by student
        $student_results = [];
        while ($row = $detailed_results->fetch_assoc()) {
            $student_results[$row['roll_no']]['name'] = $row['name'];
            $student_results[$row['roll_no']]['subjects'][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid;
        }
        
        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .alert h3 {
            margin: 0 0 10px 0;
            color: inherit;
        }
        
        .alert p {
            margin: 10px 0;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Results Management</h1>
                    <p>Manage examination results and grades</p>
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
                <li><a href="results.php" class="active"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                <li><a href="reports.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
            </ul>
        </nav>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'list'): ?>
            <div class="form-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3><i class="fas fa-chart-bar"></i> Results Overview</h3>
                    <div>
                        <select onchange="location.href='?action=enter&exam_id=' + this.value" class="btn btn-primary" style="padding: 8px 15px;">
                            <option value="">Select Exam to Enter Results</option>
                            <?php 
                            $exams_result->data_seek(0);
                            while ($exam = $exams_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $exam['id']; ?>">
                                    <?php echo $exam['exam_name'] . ' - ' . $exam['class_name'] . ' ' . $exam['section']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Class</th>
                                <th>Date</th>
                                <th>Students</th>
                                <th>Results</th>
                                <th>Avg Marks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($results_overview->num_rows > 0): ?>
                                <?php while ($row = $results_overview->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $row['exam_name']; ?></strong><br>
                                            <small style="color: #7f8c8d;"><?php echo $row['exam_type']; ?></small>
                                        </td>
                                        <td><?php echo $row['class_name'] . ' - ' . $row['section']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($row['exam_date'])); ?></td>
                                        <td>
                                            <span class="badge" style="background: #667eea; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo $row['students_with_results'] ?? 0; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: #28a745; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo $row['total_results'] ?? 0; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $row['avg_marks'] ? round($row['avg_marks'], 1) . '%' : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <a href="?action=view&exam_id=<?php echo $row['exam_id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="?action=enter&exam_id=<?php echo $row['exam_id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No results found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'enter' && $exam_id == 0): ?>
            <div class="form-container">
                <div class="alert alert-error">
                    <h3><i class="fas fa-exclamation-triangle"></i> No Exam Selected</h3>
                    <p>Please select an exam from the dropdown above to enter results.</p>
                    <a href="?action=list" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Results List
                    </a>
                </div>
            </div>
        <?php elseif ($action === 'enter' && $exam_id > 0 && !isset($exam_data)): ?>
            <div class="form-container">
                <div class="alert alert-error">
                    <h3><i class="fas fa-exclamation-triangle"></i> Exam Not Found</h3>
                    <p>The selected exam could not be found or may have been deleted.</p>
                    <a href="?action=list" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Results List
                    </a>
                </div>
            </div>
        <?php elseif ($action === 'enter' && $exam_id > 0 && isset($exam_data)): ?>
            <div class="form-container">
                <h3><i class="fas fa-edit"></i> Enter Results - <?php echo $exam_data['exam_name']; ?></h3>
                <p style="color: #7f8c8d; margin-bottom: 20px;">
                    Class: <?php echo $exam_data['class_name'] . ' - ' . $exam_data['section']; ?> | 
                    Date: <?php echo date('M j, Y', strtotime($exam_data['exam_date'])); ?>
                </p>
                
                <form method="POST">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    
                    <div style="overflow-x: auto;">
                        <table class="results-table" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                            <thead>
                                <tr style="background: #667eea; color: white;">
                                    <th style="padding: 12px; border: 1px solid #ddd;">Roll No</th>
                                    <th style="padding: 12px; border: 1px solid #ddd;">Student Name</th>
                                    <?php 
                                    $subjects_result->data_seek(0);
                                    while ($subject = $subjects_result->fetch_assoc()): 
                                    ?>
                                        <th style="padding: 12px; border: 1px solid #ddd;">
                                            <?php echo $subject['subject_name']; ?><br>
                                            <small>(<?php echo $subject['total_marks']; ?> marks)</small>
                                        </th>
                                    <?php endwhile; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = $students_result->fetch_assoc()): ?>
                                    <tr>
                                        <td style="padding: 12px; border: 1px solid #ddd; font-weight: bold;">
                                            <?php echo $student['roll_no']; ?>
                                        </td>
                                        <td style="padding: 12px; border: 1px solid #ddd;">
                                            <?php echo $student['name']; ?>
                                        </td>
                                        <?php 
                                        $subjects_result->data_seek(0);
                                        while ($subject = $subjects_result->fetch_assoc()): 
                                            $existing_mark = $existing_marks[$student['id']][$subject['id']] ?? '';
                                        ?>
                                            <td style="padding: 8px; border: 1px solid #ddd;">
                                                <input type="number" 
                                                       name="results[<?php echo $student['id']; ?>][<?php echo $subject['id']; ?>]"
                                                       value="<?php echo $existing_mark; ?>"
                                                       min="0" 
                                                       max="<?php echo $subject['total_marks']; ?>"
                                                       step="0.5"
                                                       style="width: 80px; padding: 5px; border: 1px solid #ddd; border-radius: 3px; text-align: center;">
                                            </td>
                                        <?php endwhile; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" name="save_results" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Results
                        </button>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'view' && $exam_id == 0): ?>
            <div class="form-container">
                <div class="alert alert-error">
                    <h3><i class="fas fa-exclamation-triangle"></i> No Exam Selected</h3>
                    <p>Please select an exam from the results list to view results.</p>
                    <a href="?action=list" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Results List
                    </a>
                </div>
            </div>
        <?php elseif ($action === 'view' && $exam_id > 0 && !isset($exam_data)): ?>
            <div class="form-container">
                <div class="alert alert-error">
                    <h3><i class="fas fa-exclamation-triangle"></i> Exam Not Found</h3>
                    <p>The selected exam could not be found or may have been deleted.</p>
                    <a href="?action=list" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Results List
                    </a>
                </div>
            </div>
        <?php elseif ($action === 'view' && $exam_id > 0 && isset($exam_data) && empty($student_results)): ?>
            <div class="form-container">
                <div class="alert alert-warning">
                    <h3><i class="fas fa-info-circle"></i> No Results Found</h3>
                    <p>No results have been entered for this exam yet.</p>
                    <div style="margin-top: 20px;">
                        <a href="?action=enter&exam_id=<?php echo $exam_id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Enter Results
                        </a>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Results List
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif ($action === 'view' && $exam_id > 0 && isset($exam_data) && !empty($student_results)): ?>
            <div class="form-container">
                <h3><i class="fas fa-eye"></i> View Results - <?php echo $exam_data['exam_name']; ?></h3>
                <p style="color: #7f8c8d; margin-bottom: 20px;">
                    Class: <?php echo $exam_data['class_name'] . ' - ' . $exam_data['section']; ?> | 
                    Date: <?php echo date('M j, Y', strtotime($exam_data['exam_date'])); ?>
                </p>
                
                <?php foreach ($student_results as $roll_no => $student_data): ?>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">
                            <?php echo $student_data['name']; ?> (<?php echo $roll_no; ?>)
                        </h4>
                        
                        <div class="table-container">
                            <table>
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
                                    $total_obtained = 0;
                                    $total_possible = 0;
                                    foreach ($student_data['subjects'] as $subject): 
                                        $total_obtained += $subject['marks_obtained'];
                                        $total_possible += $subject['total_marks'];
                                        
                                        // Calculate grade
                                        $percentage = $subject['percentage'];
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
                                            <td><?php echo $subject['subject_name']; ?></td>
                                            <td><?php echo $subject['marks_obtained']; ?></td>
                                            <td><?php echo $subject['total_marks']; ?></td>
                                            <td><?php echo $subject['percentage']; ?>%</td>
                                            <td>
                                                <span style="font-weight: bold; color: <?php echo $percentage >= 60 ? '#28a745' : ($percentage >= 40 ? '#ffc107' : '#dc3545'); ?>">
                                                    <?php echo $grade; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php 
                                    $overall_percentage = round(($total_obtained / $total_possible) * 100, 2);
                                    if ($overall_percentage >= 90) $overall_grade = 'A+';
                                    elseif ($overall_percentage >= 80) $overall_grade = 'A';
                                    elseif ($overall_percentage >= 70) $overall_grade = 'B+';
                                    elseif ($overall_percentage >= 60) $overall_grade = 'B';
                                    elseif ($overall_percentage >= 50) $overall_grade = 'C+';
                                    elseif ($overall_percentage >= 40) $overall_grade = 'C';
                                    elseif ($overall_percentage >= 33) $overall_grade = 'D';
                                    else $overall_grade = 'F';
                                    ?>
                                    
                                    <tr style="background: #e9ecef; font-weight: bold;">
                                        <td>TOTAL</td>
                                        <td><?php echo $total_obtained; ?></td>
                                        <td><?php echo $total_possible; ?></td>
                                        <td><?php echo $overall_percentage; ?>%</td>
                                        <td>
                                            <span style="color: <?php echo $overall_percentage >= 60 ? '#28a745' : ($overall_percentage >= 40 ? '#ffc107' : '#dc3545'); ?>">
                                                <?php echo $overall_grade; ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="?action=enter&exam_id=<?php echo $exam_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Results
                    </a>
                    <a href="?action=list" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
