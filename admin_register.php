<?php
session_start();
require_once 'db_config.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validation
    if (empty($name) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields!';
    } elseif (strlen($name) < 3) {
        $error = 'Admin name must be at least 3 characters long!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        try {
            // Check if admin name already exists
            $stmt = $pdo->prepare("SELECT id FROM admin WHERE name = ?");
            $stmt->execute([$name]);

            if ($stmt->fetch()) {
                $error = 'Admin name already exists! Please choose a different name.';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new admin
                $stmt = $pdo->prepare("INSERT INTO admin (name, password) VALUES (?, ?)");
                $stmt->execute([$name, $hashed_password]);

                $success = 'New admin registered successfully!';

                // Clear form data
                $name = '';
                $password = '';
                $confirm_password = '';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get list of existing admins
try {
    $stmt = $pdo->query("SELECT id, name, created_at FROM admin ORDER BY created_at DESC");
    $existing_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $existing_admins = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Admin - School Management System</title>
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
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            background: var(--white);
            border-radius: 15px 15px 0 0;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .content {
            background: var(--white);
            border-radius: 0 0 15px 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .form-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .form-section {
            background: var(--gray);
            padding: 25px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .form-section h2 {
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--text-light);
            color: var(--white);
        }

        .btn-secondary:hover {
            background: var(--text);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #ef4444;
            border-color: #fecaca;
        }

        .admin-list {
            background: var(--gray);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .admin-list h3 {
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: var(--white);
            border-radius: 8px;
            margin-bottom: 10px;
            border: 1px solid var(--border);
        }

        .admin-info {
            display: flex;
            flex-direction: column;
        }

        .admin-name {
            font-weight: 600;
            color: var(--text);
        }

        .admin-date {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .admin-id {
            background: var(--secondary);
            color: var(--primary);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .navigation {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 0.85rem;
        }

        .strength-weak {
            color: var(--error);
        }

        .strength-medium {
            color: var(--accent);
        }

        .strength-strong {
            color: var(--success);
        }

        @media (max-width: 768px) {
            .form-container {
                grid-template-columns: 1fr;
            }

            .navigation {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-shield"></i> Register New Admin</h1>
            <p>Add new administrators to the school management system</p>
        </div>

        <div class="content">
            <div class="navigation">
                <a href="login.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
                <a href="admin_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <div class="form-section">
                    <h2><i class="fas fa-user-plus"></i> New Admin Registration</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Admin Name</label>
                            <input type="text" id="name" name="name" class="form-control"
                                placeholder="Enter admin name" value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                required minlength="3">
                        </div>

                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="Enter password" required minlength="6">
                            <div id="password-strength" class="password-strength"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                placeholder="Confirm password" required minlength="6">
                            <div id="password-match" class="password-strength"></div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Register Admin
                        </button>
                    </form>
                </div>

                <div class="admin-list">
                    <h3><i class="fas fa-users"></i> Existing Administrators</h3>
                    <?php if (empty($existing_admins)): ?>
                        <p style="color: var(--text-light); text-align: center; padding: 20px;">
                            <i class="fas fa-info-circle"></i> No administrators found
                        </p>
                    <?php else: ?>
                        <?php foreach ($existing_admins as $admin): ?>
                            <div class="admin-item">
                                <div class="admin-info">
                                    <div class="admin-name"><?php echo htmlspecialchars($admin['name']); ?></div>
                                    <div class="admin-date">
                                        Registered: <?php echo date('M j, Y', strtotime($admin['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="admin-id">ID: <?php echo $admin['id']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div style="background: var(--secondary); padding: 20px; border-radius: 10px; margin-top: 30px;">
                <h3 style="color: var(--primary); margin-bottom: 15px;">
                    <i class="fas fa-info-circle"></i> Admin Registration Guidelines
                </h3>
                <ul style="color: var(--text); line-height: 1.6;">
                    <li><strong>Admin Name:</strong> Must be unique and at least 3 characters long</li>
                    <li><strong>Password:</strong> Must be at least 6 characters long for security</li>
                    <li><strong>Security:</strong> All passwords are automatically hashed and stored securely</li>
                    <li><strong>Access:</strong> New admins can immediately access the admin dashboard</li>
                    <li><strong>Management:</strong> Use the admin dashboard to manage the system</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');

            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            if (strength < 2) {
                strengthDiv.textContent = 'Weak password';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength < 4) {
                strengthDiv.textContent = 'Medium strength password';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = 'Strong password';
                strengthDiv.className = 'password-strength strength-strong';
            }
        });

        // Password match indicator
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('password-match');

            if (confirmPassword.length === 0) {
                matchDiv.textContent = '';
                return;
            }

            if (password === confirmPassword) {
                matchDiv.textContent = 'Passwords match';
                matchDiv.className = 'password-strength strength-strong';
            } else {
                matchDiv.textContent = 'Passwords do not match';
                matchDiv.className = 'password-strength strength-weak';
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>

</html>