-- ============================================================================
-- MediCore HMS - Extended Seed Data (Week 13)
-- Adds: encounters, prescription_details, lab_orders, invoices, payments
-- ============================================================================

USE medicore_db;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- ENCOUNTER records (linked to completed appointments)
-- ============================================================================
INSERT INTO encounter (patient_id, doctor_id, appt_id, encounter_date, encounter_type,
    chief_complaint, diagnosis, treatment_plan, vital_signs, status) VALUES

(1, 1, 1, '2026-01-15 09:00:00', 'OUTPATIENT',
 'Chest pain on exertion',
 'Angina Pectoris - Stable',
 'Continue beta-blocker, add long-acting nitrate, stress test in 2 weeks',
 '{"bp":"140/90","hr":82,"temp":36.8,"spo2":98,"rr":16}',
 'CLOSED'),

(2, 3, 2, '2026-01-16 10:00:00', 'OUTPATIENT',
 'Recurrent throbbing headaches',
 'Chronic Migraine without Aura',
 'Topiramate 25mg preventive. Triptan for acute attacks.',
 '{"bp":"118/76","hr":74,"temp":36.6,"spo2":99,"rr":14}',
 'CLOSED'),

(3, 5, 3, '2026-01-17 14:00:00', 'OUTPATIENT',
 'Wound check after appendectomy',
 'Post-Appendectomy Recovery - Uncomplicated',
 'Wound healing well. Sutures to be removed at next visit.',
 '{"bp":"122/78","hr":76,"temp":36.9,"spo2":98,"rr":15}',
 'CLOSED'),

(5, 2, 5, '2026-01-19 15:00:00', 'OUTPATIENT',
 'Forgetfulness, difficulty concentrating',
 'Mild Cognitive Impairment - under investigation',
 'MRI Brain ordered. Donepezil 5mg trial.',
 '{"bp":"130/85","hr":70,"temp":36.7,"spo2":99,"rr":14}',
 'CLOSED'),

(7, 6, 7, '2026-01-25 10:30:00', 'OUTPATIENT',
 'Right knee pain for 6 months, worse on stairs',
 'Osteoarthritis - Right Knee Grade II',
 'Ibuprofen 400mg TDS with food. Physiotherapy referral.',
 '{"bp":"128/82","hr":78,"temp":36.5,"spo2":99,"rr":15}',
 'CLOSED');

-- ============================================================================
-- PRESCRIPTION DETAILS (medicines per prescription)
-- ============================================================================
INSERT INTO prescription_detail (rx_id, medicine_id, dosage, frequency, duration, quantity) VALUES
-- rx_id 1: Angina - Aspirin + Amlodipine + Atorvastatin
(1, 3,  '75mg',   '1 tablet once daily',       '30 days', 30),
(1, 2,  '5mg',    '1 tablet once daily',        '30 days', 30),
(1, 1,  '20mg',   '1 tablet at bedtime',        '30 days', 30),

-- rx_id 2: Migraine - Paracetamol + Metoclopramide
(2, 9,  '1000mg', '2 tablets at onset of pain', '14 days', 28),

-- rx_id 3: Post-op - Amoxicillin + Paracetamol
(3, 5,  '500mg',  '1 capsule thrice daily',     '7 days',  21),
(3, 9,  '500mg',  '1 tablet every 6 hrs PRN',   '7 days',  28),

-- rx_id 7: Osteoarthritis - Ibuprofen + Calcium
(7, 10, '400mg',  '1 tablet thrice daily with food', '14 days', 42),
(7, 23, '1 tablet', '1 tablet daily',            '30 days', 30);

-- ============================================================================
-- LAB ORDERS
-- ============================================================================
INSERT INTO lab_order (patient_id, doctor_id, encounter_id, test_name, test_type,
    test_category, priority, order_date, status) VALUES
(1, 1, 1, 'Lipid Profile',      'Blood',  'Chemistry', 'ROUTINE', '2026-01-15 09:30:00', 'COMPLETED'),
(1, 1, 1, 'ECG (Resting)',      'Cardiac','Cardiology','ROUTINE', '2026-01-15 09:30:00', 'COMPLETED'),
(5, 2, 4, 'MRI Brain',          'Imaging','Radiology', 'ROUTINE', '2026-01-19 15:30:00', 'IN_PROGRESS'),
(7, 6, 5, 'X-Ray Right Knee',   'Imaging','Radiology', 'ROUTINE', '2026-01-25 11:00:00', 'COMPLETED'),
(8, 8, NULL, 'CBC + CRP',       'Blood',  'Hematology','URGENT',  '2026-01-25 14:00:00', 'COMPLETED');

-- ============================================================================
-- LAB RESULTS
-- ============================================================================
INSERT INTO lab_result (order_id, test_parameter, result_value, unit, normal_range,
    abnormal_flag, result_date, performed_by, verified_by) VALUES
(1, 'Total Cholesterol', '195',  'mg/dL', '< 200',      'NORMAL',   '2026-01-15 15:00:00', 'Lab Tech A', 'Dr. Sabina Rahman'),
(1, 'LDL',              '125',  'mg/dL', '< 130',       'NORMAL',   '2026-01-15 15:00:00', 'Lab Tech A', 'Dr. Sabina Rahman'),
(1, 'HDL',              '38',   'mg/dL', '> 40 (male)', 'LOW',      '2026-01-15 15:00:00', 'Lab Tech A', 'Dr. Sabina Rahman'),
(1, 'Triglycerides',    '175',  'mg/dL', '< 150',       'HIGH',     '2026-01-15 15:00:00', 'Lab Tech A', 'Dr. Sabina Rahman'),
(4, 'Joint Space Width','3.5',  'mm',    '4–7 mm',      'LOW',      '2026-01-25 16:00:00', 'Dr. Tanvir', 'Dr. Tanvir Hasan'),
(5, 'WBC',              '11.2', '× 10⁹/L','4.5–11.0',  'HIGH',     '2026-01-25 17:00:00', 'Lab Tech B', 'Dr. Khalid Rahman'),
(5, 'CRP',              '28',   'mg/L',  '< 5',         'CRITICAL', '2026-01-25 17:00:00', 'Lab Tech B', 'Dr. Khalid Rahman');

-- ============================================================================
-- INVOICES
-- ============================================================================
INSERT INTO invoice (patient_id, appt_id, encounter_id, invoice_number, invoice_date,
    due_date, subtotal, tax_amount, total_amount, paid_amount, payment_status) VALUES
(1, 1, 1, 'INV-20260115-0001', '2026-01-15', '2026-02-14', 1500.00, 75.00,  1575.00, 1575.00, 'PAID'),
(2, 2, 2, 'INV-20260116-0002', '2026-01-16', '2026-02-15', 1500.00, 75.00,  1575.00, 1575.00, 'PAID'),
(3, 3, 3, 'INV-20260117-0003', '2026-01-17', '2026-02-16', 2000.00, 100.00, 2100.00, 2100.00, 'PAID'),
(5, 5, 4, 'INV-20260119-0004', '2026-01-19', '2026-02-18', 2500.00, 125.00, 2625.00, 1000.00, 'PARTIALLY_PAID'),
(7, 7, 5, 'INV-20260125-0005', '2026-01-25', '2026-02-24', 1500.00, 75.00,  1575.00, 0.00,    'UNPAID'),
(8, 8, NULL,'INV-20260125-0006','2026-01-25', '2026-02-24', 1200.00, 60.00,  1260.00, 1260.00, 'PAID');

-- ============================================================================
-- PAYMENTS
-- ============================================================================
INSERT INTO payment (invoice_id, patient_id, payment_date, amount,
    payment_method, transaction_id, received_by, status) VALUES
(1, 1, '2026-01-15 10:00:00', 1575.00, 'CASH',          NULL,           'Billing Desk 1', 'COMPLETED'),
(2, 2, '2026-01-16 11:00:00', 1575.00, 'MOBILE_PAYMENT', 'TXN-BKS-0012', 'Billing Desk 2', 'COMPLETED'),
(3, 3, '2026-01-17 15:30:00', 2100.00, 'CARD',          'TXN-CARD-0031', 'Billing Desk 1', 'COMPLETED'),
(4, 5, '2026-01-19 16:00:00', 1000.00, 'CASH',          NULL,           'Billing Desk 3', 'COMPLETED'),
(6, 8, '2026-01-25 15:00:00', 1260.00, 'INSURANCE',     'INS-PREF-0091', 'Billing Desk 2', 'COMPLETED');

SET FOREIGN_KEY_CHECKS = 1;
SELECT '✅ Extended seed data (encounters, labs, invoices, payments) inserted!' AS status;
