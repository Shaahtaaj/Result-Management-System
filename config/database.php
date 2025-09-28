<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_management');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Function to check if user is logged in
function check_login($role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }
    
    if ($role && $_SESSION['role'] !== $role) {
        header("Location: ../index.php");
        exit();
    }
}

// Function to get school settings
function get_school_settings() {
    global $conn;
    $query = "SELECT * FROM school_settings WHERE id = 1";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}
?>
