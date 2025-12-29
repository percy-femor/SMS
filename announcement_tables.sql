-- Announcement tables for School Management System
-- Run this SQL code in your XAMPP phpMyAdmin to create the announcement tables

-- Table for teacher announcements
CREATE TABLE IF NOT EXISTS teacher_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE
);

-- Table for student announcements
CREATE TABLE IF NOT EXISTS student_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE CASCADE
);

-- Table to track which teachers have read announcements
CREATE TABLE IF NOT EXISTS teacher_announcement_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    teacher_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES teacher_announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_read (announcement_id, teacher_id)
);

-- Table to track which students have read announcements
CREATE TABLE IF NOT EXISTS student_announcement_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    student_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES student_announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_read (announcement_id, student_id)
);

-- Add indexes for better performance (with error handling)
CREATE INDEX IF NOT EXISTS idx_teacher_announcements_created_at ON teacher_announcements(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_teacher_announcements_priority ON teacher_announcements(priority);
CREATE INDEX IF NOT EXISTS idx_teacher_announcements_is_active ON teacher_announcements(is_active);

CREATE INDEX IF NOT EXISTS idx_student_announcements_created_at ON student_announcements(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_student_announcements_priority ON student_announcements(priority);
CREATE INDEX IF NOT EXISTS idx_student_announcements_is_active ON student_announcements(is_active);

-- Insert sample data (optional)
-- Make sure you have an admin user with id = 1 before running this.
INSERT IGNORE INTO teacher_announcements (title, message, priority, created_by) VALUES
('Staff Meeting', 'Monthly staff meeting scheduled for next Monday at 2 PM in the conference room.', 'high', 1),
('New Curriculum Update', 'Please review the updated curriculum guidelines for the upcoming semester.', 'medium', 1);

-- Insert sample data for student announcements
INSERT IGNORE INTO student_announcements (title, message, priority, created_by) VALUES
('Exam Schedule', 'Final exams will begin next week. Please check your individual schedules.', 'high', 1),
('Library Hours', 'Library will be open extended hours during exam period.', 'medium', 1);