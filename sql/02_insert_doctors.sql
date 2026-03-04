-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: DOCTOR
-- ============================================================================

USE medicore_db;

-- Insert doctors
INSERT INTO doctor (dept_id, name, specialization, qualification, phone, email, room_no, status) VALUES
-- Cardiology
(1, 'Dr. Ahmed Rahim', 'Interventional Cardiology', 'MBBS, MD (Cardiology)', '+880-1811-111111', 'ahmed.rahim@hospital.com', 'A301', 'ACTIVE'),
(1, 'Dr. Fatima Khan', 'Cardiac Electrophysiology', 'MBBS, MD, FACC', '+880-1811-111112', 'fatima.khan@hospital.com', 'A302', 'ACTIVE'),

-- Neurology
(2, 'Dr. Habib Mahmud', 'Stroke Specialist', 'MBBS, MD (Neurology)', '+880-1811-222221', 'habib.mahmud@hospital.com', 'A401', 'ACTIVE'),
(2, 'Dr. Nusrat Jahan', 'Epilepsy Specialist', 'MBBS, MD, PhD', '+880-1811-222222', 'nusrat.jahan@hospital.com', 'A402', 'ACTIVE'),

-- Orthopedics
(3, 'Dr. Karim Hossain', 'Joint Replacement', 'MBBS, MS (Ortho)', '+880-1811-333331', 'karim.hossain@hospital.com', 'B201', 'ACTIVE'),
(3, 'Dr. Shamima Begum', 'Spine Surgery', 'MBBS, MS, FRCS', '+880-1811-333332', 'shamima.begum@hospital.com', 'B202', 'ACTIVE'),

-- Pediatrics
(4, 'Dr. Iqbal Ahmed', 'Neonatology', 'MBBS, DCH, MD', '+880-1811-444441', 'iqbal.ahmed@hospital.com', 'C101', 'ACTIVE'),
(4, 'Dr. Taslima Nasrin', 'Pediatric Cardiology', 'MBBS, MD (Peds), FAAP', '+880-1811-444442', 'taslima.nasrin@hospital.com', 'C102', 'ACTIVE'),

-- General Surgery
(5, 'Dr. Rafiq Uddin', 'Laparoscopic Surgery', 'MBBS, MS (Surgery)', '+880-1811-555551', 'rafiq.uddin@hospital.com', 'B301', 'ACTIVE'),
(5, 'Dr. Monira Khatun', 'Trauma Surgery', 'MBBS, MS, FACS', '+880-1811-555552', 'monira.khatun@hospital.com', 'B302', 'ON_LEAVE'),

-- Emergency Medicine
(6, 'Dr. Salman Khan', 'Emergency Physician', 'MBBS, FCPS (EM)', '+880-1811-666661', 'salman.khan@hospital.com', 'A001', 'ACTIVE'),
(6, 'Dr. Rehana Parvin', 'Acute Care', 'MBBS, MD (EM)', '+880-1811-666662', 'rehana.parvin@hospital.com', 'A002', 'ACTIVE'),

-- Radiology
(7, 'Dr. Tanvir Hasan', 'Interventional Radiology', 'MBBS, MD (Radiology)', '+880-1811-777771', 'tanvir.hasan@hospital.com', 'C201', 'ACTIVE'),
(7, 'Dr. Nazma Akhter', 'Diagnostic Imaging', 'MBBS, DMRD, FRCR', '+880-1811-777772', 'nazma.akhter@hospital.com', 'C202', 'ACTIVE'),

-- Pathology
(8, 'Dr. Khalid Rahman', 'Clinical Pathology', 'MBBS, MD (Pathology)', '+880-1811-888881', 'khalid.rahman@hospital.com', 'CB01', 'ACTIVE'),
(8, 'Dr. Sultana Kamal', 'Hematopathology', 'MBBS, MD, FRCPath', '+880-1811-888882', 'sultana.kamal@hospital.com', 'CB02', 'ACTIVE');

-- Verify insertion
SELECT d.doctor_id, d.name, d.specialization, dept.dept_name, d.status
FROM doctor d
JOIN department dept ON d.dept_id = dept.dept_id
ORDER BY dept.dept_name, d.name;

SELECT CONCAT('Inserted ', COUNT(*), ' doctors') AS status FROM doctor;