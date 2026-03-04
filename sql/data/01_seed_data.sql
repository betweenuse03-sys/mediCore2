-- MediCore HMS - Complete Seed Data
-- Week 8 + Week 13 Combined
USE medicore_db;
SET FOREIGN_KEY_CHECKS = 0;

-- Temporarily disable the appointment overlap trigger to allow historical data
DROP TRIGGER IF EXISTS trg_before_appointment_overlap;

-- == sql_week8/data/01_insert_departments.sql ==

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

-- == sql_week8/data/02_insert_doctors.sql ==
-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: DOCTOR
-- ============================================================================

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

-- == sql_week8/data/03_insert_patients.sql ==

-- Insert patients with diverse demographics
INSERT INTO patient (name, dob, gender, blood_group, address, phone, email, emergency_contact, emergency_name) VALUES
('Mohammad Ali', '1985-03-15', 'M', 'A+', 'House 12, Road 5, Dhanmondi, Dhaka', '+880-1911-111111', 'mohammad.ali@email.com', '+880-1711-111111', 'Fatima Ali'),
('Ayesha Rahman', '1990-07-22', 'F', 'B+', 'Flat 3B, Gulshan-2, Dhaka', '+880-1922-222222', 'ayesha.rahman@email.com', '+880-1722-222222', 'Karim Rahman'),
('Jahangir Hossain', '1978-11-30', 'M', 'O+', 'Village: Sreepur, Gazipur', '+880-1933-333333', 'jahangir.h@email.com', '+880-1733-333333', 'Nasima Hossain'),
('Sumaiya Begum', '2015-01-10', 'F', 'AB+', 'House 45, Mirpur-10, Dhaka', '+880-1944-444444', 'sumaiya.parent@email.com', '+880-1744-444444', 'Rafiq Begum'),
('Abdul Karim', '1965-05-18', 'M', 'A-', 'Holding 234, Uttara Sector-7, Dhaka', '+880-1955-555555', 'abdul.karim@email.com', '+880-1755-555555', 'Halima Karim'),
('Tahmina Akter', '1995-09-25', 'F', 'B-', 'Flat 5C, Banani, Dhaka', '+880-1966-666666', 'tahmina.akter@email.com', '+880-1766-666666', 'Shahin Akter'),
('Rahim Uddin', '1982-12-08', 'M', 'O-', 'House 78, Mohammadpur, Dhaka', '+880-1977-777777', 'rahim.uddin@email.com', '+880-1777-777777', 'Salma Uddin'),
('Nadia Islam', '2010-04-14', 'F', 'A+', 'Apartment 2A, Bashundhara, Dhaka', '+880-1988-888888', 'nadia.parent@email.com', '+880-1788-888888', 'Imran Islam'),
('Farhan Ahmed', '1988-08-20', 'M', 'AB-', 'House 56, Lalmatia, Dhaka', '+880-1999-999999', 'farhan.ahmed@email.com', '+880-1799-999999', 'Sadia Ahmed'),
('Roxana Khatun', '1992-02-28', 'F', 'B+', 'Village: Kapasia, Gazipur', '+880-1900-111111', 'roxana.khatun@email.com', '+880-1700-111111', 'Mamun Khatun'),
('Shakib Hassan', '2005-06-12', 'M', 'O+', 'Flat 7D, Uttara Sector-3, Dhaka', '+880-1900-222222', 'shakib.parent@email.com', '+880-1700-222222', 'Nasrin Hassan'),
('Farzana Yasmin', '1980-10-05', 'F', 'A-', 'House 23, Badda, Dhaka', '+880-1900-333333', 'farzana.yasmin@email.com', '+880-1700-333333', 'Harun Yasmin'),
('Tanvir Khan', '1975-03-17', 'M', 'AB+', 'Holding 145, Rampura, Dhaka', '+880-1900-444444', 'tanvir.khan@email.com', '+880-1700-444444', 'Kulsum Khan'),
('Sabina Sultana', '1998-11-22', 'F', 'B-', 'Apartment 4F, Motijheel, Dhaka', '+880-1900-555555', 'sabina.sultana@email.com', '+880-1700-555555', 'Jahir Sultana'),
('Mahbub Alam', '1987-07-30', 'M', 'O-', 'House 89, Shyamoli, Dhaka', '+880-1900-666666', 'mahbub.alam@email.com', '+880-1700-666666', 'Rubina Alam');

-- Verify insertion
SELECT patient_id, name, dob, gender, blood_group, phone FROM patient ORDER BY patient_id;
SELECT CONCAT('Inserted ', COUNT(*), ' patients') AS status FROM patient;

-- Show age distribution
SELECT CASE 
    WHEN YEAR(CURDATE()) - YEAR(dob) < 18 THEN 'Child (0-17)'
    WHEN YEAR(CURDATE()) - YEAR(dob) BETWEEN 18 AND 40 THEN 'Adult (18-40)'
    WHEN YEAR(CURDATE()) - YEAR(dob) BETWEEN 41 AND 60 THEN 'Middle Age (41-60)'
    ELSE 'Senior (60+)'
END AS age_group, COUNT(*) AS count
FROM patient
GROUP BY age_group
ORDER BY CASE 
    WHEN YEAR(CURDATE()) - YEAR(dob) < 18 THEN 1
    WHEN YEAR(CURDATE()) - YEAR(dob) BETWEEN 18 AND 40 THEN 2
    WHEN YEAR(CURDATE()) - YEAR(dob) BETWEEN 41 AND 60 THEN 3
    ELSE 4
END;

-- == sql_week8/data/04_insert_rooms_beds.sql ==
-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: ROOM and BED
-- ============================================================================

-- ============================================================================
-- Insert Rooms
-- ============================================================================

INSERT INTO room (room_number, ward, room_type, floor_no, daily_rate) VALUES
-- General Ward (Lower rates)
('G101', 'General Ward A', 'GENERAL', 1, 1500.00),
('G102', 'General Ward A', 'GENERAL', 1, 1500.00),
('G103', 'General Ward A', 'GENERAL', 1, 1500.00),
('G201', 'General Ward B', 'GENERAL', 2, 1500.00),
('G202', 'General Ward B', 'GENERAL', 2, 1500.00),

-- Semi-Private Rooms
('SP301', 'Semi-Private Wing', 'SEMI_PRIVATE', 3, 3000.00),
('SP302', 'Semi-Private Wing', 'SEMI_PRIVATE', 3, 3000.00),
('SP303', 'Semi-Private Wing', 'SEMI_PRIVATE', 3, 3000.00),

-- Private Rooms (Higher rates)
('P401', 'Private Wing', 'PRIVATE', 4, 5000.00),
('P402', 'Private Wing', 'PRIVATE', 4, 5000.00),
('P403', 'Private Wing', 'PRIVATE', 4, 5000.00),
('P404', 'Private Wing', 'PRIVATE', 4, 5000.00),

-- ICU (Highest rates)
('ICU501', 'Intensive Care Unit', 'ICU', 5, 10000.00),
('ICU502', 'Intensive Care Unit', 'ICU', 5, 10000.00),
('ICU503', 'Intensive Care Unit', 'ICU', 5, 10000.00),

-- Emergency
('ER001', 'Emergency Department', 'EMERGENCY', 0, 2000.00),
('ER002', 'Emergency Department', 'EMERGENCY', 0, 2000.00),
('ER003', 'Emergency Department', 'EMERGENCY', 0, 2000.00);

-- Verify room insertion
SELECT * FROM room ORDER BY room_type, room_number;

-- ============================================================================
-- Insert Beds
-- ============================================================================

-- General Ward rooms (4 beds each)
INSERT INTO bed (room_id, bed_number, status) VALUES
(1, 'A', 'AVAILABLE'), (1, 'B', 'OCCUPIED'), (1, 'C', 'AVAILABLE'), (1, 'D', 'AVAILABLE'),
(2, 'A', 'AVAILABLE'), (2, 'B', 'AVAILABLE'), (2, 'C', 'OCCUPIED'), (2, 'D', 'AVAILABLE'),
(3, 'A', 'OCCUPIED'), (3, 'B', 'AVAILABLE'), (3, 'C', 'AVAILABLE'), (3, 'D', 'MAINTENANCE'),
(4, 'A', 'AVAILABLE'), (4, 'B', 'AVAILABLE'), (4, 'C', 'AVAILABLE'), (4, 'D', 'AVAILABLE'),
(5, 'A', 'OCCUPIED'), (5, 'B', 'AVAILABLE'), (5, 'C', 'AVAILABLE'), (5, 'D', 'AVAILABLE');

-- Semi-private rooms (2 beds each)
INSERT INTO bed (room_id, bed_number, status) VALUES
(6, 'A', 'AVAILABLE'), (6, 'B', 'AVAILABLE'),
(7, 'A', 'OCCUPIED'), (7, 'B', 'AVAILABLE'),
(8, 'A', 'AVAILABLE'), (8, 'B', 'AVAILABLE');

-- Private rooms (1 bed each)
INSERT INTO bed (room_id, bed_number, status) VALUES
(9, 'A', 'AVAILABLE'), (10, 'A', 'OCCUPIED'), (11, 'A', 'AVAILABLE'), (12, 'A', 'RESERVED');

-- ICU beds (1 bed each, critical equipment)
INSERT INTO bed (room_id, bed_number, status) VALUES
(13, 'A', 'OCCUPIED'), (14, 'A', 'OCCUPIED'), (15, 'A', 'AVAILABLE');

-- Emergency beds
INSERT INTO bed (room_id, bed_number, status) VALUES
(16, 'A', 'AVAILABLE'), (17, 'A', 'AVAILABLE'), (18, 'A', 'OCCUPIED');

-- Verify bed insertion
SELECT r.room_number, r.room_type, r.ward, b.bed_number, b.status, r.daily_rate
FROM bed b JOIN room r ON b.room_id = r.room_id
ORDER BY r.room_type, r.room_number, b.bed_number;

-- Summary statistics
SELECT r.room_type,
    COUNT(DISTINCT r.room_id) AS total_rooms,
    COUNT(b.bed_id) AS total_beds,
    SUM(CASE WHEN b.status = 'AVAILABLE' THEN 1 ELSE 0 END) AS available_beds,
    SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) AS occupied_beds,
    ROUND(100.0 * SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) / COUNT(b.bed_id), 2) AS occupancy_rate
FROM room r LEFT JOIN bed b ON r.room_id = b.room_id
GROUP BY r.room_type ORDER BY r.room_type;

SELECT CONCAT('Inserted ', COUNT(*), ' rooms') AS status FROM room;
SELECT CONCAT('Inserted ', COUNT(*), ' beds') AS status FROM bed;

-- == sql_week8/data/05_insert_medicines.sql ==
-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: MEDICINE
-- ============================================================================

-- Insert medicines with realistic stock levels and prices
INSERT INTO medicine (med_name, generic_name, category, manufacturer, unit_price, stock_qty, reorder_level, expiry_date, description) VALUES
-- Cardiovascular medications
('Atorvastatin 20mg', 'Atorvastatin', 'Cardiovascular', 'Square Pharmaceuticals', 5.50, 500, 100, '2027-12-31', 'Cholesterol-lowering medication'),
('Amlodipine 5mg', 'Amlodipine', 'Cardiovascular', 'Beximco Pharma', 3.20, 750, 150, '2027-06-30', 'Blood pressure medication'),
('Aspirin 75mg', 'Acetylsalicylic Acid', 'Cardiovascular', 'Renata Limited', 1.50, 1000, 200, '2028-03-15', 'Blood thinner'),
('Clopidogrel 75mg', 'Clopidogrel', 'Cardiovascular', 'Incepta Pharma', 8.75, 300, 80, '2027-09-20', 'Antiplatelet medication'),

-- Antibiotics
('Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', 'Square Pharmaceuticals', 6.00, 600, 120, '2026-11-30', 'Broad-spectrum antibiotic'),
('Azithromycin 500mg', 'Azithromycin', 'Antibiotic', 'Beximco Pharma', 12.50, 400, 100, '2027-02-28', 'Macrolide antibiotic'),
('Ciprofloxacin 500mg', 'Ciprofloxacin', 'Antibiotic', 'Renata Limited', 7.80, 350, 80, '2027-07-15', 'Fluoroquinolone antibiotic'),
('Metronidazole 400mg', 'Metronidazole', 'Antibiotic', 'Incepta Pharma', 4.25, 500, 100, '2027-05-10', 'Antiprotozoal medication'),

-- Pain relief and anti-inflammatory
('Paracetamol 500mg', 'Paracetamol', 'Analgesic', 'Square Pharmaceuticals', 1.00, 2000, 400, '2028-06-30', 'Pain reliever and fever reducer'),
('Ibuprofen 400mg', 'Ibuprofen', 'NSAID', 'Beximco Pharma', 2.50, 800, 200, '2027-10-20', 'Anti-inflammatory pain reliever'),
('Tramadol 50mg', 'Tramadol', 'Opioid Analgesic', 'Renata Limited', 15.00, 200, 50, '2027-04-15', 'Moderate to severe pain relief'),
('Diclofenac 50mg', 'Diclofenac', 'NSAID', 'Incepta Pharma', 3.75, 450, 100, '2027-08-25', 'Anti-inflammatory medication'),

-- Diabetes medications
('Metformin 500mg', 'Metformin', 'Antidiabetic', 'Square Pharmaceuticals', 2.80, 900, 180, '2028-01-31', 'Type 2 diabetes medication'),
('Glimepiride 2mg', 'Glimepiride', 'Antidiabetic', 'Beximco Pharma', 4.50, 400, 80, '2027-11-15', 'Blood sugar control'),
('Insulin Glargine 100IU/ml', 'Insulin Glargine', 'Antidiabetic', 'Novo Nordisk', 850.00, 50, 15, '2026-12-31', 'Long-acting insulin'),

-- Respiratory medications
('Salbutamol Inhaler', 'Salbutamol', 'Bronchodilator', 'GSK Bangladesh', 250.00, 150, 30, '2027-09-30', 'Asthma relief inhaler'),
('Montelukast 10mg', 'Montelukast', 'Anti-asthmatic', 'Square Pharmaceuticals', 18.50, 200, 50, '2027-06-20', 'Asthma and allergy control'),
('Cetirizine 10mg', 'Cetirizine', 'Antihistamine', 'Renata Limited', 2.00, 600, 120, '2028-02-28', 'Allergy relief'),

-- Gastrointestinal medications
('Omeprazole 20mg', 'Omeprazole', 'Proton Pump Inhibitor', 'Beximco Pharma', 3.50, 700, 140, '2027-12-15', 'Acid reflux medication'),
('Ranitidine 150mg', 'Ranitidine', 'H2 Blocker', 'Incepta Pharma', 2.25, 500, 100, '2027-07-30', 'Heartburn relief'),
('Domperidone 10mg', 'Domperidone', 'Antiemetic', 'Square Pharmaceuticals', 1.80, 450, 90, '2027-10-10', 'Nausea and vomiting relief'),

-- Vitamins and supplements
('Vitamin D3 60000 IU', 'Cholecalciferol', 'Vitamin', 'Renata Limited', 25.00, 300, 60, '2028-03-31', 'Vitamin D supplement'),
('Calcium + Vitamin D', 'Calcium Carbonate', 'Mineral Supplement', 'Square Pharmaceuticals', 8.00, 400, 80, '2028-05-15', 'Bone health supplement'),
('Multivitamin', 'Mixed Vitamins', 'Vitamin', 'Beximco Pharma', 12.00, 350, 70, '2027-11-30', 'Daily multivitamin'),

-- Neurological medications
('Pregabalin 75mg', 'Pregabalin', 'Neuropathic Pain', 'Incepta Pharma', 22.50, 180, 40, '2027-08-15', 'Nerve pain medication'),
('Levetiracetam 500mg', 'Levetiracetam', 'Antiepileptic', 'Square Pharmaceuticals', 35.00, 120, 30, '2027-04-20', 'Seizure control medication');

-- Verify insertion
SELECT medicine_id, med_name, category, unit_price, stock_qty, reorder_level, expiry_date
FROM medicine ORDER BY category, med_name;

-- Show medicines needing reorder
SELECT med_name, stock_qty, reorder_level, (reorder_level - stock_qty) AS qty_to_order
FROM medicine WHERE stock_qty <= reorder_level ORDER BY (reorder_level - stock_qty) DESC;

-- Show medicines expiring soon (within 6 months)
SELECT med_name, category, stock_qty, expiry_date, DATEDIFF(expiry_date, CURDATE()) AS days_to_expiry
FROM medicine WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH) ORDER BY expiry_date;

-- Summary by category
SELECT category, COUNT(*) AS med_count, SUM(stock_qty) AS total_stock,
    ROUND(SUM(unit_price * stock_qty), 2) AS total_value
FROM medicine GROUP BY category ORDER BY total_value DESC;

SELECT CONCAT('Inserted ', COUNT(*), ' medicines') AS status FROM medicine;

-- == sql_week8/data/06_insert_appointments.sql ==
-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: APPOINTMENT
-- ============================================================================

-- Insert appointments (mix of past, current, and future)
INSERT INTO appointment (patient_id, doctor_id, appt_start, appt_end, status, reason, notes) VALUES
-- Past completed appointments
(1, 1, '2026-01-15 09:00:00', '2026-01-15 09:30:00', 'COMPLETED', 'Chest pain follow-up', 'Patient responded well to medication'),
(2, 3, '2026-01-16 10:00:00', '2026-01-16 10:30:00', 'COMPLETED', 'Migraine consultation', 'Prescribed preventive medication'),
(3, 5, '2026-01-17 14:00:00', '2026-01-17 14:45:00', 'COMPLETED', 'Post-operative checkup', 'Healing progressing normally'),
(4, 7, '2026-01-18 11:00:00', '2026-01-18 11:30:00', 'COMPLETED', 'Vaccination', 'MMR vaccine administered'),
(5, 2, '2026-01-19 15:00:00', '2026-01-19 15:30:00', 'COMPLETED', 'Memory issues assessment', 'Ordered brain MRI'),

-- Recent appointments (last few days)
(6, 4, '2026-01-24 09:00:00', '2026-01-24 09:30:00', 'COMPLETED', 'Stroke recovery follow-up', 'Improvement noted, continue physiotherapy'),
(7, 6, '2026-01-25 10:30:00', '2026-01-25 11:00:00', 'COMPLETED', 'Knee pain assessment', 'X-ray ordered'),
(8, 8, '2026-01-25 14:00:00', '2026-01-25 14:30:00', 'COMPLETED', 'Fever and cough', 'Diagnosed with viral infection'),
(9, 9, '2026-01-26 11:00:00', '2026-01-26 11:30:00', 'NO_SHOW', 'Hernia consultation', 'Patient did not arrive'),
(10, 1, '2026-01-26 15:00:00', '2026-01-26 15:30:00', 'COMPLETED', 'Hypertension management', 'Adjusted medication dosage'),

-- Today's appointments
(11, 7, '2026-01-27 09:00:00', '2026-01-27 09:30:00', 'SCHEDULED', 'Growth monitoring', NULL),
(12, 2, '2026-01-27 10:00:00', '2026-01-27 10:30:00', 'SCHEDULED', 'Numbness in hands', NULL),
(13, 5, '2026-01-27 11:00:00', '2026-01-27 11:30:00', 'SCHEDULED', 'Gallbladder surgery consultation', NULL),
(14, 11, '2026-01-27 14:00:00', '2026-01-27 14:30:00', 'SCHEDULED', 'Accident victim - ER', NULL),
(15, 13, '2026-01-27 15:00:00', '2026-01-27 15:30:00', 'SCHEDULED', 'CT scan review', NULL),

-- Tomorrow's appointments
(1, 2, '2026-01-28 09:00:00', '2026-01-28 09:30:00', 'SCHEDULED', 'Neurological assessment for tingling', NULL),
(2, 6, '2026-01-28 10:00:00', '2026-01-28 10:30:00', 'SCHEDULED', 'Arthritis follow-up', NULL),
(3, 9, '2026-01-28 11:00:00', '2026-01-28 11:30:00', 'SCHEDULED', 'Appendix surgery follow-up', NULL),
(4, 7, '2026-01-28 13:00:00', '2026-01-28 13:30:00', 'SCHEDULED', 'Asthma checkup', NULL),
(5, 1, '2026-01-28 14:00:00', '2026-01-28 14:30:00', 'SCHEDULED', 'Cardiac stress test', NULL),

-- Future appointments (next week)
(6, 3, '2026-02-01 09:00:00', '2026-02-01 09:30:00', 'SCHEDULED', 'Headache follow-up', NULL),
(7, 4, '2026-02-01 10:00:00', '2026-02-01 10:30:00', 'SCHEDULED', 'Epilepsy medication review', NULL),
(8, 8, '2026-02-02 11:00:00', '2026-02-02 11:30:00', 'SCHEDULED', 'Well-child visit', NULL),
(9, 1, '2026-02-03 14:00:00', '2026-02-03 14:30:00', 'SCHEDULED', 'Cholesterol screening', NULL),
(10, 5, '2026-02-04 15:00:00', '2026-02-04 15:30:00', 'SCHEDULED', 'Pre-op consultation', NULL),

-- Cancelled appointments
(11, 3, '2026-01-20 10:00:00', '2026-01-20 10:30:00', 'CANCELLED', 'Headache consultation', 'Patient cancelled due to work'),
(12, 1, '2026-01-22 14:00:00', '2026-01-22 14:30:00', 'CANCELLED', 'Heart checkup', 'Doctor emergency - rescheduled'),
(13, 6, '2026-01-23 09:00:00', '2026-01-23 09:30:00', 'CANCELLED', 'Joint pain', 'Patient requested change'),

-- Future appointments (2 weeks ahead)
(14, 2, '2026-02-10 10:00:00', '2026-02-10 10:30:00', 'SCHEDULED', 'Quarterly neurology checkup', NULL),
(15, 7, '2026-02-11 11:00:00', '2026-02-11 11:30:00', 'SCHEDULED', 'Immunization schedule', NULL),
(1, 3, '2026-02-12 14:00:00', '2026-02-12 14:30:00', 'SCHEDULED', 'Migraine prevention review', NULL);

-- Verify insertion
SELECT a.appt_id, p.name AS patient_name, d.name AS doctor_name, a.appt_start, a.status, a.reason
FROM appointment a JOIN patient p ON a.patient_id = p.patient_id JOIN doctor d ON a.doctor_id = d.doctor_id
ORDER BY a.appt_start DESC LIMIT 20;

-- Today's schedule
SELECT d.name AS doctor, p.name AS patient, a.appt_start, a.appt_end, a.status, a.reason
FROM appointment a JOIN patient p ON a.patient_id = p.patient_id JOIN doctor d ON a.doctor_id = d.doctor_id
WHERE DATE(a.appt_start) = CURDATE() ORDER BY a.appt_start;

-- Appointment statistics
SELECT status, COUNT(*) AS count,
    ROUND(100.0 * COUNT(*) / (SELECT COUNT(*) FROM appointment), 2) AS percentage
FROM appointment GROUP BY status ORDER BY count DESC;

SELECT CONCAT('Inserted ', COUNT(*), ' appointments') AS status FROM appointment;

-- == sql_week8/data/07_insert_prescriptions.sql ==
-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: PRESCRIPTION
-- ============================================================================

-- Insert prescriptions linked to completed appointments
INSERT INTO prescription (patient_id, doctor_id, appt_id, issued_date, diagnosis, instructions, status) VALUES
-- Prescriptions from past appointments
(1, 1, 1, '2026-01-15 09:25:00', 'Angina Pectoris', 'Take medications as prescribed. Avoid strenuous activities. Follow low-salt diet. Return if chest pain worsens.', 'DISPENSED'),
(2, 3, 2, '2026-01-16 10:20:00', 'Chronic Migraine', 'Take preventive medication daily. Avoid triggers (stress, lack of sleep). Keep headache diary.', 'DISPENSED'),
(3, 5, 3, '2026-01-17 14:40:00', 'Post-Appendectomy Recovery', 'Continue antibiotics for 7 days. Light diet for 2 weeks. No heavy lifting for 4 weeks. Return for suture removal.', 'DISPENSED'),
(4, 7, 4, '2026-01-18 11:25:00', 'Routine Immunization', 'No medications needed. Monitor for fever in next 24 hours. Apply cold compress if injection site is sore.', 'ACTIVE'),
(5, 2, 5, '2026-01-19 15:25:00', 'Mild Cognitive Impairment', 'Start cognitive enhancer. Brain MRI scheduled. Mental exercises recommended. Follow-up in 2 weeks.', 'DISPENSED'),
(6, 4, 6, '2026-01-24 09:25:00', 'Ischemic Stroke - Recovery Phase', 'Continue antiplatelet therapy. Blood pressure control critical. Physiotherapy 3x/week. Speech therapy referral.', 'DISPENSED'),
(7, 6, 7, '2026-01-25 10:55:00', 'Osteoarthritis - Right Knee', 'NSAIDs for pain. Weight reduction advised. Physiotherapy exercises. Avoid prolonged standing.', 'DISPENSED'),
(8, 8, 8, '2026-01-25 14:25:00', 'Viral Upper Respiratory Infection', 'Symptomatic treatment only. Rest and hydration. Antipyretics as needed. Return if symptoms worsen after 3 days.', 'DISPENSED'),
(10, 1, 10, '2026-01-26 15:25:00', 'Essential Hypertension', 'Increased Amlodipine dosage. Monitor BP at home twice daily. Reduce salt intake. Exercise 30min daily.', 'DISPENSED'),

-- Today's prescriptions (from scheduled appointments that would be completed)
(11, 7, 11, '2026-01-27 09:25:00', 'Growth Assessment - Normal', 'Growth parameters normal. Continue balanced diet. Multivitamin supplement recommended. Return in 6 months.', 'ACTIVE'),
(12, 2, 12, '2026-01-27 10:25:00', 'Peripheral Neuropathy', 'Start neuropathic pain medication. Vitamin B12 supplement. Nerve conduction study scheduled. Avoid alcohol.', 'ACTIVE'),
(13, 5, 13, '2026-01-27 11:25:00', 'Cholelithiasis (Gallstones)', 'Pre-operative evaluation complete. Surgery scheduled for next week. Fasting instructions provided. Pre-op antibiotics.', 'ACTIVE'),

-- Prescriptions without linked appointments (walk-ins or emergency)
(14, 11, NULL, '2026-01-27 14:15:00', 'Multiple Contusions - Traffic Accident', 'Pain management. Wound care instructions. X-rays negative for fractures. Return if severe pain or swelling increases.', 'ACTIVE'),
(15, 13, NULL, '2026-01-27 15:10:00', 'CT Scan Review - Normal', 'No significant findings on CT. Continue current medications. Preventive care advised. Annual follow-up recommended.', 'ACTIVE');

-- Verify insertion
SELECT rx.rx_id, p.name AS patient_name, d.name AS doctor_name, rx.issued_date, rx.diagnosis, rx.status
FROM prescription rx JOIN patient p ON rx.patient_id = p.patient_id JOIN doctor d ON rx.doctor_id = d.doctor_id
ORDER BY rx.issued_date DESC;

-- Prescriptions by doctor
SELECT d.name AS doctor, dept.dept_name, COUNT(rx.rx_id) AS total_prescriptions,
    SUM(CASE WHEN rx.status = 'DISPENSED' THEN 1 ELSE 0 END) AS dispensed,
    SUM(CASE WHEN rx.status = 'ACTIVE' THEN 1 ELSE 0 END) AS active
FROM doctor d LEFT JOIN prescription rx ON d.doctor_id = rx.doctor_id LEFT JOIN department dept ON d.dept_id = dept.dept_id
GROUP BY d.doctor_id, d.name, dept.dept_name HAVING total_prescriptions > 0 ORDER BY total_prescriptions DESC;

-- Recent prescription diagnoses
SELECT diagnosis, COUNT(*) AS frequency
FROM prescription WHERE issued_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY diagnosis ORDER BY frequency DESC;

SELECT CONCAT('Inserted ', COUNT(*), ' prescriptions') AS status FROM prescription;

-- Re-enable the appointment overlap trigger
DELIMITER $$
CREATE TRIGGER trg_before_appointment_overlap
BEFORE INSERT ON appointment
FOR EACH ROW
BEGIN
    DECLARE v_overlap_count INT DEFAULT 0;
    DECLARE v_doctor_name   VARCHAR(100);
    DECLARE v_conflict_time DATETIME;
    DECLARE v_error_msg     VARCHAR(500);

    IF NEW.status = 'SCHEDULED' OR NEW.status = 'CONFIRMED' THEN

        -- Time validation
        IF NEW.appt_end <= NEW.appt_start THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'INVALID TIME: Appointment end time must be after start time';
        END IF;

        IF NEW.appt_start < NOW() THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'INVALID TIME: Cannot schedule appointment in the past';
        END IF;

        -- Overlap check
        SELECT COUNT(*), MAX(appt_start)
        INTO   v_overlap_count, v_conflict_time
        FROM   appointment
        WHERE  doctor_id = NEW.doctor_id
          AND  status IN ('SCHEDULED', 'CONFIRMED')
          AND  (
                    (NEW.appt_start >= appt_start AND NEW.appt_start < appt_end) OR
                    (NEW.appt_end   >  appt_start AND NEW.appt_end  <= appt_end) OR
                    (NEW.appt_start <= appt_start AND NEW.appt_end  >= appt_end)
                );

        IF v_overlap_count > 0 THEN
            SELECT name INTO v_doctor_name FROM doctor WHERE doctor_id = NEW.doctor_id;

            SET v_error_msg = CONCAT(
                'APPOINTMENT OVERLAP: Doctor "', IFNULL(v_doctor_name, 'Unknown'),
                '" already has appointment at ',
                DATE_FORMAT(v_conflict_time, '%Y-%m-%d %H:%i'),
                '. New slot: ',
                DATE_FORMAT(NEW.appt_start, '%Y-%m-%d %H:%i'),
                ' – ',
                DATE_FORMAT(NEW.appt_end, '%H:%i')
            );
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
        END IF;

    END IF;
END$$
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
SELECT '✅ All seed data inserted and trigger re-enabled!' AS status;