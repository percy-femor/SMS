<?php
session_start();
require_once '../db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['classId']) || !isset($input['assignment']) || !isset($input['gradesData'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$class_id = (int)$input['classId'];
$assignment = $input['assignment'];
$grades_data = $input['gradesData'];
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];
$academic_year = date('Y') . '/' . (date('Y') + 1);
$term = 'First Term'; // You can make this dynamic

try {
    // Verify teacher owns this class and get class name
    $stmt = $pdo->prepare("SELECT class_id, class_name FROM classes WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $teacher_id]);
    $class = $stmt->fetch();
    
    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found or access denied']);
        exit();
    }
    
    $class_name = $class['class_name'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($grades_data as $student_id => $grade) {
        if (empty($grade)) continue; // Skip empty grades
        
        $student_id = (int)$student_id;
        
        // Verify student is in this class and get student name
        $stmt = $pdo->prepare("SELECT id, full_name FROM students WHERE id = ? AND class_id = ?");
        $stmt->execute([$student_id, $class_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            $error_count++;
            continue;
        }
        
        $student_name = $student['full_name'];
        
        // Calculate score based on grade
        $score = null;
        switch ($grade) {
            case 'A': $score = 95; break;
            case 'B': $score = 85; break;
            case 'C': $score = 75; break;
            case 'D': $score = 65; break;
            case 'F': $score = 55; break;
            default: $score = null;
        }
        
        // Check if grade already exists
        $stmt = $pdo->prepare("SELECT grade_id FROM grades WHERE student_id = ? AND class_id = ? AND assignment_type = ? AND academic_year = ? AND term = ?");
        $stmt->execute([$student_id, $class_id, $assignment, $academic_year, $term]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing grade
            $stmt = $pdo->prepare("UPDATE grades SET grade = ?, score = ?, student_name = ?, recorded_by = ?, recorded_by_name = ?, recorded_at = NOW() WHERE grade_id = ?");
            $stmt->execute([$grade, $score, $student_name, $teacher_id, $teacher_name, $existing['grade_id']]);
        } else {
            // Insert new grade
            $stmt = $pdo->prepare("INSERT INTO grades (student_id, student_name, class_id, class_name, assignment_type, grade, score, academic_year, term, recorded_by, recorded_by_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $student_name, $class_id, $class_name, $assignment, $grade, $score, $academic_year, $term, $teacher_id, $teacher_name]);
        }
        
        if ($stmt->rowCount() > 0) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Grades saved successfully! ($success_count records updated)",
        'stats' => [
            'successful' => $success_count,
            'failed' => $error_count
        ]
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
?>