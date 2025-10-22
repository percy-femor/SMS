<?php
session_start();
require_once 'db_config.php';

echo "<h1>Simple Login Test</h1>";

// Test database connection
try {
    $stmt = $pdo->query("SELECT * FROM admin");
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Database connected. Admin found: " . $admin['name'] . "</p>";
        echo "<p>Stored password: " . $admin['password'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ No admin found in database</p>";
    }
} catch (Exception $e) {
    die("<p style='color: red;'>Database error: " . $e->getMessage() . "</p>");
}

// Simple form processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<p>Submitted - Name: $name, Password: $password</p>";
    
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE name = ? AND password = ?");
    $stmt->execute([$name, $password]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p style='color: green; font-size: 20px;'>✓ LOGIN SUCCESSFUL!</p>";
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        echo "<p><a href='admin_dashboard.php'>Go to Dashboard</a></p>";
    } else {
        echo "<p style='color: red;'>✗ Login failed</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        form { margin: 20px 0; padding: 20px; border: 1px solid #ccc; }
        input, button { display: block; margin: 10px 0; padding: 10px; }
    </style>
</head>
<body>
    <h2>Test Admin Login</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Admin name" value="admin" required>
        <input type="password" name="password" placeholder="Password" value="admin123" required>
        <button type="submit">Login</button>
    </form>
    
    <h3>Debug Info:</h3>
    <p>Make sure your admin table has: name='admin', password='admin123'</p>
</body>
</html>