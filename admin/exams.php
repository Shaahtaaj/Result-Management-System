<?php
session_start();
include '../config/database.php';
check_login('admin');

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_exam'])) {
        $exam_name = sanitize_input($_POST['exam_name']);
        $exam_type = sanitize_input($_POST['exam_type']);
        $class_id = (int)$_POST['class_id'];
        $exam_date = $_POST['exam_date'];
        $academic_year = sanitize_input($_POST['academic_year']);
        
        $insert_query = "INSERT INTO exams (exam_name, exam_type, class_id, exam_date, academic_year) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ssiss", $exam_name, $exam_type, $class_id, $exam_date, $academic_year);
        
        if ($insert_stmt->execute()) {
            $message = "Exam created successfully!";
        } else {
            $error = "Failed to create exam.";
        }
    }
    
    if (isset($_POST['edit_exam'])) {
        $exam_id = (int)$_POST['exam_id'];
        $exam_name = sanitize_input($_POST['exam_name']);
        $exam_type = sanitize_input($_POST['exam_type']);
        $exam_date = $_POST['exam_date'];
        $status = sanitize_input($_POST['status']);
        
        $update_query = "UPDATE exams SET exam_name = ?, exam_type = ?, exam_date = ?, status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssssi", $exam_name, $exam_type, $exam_date, $status, $exam_id);
        
        if ($update_stmt->execute()) {
            $message = "Exam updated successfully!";
        } else {
            $error = "Failed to update exam.";
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = (int)$_GET['delete'];
    
    // Check if exam has results
    $check_results = "SELECT COUNT(*) as count FROM results WHERE exam_id = ?";
    $check_stmt = $conn->prepare($check_results);
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $result_count = $check_result->fetch_assoc()['count'];
    
    if ($result_count > 0) {
        $error = "Cannot delete exam. It has $result_count results recorded.";
    } else {
        $delete_query = "DELETE FROM exams WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $delete_id);
        
        if ($delete_stmt->execute()) {
            $message = "Exam deleted successfully!";
        } else {
            $error = "Failed to delete exam.";
        }
    }
}

// Get exams with class information
$exams_query = "
    SELECT e.*, c.class_name, c.section, COUNT(r.id) as result_count,
           COUNT(DISTINCT r.student_id) as student_count
    FROM exams e 
    JOIN classes c ON e.class_id = c.id 
    LEFT JOIN results r ON e.id = r.exam_id
    GROUP BY e.id 
    ORDER BY e.exam_date DESC, c.class_name, c.section
";
$exams_result = $conn->query($exams_query);

// Get classes for dropdown
$classes_query = "SELECT * FROM classes ORDER BY class_name, section";
$classes_result = $conn->query($classes_query);

// Get current academic year from school settings
$school_settings = get_school_settings();
$current_academic_year = $school_settings ? $school_settings['academic_year'] : date('Y') . '-' . (date('Y') + 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exams Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Exams Management</h1>
                    <p>Create and manage examinations</p>
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
                <li><a href="exams.php" class="active"><i class="fas fa-clipboard-list"></i> Exams</a></li>
                <li><a href="results.php"><i class="fas fa-chart-bar"></i> Results</a></li>
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
        
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
            <!-- Add New Exam Form -->
            <div class="form-container">
                <h3><i class="fas fa-plus"></i> Create New Exam</h3>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="exam_name">Exam Name *</label>
                        <input type="text" id="exam_name" name="exam_name" required placeholder="e.g., First Term Examination">
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_type">Exam Type *</label>
                        <select id="exam_type" name="exam_type" required>
                            <option value="">Select Type</option>
                            <option value="Midterm">Midterm</option>
                            <option value="Final">Final</option>
                            <option value="Monthly">Monthly Test</option>
                            <option value="Weekly">Weekly Test</option>
                            <option value="Unit Test">Unit Test</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="class_id">Class *</label>
                        <select id="class_id" name="class_id" required>
                            <option value="">Select Class</option>
                            <?php 
                            $classes_result->data_seek(0);
                            while ($class = $classes_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo $class['class_name'] . ' - ' . $class['section']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_date">Exam Date *</label>
                        <input type="date" id="exam_date" name="exam_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="academic_year">Academic Year *</label>
                        <input type="text" id="academic_year" name="academic_year" value="<?php echo $current_academic_year; ?>" required>
                    </div>
                    
                    <button type="submit" name="add_exam" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Exam
                    </button>
                </form>
                
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <h4 style="color: #2c3e50; margin-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> Exam Status
                    </h4>
                    <div style="font-size: 0.9rem; color: #7f8c8d;">
                        <p><strong>Scheduled:</strong> Exam is planned but not started</p>
                        <p><strong>Ongoing:</strong> Exam is currently in progress</p>
                        <p><strong>Completed:</strong> Exam is finished and results can be entered</p>
                    </div>
                </div>
            </div>
            
            <!-- Exams List -->
            <div class="form-container">
                <h3><i class="fas fa-list"></i> Scheduled Exams</h3>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Exam Name</th>
                                <th>Type</th>
                                <th>Class</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Results</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($exams_result->num_rows > 0): ?>
                                <?php while ($row = $exams_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['exam_name']; ?></td>
                                        <td>
                                            <span class="badge" style="background: 
                                                <?php 
                                                switch($row['exam_type']) {
                                                    case 'Final': echo '#dc3545'; break;
                                                    case 'Midterm': echo '#ffc107'; break;
                                                    case 'Monthly': echo '#28a745'; break;
                                                    case 'Weekly': echo '#17a2b8'; break;
                                                    default: echo '#6c757d';
                                                }
                                                ?>; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo $row['exam_type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $row['class_name'] . ' - ' . $row['section']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($row['exam_date'])); ?></td>
                                        <td>
                                            <span class="badge" style="background: 
                                                <?php 
                                                switch($row['status']) {
                                                    case 'Completed': echo '#28a745'; break;
                                                    case 'Ongoing': echo '#ffc107'; break;
                                                    default: echo '#6c757d';
                                                }
                                                ?>; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: #667eea; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo $row['student_count']; ?> students
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick="editExam(<?php echo $row['id']; ?>, '<?php echo addslashes($row['exam_name']); ?>', '<?php echo $row['exam_type']; ?>', '<?php echo $row['exam_date']; ?>', '<?php echo $row['status']; ?>')" 
                                                    class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($row['result_count'] == 0): ?>
                                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;" 
                                                   onclick="return confirm('Are you sure you want to delete this exam?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No exams found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Exam Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px;">
            <h3><i class="fas fa-edit"></i> Edit Exam</h3>
            
            <form method="POST">
                <input type="hidden" id="edit_exam_id" name="exam_id">
                
                <div class="form-group">
                    <label for="edit_exam_name">Exam Name *</label>
                    <input type="text" id="edit_exam_name" name="exam_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_exam_type">Exam Type *</label>
                    <select id="edit_exam_type" name="exam_type" required>
                        <option value="Midterm">Midterm</option>
                        <option value="Final">Final</option>
                        <option value="Monthly">Monthly Test</option>
                        <option value="Weekly">Weekly Test</option>
                        <option value="Unit Test">Unit Test</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_exam_date">Exam Date *</label>
                    <input type="date" id="edit_exam_date" name="exam_date" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status *</label>
                    <select id="edit_status" name="status" required>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="edit_exam" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Exam
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editExam(id, name, type, date, status) {
            document.getElementById('edit_exam_id').value = id;
            document.getElementById('edit_exam_name').value = name;
            document.getElementById('edit_exam_type').value = type;
            document.getElementById('edit_exam_date').value = date;
            document.getElementById('edit_status').value = status;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Set minimum date to today for new exams
        document.getElementById('exam_date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>
