<?php
// Simple test script to verify login functionality
session_start();
require_once 'db_config.php';

echo "<h1>Login Test Results</h1>";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin");
    $admin_count = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Database connected. Admin records: $admin_count</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Test admin login
$test_name = 'admin';
$test_password = 'admin123';

echo "<h2>Testing Admin Login</h2>";
echo "<p>Testing with: name='$test_name', password='$test_password'</p>";

try {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE name = ?");
    $stmt->execute([$test_name]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Admin found in database</p>";
        echo "<p>Stored password: " . $admin['password'] . "</p>";
        
        if ($test_password === $admin['password']) {
            echo "<p style='color: green; font-size: 18px;'>✓ PASSWORD MATCH - Login should work!</p>";
            
            // Test session creation
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['role'] = 'admin';
            
            echo "<p style='color: green;'>✓ Session variables set successfully</p>";
            echo "<p><a href='admin_dashboard.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Dashboard Access</a></p>";
        } else {
            echo "<p style='color: red;'>✗ Password mismatch</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Admin not found in database</p>";
        echo "<p>Please ensure you have an admin record with name='admin' and password='admin123'</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Session Information</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
echo "<p><a href='logout.php'>Logout</a></p>";
echo "<p><a href='simple_login.php'>Simple Login Test</a></p>";
?>
