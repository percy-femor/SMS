<?php
session_start();
require_once '../db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['student_id'];

try {
    // Get student basic info
    $stmt = $pdo->prepare("
        SELECT s.*, c.class_name, c.class_code 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        WHERE s.id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }

    // Get comprehensive grade statistics
    $stmt = $pdo->prepare("
        SELECT 
            assignment_type,
            grade,
            score,
            COUNT(*) as count,
            AVG(score) as avg_score,
            MAX(score) as max_score,
            MIN(score) as min_score
        FROM grades 
        WHERE student_id = ? 
        GROUP BY assignment_type, grade
        ORDER BY assignment_type, score DESC
    ");
    $stmt->execute([$student_id]);
    $grade_stats = $stmt->fetchAll();

    // Get grade distribution
    $stmt = $pdo->prepare("
        SELECT 
            grade,
            COUNT(*) as count
        FROM grades 
        WHERE student_id = ?
        GROUP BY grade
        ORDER BY FIELD(grade, 'A', 'B', 'C', 'D', 'F')
    ");
    $stmt->execute([$student_id]);
    $grade_distribution = $stmt->fetchAll();

    // Get attendance statistics (last 90 days)
    $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            DATE_FORMAT(date, '%Y-%m') as month
        FROM attendance 
        WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        GROUP BY status, DATE_FORMAT(date, '%Y-%m')
        ORDER BY month DESC, status
    ");
    $stmt->execute([$student_id]);
    $attendance_stats = $stmt->fetchAll();

    // Get overall attendance summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
        FROM attendance 
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $attendance_summary = $stmt->fetch();

    // Calculate percentages
    $total_days = $attendance_summary['total_days'];
    $attendance_rate = $total_days > 0 ? round(($attendance_summary['present_days'] / $total_days) * 100, 1) : 0;

    // Get assignment completion stats
    $stmt = $pdo->prepare("
        SELECT 
            a.assignment_type,
            COUNT(*) as total_assignments,
            SUM(CASE WHEN g.grade IS NOT NULL THEN 1 ELSE 0 END) as completed_assignments
        FROM assignments a
        LEFT JOIN grades g ON a.id = g.assignment_id AND g.student_id = ?
        WHERE a.class_id = ?
        GROUP BY a.assignment_type
    ");
    $stmt->execute([$student_id, $student['class_id']]);
    $assignment_stats = $stmt->fetchAll();

    // Calculate overall average grade
    $stmt = $pdo->prepare("SELECT AVG(score) as overall_avg FROM grades WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $overall_avg = $stmt->fetch();
    $overall_average = $overall_avg['overall_avg'] ? round($overall_avg['overall_avg'], 1) : null;

    // Prepare response data
    $overview = [
        'student_name' => $student['full_name'],
        'class_name' => $student['class_name'],
        'class_code' => $student['class_code'],
        'total_grades' => count($grade_stats),
        'overall_average' => $overall_average,
        'attendance_rate' => $attendance_rate,
        'total_attendance_days' => $total_days,
        'present_days' => $attendance_summary['present_days'],
        'absent_days' => $attendance_summary['absent_days'],
        'late_days' => $attendance_summary['late_days']
    ];

    // Prepare grade distribution for charts
    $grade_dist = [];
    foreach ($grade_distribution as $dist) {
        $grade_dist[$dist['grade']] = (int)$dist['count'];
    }

    // Prepare assignment completion rates
    $completion_rates = [];
    foreach ($assignment_stats as $stat) {
        $completion_rate = $stat['total_assignments'] > 0 ? 
            round(($stat['completed_assignments'] / $stat['total_assignments']) * 100, 1) : 0;
        $completion_rates[$stat['assignment_type']] = $completion_rate;
    }

    echo json_encode([
        'success' => true,
        'overview' => $overview,
        'grade_distribution' => $grade_dist,
        'grade_stats' => $grade_stats,
        'attendance_stats' => $attendance_stats,
        'completion_rates' => $completion_rates,
        'attendance_summary' => $attendance_summary
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_students_statistics.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>