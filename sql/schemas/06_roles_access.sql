-- ============================================================================
-- MediCore HMS - Role-Based Access Control
-- Week 13 Final Submission
--
-- Roles created:
--   medicore_admin  - Full access (all DDL + DML)
--   medicore_doctor - Read patients/appointments, write encounters/prescriptions
--   medicore_nurse  - Read/write beds and lab orders, read patients
--   medicore_billing- Read/write invoices and payments only
--   medicore_readonly- SELECT only on all tables
-- ============================================================================

USE medicore_db;

-- ============================================================================
-- Create application users
-- ============================================================================
-- NOTE: Adjust passwords before production deployment!

CREATE USER IF NOT EXISTS 'medicore_admin'@'localhost'   IDENTIFIED BY 'Admin@Medicore2024!';
CREATE USER IF NOT EXISTS 'medicore_doctor'@'localhost'  IDENTIFIED BY 'Doctor@Medicore2024!';
CREATE USER IF NOT EXISTS 'medicore_nurse'@'localhost'   IDENTIFIED BY 'Nurse@Medicore2024!';
CREATE USER IF NOT EXISTS 'medicore_billing'@'localhost' IDENTIFIED BY 'Billing@Medicore2024!';
CREATE USER IF NOT EXISTS 'medicore_readonly'@'localhost' IDENTIFIED BY 'Read@Medicore2024!';

-- ============================================================================
-- ADMIN role - full privileges
-- ============================================================================
GRANT ALL PRIVILEGES ON medicore_db.* TO 'medicore_admin'@'localhost';

-- ============================================================================
-- DOCTOR role
-- ============================================================================
GRANT SELECT ON medicore_db.patient        TO 'medicore_doctor'@'localhost';
GRANT SELECT ON medicore_db.appointment    TO 'medicore_doctor'@'localhost';
GRANT SELECT ON medicore_db.department     TO 'medicore_doctor'@'localhost';
GRANT SELECT ON medicore_db.medicine       TO 'medicore_doctor'@'localhost';
GRANT SELECT ON medicore_db.lab_result     TO 'medicore_doctor'@'localhost';
GRANT SELECT, INSERT, UPDATE ON medicore_db.encounter           TO 'medicore_doctor'@'localhost';
GRANT SELECT, INSERT, UPDATE ON medicore_db.prescription        TO 'medicore_doctor'@'localhost';
GRANT SELECT, INSERT, UPDATE ON medicore_db.prescription_detail TO 'medicore_doctor'@'localhost';
GRANT SELECT, INSERT         ON medicore_db.lab_order           TO 'medicore_doctor'@'localhost';
GRANT UPDATE (status, notes) ON medicore_db.appointment         TO 'medicore_doctor'@'localhost';

-- ============================================================================
-- NURSE role
-- ============================================================================
GRANT SELECT ON medicore_db.patient     TO 'medicore_nurse'@'localhost';
GRANT SELECT ON medicore_db.appointment TO 'medicore_nurse'@'localhost';
GRANT SELECT ON medicore_db.room        TO 'medicore_nurse'@'localhost';
GRANT SELECT, UPDATE ON medicore_db.bed         TO 'medicore_nurse'@'localhost';
GRANT SELECT, INSERT, UPDATE ON medicore_db.lab_order  TO 'medicore_nurse'@'localhost';
GRANT SELECT, INSERT         ON medicore_db.lab_result TO 'medicore_nurse'@'localhost';

-- ============================================================================
-- BILLING role
-- ============================================================================
GRANT SELECT ON medicore_db.patient     TO 'medicore_billing'@'localhost';
GRANT SELECT ON medicore_db.appointment TO 'medicore_billing'@'localhost';
GRANT SELECT, INSERT, UPDATE ON medicore_db.invoice TO 'medicore_billing'@'localhost';
GRANT SELECT, INSERT         ON medicore_db.payment TO 'medicore_billing'@'localhost';

-- ============================================================================
-- READ-ONLY role
-- ============================================================================
GRANT SELECT ON medicore_db.* TO 'medicore_readonly'@'localhost';

FLUSH PRIVILEGES;

SELECT 'Role-based access control configured successfully!' AS status;

-- Show grants summary
SELECT 
    GRANTEE,
    TABLE_SCHEMA,
    TABLE_NAME,
    PRIVILEGE_TYPE,
    IS_GRANTABLE
FROM information_schema.TABLE_PRIVILEGES
WHERE TABLE_SCHEMA = 'medicore_db'
ORDER BY GRANTEE, TABLE_NAME;
