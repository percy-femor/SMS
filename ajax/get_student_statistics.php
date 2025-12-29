<?php
session_start();
require_once '../db_config.php';

header('Content-Type: application/json');

// Check if user is logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Teacher login required']);
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Get class_id from request
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;
$all_classes = isset($_GET['all_classes']) && $_GET['all_classes'] == '1';

try {
    // Get teacher's classes to verify access
    $stmt = $pdo->prepare("SELECT class_id FROM classes WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $teacher_classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($teacher_classes)) {
        echo json_encode([
            'success' => true, 
            'overview' => ['total_students' => 0, 'class_count' => 0],
            'students' => [],
            'attendance' => [],
            'grades' => []
        ]);
        exit();
    }
    
    // Build query based on parameters
    if ($class_id && in_array($class_id, $teacher_classes)) {
        // Get statistics for specific class
        $class_filter = "s.class_id = ?";
        $params = [$class_id];
    } else if ($all_classes) {
        // Get statistics for all teacher's classes
        $placeholders = str_repeat('?,', count($teacher_classes) - 1) . '?';
        $class_filter = "s.class_id IN ($placeholders)";
        $params = $teacher_classes;
    } else {
        // Default: get statistics for all teacher's classes
        $placeholders = str_repeat('?,', count($teacher_classes) - 1) . '?';
        $class_filter = "s.class_id IN ($placeholders)";
        $params = $teacher_classes;
    }
    
    // Get student overview
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT s.id) as total_students,
            COUNT(DISTINCT s.class_id) as class_count,
            c.class_name,
            c.class_code
        FROM students s
        JOIN classes c ON s.class_id = c.class_id
        WHERE $class_filter
        GROUP BY c.class_id
        ORDER BY c.class_name
    ");
    $stmt->execute($params);
    $overview = $stmt->fetchAll();
    
    // Get detailed student information
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.full_name,
            s.email,
            s.passport_path,
            c.class_name,
            c.class_code,
            COUNT(DISTINCT a.id) as total_assignments,
            COUNT(DISTINCT sub.id) as submitted_assignments,
            AVG(g.score) as average_score,
            COUNT(DISTINCT att.attendance_id) as total_attendance_days,
            SUM(CASE WHEN att.status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN att.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN att.status = 'late' THEN 1 ELSE 0 END) as late_days
        FROM students s
        JOIN classes c ON s.class_id = c.class_id
        LEFT JOIN assignments a ON s.class_id = a.class_id
        LEFT JOIN assignment_submissions sub ON s.id = sub.student_id AND a.id = sub.assignment_id
        LEFT JOIN grades g ON s.id = g.student_id
        LEFT JOIN attendance att ON s.id = att.student_id AND att.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        WHERE $class_filter
        GROUP BY s.id
        ORDER BY s.full_name
    ");
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate attendance rates for each student
    foreach ($students as &$student) {
        $total_days = $student['total_attendance_days'];
        if ($total_days > 0) {
            $student['attendance_rate'] = round(($student['present_days'] / $total_days) * 100, 1);
        } else {
            $student['attendance_rate'] = 0;
        }
        
        // Calculate assignment completion rate
        $total_assignments = $student['total_assignments'];
        if ($total_assignments > 0) {
            $student['completion_rate'] = round(($student['submitted_assignments'] / $total_assignments) * 100, 1);
        } else {
            $student['completion_rate'] = 0;
        }
        
        // Format average score
        $student['average_score'] = $student['average_score'] ? round($student['average_score'], 1) : 'N/A';
    }
    
    // Get grade distribution
    $stmt = $pdo->prepare("
        SELECT 
            g.grade,
            COUNT(*) as count
        FROM grades g
        JOIN students s ON g.student_id = s.id
        WHERE $class_filter
        GROUP BY g.grade
        ORDER BY FIELD(g.grade, 'A', 'B', 'C', 'D', 'F')
    ");
    $stmt->execute($params);
    $grade_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get attendance summary
    $stmt = $pdo->prepare("
        SELECT 
            att.status,
            COUNT(*) as count,
            DATE_FORMAT(att.date, '%Y-%m') as month
        FROM attendance att
        JOIN students s ON att.student_id = s.id
        WHERE $class_filter AND att.date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        GROUP BY att.status, DATE_FORMAT(att.date, '%Y-%m')
        ORDER BY month DESC, att.status
    ");
    $stmt->execute($params);
    $attendance_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate overview statistics
    $total_students = count($students);
    $total_attendance_records = 0;
    $present_count = 0;
    $absent_count = 0;
    $late_count = 0;
    
    // Calculate attendance summary from attendance_stats
    foreach ($attendance_stats as $stat) {
        $count = (int)$stat['count'];
        $total_attendance_records += $count;
        if ($stat['status'] == 'present') {
            $present_count += $count;
        } elseif ($stat['status'] == 'absent') {
            $absent_count += $count;
        } elseif ($stat['status'] == 'late') {
            $late_count += $count;
        }
    }
    
    // Calculate average attendance rate from students
    $total_attendance_rate = 0;
    $students_with_attendance = 0;
    foreach ($students as $student) {
        if (isset($student['attendance_rate']) && $student['attendance_rate'] > 0) {
            $total_attendance_rate += $student['attendance_rate'];
            $students_with_attendance++;
        }
    }
    $avg_attendance_rate = $students_with_attendance > 0 ? round($total_attendance_rate / $students_with_attendance, 1) : 0;
    
    // Calculate average grade and total grades
    $total_grades = 0;
    $total_score = 0;
    $students_with_grades = 0;
    foreach ($students as $student) {
        if (isset($student['average_score']) && $student['average_score'] !== 'N/A' && is_numeric($student['average_score'])) {
            $total_score += (float)$student['average_score'];
            $students_with_grades++;
        }
    }
    $avg_grade = $students_with_grades > 0 ? round($total_score / $students_with_grades, 1) : 'N/A';
    
    // Count total grades from grade_distribution
    foreach ($grade_distribution as $dist) {
        $total_grades += (int)$dist['count'];
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'overview' => [
            'total_students' => $total_students,
            'class_count' => count($overview),
            'classes' => $overview,
            'avg_attendance_rate' => $avg_attendance_rate,
            'avg_grade' => $avg_grade,
            'total_grades' => $total_grades,
            'total_attendance_records' => $total_attendance_records,
            'attendance_stats' => [
                'present' => $present_count,
                'absent' => $absent_count,
                'late' => $late_count
            ]
        ],
        'students' => $students,
        'grade_distribution' => $grade_distribution,
        'attendance_stats' => $attendance_stats
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error in get_student_statistics.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>