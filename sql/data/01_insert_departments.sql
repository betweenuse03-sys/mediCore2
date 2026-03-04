-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: DEPARTMENT
-- ============================================================================

USE medicore_db;

-- Insert departments
INSERT INTO department (dept_name, dept_head, phone, location) VALUES
('Cardiology', 'Dr. Sarah Ahmed', '+880-1711-111111', 'Building A, Floor 3'),
('Neurology', 'Dr. Rahman Khan', '+880-1711-222222', 'Building A, Floor 4'),
('Orthopedics', 'Dr. Farhana Begum', '+880-1711-333333', 'Building B, Floor 2'),
('Pediatrics', 'Dr. Mahmud Hasan', '+880-1711-444444', 'Building C, Floor 1'),
('General Surgery', 'Dr. Kamal Uddin', '+880-1711-555555', 'Building B, Floor 3'),
('Emergency Medicine', 'Dr. Nasrin Akter', '+880-1711-666666', 'Building A, Ground Floor'),
('Radiology', 'Dr. Imran Sheikh', '+880-1711-777777', 'Building C, Floor 2'),
('Pathology', 'Dr. Sabina Rahman', '+880-1711-888888', 'Building C, Basement');

-- Verify insertion
SELECT * FROM department;

SELECT CONCAT('Inserted ', COUNT(*), ' departments') AS status FROM department;