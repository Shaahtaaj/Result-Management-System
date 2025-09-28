<?php
session_start();
include '../config/database.php';
check_login('admin');

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_class'])) {
        $class_name = sanitize_input($_POST['class_name']);
        $section = sanitize_input($_POST['section']);
        
        // Check if class-section combination already exists
        $check_query = "SELECT id FROM classes WHERE class_name = ? AND section = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $class_name, $section);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Class with this section already exists!";
        } else {
            $insert_query = "INSERT INTO classes (class_name, section) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ss", $class_name, $section);
            
            if ($insert_stmt->execute()) {
                $message = "Class added successfully!";
            } else {
                $error = "Failed to add class.";
            }
        }
    }
    
    if (isset($_POST['edit_class'])) {
        $class_id = (int)$_POST['class_id'];
        $class_name = sanitize_input($_POST['class_name']);
        $section = sanitize_input($_POST['section']);
        
        $update_query = "UPDATE classes SET class_name = ?, section = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssi", $class_name, $section, $class_id);
        
        if ($update_stmt->execute()) {
            $message = "Class updated successfully!";
        } else {
            $error = "Failed to update class.";
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = (int)$_GET['delete'];
    
    // Check if class has students
    $check_students = "SELECT COUNT(*) as count FROM students WHERE class_id = ?";
    $check_stmt = $conn->prepare($check_students);
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $student_count = $check_result->fetch_assoc()['count'];
    
    if ($student_count > 0) {
        $error = "Cannot delete class. It has $student_count students enrolled.";
    } else {
        $delete_query = "DELETE FROM classes WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $delete_id);
        
        if ($delete_stmt->execute()) {
            $message = "Class deleted successfully!";
        } else {
            $error = "Failed to delete class.";
        }
    }
}

// Get classes with student count
$classes_query = "
    SELECT c.*, COUNT(s.id) as student_count 
    FROM classes c 
    LEFT JOIN students s ON c.id = s.class_id 
    GROUP BY c.id 
    ORDER BY c.class_name, c.section
";
$classes_result = $conn->query($classes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Classes Management</h1>
                    <p>Manage school classes and sections</p>
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
                <li><a href="classes.php" class="active"><i class="fas fa-chalkboard"></i> Classes</a></li>
                <li><a href="subjects.php"><i class="fas fa-book"></i> Subjects</a></li>
                <li><a href="students.php"><i class="fas fa-graduation-cap"></i> Students</a></li>
                <li><a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
                <li><a href="exams.php"><i class="fas fa-clipboard-list"></i> Exams</a></li>
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
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- Add New Class Form -->
            <div class="form-container">
                <h3><i class="fas fa-plus"></i> Add New Class</h3>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="class_name">Class Name *</label>
                        <select id="class_name" name="class_name" required>
                            <option value="">Select Class</option>
                            <option value="Nursery">Nursery</option>
                            <option value="KG">KG</option>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                            <option value="Grade 7">Grade 7</option>
                            <option value="Grade 8">Grade 8</option>
                            <option value="Grade 9">Grade 9</option>
                            <option value="Grade 10">Grade 10</option>
                            <option value="Grade 11">Grade 11</option>
                            <option value="Grade 12">Grade 12</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="section">Section *</label>
                        <select id="section" name="section" required>
                            <option value="">Select Section</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_class" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Class
                    </button>
                </form>
            </div>
            
            <!-- Classes List -->
            <div class="form-container">
                <h3><i class="fas fa-list"></i> Existing Classes</h3>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Students</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($classes_result->num_rows > 0): ?>
                                <?php while ($row = $classes_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['class_name']; ?></td>
                                        <td><?php echo $row['section']; ?></td>
                                        <td>
                                            <span class="badge" style="background: #667eea; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo $row['student_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick="editClass(<?php echo $row['id']; ?>, '<?php echo $row['class_name']; ?>', '<?php echo $row['section']; ?>')" 
                                                    class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($row['student_count'] == 0): ?>
                                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;" 
                                                   onclick="return confirm('Are you sure you want to delete this class?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No classes found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Class Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 400px;">
            <h3><i class="fas fa-edit"></i> Edit Class</h3>
            
            <form method="POST">
                <input type="hidden" id="edit_class_id" name="class_id">
                
                <div class="form-group">
                    <label for="edit_class_name">Class Name *</label>
                    <select id="edit_class_name" name="class_name" required>
                        <option value="">Select Class</option>
                        <option value="Nursery">Nursery</option>
                        <option value="KG">KG</option>
                        <option value="Grade 1">Grade 1</option>
                        <option value="Grade 2">Grade 2</option>
                        <option value="Grade 3">Grade 3</option>
                        <option value="Grade 4">Grade 4</option>
                        <option value="Grade 5">Grade 5</option>
                        <option value="Grade 6">Grade 6</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                        <option value="Grade 11">Grade 11</option>
                        <option value="Grade 12">Grade 12</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_section">Section *</label>
                    <select id="edit_section" name="section" required>
                        <option value="">Select Section</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                    </select>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="edit_class" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Class
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editClass(id, className, section) {
            document.getElementById('edit_class_id').value = id;
            document.getElementById('edit_class_name').value = className;
            document.getElementById('edit_section').value = section;
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
    </script>
</body>
</html>
