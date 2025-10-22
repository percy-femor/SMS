<?php
session_start();
require_once 'db_config.php';

$error = '';
$success = '';

// Debug: Show what's being submitted
if ($_POST) {
    echo "<div style='background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔍 Debug: Form Data Received</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    echo "</div>";
}

// Check if already logged in
if (isset($_SESSION['admin_id']) || isset($_SESSION['teacher_id']) || isset($_SESSION['student_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

// Handle login form submission
if ($_POST) {
    $role = trim($_POST['role'] ?? '');
    
    echo "<div style='background: #fff3e0; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔍 Debug: Processing Login</h3>";
    echo "Role: '$role'<br>";
    
    if (empty($role)) {
        $error = 'Please select your role!';
        echo "Error: No role selected<br>";
    } else {
        try {
            if ($role === 'admin') {
                $name = trim($_POST['name'] ?? '');
                $password = trim($_POST['password'] ?? '');
                
                echo "Admin - Name: '$name', Password: '$password'<br>";
                
                if (empty($name) || empty($password)) {
                    $error = 'Please fill in all fields!';
                    echo "Error: Empty fields<br>";
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM admin WHERE name = ? AND password = ?");
                    $stmt->execute([$name, $password]);
                    $admin = $stmt->fetch();
                    
                    if ($admin) {
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_name'] = $admin['name'];
                        $_SESSION['role'] = 'admin';
                        echo "✅ Login successful! Redirecting...<br>";
                        header('Location: admin_dashboard.php');
                        exit();
                    } else {
                        $error = 'Invalid admin credentials!';
                        echo "Error: Invalid credentials<br>";
                    }
                }
            } elseif ($role === 'teacher') {
                $email = trim($_POST['email'] ?? '');
                $password = trim($_POST['userPassword'] ?? '');
                
                echo "Teacher - Email: '$email', Password: '$password'<br>";
                
                if (empty($email) || empty($password)) {
                    $error = 'Please fill in all fields!';
                    echo "Error: Empty fields<br>";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address!';
                    echo "Error: Invalid email<br>";
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE email = ? AND password = ?");
                    $stmt->execute([$email, $password]);
                    $teacher = $stmt->fetch();
                    
                    if ($teacher) {
                        $_SESSION['teacher_id'] = $teacher['id'];
                        $_SESSION['teacher_name'] = $teacher['full_name'];
                        $_SESSION['role'] = 'teacher';
                        echo "✅ Login successful! Redirecting...<br>";
                        header('Location: teacher_dashboard.php');
                        exit();
                    } else {
                        $error = 'Invalid teacher credentials!';
                        echo "Error: Invalid credentials<br>";
                    }
                }
            } elseif ($role === 'student') {
                $email = trim($_POST['email'] ?? '');
                $password = trim($_POST['userPassword'] ?? '');
                
                echo "Student - Email: '$email', Password: '$password'<br>";
                
                if (empty($email) || empty($password)) {
                    $error = 'Please fill in all fields!';
                    echo "Error: Empty fields<br>";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address!';
                    echo "Error: Invalid email<br>";
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND password = ?");
                    $stmt->execute([$email, $password]);
                    $student = $stmt->fetch();
                    
                    if ($student) {
                        $_SESSION['student_id'] = $student['id'];
                        $_SESSION['student_name'] = $student['full_name'];
                        $_SESSION['role'] = 'student';
                        echo "✅ Login successful! Redirecting...<br>";
                        header('Location: student_dashboard.php');
                        exit();
                    } else {
                        $error = 'Invalid student credentials!';
                        echo "Error: Invalid credentials<br>";
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
            echo "Error: " . $e->getMessage() . "<br>";
        }
    }
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Login - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #f0f9ff;
            --accent: #f59e0b;
            --text: #1f2937;
            --text-light: #6b7280;
            --white: #ffffff;
            --gray: #f8fafc;
            --border: #e5e7eb;
            --error: #ef4444;
            --success: #10b981;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }

        .login-header {
            background: var(--primary);
            color: var(--white);
            padding: 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-body {
            padding: 30px;
        }

        .role-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 25px;
        }

        .role-option {
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--white);
        }

        .role-option:hover {
            border-color: var(--primary);
        }

        .role-option.active {
            border-color: var(--primary);
            background: var(--secondary);
            color: var(--primary);
        }

        .role-option i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .alert-error {
            background: #fee2e2;
            color: var(--error);
            border: 1px solid #fecaca;
        }

        .hidden {
            display: none;
        }

        .debug-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-bug"></i> Debug Login</h1>
            <p>School Management System - Debug Mode</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off" novalidate>
                <div class="role-selector">
                    <div class="role-option" data-role="admin">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin</span>
                    </div>
                    <div class="role-option" data-role="teacher">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Teacher</span>
                    </div>
                    <div class="role-option" data-role="student">
                        <i class="fas fa-user-graduate"></i>
                        <span>Student</span>
                    </div>
                </div>

                <input type="hidden" name="role" id="selectedRole" value="admin">

                <!-- Admin Login Fields -->
                <div id="adminFields">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Admin Name</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Enter admin name" value="admin" autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="adminPassword"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="adminPassword" name="password" class="form-control" placeholder="Enter password" value="admin123" autocomplete="current-password">
                    </div>
                </div>

                <!-- Teacher/Student Login Fields -->
                <div id="userFields" class="hidden">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label for="userPassword"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="userPassword" name="userPassword" class="form-control" placeholder="Enter password" autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> <span>Login to Dashboard</span>
                </button>
            </form>

            <div class="debug-info">
                <strong>🔍 Debug Mode Active</strong><br>
                This page shows exactly what data is being submitted and processed.<br>
                <small>Admin credentials are pre-filled for testing</small>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleOptions = document.querySelectorAll('.role-option');
            const selectedRoleInput = document.getElementById('selectedRole');
            const adminFields = document.getElementById('adminFields');
            const userFields = document.getElementById('userFields');

            function showFieldsForRole(role) {
                adminFields.classList.add('hidden');
                userFields.classList.add('hidden');
                
                if (role === 'admin') {
                    adminFields.classList.remove('hidden');
                } else {
                    userFields.classList.remove('hidden');
                }
                
                selectedRoleInput.value = role;
            }

            roleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    roleOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    
                    const role = this.getAttribute('data-role');
                    showFieldsForRole(role);
                });
            });

            const adminOption = document.querySelector('.role-option[data-role="admin"]');
            if (adminOption) {
                adminOption.classList.add('active');
                showFieldsForRole('admin');
            }
        });
    </script>
</body>
</html>
