<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['assignment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
    exit();
}

$teacherId = $_SESSION['teacher_id'];
$assignmentId = $_GET['assignment_id'];

try {
    // Get assignment details with class and submission info
    $stmt = $pdo->prepare("
        SELECT a.*, 
               c.class_name, 
               c.class_code,
               COUNT(DISTINCT s.id) as total_students,
               COUNT(DISTINCT sub.id) as submissions_count,
               COUNT(DISTINCT CASE WHEN sub.grade_value IS NOT NULL THEN sub.id END) as graded_count
        FROM assignments a 
        JOIN classes c ON a.class_id = c.class_id 
        LEFT JOIN students s ON c.class_id = s.class_id
        LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = s.id
        WHERE a.id = ? AND a.teacher_id = ?
        GROUP BY a.id
    ");
    $stmt->execute([$assignmentId, $teacherId]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit();
    }

    // Get submission details
    $stmt = $pdo->prepare("
        SELECT sub.*, s.full_name as student_name, s.email as student_email
        FROM assignment_submissions sub
        JOIN students s ON sub.student_id = s.id
        WHERE sub.assignment_id = ?
        ORDER BY sub.submitted_at DESC
    ");
    $stmt->execute([$assignmentId]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get students who haven't submitted
    $stmt = $pdo->prepare("
        SELECT s.id, s.full_name, s.email
        FROM students s
        WHERE s.class_id = ?
        AND s.id NOT IN (
            SELECT student_id FROM assignment_submissions WHERE assignment_id = ?
        )
        ORDER BY s.full_name
    ");
    $stmt->execute([$assignment['class_id'], $assignmentId]);
    $pending_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'assignment' => $assignment,
        'submissions' => $submissions,
        'pending_students' => $pending_students
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

