<?php
session_start();
include '../config/database.php';
check_login('admin');

$message = '';
$error = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$teacher_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_teacher'])) {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $subject_specialization = sanitize_input($_POST['subject_specialization']);
        $qualification = sanitize_input($_POST['qualification']);
        $experience_years = (int)$_POST['experience_years'];
        $username = sanitize_input($_POST['username']);
        
        // Check if username already exists
        $check_query = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            // Create user account for teacher
            $password = password_hash('teacher123', PASSWORD_DEFAULT); // Default password
            
            $user_query = "INSERT INTO users (username, password, role) VALUES (?, ?, 'teacher')";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("ss", $username, $password);
            
            if ($user_stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Insert teacher record
                $teacher_query = "INSERT INTO teachers (user_id, name, email, phone, subject_specialization, qualification, experience_years) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $teacher_stmt = $conn->prepare($teacher_query);
                $teacher_stmt->bind_param("isssssi", $user_id, $name, $email, $phone, $subject_specialization, $qualification, $experience_years);
                
                if ($teacher_stmt->execute()) {
                    $message = "Teacher added successfully! Login credentials - Username: $username, Password: teacher123";
                    $action = 'list';
                } else {
                    $error = "Failed to add teacher.";
                }
            } else {
                $error = "Failed to create user account.";
            }
        }
    }
    
    if (isset($_POST['edit_teacher'])) {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $subject_specialization = sanitize_input($_POST['subject_specialization']);
        $qualification = sanitize_input($_POST['qualification']);
        $experience_years = (int)$_POST['experience_years'];
        
        $update_query = "UPDATE teachers SET name = ?, email = ?, phone = ?, subject_specialization = ?, qualification = ?, experience_years = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssssi", $name, $email, $phone, $subject_specialization, $qualification, $experience_years, $teacher_id);
        
        if ($update_stmt->execute()) {
            $message = "Teacher updated successfully!";
            $action = 'list';
        } else {
            $error = "Failed to update teacher.";
        }
    }
    
    if (isset($_POST['assign_classes'])) {
        $teacher_id = (int)$_POST['teacher_id'];
        $assignments = $_POST['assignments'] ?? [];
        
        // Delete existing assignments
        $delete_query = "DELETE FROM teacher_classes WHERE teacher_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $teacher_id);
        $delete_stmt->execute();
        
        // Insert new assignments
        if (!empty($assignments)) {
            $insert_query = "INSERT INTO teacher_classes (teacher_id, class_id, subject_id, is_class_teacher) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            
            foreach ($assignments as $assignment) {
                $parts = explode('|', $assignment);
                if (count($parts) >= 2) {
                    $class_id = (int)$parts[0];
                    $subject_id = (int)$parts[1];
                    $is_class_teacher = isset($parts[2]) ? 1 : 0;
                    
                    $insert_stmt->bind_param("iiii", $teacher_id, $class_id, $subject_id, $is_class_teacher);
                    $insert_stmt->execute();
                }
            }
        }
        
        $message = "Class assignments updated successfully!";
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = (int)$_GET['delete'];
    
    // Get user_id first
    $user_query = "SELECT user_id FROM teachers WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $delete_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    
    // Delete teacher (will cascade to user due to foreign key)
    $delete_query = "DELETE FROM teachers WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        // Also delete user account
        if ($user_data) {
            $delete_user_query = "DELETE FROM users WHERE id = ?";
            $delete_user_stmt = $conn->prepare($delete_user_query);
            $delete_user_stmt->bind_param("i", $user_data['user_id']);
            $delete_user_stmt->execute();
        }
        $message = "Teacher deleted successfully!";
    } else {
        $error = "Failed to delete teacher.";
    }
}

// Get teachers list
if ($action === 'list') {
    $teachers_query = "SELECT t.*, u.username FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY t.name";
    $teachers_result = $conn->query($teachers_query);
}

// Get teacher data for editing
if ($action === 'edit' && $teacher_id > 0) {
    $teacher_query = "SELECT * FROM teachers WHERE id = ?";
    $teacher_stmt = $conn->prepare($teacher_query);
    $teacher_stmt->bind_param("i", $teacher_id);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();
    $teacher_data = $teacher_result->fetch_assoc();
    
    if (!$teacher_data) {
        $error = "Teacher not found!";
        $action = 'list';
    }
}

// Get data for class assignments
if ($action === 'assign' && $teacher_id > 0) {
    $teacher_query = "SELECT * FROM teachers WHERE id = ?";
    $teacher_stmt = $conn->prepare($teacher_query);
    $teacher_stmt->bind_param("i", $teacher_id);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();
    $teacher_data = $teacher_result->fetch_assoc();
    
    // Get all classes and subjects
    $classes_query = "SELECT * FROM classes ORDER BY class_name, section";
    $classes_result = $conn->query($classes_query);
    
    $subjects_query = "SELECT s.*, c.class_name, c.section FROM subjects s JOIN classes c ON s.class_id = c.id ORDER BY c.class_name, c.section, s.subject_name";
    $subjects_result = $conn->query($subjects_query);
    
    // Get current assignments
    $assignments_query = "SELECT tc.*, c.class_name, c.section, s.subject_name FROM teacher_classes tc JOIN classes c ON tc.class_id = c.id JOIN subjects s ON tc.subject_id = s.id WHERE tc.teacher_id = ?";
    $assignments_stmt = $conn->prepare($assignments_query);
    $assignments_stmt->bind_param("i", $teacher_id);
    $assignments_stmt->execute();
    $assignments_result = $assignments_stmt->get_result();
    
    $current_assignments = [];
    while ($row = $assignments_result->fetch_assoc()) {
        $current_assignments[] = $row['class_id'] . '|' . $row['subject_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Teachers Management</h1>
                    <p>Manage teacher records and assignments</p>
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
                <li><a href="teachers.php" class="active"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
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
        
        <?php if ($action === 'list'): ?>
            <div class="form-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3><i class="fas fa-chalkboard-teacher"></i> Teachers List</h3>
                    <a href="?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Teacher
                    </a>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Specialization</th>
                                <th>Experience</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($teachers_result->num_rows > 0): ?>
                                <?php while ($row = $teachers_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['name']; ?></td>
                                        <td><?php echo $row['username']; ?></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td><?php echo $row['phone']; ?></td>
                                        <td><?php echo $row['subject_specialization']; ?></td>
                                        <td><?php echo $row['experience_years']; ?> years</td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=assign&id=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-tasks"></i>
                                            </a>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;" 
                                               onclick="return confirm('Are you sure you want to delete this teacher?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No teachers found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add'): ?>
            <div class="form-container">
                <h3><i class="fas fa-user-plus"></i> Add New Teacher</h3>
                
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="name">Teacher Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="subject_specialization">Subject Specialization</label>
                            <input type="text" id="subject_specialization" name="subject_specialization" placeholder="e.g., Mathematics, English">
                        </div>
                        
                        <div class="form-group">
                            <label for="experience_years">Years of Experience</label>
                            <input type="number" id="experience_years" name="experience_years" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="qualification">Qualification</label>
                        <textarea id="qualification" name="qualification" rows="3" placeholder="Educational qualifications and certifications"></textarea>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" name="add_teacher" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Teacher
                        </button>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'edit' && isset($teacher_data)): ?>
            <div class="form-container">
                <h3><i class="fas fa-edit"></i> Edit Teacher</h3>
                
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="name">Teacher Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo $teacher_data['name']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo $teacher_data['email']; ?>">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo $teacher_data['phone']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="experience_years">Years of Experience</label>
                            <input type="number" id="experience_years" name="experience_years" min="0" value="<?php echo $teacher_data['experience_years']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject_specialization">Subject Specialization</label>
                        <input type="text" id="subject_specialization" name="subject_specialization" value="<?php echo $teacher_data['subject_specialization']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="qualification">Qualification</label>
                        <textarea id="qualification" name="qualification" rows="3"><?php echo $teacher_data['qualification']; ?></textarea>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" name="edit_teacher" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Teacher
                        </button>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'assign' && isset($teacher_data)): ?>
            <div class="form-container">
                <h3><i class="fas fa-tasks"></i> Assign Classes & Subjects to <?php echo $teacher_data['name']; ?></h3>
                
                <form method="POST">
                    <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
                    
                    <div class="form-group">
                        <label>Select Class-Subject Combinations:</label>
                        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #e9ecef; padding: 15px; border-radius: 5px;">
                            <?php 
                            $subjects_result->data_seek(0);
                            while ($subject = $subjects_result->fetch_assoc()): 
                                $assignment_key = $subject['class_id'] . '|' . $subject['id'];
                                $is_assigned = in_array($assignment_key, $current_assignments);
                            ?>
                                <div style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="assignments[]" value="<?php echo $assignment_key; ?>" 
                                               <?php echo $is_assigned ? 'checked' : ''; ?> style="margin-right: 10px;">
                                        <strong><?php echo $subject['class_name'] . ' - ' . $subject['section']; ?></strong>
                                        <span style="margin-left: 15px; color: #667eea;"><?php echo $subject['subject_name']; ?></span>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" name="assign_classes" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Assignments
                        </button>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
