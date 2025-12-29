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

$assignmentId = $_GET['assignment_id'];
$teacherId = $_SESSION['teacher_id'];

try {
    // Verify assignment belongs to teacher
    $stmt = $pdo->prepare("
        SELECT a.*, c.class_name 
        FROM assignments a 
        JOIN classes c ON a.class_id = c.class_id 
        WHERE a.id = ? AND a.teacher_id = ?
    ");
    $stmt->execute([$assignmentId, $teacherId]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit();
    }
    
    // Get submissions
    $stmt = $pdo->prepare("
        SELECT s.*, st.full_name as student_name, st.email as student_email
        FROM assignment_submissions s
        JOIN students st ON s.student_id = st.id
        WHERE s.assignment_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$assignmentId]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'assignment' => $assignment,
        'submissions' => $submissions
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>