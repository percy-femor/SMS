<?php
session_start();
require_once 'db_connect.php';

// Test script to insert sample grades data
echo "<h2>Inserting Sample Grades Data</h2>";

try {
    // First, let's check what students exist
    $stmt = $pdo->query("SELECT id, full_name FROM students LIMIT 5");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($students)) {
        echo "<p style='color: red;'>No students found in database. Please add students first.</p>";
        exit;
    }

    echo "<p>Found students:</p>";
    echo "<ul>";
    foreach ($students as $student) {
        echo "<li>ID: {$student['id']} - {$student['full_name']}</li>";
    }
    echo "</ul>";

    // Check what classes exist
    $stmt = $pdo->query("SELECT class_id, class_name, teacher_id FROM classes LIMIT 5");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($classes)) {
        echo "<p style='color: red;'>No classes found in database. Please add classes first.</p>";
        exit;
    }

    echo "<p>Found classes:</p>";
    echo "<ul>";
    foreach ($classes as $class) {
        echo "<li>ID: {$class['class_id']} - {$class['class_name']} (Teacher ID: {$class['teacher_id']})</li>";
    }
    echo "</ul>";

    // Insert sample grades for the first student in the first class
    $sampleGrades = [
        [
            'student_id' => $students[0]['id'],
            'student_name' => $students[0]['full_name'],
            'class_id' => $classes[0]['class_id'],
            'class_name' => $classes[0]['class_name'],
            'assignment_type' => 'Mathematics Quiz',
            'grade' => 'A',
            'score' => 95.00,
            'academic_year' => '2023-2024',
            'term' => 'First Term',
            'recorded_by' => $classes[0]['teacher_id'],
            'recorded_by_name' => 'Teacher Name'
        ],
        [
            'student_id' => $students[0]['id'],
            'student_name' => $students[0]['full_name'],
            'class_id' => $classes[0]['class_id'],
            'class_name' => $classes[0]['class_name'],
            'assignment_type' => 'English Essay',
            'grade' => 'B+',
            'score' => 87.50,
            'academic_year' => '2023-2024',
            'term' => 'First Term',
            'recorded_by' => $classes[0]['teacher_id'],
            'recorded_by_name' => 'Teacher Name'
        ],
        [
            'student_id' => $students[0]['id'],
            'student_name' => $students[0]['full_name'],
            'class_id' => $classes[0]['class_id'],
            'class_name' => $classes[0]['class_name'],
            'assignment_type' => 'Science Lab Report',
            'grade' => 'A-',
            'score' => 92.00,
            'academic_year' => '2023-2024',
            'term' => 'First Term',
            'recorded_by' => $classes[0]['teacher_id'],
            'recorded_by_name' => 'Teacher Name'
        ]
    ];

    $insertedCount = 0;
    foreach ($sampleGrades as $gradeData) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO grades (student_id, student_name, class_id, class_name, assignment_type, grade, score, academic_year, term, recorded_by, recorded_by_name)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $gradeData['student_id'],
                $gradeData['student_name'],
                $gradeData['class_id'],
                $gradeData['class_name'],
                $gradeData['assignment_type'],
                $gradeData['grade'],
                $gradeData['score'],
                $gradeData['academic_year'],
                $gradeData['term'],
                $gradeData['recorded_by'],
                $gradeData['recorded_by_name']
            ]);
            $insertedCount++;
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>Warning: Could not insert grade for {$gradeData['assignment_type']}: " . $e->getMessage() . "</p>";
        }
    }

    echo "<p style='color: green;'>✓ Successfully inserted $insertedCount sample grades!</p>";

    // Now test the query
    echo "<h3>Testing the Fixed Query:</h3>";
    $stmt = $pdo->prepare("
        SELECT g.*, s.full_name as student_name 
        FROM grades g 
        JOIN students s ON g.student_id = s.id 
        WHERE g.student_id = ? 
        ORDER BY g.recorded_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$students[0]['id']]);
    $grades = $stmt->fetchAll();

    echo "<p>Found " . count($grades) . " grades for student {$students[0]['full_name']}:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Assignment Type</th><th>Grade</th><th>Score</th><th>Academic Year</th><th>Term</th><th>Date Recorded</th></tr>";
    foreach ($grades as $grade) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($grade['assignment_type']) . "</td>";
        echo "<td>" . htmlspecialchars($grade['grade']) . "</td>";
        echo "<td>" . htmlspecialchars($grade['score']) . "</td>";
        echo "<td>" . htmlspecialchars($grade['academic_year']) . "</td>";
        echo "<td>" . htmlspecialchars($grade['term']) . "</td>";
        echo "<td>" . date('M j, Y', strtotime($grade['recorded_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>1. Check the student dashboard to see if grades now appear</li>";
echo "<li>2. If you want to test with a different student, modify the \$studentId variable</li>";
echo "<li>3. The grades section should now display correctly with the fixed query</li>";
echo "</ul>";
