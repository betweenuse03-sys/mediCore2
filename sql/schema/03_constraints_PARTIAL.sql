-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Constraints Script (PARTIAL)
-- ============================================================================
-- Purpose: Add UNIQUE constraints and CHECK constraints to ensure data integrity
-- ============================================================================

USE hospital_db;

-- ============================================================================
-- UNIQUE Constraints
-- ============================================================================

-- Department: Ensure unique department names
ALTER TABLE department
    ADD CONSTRAINT uq_dept_name UNIQUE (dept_name);

-- Doctor: Ensure unique email addresses
ALTER TABLE doctor
    ADD CONSTRAINT uq_doctor_email UNIQUE (email);

-- Patient: Ensure unique phone numbers (one patient per phone)
ALTER TABLE patient
    ADD CONSTRAINT uq_patient_phone UNIQUE (phone);

-- Room: Ensure unique room numbers
ALTER TABLE room
    ADD CONSTRAINT uq_room_number UNIQUE (room_number);

-- Bed: Ensure unique bed number within each room
ALTER TABLE bed
    ADD CONSTRAINT uq_bed_room UNIQUE (room_id, bed_number);

-- Medicine: Ensure unique medicine names
ALTER TABLE medicine
    ADD CONSTRAINT uq_medicine_name UNIQUE (med_name);

-- ============================================================================
-- CHECK Constraints (MySQL 8.0.16+)
-- ============================================================================

-- Patient: Ensure valid date of birth (not future date)
ALTER TABLE patient
    ADD CONSTRAINT chk_patient_dob 
    CHECK (dob <= CURDATE());

-- Appointment: Ensure end time is after start time
ALTER TABLE appointment
    ADD CONSTRAINT chk_appt_time 
    CHECK (appt_end > appt_start);

-- Appointment: Ensure appointment is not too far in future (1 year max)
ALTER TABLE appointment
    ADD CONSTRAINT chk_appt_future 
    CHECK (appt_start <= DATE_ADD(CURDATE(), INTERVAL 1 YEAR));

-- Room: Ensure positive daily rate
ALTER TABLE room
    ADD CONSTRAINT chk_room_rate 
    CHECK (daily_rate >= 0);

-- Medicine: Ensure positive unit price
ALTER TABLE medicine
    ADD CONSTRAINT chk_medicine_price 
    CHECK (unit_price >= 0);

-- Medicine: Ensure non-negative stock quantity
ALTER TABLE medicine
    ADD CONSTRAINT chk_medicine_stock 
    CHECK (stock_qty >= 0);

-- Medicine: Ensure reorder level is positive
ALTER TABLE medicine
    ADD CONSTRAINT chk_medicine_reorder 
    CHECK (reorder_level > 0);

-- ============================================================================
-- NOT NULL Constraints (Additional - already in CREATE TABLE but documented)
-- ============================================================================
-- These are enforced in table creation but listed here for documentation:
-- 
-- department.dept_name NOT NULL
-- doctor.name NOT NULL
-- patient.name NOT NULL
-- patient.dob NOT NULL
-- patient.gender NOT NULL
-- patient.phone NOT NULL
-- appointment.patient_id NOT NULL
-- appointment.doctor_id NOT NULL
-- appointment.appt_start NOT NULL
-- appointment.appt_end NOT NULL
-- bed.room_id NOT NULL
-- bed.bed_number NOT NULL
-- medicine.med_name NOT NULL
-- medicine.unit_price NOT NULL
-- medicine.stock_qty NOT NULL
-- prescription.patient_id NOT NULL
-- prescription.doctor_id NOT NULL
-- ============================================================================

-- Verify constraints
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = 'hospital_db'
ORDER BY TABLE_NAME, CONSTRAINT_TYPE;

SELECT 'Constraints added successfully!' AS status;

-- ============================================================================
-- End of Constraints Script (Week 8 Partial)
-- ============================================================================