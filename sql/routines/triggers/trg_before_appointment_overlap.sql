-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Trigger: trg_before_appointment_overlap
-- ============================================================================
-- Purpose: Prevent scheduling overlapping appointments for the same doctor
-- Type: BEFORE INSERT
-- Table: appointment
-- ============================================================================

USE hospital_db;

-- Drop trigger if it exists
DROP TRIGGER IF EXISTS trg_before_appointment_overlap;

-- Change delimiter
DELIMITER $$

CREATE TRIGGER trg_before_appointment_overlap
BEFORE INSERT ON appointment
FOR EACH ROW
BEGIN
    DECLARE v_overlap_count INT DEFAULT 0;
    DECLARE v_doctor_name VARCHAR(100);
    DECLARE v_conflict_time DATETIME;
    DECLARE v_error_msg VARCHAR(500);
    
    -- Only check for SCHEDULED appointments
    IF NEW.status = 'SCHEDULED' THEN
        
        -- ====================================================================
        -- Check for overlapping appointments for the same doctor
        -- ====================================================================
        -- An overlap occurs when:
        -- 1. New start time falls within existing appointment, OR
        -- 2. New end time falls within existing appointment, OR
        -- 3. New appointment completely encompasses existing appointment
        -- ====================================================================
        
        SELECT COUNT(*), MAX(appt_start)
        INTO v_overlap_count, v_conflict_time
        FROM appointment
        WHERE doctor_id = NEW.doctor_id
          AND status = 'SCHEDULED'
          AND appt_id != IFNULL(NEW.appt_id, 0)  -- Exclude self for updates
          AND (
              -- New appointment starts during existing appointment
              (NEW.appt_start >= appt_start AND NEW.appt_start < appt_end)
              OR
              -- New appointment ends during existing appointment
              (NEW.appt_end > appt_start AND NEW.appt_end <= appt_end)
              OR
              -- New appointment completely covers existing appointment
              (NEW.appt_start <= appt_start AND NEW.appt_end >= appt_end)
          );
        
        -- ====================================================================
        -- If overlap detected, prevent insertion
        -- ====================================================================
        IF v_overlap_count > 0 THEN
            -- Get doctor name for better error message
            SELECT name INTO v_doctor_name
            FROM doctor
            WHERE doctor_id = NEW.doctor_id;
            
            -- Create detailed error message
            SET v_error_msg = CONCAT(
                'APPOINTMENT OVERLAP: Doctor "', 
                IFNULL(v_doctor_name, 'Unknown'),
                '" (ID: ', NEW.doctor_id, 
                ') already has a scheduled appointment at ',
                DATE_FORMAT(v_conflict_time, '%Y-%m-%d %H:%i'),
                '. Cannot schedule overlapping appointment from ',
                DATE_FORMAT(NEW.appt_start, '%Y-%m-%d %H:%i'),
                ' to ',
                DATE_FORMAT(NEW.appt_end, '%H:%i')
            );
            
            -- Raise error to prevent insertion
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = v_error_msg;
        END IF;
        
        -- ====================================================================
        -- Additional validation: Ensure end time is after start time
        -- ====================================================================
        IF NEW.appt_end <= NEW.appt_start THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'INVALID TIME: Appointment end time must be after start time';
        END IF;
        
        -- ====================================================================
        -- Additional validation: Prevent scheduling in the past
        -- ====================================================================
        IF NEW.appt_start < NOW() THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'INVALID TIME: Cannot schedule appointment in the past';
        END IF;
        
    END IF;
    
END$$

DELIMITER ;

-- ============================================================================
-- Test the Trigger
-- ============================================================================

-- Test 1: Insert a valid appointment (should succeed)
INSERT INTO appointment (patient_id, doctor_id, appt_start, appt_end, status, reason)
VALUES (1, 1, '2026-03-01 14:00:00', '2026-03-01 14:30:00', 'SCHEDULED', 'Test appointment 1');

SELECT 'Test 1 PASSED: Valid appointment inserted' AS test_result;

-- Test 2: Try to insert overlapping appointment (should FAIL)
-- This should trigger an error
-- Uncomment to test:
/*
INSERT INTO appointment (patient_id, doctor_id, appt_start, appt_end, status, reason)
VALUES (2, 1, '2026-03-01 14:15:00', '2026-03-01 14:45:00', 'SCHEDULED', 'Overlap test - should fail');
*/

-- Test 3: Insert non-overlapping appointment for same doctor (should succeed)
INSERT INTO appointment (patient_id, doctor_id, appt_start, appt_end, status, reason)
VALUES (2, 1, '2026-03-01 15:00:00', '2026-03-01 15:30:00', 'SCHEDULED', 'Test appointment 2');

SELECT 'Test 3 PASSED: Non-overlapping appointment inserted' AS test_result;

-- Test 4: Cancelled appointments should not trigger overlap check
INSERT INTO appointment (patient_id, doctor_id, appt_start, appt_end, status, reason)
VALUES (3, 1, '2026-03-01 14:10:00', '2026-03-01 14:40:00', 'CANCELLED', 'Cancelled - no conflict');

SELECT 'Test 4 PASSED: Cancelled appointment can overlap (by design)' AS test_result;

-- View all appointments for doctor 1
SELECT 
    appt_id,
    patient_id,
    appt_start,
    appt_end,
    status,
    reason
FROM appointment
WHERE doctor_id = 1
  AND DATE(appt_start) = '2026-03-01'
ORDER BY appt_start;

-- Verify trigger creation
SHOW TRIGGERS FROM hospital_db WHERE `Trigger` = 'trg_before_appointment_overlap';

SELECT 'Trigger trg_before_appointment_overlap created successfully!' AS status;

-- ============================================================================
-- Trigger Details
-- ============================================================================
-- Name: trg_before_appointment_overlap
-- Type: BEFORE INSERT trigger
-- Table: appointment
-- Purpose: Ensure no double-booking of doctors
-- Timing: Executes before each INSERT operation
-- Scope: Row-level trigger (fires for each row)
-- 
-- Business Rules Enforced:
--   1. Doctor cannot have overlapping SCHEDULED appointments
--   2. Appointment end time must be after start time
--   3. Cannot schedule appointments in the past
--   4. Cancelled/Completed appointments don't block scheduling
-- 
-- Overlap Detection Logic:
--   Checks three overlap scenarios:
--   - New appointment starts during existing appointment
--   - New appointment ends during existing appointment
--   - New appointment completely encompasses existing appointment
-- 
-- Error Handling:
--   - Raises SQLSTATE '45000' (user-defined error)
--   - Provides detailed error message with doctor name and conflict time
--   - Prevents INSERT operation completely
-- 
-- Benefits:
--   1. Automatic enforcement at database level
--   2. Cannot be bypassed by application code
--   3. Prevents data integrity issues
--   4. Centralized business logic
--   5. No performance impact on normal operations
-- 
-- Week 13 Enhancement:
--   This trigger demonstrates complex business logic enforcement.
--   For Week 13, additional triggers will be added for:
--   - Stock updates when prescriptions are filled
--   - Automatic invoice status updates when payments are made
--   - Audit logging for sensitive operations
-- ============================================================================