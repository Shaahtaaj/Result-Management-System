<?php
include 'config/database.php';

echo "<h2>Database Connection Test</h2>";
if ($conn) {
    echo "✅ Database connected successfully<br><br>";
} else {
    echo "❌ Database connection failed<br><br>";
    exit;
}

echo "<h2>Users in Database:</h2>";
$query = "SELECT id, username, role FROM users ORDER BY role, username";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ No users found in database<br><br>";
}

echo "<h2>Teachers in Database:</h2>";
$query = "SELECT t.id, t.name, u.username FROM teachers t JOIN users u ON t.user_id = u.id";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Teacher ID</th><th>Name</th><th>Username</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ No teachers found in database<br><br>";
}

echo "<h2>Test Login Credentials:</h2>";
echo "<strong>Admin:</strong> admin / admin123<br>";
echo "<strong>Teacher:</strong> teacher1 / teacher123<br>";
echo "<strong>Student:</strong> STU001 / STU001<br><br>";

echo "<h2>Password Hash Test:</h2>";
$test_password = 'teacher123';
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // Current hash in DB
echo "Testing password 'teacher123' against current hash: ";
if (password_verify($test_password, $hash)) {
    echo "✅ MATCH";
} else {
    echo "❌ NO MATCH";
}
echo "<br>";

$test_password2 = 'password';
echo "Testing password 'password' against current hash: ";
if (password_verify($test_password2, $hash)) {
    echo "✅ MATCH";
} else {
    echo "❌ NO MATCH";
}
echo "<br><br>";

echo "<p><strong>Note:</strong> Delete this file after debugging for security!</p>";
?>
