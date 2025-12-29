<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'db_config.php';

$studentId = $_SESSION['student_id'];
$success = '';
$error = '';

// Get student details
$stmt = $pdo->prepare("SELECT s.*, c.class_name, c.class_code FROM students s LEFT JOIN classes c ON s.class_id = c.class_id WHERE s.id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get assignments
$assignments = [];
try {
    $stmt = $pdo->prepare("
        SELECT a.*, t.full_name as teacher_name, c.class_name 
        FROM assignments a 
        JOIN teachers t ON a.teacher_id = t.id 
        JOIN classes c ON a.class_id = c.class_id 
        WHERE a.class_id = ? AND a.deadline >= CURDATE()
        ORDER BY a.deadline ASC 
        LIMIT 10
    ");
    $stmt->execute([$student['class_id']]);
    $assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    // Assignments table might not exist
}

// Get grades
$grades = [];
try {
    $stmt = $pdo->prepare("
        SELECT g.*, s.full_name as student_name 
        FROM grades g 
        JOIN students s ON g.student_id = s.id 
        WHERE g.student_id = ? 
        ORDER BY g.recorded_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$studentId]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching grades: " . $e->getMessage());
    $grades = [];
}

// Get attendance
$attendance = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? 
        ORDER BY date DESC 
        LIMIT 30
    ");
    $stmt->execute([$studentId]);
    $attendance = $stmt->fetchAll();
} catch (PDOException $e) {
    // Attendance table might not exist
}


// Calculate attendance stats
$attendanceStats = ['present' => 0, 'absent' => 0, 'late' => 0];
foreach ($attendance as $record) {
    if (isset($attendanceStats[$record['status']])) {
        $attendanceStats[$record['status']]++;
    }
}
$totalAttendance = array_sum($attendanceStats);
$attendanceRate = $totalAttendance > 0 ? round(($attendanceStats['present'] / $totalAttendance) * 100) : 0;

// Get fee summary for this student
function getStudentFeeSummary($pdo, $student_id)
{
    try {
        // Get all fee types
        $fee_types = $pdo->query("SELECT * FROM fee_types")->fetchAll();

        // Get payments for this student
        $stmt = $pdo->prepare("SELECT fee_type_id, SUM(amount) as total_paid FROM fee_payments WHERE student_id = ? GROUP BY fee_type_id");
        $stmt->execute([$student_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $summary = [];
        foreach ($fee_types as $fee_type) {
            $paid = $payments[$fee_type['id']] ?? 0;
            $remaining = $fee_type['amount'] - $paid;
            $summary[] = [
                'name' => $fee_type['name'],
                'total' => $fee_type['amount'],
                'paid' => $paid,
                'remaining' => max(0, $remaining)
            ];
        }

        return $summary;
    } catch (PDOException $e) {
        return [];
    }
}

$fee_summary = getStudentFeeSummary($pdo, $studentId);

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_assignment'])) {
    $assignment_id = $_POST['assignment_id'];

    // Check if already submitted (additional check for security)
    $stmt = $pdo->prepare("SELECT id, status FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
    $stmt->execute([$assignment_id, $studentId]);
    $existingSubmission = $stmt->fetch();

    if ($existingSubmission) {
        $error = "You have already submitted this assignment. Resubmission is not allowed.";
    } else {
        // Handle file upload
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = [
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
            ];

            $fileType = $_FILES['submission_file']['type'];
            $fileSize = $_FILES['submission_file']['size'];

            // Check file size (max 10MB)
            if ($fileSize > 10 * 1024 * 1024) {
                $error = 'File size too large. Maximum size is 10MB.';
            } elseif (isset($allowedTypes[$fileType])) {
                $ext = $allowedTypes[$fileType];
                $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'submissions';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }

                $fileName = 'submission_' . $studentId . '_' . $assignment_id . '_' . uniqid() . '.' . $ext;
                $dest = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

                if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $dest)) {
                    $filePath = 'uploads/submissions/' . $fileName;

                    // Determine if submission is late
                    $stmt = $pdo->prepare("SELECT deadline FROM assignments WHERE id = ?");
                    $stmt->execute([$assignment_id]);
                    $assignment = $stmt->fetch();

                    $submissionStatus = 'submitted';
                    if ($assignment && strtotime($assignment['deadline']) < time()) {
                        $submissionStatus = 'late';
                    }

                    // Insert submission
                    $stmt = $pdo->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, file_path, file_name, file_size, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$assignment_id, $studentId, $filePath, $fileName, $fileSize, $submissionStatus]);
                    $success = "Assignment submitted successfully!" . ($submissionStatus === 'late' ? " (Note: This submission is late)" : "");

                    // Refresh the page to update the submission status
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = 'Failed to save uploaded file.';
                }
            } else {
                $error = 'Invalid file type. Only PDF and Word documents are allowed.';
            }
        } else {
            $error = 'Please select a file to upload.';
        }
    }
}


// Get announcements
$announcements = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title, 
            message as content,
            priority,
            created_at,
            created_by
        FROM student_announcements 
        WHERE is_active = 1
        ORDER BY 
            CASE priority 
                WHEN 'high' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'low' THEN 3 
            END,
            created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll();

    if (!empty($announcements)) {
        foreach ($announcements as &$announcement) {
            try {
                $readStmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM student_announcement_reads 
                    WHERE announcement_id = ? AND student_id = ?
                ");
                $readStmt->execute([$announcement['id'], $studentId]);
                $readResult = $readStmt->fetch(PDO::FETCH_ASSOC);

                $announcement['is_read'] = ($readResult['count'] > 0) ? 1 : 0;
                $announcement['created_by_name'] = 'Administrator';
            } catch (PDOException $e) {
                $announcement['is_read'] = 0;
                $announcement['created_by_name'] = 'Administrator';
            }
        }
        unset($announcement);

        foreach ($announcements as $announcement) {
            if (!$announcement['is_read']) {
                try {
                    $markReadStmt = $pdo->prepare("
                        INSERT IGNORE INTO student_announcement_reads (announcement_id, student_id) 
                        VALUES (?, ?)
                    ");
                    $markReadStmt->execute([$announcement['id'], $studentId]);
                } catch (PDOException $e) {
                    // Ignore read tracking errors
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log("Announcements error: " . $e->getMessage());
    $announcements = [];
}

// Get store items for students
$store_items = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM store_items WHERE is_available = TRUE AND quantity_available > 0 ORDER BY created_at DESC");
    $stmt->execute();
    $store_items = $stmt->fetchAll();
} catch (PDOException $e) {
    // Store items table might not exist yet
}

// Handle store orders
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'] ?? 1;

    try {
        // Check item availability
        $stmt = $pdo->prepare("SELECT * FROM store_items WHERE id = ? AND is_available = TRUE AND quantity_available >= ?");
        $stmt->execute([$item_id, $quantity]);
        $item = $stmt->fetch();

        if ($item) {
            $total_amount = $item['price'] * $quantity;

            // Create order
            $stmt = $pdo->prepare("INSERT INTO store_orders (student_id, item_id, quantity, total_amount, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$studentId, $item_id, $quantity, $total_amount]);

            // Update item quantity
            $stmt = $pdo->prepare("UPDATE store_items SET quantity_available = quantity_available - ? WHERE id = ?");
            $stmt->execute([$quantity, $item_id]);

            $success = "Order placed successfully! Please proceed with payment.";
        } else {
            $error = "Item not available or insufficient quantity.";
        }
    } catch (PDOException $e) {
        $error = "Error placing order: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - FISC-Manage</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5; /* Indigo 600 */
            --primary-dark: #4338ca; /* Indigo 700 */
            --secondary: #8b5cf6; /* Violet 500 */
            --accent: #f59e0b; /* Amber 500 */
            --text: #1f2937; /* Gray 800 */
            --text-light: #6b7280; /* Gray 500 */
            --white: #ffffff;
            --gray-light: #f9fafb;
            --glass: rgba(255, 255, 255, 0.9);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 1rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }

        body {
            background: #f3f4f6;
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--white);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--primary);
        }

        .sidebar-header i {
            font-size: 2rem;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .nav-links {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .nav-item:hover, .nav-item.active {
            background: #eef2ff;
            color: var(--primary);
            font-weight: 500;
        }

        .nav-item i {
            width: 24px;
            text-align: center;
        }

        .user-profile {
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            object-fit: cover;
        }

        .user-info {
            flex: 1;
            overflow: hidden;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: var(--glass);
            padding: 1rem 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(10px);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
        }

        .top-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.4);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        /* Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #eef2ff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f3f4f6;
        }

        th {
            font-weight: 600;
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        tr:hover {
            background: #f9fafb;
        }

        /* Badges */
        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-high { background: #fee2e2; color: #ef4444; }
        .priority-medium { background: #fef3c7; color: #d97706; }
        .priority-low { background: #dbeafe; color: #1e40af; }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--white);
            width: 90%;
            max-width: 600px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* Mobile */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
            }
        }

        .view-section {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .view-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-graduation-cap"></i>
            <h2>FISC-Manage</h2>
        </div>
        <div class="nav-links">
            <div class="nav-item active" onclick="switchView('dashboard-view')">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </div>
            <div class="nav-item" onclick="switchView('assignments-view')">
                <i class="fas fa-tasks"></i>
                <span>Assignments</span>
            </div>
            <div class="nav-item" onclick="switchView('grades-view')">
                <i class="fas fa-chart-line"></i>
                <span>My Grades</span>
            </div>
            <div class="nav-item" onclick="switchView('attendance-view')">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </div>
            <div class="nav-item" onclick="switchView('fees-view')">
                <i class="fas fa-money-bill-wave"></i>
                <span>School Fees</span>
            </div>
            <div class="nav-item" onclick="switchView('store-view')">
                <i class="fas fa-shopping-cart"></i>
                <span>School Store</span>
            </div>
        </div>
        <div class="user-profile">
            <?php if ($student && !empty($student['passport_path'])): ?>
                <img src="<?php echo htmlspecialchars($student['passport_path']); ?>" class="user-avatar" alt="Profile">
            <?php else: ?>
                <div class="user-avatar"><i class="fas fa-user"></i></div>
            <?php endif; ?>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['student_name']); ?></div>
                <div class="user-role">Student</div>
            </div>
            <a href="logout.php" style="color:var(--text-light);"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div style="display:flex; align-items:center; gap:1rem;">
                <button class="btn-sm" onclick="toggleSidebar()" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--text); display:none;" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <h1 class="page-title" id="page-title">Dashboard</h1>
            </div>
            <div class="top-actions">
                <div style="text-align:right;">
                    <div style="font-weight:600;"><?php echo date('l, F j'); ?></div>
                    <div style="font-size:0.9rem; color:var(--text-light);"><?php echo $student['class_name'] ?? 'No Class'; ?></div>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <?php if ($success): ?>
            <div style="background:#d1fae5; color:#065f46; padding:1rem; border-radius:0.5rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background:#fee2e2; color:#ef4444; padding:1rem; border-radius:0.5rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard View -->
        <div id="dashboard-view" class="view-section active">
            <div class="stats-grid">
                <div class="stat-card" onclick="switchView('grades-view')" style="cursor:pointer;">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-value"><?php echo count($grades); ?></div>
                    <div class="stat-label">Recorded Grades</div>
                </div>
                <div class="stat-card" onclick="switchView('assignments-view')" style="cursor:pointer;">
                    <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                    <div class="stat-value"><?php echo count($assignments); ?></div>
                    <div class="stat-label">Pending Assignments</div>
                </div>
                <div class="stat-card" onclick="switchView('attendance-view')" style="cursor:pointer;">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-value"><?php echo $attendanceRate; ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
                <div class="stat-card" onclick="switchView('fees-view')" style="cursor:pointer;">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <?php
                    $school_fees_remaining = 0;
                    foreach ($fee_summary as $fee) {
                        if ($fee['name'] === 'School Fees') {
                            $school_fees_remaining = $fee['remaining'];
                            break;
                        }
                    }
                    ?>
                    <div class="stat-value" style="font-size:1.5rem; color:<?php echo $school_fees_remaining > 0 ? '#ef4444' : '#10b981'; ?>">
                        <?php echo $school_fees_remaining > 0 ? '₦'.number_format($school_fees_remaining) : 'Paid'; ?>
                    </div>
                    <div class="stat-label">Fees Due</div>
                </div>
            </div>

            <!-- Recent Announcements -->
            <div class="content-card">
                <div class="section-header">
                    <h2 class="section-title">Recent Announcements</h2>
                    <button class="btn btn-sm btn-primary" onclick="openModal('allAnnouncementsModal')">View All</button>
                </div>
                <?php if (!empty($announcements)): ?>
                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        <?php foreach (array_slice($announcements, 0, 3) as $announcement): ?>
                            <div style="padding:1rem; background:#f9fafb; border-radius:0.5rem; border-left:4px solid var(--primary);">
                                <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                    <span class="priority-badge priority-<?php echo $announcement['priority']; ?>"><?php echo ucfirst($announcement['priority']); ?></span>
                                </div>
                                <p style="color:var(--text-light); font-size:0.9rem; margin-bottom:0.5rem;"><?php echo nl2br(htmlspecialchars(substr($announcement['content'], 0, 150) . '...')); ?></p>
                                <div style="font-size:0.8rem; color:var(--text-light);">
                                    <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align:center; color:var(--text-light);">No announcements yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assignments View -->
        <div id="assignments-view" class="view-section">
            <div class="section-header">
                <h2 class="section-title">My Assignments</h2>
            </div>
            <div class="content-card">
                <?php if (!empty($assignments)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Teacher</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <?php
                                    $isSubmitted = !empty($assignment['submission_id']);
                                    $submissionStatus = $assignment['submission_status'] ?? 'not_submitted';
                                    $dueDate = strtotime($assignment['deadline']);
                                    $daysLeft = round(($dueDate - time()) / (60 * 60 * 24));
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                            <div style="font-size:0.8rem; color:var(--text-light);"><?php echo htmlspecialchars($assignment['class_name']); ?></div>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', $dueDate); ?>
                                            <?php if ($daysLeft < 0): ?>
                                                <div style="color:#ef4444; font-size:0.8rem;">Overdue</div>
                                            <?php elseif ($daysLeft <= 3): ?>
                                                <div style="color:#f59e0b; font-size:0.8rem;">Due soon</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                        <td>
                                            <?php if ($isSubmitted): ?>
                                                <span class="priority-badge priority-low">Submitted</span>
                                            <?php else: ?>
                                                <span class="priority-badge priority-medium">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:0.5rem;">
                                                <?php if (!empty($assignment['file_path'])): ?>
                                                    <button class="btn btn-sm btn-primary" onclick="openDownloadModal('<?php echo $assignment['id']; ?>', '<?php echo addslashes($assignment['title']); ?>', '', '', '<?php echo $assignment['file_path']; ?>', '<?php echo basename($assignment['file_path']); ?>')"><i class="fas fa-download"></i></button>
                                                <?php endif; ?>
                                                <?php if (!$isSubmitted): ?>
                                                    <button class="btn btn-sm btn-success" onclick="openSubmitModal('<?php echo $assignment['id']; ?>', '<?php echo addslashes($assignment['title']); ?>', '<?php echo $assignment['deadline']; ?>')">Submit</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align:center; padding:2rem; color:var(--text-light);">No pending assignments.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Grades View -->
        <div id="grades-view" class="view-section">
            <div class="section-header">
                <h2 class="section-title">My Grades</h2>
            </div>
            <div class="content-card">
                <?php if (!empty($grades)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject/Type</th>
                                    <th>Grade</th>
                                    <th>Score</th>
                                    <th>Term</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;"><?php echo htmlspecialchars($grade['assignment_type']); ?></div>
                                        </td>
                                        <td>
                                            <span style="font-weight:700; font-size:1.1rem; color:var(--primary);"><?php echo htmlspecialchars($grade['grade']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($grade['score']); ?>%</td>
                                        <td><?php echo htmlspecialchars($grade['term']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($grade['recorded_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align:center; padding:2rem; color:var(--text-light);">No grades recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Attendance View -->
        <div id="attendance-view" class="view-section">
            <div class="section-header">
                <h2 class="section-title">Attendance History</h2>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Present</div>
                    <div class="stat-value" style="color:#10b981;"><?php echo $attendanceStats['present']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Absent</div>
                    <div class="stat-value" style="color:#ef4444;"><?php echo $attendanceStats['absent']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Late</div>
                    <div class="stat-value" style="color:#f59e0b;"><?php echo $attendanceStats['late']; ?></div>
                </div>
            </div>
            <div class="content-card">
                <?php if (!empty($attendance)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance as $record): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'priority-low';
                                            if ($record['status'] == 'present') $statusClass = 'priority-low'; // Greenish via CSS override or new class
                                            elseif ($record['status'] == 'absent') $statusClass = 'priority-high';
                                            elseif ($record['status'] == 'late') $statusClass = 'priority-medium';
                                            ?>
                                            <span class="priority-badge <?php echo $statusClass; ?>" style="<?php if($record['status']=='present') echo 'background:#d1fae5; color:#065f46;'; ?>"><?php echo ucfirst($record['status']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align:center; padding:2rem; color:var(--text-light);">No attendance records found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fees View -->
        <div id="fees-view" class="view-section">
            <div class="section-header">
                <h2 class="section-title">School Fees</h2>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:1.5rem;">
                <?php foreach ($fee_summary as $fee): ?>
                    <div class="content-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                            <h3 style="font-size:1.1rem; font-weight:600;"><?php echo htmlspecialchars($fee['name']); ?></h3>
                            <?php if ($fee['remaining'] <= 0): ?>
                                <span class="priority-badge" style="background:#d1fae5; color:#065f46;">Paid</span>
                            <?php else: ?>
                                <span class="priority-badge priority-high">Pending</span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:var(--text-light); margin-bottom:0.5rem;">
                                <span>Paid: ₦<?php echo number_format($fee['paid']); ?></span>
                                <span>Total: ₦<?php echo number_format($fee['total']); ?></span>
                            </div>
                            <div style="width:100%; height:8px; background:#f3f4f6; border-radius:4px; overflow:hidden;">
                                <div style="height:100%; background:var(--primary); width:<?php echo ($fee['paid'] / $fee['total']) * 100; ?>%;"></div>
                            </div>
                        </div>
                        <?php if ($fee['remaining'] > 0): ?>
                            <div style="text-align:right; color:#ef4444; font-weight:600;">
                                Due: ₦<?php echo number_format($fee['remaining']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Store View -->
        <div id="store-view" class="view-section">
            <div class="section-header">
                <h2 class="section-title">School Store</h2>
            </div>
            <?php if (!empty($store_items)): ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:1.5rem;">
                    <?php foreach ($store_items as $item): ?>
                        <div class="content-card" style="padding:0; overflow:hidden;">
                            <div style="height:150px; background:#f3f4f6; display:flex; align-items:center; justify-content:center;">
                                <?php if ($item['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <i class="fas fa-image" style="font-size:3rem; color:#d1d5db;"></i>
                                <?php endif; ?>
                            </div>
                            <div style="padding:1.5rem;">
                                <h3 style="font-size:1.1rem; font-weight:600; margin-bottom:0.5rem;"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p style="color:var(--text-light); font-size:0.9rem; margin-bottom:1rem;"><?php echo htmlspecialchars($item['description']); ?></p>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                                    <span style="font-weight:700; color:var(--primary);">₦<?php echo number_format($item['price']); ?></span>
                                    <span style="font-size:0.8rem; color:var(--text-light);"><?php echo $item['quantity_available']; ?> left</span>
                                </div>
                                <form method="POST" onsubmit="return confirm('Purchase <?php echo addslashes($item['name']); ?> for ₦<?php echo $item['price']; ?>?')">
                                    <input type="hidden" name="place_order" value="1">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <div style="display:flex; gap:0.5rem;">
                                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $item['quantity_available']; ?>" class="form-control" style="width:70px;">
                                        <button type="submit" class="btn btn-primary" style="flex:1;">Buy</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="content-card" style="text-align:center;">
                    <p>No items available in the store.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Submit Assignment Modal -->
        <div id="submitAssignmentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="section-title">Submit Assignment</h3>
                    <button onclick="closeModal('submitAssignmentModal')" style="background:none;border:none;cursor:pointer;font-size:1.2rem;"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data" id="submitAssignmentForm">
                        <input type="hidden" name="submit_assignment" value="1">
                        <input type="hidden" id="assignment_id" name="assignment_id">

                        <div class="form-group">
                            <label>Assignment</label>
                            <input type="text" id="assignment_title" class="form-control" readonly style="background:#f9fafb;">
                        </div>

                        <div class="form-group">
                            <label>Due Date</label>
                            <input type="text" id="assignment_deadline" class="form-control" readonly style="background:#f9fafb;">
                        </div>

                        <div class="form-group">
                            <label>Upload Work (PDF/Word)</label>
                            <input type="file" name="submission_file" class="form-control" accept=".pdf,.doc,.docx" required>
                            <small style="color:var(--text-light); font-size:0.8rem;">Max size: 10MB</small>
                        </div>

                        <button type="submit" class="btn btn-success" style="width:100%;">Submit Assignment</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Download Assignment Modal -->
        <div id="downloadAssignmentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="section-title">Assignment Details</h3>
                    <button onclick="closeModal('downloadAssignmentModal')" style="background:none;border:none;cursor:pointer;font-size:1.2rem;"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body" id="downloadAssignmentContent">
                    <!-- Content loaded via JS -->
                </div>
            </div>
        </div>

        <!-- All Announcements Modal -->
        <div id="allAnnouncementsModal" class="modal">
            <div class="modal-content" style="max-width:800px;">
                <div class="modal-header">
                    <h3 class="section-title">All Announcements</h3>
                    <button onclick="closeModal('allAnnouncementsModal')" style="background:none;border:none;cursor:pointer;font-size:1.2rem;"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($announcements)): ?>
                        <div style="display:flex; flex-direction:column; gap:1rem;">
                            <?php foreach ($announcements as $announcement): ?>
                                <div style="padding:1rem; background:#f9fafb; border-radius:0.5rem; border-left:4px solid var(--primary);">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                        <span class="priority-badge priority-<?php echo $announcement['priority']; ?>"><?php echo ucfirst($announcement['priority']); ?></span>
                                    </div>
                                    <div style="font-size:0.8rem; color:var(--text-light); margin-bottom:0.5rem;">
                                        <?php echo date('M j, Y g:i A', strtotime($announcement['created_at'])); ?>
                                    </div>
                                    <p style="color:var(--text); font-size:0.9rem; line-height:1.5;"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align:center;">No announcements found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
    <script>
        // View Switching
        function switchView(viewId) {
            // Hide all views
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            // Show selected view
            document.getElementById(viewId).classList.add('active');
            
            // Update sidebar active state
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            // Find the nav item that calls this function (approximate)
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                if(item.getAttribute('onclick').includes(viewId)) {
                    item.classList.add('active');
                }
            });
            
            // Update page title
            const titles = {
                'dashboard-view': 'Dashboard',
                'assignments-view': 'My Assignments',
                'grades-view': 'My Grades',
                'attendance-view': 'Attendance',
                'fees-view': 'School Fees',
                'store-view': 'School Store'
            };
            document.getElementById('page-title').textContent = titles[viewId];
            
            // Close sidebar on mobile
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        // Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Assignment Functions
        function openSubmitModal(id, title, deadline) {
            document.getElementById('assignment_id').value = id;
            document.getElementById('assignment_title').value = title;
            document.getElementById('assignment_deadline').value = new Date(deadline).toLocaleDateString();
            openModal('submitAssignmentModal');
        }

        function openDownloadModal(id, title, description, instructions, filePath, fileName) {
            const content = `
                <div class="form-group">
                    <label>Title</label>
                    <div class="form-control" style="background:#f9fafb;">${title}</div>
                </div>
                <div class="form-group">
                    <label>File</label>
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <span>${fileName}</span>
                        <a href="${filePath}" class="btn btn-sm btn-primary" download>Download</a>
                    </div>
                </div>
            `;
            document.getElementById('downloadAssignmentContent').innerHTML = content;
            openModal('downloadAssignmentModal');
        }
    </script>
</body>
</html>