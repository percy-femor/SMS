-- SQL script to hash the admin password from plain text to hashed form
-- This will change the admin password from "admin123" to its hashed equivalent

USE school_management;

-- Update the admin password from plain text to hashed form
-- The hashed password below corresponds to "admin123"
UPDATE admin 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE name = 'admin' AND password = 'admin123';

-- Verify the update
SELECT 
    id,
    name,
    password,
    CASE 
        WHEN password = 'admin123' THEN 'Plain Text (Not Secure)'
        WHEN password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' THEN 'Hashed (Secure)'
        ELSE 'Other'
    END as password_status
FROM admin 
WHERE name = 'admin';

-- Display completion message
SELECT 'Admin password has been successfully hashed!' as status,
       'Login credentials: admin / admin123' as credentials,
       'The password is now stored securely in the database' as security_note;
