-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: PRESCRIPTION
-- ============================================================================

USE medicore_db;

-- Insert prescriptions linked to completed appointments
INSERT INTO prescription (patient_id, doctor_id, appt_id, issued_date, diagnosis, instructions, status) VALUES
-- Prescriptions from past appointments
(1, 1, 1, '2026-01-15 09:25:00', 
 'Angina Pectoris', 
 'Take medications as prescribed. Avoid strenuous activities. Follow low-salt diet. Return if chest pain worsens.', 
 'DISPENSED'),

(2, 3, 2, '2026-01-16 10:20:00', 
 'Chronic Migraine', 
 'Take preventive medication daily. Avoid triggers (stress, lack of sleep). Keep headache diary.', 
 'DISPENSED'),

(3, 5, 3, '2026-01-17 14:40:00', 
 'Post-Appendectomy Recovery', 
 'Continue antibiotics for 7 days. Light diet for 2 weeks. No heavy lifting for 4 weeks. Return for suture removal.', 
 'DISPENSED'),

(4, 7, 4, '2026-01-18 11:25:00', 
 'Routine Immunization', 
 'No medications needed. Monitor for fever in next 24 hours. Apply cold compress if injection site is sore.', 
 'ACTIVE'),

(5, 2, 5, '2026-01-19 15:25:00', 
 'Mild Cognitive Impairment', 
 'Start cognitive enhancer. Brain MRI scheduled. Mental exercises recommended. Follow-up in 2 weeks.', 
 'DISPENSED'),

(6, 4, 6, '2026-01-24 09:25:00', 
 'Ischemic Stroke - Recovery Phase', 
 'Continue antiplatelet therapy. Blood pressure control critical. Physiotherapy 3x/week. Speech therapy referral.', 
 'DISPENSED'),

(7, 6, 7, '2026-01-25 10:55:00', 
 'Osteoarthritis - Right Knee', 
 'NSAIDs for pain. Weight reduction advised. Physiotherapy exercises. Avoid prolonged standing.', 
 'DISPENSED'),

(8, 8, 8, '2026-01-25 14:25:00', 
 'Viral Upper Respiratory Infection', 
 'Symptomatic treatment only. Rest and hydration. Antipyretics as needed. Return if symptoms worsen after 3 days.', 
 'DISPENSED'),

(10, 1, 10, '2026-01-26 15:25:00', 
 'Essential Hypertension', 
 'Increased Amlodipine dosage. Monitor BP at home twice daily. Reduce salt intake. Exercise 30min daily.', 
 'DISPENSED'),

-- Today's prescriptions (from scheduled appointments that would be completed)
(11, 7, 11, '2026-01-27 09:25:00', 
 'Growth Assessment - Normal', 
 'Growth parameters normal. Continue balanced diet. Multivitamin supplement recommended. Return in 6 months.', 
 'ACTIVE'),

(12, 2, 12, '2026-01-27 10:25:00', 
 'Peripheral Neuropathy', 
 'Start neuropathic pain medication. Vitamin B12 supplement. Nerve conduction study scheduled. Avoid alcohol.', 
 'ACTIVE'),

(13, 5, 13, '2026-01-27 11:25:00', 
 'Cholelithiasis (Gallstones)', 
 'Pre-operative evaluation complete. Surgery scheduled for next week. Fasting instructions provided. Pre-op antibiotics.', 
 'ACTIVE'),

-- Prescriptions without linked appointments (walk-ins or emergency)
(14, 11, NULL, '2026-01-27 14:15:00', 
 'Multiple Contusions - Traffic Accident', 
 'Pain management. Wound care instructions. X-rays negative for fractures. Return if severe pain or swelling increases.', 
 'ACTIVE'),

(15, 13, NULL, '2026-01-27 15:10:00', 
 'CT Scan Review - Normal', 
 'No significant findings on CT. Continue current medications. Preventive care advised. Annual follow-up recommended.', 
 'ACTIVE');

-- Verify insertion
SELECT 
    rx.rx_id,
    p.name AS patient_name,
    d.name AS doctor_name,
    rx.issued_date,
    rx.diagnosis,
    rx.status
FROM prescription rx
JOIN patient p ON rx.patient_id = p.patient_id
JOIN doctor d ON rx.doctor_id = d.doctor_id
ORDER BY rx.issued_date DESC;

-- Prescriptions by doctor
SELECT 
    d.name AS doctor,
    dept.dept_name,
    COUNT(rx.rx_id) AS total_prescriptions,
    SUM(CASE WHEN rx.status = 'DISPENSED' THEN 1 ELSE 0 END) AS dispensed,
    SUM(CASE WHEN rx.status = 'ACTIVE' THEN 1 ELSE 0 END) AS active
FROM doctor d
LEFT JOIN prescription rx ON d.doctor_id = rx.doctor_id
LEFT JOIN department dept ON d.dept_id = dept.dept_id
GROUP BY d.doctor_id, d.name, dept.dept_name
HAVING total_prescriptions > 0
ORDER BY total_prescriptions DESC;

-- Recent prescription diagnoses
SELECT 
    diagnosis,
    COUNT(*) AS frequency
FROM prescription
WHERE issued_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY diagnosis
ORDER BY frequency DESC;

SELECT CONCAT('Inserted ', COUNT(*), ' prescriptions') AS status FROM prescription;