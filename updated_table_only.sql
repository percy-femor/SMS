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

-- Ensure teachers table has additional fields used by the app
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL;
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS subject VARCHAR(100) NULL;
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS sex VARCHAR(10) NULL;
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS passport_path VARCHAR(255) NULL;

-- Ensure students table has additional fields used by the app
ALTER TABLE students ADD COLUMN IF NOT EXISTS sex VARCHAR(10) NULL;
ALTER TABLE students ADD COLUMN IF NOT EXISTS passport_path VARCHAR(255) NULL;

-- Create fee types table
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

-- Update fee_payments table to include fee_type
ALTER TABLE fee_payments ADD COLUMN IF NOT EXISTS fee_type_id INT(11) NULL;
ALTER TABLE fee_payments ADD FOREIGN KEY IF NOT EXISTS (fee_type_id) REFERENCES fee_types(id);





-- for the store functionality
-- Store items table
CREATE TABLE IF NOT EXISTS store_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('book', 'stationery', 'uniform', 'other') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity_available INT DEFAULT 0,
    image_url VARCHAR(500),
    is_available BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE SET NULL
);

-- Store orders table
CREATE TABLE IF NOT EXISTS store_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled', 'completed') DEFAULT 'pending',
    payment_reference VARCHAR(255),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_date TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES store_items(id) ON DELETE CASCADE
);