<?php
session_start();
include '../config/database.php';
check_login('admin');

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    $school_name = sanitize_input($_POST['school_name']);
    $motto = sanitize_input($_POST['motto']);
    $address = sanitize_input($_POST['address']);
    $phone = sanitize_input($_POST['phone']);
    $email = sanitize_input($_POST['email']);
    $website = sanitize_input($_POST['website']);
    $principal_name = sanitize_input($_POST['principal_name']);
    $academic_year = sanitize_input($_POST['academic_year']);
    
    // Handle logo upload
    $logo_filename = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $upload_dir = '../uploads/school/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logo_filename = 'logo_' . time() . '.' . $file_extension;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo_filename)) {
            // Logo uploaded successfully
        } else {
            $error = "Failed to upload logo.";
        }
    }
    
    // Handle signature upload
    $signature_filename = '';
    if (isset($_FILES['principal_signature']) && $_FILES['principal_signature']['error'] === 0) {
        $upload_dir = '../uploads/school/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['principal_signature']['name'], PATHINFO_EXTENSION);
        $signature_filename = 'signature_' . time() . '.' . $file_extension;
        
        if (move_uploaded_file($_FILES['principal_signature']['tmp_name'], $upload_dir . $signature_filename)) {
            // Signature uploaded successfully
        } else {
            $error = "Failed to upload signature.";
        }
    }
    
    if (empty($error)) {
        // Check if settings exist
        $check_query = "SELECT id FROM school_settings WHERE id = 1";
        $check_result = $conn->query($check_query);
        
        if ($check_result->num_rows > 0) {
            // Update existing settings
            $update_query = "UPDATE school_settings SET 
                school_name = ?, motto = ?, address = ?, phone = ?, email = ?, 
                website = ?, principal_name = ?, academic_year = ?";
            
            $params = [$school_name, $motto, $address, $phone, $email, $website, $principal_name, $academic_year];
            $types = "ssssssss";
            
            if ($logo_filename) {
                $update_query .= ", logo = ?";
                $params[] = $logo_filename;
                $types .= "s";
            }
            
            if ($signature_filename) {
                $update_query .= ", principal_signature = ?";
                $params[] = $signature_filename;
                $types .= "s";
            }
            
            $update_query .= " WHERE id = 1";
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $message = "School settings updated successfully!";
            } else {
                $error = "Failed to update settings.";
            }
        } else {
            // Insert new settings
            $insert_query = "INSERT INTO school_settings 
                (school_name, motto, address, phone, email, website, principal_name, academic_year, logo, principal_signature) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ssssssssss", $school_name, $motto, $address, $phone, $email, $website, $principal_name, $academic_year, $logo_filename, $signature_filename);
            
            if ($stmt->execute()) {
                $message = "School settings saved successfully!";
            } else {
                $error = "Failed to save settings.";
            }
        }
    }
}

// Get current settings
$settings = get_school_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>School Settings</h1>
                    <p>Manage school information and configuration</p>
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
                <li><a href="school-settings.php" class="active"><i class="fas fa-cog"></i> School Settings</a></li>
                <li><a href="classes.php"><i class="fas fa-chalkboard"></i> Classes</a></li>
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
        
        <div class="form-container">
            <h3><i class="fas fa-school"></i> School Information</h3>
            
            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="school_name">School Name *</label>
                        <input type="text" id="school_name" name="school_name" 
                               value="<?php echo $settings ? $settings['school_name'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="academic_year">Academic Year *</label>
                        <input type="text" id="academic_year" name="academic_year" 
                               value="<?php echo $settings ? $settings['academic_year'] : ''; ?>" 
                               placeholder="e.g., 2024-25" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="motto">School Motto</label>
                    <input type="text" id="motto" name="motto" 
                           value="<?php echo $settings ? $settings['motto'] : ''; ?>" 
                           placeholder="e.g., Excellence in Education, Character in Life">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo $settings ? $settings['address'] : ''; ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" 
                               value="<?php echo $settings ? $settings['phone'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo $settings ? $settings['email'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" 
                               value="<?php echo $settings ? $settings['website'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="principal_name">Principal Name</label>
                    <input type="text" id="principal_name" name="principal_name" 
                           value="<?php echo $settings ? $settings['principal_name'] : ''; ?>">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="logo">School Logo</label>
                        <input type="file" id="logo" name="logo" accept="image/*">
                        <?php if ($settings && $settings['logo']): ?>
                            <div style="margin-top: 10px;">
                                <img src="../uploads/school/<?php echo $settings['logo']; ?>" 
                                     alt="Current Logo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 10px;">
                                <p style="font-size: 0.9rem; color: #7f8c8d;">Current Logo</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="principal_signature">Principal Signature</label>
                        <input type="file" id="principal_signature" name="principal_signature" accept="image/*">
                        <?php if ($settings && $settings['principal_signature']): ?>
                            <div style="margin-top: 10px;">
                                <img src="../uploads/school/<?php echo $settings['principal_signature']; ?>" 
                                     alt="Current Signature" style="width: 150px; height: 75px; object-fit: cover; border-radius: 5px;">
                                <p style="font-size: 0.9rem; color: #7f8c8d;">Current Signature</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
