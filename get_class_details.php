<?php
require_once 'db_config.php';

if (isset($_GET['class_id'])) {
    $class_id = $_GET['class_id'];
    
    // Get class details
    $stmt = $pdo->prepare("SELECT c.*, t.full_name as teacher_name FROM classes c LEFT JOIN teachers t ON c.teacher_id = t.id WHERE c.class_id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();
    
    // Get students in this class
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll();
    
    // Get all teachers for assignment
    $teachers = $pdo->query("SELECT * FROM teachers ORDER BY full_name")->fetchAll();
    
    echo json_encode([
        'success' => true,
        'class' => $class,
        'students' => $students,
        'teachers' => $teachers
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Class ID required']);
}
?>