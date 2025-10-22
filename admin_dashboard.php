<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'db_config.php';

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Register Teacher
    if (isset($_POST['register_teacher'])) {
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'] ?? '';
        $subject = $_POST['subject'] ?? '';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO teachers (email, password, full_name, phone, subject) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$email, $password, $full_name, $phone, $subject]);
            $success = "Teacher registered successfully!";
        } catch (PDOException $e) {
            $error = "Error registering teacher: " . $e->getMessage();
        }
    }
    
    // Update Teacher
    if (isset($_POST['update_teacher'])) {
        $teacher_id = $_POST['teacher_id'];
        $email = $_POST['email'];
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'] ?? '';
        $subject = $_POST['subject'] ?? '';
        
        try {
            $stmt = $pdo->prepare("UPDATE teachers SET email = ?, full_name = ?, phone = ?, subject = ? WHERE id = ?");
            $stmt->execute([$email, $full_name, $phone, $subject, $teacher_id]);
            $success = "Teacher updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating teacher: " . $e->getMessage();
        }
    }
    
    // Register Student
    if (isset($_POST['register_student'])) {
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = $_POST['full_name'];
        $class_id = $_POST['class_id'];
        
        try {
            // Check class capacity
            $stmt = $pdo->prepare("SELECT current_enrollment, max_capacity FROM classes WHERE class_id = ?");
            $stmt->execute([$class_id]);
            $class = $stmt->fetch();
            
            if ($class && $class['current_enrollment'] < $class['max_capacity']) {
                // Insert student
                $stmt = $pdo->prepare("INSERT INTO students (email, password, full_name, class_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$email, $password, $full_name, $class_id]);
                
                // Update class enrollment
                $stmt = $pdo->prepare("UPDATE classes SET current_enrollment = current_enrollment + 1 WHERE class_id = ?");
                $stmt->execute([$class_id]);
                
                $success = "Student registered successfully!";
            } else {
                $error = "Class is full! Maximum capacity reached.";
            }
        } catch (PDOException $e) {
            $error = "Error registering student: " . $e->getMessage();
        }
    }
    
    // Create Class
    if (isset($_POST['create_class'])) {
        $class_name = $_POST['class_name'];
        $class_code = $_POST['class_code'];
        $teacher_id = $_POST['teacher_id'] ?: NULL;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO classes (class_name, class_code, teacher_id) VALUES (?, ?, ?)");
            $stmt->execute([$class_name, $class_code, $teacher_id]);
            $success = "Class created successfully!";
        } catch (PDOException $e) {
            $error = "Error creating class: " . $e->getMessage();
        }
    }
    
    // Assign Teacher to Class
    if (isset($_POST['assign_teacher'])) {
        $class_id = $_POST['class_id'];
        $teacher_id = $_POST['teacher_id'] ?: NULL;
        
        try {
            $stmt = $pdo->prepare("UPDATE classes SET teacher_id = ? WHERE class_id = ?");
            $stmt->execute([$teacher_id, $class_id]);
            $success = "Teacher assigned to class successfully!";
        } catch (PDOException $e) {
            $error = "Error assigning teacher: " . $e->getMessage();
        }
    }
    
    // Remove Student from Class
    if (isset($_POST['remove_student'])) {
        $student_id = $_POST['student_id'];
        $class_id = $_POST['class_id'];
        
        try {
            // Remove student from class
            $stmt = $pdo->prepare("UPDATE students SET class_id = NULL WHERE id = ?");
            $stmt->execute([$student_id]);
            
            // Update class enrollment
            $stmt = $pdo->prepare("UPDATE classes SET current_enrollment = current_enrollment - 1 WHERE class_id = ?");
            $stmt->execute([$class_id]);
            
            $success = "Student removed from class successfully!";
        } catch (PDOException $e) {
            $error = "Error removing student: " . $e->getMessage();
        }
    }
    
    // Record Fee Payment
    if (isset($_POST['record_payment'])) {
        $student_id = $_POST['student_id'];
        $amount = $_POST['amount'];
        $payment_date = $_POST['payment_date'];
        $term = $_POST['term'];
        $academic_year = $_POST['academic_year'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, term, academic_year) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $amount, $payment_date, $term, $academic_year]);
            $success = "Fee payment recorded successfully!";
        } catch (PDOException $e) {
            $error = "Error recording payment: " . $e->getMessage();
        }
    }
}

// Get data for dropdowns and listings
$teachers = $pdo->query("SELECT * FROM teachers ORDER BY full_name")->fetchAll();
$classes = $pdo->query("SELECT c.*, t.full_name as teacher_name FROM classes c LEFT JOIN teachers t ON c.teacher_id = t.id ORDER BY c.class_name")->fetchAll();
$students = $pdo->query("SELECT s.*, c.class_name, c.class_code FROM students s LEFT JOIN classes c ON s.class_id = c.class_id ORDER BY s.full_name")->fetchAll();

// Get fee payments if table exists
try {
    $fee_payments = $pdo->query("SELECT fp.*, s.full_name as student_name FROM fee_payments fp JOIN students s ON fp.student_id = s.id ORDER BY fp.payment_date DESC")->fetchAll();
} catch (PDOException $e) {
    $fee_payments = [];
}

// Get counts for dashboard
$teacher_count = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$student_count = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$class_count = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();

// Calculate capacity percentage
$total_capacity = $class_count * 30;
$capacity_percentage = $total_capacity > 0 ? round(($student_count / $total_capacity) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FISC-Manage</title>
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
        }
        
        body { 
            background: #f5f5f5; 
        }
        
        .header { 
            background: var(--primary); 
            color: var(--white); 
            padding: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        
        .dashboard-cards { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        
        .card { 
            background: var(--white); 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            text-align: center; 
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .card i { 
            font-size: 2.5rem; 
            color: var(--primary); 
            margin-bottom: 15px; 
        }
        
        .card h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--text);
        }
        
        .card p {
            color: var(--text-light);
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .forms-container { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 30px; 
        }
        
        .form-section { 
            background: var(--white); 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
        }
        
        .form-section h2 {
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group { 
            margin-bottom: 15px; 
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text);
        }
        
        .form-control { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid var(--border); 
            border-radius: 5px; 
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn { 
            padding: 12px 24px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary { 
            background: var(--primary); 
            color: var(--white); 
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .logout { 
            float: right; 
            background: var(--accent); 
            color: var(--white);
            text-decoration: none;
        }
        
        .logout:hover {
            background: #e69008;
        }
        
        .success { 
            background: #d1fae5; 
            color: #065f46; 
            padding: 12px; 
            border-radius: 5px; 
            margin-bottom: 15px; 
            border: 1px solid #a7f3d0;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-message {
            margin-top: 5px;
            opacity: 0.9;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .btn-success {
            background: #10b981;
            color: var(--white);
        }
        
        .btn-success:hover {
            background: #0da271;
            transform: translateY(-2px);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: var(--white);
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            background: var(--primary);
            color: var(--white);
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
        }
        
        .close {
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid transparent;
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
        
        .data-section {
            background: var(--white);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .data-section h2 {
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .data-table th {
            background: var(--secondary);
            color: var(--primary);
            font-weight: 600;
        }
        
        .data-table tr:hover {
            background: var(--gray);
        }
        
        .capacity-warning {
            color: #ef4444;
            font-weight: 600;
        }
        
        .capacity-ok {
            color: #10b981;
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            color: var(--text-light);
            padding: 40px;
            font-style: italic;
        }
        
        .clickable {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .clickable:hover {
            background: var(--secondary);
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: var(--white);
        }
        
        .btn-warning:hover {
            background: #e69008;
        }
        
        .btn-danger {
            background: #ef4444;
            color: var(--white);
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        @media (max-width: 768px) {
            .forms-container {
                grid-template-columns: 1fr;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .logout {
                float: none;
                margin-top: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div>
                    <h1><i class="fas fa-graduation-cap"></i> Admin Dashboard</h1>
                    <p class="welcome-message">Welcome, <?php echo $_SESSION['admin_name']; ?>!</p>
                </div>
                <a href="logout.php" class="btn logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Notifications -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3>Teachers</h3>
                <p><?php echo $teacher_count; ?> Registered</p>
            </div>
            <div class="card">
                <i class="fas fa-user-graduate"></i>
                <h3>Students</h3>
                <p><?php echo $student_count; ?> Registered</p>
            </div>
            <div class="card">
                <i class="fas fa-school"></i>
                <h3>Classes</h3>
                <p><?php echo $class_count; ?> Created</p>
            </div>
            <div class="card">
                <i class="fas fa-chart-bar"></i>
                <h3>Capacity</h3>
                <p><?php echo $capacity_percentage; ?>% Used</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="openModal('registerModal')">
                <i class="fas fa-user-plus"></i> Register User
            </button>
            <button class="btn btn-success" onclick="openModal('classModal')">
                <i class="fas fa-plus-circle"></i> Create Class
            </button>
            <button class="btn btn-primary" onclick="openModal('assignModal')">
                <i class="fas fa-link"></i> Assign Teacher
            </button>
            <button class="btn btn-warning" onclick="openModal('feeModal')">
                <i class="fas fa-money-bill-wave"></i> Record Fees
            </button>
        </div>

        <!-- Students List -->
        <div class="data-section">
            <h2><i class="fas fa-user-graduate"></i> Registered Students</h2>
            <?php if (count($students) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td>
                                    <?php if ($student['class_name']): ?>
                                        <?php echo htmlspecialchars($student['class_name']); ?> (<?php echo $student['class_code']; ?>)
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($student['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-user-graduate" style="font-size: 3rem; margin-bottom: 10px;"></i>
                    <p>No students registered yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Teachers List -->
        <div class="data-section">
            <h2><i class="fas fa-chalkboard-teacher"></i> Registered Teachers</h2>
            <?php if (count($teachers) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr class="clickable" onclick="showTeacherDetails(<?php echo $teacher['id']; ?>)">
                                <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['subject'] ?? 'Not specified'); ?></td>
                                <td><?php echo htmlspecialchars($teacher['phone'] ?? 'Not provided'); ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); editTeacher(<?php echo $teacher['id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chalkboard-teacher" style="font-size: 3rem; margin-bottom: 10px;"></i>
                    <p>No teachers registered yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Classes List -->
        <div class="data-section">
            <h2><i class="fas fa-school"></i> Classes</h2>
            <?php if (count($classes) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Class Name</th>
                            <th>Teacher</th>
                            <th>Enrollment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): ?>
                            <tr class="clickable" onclick="showClassDetails(<?php echo $class['class_id']; ?>)">
                                <td><strong><?php echo $class['class_code']; ?></strong></td>
                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td>
                                    <?php if ($class['teacher_name']): ?>
                                        <?php echo htmlspecialchars($class['teacher_name']); ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $class['current_enrollment']; ?> / <?php echo $class['max_capacity']; ?>
                                </td>
                                <td>
                                    <?php if ($class['current_enrollment'] >= $class['max_capacity']): ?>
                                        <span class="capacity-warning">Full</span>
                                    <?php else: ?>
                                        <span class="capacity-ok">Available</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); editClass(<?php echo $class['class_id']; ?>)">
                                        <i class="fas fa-edit"></i> Manage
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-school" style="font-size: 3rem; margin-bottom: 10px;"></i>
                    <p>No classes created yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Fee Payments Section -->
        <div class="data-section">
            <h2><i class="fas fa-money-bill-wave"></i> Fee Payments</h2>
            <?php if (count($fee_payments) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Amount</th>
                            <th>Term</th>
                            <th>Academic Year</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fee_payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                <td>₦<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($payment['term']); ?></td>
                                <td><?php echo htmlspecialchars($payment['academic_year']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-money-bill-wave" style="font-size: 3rem; margin-bottom: 10px;"></i>
                    <p>No fee payments recorded yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- All modals and JavaScript remain the same as previous enhanced version -->
    <!-- [Include all the same modals and JavaScript from the previous enhanced version] -->
         <!-- Register User Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Register User</h2>
                <button class="close" onclick="closeModal('registerModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="tabs">
                    <button class="tab active" onclick="switchTab('teacherTab')">Register Teacher</button>
                    <button class="tab" onclick="switchTab('studentTab')">Register Student</button>
                </div>

                <div id="teacherTab" class="tab-content active">
                    <form method="POST">
                        <input type="hidden" name="register_teacher" value="1">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Teacher</button>
                    </form>
                </div>

                <div id="studentTab" class="tab-content">
                    <form method="POST">
                        <input type="hidden" name="register_student" value="1">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Assign to Class</label>
                            <select name="class_id" class="form-control" required>
                                <option value="">Select a class</option>
                                <?php foreach ($classes as $class): ?>
                                    <?php if ($class['current_enrollment'] < $class['max_capacity']): ?>
                                        <option value="<?php echo $class['class_id']; ?>">
                                            <?php echo htmlspecialchars($class['class_name']); ?> (<?php echo $class['class_code']; ?>) - 
                                            <?php echo $class['current_enrollment']; ?>/<?php echo $class['max_capacity']; ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Student</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Class Modal -->
    <div id="classModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Create New Class</h2>
                <button class="close" onclick="closeModal('classModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="create_class" value="1">
                    <div class="form-group">
                        <label>Class Name</label>
                        <input type="text" name="class_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Class Code</label>
                        <input type="text" name="class_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Assign Teacher (Optional)</label>
                        <select name="teacher_id" class="form-control">
                            <option value="">No teacher assigned</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Create Class</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Teacher Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-link"></i> Assign Teacher to Class</h2>
                <button class="close" onclick="closeModal('assignModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="assign_teacher" value="1">
                    <div class="form-group">
                        <label>Select Class</label>
                        <select name="class_id" class="form-control" required>
                            <option value="">Select a class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name']); ?> (<?php echo $class['class_code']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Teacher</label>
                        <select name="teacher_id" class="form-control">
                            <option value="">No teacher (unassign)</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Assign Teacher</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Fee Payment Modal -->
    <div id="feeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-money-bill-wave"></i> Record Fee Payment</h2>
                <button class="close" onclick="closeModal('feeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="record_payment" value="1">
                    <div class="form-group">
                        <label>Student</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">Select a student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['full_name']); ?> - <?php echo $student['class_name'] ?? 'No Class'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount (₵)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Term</label>
                        <select name="term" class="form-control" required>
                            <option value="First Term">First Term</option>
                            <option value="Second Term">Second Term</option>
                            <option value="Third Term">Third Term</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Academic Year</label>
                        <input type="text" name="academic_year" class="form-control" required value="<?php echo date('Y'); ?>/<?php echo date('Y') + 1; ?>">
                    </div>
                    <button type="submit" class="btn btn-success">Record Payment</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Teacher Details Modal -->
    <div id="teacherDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-chalkboard-teacher"></i> Teacher Details</h2>
                <button class="close" onclick="closeModal('teacherDetailModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="teacherDetailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Class Details Modal -->
    <div id="classDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-school"></i> Class Details</h2>
                <button class="close" onclick="closeModal('classDetailModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="classDetailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div id="editTeacherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Teacher</h2>
                <button class="close" onclick="closeModal('editTeacherModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="update_teacher" value="1">
                    <input type="hidden" id="edit_teacher_id" name="teacher_id">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="edit_teacher_name" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="edit_teacher_email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" id="edit_teacher_phone" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" id="edit_teacher_subject" name="subject" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Teacher</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Tab switching function
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });

        // Teacher details function
        async function showTeacherDetails(teacherId) {
            try {
                const response = await fetch(`get_teacher_details.php?teacher_id=${teacherId}`);
                const data = await response.json();
                
                if (data.success) {
                    const teacher = data.teacher;
                    const classes = data.classes;
                    
                    let classesHtml = '';
                    if (classes.length > 0) {
                        classesHtml = `
                            <div style="background: #f8fafc; padding: 15px; border-radius: 5px; margin: 15px 0;">
                                <h3 style="color: #2563eb; margin-bottom: 10px;">
                                    <i class="fas fa-school"></i> Assigned Classes
                                </h3>
                                ${classes.map(cls => `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px solid #e5e7eb;">
                                        <span>${cls.class_name} (${cls.class_code})</span>
                                        <span style="color: #10b981; font-weight: 600;">${cls.current_enrollment}/${cls.max_capacity} students</span>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    } else {
                        classesHtml = `
                            <div style="background: #f8fafc; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center; color: #6b7280;">
                                <i class="fas fa-school" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <p>No classes assigned</p>
                            </div>
                        `;
                    }
                    
                    document.getElementById('teacherDetailContent').innerHTML = `
                        <div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                <div>
                                    <strong>Full Name:</strong><br>
                                    ${teacher.full_name}
                                </div>
                                <div>
                                    <strong>Email:</strong><br>
                                    ${teacher.email}
                                </div>
                                <div>
                                    <strong>Phone:</strong><br>
                                    ${teacher.phone || 'Not provided'}
                                </div>
                                <div>
                                    <strong>Subject:</strong><br>
                                    ${teacher.subject || 'Not specified'}
                                </div>
                                <div>
                                    <strong>Registered:</strong><br>
                                    ${new Date(teacher.created_at).toLocaleDateString()}
                                </div>
                            </div>
                            ${classesHtml}
                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button class="btn btn-primary" onclick="editTeacher(${teacher.id})">
                                    <i class="fas fa-edit"></i> Edit Information
                                </button>
                                <button class="btn btn-warning" onclick="closeModal('teacherDetailModal')">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                        </div>
                    `;
                    
                    openModal('teacherDetailModal');
                }
            } catch (error) {
                console.error('Error fetching teacher details:', error);
                alert('Error loading teacher details');
            }
        }

        // Class details function
        async function showClassDetails(classId) {
            try {
                const response = await fetch(`get_class_details.php?class_id=${classId}`);
                const data = await response.json();
                
                if (data.success) {
                    const classInfo = data.class;
                    const students = data.students;
                    const teachers = data.teachers;
                    
                    let studentsHtml = '<div style="text-align: center; color: #6b7280; padding: 20px;"><i class="fas fa-user-graduate" style="font-size: 2rem;"></i><p>No students enrolled</p></div>';
                    if (students.length > 0) {
                        studentsHtml = `
                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 5px;">
                                ${students.map(student => `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #e5e7eb;">
                                        <span>${student.full_name}</span>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Remove ${student.full_name} from class?')">
                                            <input type="hidden" name="remove_student" value="1">
                                            <input type="hidden" name="student_id" value="${student.id}">
                                            <input type="hidden" name="class_id" value="${classId}">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    }
                    
                    document.getElementById('classDetailContent').innerHTML = `
                        <div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                <div>
                                    <strong>Class Name:</strong><br>
                                    ${classInfo.class_name}
                                </div>
                                <div>
                                    <strong>Class Code:</strong><br>
                                    ${classInfo.class_code}
                                </div>
                                <div>
                                    <strong>Teacher:</strong><br>
                                    ${classInfo.teacher_name || 'Not assigned'}
                                </div>
                                <div>
                                    <strong>Enrollment:</strong><br>
                                    ${classInfo.current_enrollment}/${classInfo.max_capacity}
                                </div>
                                <div>
                                    <strong>Status:</strong><br>
                                    <span style="color: ${classInfo.current_enrollment >= classInfo.max_capacity ? '#ef4444' : '#10b981'}; font-weight: 600;">
                                        ${classInfo.current_enrollment >= classInfo.max_capacity ? 'Full' : 'Available'}
                                    </span>
                                </div>
                            </div>
                            
                            <div style="background: #f8fafc; padding: 15px; border-radius: 5px; margin: 15px 0;">
                                <h3 style="color: #2563eb; margin-bottom: 10px;">
                                    <i class="fas fa-user-graduate"></i> Enrolled Students (${students.length})
                                </h3>
                                ${studentsHtml}
                            </div>
                            
                            <div style="background: #f0f9ff; padding: 15px; border-radius: 5px; margin: 15px 0;">
                                <h3 style="color: #2563eb; margin-bottom: 10px;">
                                    <i class="fas fa-chalkboard-teacher"></i> Assign Teacher
                                </h3>
                                <form method="POST">
                                    <input type="hidden" name="assign_teacher" value="1">
                                    <input type="hidden" name="class_id" value="${classId}">
                                    <div class="form-group">
                                        <select name="teacher_id" class="form-control">
                                            <option value="">No teacher (unassign)</option>
                                            ${teachers.map(teacher => `
                                                <option value="${teacher.id}" ${classInfo.teacher_id == teacher.id ? 'selected' : ''}>
                                                    ${teacher.full_name}
                                                </option>
                                            `).join('')}
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Assignment
                                    </button>
                                </form>
                            </div>
                        </div>
                    `;
                    
                    openModal('classDetailModal');
                }
            } catch (error) {
                console.error('Error fetching class details:', error);
                alert('Error loading class details');
            }
        }

        // Edit teacher function
        async function editTeacher(teacherId) {
            try {
                const response = await fetch(`get_teacher_details.php?teacher_id=${teacherId}`);
                const data = await response.json();
                
                if (data.success) {
                    const teacher = data.teacher;
                    
                    document.getElementById('edit_teacher_id').value = teacher.id;
                    document.getElementById('edit_teacher_name').value = teacher.full_name;
                    document.getElementById('edit_teacher_email').value = teacher.email;
                    document.getElementById('edit_teacher_phone').value = teacher.phone || '';
                    document.getElementById('edit_teacher_subject').value = teacher.subject || '';
                    
                    closeModal('teacherDetailModal');
                    openModal('editTeacherModal');
                }
            } catch (error) {
                console.error('Error fetching teacher data:', error);
                alert('Error loading teacher data');
            }
        }

        function editClass(classId) {
            showClassDetails(classId);
        }
    </script>

</body>
</html>