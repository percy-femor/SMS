<?php
session_start();
require_once '../db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Class ID required']);
    exit();
}

$class_id = (int)$_GET['class_id'];
$teacher_id = $_SESSION['teacher_id'];

try {
    // Verify teacher owns this class
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $teacher_id]);
    $class = $stmt->fetch();
    
    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found or access denied']);
        exit();
    }
    
    // Get students in this class
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM students s 
        WHERE s.class_id = ? 
        ORDER BY s.full_name
    ");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'class' => $class,
        'students' => $students
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>