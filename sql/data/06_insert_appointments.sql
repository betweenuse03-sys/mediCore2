-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: APPOINTMENT
-- ============================================================================

USE medicore_db;

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
SELECT 
    a.appt_id,
    p.name AS patient_name,
    d.name AS doctor_name,
    a.appt_start,
    a.status,
    a.reason
FROM appointment a
JOIN patient p ON a.patient_id = p.patient_id
JOIN doctor d ON a.doctor_id = d.doctor_id
ORDER BY a.appt_start DESC
LIMIT 20;

-- Today's schedule
SELECT 
    d.name AS doctor,
    p.name AS patient,
    a.appt_start,
    a.appt_end,
    a.status,
    a.reason
FROM appointment a
JOIN patient p ON a.patient_id = p.patient_id
JOIN doctor d ON a.doctor_id = d.doctor_id
WHERE DATE(a.appt_start) = CURDATE()
ORDER BY a.appt_start;

-- Appointment statistics
SELECT 
    status,
    COUNT(*) AS count,
    ROUND(100.0 * COUNT(*) / (SELECT COUNT(*) FROM appointment), 2) AS percentage
FROM appointment
GROUP BY status
ORDER BY count DESC;

SELECT CONCAT('Inserted ', COUNT(*), ' appointments') AS status FROM appointment;