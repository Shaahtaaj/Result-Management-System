<?php
session_start();
include '../config/database.php';
check_login('admin');

$message = '';
$error = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_student'])) {
        $name = sanitize_input($_POST['name']);
        $roll_no = sanitize_input($_POST['roll_no']);
        $class_id = (int)$_POST['class_id'];
        $parent_name = sanitize_input($_POST['parent_name']);
        $parent_contact = sanitize_input($_POST['parent_contact']);
        $parent_email = sanitize_input($_POST['parent_email']);
        $address = sanitize_input($_POST['address']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $admission_date = $_POST['admission_date'];
        
        // Check if roll number already exists in students table
        $check_query = "SELECT id FROM students WHERE roll_no = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $roll_no);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        // Check if username already exists in users table
        $username = $roll_no;
        $check_user_query = "SELECT id FROM users WHERE username = ?";
        $check_user_stmt = $conn->prepare($check_user_query);
        $check_user_stmt->bind_param("s", $username);
        $check_user_stmt->execute();
        $check_user_result = $check_user_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Roll number already exists!";
        } else if ($check_user_result->num_rows > 0) {
            $error = "Username '$username' already exists in the system!";
        } else {
            // Handle photo upload
            $photo_filename = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
                $upload_dir = '../uploads/students/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo_filename = $roll_no . '_' . time() . '.' . $file_extension;
                
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_filename)) {
                    $error = "Failed to upload photo.";
                }
            }
            
            if (empty($error)) {
                // Create user account for student
                $username = $roll_no;
                $password = password_hash($roll_no, PASSWORD_DEFAULT); // Default password is roll number
                
                $user_query = "INSERT INTO users (username, password, role) VALUES (?, ?, 'student')";
                $user_stmt = $conn->prepare($user_query);
                $user_stmt->bind_param("ss", $username, $password);
                
                if ($user_stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Insert student record
                    $student_query = "INSERT INTO students (user_id, name, roll_no, photo, class_id, parent_name, parent_contact, parent_email, address, date_of_birth, gender, admission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $student_stmt = $conn->prepare($student_query);
                    $student_stmt->bind_param("isssississs", $user_id, $name, $roll_no, $photo_filename, $class_id, $parent_name, $parent_contact, $parent_email, $address, $date_of_birth, $gender, $admission_date);
                    
                    if ($student_stmt->execute()) {
                        $message = "Student added successfully! Login credentials - Username: $roll_no, Password: $roll_no";
                        $action = 'list';
                    } else {
                        $error = "Failed to add student.";
                    }
                } else {
                    $error = "Failed to create user account.";
                }
            }
        }
    }
    
    if (isset($_POST['edit_student'])) {
        $name = sanitize_input($_POST['name']);
        $parent_name = sanitize_input($_POST['parent_name']);
        $parent_contact = sanitize_input($_POST['parent_contact']);
        $parent_email = sanitize_input($_POST['parent_email']);
        $address = sanitize_input($_POST['address']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        
        // Handle photo upload
        $photo_update = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $upload_dir = '../uploads/students/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Get student's roll number for filename
            $roll_query = "SELECT roll_no FROM students WHERE id = ?";
            $roll_stmt = $conn->prepare($roll_query);
            $roll_stmt->bind_param("i", $student_id);
            $roll_stmt->execute();
            $roll_result = $roll_stmt->get_result();
            $roll_data = $roll_result->fetch_assoc();
            
            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_filename = $roll_data['roll_no'] . '_' . time() . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_filename)) {
                $photo_update = ", photo = '$photo_filename'";
            }
        }
        
        $update_query = "UPDATE students SET name = ?, parent_name = ?, parent_contact = ?, parent_email = ?, address = ?, date_of_birth = ?, gender = ? $photo_update WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssssssi", $name, $parent_name, $parent_contact, $parent_email, $address, $date_of_birth, $gender, $student_id);
        
        if ($update_stmt->execute()) {
            $message = "Student updated successfully!";
            $action = 'list';
        } else {
            $error = "Failed to update student.";
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $delete_id = (int)$_GET['delete'];
    
    // Get user_id first
    $user_query = "SELECT user_id FROM students WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $delete_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    
    // Delete student (will cascade to user due to foreign key)
    $delete_query = "DELETE FROM students WHERE id = ?";
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
        $message = "Student deleted successfully!";
    } else {
        $error = "Failed to delete student.";
    }
}

// Get classes for dropdown
$classes_query = "SELECT * FROM classes ORDER BY class_name, section";
$classes_result = $conn->query($classes_query);

// Get students list
if ($action === 'list') {
    $students_query = "
        SELECT s.*, c.class_name, c.section 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.id 
        ORDER BY s.roll_no
    ";
    $students_result = $conn->query($students_query);
}

// Get student data for editing
if ($action === 'edit' && $student_id > 0) {
    $student_query = "SELECT * FROM students WHERE id = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    $student_data = $student_result->fetch_assoc();
    
    if (!$student_data) {
        $error = "Student not found!";
        $action = 'list';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Students Management</h1>
                    <p>Manage student records and information</p>
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
                <li><a href="students.php" class="active"><i class="fas fa-graduation-cap"></i> Students</a></li>
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
        
        <?php if ($action === 'list'): ?>
            <div class="form-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3><i class="fas fa-graduation-cap"></i> Students List</h3>
                    <a href="?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Student
                    </a>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Parent Name</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($students_result->num_rows > 0): ?>
                                <?php while ($row = $students_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if ($row['photo']): ?>
                                                <img src="../uploads/students/<?php echo $row['photo']; ?>" 
                                                     alt="Student Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="width: 40px; height: 40px; background: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-user" style="color: #667eea;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $row['roll_no']; ?></td>
                                        <td><?php echo $row['name']; ?></td>
                                        <td><?php echo $row['class_name'] . ' - ' . $row['section']; ?></td>
                                        <td><?php echo $row['parent_name']; ?></td>
                                        <td><?php echo $row['parent_contact']; ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;" 
                                               onclick="return confirm('Are you sure you want to delete this student?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add'): ?>
            <div class="form-container">
                <h3><i class="fas fa-user-plus"></i> Add New Student</h3>
                
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="name">Student Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="roll_no">Roll Number *</label>
                            <input type="text" id="roll_no" name="roll_no" required>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
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
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth">
                        </div>
                        
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="photo">Student Photo</label>
                        <input type="file" id="photo" name="photo" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="admission_date">Admission Date</label>
                        <input type="date" id="admission_date" name="admission_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <h4 style="margin: 30px 0 20px 0; color: #2c3e50;">Parent/Guardian Information</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="parent_name">Parent/Guardian Name</label>
                            <input type="text" id="parent_name" name="parent_name">
                        </div>
                        
                        <div class="form-group">
                            <label for="parent_contact">Contact Number</label>
                            <input type="text" id="parent_contact" name="parent_contact">
                        </div>
                        
                        <div class="form-group">
                            <label for="parent_email">Email Address</label>
                            <input type="email" id="parent_email" name="parent_email">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" name="add_student" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Student
                        </button>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'edit' && isset($student_data)): ?>
            <div class="form-container">
                <h3><i class="fas fa-edit"></i> Edit Student</h3>
                
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="name">Student Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo $student_data['name']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Roll Number</label>
                            <input type="text" value="<?php echo $student_data['roll_no']; ?>" readonly style="background: #f8f9fa;">
                            <small style="color: #7f8c8d;">Roll number cannot be changed</small>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $student_data['date_of_birth']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo $student_data['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $student_data['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $student_data['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="photo">Student Photo</label>
                        <input type="file" id="photo" name="photo" accept="image/*">
                        <?php if ($student_data['photo']): ?>
                            <div style="margin-top: 10px;">
                                <img src="../uploads/students/<?php echo $student_data['photo']; ?>" 
                                     alt="Current Photo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 10px;">
                                <p style="font-size: 0.9rem; color: #7f8c8d;">Current Photo</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h4 style="margin: 30px 0 20px 0; color: #2c3e50;">Parent/Guardian Information</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="parent_name">Parent/Guardian Name</label>
                            <input type="text" id="parent_name" name="parent_name" value="<?php echo $student_data['parent_name']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="parent_contact">Contact Number</label>
                            <input type="text" id="parent_contact" name="parent_contact" value="<?php echo $student_data['parent_contact']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="parent_email">Email Address</label>
                            <input type="email" id="parent_email" name="parent_email" value="<?php echo $student_data['parent_email']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo $student_data['address']; ?></textarea>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" name="edit_student" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Student
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
