<?php
// Final test script to verify login functionality
session_start();
require_once 'db_config.php';

echo "<h1>🔧 Final Login Test</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin");
    $count = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Database connected successfully</p>";
    echo "<p>Admin records found: $count</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Admin Record Check
echo "<h2>2. Admin Record Check</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE name = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Admin record found</p>";
        echo "<p>ID: {$admin['id']}, Name: '{$admin['name']}', Password: '{$admin['password']}'</p>";
        
        // Test 3: Password Verification
        echo "<h2>3. Password Verification Test</h2>";
        if ($admin['password'] === 'admin123') {
            echo "<p style='color: green;'>✓ Password matches expected value</p>";
        } else {
            echo "<p style='color: red;'>✗ Password mismatch!</p>";
            echo "<p>Expected: 'admin123'</p>";
            echo "<p>Found: '{$admin['password']}'</p>";
        }
        
        // Test 4: Session Creation
        echo "<h2>4. Session Creation Test</h2>";
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['role'] = 'admin';
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        echo "<p style='color: green;'>✓ Session variables created successfully</p>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        // Test 5: Dashboard Access
        echo "<h2>5. Dashboard Access Test</h2>";
        echo "<p><a href='admin_dashboard.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Admin Dashboard Access</a></p>";
        
    } else {
        echo "<p style='color: red;'>✗ No admin record found</p>";
        echo "<p>Please run <a href='setup_admin.php'>setup_admin.php</a> first</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Query error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🎯 Ready to Test Login</h2>";
echo "<p><strong>Test the fixed login page:</strong></p>";
echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🚀 Go to Login Page</a></p>";

echo "<p><strong>Expected behavior:</strong></p>";
echo "<ul>";
echo "<li>Select 'Admin' role (should be pre-selected)</li>";
echo "<li>Name field should be pre-filled with 'admin'</li>";
echo "<li>Password field should be pre-filled with 'admin123'</li>";
echo "<li>Click 'Login to Dashboard'</li>";
echo "<li>Should redirect to admin_dashboard.php</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>🔧 Troubleshooting</h2>";
echo "<p>If login still doesn't work:</p>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl+F5)</li>";
echo "<li>Check browser console for JavaScript errors (F12)</li>";
echo "<li>Try the <a href='simple_admin_test.php'>simple test page</a></li>";
echo "<li>Run <a href='setup_admin.php'>database setup</a></li>";
echo "</ol>";
?>


