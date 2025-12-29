<?php
require_once 'db_config.php';

echo "<h1>Database Status Check</h1>";

try {
    // Check if tables exist
    echo "<h2>Tables Check:</h2>";
    $tables = ['teachers', 'classes', 'students'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "Table '$table': $count records found<br>";
    }

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

    echo "<h2>Students:</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $student_count = $stmt->fetchColumn();
    echo "Total students: $student_count<br>";

    if ($student_count > 0) {
        $stmt = $pdo->query("SELECT id, full_name, email, class_id FROM students LIMIT 5");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($students as $student) {
            echo "Student ID: {$student['id']}, Name: {$student['full_name']}, Class ID: {$student['class_id']}<br>";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
