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

if (!$input || !isset($input['classId']) || !isset($input['attendanceData']) || !isset($input['date'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$class_id = (int)$input['classId'];
$attendance_data = $input['attendanceData'];
$date = $input['date'];
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

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
    
    foreach ($attendance_data as $student_id => $status) {
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
        
        // Check if attendance already exists for this date
        $stmt = $pdo->prepare("SELECT attendance_id FROM attendance WHERE student_id = ? AND class_id = ? AND date = ?");
        $stmt->execute([$student_id, $class_id, $date]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing attendance
            $stmt = $pdo->prepare("UPDATE attendance SET status = ?, student_name = ?, recorded_by = ?, recorded_by_name = ?, recorded_at = NOW() WHERE attendance_id = ?");
            $stmt->execute([$status, $student_name, $teacher_id, $teacher_name, $existing['attendance_id']]);
        } else {
            // Insert new attendance
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, student_name, class_id, class_name, date, status, recorded_by, recorded_by_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $student_name, $class_id, $class_name, $date, $status, $teacher_id, $teacher_name]);
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
        'message' => "Attendance saved successfully! ($success_count records updated)",
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