USE school_management;

-- Only create the NEW tables that don't exist
CREATE TABLE IF NOT EXISTS classes (
    class_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    class_code VARCHAR(20) UNIQUE NOT NULL,
    teacher_id INT(11) NULL,
    max_capacity INT DEFAULT 30,
    current_enrollment INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

-- Add class_id column to students if it doesn't exist
ALTER TABLE students ADD COLUMN IF NOT EXISTS class_id INT(11) NULL;

-- Add foreign key constraint
ALTER TABLE students ADD FOREIGN KEY IF NOT EXISTS (class_id) REFERENCES classes(class_id);