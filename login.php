<?php
session_start();
require_once 'db_config.php';

$error = '';
$success = '';

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

// Handle login form submission
if ($_POST) {
    $role = trim($_POST['role'] ?? '');
    
    if (empty($role)) {
        $error = 'Please select your role!';
    } else {
        try {
            if ($role === 'admin') {
                $name = trim($_POST['name'] ?? '');
                $password = trim($_POST['password'] ?? '');
                
                if (empty($name) || empty($password)) {
                    $error = 'Please fill in all fields!';
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM admin WHERE name = ? AND password = ?");
                    $stmt->execute([$name, $password]);
                    $admin = $stmt->fetch();
                    
                    if ($admin) {
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_name'] = $admin['name'];
                        $_SESSION['role'] = 'admin';
                        header('Location: admin_dashboard.php');
                        exit();
                    } else {
                        $error = 'Invalid admin credentials!';
                    }
                }
            } elseif ($role === 'teacher') {
                $email = trim($_POST['email'] ?? '');
                $password = trim($_POST['userPassword'] ?? '');
                
                if (empty($email) || empty($password)) {
                    $error = 'Please fill in all fields!';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address!';
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE email = ? AND password = ?");
                    $stmt->execute([$email, $password]);
                    $teacher = $stmt->fetch();
                    
                    if ($teacher) {
                        $_SESSION['teacher_id'] = $teacher['id'];
                        $_SESSION['teacher_name'] = $teacher['full_name'];
                        $_SESSION['role'] = 'teacher';
                        header('Location: teacher_dashboard.php');
                        exit();
                    } else {
                        $error = 'Invalid teacher credentials!';
                    }
                }
            } elseif ($role === 'student') {
                $email = trim($_POST['email'] ?? '');
                $password = trim($_POST['userPassword'] ?? '');
                
                if (empty($email) || empty($password)) {
                    $error = 'Please fill in all fields!';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address!';
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND password = ?");
                    $stmt->execute([$email, $password]);
                    $student = $stmt->fetch();
                    
                    if ($student) {
                        $_SESSION['student_id'] = $student['id'];
                        $_SESSION['student_name'] = $student['full_name'];
                        $_SESSION['role'] = 'student';
                        header('Location: student_dashboard.php');
                        exit();
                    } else {
                        $error = 'Invalid student credentials!';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System - Login</title>
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
            max-width: 450px;
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

        .login-header p {
            opacity: 0.9;
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

        /* Prevent browser autofill styles */
        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:hover,
        .form-control:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--text);
            -webkit-box-shadow: 0 0 0px 1000px var(--white) inset;
            transition: background-color 5000s ease-in-out 0s;
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
            gap: 8px;        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
        }

        .btn-loading {
            background: var(--primary-dark);
            position: relative;
        }

        .btn-loading .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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

        .alert-success {
            background: #d1fae5;
            color: var(--success);
            border: 1px solid #a7f3d0;
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .admin-info {
            background: var(--secondary);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: var(--text-light);
            text-align: center;
        }

        .demo-credentials {
            background: var(--secondary);
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 0.75rem;
            color: var(--text-light);
            text-align: center;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 480px) {
            .role-selector {
                grid-template-columns: 1fr;
            }
            
            .login-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-graduation-cap"></i> FISC-Manage</h1>
            <p>School Management System</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
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
                    <div class="demo-credentials">
                        <strong>Demo Admin Credentials Pre-filled - Click Login to Continue</strong>
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

            <div class="admin-info">
                <strong>Available Demo Credentials:</strong><br>
                Admin: name="admin", password="admin123"<br>
                <small>Teacher and Student: Use credentials from your database</small>
            </div>

            <div class="login-footer">
                <p>Select your role to continue</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleOptions = document.querySelectorAll('.role-option');
            const selectedRoleInput = document.getElementById('selectedRole');
            const adminFields = document.getElementById('adminFields');
            const userFields = document.getElementById('userFields');

            // Function to show appropriate fields
            function showFieldsForRole(role) {
                // Hide all fields first
                adminFields.classList.add('hidden');
                userFields.classList.add('hidden');
                
                // Show appropriate fields
                if (role === 'admin') {
                    adminFields.classList.remove('hidden');
                } else {
                    userFields.classList.remove('hidden');
                }
                
                selectedRoleInput.value = role;
            }

            // Role selection
            roleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove active class from all options
                    roleOptions.forEach(opt => opt.classList.remove('active'));
                    
                    // Add active class to clicked option
                    this.classList.add('active');
                    
                    const role = this.getAttribute('data-role');
                    showFieldsForRole(role);
                });
            });

            // Auto-select admin role on page load
            const adminOption = document.querySelector('.role-option[data-role="admin"]');
            if (adminOption) {
                adminOption.classList.add('active');
                showFieldsForRole('admin');
            }
        });
    </script>
</body>
</html>
