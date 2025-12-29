<?php
session_start();
require_once '../db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$class_id = $_POST['class_id'] ?? '';
$assignment_type = $_POST['assignment_type'] ?? '';
$deadline = $_POST['deadline'] ?? '';
$total_points = $_POST['total_points'] ?? 100;
$instructions = $_POST['instructions'] ?? '';

// Validation
if (empty($title) || empty($class_id) || empty($assignment_type) || empty($deadline)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit();
}

try {
    // Verify teacher owns this class
    $stmt = $pdo->prepare("SELECT class_id FROM classes WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $teacher_id]);
    $class = $stmt->fetch();
    
    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found or access denied']);
        exit();
    }
    
    // Create assignment
    $stmt = $pdo->prepare("
        INSERT INTO assignments (teacher_id, class_id, title, description, assignment_type, deadline, total_points, instructions, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    
    $stmt->execute([
        $teacher_id,
        $class_id,
        $title,
        $description,
        $assignment_type,
        $deadline,
        $total_points,
        $instructions
    ]);
    
    $assignment_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Assignment created successfully!',
        'assignment_id' => $assignment_id
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>