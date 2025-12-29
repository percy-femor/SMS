<?php
// Database setup script
require_once 'db_config.php';

echo "<h1>🗄️ Database Setup</h1>";

try {
    // Test connection
    echo "<h2>1. Testing Database Connection</h2>";
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✓ Database connected successfully</p>";

    // Check if admin table exists
    echo "<h2>2. Checking Admin Table</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Admin table exists</p>";

        // Check admin records
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
            echo "<h3>Creating admin record...</h3>";

            $stmt = $pdo->prepare("INSERT INTO admin (name, password) VALUES (?, ?)");
            $stmt->execute(['admin', 'admin123']);
            echo "<p style='color: green;'>✓ Admin record created: name='admin', password='admin123'</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Admin table does not exist</p>";
        echo "<h3>Creating admin table...</h3>";

        $sql = "CREATE TABLE admin (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL
        )";

        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ Admin table created</p>";

        // Insert admin record
        $stmt = $pdo->prepare("INSERT INTO admin (name, password) VALUES (?, ?)");
        $stmt->execute(['admin', 'admin123']);
        echo "<p style='color: green;'>✓ Admin record created: name='admin', password='admin123'</p>";
    }

    // Test login
    echo "<h2>3. Testing Login</h2>";
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE name = ? AND password = ?");
    $stmt->execute(['admin', 'admin123']);
    $test_admin = $stmt->fetch();

    if ($test_admin) {
        echo "<p style='color: green; font-size: 18px;'>✓ LOGIN TEST SUCCESSFUL!</p>";
        echo "<p>You can now login with:</p>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> admin</li>";
        echo "<li><strong>Password:</strong> admin123</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Login test failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🚀 Ready to Login</h2>";
echo "<p><a href='login.php' style='background: #007cba; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Go to Login Page</a></p>";
echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Go to Login Page</a></p>";
