<?php
// Database setup script for admin login
require_once 'db_config.php';

echo "<h1>Admin Setup Script</h1>";

try {
    // Test database connection
    echo "<h2>1. Testing Database Connection</h2>";
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✓ Database connection successful</p>";

    // Check if admin table exists
    echo "<h2>2. Checking Admin Table</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Admin table exists</p>";

        // Check table structure
        $stmt = $pdo->query("DESCRIBE admin");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Table structure:</p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";

        // Check existing admin records
        echo "<h2>3. Checking Existing Admin Records</h2>";
        $stmt = $pdo->query("SELECT * FROM admin");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($admins) > 0) {
            echo "<p style='color: green;'>✓ Found " . count($admins) . " admin record(s)</p>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Password</th></tr>";
            foreach ($admins as $admin) {
                echo "<tr>";
                echo "<td>{$admin['id']}</td>";
                echo "<td>{$admin['name']}</td>";
                echo "<td>{$admin['password']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠ No admin records found</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Admin table does not exist</p>";
        echo "<h2>Creating Admin Table</h2>";

        // Create admin table
        $sql = "CREATE TABLE admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ Admin table created successfully</p>";
    }

    // Ensure admin record exists
    echo "<h2>4. Ensuring Admin Record Exists</h2>";
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE name = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();

    if (!$admin) {
        echo "<p style='color: orange;'>⚠ Admin record not found, creating one...</p>";

        $stmt = $pdo->prepare("INSERT INTO admin (name, password) VALUES (?, ?)");
        $stmt->execute(['admin', 'admin123']);
        echo "<p style='color: green;'>✓ Admin record created: name='admin', password='admin123'</p>";
    } else {
        echo "<p style='color: green;'>✓ Admin record exists</p>";
        echo "<p>Name: {$admin['name']}</p>";
        echo "<p>Password: {$admin['password']}</p>";

        // Update password if it's not 'admin123'
        if ($admin['password'] !== 'admin123') {
            echo "<p style='color: orange;'>⚠ Updating password to 'admin123'...</p>";
            $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE name = ?");
            $stmt->execute(['admin123', 'admin']);
            echo "<p style='color: green;'>✓ Password updated to 'admin123'</p>";
        }
    }

    // Test login
    echo "<h2>5. Testing Login</h2>";
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE name = ? AND password = ?");
    $stmt->execute(['admin', 'admin123']);
    $test_admin = $stmt->fetch();

    if ($test_admin) {
        echo "<p style='color: green; font-size: 18px;'>✓ LOGIN TEST SUCCESSFUL!</p>";
        echo "<p>You should now be able to login with:</p>";
        echo "<ul>";
        echo "<li><strong>Name:</strong> admin</li>";
        echo "<li><strong>Password:</strong> admin123</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Login test failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<p><a href='login.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Login Now</a></p>";
echo "<p><a href='test_login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Login Test</a></p>";
