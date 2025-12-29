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

    // Debug information
    error_log("Login attempt - Role: " . $role);
    error_log("POST data: " . print_r($_POST, true));

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
                    // Debug admin login attempt
                    error_log("Admin login attempt - Name: " . $name . ", Password: " . $password);

                    $stmt = $pdo->prepare("SELECT * FROM admin WHERE name = ?");
                    $stmt->execute([$name]);
                    $admin = $stmt->fetch();

                    if ($admin) {
                        error_log("Admin found in database - ID: " . $admin['id']);
                        error_log("Stored password: " . $admin['password']);

                        // Check if password is still in plain text and hash it (for backward compatibility)
                        if ($admin['password'] === 'admin123' && $password === 'admin123') {
                            error_log("Plain text password detected - hashing it");
                            // Hash the plain text password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $update_stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE name = ?");
                            $update_stmt->execute([$hashed_password, $name]);

                            // Update the admin array with the new hashed password
                            $admin['password'] = $hashed_password;
                            error_log("Password hashed and updated in database");
                        }

                        // Verify password using secure password_verify()
                        $password_valid = password_verify($password, $admin['password']);
                        error_log("Password verification result: " . ($password_valid ? 'TRUE' : 'FALSE'));

                        if ($password_valid) {
                            $_SESSION['admin_id'] = $admin['id'];
                            $_SESSION['admin_name'] = $admin['name'];
                            $_SESSION['role'] = 'admin';
                            error_log("Admin login successful - redirecting to dashboard");
                            header('Location: admin_dashboard.php');
                            exit();
                        } else {
                            $error = 'Invalid admin credentials!';
                            error_log("Admin login failed - invalid credentials");
                        }
                    } else {
                        $error = 'Invalid admin credentials!';
                        error_log("Admin not found in database");
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
                    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE email = ?");
                    $stmt->execute([$email]);
                    $teacher = $stmt->fetch();

                    if ($teacher && password_verify($password, $teacher['password'])) {
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
                    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
                    $stmt->execute([$email]);
                    $student = $stmt->fetch();

                    if ($student && password_verify($password, $student['password'])) {
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
    <title>Login - FISC-Manage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #8b5cf6;
            --text: #1f2937;
            --text-light: #6b7280;
            --white: #ffffff;
            --glass: rgba(255, 255, 255, 0.95);
            --error: #ef4444;
            --success: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            background-color: #f3f4f6;
        }

        .split-screen {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left Side - Visual */
        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: var(--white);
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            top: -25%;
            left: -25%;
        }

        .brand-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 500px;
        }

        .brand-logo {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: float 6s ease-in-out infinite;
        }

        .brand-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .brand-text {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        /* Right Side - Login Form */
        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #f9fafb;
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
            background: var(--white);
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.6s ease-out;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-header h2 {
            font-size: 1.75rem;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--text-light);
        }

        /* Role Selector */
        .role-selector {
            background: #f3f4f6;
            padding: 0.5rem;
            border-radius: 1rem;
            display: flex;
            margin-bottom: 2rem;
            position: relative;
        }

        .role-option {
            flex: 1;
            text-align: center;
            padding: 0.75rem;
            cursor: pointer;
            border-radius: 0.75rem;
            font-weight: 500;
            color: var(--text-light);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .role-option.active {
            background: var(--white);
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .role-option i {
            font-size: 1rem;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: #f9fafb;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .form-control:focus + i {
            color: var(--primary);
        }

        .btn {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(79, 70, 229, 0.3);
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            animation: shake 0.5s ease-in-out;
        }

        .alert-error {
            background: #fef2f2;
            color: var(--error);
            border: 1px solid #fee2e2;
        }

        .alert-success {
            background: #ecfdf5;
            color: var(--success);
            border: 1px solid #d1fae5;
        }

        /* Helper Links */
        .helper-links {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .helper-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .helper-links a:hover {
            text-decoration: underline;
        }

        .demo-credentials {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f3f4f6;
            border-radius: 0.75rem;
            font-size: 0.85rem;
            color: var(--text-light);
            text-align: center;
        }

        .hidden {
            display: none;
        }

        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @media (max-width: 992px) {
            .left-panel {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="split-screen">
        <!-- Left Panel -->
        <div class="left-panel">
            <div class="brand-content">
                <div class="brand-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1 class="brand-title">Welcome Back!</h1>
                <p class="brand-text">Access your dashboard to manage students, track progress, and stay connected with your school community.</p>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <div class="login-wrapper">
                <div class="login-header">
                    <h2>Sign In</h2>
                    <p>Please select your role to continue</p>
                </div>

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

                <form method="POST" action="" autocomplete="off">
                    <div class="role-selector">
                        <div class="role-option active" data-role="admin">
                            <i class="fas fa-user-shield"></i> Admin
                        </div>
                        <div class="role-option" data-role="teacher">
                            <i class="fas fa-chalkboard-teacher"></i> Teacher
                        </div>
                        <div class="role-option" data-role="student">
                            <i class="fas fa-user-graduate"></i> Student
                        </div>
                    </div>

                    <input type="hidden" name="role" id="selectedRole" value="admin">

                    <!-- Admin Fields -->
                    <div id="adminFields">
                        <div class="form-group">
                            <label>Admin Name</label>
                            <div class="input-group">
                                <input type="text" name="name" class="form-control" placeholder="Enter admin name" value="admin">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control" placeholder="Enter password" value="admin123">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                    </div>

                    <!-- User Fields -->
                    <div id="userFields" class="hidden">
                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-group">
                                <input type="email" name="email" class="form-control" placeholder="name@school.com">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="input-group">
                                <input type="password" name="userPassword" class="form-control" placeholder="Enter password">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span>Sign In</span> <i class="fas fa-arrow-right"></i>
                    </button>

                    <div class="helper-links">
                        <a href="index.php">Back to Home</a>
                        <span style="margin: 0 10px; color: #d1d5db;">|</span>
                        <a href="admin_register.php">Register Admin</a>
                    </div>

                    <div class="demo-credentials">
                        <i class="fas fa-info-circle"></i> Demo: <strong>admin</strong> / <strong>admin123</strong>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleOptions = document.querySelectorAll('.role-option');
            const selectedRoleInput = document.getElementById('selectedRole');
            const adminFields = document.getElementById('adminFields');
            const userFields = document.getElementById('userFields');

            roleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Update UI
                    roleOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');

                    // Update Logic
                    const role = this.getAttribute('data-role');
                    selectedRoleInput.value = role;

                    // Toggle Fields
                    if (role === 'admin') {
                        adminFields.classList.remove('hidden');
                        userFields.classList.add('hidden');
                    } else {
                        adminFields.classList.add('hidden');
                        userFields.classList.remove('hidden');
                    }
                });
            });
        });
    </script>
</body>

</html>