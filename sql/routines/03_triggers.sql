-- ============================================================================
-- MediCore HMS - Triggers
-- Week 8 + Week 13 Combined Final Submission
-- Total: 5 triggers (exceeds minimum of 3)
--
-- Week 8:
--   trg_before_appointment_overlap   (BEFORE INSERT)
--
-- Week 13:
--   trg_after_appointment_status     (AFTER UPDATE  - audit log)
--   trg_before_medicine_stock        (BEFORE UPDATE - stock guard)
--   trg_after_patient_update         (AFTER UPDATE  - patient history)
--   trg_after_payment_insert         (AFTER INSERT  - multi-row / complex)
--
-- Coverage:
--   ✅ At least one BEFORE trigger
--   ✅ At least one AFTER trigger
--   ✅ At least one multi-row/complex trigger
--   ✅ Audit table updated via triggers
-- ============================================================================

USE medicore_db;

DELIMITER $$

-- ============================================================================
-- TRIGGER 1: trg_before_appointment_overlap (Week 8)
-- BEFORE INSERT on appointment
-- Prevents double-booking a doctor and validates time rules
-- ============================================================================
DROP TRIGGER IF EXISTS trg_before_appointment_overlap$$

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

-- ============================================================================
-- TRIGGER 2: trg_after_appointment_status (Week 13)
-- AFTER UPDATE on appointment
-- Writes to audit_log whenever appointment status changes
-- ============================================================================
DROP TRIGGER IF EXISTS trg_after_appointment_status$$

CREATE TRIGGER trg_after_appointment_status
AFTER UPDATE ON appointment
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by)
        VALUES (
            'appointment',
            NEW.appt_id,
            'UPDATE',
            JSON_OBJECT(
                'status',     OLD.status,
                'appt_start', OLD.appt_start,
                'appt_end',   OLD.appt_end
            ),
            JSON_OBJECT(
                'status',     NEW.status,
                'appt_start', NEW.appt_start,
                'appt_end',   NEW.appt_end
            ),
            'SYSTEM'
        );
    END IF;
END$$

-- ============================================================================
-- TRIGGER 3: trg_before_medicine_stock (Week 13)
-- BEFORE UPDATE on medicine
-- Prevents stock from going negative (safety guard)
-- ============================================================================
DROP TRIGGER IF EXISTS trg_before_medicine_stock$$

CREATE TRIGGER trg_before_medicine_stock
BEFORE UPDATE ON medicine
FOR EACH ROW
BEGIN
    -- Prevent negative stock
    IF NEW.stock_qty < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot reduce stock below zero';
    END IF;

    -- Warn if stock drops to or below reorder level (log to audit)
    IF NEW.stock_qty <= NEW.reorder_level AND OLD.stock_qty > OLD.reorder_level THEN
        INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by)
        VALUES (
            'medicine',
            NEW.medicine_id,
            'UPDATE',
            JSON_OBJECT('stock_qty', OLD.stock_qty, 'status', 'ABOVE_REORDER'),
            JSON_OBJECT('stock_qty', NEW.stock_qty, 'status', 'BELOW_REORDER_ALERT'),
            'SYSTEM_STOCK_CHECK'
        );
    END IF;
END$$

-- ============================================================================
-- TRIGGER 4: trg_after_patient_update (Week 13)
-- AFTER UPDATE on patient
-- Logs every field change to patient_history (multi-column check)
-- Demonstrates: complex/multi-row audit trigger
-- ============================================================================
DROP TRIGGER IF EXISTS trg_after_patient_update$$

CREATE TRIGGER trg_after_patient_update
AFTER UPDATE ON patient
FOR EACH ROW
BEGIN
    -- Track phone changes
    IF OLD.phone != NEW.phone THEN
        INSERT INTO patient_history (patient_id, field_changed, old_value, new_value, change_description, changed_by)
        VALUES (NEW.patient_id, 'phone', OLD.phone, NEW.phone, 'Phone number updated', 'SYSTEM');
    END IF;

    -- Track address changes
    IF IFNULL(OLD.address,'') != IFNULL(NEW.address,'') THEN
        INSERT INTO patient_history (patient_id, field_changed, old_value, new_value, change_description, changed_by)
        VALUES (NEW.patient_id, 'address', OLD.address, NEW.address, 'Address updated', 'SYSTEM');
    END IF;

    -- Track insurance changes
    IF IFNULL(OLD.insurance_provider,'') != IFNULL(NEW.insurance_provider,'') THEN
        INSERT INTO patient_history (patient_id, field_changed, old_value, new_value, change_description, changed_by)
        VALUES (NEW.patient_id, 'insurance_provider', OLD.insurance_provider, NEW.insurance_provider,
                'Insurance provider updated', 'SYSTEM');
    END IF;

    -- Track email changes
    IF IFNULL(OLD.email,'') != IFNULL(NEW.email,'') THEN
        INSERT INTO patient_history (patient_id, field_changed, old_value, new_value, change_description, changed_by)
        VALUES (NEW.patient_id, 'email', OLD.email, NEW.email, 'Email updated', 'SYSTEM');
    END IF;

    -- Always write to the general audit log
    INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by)
    VALUES (
        'patient',
        NEW.patient_id,
        'UPDATE',
        JSON_OBJECT('name', OLD.name, 'phone', OLD.phone, 'email', OLD.email),
        JSON_OBJECT('name', NEW.name, 'phone', NEW.phone, 'email', NEW.email),
        'SYSTEM'
    );
END$$

-- ============================================================================
-- TRIGGER 5: trg_after_payment_insert (Week 13)
-- AFTER INSERT on payment
-- COMPLEX / MULTI-STEP trigger:
--   1. Updates invoice paid_amount & payment_status
--   2. Writes to audit_log
--   3. If invoice is now PAID, marks appointment as COMPLETED
-- ============================================================================
DROP TRIGGER IF EXISTS trg_after_payment_insert$$

CREATE TRIGGER trg_after_payment_insert
AFTER INSERT ON payment
FOR EACH ROW
BEGIN
    DECLARE v_total_amount   DECIMAL(12, 2);
    DECLARE v_new_paid       DECIMAL(12, 2);
    DECLARE v_new_status     VARCHAR(20);
    DECLARE v_appt_id        INT;

    -- Get current invoice totals
    SELECT total_amount, paid_amount + NEW.amount, appt_id
    INTO   v_total_amount, v_new_paid, v_appt_id
    FROM   invoice
    WHERE  invoice_id = NEW.invoice_id;

    -- Determine new payment status
    IF v_new_paid >= v_total_amount THEN
        SET v_new_status = 'PAID';
    ELSE
        SET v_new_status = 'PARTIALLY_PAID';
    END IF;

    -- Update invoice
    UPDATE invoice
    SET paid_amount    = v_new_paid,
        payment_status = v_new_status,
        updated_at     = NOW()
    WHERE invoice_id = NEW.invoice_id;

    -- If fully paid and linked to appointment, mark appointment COMPLETED
    IF v_new_status = 'PAID' AND v_appt_id IS NOT NULL THEN
        UPDATE appointment
        SET status     = 'COMPLETED',
            updated_at = NOW()
        WHERE appt_id = v_appt_id
          AND status NOT IN ('COMPLETED', 'CANCELLED');
    END IF;

    -- Audit log entry
    INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by)
    VALUES (
        'payment',
        NEW.payment_id,
        'INSERT',
        NULL,
        JSON_OBJECT(
            'invoice_id',     NEW.invoice_id,
            'amount',         NEW.amount,
            'method',         NEW.payment_method,
            'invoice_status', v_new_status
        ),
        IFNULL(NEW.received_by, 'SYSTEM')
    );
END$$

DELIMITER ;

SELECT '✅ All 5 triggers created successfully!' AS status;

-- Verify
SHOW TRIGGERS FROM medicore_db;
