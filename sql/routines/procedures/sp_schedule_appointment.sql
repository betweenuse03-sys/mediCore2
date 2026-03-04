-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Stored Procedure: sp_schedule_appointment
-- ============================================================================
-- Purpose: Schedule a new appointment with validation and error handling
-- Parameters:
--   p_patient_id    INT     - ID of the patient
--   p_doctor_id     INT     - ID of the doctor
--   p_start_time    DATETIME - Appointment start time
--   p_end_time      DATETIME - Appointment end time
--   p_reason        TEXT    - Reason for appointment
-- ============================================================================

USE hospital_db;

-- Drop procedure if it exists
DROP PROCEDURE IF EXISTS sp_schedule_appointment;

-- Change delimiter
DELIMITER $$

CREATE PROCEDURE sp_schedule_appointment(
    IN p_patient_id INT,
    IN p_doctor_id INT,
    IN p_start_time DATETIME,
    IN p_end_time DATETIME,
    IN p_reason TEXT
)
BEGIN
    -- Declare variables
    DECLARE v_patient_exists INT DEFAULT 0;
    DECLARE v_doctor_exists INT DEFAULT 0;
    DECLARE v_doctor_status VARCHAR(20);
    DECLARE v_overlap_count INT DEFAULT 0;
    DECLARE v_error_msg VARCHAR(255);
    
    -- Declare exit handler for SQL exceptions
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- Rollback transaction on error
        ROLLBACK;
        -- Return error message
        SELECT 'ERROR: Transaction rolled back due to error' AS message;
    END;
    
    -- Start transaction
    START TRANSACTION;
    
    -- ========================================================================
    -- Validation 1: Check if patient exists
    -- ========================================================================
    SELECT COUNT(*) INTO v_patient_exists
    FROM patient
    WHERE patient_id = p_patient_id;
    
    IF v_patient_exists = 0 THEN
        SET v_error_msg = CONCAT('ERROR: Patient ID ', p_patient_id, ' does not exist');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END IF;
    
    -- ========================================================================
    -- Validation 2: Check if doctor exists and is active
    -- ========================================================================
    SELECT COUNT(*), IFNULL(MAX(status), 'NOT_FOUND') 
    INTO v_doctor_exists, v_doctor_status
    FROM doctor
    WHERE doctor_id = p_doctor_id;
    
    IF v_doctor_exists = 0 THEN
        SET v_error_msg = CONCAT('ERROR: Doctor ID ', p_doctor_id, ' does not exist');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END IF;
    
    IF v_doctor_status != 'ACTIVE' THEN
        SET v_error_msg = CONCAT('ERROR: Doctor is not available (Status: ', v_doctor_status, ')');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END IF;
    
    -- ========================================================================
    -- Validation 3: Check time validity
    -- ========================================================================
    IF p_end_time <= p_start_time THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERROR: End time must be after start time';
    END IF;
    
    IF p_start_time < NOW() THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERROR: Cannot schedule appointment in the past';
    END IF;
    
    -- ========================================================================
    -- Validation 4: Check for overlapping doctor appointments
    -- ========================================================================
    SELECT COUNT(*) INTO v_overlap_count
    FROM appointment
    WHERE doctor_id = p_doctor_id
      AND status = 'SCHEDULED'
      AND (
          (p_start_time >= appt_start AND p_start_time < appt_end)
          OR
          (p_end_time > appt_start AND p_end_time <= appt_end)
          OR
          (p_start_time <= appt_start AND p_end_time >= appt_end)
      );
    
    IF v_overlap_count > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERROR: Doctor already has an appointment at this time';
    END IF;
    
    -- ========================================================================
    -- Insert the appointment
    -- ========================================================================
    INSERT INTO appointment (
        patient_id,
        doctor_id,
        appt_start,
        appt_end,
        status,
        reason
    ) VALUES (
        p_patient_id,
        p_doctor_id,
        p_start_time,
        p_end_time,
        'SCHEDULED',
        p_reason
    );
    
    -- Commit transaction
    COMMIT;
    
    -- Return success message with appointment ID
    SELECT 
        LAST_INSERT_ID() AS appointment_id,
        'SUCCESS: Appointment scheduled successfully' AS message,
        p_start_time AS scheduled_time;
        
END$$

DELIMITER ;

-- ============================================================================
-- Test the Procedure
-- ============================================================================

-- Test 1: Valid appointment (should succeed)
CALL sp_schedule_appointment(
    1,                              -- patient_id
    1,                              -- doctor_id
    '2026-02-15 10:00:00',         -- start time
    '2026-02-15 10:30:00',         -- end time
    'Cardiac follow-up consultation'
);

-- Verify the appointment was created
SELECT * FROM appointment WHERE appointment_id = LAST_INSERT_ID();

-- Test 2: Try to create overlapping appointment (should fail)
-- Uncomment to test:
/*
CALL sp_schedule_appointment(
    2,                              -- different patient
    1,                              -- same doctor
    '2026-02-15 10:15:00',         -- overlapping time
    '2026-02-15 10:45:00',
    'Test overlap - should fail'
);
*/

-- Test 3: Invalid patient ID (should fail)
-- Uncomment to test:
/*
CALL sp_schedule_appointment(
    9999,                           -- non-existent patient
    1,
    '2026-02-16 11:00:00',
    '2026-02-16 11:30:00',
    'Test invalid patient'
);
*/

-- Verify procedure creation
SHOW PROCEDURE STATUS WHERE Db = 'hospital_db' AND Name = 'sp_schedule_appointment';

SELECT 'Procedure sp_schedule_appointment created successfully!' AS status;

-- ============================================================================
-- Procedure Details
-- ============================================================================
-- Name: sp_schedule_appointment
-- Purpose: Safely schedule appointments with comprehensive validation
-- Transaction Safety: Yes (uses START TRANSACTION, COMMIT, ROLLBACK)
-- Error Handling: Yes (EXIT HANDLER for SQLEXCEPTION)
-- Validations:
--   1. Patient exists
--   2. Doctor exists and is ACTIVE
--   3. End time > Start time
--   4. Start time is not in the past
--   5. No overlapping doctor appointments
-- Benefits:
--   1. Centralized business logic
--   2. Data integrity guaranteed
--   3. Consistent error handling
--   4. Prevents double-booking
--   5. Atomic operation (all or nothing)
-- ============================================================================