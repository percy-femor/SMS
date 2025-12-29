<?php
// Simple database test
try {
    $pdo = new PDO('mysql:host=localhost;dbname=school_management', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Database connection successful!<br><br>";

    // Check teachers
    $result = $pdo->query("SELECT COUNT(*) FROM teachers");
    $teacher_count = $result->fetchColumn();
    echo "Teachers table: $teacher_count records<br>";

    if ($teacher_count > 0) {
        $result = $pdo->query("SELECT teacher_id, full_name FROM teachers");
        $teachers = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "Available teachers:<br>";
        foreach ($teachers as $teacher) {
            echo "- ID: {$teacher['teacher_id']}, Name: {$teacher['full_name']}<br>";
        }
    }

    echo "<br>";

    // Check classes
    $result = $pdo->query("SELECT COUNT(*) FROM classes");
    $class_count = $result->fetchColumn();
    echo "Classes table: $class_count records<br>";

    if ($class_count > 0) {
        $result = $pdo->query("SELECT class_id, class_name, teacher_id FROM classes");
        $classes = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "Available classes:<br>";
        foreach ($classes as $class) {
            echo "- ID: {$class['class_id']}, Name: {$class['class_name']}, Teacher ID: {$class['teacher_id']}<br>";
        }
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
}
