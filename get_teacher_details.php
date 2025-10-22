<?php
require_once 'db_config.php';

if (isset($_GET['teacher_id'])) {
    $teacher_id = $_GET['teacher_id'];
    
    // Get teacher details
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
    
    // Get classes assigned to this teacher
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $classes = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'teacher' => $teacher,
        'classes' => $classes
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Teacher ID required']);
}
?>