<?php
require_once 'db_config.php';

echo "<h1>Database Check</h1>";

try {
    // Check teachers
    echo "<h2>Teachers:</h2>";
    $stmt = $pdo->query("SELECT teacher_id, full_name, email FROM teachers");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($teachers)) {
        echo "No teachers found!<br>";
    } else {
        foreach ($teachers as $teacher) {
            echo "Teacher ID: {$teacher['teacher_id']}, Name: {$teacher['full_name']}, Email: {$teacher['email']}<br>";
        }
    }

    // Check classes
    echo "<h2>Classes:</h2>";
    $stmt = $pdo->query("SELECT class_id, class_name, class_code, teacher_id FROM classes");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($classes)) {
        echo "No classes found!<br>";
    } else {
        foreach ($classes as $class) {
            echo "Class ID: {$class['class_id']}, Name: {$class['class_name']}, Code: {$class['class_code']}, Teacher ID: {$class['teacher_id']}<br>";
        }
    }

    // Check students
    echo "<h2>Students:</h2>";
    $stmt = $pdo->query("SELECT id, full_name, email, class_id FROM students LIMIT 10");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($students)) {
        echo "No students found!<br>";
    } else {
        foreach ($students as $student) {
            echo "Student ID: {$student['id']}, Name: {$student['full_name']}, Class ID: {$student['class_id']}<br>";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
