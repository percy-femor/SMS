-- create grades table
CREATE TABLE IF NOT EXISTS grades (
    grade_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    class_id INT NOT NULL,
    class_name VARCHAR(255) NOT NULL,
    assignment_type VARCHAR(100) NOT NULL,
    grade VARCHAR(5) NOT NULL,
    score DECIMAL(5,2),
    academic_year VARCHAR(20),
    term VARCHAR(50),
    recorded_by INT NOT NULL,
    recorded_by_name VARCHAR(255) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES teachers(id),
    UNIQUE KEY unique_grade (student_id, class_id, assignment_type, academic_year, term)
);

-- creating the attendance table
CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    class_id INT NOT NULL,
    class_name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,
    recorded_by INT NOT NULL,
    recorded_by_name VARCHAR(255) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES teachers(id),
    UNIQUE KEY unique_attendance (student_id, class_id, date)
);

--creating the attendance table
CREATE TABLE IF NOT EXISTS assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assignment_type VARCHAR(100) NOT NULL,
    deadline DATE NOT NULL,
    total_points INT DEFAULT 100,
    instructions TEXT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (class_id) REFERENCES classes(class_id)
);