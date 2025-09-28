<?php
session_start();
include '../config/database.php';
check_login('admin');

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_subject'])) {
        $subject_name = sanitize_input($_POST['subject_name']);
        $class_id = (int)$_POST['class_id'];
        $total_marks = (int)$_POST['total_marks'];
        
        // Check if subject already exists for this class
        $check_query = "SELECT id FROM subjects WHERE subject_name = ? AND class_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("si", $subject_name, $class_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Subject already exists for this class!";
        } else {
            $insert_query = "INSERT INTO subjects (subject_name, class_id, total_marks) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sii", $subject_name, $class_id, $total_marks);
            
            if ($insert_stmt->execute()) {
                $message = "Subject added successfully!";
            } else {
                $error = "Failed to add subject.";
            }
        }
    }
    
    if (isset($_POST['edit_subject'])) {
        $subject_id = (int)$_POST['subject_id'];
        $subject_name = sanitize_input($_POST['subject_name']);
        $total_marks = (int)$_POST['total_marks'];
        
        $update_query = "UPDATE subjects SET subject_name = ?, total_marks = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sii", $subject_name, $total_marks, $subject_id);
        
        if ($update_stmt->execute()) {
            $message = "Subject updated successfully!";
        } else {
            $error = "Failed to update subject.";
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = (int)$_GET['delete'];
    
    // Check if subject has results
    $check_results = "SELECT COUNT(*) as count FROM results WHERE subject_id = ?";
    $check_stmt = $conn->prepare($check_results);
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $result_count = $check_result->fetch_assoc()['count'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete associated results if they exist
        if ($result_count > 0) {
            $delete_results = "DELETE FROM results WHERE subject_id = ?";
            $delete_results_stmt = $conn->prepare($delete_results);
            $delete_results_stmt->bind_param("i", $delete_id);
            $delete_results_stmt->execute();
        }
        
        // Delete the subject
        $delete_query = "DELETE FROM subjects WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $delete_id);
        $delete_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        $message = "Subject deleted successfully!" . ($result_count > 0 ? " $result_count associated results were also removed." : "");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Failed to delete subject: " . $e->getMessage();
    }
}

// Get subjects with class information
$subjects_query = "
    SELECT s.*, c.class_name, c.section, COUNT(r.id) as result_count
    FROM subjects s 
    JOIN classes c ON s.class_id = c.id 
    LEFT JOIN results r ON s.id = r.subject_id
    GROUP BY s.id 
    ORDER BY c.class_name, c.section, s.subject_name
";
$subjects_result = $conn->query($subjects_query);

// Get classes for dropdown
$classes_query = "SELECT * FROM classes ORDER BY class_name, section";
$classes_result = $conn->query($classes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Subjects Management</h1>
                    <p>Manage subjects for different classes</p>
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
                <li><a href="subjects.php" class="active"><i class="fas fa-book"></i> Subjects</a></li>
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
        
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
            <!-- Add New Subject Form -->
            <div class="form-container">
                <h3><i class="fas fa-plus"></i> Add New Subject</h3>
                
                <form method="POST">
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
                        <label for="subject_name">Subject Name *</label>
                        <input type="text" id="subject_name" name="subject_name" required placeholder="e.g., Mathematics">
                    </div>
                    
                    <div class="form-group">
                        <label for="total_marks">Total Marks *</label>
                        <input type="number" id="total_marks" name="total_marks" min="1" value="100" required>
                    </div>
                    
                    <button type="submit" name="add_subject" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Subject
                    </button>
                </form>
                
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <h4 style="color: #2c3e50; margin-bottom: 15px;">
                        <i class="fas fa-lightbulb"></i> Common Subjects
                    </h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <button type="button" onclick="fillSubject('English')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">English</button>
                        <button type="button" onclick="fillSubject('Mathematics')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Mathematics</button>
                        <button type="button" onclick="fillSubject('Science')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Science</button>
                        <button type="button" onclick="fillSubject('Social Studies')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Social Studies</button>
                        <button type="button" onclick="fillSubject('Physics')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Physics</button>
                        <button type="button" onclick="fillSubject('Chemistry')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Chemistry</button>
                        <button type="button" onclick="fillSubject('Biology')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Biology</button>
                        <button type="button" onclick="fillSubject('Computer Science')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Computer Science</button>
                        <button type="button" onclick="fillSubject('Art & Craft')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Art & Craft</button>
                    </div>
                </div>
            </div>
            
            <!-- Subjects List -->
            <div class="form-container">
                <h3><i class="fas fa-list"></i> Existing Subjects</h3>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Total Marks</th>
                                <th>Results</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($subjects_result->num_rows > 0): ?>
                                <?php while ($row = $subjects_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['subject_name']; ?></td>
                                        <td><?php echo $row['class_name'] . ' - ' . $row['section']; ?></td>
                                        <td><?php echo $row['total_marks']; ?></td>
                                        <td>
                                            <span class="badge" style="background: #667eea; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo $row['result_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick="editSubject(<?php echo $row['id']; ?>, '<?php echo addslashes($row['subject_name']); ?>', <?php echo $row['total_marks']; ?>)" 
                                                    class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" 
                                               onclick="return confirm('Are you sure you want to delete this subject? <?php echo ($row['result_count'] > 0) ? '\nWarning: This subject has ' . $row['result_count'] . ' results associated with it. Deleting will remove all related results.' : ''; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No subjects found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Subject Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 400px;">
            <h3><i class="fas fa-edit"></i> Edit Subject</h3>
            
            <form method="POST">
                <input type="hidden" id="edit_subject_id" name="subject_id">
                
                <div class="form-group">
                    <label for="edit_subject_name">Subject Name *</label>
                    <input type="text" id="edit_subject_name" name="subject_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_total_marks">Total Marks *</label>
                    <input type="number" id="edit_total_marks" name="total_marks" min="1" required>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="edit_subject" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Subject
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function fillSubject(subjectName) {
            document.getElementById('subject_name').value = subjectName;
        }
        
        function editSubject(id, name, totalMarks) {
            document.getElementById('edit_subject_id').value = id;
            document.getElementById('edit_subject_name').value = name;
            document.getElementById('edit_total_marks').value = totalMarks;
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
