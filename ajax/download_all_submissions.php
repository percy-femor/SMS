<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    exit();
}

if (!isset($_GET['assignment_id'])) {
    http_response_code(400);
    exit();
}

$assignmentId = $_GET['assignment_id'];
$teacherId = $_SESSION['teacher_id'];

try {
    // Verify assignment belongs to teacher
    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$assignmentId, $teacherId]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        http_response_code(404);
        exit();
    }
    
    // Get submissions
    $stmt = $pdo->prepare("
        SELECT s.*, st.full_name as student_name
        FROM assignment_submissions s
        JOIN students st ON s.student_id = st.id
        WHERE s.assignment_id = ?
    ");
    $stmt->execute([$assignmentId]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create zip file
    $zip = new ZipArchive();
    $zipFileName = tempnam(sys_get_temp_dir(), 'submissions_') . '.zip';
    
    if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
        foreach ($submissions as $submission) {
            $filePath = __DIR__ . '/../' . $submission['file_path'];
            if (file_exists($filePath)) {
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $submission['student_name']);
                $extension = pathinfo($submission['file_name'], PATHINFO_EXTENSION);
                $zip->addFile($filePath, $safeName . '_' . $submission['id'] . '.' . $extension);
            }
        }
        $zip->close();
        
        // Send zip file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="submissions_assignment_' . $assignmentId . '.zip"');
        header('Content-Length: ' . filesize($zipFileName));
        readfile($zipFileName);
        
        // Clean up
        unlink($zipFileName);
    } else {
        http_response_code(500);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
}
?>