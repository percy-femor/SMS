<?php
session_start();
require_once 'db_config.php';

$error = '';
$success = '';

// Check if user is already logged in and redirect
if (isset($_SESSION['role'])) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin_dashboard.php');
            exit();
        case 'teacher':
            header('Location: teacher_dashboard.php');
            exit();
        case 'student':
            header('Location: student_dashboard.php');
            exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "<div style='background: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>Debug Info:</strong><br>";
    echo "Role: '$role'<br>";
    echo "Name: '$name'<br>";
    echo "Password: '$password'<br>";
    echo "</div>";
    
    if ($role == 'admin') {
        if (empty($name) || empty($password)) {
            $error = 'Please fill in all fields!';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM admin WHERE name = ?");
                $stmt->execute([$name]);
                $admin = $stmt->fetch();
                
                echo "<div style='background: #fff3e0; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "<strong>Database Query Result:</strong><br>";
                if ($admin) {
                    echo "Admin found: ID={$admin['id']}, Name='{$admin['name']}', Password='{$admin['password']}'<br>";
                } else {
                    echo "No admin found with name '$name'<br>";
                }
                echo "</div>";
                
                if ($admin && $password === $admin['password']) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                    echo "<strong>✓ LOGIN SUCCESSFUL!</strong><br>";
                    echo "Session created. Redirecting to dashboard...<br>";
                    echo "</div>";
                    
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid admin credentials!';
                    echo "<div style='background: #ffebee; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                    echo "<strong>✗ Login Failed:</strong><br>";
                    echo "Password comparison: '$password' === '{$admin['password']}' = " . ($password === $admin['password'] ? 'true' : 'false') . "<br>";
                    echo "</div>";
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Please select admin role';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Admin Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        button { width: 100%; padding: 12px; background: #007cba; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #005a87; }
        .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #e8f5e8; color: #2e7d32; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Admin Login Test (No JavaScript)</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="role" value="admin">
            
            <div class="form-group">
                <label for="name">Admin Name:</label>
                <input type="text" id="name" name="name" value="admin" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" value="admin123" required>
            </div>
            
            <button type="submit">Login as Admin</button>
        </form>
        
        <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
            <strong>Test Credentials:</strong><br>
            Name: admin<br>
            Password: admin123
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="login.php" style="color: #007cba;">← Back to Main Login</a>
        </div>
    </div>
</body>
</html>
