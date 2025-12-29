-- Migration script for existing School Management System databases
-- This script adds new features without affecting existing data
-- Safe to run on existing installations

USE school_management;

-- Add new columns to teachers table if they don't exist
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL;
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS subject VARCHAR(100) NULL;
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS sex VARCHAR(10) NULL;
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS passport_path VARCHAR(255) NULL;

-- Add new columns to students table if they don't exist
ALTER TABLE students ADD COLUMN IF NOT EXISTS sex VARCHAR(10) NULL;
ALTER TABLE students ADD COLUMN IF NOT EXISTS passport_path VARCHAR(255) NULL;

-- Create classes table if it doesn't exist
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

-- Create fee types table if it doesn't exist
CREATE TABLE IF NOT EXISTS fee_types (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default fee types if they don't exist
INSERT IGNORE INTO fee_types (name, amount, description) VALUES 
('School Fees', 5000.00, 'Annual school fees'),
('Feeding', 2000.00, 'School feeding program'),
('Transport', 1500.00, 'Transportation fees');

-- Create fee_payments table if it doesn't exist
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

-- Add fee_type_id column to existing fee_payments table if it doesn't exist
ALTER TABLE fee_payments ADD COLUMN IF NOT EXISTS fee_type_id INT(11) NULL;

-- Add foreign key constraints if they don't exist
-- Check and add students.class_id foreign key
SET @constraint_exists = (
    SELECT COUNT(*) 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'school_management' 
    AND TABLE_NAME = 'students' 
    AND COLUMN_NAME = 'class_id' 
    AND CONSTRAINT_NAME != 'PRIMARY'
    AND REFERENCED_TABLE_NAME = 'classes'
);

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE students ADD FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE SET NULL',
    'SELECT "Foreign key constraint already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add fee_payments.fee_type_id foreign key
SET @constraint_exists = (
    SELECT COUNT(*) 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'school_management' 
    AND TABLE_NAME = 'fee_payments' 
    AND COLUMN_NAME = 'fee_type_id' 
    AND CONSTRAINT_NAME != 'PRIMARY'
    AND REFERENCED_TABLE_NAME = 'fee_types'
);

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE fee_payments ADD FOREIGN KEY (fee_type_id) REFERENCES fee_types(id) ON DELETE SET NULL',
    'SELECT "Foreign key constraint already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Display migration completion message
SELECT 'Migration completed successfully!' as status,
       'New features added: Sex fields, Passport uploads, Fee types, Enhanced classes' as features,
       'Fee types: School Fees (₦5,000), Feeding (₦2,000), Transport (₦1,500)' as fee_info,
       'Remember to create uploads/passports/ directory with 755 permissions' as upload_note;
