<?php
session_start();
require_once 'db_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'];
    
    if ($role == 'admin') {
        $name = $_POST['name'];
        $password = $_POST['password'];
        
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
        
    } elseif ($role == 'teacher') {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
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
        
    } elseif ($role == 'student') {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
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
            <h1><i class="fas fa-graduation-cap"></i> EduManage</h1>
            <p>School Management System</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="">
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

                <input type="hidden" name="role" id="selectedRole" value="">

                <!-- Admin Login Fields -->
                <div id="adminFields" class="hidden">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Admin Name</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Enter admin name">
                    </div>
                    <div class="form-group">
                        <label for="adminPassword"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="adminPassword" name="password" class="form-control" placeholder="Enter password">
                    </div>
                </div>

                <!-- Teacher/Student Login Fields -->
                <div id="userFields" class="hidden">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label for="userPassword"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="userPassword" name="password" class="form-control" placeholder="Enter password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="loginBtn" disabled>
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="admin-info">
                <strong>Admin Demo Credentials:</strong><br>
                Name: admin | Password: admin123
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
            const loginBtn = document.getElementById('loginBtn');
            const loginForm = document.getElementById('loginForm');

            roleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove active class from all options
                    roleOptions.forEach(opt => opt.classList.remove('active'));
                    
                    // Add active class to clicked option
                    this.classList.add('active');
                    
                    const role = this.getAttribute('data-role');
                    selectedRoleInput.value = role;
                    
                    // Show appropriate fields
                    if (role === 'admin') {
                        adminFields.classList.remove('hidden');
                        userFields.classList.add('hidden');
                    } else {
                        adminFields.classList.add('hidden');
                        userFields.classList.remove('hidden');
                    }
                    
                    // Enable login button
                    loginBtn.disabled = false;
                });
            });

            // Form validation
            loginForm.addEventListener('submit', function(e) {
                const role = selectedRoleInput.value;
                
                if (!role) {
                    e.preventDefault();
                    alert('Please select your role');
                    return;
                }
                
                if (role === 'admin') {
                    const name = document.getElementById('name').value;
                    const password = document.getElementById('adminPassword').value;
                    
                    if (!name || !password) {
                        e.preventDefault();
                        alert('Please fill in all fields');
                        return;
                    }
                } else {
                    const email = document.getElementById('email').value;
                    const password = document.getElementById('userPassword').value;
                    
                    if (!email || !password) {
                        e.preventDefault();
                        alert('Please fill in all fields');
                        return;
                    }
                    
                    // Basic email validation
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        e.preventDefault();
                        alert('Please enter a valid email address');
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>