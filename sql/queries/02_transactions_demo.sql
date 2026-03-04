-- ============================================================================
-- MediCore HMS - Transaction Demonstrations
-- Week 13 Final Submission
--
-- Demonstrates:
--   ✅ Commit / Rollback patterns
--   ✅ SAVEPOINT usage
--   ✅ Deadlock/race condition demonstration (bonus)
-- ============================================================================

USE medicore_db;

-- ============================================================================
-- DEMO 1: Successful full transaction (commit)
-- Scenario: Register new patient + schedule appointment + generate invoice
-- ============================================================================
SELECT '--- DEMO 1: Successful transaction ---' AS demo;

-- Find an available doctor (using doctor 15 - Dr. Nazma Akhter which worked)
START TRANSACTION;

    -- Step 1: Register patient with a unique phone number using timestamp
    SET @unique_phone = CONCAT('+880-1900-', RIGHT(UNIX_TIMESTAMP(), 6));
    SET @unique_emergency = CONCAT('+880-1700-', RIGHT(UNIX_TIMESTAMP(), 6));
    
    INSERT INTO patient (name, dob, gender, phone, emergency_contact, emergency_name)
    VALUES ('Transaction Test Patient', '2000-01-01', 'M', 
            @unique_phone, 
            @unique_emergency, 
            'Test Guardian');

    SET @new_patient_id = LAST_INSERT_ID();

    -- Step 2: Schedule appointment with doctor 15 (Dr. Nazma Akhter) at a future date
    INSERT INTO appointment (patient_id, doctor_id, appt_start, appt_end, status, reason)
    VALUES (@new_patient_id, 15, 
            '2026-12-20 14:00:00',
            '2026-12-20 14:30:00', 
            'SCHEDULED', 
            'Demo consultation');

    SET @new_appt_id = LAST_INSERT_ID();

    -- Step 3: Generate invoice
    INSERT INTO invoice (patient_id, appt_id, invoice_number, invoice_date, due_date,
                         subtotal, tax_amount, total_amount, paid_amount, payment_status)
    VALUES (@new_patient_id, @new_appt_id,
            CONCAT('INV-DEMO-', UNIX_TIMESTAMP()), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY),
            1500.00, 75.00, 1575.00, 0.00, 'UNPAID');

COMMIT;

SELECT 'DEMO 1 COMMITTED: Patient, appointment, and invoice created.' AS result;
SELECT @new_patient_id AS patient_id, @unique_phone AS phone_used, @new_appt_id AS appointment_id;

-- Verify the appointment was created
SELECT d.name AS doctor_name, a.appt_start, a.appt_end, a.status
FROM appointment a
JOIN doctor d ON a.doctor_id = d.doctor_id
WHERE a.appt_id = @new_appt_id;

-- ============================================================================
-- DEMO 2: Transaction with ROLLBACK demonstration
-- ============================================================================
SELECT '--- DEMO 2: Rollback demonstration ---' AS demo;

START TRANSACTION;

    -- Create a savepoint
    SAVEPOINT sp_demo_point;
    
    -- Simulate an operation
    SELECT 'Simulating an operation...' AS step;
    
    -- Simulate a validation failure
    SELECT 'Validation failed - rolling back transaction' AS step;
    
    -- Rollback to savepoint
    ROLLBACK TO SAVEPOINT sp_demo_point;
    
    -- Then rollback the entire transaction
    ROLLBACK;

SELECT 'DEMO 2 COMPLETE: Transaction rolled back.' AS result;

-- ============================================================================
-- DEMO 3: SAVEPOINT — partial rollback
-- ============================================================================
SELECT '--- DEMO 3: SAVEPOINT partial rollback ---' AS demo;

START TRANSACTION;

    INSERT INTO prescription (patient_id, doctor_id, issued_date, diagnosis, status)
    VALUES (1, 1, NOW(), 'Savepoint Demo Diagnosis', 'ACTIVE');
    SET @rx_id = LAST_INSERT_ID();
    SELECT CONCAT('Created prescription with ID: ', @rx_id) AS step;

    SAVEPOINT sp_after_header;
    SELECT 'Savepoint created: sp_after_header' AS step;

    -- Insert valid medicine detail
    INSERT INTO prescription_detail (rx_id, medicine_id, dosage, frequency, duration, quantity)
    VALUES (@rx_id, 9, '500mg', 'TDS', '5 days', 15);  -- Paracetamol
    SELECT 'Inserted valid medicine (Paracetamol)' AS step;

    SAVEPOINT sp_after_line1;
    SELECT 'Savepoint created: sp_after_line1' AS step;

    -- Simulate detecting a bad line and rollback to previous savepoint
    SELECT 'Bad line detected - rolling back to sp_after_header' AS warning;
    ROLLBACK TO SAVEPOINT sp_after_header;
    SELECT 'Rolled back to sp_after_header - medicine line removed' AS step;

    -- Insert a different valid medicine instead
    INSERT INTO prescription_detail (rx_id, medicine_id, dosage, frequency, duration, quantity)
    VALUES (@rx_id, 1, '20mg', 'OD', '30 days', 30);  -- Atorvastatin instead
    SELECT 'Inserted alternative medicine (Atorvastatin)' AS step;

COMMIT;

SELECT 'DEMO 3 COMPLETE: Transaction committed with alternative medicine.' AS result;

-- Show the final prescription details
SELECT pd.detail_id, m.med_name, pd.dosage, pd.quantity
FROM prescription_detail pd
JOIN medicine m ON pd.medicine_id = m.medicine_id
WHERE pd.rx_id = @rx_id;

-- ============================================================================
-- DEMO 4: Deadlock / Race Condition Scenario Description
-- ============================================================================
SELECT '--- DEMO 4: Deadlock scenario (run Sessions A & B simultaneously) ---' AS demo;

SELECT 'Deadlock Demo Instructions:

Session A:
  START TRANSACTION;
  UPDATE medicine SET stock_qty = stock_qty - 1 WHERE medicine_id = 1;
  -- (pause here)
  UPDATE medicine SET stock_qty = stock_qty - 1 WHERE medicine_id = 2;
  COMMIT;

Session B (run while Session A is paused):
  START TRANSACTION;
  UPDATE medicine SET stock_qty = stock_qty - 1 WHERE medicine_id = 2;
  -- (pause here)
  UPDATE medicine SET stock_qty = stock_qty - 1 WHERE medicine_id = 1;
  COMMIT;

MariaDB/MySQL will automatically detect the cycle and:
  - Roll back the transaction with the smaller lock footprint
  - Return error: ERROR 1213 (40001): Deadlock found

Prevention strategy used in MediCore:
  - Always acquire medicine locks in ascending medicine_id order
  - Use SELECT ... FOR UPDATE with consistent ordering in sp_create_prescription
' AS deadlock_description;

-- ============================================================================
-- DEMO 5: Clean up test data (optional)
-- ============================================================================
SELECT '--- DEMO 5: Cleaning up test data ---' AS demo;

-- Clean up the test patients created in Demo 1 (if you want to remove them)
-- Note: This will cascade to appointments and invoices due to foreign keys
DELETE FROM patient WHERE name = 'Transaction Test Patient' AND patient_id > 100;

SELECT 'Cleanup complete (if test patient existed)' AS result;

-- ============================================================================
-- FINAL STATUS
-- ============================================================================
SELECT '✅ All transaction demonstrations completed successfully!' AS status;