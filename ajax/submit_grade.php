<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['submission_id']) || !isset($input['grade_value'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$teacherId = $_SESSION['teacher_id'];
$submissionId = $input['submission_id'];
$gradeValue = $input['grade_value'];
$feedback = $input['feedback'] ?? '';

// Validate grade value
if (!is_numeric($gradeValue) || $gradeValue < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid grade value']);
    exit();
}

try {
    // Verify the submission belongs to an assignment by this teacher
    $stmt = $pdo->prepare("
        SELECT sub.*, a.total_points, a.teacher_id
        FROM assignment_submissions sub
        JOIN assignments a ON sub.assignment_id = a.id
        WHERE sub.id = ? AND a.teacher_id = ?
    ");
    $stmt->execute([$submissionId, $teacherId]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        echo json_encode(['success' => false, 'message' => 'Submission not found or unauthorized']);
        exit();
    }

    // Validate grade doesn't exceed max points
    if ($gradeValue > $submission['total_points']) {
        echo json_encode(['success' => false, 'message' => 'Grade cannot exceed maximum points (' . $submission['total_points'] . ')']);
        exit();
    }

    // Update the submission
    $stmt = $pdo->prepare("
        UPDATE assignment_submissions 
        SET grade_value = ?, feedback = ?, status = 'graded', graded_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$gradeValue, $feedback, $submissionId]);

    // Get updated submission data
    $stmt = $pdo->prepare("
        SELECT sub.*, s.full_name as student_name
        FROM assignment_submissions sub
        JOIN students s ON sub.student_id = s.id
        WHERE sub.id = ?
    ");
    $stmt->execute([$submissionId]);
    $updatedSubmission = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Grade submitted successfully!',
        'submission' => $updatedSubmission
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

