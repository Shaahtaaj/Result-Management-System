<?php
session_start();
include '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_POST) {
    $roll_no = sanitize_input($_POST['roll_no']);
    $password = $_POST['password'];
    
    if (!empty($roll_no) && !empty($password)) {
        $query = "SELECT u.*, s.name as student_name, s.roll_no, s.class_id 
                  FROM users u 
                  JOIN students s ON u.id = s.user_id 
                  WHERE s.roll_no = ? AND u.role = 'student'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $roll_no);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // For demo purposes, password is roll number
            // In production, use password_verify() with hashed passwords
            if ($password === $roll_no || password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['student_name'] = $user['student_name'];
                $_SESSION['roll_no'] = $user['roll_no'];
                $_SESSION['class_id'] = $user['class_id'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "Invalid roll number!";
        }
    } else {
        $error = "Please fill in all fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - School Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <form method="POST" class="login-form">
            <div class="text-center mb-20">
                <i class="fas fa-graduation-cap" style="font-size: 3rem; color: #667eea;"></i>
                <h2>Student/Parent Login</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="roll_no">Roll Number</label>
                <input type="text" id="roll_no" name="roll_no" placeholder="Enter your roll number" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
            
            <div class="text-center mt-20">
                <a href="../index.php">← Back to Home</a>
            </div>
            
            <div class="text-center mt-20" style="font-size: 0.9rem; color: #7f8c8d;">
                <strong>Demo Credentials:</strong><br>
                Roll Number: STU001<br>
                Password: STU001<br>
                <small>(Default password is your roll number)</small>
            </div>
        </form>
    </div>
</body>
</html>
