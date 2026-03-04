-- ============================================================================
-- MediCore HMS - Stored Functions
-- Week 8 + Week 13 Combined Final Submission
-- Total: 6 functions (exceeds minimum of 3)
--   fn_patient_age              (Week 8)
--   fn_invoice_balance          (Week 13)
--   fn_bed_availability         (Week 13)
--   fn_doctor_schedule          (Week 13)
--   fn_medicine_stock_status    (Week 13)
--   fn_patient_total_bill       (Week 13)
-- ============================================================================

USE medicore_db;

DELIMITER $$

-- ============================================================================
-- fn_patient_age (Week 8)
-- Returns the age of a patient in whole years
-- ============================================================================
DROP FUNCTION IF EXISTS fn_patient_age$$

CREATE FUNCTION fn_patient_age(p_dob DATE)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_age INT;
    SET v_age = TIMESTAMPDIFF(YEAR, p_dob, CURDATE());
    RETURN v_age;
END$$

-- ============================================================================
-- fn_invoice_balance (Week 13)
-- Returns outstanding balance for a given invoice
-- ============================================================================
DROP FUNCTION IF EXISTS fn_invoice_balance$$

CREATE FUNCTION fn_invoice_balance(p_invoice_id INT)
RETURNS DECIMAL(12, 2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_balance DECIMAL(12, 2);

    SELECT (total_amount - paid_amount) INTO v_balance
    FROM invoice
    WHERE invoice_id = p_invoice_id;

    RETURN COALESCE(v_balance, 0.00);
END$$

-- ============================================================================
-- fn_bed_availability (Week 13)
-- Returns count of available beds for a given room type
-- ============================================================================
DROP FUNCTION IF EXISTS fn_bed_availability$$

CREATE FUNCTION fn_bed_availability(p_room_type VARCHAR(50))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_available_count INT;

    SELECT COUNT(*) INTO v_available_count
    FROM bed b
    JOIN room r ON b.room_id = r.room_id
    WHERE r.room_type = p_room_type
      AND b.status = 'AVAILABLE';

    RETURN COALESCE(v_available_count, 0);
END$$

-- ============================================================================
-- fn_doctor_schedule (Week 13)
-- Returns 'BUSY' or 'AVAILABLE' for a doctor at a given datetime
-- ============================================================================
DROP FUNCTION IF EXISTS fn_doctor_schedule$$

CREATE FUNCTION fn_doctor_schedule(
    p_doctor_id  INT,
    p_check_time DATETIME
)
RETURNS VARCHAR(20)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_count INT;

    SELECT COUNT(*) INTO v_count
    FROM appointment
    WHERE doctor_id = p_doctor_id
      AND status IN ('SCHEDULED', 'CONFIRMED', 'IN_PROGRESS')
      AND p_check_time >= appt_start
      AND p_check_time < appt_end;

    IF v_count > 0 THEN
        RETURN 'BUSY';
    ELSE
        RETURN 'AVAILABLE';
    END IF;
END$$

-- ============================================================================
-- fn_medicine_stock_status (Week 13)
-- Returns stock status label for a medicine
-- ============================================================================
DROP FUNCTION IF EXISTS fn_medicine_stock_status$$

CREATE FUNCTION fn_medicine_stock_status(p_medicine_id INT)
RETURNS VARCHAR(20)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_stock_qty    INT;
    DECLARE v_reorder_level INT;

    SELECT stock_qty, reorder_level
    INTO   v_stock_qty, v_reorder_level
    FROM   medicine
    WHERE  medicine_id = p_medicine_id;

    IF v_stock_qty = 0 THEN
        RETURN 'OUT_OF_STOCK';
    ELSEIF v_stock_qty <= v_reorder_level THEN
        RETURN 'LOW';
    ELSEIF v_stock_qty > v_reorder_level * 2 THEN
        RETURN 'ADEQUATE';
    ELSE
        RETURN 'NORMAL';
    END IF;
END$$

-- ============================================================================
-- fn_patient_total_bill (Week 13)
-- Returns total outstanding amount owed by a patient across all invoices
-- ============================================================================
DROP FUNCTION IF EXISTS fn_patient_total_bill$$

CREATE FUNCTION fn_patient_total_bill(p_patient_id INT)
RETURNS DECIMAL(12, 2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_outstanding DECIMAL(12, 2);

    SELECT SUM(total_amount - paid_amount)
    INTO   v_total_outstanding
    FROM   invoice
    WHERE  patient_id    = p_patient_id
      AND  payment_status IN ('UNPAID', 'PARTIALLY_PAID');

    RETURN COALESCE(v_total_outstanding, 0.00);
END$$

DELIMITER ;

SELECT '✅ All 6 stored functions created successfully!' AS status;

-- Quick smoke tests
SELECT fn_patient_age('1990-05-15')         AS test_patient_age;
SELECT fn_bed_availability('ICU')           AS icu_beds_available;
SELECT fn_medicine_stock_status(1)          AS medicine_1_stock_status;
SELECT fn_patient_total_bill(5)             AS patient_5_outstanding;
