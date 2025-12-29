-- Complete School Management System Database Setup
-- This script sets up all tables and data needed for the enhanced admin dashboard
-- Safe to run on new installations - uses CREATE TABLE IF NOT EXISTS and INSERT IGNORE

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS school_management;
USE school_management;

-- Admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin if none exists (password is hashed)
INSERT IGNORE INTO admin (name, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Teachers table with all enhanced fields
CREATE TABLE IF NOT EXISTS teachers (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    subject VARCHAR(100) NULL,
    sex VARCHAR(10) NULL,
    passport_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table with all enhanced fields
CREATE TABLE IF NOT EXISTS students (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    class VARCHAR(50) NULL,
    class_id INT(11) NULL,
    sex VARCHAR(10) NULL,
    passport_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Classes table
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

-- Fee types table
CREATE TABLE IF NOT EXISTS fee_types (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default fee types
INSERT IGNORE INTO fee_types (name, amount, description) VALUES 
('School Fees', 5000.00, 'Annual school fees'),
('Feeding', 2000.00, 'School feeding program'),
('Transport', 1500.00, 'Transportation fees');

-- Fee payments table
CREATE TABLE IF NOT EXISTS fee_payments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    student_id INT(11) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    term VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    fee_type_id INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_type_id) REFERENCES fee_types(id) ON DELETE SET NULL
);

-- Add foreign key constraint for students.class_id if it doesn't exist
-- This is done separately to avoid errors if the constraint already exists
SET @constraint_exists = (
    SELECT COUNT(*) 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'school_management' 
    AND TABLE_NAME = 'students' 
    AND COLUMN_NAME = 'class_id' 
    AND CONSTRAINT_NAME != 'PRIMARY'
);

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE students ADD FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE SET NULL',
    'SELECT "Foreign key constraint already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create uploads directory structure (this will be handled by PHP, but noted here)
-- The uploads/passports/ directory should be created with proper permissions (755)

-- Sample data for testing (optional - uncomment if needed)
/*
-- Sample teachers
INSERT IGNORE INTO teachers (email, password, full_name, phone, subject, sex) VALUES 
('teacher1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', '08012345678', 'Mathematics', 'Male'),
('teacher2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Doe', '08087654321', 'English', 'Female');

-- Sample classes
INSERT IGNORE INTO classes (class_name, class_code, teacher_id, max_capacity) VALUES 
('Grade 10A', 'G10A', 1, 30),
('Grade 10B', 'G10B', 2, 30),
('Grade 11A', 'G11A', 1, 30);

-- Sample students
INSERT IGNORE INTO students (email, password, full_name, class_id, sex) VALUES 
('student1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Johnson', 1, 'Female'),
('student2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Wilson', 1, 'Male'),
('student3@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carol Brown', 2, 'Female');
*/

-- Display setup completion message
SELECT 'Database setup completed successfully!' as status,
       'Admin login: admin / admin123' as admin_credentials,
       'Fee types: School Fees (₦5,000), Feeding (₦2,000), Transport (₦1,500)' as fee_info,
       'Remember to create uploads/passports/ directory with 755 permissions' as upload_note;
