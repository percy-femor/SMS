<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'db_config.php';

$teacherId = $_SESSION['teacher_id'];
$success = '';
$error = '';

// Handle assignment creation with file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_assignment'])) {
    $title = $_POST['title'];
    $description = $_POST['description'] ?? '';
    $class_id = $_POST['class_id'];
    $assignment_type = $_POST['assignment_type'];
    $deadline = $_POST['deadline'];
    $total_points = $_POST['total_points'] ?? 100;
    $instructions = $_POST['instructions'] ?? '';

    $filePath = null;
    $fileName = null;
    $fileSize = null;

    // Handle file upload
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];

        $fileType = $_FILES['assignment_file']['type'];
        $fileSize = $_FILES['assignment_file']['size'];

        if (isset($allowedTypes[$fileType])) {
            $ext = $allowedTypes[$fileType];
            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assignments';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            $fileName = 'assignment_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $title) . '.' . $ext;
            $dest = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $dest)) {
                $filePath = 'uploads/assignments/' . $fileName;
            } else {
                $error = 'Failed to save uploaded assignment file.';
            }
        } else {
            $error = 'Invalid file type. Only PDF and Word documents are allowed.';
        }
    }

    if (!$error) {
        try {
            $stmt = $pdo->prepare("INSERT INTO assignments (title, description, class_id, teacher_id, assignment_type, deadline, total_points, instructions, file_path, file_name, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $class_id, $teacherId, $assignment_type, $deadline, $total_points, $instructions, $filePath, $fileName, $fileSize]);
            $success = "Assignment created successfully!";
        } catch (PDOException $e) {
            $error = "Error creating assignment: " . $e->getMessage();
        }
    }
}

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_grade'])) {
    $submission_id = $_POST['submission_id'];
    $grade_value = $_POST['grade_value'];
    $feedback = $_POST['feedback'] ?? '';

    try {
        $stmt = $pdo->prepare("UPDATE assignment_submissions SET grade_value = ?, feedback = ?, status = 'graded' WHERE id = ?");
        $stmt->execute([$grade_value, $feedback, $submission_id]);
        $success = "Grade submitted successfully!";
    } catch (PDOException $e) {
        $error = "Error submitting grade: " . $e->getMessage();
    }
}

// Initialize variables with default values
$teacher = null;
$class_count = 0;
$student_count = 0;
$assignment_count = 0;
$announcements = [];
$teacher_classes = [];

try {
    // Get teacher details
    $stmt = $pdo->prepare("SELECT full_name, sex, passport_path, email, phone, subject FROM teachers WHERE id = ?");
    if ($stmt->execute([$teacherId])) {
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get teacher's classes
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(s.id) as student_count 
        FROM classes c 
        LEFT JOIN students s ON c.class_id = s.class_id 
        WHERE c.teacher_id = ? 
        GROUP BY c.class_id
        ORDER BY c.class_name
    ");
    if ($stmt->execute([$teacherId])) {
        $teacher_classes = $stmt->fetchAll();
        $class_count = count($teacher_classes);
    }

    // Get total student count for teacher's classes
    if (!empty($teacher_classes)) {
        $class_ids = array_column($teacher_classes, 'class_id');
        if (!empty($class_ids)) {
            $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
            try {
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT s.id) as total_students
                    FROM students s
                    WHERE s.class_id IN ($placeholders)
                ");
                if ($stmt->execute($class_ids)) {
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $student_count = (int)($result['total_students'] ?? 0);
                }
            } catch (PDOException $e) {
                // If query fails, student_count remains 0
                error_log("Error counting students: " . $e->getMessage());
            }
        }
    }

    // Get assignments with submission counts, grouped by class and creation date
    $assignments = [];
    $assignments_by_class = [];
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, c.class_name, c.class_code,
                   COUNT(DISTINCT s.id) as total_students,
                   COUNT(DISTINCT sub.id) as submissions_count
            FROM assignments a 
            JOIN classes c ON a.class_id = c.class_id 
            LEFT JOIN students s ON c.class_id = s.class_id
            LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = s.id
            WHERE a.teacher_id = ?
            GROUP BY a.id
            ORDER BY c.class_name ASC, DATE(a.created_at) DESC, a.created_at DESC
        ");
        if ($stmt->execute([$teacherId])) {
            $assignments = $stmt->fetchAll();
            
            // Group assignments by class and creation date
            foreach ($assignments as $assignment) {
                $class_name = $assignment['class_name'];
                $created_date = date('Y-m-d', strtotime($assignment['created_at']));
                $date_label = date('F j, Y', strtotime($assignment['created_at']));
                
                if (!isset($assignments_by_class[$class_name])) {
                    $assignments_by_class[$class_name] = [];
                }
                if (!isset($assignments_by_class[$class_name][$created_date])) {
                    $assignments_by_class[$class_name][$created_date] = [
                        'date_label' => $date_label,
                        'assignments' => []
                    ];
                }
                $assignments_by_class[$class_name][$created_date]['assignments'][] = $assignment;
            }
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }

    // Get pending assignments count
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM assignments 
            WHERE teacher_id = ? AND deadline >= CURDATE() AND status = 'active'
        ");
        if ($stmt->execute([$teacherId])) {
            $assignment_count = $stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        $assignment_count = 0;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get teacher announcements

// Auto-expire announcements older than 7 days (optional - uncomment if you want to automatically deactivate old announcements)

try {
    $expire_stmt = $pdo->prepare("
        UPDATE teacher_announcements 
        SET is_active = FALSE 
        WHERE is_active = TRUE 
        AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $expire_stmt->execute();
} catch (PDOException $e) {
    // Silently continue if this fails
    error_log("Auto-expire announcements error: " . $e->getMessage());
}


try {
    $announcements_stmt = $pdo->prepare("
        SELECT ta.*, a.name as admin_name 
        FROM teacher_announcements ta 
        LEFT JOIN admin a ON ta.created_by = a.id 
        WHERE ta.is_active = TRUE 
        AND ta.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY 
            CASE ta.priority 
                WHEN 'high' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'low' THEN 3 
            END,
            ta.created_at DESC
        LIMIT 10
    ");
    $announcements_stmt->execute();
    $announcements = $announcements_stmt->fetchAll();

    // Mark announcements as read for this teacher (tolerate missing reads table)
    try {
        foreach ($announcements as $announcement) {
            $check_read_stmt = $pdo->prepare("
                SELECT id FROM teacher_announcement_reads 
                WHERE announcement_id = ? AND teacher_id = ?
            ");
            $check_read_stmt->execute([$announcement['id'], $teacherId]);

            if (!$check_read_stmt->fetch()) {
                $mark_read_stmt = $pdo->prepare("
                    INSERT INTO teacher_announcement_reads (announcement_id, teacher_id) 
                    VALUES (?, ?)
                ");
                $mark_read_stmt->execute([$announcement['id'], $teacherId]);
            }
        }
    } catch (PDOException $e) {
        // Ignore marking errors to not block announcement display
    }
} catch (PDOException $e) {
    $announcements = [];
    // Show the actual error message for debugging
    $error = "Error loading announcements: " . $e->getMessage();
    // Log the error for debugging
    error_log("Teacher Dashboard - Announcements Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - EduManage</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5; --primary-dark: #4338ca; --secondary: #8b5cf6; --accent: #f59e0b;
            --text: #1f2937; --text-light: #6b7280; --white: #ffffff; --gray-light: #f9fafb;
            --glass: rgba(255, 255, 255, 0.9); --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1); --radius: 1rem;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f3f4f6; color: var(--text); display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: var(--white); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; box-shadow: var(--shadow-md); display: flex; flex-direction: column; transition: transform 0.3s ease; }
        .logo { padding: 2rem; display: flex; align-items: center; gap: 1rem; color: var(--primary); font-size: 1.5rem; font-weight: 700; border-bottom: 1px solid #e5e7eb; }
        .nav-links { flex: 1; padding: 2rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; color: var(--text-light); text-decoration: none; border-radius: 0.75rem; transition: all 0.3s ease; cursor: pointer; }
        .nav-item:hover, .nav-item.active { background: #eef2ff; color: var(--primary); transform: translateX(5px); }
        .nav-item i { width: 24px; text-align: center; }
        .user-profile { padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; align-items: center; gap: 1rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        
        /* Main Content */
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; transition: margin 0.3s ease; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: var(--glass); padding: 1rem 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); backdrop-filter: blur(10px); }
        .page-title { font-size: 1.5rem; font-weight: 600; color: var(--text); }
        .logout-btn { color: #ef4444; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: background 0.2s; }
        .logout-btn:hover { background: #fef2f2; }

        /* Views */
        .view-section { display: none; animation: fadeIn 0.4s ease; }
        .view-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: var(--white); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); transition: transform 0.3s ease; border-left: 4px solid var(--primary); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; background: #eef2ff; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--text); }
        .stat-label { color: var(--text-light); font-size: 0.9rem; }

        /* Tables & Lists */
        .content-card { background: var(--white); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); margin-bottom: 2rem; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .section-title { font-size: 1.25rem; font-weight: 600; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 0.75rem; border: none; font-weight: 500; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.5rem; color: white; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.3); }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }
        .btn-success { background: #10b981; }
        .btn-danger { background: #ef4444; }
        .btn-warning { background: #f59e0b; }
        
        /* Announcements */
        .announcement-item { padding: 1rem; border-bottom: 1px solid #f3f4f6; transition: background 0.2s; }
        .announcement-item:last-child { border-bottom: none; }
        .announcement-item:hover { background: #f9fafb; }
        .announcement-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
        .priority-badge { padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600; }
        .priority-high { background: #fef2f2; color: #ef4444; }
        .priority-medium { background: #fffbeb; color: #f59e0b; }
        .priority-low { background: #f0fdf4; color: #10b981; }

        /* Forms */
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text); }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; transition: all 0.3s; font-family: inherit; }
        .form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }

        /* Modals */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .modal-content { background: var(--white); width: 90%; max-width: 600px; border-radius: var(--radius); box-shadow: var(--shadow-lg); animation: slideUp 0.3s ease; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 1.5rem; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i> EduManage
        </div>
        <div class="nav-links">
            <a class="nav-item active" onclick="switchView('dashboard-view')"><i class="fas fa-th-large"></i> Dashboard</a>
            <a class="nav-item" onclick="switchView('classes-view')"><i class="fas fa-chalkboard-teacher"></i> My Classes</a>
            <a class="nav-item" onclick="switchView('assignments-view')"><i class="fas fa-tasks"></i> Assignments</a>
            <a class="nav-item" onclick="switchView('reports-view')"><i class="fas fa-chart-line"></i> Reports</a>
        </div>
        <div class="user-profile">
            <?php if ($teacher && !empty($teacher['passport_path'])): ?>
                <img src="<?php echo htmlspecialchars($teacher['passport_path']); ?>" class="user-avatar" style="object-fit:cover;">
            <?php else: ?>
                <div class="user-avatar"><?php echo substr($teacher['full_name'] ?? 'T', 0, 1); ?></div>
            <?php endif; ?>
            <div style="flex:1; overflow:hidden;">
                <div style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($teacher['full_name'] ?? 'Teacher'); ?></div>
                <div style="font-size:0.8rem; color:var(--text-light);">Teacher</div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <header class="top-bar">
            <div style="display:flex; align-items:center; gap:1rem;">
                <button class="btn-icon" id="menu-toggle" onclick="toggleSidebar()" style="display:none; background:none; border:none; font-size:1.2rem; cursor:pointer;"><i class="fas fa-bars"></i></button>
                <h1 class="page-title" id="page-title">Dashboard</h1>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </header>

        <!-- Notifications -->
        <?php if ($error): ?>
            <div style="background:#fee2e2; color:#ef4444; padding:1rem; border-radius:0.5rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div style="background:#d1fae5; color:#065f46; padding:1rem; border-radius:0.5rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard View -->
        <div id="dashboard-view" class="view-section active">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chalkboard"></i></div>
                    <div class="stat-value"><?php echo $class_count; ?></div>
                    <div class="stat-label">Active Classes</div>
                </div>
                <div class="stat-card" style="border-left-color: var(--secondary);">
                    <div class="stat-icon" style="color: var(--secondary); background: #f3e8ff;"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-value"><?php echo $student_count; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card" style="border-left-color: var(--accent);">
                    <div class="stat-icon" style="color: var(--accent); background: #fef3c7;"><i class="fas fa-clipboard-list"></i></div>
                    <div class="stat-value"><?php echo $assignment_count; ?></div>
                    <div class="stat-label">Pending Assignments</div>
                </div>
            </div>

            <div class="content-card">
                <div class="section-header">
                    <h3 class="section-title">Recent Announcements</h3>
                </div>
                <?php if (!empty($announcements)): ?>
                    <div class="announcements-list">
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item">
                                <div class="announcement-header">
                                    <span style="font-weight:600;"><?php echo htmlspecialchars($announcement['title']); ?></span>
                                    <span class="priority-badge priority-<?php echo $announcement['priority']; ?>"><?php echo ucfirst($announcement['priority']); ?></span>
                                </div>
                                <p style="color:var(--text-light); margin-bottom:0.5rem;"><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
                                <div style="font-size:0.8rem; color:var(--text-light);">
                                    <i class="far fa-clock"></i> <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align:center; color:var(--text-light); padding:2rem;">No recent announcements</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Classes View -->
        <div id="classes-view" class="view-section">
            <div class="section-header">
                <h2 class="section-title">My Classes</h2>
                <button class="btn btn-warning" onclick="openModal('attendanceModal')"><i class="fas fa-user-check"></i> Take Attendance</button>
            </div>
            <div class="stats-grid">
                <?php if (!empty($teacher_classes)): ?>
                    <?php foreach ($teacher_classes as $class): ?>
                        <div class="stat-card" onclick="openClassDetails(<?php echo $class['class_id']; ?>)" style="cursor:pointer;">
                            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="stat-value" style="font-size:1.5rem;"><?php echo htmlspecialchars($class['class_name']); ?></div>
                            <div class="stat-label"><?php echo htmlspecialchars($class['class_code']); ?></div>
                            <div style="margin-top:1rem; display:flex; justify-content:space-between; align-items:center; font-size:0.9rem; color:var(--text-light);">
                                <span><i class="fas fa-users"></i> <?php echo $class['student_count']; ?> Students</span>
                                <span><?php echo $class['current_enrollment']; ?>/<?php echo $class['max_capacity']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="content-card" style="grid-column: 1/-1; text-align:center;">
                        <p>No classes assigned yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Class Details Container (Hidden by default) -->
            <div id="class-details-container" style="display:none; margin-top:2rem;">
                <div class="content-card">
                    <div class="section-header">
                        <h3 class="section-title" id="class-details-title">Class Details</h3>
                        <button class="btn btn-sm btn-primary" onclick="closeClassDetails()">Back to List</button>
                    </div>
                    <div id="class-details-content">
                        <div style="text-align:center; padding:2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments View -->
        <div id="assignments-view" class="view-section">
            <div class="section-header">
                <h2 class="section-title">Assignments</h2>
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <select id="assignmentFilter" class="form-control" style="width:200px;" onchange="filterAssignments()">
                        <option value="all">All Classes</option>
                        <?php foreach ($teacher_classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" onclick="openModal('assignmentModal')"><i class="fas fa-plus"></i> Create Assignment</button>
                </div>
            </div>
            
            <div class="content-card">
                <?php if (!empty($assignments_by_class)): ?>
                    <?php foreach ($assignments_by_class as $class_name => $dates): ?>
                        <?php 
                            // Get class_id from first assignment in this class
                            $first_assignment = null;
                            foreach ($dates as $date_data) {
                                if (!empty($date_data['assignments'])) {
                                    $first_assignment = $date_data['assignments'][0];
                                    break;
                                }
                            }
                            $class_id = $first_assignment['class_id'] ?? '';
                        ?>
                        <div class="assignment-class-group" data-class-id="<?php echo $class_id; ?>">
                            <div style="background:linear-gradient(135deg, var(--primary), var(--secondary)); color:white; padding:1rem 1.5rem; border-radius:0.5rem; margin-bottom:1rem;">
                                <h3 style="margin:0; font-size:1.25rem; display:flex; align-items:center; gap:0.5rem;">
                                    <i class="fas fa-chalkboard"></i> <?php echo htmlspecialchars($class_name); ?>
                                </h3>
                            </div>
                            
                            <?php foreach ($dates as $date => $date_data): ?>
                                <div style="margin-bottom:2rem;">
                                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem; padding:0.5rem; background:#f9fafb; border-left:4px solid var(--primary); border-radius:0.25rem;">
                                        <i class="fas fa-calendar-alt" style="color:var(--primary);"></i>
                                        <strong style="color:var(--text);"><?php echo $date_data['date_label']; ?></strong>
                                        <span style="color:var(--text-light); font-size:0.9rem;">(<?php echo count($date_data['assignments']); ?> assignment<?php echo count($date_data['assignments']) > 1 ? 's' : ''; ?>)</span>
                                    </div>
                                    
                                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(350px, 1fr)); gap:1rem;">
                                        <?php foreach ($date_data['assignments'] as $assignment): ?>
                                            <div style="border:1px solid #e5e7eb; border-radius:0.75rem; padding:1.5rem; background:white; transition:all 0.3s; cursor:pointer;" 
                                                 onmouseover="this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'; this.style.transform='translateY(-2px)'"
                                                 onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'"
                                                 onclick="viewAssignmentDetails(<?php echo $assignment['id']; ?>)">
                                                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:1rem;">
                                                    <div style="flex:1;">
                                                        <h4 style="margin:0; font-size:1.1rem; color:var(--text); margin-bottom:0.5rem;">
                                                            <?php echo htmlspecialchars($assignment['title']); ?>
                                                        </h4>
                                                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.5rem;">
                                                            <span class="priority-badge priority-medium" style="font-size:0.75rem;">
                                                                <?php echo ucfirst($assignment['assignment_type']); ?>
                                                            </span>
                                                            <span style="font-size:0.8rem; color:var(--text-light);">
                                                                <i class="fas fa-star"></i> <?php echo $assignment['total_points']; ?> points
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem; font-size:0.85rem;">
                                                    <div style="color:var(--text-light);">
                                                        <i class="far fa-calendar-check"></i> Deadline
                                                    </div>
                                                    <div style="font-weight:500; color:<?php echo strtotime($assignment['deadline']) < time() ? '#ef4444' : 'var(--text)'; ?>">
                                                        <?php echo date('M j, Y', strtotime($assignment['deadline'])); ?>
                                                    </div>
                                                    
                                                    <div style="color:var(--text-light);">
                                                        <i class="fas fa-users"></i> Submissions
                                                    </div>
                                                    <div style="font-weight:500;">
                                                        <?php echo $assignment['submissions_count']; ?> / <?php echo $assignment['total_students']; ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($assignment['description'])): ?>
                                                    <div style="font-size:0.85rem; color:var(--text-light); margin-bottom:1rem; max-height:60px; overflow:hidden; text-overflow:ellipsis;">
                                                        <?php echo htmlspecialchars(substr($assignment['description'], 0, 100)); ?><?php echo strlen($assignment['description']) > 100 ? '...' : ''; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div style="display:flex; gap:0.5rem; margin-top:1rem;">
                                                    <button class="btn btn-sm btn-primary" style="flex:1;" onclick="event.stopPropagation(); viewAssignmentDetails(<?php echo $assignment['id']; ?>)">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                    <button class="btn btn-sm" style="background:#e5e7eb; color:var(--text);" onclick="event.stopPropagation(); viewSubmissions(<?php echo $assignment['id']; ?>)">
                                                        <i class="fas fa-list"></i> Submissions
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; padding:2rem; color:var(--text-light);">No assignments created yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reports View -->
        <div id="reports-view" class="view-section">
            <div class="section-header">
                <h2 class="section-title">Performance Reports</h2>
            </div>
            <div class="content-card">
                <div style="text-align:center; padding:3rem;">
                    <i class="fas fa-chart-bar" style="font-size:3rem; color:#e5e7eb; margin-bottom:1rem;"></i>
                    <p style="color:var(--text-light);">Select a class to view detailed performance reports.</p>
                    <div style="margin-top:1rem;">
                        <select class="form-control" style="max-width:300px; margin:0 auto;" onchange="loadClassReports(this.value)">
                            <option value="">Select Class...</option>
                            <?php foreach ($teacher_classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div id="reports-content" style="margin-top:2rem;"></div>
            </div>
        </div>
    <!-- Create Assignment Modal -->
    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create Assignment</h3>
                <button onclick="closeModal('assignmentModal')" style="background:none;border:none;cursor:pointer;font-size:1.2rem;"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="assignmentForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="create_assignment" value="1">
                    <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="form-group"><label>Class</label>
                        <select name="class_id" class="form-control" required>
                            <?php foreach ($teacher_classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Type</label>
                        <select name="assignment_type" class="form-control" required>
                            <option value="quiz">Quiz</option>
                            <option value="homework">Homework</option>
                            <option value="project">Project</option>
                            <option value="exam">Exam</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Deadline</label><input type="date" name="deadline" class="form-control" required min="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="form-group"><label>Points</label><input type="number" name="total_points" class="form-control" value="100"></div>
                    <div class="form-group"><label>File (PDF/Word)</label><input type="file" name="assignment_file" class="form-control" accept=".pdf,.doc,.docx"></div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Create Assignment</button>
                </form>
            </div>
        </div>
    </div>

    <!-- View Submissions Modal -->
    <div id="submissionsModal" class="modal">
        <div class="modal-content" style="max-width:800px;">
            <div class="modal-header">
                <h3>Submissions</h3>
                <button onclick="closeModal('submissionsModal')" style="background:none;border:none;cursor:pointer;font-size:1.2rem;"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="submissionsContent">
                <div style="text-align:center; padding:2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>

    <!-- Assignment Details Modal -->
    <div id="assignmentDetailsModal" class="modal">
        <div class="modal-content" style="max-width:900px;">
            <div class="modal-header">
                <h3 id="assignmentDetailsTitle">Assignment Details</h3>
                <button onclick="closeModal('assignmentDetailsModal')" style="background:none;border:none;cursor:pointer;font-size:1.2rem;"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="assignmentDetailsContent">
                <div style="text-align:center; padding:2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>

    <!-- Grade Submission Modal -->
    <div id="gradeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Grade Submission</h3>
                <button onclick="closeModal('gradeModal')" style="background:none;border:none;cursor:pointer;font-size:1.2rem;"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="gradeForm" method="POST">
                    <input type="hidden" name="submit_grade" value="1">
                    <input type="hidden" id="grade_submission_id" name="submission_id">
                    <div class="form-group"><label>Student</label><input type="text" id="grade_student_name" class="form-control" readonly></div>
                    <div class="form-group"><label>Assignment</label><input type="text" id="grade_assignment_title" class="form-control" readonly></div>
                    <div class="form-group"><label>Grade (Max: <span id="grade_max_points">100</span>)</label><input type="number" id="grade_value" name="grade_value" class="form-control" required></div>
                    <div class="form-group"><label>Feedback</label><textarea name="feedback" class="form-control" rows="3"></textarea></div>
                    <button type="submit" class="btn btn-success" style="width:100%;">Submit Grade</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Attendance Modal -->
    <div id="attendanceModal" class="modal">
        <div class="modal-content" style="max-width:800px;">
            <div class="modal-header">
                <h3>Take Attendance</h3>
                <button onclick="closeModal('attendanceModal')" style="background:none;border:none;cursor:pointer;font-size:1.2rem;"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom:1.5rem;">
                    <select id="attendanceClassSelect" class="form-control" onchange="loadStudentsForAttendance(this.value)">
                        <option value="">Select Class...</option>
                        <?php foreach ($teacher_classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex; gap:1rem; align-items:flex-end; margin-bottom:1rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Date</label>
                        <input type="date" id="attendanceDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div style="display:flex; gap:0.5rem;">
                        <button class="btn btn-sm btn-success" onclick="markAll('present')"><i class="fas fa-user-check"></i> All Present</button>
                        <button class="btn btn-sm btn-danger" onclick="markAll('absent')"><i class="fas fa-user-times"></i> All Absent</button>
                        <button class="btn btn-sm btn-warning" onclick="markAll('late')"><i class="fas fa-clock"></i> All Late</button>
                    </div>
                </div>
                <div id="attendanceStudentsList"></div>
                <div id="attendanceActions" style="display:none; margin-top:1.5rem; display:flex; justify-content:space-between; align-items:center;">
                    <div id="attendanceSummary" style="color:var(--text-light);"></div>
                    <div>
                        <button class="btn btn-success" onclick="saveAttendance()"><i class="fas fa-save"></i> Save Attendance</button>
                    </div>
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
            event.currentTarget.classList.add('active');
            
            // Update page title
            const titles = {
                'dashboard-view': 'Dashboard',
                'classes-view': 'My Classes',
                'assignments-view': 'Assignments',
                'reports-view': 'Performance Reports'
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

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Class Details
        function openClassDetails(classId) {
            document.getElementById('class-details-container').style.display = 'block';
            document.getElementById('class-details-content').innerHTML = '<div style="text-align:center; padding:2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            
            // Fetch class details via AJAX
            fetch(`ajax/get_class_details.php?class_id=${classId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const classInfo = data.class;
                        const students = data.students;
                        
                        let studentsHtml = '';
                        if (students.length > 0) {
                            studentsHtml = `
                                <table style="width:100%; border-collapse:collapse; margin-top:1rem;">
                                    <thead>
                                        <tr style="text-align:left; border-bottom:2px solid #f3f4f6;">
                                            <th style="padding:0.5rem;">Student</th>
                                            <th style="padding:0.5rem;">Email</th>
                                            <th style="padding:0.5rem;">Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${students.map(s => `
                                            <tr style="border-bottom:1px solid #f3f4f6;">
                                                <td style="padding:0.5rem; font-weight:500;">${s.full_name}</td>
                                                <td style="padding:0.5rem; color:var(--text-light);">${s.email}</td>
                                                <td style="padding:0.5rem; color:var(--text-light);">${new Date(s.created_at).toLocaleDateString()}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            `;
                        } else {
                            studentsHtml = '<p style="text-align:center; padding:1rem;">No students enrolled.</p>';
                        }

                        document.getElementById('class-details-title').textContent = `${classInfo.class_name} (${classInfo.class_code})`;
                        document.getElementById('class-details-content').innerHTML = `
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                                <div style="background:#f9fafb; padding:1rem; border-radius:0.5rem;">
                                    <div style="font-size:0.8rem; color:var(--text-light);">Enrollment</div>
                                    <div style="font-size:1.2rem; font-weight:600;">${classInfo.current_enrollment}/${classInfo.max_capacity}</div>
                                </div>
                                <div style="background:#f9fafb; padding:1rem; border-radius:0.5rem;">
                                    <div style="font-size:0.8rem; color:var(--text-light);">Created</div>
                                    <div style="font-size:1.2rem; font-weight:600;">${new Date(classInfo.created_at).toLocaleDateString()}</div>
                                </div>
                            </div>
                            <h4 style="font-weight:600; margin-bottom:1rem;">Enrolled Students</h4>
                            ${studentsHtml}
                        `;
                    } else {
                        document.getElementById('class-details-content').innerHTML = `<p style="color:red;">Error: ${data.message}</p>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('class-details-content').innerHTML = '<p style="color:red;">Error loading details.</p>';
                });
        }

        function closeClassDetails() {
            document.getElementById('class-details-container').style.display = 'none';
        }

        // Attendance
        let attendanceData = {};
        function loadStudentsForAttendance(classId) {
            if (!classId) {
                document.getElementById('attendanceStudentsList').innerHTML = '';
                document.getElementById('attendanceActions').style.display = 'none';
                return;
            }
            
            document.getElementById('attendanceStudentsList').innerHTML = '<div style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            fetch(`ajax/get_class_students.php?class_id=${classId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const students = data.students;
                        if (students.length > 0) {
                            attendanceData = {};
                            students.forEach(s => attendanceData[s.id] = 'present'); // Default present
                            
                            document.getElementById('attendanceStudentsList').innerHTML = `
                                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                    ${students.map(s => `
                                        <div style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem; background:#f9fafb; border-radius:0.5rem;">
                                            <span style="font-weight:500;">${s.full_name}</span>
                                            <div style="display:flex; gap:0.5rem;">
                                                <button class="btn btn-sm btn-success" onclick="markAttendance(this, ${s.id}, 'present')">Present</button>
                                                <button class="btn btn-sm" style="background:#e5e7eb;" onclick="markAttendance(this, ${s.id}, 'absent')">Absent</button>
                                                <button class="btn btn-sm" style="background:#e5e7eb;" onclick="markAttendance(this, ${s.id}, 'late')">Late</button>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            `;
                            document.getElementById('attendanceActions').style.display = 'block';
                            updateAttendanceSummary();
                        } else {
                            document.getElementById('attendanceStudentsList').innerHTML = '<p>No students in this class.</p>';
                            document.getElementById('attendanceActions').style.display = 'none';
                        }
                    }
                });
        }

        function markAttendance(btn, studentId, status) {
            attendanceData[studentId] = status;
            // Reset buttons in this row
            const buttons = btn.parentElement.querySelectorAll('button');
            buttons.forEach(b => {
                b.style.background = '#e5e7eb';
                b.classList.remove('btn-success', 'btn-danger', 'btn-warning');
                b.style.color = 'var(--text)';
            });
            
            // Highlight selected
            if (status === 'present') {
                btn.classList.add('btn-success');
                btn.style.background = '#10b981';
            } else if (status === 'absent') {
                btn.classList.add('btn-danger');
                btn.style.background = '#ef4444';
            } else if (status === 'late') {
                btn.classList.add('btn-warning');
                btn.style.background = '#f59e0b';
            }
            btn.style.color = 'white';
            updateAttendanceSummary();
        }

        function saveAttendance() {
            const classId = document.getElementById('attendanceClassSelect').value;
            const dateInput = document.getElementById('attendanceDate');
            const dateVal = (dateInput && dateInput.value) ? dateInput.value : new Date().toISOString().split('T')[0];

            fetch('ajax/save_attendance.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    classId: classId,
                    attendanceData: attendanceData,
                    date: dateVal
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.createElement('div');
                    notification.style.cssText = 'position:fixed; top:20px; right:20px; background:#10b981; color:white; padding:1rem 1.5rem; border-radius:0.5rem; box-shadow:0 4px 6px rgba(0,0,0,0.1); z-index:2000; display:flex; align-items:center; gap:0.5rem;';
                    notification.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message || 'Attendance saved successfully!'}`;
                    document.body.appendChild(notification);
                    setTimeout(() => { notification.style.opacity = '0'; notification.style.transition = 'opacity 0.3s'; setTimeout(() => notification.remove(), 300); }, 3000);

                    closeModal('attendanceModal');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(() => {
                alert('Network error while saving attendance.');
            });
        }

        function markAll(status) {
            // Apply status to all students
            Object.keys(attendanceData).forEach(id => attendanceData[id] = status);
            // Update UI buttons accordingly
            document.querySelectorAll('#attendanceStudentsList > div > div').forEach(row => {
                const buttons = row.querySelectorAll('button');
                buttons.forEach(b => {
                    b.style.background = '#e5e7eb';
                    b.classList.remove('btn-success', 'btn-danger', 'btn-warning');
                    b.style.color = 'var(--text)';
                });
                const targetBtn = Array.from(buttons).find(b => b.textContent.toLowerCase().includes(status));
                if (targetBtn) {
                    if (status === 'present') { targetBtn.classList.add('btn-success'); targetBtn.style.background = '#10b981'; }
                    if (status === 'absent') { targetBtn.classList.add('btn-danger'); targetBtn.style.background = '#ef4444'; }
                    if (status === 'late') { targetBtn.classList.add('btn-warning'); targetBtn.style.background = '#f59e0b'; }
                    targetBtn.style.color = 'white';
                }
            });
            updateAttendanceSummary();
        }

        function updateAttendanceSummary() {
            const counts = { present: 0, absent: 0, late: 0 };
            Object.values(attendanceData).forEach(s => { if (counts[s] !== undefined) counts[s]++; });
            const total = Object.keys(attendanceData).length;
            const summaryEl = document.getElementById('attendanceSummary');
            if (summaryEl) {
                summaryEl.innerHTML = `Total: ${total} | Present: <span style="color:#10b981; font-weight:600;">${counts.present}</span> | Absent: <span style="color:#ef4444; font-weight:600;">${counts.absent}</span> | Late: <span style="color:#f59e0b; font-weight:600;">${counts.late}</span>`;
            }
        }

        // Assignment Details
        function viewAssignmentDetails(assignmentId) {
            openModal('assignmentDetailsModal');
            document.getElementById('assignmentDetailsContent').innerHTML = '<div style="text-align:center; padding:2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            
            fetch(`ajax/get_assignment_details.php?assignment_id=${assignmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const assignment = data.assignment;
                        const submissions = data.submissions;
                        const pending = data.pending_students;
                        
                        const submissionRate = assignment.total_students > 0 ? 
                            Math.round((assignment.submissions_count / assignment.total_students) * 100) : 0;
                        const gradedRate = assignment.submissions_count > 0 ? 
                            Math.round((assignment.graded_count / assignment.submissions_count) * 100) : 0;
                        
                        document.getElementById('assignmentDetailsTitle').textContent = assignment.title;
                        document.getElementById('assignmentDetailsContent').innerHTML = `
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
                                <div style="background:#eef2ff; padding:1rem; border-radius:0.5rem; border-left:4px solid var(--primary);">
                                    <div style="font-size:0.8rem; color:var(--text-light); margin-bottom:0.5rem;">Class</div>
                                    <div style="font-weight:600; font-size:1.1rem;">${assignment.class_name}</div>
                                    <div style="font-size:0.85rem; color:var(--text-light);">${assignment.class_code}</div>
                                </div>
                                <div style="background:#fef3c7; padding:1rem; border-radius:0.5rem; border-left:4px solid var(--accent);">
                                    <div style="font-size:0.8rem; color:var(--text-light); margin-bottom:0.5rem;">Type</div>
                                    <div style="font-weight:600; font-size:1.1rem;">${assignment.assignment_type.charAt(0).toUpperCase() + assignment.assignment_type.slice(1)}</div>
                                    <div style="font-size:0.85rem; color:var(--text-light);">${assignment.total_points} points</div>
                                </div>
                                <div style="background:#fee2e2; padding:1rem; border-radius:0.5rem; border-left:4px solid #ef4444;">
                                    <div style="font-size:0.8rem; color:var(--text-light); margin-bottom:0.5rem;">Deadline</div>
                                    <div style="font-weight:600; font-size:1.1rem;">${new Date(assignment.deadline).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</div>
                                    <div style="font-size:0.85rem; color:var(--text-light);">${new Date(assignment.deadline) < new Date() ? 'Overdue' : 'Active'}</div>
                                </div>
                                <div style="background:#d1fae5; padding:1rem; border-radius:0.5rem; border-left:4px solid #10b981;">
                                    <div style="font-size:0.8rem; color:var(--text-light); margin-bottom:0.5rem;">Created</div>
                                    <div style="font-weight:600; font-size:1.1rem;">${new Date(assignment.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</div>
                                    <div style="font-size:0.85rem; color:var(--text-light);">${new Date(assignment.created_at).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}</div>
                                </div>
                            </div>
                            
                            <div style="background:#f9fafb; padding:1.5rem; border-radius:0.5rem; margin-bottom:2rem;">
                                <h4 style="margin:0 0 1rem 0; color:var(--text);">Description</h4>
                                <p style="color:var(--text-light); line-height:1.6; white-space:pre-wrap;">${assignment.description || 'No description provided.'}</p>
                            </div>
                            
                            ${assignment.instructions ? `
                                <div style="background:#fffbeb; padding:1.5rem; border-radius:0.5rem; margin-bottom:2rem; border-left:4px solid var(--accent);">
                                    <h4 style="margin:0 0 1rem 0; color:var(--text); display:flex; align-items:center; gap:0.5rem;">
                                        <i class="fas fa-list-check"></i> Instructions
                                    </h4>
                                    <p style="color:var(--text-light); line-height:1.6; white-space:pre-wrap;">${assignment.instructions}</p>
                                </div>
                            ` : ''}
                            
                            ${assignment.file_path ? `
                                <div style="margin-bottom:2rem;">
                                    <a href="${assignment.file_path}" class="btn btn-primary" download style="display:inline-flex; align-items:center; gap:0.5rem;">
                                        <i class="fas fa-download"></i> Download Assignment File
                                    </a>
                                </div>
                            ` : ''}
                            
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:1rem; margin-bottom:2rem;">
                                <div style="text-align:center; padding:1rem; background:#eef2ff; border-radius:0.5rem;">
                                    <div style="font-size:2rem; font-weight:700; color:var(--primary);">${assignment.total_students}</div>
                                    <div style="font-size:0.85rem; color:var(--text-light);">Total Students</div>
                                </div>
                                <div style="text-align:center; padding:1rem; background:#d1fae5; border-radius:0.5rem;">
                                    <div style="font-size:2rem; font-weight:700; color:#10b981;">${assignment.submissions_count}</div>
                                    <div style="font-size:0.85rem; color:var(--text-light);">Submissions</div>
                                    <div style="font-size:0.75rem; color:var(--text-light); margin-top:0.25rem;">${submissionRate}%</div>
                                </div>
                                <div style="text-align:center; padding:1rem; background:#fef3c7; border-radius:0.5rem;">
                                    <div style="font-size:2rem; font-weight:700; color:var(--accent);">${assignment.graded_count || 0}</div>
                                    <div style="font-size:0.85rem; color:var(--text-light);">Graded</div>
                                    <div style="font-size:0.75rem; color:var(--text-light); margin-top:0.25rem;">${gradedRate}%</div>
                                </div>
                                <div style="text-align:center; padding:1rem; background:#fee2e2; border-radius:0.5rem;">
                                    <div style="font-size:2rem; font-weight:700; color:#ef4444;">${pending.length}</div>
                                    <div style="font-size:0.85rem; color:var(--text-light);">Pending</div>
                                </div>
                            </div>
                            
                            <div style="display:flex; gap:0.5rem; margin-bottom:2rem;">
                                <button class="btn btn-primary" onclick="viewSubmissions(${assignment.id})" style="flex:1;">
                                    <i class="fas fa-list"></i> View All Submissions
                                </button>
                                <button class="btn" style="background:#e5e7eb; color:var(--text);" onclick="downloadAllSubmissions(${assignment.id})">
                                    <i class="fas fa-download"></i> Download All
                                </button>
                            </div>
                            
                            <div style="border-top:2px solid #e5e7eb; padding-top:1.5rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                                    <h4 style="margin:0; color:var(--text);">
                                        <i class="fas fa-file-alt"></i> Submissions (${submissions.length})
                                        <span style="font-size:0.9rem; font-weight:400; color:var(--text-light); margin-left:0.5rem;">
                                            (${assignment.graded_count || 0} graded, ${submissions.length - (assignment.graded_count || 0)} pending)
                                        </span>
                                    </h4>
                                    <div style="display:flex; gap:0.5rem;">
                                        <button class="btn btn-sm" style="background:#e5e7eb; color:var(--text);" onclick="expandAllGrading()">
                                            <i class="fas fa-expand"></i> Expand All
                                        </button>
                                        <button class="btn btn-sm" style="background:#e5e7eb; color:var(--text);" onclick="collapseAllGrading()">
                                            <i class="fas fa-compress"></i> Collapse All
                                        </button>
                                    </div>
                                </div>
                                ${submissions.length > 0 ? `
                                    <div style="display:flex; flex-direction:column; gap:1rem; max-height:600px; overflow-y:auto;">
                                        ${submissions.map((sub, index) => `
                                            <div id="submission-${sub.id}" style="padding:1.5rem; border:2px solid ${sub.grade_value ? '#d1fae5' : '#e5e7eb'}; border-radius:0.75rem; background:${sub.grade_value ? '#f0fdf4' : 'white'}; transition:all 0.3s;">
                                                <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:1rem;">
                                                    <div style="flex:1;">
                                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                                                            <div style="font-weight:600; font-size:1.1rem;">${sub.student_name}</div>
                                                            ${sub.grade_value ? 
                                                                `<span style="padding:0.25rem 0.75rem; background:#10b981; color:white; border-radius:1rem; font-size:0.75rem; font-weight:600;">
                                                                    Graded: ${sub.grade_value}/${assignment.total_points}
                                                                </span>` 
                                                                : 
                                                                `<span style="padding:0.25rem 0.75rem; background:#fef3c7; color:#92400e; border-radius:1rem; font-size:0.75rem; font-weight:600;">
                                                                    Pending
                                                                </span>`
                                                            }
                                                        </div>
                                                        <div style="font-size:0.85rem; color:var(--text-light); margin-bottom:0.5rem;">
                                                            <i class="far fa-clock"></i> Submitted: ${new Date(sub.submitted_at).toLocaleString()}
                                                        </div>
                                                        ${sub.file_path ? `
                                                            <a href="${sub.file_path}" class="btn btn-sm btn-primary" download style="margin-top:0.5rem;">
                                                                <i class="fas fa-download"></i> Download Submission
                                                            </a>
                                                        ` : ''}
                                                    </div>
                                                </div>
                                                
                                                <div id="grading-form-${sub.id}" style="display:${sub.grade_value ? 'none' : 'block'}; margin-top:1rem; padding-top:1rem; border-top:1px solid #e5e7eb;">
                                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                                                        <div>
                                                            <label style="display:block; font-weight:500; margin-bottom:0.5rem; color:var(--text);">
                                                                Grade (Max: ${assignment.total_points} points)
                                                            </label>
                                                            <input type="number" 
                                                                   id="grade-input-${sub.id}" 
                                                                   class="form-control" 
                                                                   min="0" 
                                                                   max="${assignment.total_points}" 
                                                                   step="0.01"
                                                                   value="${sub.grade_value || ''}"
                                                                   placeholder="Enter grade"
                                                                   style="width:100%;">
                                                        </div>
                                                        <div style="display:flex; align-items:end;">
                                                            <button class="btn btn-success" 
                                                                    onclick="submitGrade(${sub.id}, ${assignment.total_points}, ${assignment.id})"
                                                                    style="width:100%;">
                                                                <i class="fas fa-check"></i> Submit Grade
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label style="display:block; font-weight:500; margin-bottom:0.5rem; color:var(--text);">
                                                            Feedback (Optional)
                                                        </label>
                                                        <textarea id="feedback-input-${sub.id}" 
                                                                  class="form-control" 
                                                                  rows="3" 
                                                                  placeholder="Enter feedback for the student..."
                                                                  style="width:100%; resize:vertical;">${sub.feedback || ''}</textarea>
                                                    </div>
                                                </div>
                                                
                                                ${sub.grade_value ? `
                                                    <div id="grade-display-${sub.id}" style="margin-top:1rem; padding-top:1rem; border-top:1px solid #e5e7eb;">
                                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                                            <div>
                                                                <div style="font-weight:600; color:var(--text); margin-bottom:0.25rem;">
                                                                    Grade: <span style="color:#10b981;">${sub.grade_value}/${assignment.total_points}</span>
                                                                    (${Math.round((sub.grade_value / assignment.total_points) * 100)}%)
                                                                </div>
                                                                ${sub.feedback ? `
                                                                    <div style="background:#f9fafb; padding:0.75rem; border-radius:0.5rem; margin-top:0.5rem; font-size:0.9rem; color:var(--text-light);">
                                                                        <strong>Feedback:</strong> ${sub.feedback}
                                                                    </div>
                                                                ` : ''}
                                                            </div>
                                                            <button class="btn btn-sm btn-warning" 
                                                                    onclick="toggleGradingForm(${sub.id}, ${assignment.total_points}, '${(sub.feedback || '').replace(/'/g, "\\'")}')">
                                                                <i class="fas fa-edit"></i> Edit Grade
                                                            </button>
                                                        </div>
                                                    </div>
                                                ` : ''}
                                            </div>
                                        `).join('')}
                                    </div>
                                ` : '<p style="text-align:center; color:var(--text-light); padding:1rem;">No submissions yet.</p>'}
                                
                                ${pending.length > 0 ? `
                                    <div style="margin-top:1.5rem; padding-top:1.5rem; border-top:2px solid #e5e7eb;">
                                        <h4 style="margin:0 0 1rem 0; color:var(--text);">Pending Students (${pending.length})</h4>
                                        <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                                            ${pending.map(s => `<span style="padding:0.5rem 1rem; background:#fee2e2; border-radius:0.25rem; font-size:0.85rem;">${s.full_name}</span>`).join('')}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    } else {
                        document.getElementById('assignmentDetailsContent').innerHTML = `<p style="color:red; text-align:center;">Error: ${data.message}</p>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('assignmentDetailsContent').innerHTML = '<p style="color:red; text-align:center;">Error loading assignment details.</p>';
                });
        }

        // Filter assignments
        function filterAssignments() {
            const filterValue = document.getElementById('assignmentFilter').value;
            const groups = document.querySelectorAll('.assignment-class-group');
            
            groups.forEach(group => {
                if (filterValue === 'all' || group.dataset.classId === filterValue) {
                    group.style.display = 'block';
                } else {
                    group.style.display = 'none';
                }
            });
        }

        // Download all submissions
        function downloadAllSubmissions(assignmentId) {
            window.location.href = `ajax/download_all_submissions.php?assignment_id=${assignmentId}`;
        }

        // Toggle grading form
        function toggleGradingForm(submissionId, maxPoints, currentFeedback = '') {
            const formDiv = document.getElementById(`grading-form-${submissionId}`);
            const displayDiv = document.getElementById(`grade-display-${submissionId}`);
            
            if (formDiv.style.display === 'none') {
                formDiv.style.display = 'block';
                if (displayDiv) displayDiv.style.display = 'none';
                
                // Set current values
                const gradeInput = document.getElementById(`grade-input-${submissionId}`);
                const feedbackInput = document.getElementById(`feedback-input-${submissionId}`);
                if (gradeInput) {
                    const currentGrade = displayDiv ? displayDiv.textContent.match(/(\d+)\/(\d+)/)?.[1] : '';
                    if (currentGrade) gradeInput.value = currentGrade;
                }
                if (feedbackInput && currentFeedback) {
                    feedbackInput.value = currentFeedback;
                }
            } else {
                formDiv.style.display = 'none';
                if (displayDiv) displayDiv.style.display = 'block';
            }
        }

        // Submit grade via AJAX
        function submitGrade(submissionId, maxPoints, assignmentId) {
            const gradeInput = document.getElementById(`grade-input-${submissionId}`);
            const feedbackInput = document.getElementById(`feedback-input-${submissionId}`);
            
            const gradeValue = parseFloat(gradeInput.value);
            const feedback = feedbackInput.value.trim();
            
            // Validation
            if (isNaN(gradeValue) || gradeValue < 0) {
                alert('Please enter a valid grade (0 or higher)');
                return;
            }
            
            if (gradeValue > maxPoints) {
                alert(`Grade cannot exceed maximum points (${maxPoints})`);
                return;
            }
            
            // Disable button during submission
            const submitBtn = gradeInput.closest('.form-control').nextElementSibling?.querySelector('button');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            }
            
            // Submit via AJAX
            fetch('ajax/submit_grade.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    submission_id: submissionId,
                    grade_value: gradeValue,
                    feedback: feedback
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload assignment details to show updated grade
                    viewAssignmentDetails(assignmentId);
                    
                    // Show success message
                    const notification = document.createElement('div');
                    notification.style.cssText = 'position:fixed; top:20px; right:20px; background:#10b981; color:white; padding:1rem 1.5rem; border-radius:0.5rem; box-shadow:0 4px 6px rgba(0,0,0,0.1); z-index:2000; display:flex; align-items:center; gap:0.5rem;';
                    notification.innerHTML = `<i class="fas fa-check-circle"></i> Grade submitted successfully!`;
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.style.opacity = '0';
                        notification.style.transition = 'opacity 0.3s';
                        setTimeout(() => notification.remove(), 300);
                    }, 3000);
                } else {
                    alert('Error: ' + data.message);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Grade';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the grade. Please try again.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Grade';
                }
            });
        }

        // Expand all grading forms
        function expandAllGrading() {
            const allForms = document.querySelectorAll('[id^="grading-form-"]');
            const allDisplays = document.querySelectorAll('[id^="grade-display-"]');
            
            allForms.forEach(form => {
                form.style.display = 'block';
            });
            
            allDisplays.forEach(display => {
                if (display) display.style.display = 'none';
            });
        }

        // Collapse all grading forms
        function collapseAllGrading() {
            const allForms = document.querySelectorAll('[id^="grading-form-"]');
            const allDisplays = document.querySelectorAll('[id^="grade-display-"]');
            
            allForms.forEach(form => {
                // Only hide if there's a grade display (meaning it's already graded)
                const submissionId = form.id.replace('grading-form-', '');
                const displayDiv = document.getElementById(`grade-display-${submissionId}`);
                if (displayDiv) {
                    form.style.display = 'none';
                    displayDiv.style.display = 'block';
                }
            });
        }

        // Submissions
        function viewSubmissions(assignmentId) {
            openModal('submissionsModal');
            document.getElementById('submissionsContent').innerHTML = '<div style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            fetch(`ajax/get_assignment_submissions.php?assignment_id=${assignmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const submissions = data.submissions;
                        if (submissions.length > 0) {
                            document.getElementById('submissionsContent').innerHTML = `
                                <div style="display:flex; flex-direction:column; gap:1rem;">
                                    ${submissions.map(sub => `
                                        <div style="padding:1rem; border:1px solid #e5e7eb; border-radius:0.5rem; display:flex; justify-content:space-between; align-items:center;">
                                            <div>
                                                <div style="font-weight:600;">${sub.student_name}</div>
                                                <div style="font-size:0.8rem; color:var(--text-light);">Submitted: ${new Date(sub.submitted_at).toLocaleDateString()}</div>
                                            </div>
                                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                                <a href="${sub.file_path}" class="btn btn-sm btn-primary" download>Download</a>
                                                ${sub.grade_value ? 
                                                    `<span class="priority-badge priority-low">Graded: ${sub.grade_value}</span>
                                                     <button class="btn btn-sm btn-warning" onclick="openGradeModal(${sub.id}, '${sub.student_name}', '${data.assignment.title}', ${data.assignment.total_points}, ${sub.grade_value}, '${sub.feedback || ''}')"><i class="fas fa-edit"></i></button>` 
                                                    : 
                                                    `<button class="btn btn-sm btn-success" onclick="openGradeModal(${sub.id}, '${sub.student_name}', '${data.assignment.title}', ${data.assignment.total_points})">Grade</button>`
                                                }
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            `;
                        } else {
                            document.getElementById('submissionsContent').innerHTML = '<p style="text-align:center;">No submissions yet.</p>';
                        }
                    }
                });
        }

        function openGradeModal(subId, studentName, assignTitle, maxPoints, currentGrade = '', feedback = '') {
            document.getElementById('grade_submission_id').value = subId;
            document.getElementById('grade_student_name').value = studentName;
            document.getElementById('grade_assignment_title').value = assignTitle;
            document.getElementById('grade_max_points').textContent = maxPoints;
            document.getElementById('grade_value').value = currentGrade;
            document.querySelector('#gradeForm textarea[name="feedback"]').value = feedback;
            openModal('gradeModal');
        }

        // Reports
        function loadClassReports(classId) {
            if (!classId) {
                document.getElementById('reports-content').innerHTML = '';
                return;
            }
            document.getElementById('reports-content').innerHTML = '<div style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading stats...</div>';
            
            fetch(`ajax/get_student_statistics.php?class_id=${classId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const overview = data.overview;
                        document.getElementById('reports-content').innerHTML = `
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-label">Avg Attendance</div>
                                    <div class="stat-value">${overview.avg_attendance_rate}%</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-label">Avg Grade</div>
                                    <div class="stat-value">${overview.avg_grade || 'N/A'}</div>
                                </div>
                            </div>
                            <!-- Add more charts here if needed -->
                        `;
                    } else {
                        document.getElementById('reports-content').innerHTML = '<p>Error loading reports.</p>';
                    }
                });
        }
    </script>

    </main>
</body>
</html>