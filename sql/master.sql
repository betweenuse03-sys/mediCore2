-- ============================================================================
-- MediCore HMS (Hospital Management System)
-- MASTER SETUP SCRIPT
-- Week 8 + Week 13 Combined Final Submission
-- ============================================================================
-- Compatible with: MariaDB 10.4+ / MySQL 8.0+
-- Execution Time: ~2–3 minutes
--
-- To run in XAMPP / phpMyAdmin:
--   1. Open phpMyAdmin → SQL tab
--   2. Paste this file OR use Import → select this file
--
-- To run from command line:
--   mysql -u root -p < master.sql
--
-- Folder structure used by this script:
--   schemas/01_create_database.sql
--   schemas/02_create_tables.sql
--   schemas/03_constraints_and_fk.sql
--   schemas/04_indexes.sql
--   schemas/05_views.sql
--   schemas/06_roles_access.sql
--   routines/01_functions.sql
--   routines/02_procedures.sql
--   routines/03_triggers.sql
--   data/01_seed_data.sql
--   data/02_extended_data.sql
--   queries/01_all_queries.sql
--   queries/02_transactions_demo.sql
--
-- This master file replicates the full content inline for single-file execution.
-- ============================================================================

SET @OLD_UNIQUE_CHECKS     = @@UNIQUE_CHECKS,     UNIQUE_CHECKS     = 0;
SET @OLD_FOREIGN_KEY_CHECKS= @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS= 0;
SET @OLD_SQL_MODE           = @@SQL_MODE, SQL_MODE = 
    'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

SELECT '============================================================' AS '';
SELECT '  MediCore HMS — Complete Database Setup'                     AS '';
SELECT '  Week 8 + Week 13 Final Submission'                          AS '';
SELECT '============================================================' AS '';

-- ============================================================================
-- STEP 1: Create Database
-- ============================================================================
SELECT '[1/10] Creating database...' AS step;

DROP DATABASE IF EXISTS medicore_db;
CREATE DATABASE medicore_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medicore_db;
SET time_zone = '+06:00';

SELECT '✓ Database created' AS status;

-- ============================================================================
-- STEP 2: Create Tables (17 tables)
-- ============================================================================
SELECT '[2/10] Creating tables...' AS step;

-- Normalization note: All tables are 3NF.
-- Denormalization: invoice.balance_due is GENERATED ALWAYS for performance.

CREATE TABLE IF NOT EXISTS department (
    dept_id     INT AUTO_INCREMENT PRIMARY KEY,
    dept_name   VARCHAR(100) NOT NULL,
    dept_head   VARCHAR(100),
    phone       VARCHAR(20),
    email       VARCHAR(100),
    location    VARCHAR(100),
    floor_no    INT,
    description TEXT,
    budget      DECIMAL(12,2),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dept_name     (dept_name),
    INDEX idx_dept_location (location)
) ENGINE=InnoDB COMMENT='Hospital departments';

CREATE TABLE IF NOT EXISTS doctor (
    doctor_id        INT AUTO_INCREMENT PRIMARY KEY,
    dept_id          INT,
    name             VARCHAR(100) NOT NULL,
    specialization   VARCHAR(100),
    qualification    VARCHAR(200),
    phone            VARCHAR(20),
    email            VARCHAR(100),
    license_no       VARCHAR(50),
    room_no          VARCHAR(20),
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    experience_years INT DEFAULT 0,
    status           ENUM('ACTIVE','ON_LEAVE','INACTIVE','RETIRED') DEFAULT 'ACTIVE',
    joining_date     DATE,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_doctor_name          (name),
    INDEX idx_doctor_status        (status),
    INDEX idx_doctor_dept          (dept_id),
    INDEX idx_doctor_specialization(specialization)
) ENGINE=InnoDB COMMENT='Doctor information';

CREATE TABLE IF NOT EXISTS patient (
    patient_id          INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    dob                 DATE NOT NULL,
    gender              ENUM('M','F','OTHER') NOT NULL,
    blood_group         VARCHAR(5),
    address             TEXT,
    city                VARCHAR(100),
    state               VARCHAR(100),
    postal_code         VARCHAR(20),
    country             VARCHAR(100) DEFAULT 'Bangladesh',
    phone               VARCHAR(20) NOT NULL,
    email               VARCHAR(100),
    emergency_contact   VARCHAR(20),
    emergency_name      VARCHAR(100),
    emergency_relation  VARCHAR(50),
    occupation          VARCHAR(100),
    marital_status      ENUM('SINGLE','MARRIED','DIVORCED','WIDOWED'),
    insurance_provider  VARCHAR(100),
    insurance_policy_no VARCHAR(50),
    allergies           TEXT,
    medical_notes       TEXT,
    registration_date   DATE DEFAULT (CURRENT_DATE),
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_name        (name),
    INDEX idx_patient_phone       (phone),
    INDEX idx_patient_dob         (dob),
    INDEX idx_patient_city        (city),
    INDEX idx_patient_registration(registration_date)
) ENGINE=InnoDB COMMENT='Patient demographics';

CREATE TABLE IF NOT EXISTS staff (
    staff_id      INT AUTO_INCREMENT PRIMARY KEY,
    dept_id       INT,
    name          VARCHAR(100) NOT NULL,
    role          VARCHAR(100) NOT NULL,
    qualification VARCHAR(200),
    phone         VARCHAR(20),
    email         VARCHAR(100),
    address       TEXT,
    date_of_birth DATE,
    joining_date  DATE,
    salary        DECIMAL(10,2),
    shift_type    ENUM('MORNING','EVENING','NIGHT','ROTATING') DEFAULT 'MORNING',
    status        ENUM('ACTIVE','ON_LEAVE','INACTIVE') DEFAULT 'ACTIVE',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_name  (name),
    INDEX idx_staff_role  (role),
    INDEX idx_staff_dept  (dept_id),
    INDEX idx_staff_status(status)
) ENGINE=InnoDB COMMENT='Hospital staff';

CREATE TABLE IF NOT EXISTS appointment (
    appt_id          INT AUTO_INCREMENT PRIMARY KEY,
    patient_id       INT NOT NULL,
    doctor_id        INT NOT NULL,
    appt_start       DATETIME NOT NULL,
    appt_end         DATETIME NOT NULL,
    status           ENUM('SCHEDULED','CONFIRMED','IN_PROGRESS','COMPLETED','CANCELLED','NO_SHOW') DEFAULT 'SCHEDULED',
    reason           TEXT,
    symptoms         TEXT,
    appointment_type ENUM('CONSULTATION','FOLLOW_UP','EMERGENCY','ROUTINE_CHECKUP') DEFAULT 'CONSULTATION',
    notes            TEXT,
    cancelled_reason TEXT,
    cancelled_by     VARCHAR(100),
    reminder_sent    BOOLEAN DEFAULT FALSE,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_appt_doctor_date(doctor_id, appt_start),
    INDEX idx_appt_patient    (patient_id),
    INDEX idx_appt_status     (status),
    INDEX idx_appt_date       (appt_start),
    INDEX idx_appt_type       (appointment_type)
) ENGINE=InnoDB COMMENT='Appointments';

CREATE TABLE IF NOT EXISTS encounter (
    encounter_id         INT AUTO_INCREMENT PRIMARY KEY,
    patient_id           INT NOT NULL,
    doctor_id            INT NOT NULL,
    appt_id              INT,
    encounter_date       DATETIME NOT NULL,
    encounter_type       ENUM('OUTPATIENT','INPATIENT','EMERGENCY','FOLLOWUP') DEFAULT 'OUTPATIENT',
    chief_complaint      TEXT,
    diagnosis            TEXT,
    treatment_plan       TEXT,
    vital_signs          JSON,
    physical_examination TEXT,
    doctor_notes         TEXT,
    follow_up_required   BOOLEAN DEFAULT FALSE,
    follow_up_date       DATE,
    status               ENUM('ACTIVE','CLOSED') DEFAULT 'ACTIVE',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_encounter_patient(patient_id),
    INDEX idx_encounter_doctor (doctor_id),
    INDEX idx_encounter_date   (encounter_date),
    INDEX idx_encounter_type   (encounter_type)
) ENGINE=InnoDB COMMENT='Medical encounters (JSON vital_signs: advanced feature)';

CREATE TABLE IF NOT EXISTS medicine (
    medicine_id        INT AUTO_INCREMENT PRIMARY KEY,
    med_name           VARCHAR(200) NOT NULL,
    generic_name       VARCHAR(200),
    brand_name         VARCHAR(200),
    category           VARCHAR(100),
    manufacturer       VARCHAR(150),
    form               ENUM('TABLET','CAPSULE','SYRUP','INJECTION','CREAM','DROPS','INHALER') DEFAULT 'TABLET',
    strength           VARCHAR(50),
    unit_price         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_qty          INT NOT NULL DEFAULT 0,
    reorder_level      INT DEFAULT 50,
    max_stock_level    INT DEFAULT 1000,
    expiry_date        DATE,
    batch_no           VARCHAR(50),
    supplier           VARCHAR(150),
    description        TEXT,
    side_effects       TEXT,
    contraindications  TEXT,
    storage_conditions VARCHAR(200),
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_med_name    (med_name),
    INDEX idx_med_generic (generic_name),
    INDEX idx_med_category(category),
    INDEX idx_med_stock   (stock_qty),
    INDEX idx_med_expiry  (expiry_date)
) ENGINE=InnoDB COMMENT='Medicine inventory';

CREATE TABLE IF NOT EXISTS prescription (
    rx_id          INT AUTO_INCREMENT PRIMARY KEY,
    patient_id     INT NOT NULL,
    doctor_id      INT NOT NULL,
    appt_id        INT,
    encounter_id   INT,
    issued_date    DATETIME DEFAULT CURRENT_TIMESTAMP,
    diagnosis      TEXT,
    instructions   TEXT,
    follow_up_days INT,
    status         ENUM('ACTIVE','DISPENSED','PARTIALLY_DISPENSED','CANCELLED','EXPIRED') DEFAULT 'ACTIVE',
    valid_until    DATE,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rx_patient(patient_id),
    INDEX idx_rx_doctor (doctor_id),
    INDEX idx_rx_date   (issued_date),
    INDEX idx_rx_status (status)
) ENGINE=InnoDB COMMENT='Prescriptions';

CREATE TABLE IF NOT EXISTS prescription_detail (
    detail_id     INT AUTO_INCREMENT PRIMARY KEY,
    rx_id         INT NOT NULL,
    medicine_id   INT NOT NULL,
    dosage        VARCHAR(100) NOT NULL,
    frequency     VARCHAR(100),
    duration      VARCHAR(50),
    quantity      INT NOT NULL,
    instructions  TEXT,
    dispensed_qty INT DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_detail_rx      (rx_id),
    INDEX idx_detail_medicine(medicine_id)
) ENGINE=InnoDB COMMENT='Prescription line items';

CREATE TABLE IF NOT EXISTS lab_order (
    order_id              INT AUTO_INCREMENT PRIMARY KEY,
    patient_id            INT NOT NULL,
    doctor_id             INT NOT NULL,
    encounter_id          INT,
    test_name             VARCHAR(200) NOT NULL,
    test_type             VARCHAR(100),
    test_category         VARCHAR(100),
    priority              ENUM('ROUTINE','URGENT','STAT') DEFAULT 'ROUTINE',
    order_date            DATETIME DEFAULT CURRENT_TIMESTAMP,
    sample_collected_date DATETIME,
    sample_type           VARCHAR(100),
    clinical_notes        TEXT,
    status                ENUM('ORDERED','SAMPLE_COLLECTED','IN_PROGRESS','COMPLETED','CANCELLED') DEFAULT 'ORDERED',
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_lab_patient(patient_id),
    INDEX idx_lab_doctor (doctor_id),
    INDEX idx_lab_status (status),
    INDEX idx_lab_date   (order_date)
) ENGINE=InnoDB COMMENT='Lab orders';

CREATE TABLE IF NOT EXISTS lab_result (
    result_id     INT AUTO_INCREMENT PRIMARY KEY,
    order_id      INT NOT NULL,
    test_parameter VARCHAR(200),
    result_value  VARCHAR(500),
    unit          VARCHAR(50),
    normal_range  VARCHAR(100),
    abnormal_flag ENUM('NORMAL','HIGH','LOW','CRITICAL') DEFAULT 'NORMAL',
    result_date   DATETIME DEFAULT CURRENT_TIMESTAMP,
    performed_by  VARCHAR(100),
    verified_by   VARCHAR(100),
    comments      TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_result_order(order_id),
    INDEX idx_result_date (result_date)
) ENGINE=InnoDB COMMENT='Lab results';

CREATE TABLE IF NOT EXISTS room (
    room_id      INT AUTO_INCREMENT PRIMARY KEY,
    room_number  VARCHAR(20) NOT NULL,
    ward         VARCHAR(50),
    room_type    ENUM('GENERAL','SEMI_PRIVATE','PRIVATE','ICU','CCU','NICU','EMERGENCY','OPERATION_THEATRE') DEFAULT 'GENERAL',
    floor_no     INT,
    dept_id      INT,
    bed_capacity INT DEFAULT 1,
    daily_rate   DECIMAL(10,2) DEFAULT 0.00,
    amenities    TEXT,
    status       ENUM('AVAILABLE','OCCUPIED','MAINTENANCE','RESERVED') DEFAULT 'AVAILABLE',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_type  (room_type),
    INDEX idx_room_ward  (ward),
    INDEX idx_room_floor (floor_no),
    INDEX idx_room_status(status)
) ENGINE=InnoDB COMMENT='Rooms';

CREATE TABLE IF NOT EXISTS bed (
    bed_id             INT AUTO_INCREMENT PRIMARY KEY,
    room_id            INT NOT NULL,
    bed_number         VARCHAR(10) NOT NULL,
    bed_type           ENUM('STANDARD','ELECTRIC','ICU','PEDIATRIC') DEFAULT 'STANDARD',
    status             ENUM('AVAILABLE','OCCUPIED','MAINTENANCE','RESERVED','CLEANING') DEFAULT 'AVAILABLE',
    current_patient_id INT,
    admission_date     DATETIME,
    expected_discharge DATE,
    notes              TEXT,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bed_status (status),
    INDEX idx_bed_room   (room_id),
    INDEX idx_bed_patient(current_patient_id)
) ENGINE=InnoDB COMMENT='Beds';

CREATE TABLE IF NOT EXISTS invoice (
    invoice_id      INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT NOT NULL,
    appt_id         INT,
    encounter_id    INT,
    invoice_number  VARCHAR(50) NOT NULL,
    invoice_date    DATE NOT NULL,
    due_date        DATE,
    subtotal        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount      DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid_amount     DECIMAL(12,2) DEFAULT 0.00,
    balance_due     DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED,
    payment_status  ENUM('UNPAID','PARTIALLY_PAID','PAID','OVERDUE','CANCELLED') DEFAULT 'UNPAID',
    billing_address TEXT,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice_patient(patient_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_invoice_date   (invoice_date),
    INDEX idx_invoice_status (payment_status)
) ENGINE=InnoDB COMMENT='Invoices';

CREATE TABLE IF NOT EXISTS payment (
    payment_id       INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id       INT NOT NULL,
    patient_id       INT NOT NULL,
    payment_date     DATETIME DEFAULT CURRENT_TIMESTAMP,
    amount           DECIMAL(10,2) NOT NULL,
    payment_method   ENUM('CASH','CARD','CHEQUE','BANK_TRANSFER','INSURANCE','MOBILE_PAYMENT') DEFAULT 'CASH',
    transaction_id   VARCHAR(100),
    reference_number VARCHAR(100),
    payment_notes    TEXT,
    received_by      VARCHAR(100),
    status           ENUM('PENDING','COMPLETED','FAILED','REFUNDED') DEFAULT 'COMPLETED',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_invoice(invoice_id),
    INDEX idx_payment_patient(patient_id),
    INDEX idx_payment_date   (payment_date),
    INDEX idx_payment_method (payment_method)
) ENGINE=InnoDB COMMENT='Payments';

CREATE TABLE IF NOT EXISTS audit_log (
    log_id           INT AUTO_INCREMENT PRIMARY KEY,
    table_name       VARCHAR(100) NOT NULL,
    record_id        INT,
    action           ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    old_values       JSON,
    new_values       JSON,
    changed_by       VARCHAR(100),
    ip_address       VARCHAR(45),
    user_agent       VARCHAR(255),
    change_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_table    (table_name),
    INDEX idx_audit_action   (action),
    INDEX idx_audit_timestamp(change_timestamp),
    INDEX idx_audit_user     (changed_by)
) ENGINE=InnoDB COMMENT='Audit trail — updated by triggers';

CREATE TABLE IF NOT EXISTS patient_history (
    history_id         INT AUTO_INCREMENT PRIMARY KEY,
    patient_id         INT NOT NULL,
    field_changed      VARCHAR(100),
    old_value          TEXT,
    new_value          TEXT,
    change_description TEXT,
    changed_by         VARCHAR(100),
    changed_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_history_patient(patient_id),
    INDEX idx_history_date   (changed_at)
) ENGINE=InnoDB COMMENT='Patient record change history';

SELECT '✓ All 17 tables created' AS status;

-- ============================================================================
-- STEP 3: Constraints & Foreign Keys
-- ============================================================================
SELECT '[3/10] Adding constraints and foreign keys...' AS step;

ALTER TABLE department  ADD CONSTRAINT uq_dept_name     UNIQUE (dept_name);
ALTER TABLE doctor      ADD CONSTRAINT uq_doctor_email  UNIQUE (email);
ALTER TABLE patient     ADD CONSTRAINT uq_patient_phone UNIQUE (phone);
ALTER TABLE room        ADD CONSTRAINT uq_room_number   UNIQUE (room_number);
ALTER TABLE bed         ADD CONSTRAINT uq_bed_room      UNIQUE (room_id, bed_number);
ALTER TABLE medicine    ADD CONSTRAINT uq_medicine_name UNIQUE (med_name);
ALTER TABLE invoice     ADD CONSTRAINT uq_invoice_number UNIQUE (invoice_number);

ALTER TABLE patient     ADD CONSTRAINT chk_patient_dob    CHECK (dob <= CURDATE());
ALTER TABLE appointment ADD CONSTRAINT chk_appt_time       CHECK (appt_end > appt_start);
ALTER TABLE medicine    ADD CONSTRAINT chk_medicine_price  CHECK (unit_price >= 0);
ALTER TABLE medicine    ADD CONSTRAINT chk_medicine_stock  CHECK (stock_qty >= 0);
ALTER TABLE invoice     ADD CONSTRAINT chk_invoice_amounts CHECK (paid_amount >= 0 AND total_amount >= 0);
ALTER TABLE payment     ADD CONSTRAINT chk_payment_positive CHECK (amount > 0);

ALTER TABLE doctor           ADD CONSTRAINT fk_doctor_dept   FOREIGN KEY (dept_id) REFERENCES department(dept_id) ON DELETE SET NULL;
ALTER TABLE staff            ADD CONSTRAINT fk_staff_dept    FOREIGN KEY (dept_id) REFERENCES department(dept_id) ON DELETE SET NULL;
ALTER TABLE appointment      ADD CONSTRAINT fk_appt_patient  FOREIGN KEY (patient_id) REFERENCES patient(patient_id);
ALTER TABLE appointment      ADD CONSTRAINT fk_appt_doctor   FOREIGN KEY (doctor_id)  REFERENCES doctor(doctor_id);
ALTER TABLE encounter        ADD CONSTRAINT fk_enc_patient   FOREIGN KEY (patient_id) REFERENCES patient(patient_id);
ALTER TABLE encounter        ADD CONSTRAINT fk_enc_doctor    FOREIGN KEY (doctor_id)  REFERENCES doctor(doctor_id);
ALTER TABLE encounter        ADD CONSTRAINT fk_enc_appt      FOREIGN KEY (appt_id)    REFERENCES appointment(appt_id) ON DELETE SET NULL;
ALTER TABLE prescription     ADD CONSTRAINT fk_rx_patient    FOREIGN KEY (patient_id)  REFERENCES patient(patient_id);
ALTER TABLE prescription     ADD CONSTRAINT fk_rx_doctor     FOREIGN KEY (doctor_id)   REFERENCES doctor(doctor_id);
ALTER TABLE prescription     ADD CONSTRAINT fk_rx_appt       FOREIGN KEY (appt_id)     REFERENCES appointment(appt_id) ON DELETE SET NULL;
ALTER TABLE prescription     ADD CONSTRAINT fk_rx_encounter  FOREIGN KEY (encounter_id) REFERENCES encounter(encounter_id) ON DELETE SET NULL;
ALTER TABLE prescription_detail ADD CONSTRAINT fk_detail_rx  FOREIGN KEY (rx_id)       REFERENCES prescription(rx_id) ON DELETE CASCADE;
ALTER TABLE prescription_detail ADD CONSTRAINT fk_detail_med FOREIGN KEY (medicine_id) REFERENCES medicine(medicine_id);
ALTER TABLE lab_order        ADD CONSTRAINT fk_lab_patient   FOREIGN KEY (patient_id)  REFERENCES patient(patient_id);
ALTER TABLE lab_order        ADD CONSTRAINT fk_lab_doctor    FOREIGN KEY (doctor_id)   REFERENCES doctor(doctor_id);
ALTER TABLE lab_order        ADD CONSTRAINT fk_lab_encounter FOREIGN KEY (encounter_id) REFERENCES encounter(encounter_id) ON DELETE SET NULL;
ALTER TABLE lab_result       ADD CONSTRAINT fk_result_order  FOREIGN KEY (order_id)    REFERENCES lab_order(order_id) ON DELETE CASCADE;
ALTER TABLE room             ADD CONSTRAINT fk_room_dept     FOREIGN KEY (dept_id)     REFERENCES department(dept_id) ON DELETE SET NULL;
ALTER TABLE bed              ADD CONSTRAINT fk_bed_room      FOREIGN KEY (room_id)     REFERENCES room(room_id);
ALTER TABLE bed              ADD CONSTRAINT fk_bed_patient   FOREIGN KEY (current_patient_id) REFERENCES patient(patient_id) ON DELETE SET NULL;
ALTER TABLE invoice          ADD CONSTRAINT fk_inv_patient   FOREIGN KEY (patient_id)  REFERENCES patient(patient_id);
ALTER TABLE invoice          ADD CONSTRAINT fk_inv_appt      FOREIGN KEY (appt_id)     REFERENCES appointment(appt_id) ON DELETE SET NULL;
ALTER TABLE payment          ADD CONSTRAINT fk_pay_invoice   FOREIGN KEY (invoice_id)  REFERENCES invoice(invoice_id);
ALTER TABLE payment          ADD CONSTRAINT fk_pay_patient   FOREIGN KEY (patient_id)  REFERENCES patient(patient_id);
ALTER TABLE patient_history  ADD CONSTRAINT fk_hist_patient  FOREIGN KEY (patient_id)  REFERENCES patient(patient_id) ON DELETE CASCADE;

SELECT '✓ Constraints and foreign keys added' AS status;

-- ============================================================================
-- STEP 4: Indexing Strategies
-- ============================================================================
SELECT '[4/10] Applying indexing strategies...' AS step;

-- Strategy 1: Composite covering indexes
CREATE INDEX idx_appt_doctor_start_status ON appointment (doctor_id, appt_start, status);
CREATE INDEX idx_invoice_patient_status   ON invoice     (patient_id, payment_status, invoice_date);
CREATE INDEX idx_rx_doctor_patient        ON prescription (doctor_id, patient_id, issued_date);
CREATE INDEX idx_lab_priority_status      ON lab_order   (priority, status, order_date);
CREATE INDEX idx_med_stock_reorder        ON medicine    (stock_qty, reorder_level, expiry_date);

-- Strategy 2: Functional expression indexes
CREATE INDEX idx_appt_date_only    ON appointment ((DATE(appt_start)));
CREATE INDEX idx_invoice_yearmonth ON invoice     ((DATE_FORMAT(invoice_date, '%Y-%m')));

-- Full-text search
ALTER TABLE patient  ADD FULLTEXT INDEX ft_patient_name  (name);
ALTER TABLE medicine ADD FULLTEXT INDEX ft_medicine_name (med_name, generic_name);

SELECT '✓ Indexing strategies applied (2 strategies, 9 indexes)' AS status;

-- ============================================================================
-- STEP 5: Stored Functions (6 functions)
-- ============================================================================
SELECT '[5/10] Creating stored functions...' AS step;

DELIMITER $$

DROP FUNCTION IF EXISTS fn_patient_age$$
CREATE FUNCTION fn_patient_age(p_dob DATE)
RETURNS INT DETERMINISTIC READS SQL DATA
BEGIN
    RETURN TIMESTAMPDIFF(YEAR, p_dob, CURDATE());
END$$

DROP FUNCTION IF EXISTS fn_invoice_balance$$
CREATE FUNCTION fn_invoice_balance(p_invoice_id INT)
RETURNS DECIMAL(12,2) DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE v DECIMAL(12,2);
    SELECT (total_amount - paid_amount) INTO v FROM invoice WHERE invoice_id = p_invoice_id;
    RETURN COALESCE(v, 0.00);
END$$

DROP FUNCTION IF EXISTS fn_bed_availability$$
CREATE FUNCTION fn_bed_availability(p_room_type VARCHAR(50))
RETURNS INT DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE v INT;
    SELECT COUNT(*) INTO v FROM bed b JOIN room r ON b.room_id = r.room_id
    WHERE r.room_type = p_room_type AND b.status = 'AVAILABLE';
    RETURN COALESCE(v, 0);
END$$

DROP FUNCTION IF EXISTS fn_doctor_schedule$$
CREATE FUNCTION fn_doctor_schedule(p_doctor_id INT, p_check_time DATETIME)
RETURNS VARCHAR(20) DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE v INT;
    SELECT COUNT(*) INTO v FROM appointment
    WHERE doctor_id = p_doctor_id AND status IN ('SCHEDULED','CONFIRMED','IN_PROGRESS')
      AND p_check_time >= appt_start AND p_check_time < appt_end;
    RETURN IF(v > 0, 'BUSY', 'AVAILABLE');
END$$

DROP FUNCTION IF EXISTS fn_medicine_stock_status$$
CREATE FUNCTION fn_medicine_stock_status(p_medicine_id INT)
RETURNS VARCHAR(20) DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE s INT; DECLARE r INT;
    SELECT stock_qty, reorder_level INTO s, r FROM medicine WHERE medicine_id = p_medicine_id;
    IF s = 0           THEN RETURN 'OUT_OF_STOCK'; END IF;
    IF s <= r          THEN RETURN 'LOW';          END IF;
    IF s > r * 2       THEN RETURN 'ADEQUATE';     END IF;
    RETURN 'NORMAL';
END$$

DROP FUNCTION IF EXISTS fn_patient_total_bill$$
CREATE FUNCTION fn_patient_total_bill(p_patient_id INT)
RETURNS DECIMAL(12,2) DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE v DECIMAL(12,2);
    SELECT SUM(total_amount - paid_amount) INTO v FROM invoice
    WHERE patient_id = p_patient_id AND payment_status IN ('UNPAID','PARTIALLY_PAID');
    RETURN COALESCE(v, 0.00);
END$$

DELIMITER ;
SELECT '✓ 6 stored functions created' AS status;

-- ============================================================================
-- STEP 6: Stored Procedures (9 procedures)
-- ============================================================================
SELECT '[6/10] Creating stored procedures...' AS step;

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_schedule_appointment$$
CREATE PROCEDURE sp_schedule_appointment(
    IN p_patient_id INT, IN p_doctor_id INT,
    IN p_start_time DATETIME, IN p_end_time DATETIME, IN p_reason TEXT)
BEGIN
    DECLARE v_px INT DEFAULT 0; DECLARE v_dx INT DEFAULT 0;
    DECLARE v_ds VARCHAR(20);   DECLARE v_ov INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; SELECT 'ERROR' AS message; END;
    START TRANSACTION;
    SELECT COUNT(*) INTO v_px FROM patient WHERE patient_id = p_patient_id;
    IF v_px = 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient not found'; END IF;
    SELECT COUNT(*), IFNULL(MAX(status),'X') INTO v_dx, v_ds FROM doctor WHERE doctor_id = p_doctor_id;
    IF v_dx = 0            THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doctor not found'; END IF;
    IF v_ds != 'ACTIVE'    THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doctor not active'; END IF;
    IF p_end_time <= p_start_time THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'End must be after start'; END IF;
    IF p_start_time < NOW()       THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot schedule in past'; END IF;
    SELECT COUNT(*) INTO v_ov FROM appointment
    WHERE doctor_id = p_doctor_id AND status = 'SCHEDULED'
      AND ((p_start_time >= appt_start AND p_start_time < appt_end)
        OR (p_end_time > appt_start AND p_end_time <= appt_end)
        OR (p_start_time <= appt_start AND p_end_time >= appt_end));
    IF v_ov > 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doctor already booked at this time'; END IF;
    INSERT INTO appointment (patient_id, doctor_id, appt_start, appt_end, status, reason)
    VALUES (p_patient_id, p_doctor_id, p_start_time, p_end_time, 'SCHEDULED', p_reason);
    COMMIT;
    SELECT LAST_INSERT_ID() AS appointment_id, 'SUCCESS: Appointment scheduled' AS message;
END$$

DROP PROCEDURE IF EXISTS sp_register_patient$$
CREATE PROCEDURE sp_register_patient(
    IN p_name VARCHAR(100), IN p_dob DATE, IN p_gender ENUM('M','F','OTHER'),
    IN p_blood_group VARCHAR(5), IN p_address TEXT, IN p_phone VARCHAR(20),
    IN p_email VARCHAR(100), IN p_emergency_contact VARCHAR(20), IN p_emergency_name VARCHAR(100))
BEGIN
    DECLARE v_id INT; DECLARE v_age INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; SELECT 'ERROR: Registration failed' AS message; END;
    START TRANSACTION;
    SET v_age = TIMESTAMPDIFF(YEAR, p_dob, CURDATE());
    IF v_age < 0 OR v_age > 150 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid DOB'; END IF;
    INSERT INTO patient (name, dob, gender, blood_group, address, phone, email, emergency_contact, emergency_name)
    VALUES (p_name, p_dob, p_gender, p_blood_group, p_address, p_phone, p_email, p_emergency_contact, p_emergency_name);
    SET v_id = LAST_INSERT_ID(); COMMIT;
    SELECT v_id AS patient_id, 'SUCCESS: Patient registered' AS message;
END$$

DROP PROCEDURE IF EXISTS sp_generate_invoice$$
CREATE PROCEDURE sp_generate_invoice(
    IN p_patient_id INT, IN p_appt_id INT,
    IN p_consultation_fee DECIMAL(10,2), IN p_additional_charges DECIMAL(10,2))
BEGIN
    DECLARE v_inv_id INT; DECLARE v_num VARCHAR(50);
    DECLARE v_total DECIMAL(12,2); DECLARE v_tax DECIMAL(10,2);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; SELECT 'ERROR: Invoice failed' AS message; END;
    START TRANSACTION;
    SET v_num = CONCAT('INV-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND()*10000),4,'0'));
    SET v_tax   = (p_consultation_fee + p_additional_charges) * 0.05;
    SET v_total = p_consultation_fee + p_additional_charges + v_tax;
    INSERT INTO invoice (patient_id, appt_id, invoice_number, invoice_date, due_date,
                         subtotal, tax_amount, total_amount, paid_amount, payment_status)
    VALUES (p_patient_id, p_appt_id, v_num, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY),
            p_consultation_fee + p_additional_charges, v_tax, v_total, 0.00, 'UNPAID');
    SET v_inv_id = LAST_INSERT_ID(); COMMIT;
    SELECT v_inv_id AS invoice_id, v_num AS invoice_number, v_total AS total_amount, 'SUCCESS' AS message;
END$$

DROP PROCEDURE IF EXISTS sp_process_payment$$
CREATE PROCEDURE sp_process_payment(
    IN p_invoice_id INT, IN p_amount DECIMAL(10,2),
    IN p_payment_method VARCHAR(50), IN p_transaction_id VARCHAR(100))
BEGIN
    DECLARE v_pat INT; DECLARE v_tot DECIMAL(12,2); DECLARE v_paid DECIMAL(12,2);
    DECLARE v_bal DECIMAL(12,2); DECLARE v_stat VARCHAR(20);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; SELECT 'ERROR: Payment failed' AS message; END;
    START TRANSACTION;
    SELECT patient_id, total_amount, paid_amount INTO v_pat, v_tot, v_paid FROM invoice WHERE invoice_id = p_invoice_id;
    IF p_amount <= 0                     THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Amount must be positive'; END IF;
    IF (v_paid + p_amount) > v_tot       THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Exceeds invoice total'; END IF;
    INSERT INTO payment (invoice_id, patient_id, amount, payment_method, transaction_id, status)
    VALUES (p_invoice_id, v_pat, p_amount, p_payment_method, p_transaction_id, 'COMPLETED');
    SET v_bal  = v_tot - (v_paid + p_amount);
    SET v_stat = IF(v_bal = 0, 'PAID', 'PARTIALLY_PAID');
    UPDATE invoice SET paid_amount = paid_amount + p_amount, payment_status = v_stat, updated_at = NOW() WHERE invoice_id = p_invoice_id;
    COMMIT;
    SELECT LAST_INSERT_ID() AS payment_id, v_bal AS remaining_balance, v_stat AS status, 'SUCCESS' AS message;
END$$

DROP PROCEDURE IF EXISTS sp_admit_patient$$
CREATE PROCEDURE sp_admit_patient(IN p_patient_id INT, IN p_doctor_id INT, IN p_room_type VARCHAR(50), IN p_diagnosis TEXT)
BEGIN
    DECLARE v_bid INT; DECLARE v_rid INT; DECLARE v_eid INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; SELECT 'ERROR: Admission failed' AS message; END;
    START TRANSACTION;
    SELECT b.bed_id, b.room_id INTO v_bid, v_rid FROM bed b JOIN room r ON b.room_id = r.room_id
    WHERE r.room_type = p_room_type AND b.status = 'AVAILABLE' LIMIT 1;
    IF v_bid IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No available beds'; END IF;
    INSERT INTO encounter (patient_id, doctor_id, encounter_date, encounter_type, diagnosis, status)
    VALUES (p_patient_id, p_doctor_id, NOW(), 'INPATIENT', p_diagnosis, 'ACTIVE');
    SET v_eid = LAST_INSERT_ID();
    UPDATE bed SET status='OCCUPIED', current_patient_id=p_patient_id, admission_date=NOW(), updated_at=NOW() WHERE bed_id=v_bid;
    COMMIT;
    SELECT v_eid AS encounter_id, v_bid AS bed_id, v_rid AS room_id, 'SUCCESS: Admitted' AS message;
END$$

DROP PROCEDURE IF EXISTS sp_discharge_patient$$
CREATE PROCEDURE sp_discharge_patient(IN p_patient_id INT, IN p_encounter_id INT, IN p_discharge_notes TEXT)
BEGIN
    DECLARE v_bid INT; DECLARE v_rid INT; DECLARE v_days INT; DECLARE v_rate DECIMAL(10,2); DECLARE v_charges DECIMAL(12,2);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; SELECT 'ERROR: Discharge failed' AS message; END;
    START TRANSACTION;
    SELECT b.bed_id, b.room_id, r.daily_rate, GREATEST(DATEDIFF(NOW(),b.admission_date),1)
    INTO v_bid, v_rid, v_rate, v_days
    FROM bed b JOIN room r ON b.room_id=r.room_id WHERE b.current_patient_id=p_patient_id AND b.status='OCCUPIED' LIMIT 1;
    IF v_bid IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient not admitted'; END IF;
    SET v_charges = v_rate * v_days;
    UPDATE encounter SET status='CLOSED', doctor_notes=CONCAT(COALESCE(doctor_notes,''),'\nDischarge: ',p_discharge_notes), updated_at=NOW() WHERE encounter_id=p_encounter_id;
    UPDATE bed SET status='CLEANING', current_patient_id=NULL, admission_date=NULL, updated_at=NOW() WHERE bed_id=v_bid;
    UPDATE room SET status='AVAILABLE' WHERE room_id=v_rid;
    CALL sp_generate_invoice(p_patient_id, NULL, v_charges, 0.00);
    COMMIT;
    SELECT v_days AS days_stayed, v_charges AS room_charges, 'SUCCESS: Discharged' AS message;
END$$

DROP PROCEDURE IF EXISTS sp_create_prescription$$
CREATE PROCEDURE sp_create_prescription(IN p_patient_id INT, IN p_doctor_id INT, IN p_encounter_id INT, IN p_diagnosis TEXT, IN p_medicines JSON)
BEGIN
    DECLARE v_rxid INT; DECLARE v_cnt INT; DECLARE v_idx INT DEFAULT 0;
    DECLARE v_mid INT; DECLARE v_dos VARCHAR(100); DECLARE v_frq VARCHAR(100);
    DECLARE v_dur VARCHAR(50); DECLARE v_qty INT; DECLARE v_stk INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; SELECT 'ERROR: Prescription failed' AS message; END;
    START TRANSACTION;
    INSERT INTO prescription (patient_id, doctor_id, encounter_id, issued_date, diagnosis, status)
    VALUES (p_patient_id, p_doctor_id, p_encounter_id, NOW(), p_diagnosis, 'ACTIVE');
    SET v_rxid = LAST_INSERT_ID(); SET v_cnt = JSON_LENGTH(p_medicines);
    WHILE v_idx < v_cnt DO
        SET v_mid = JSON_EXTRACT(p_medicines, CONCAT('$[',v_idx,'].medicine_id'));
        SET v_dos = JSON_UNQUOTE(JSON_EXTRACT(p_medicines, CONCAT('$[',v_idx,'].dosage')));
        SET v_frq = JSON_UNQUOTE(JSON_EXTRACT(p_medicines, CONCAT('$[',v_idx,'].frequency')));
        SET v_dur = JSON_UNQUOTE(JSON_EXTRACT(p_medicines, CONCAT('$[',v_idx,'].duration')));
        SET v_qty = JSON_EXTRACT(p_medicines, CONCAT('$[',v_idx,'].quantity'));
        SELECT stock_qty INTO v_stk FROM medicine WHERE medicine_id = v_mid;
        IF v_stk < v_qty THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock'; END IF;
        INSERT INTO prescription_detail (rx_id, medicine_id, dosage, frequency, duration, quantity) VALUES (v_rxid, v_mid, v_dos, v_frq, v_dur, v_qty);
        SET v_idx = v_idx + 1;
    END WHILE;
    COMMIT;
    SELECT v_rxid AS prescription_id, 'SUCCESS' AS message;
END$$

DROP PROCEDURE IF EXISTS sp_generate_report$$
CREATE PROCEDURE sp_generate_report(IN p_type VARCHAR(50), IN p_start DATE, IN p_end DATE)
BEGIN
    IF p_type = 'APPOINTMENTS' THEN
        SELECT a.appt_id, p.name AS patient, d.name AS doctor, dept.dept_name, a.appt_start, a.status
        FROM appointment a JOIN patient p ON a.patient_id=p.patient_id JOIN doctor d ON a.doctor_id=d.doctor_id
        JOIN department dept ON d.dept_id=dept.dept_id WHERE DATE(a.appt_start) BETWEEN p_start AND p_end ORDER BY a.appt_start DESC;
    ELSEIF p_type = 'REVENUE' THEN
        SELECT DATE(invoice_date) AS date, COUNT(*) AS invoices, SUM(total_amount) AS billed,
               SUM(paid_amount) AS collected, SUM(total_amount-paid_amount) AS outstanding
        FROM invoice WHERE invoice_date BETWEEN p_start AND p_end GROUP BY DATE(invoice_date) ORDER BY date DESC;
    ELSE SELECT 'Invalid type. Use: APPOINTMENTS, REVENUE' AS error; END IF;
END$$

DROP PROCEDURE IF EXISTS sp_dispense_medicine$$
CREATE PROCEDURE sp_dispense_medicine(IN p_rx_id INT)
BEGIN
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_did INT; DECLARE v_mid INT; DECLARE v_qty INT;
    DECLARE v_stk INT; DECLARE v_mname VARCHAR(200);
    DECLARE cur_d CURSOR FOR SELECT detail_id, medicine_id, quantity FROM prescription_detail WHERE rx_id = p_rx_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; SELECT 'ERROR: Dispensing failed' AS message; END;
    START TRANSACTION;
    IF (SELECT COUNT(*) FROM prescription WHERE rx_id = p_rx_id AND status = 'ACTIVE') = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Prescription not active';
    END IF;
    OPEN cur_d;
    lp: LOOP
        FETCH cur_d INTO v_did, v_mid, v_qty;
        IF v_done THEN LEAVE lp; END IF;
        SELECT stock_qty, med_name INTO v_stk, v_mname FROM medicine WHERE medicine_id = v_mid;
        IF v_stk < v_qty THEN CLOSE cur_d; SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = CONCAT('Insufficient stock: ', v_mname); END IF;
        UPDATE medicine SET stock_qty = stock_qty - v_qty, updated_at = NOW() WHERE medicine_id = v_mid;
        UPDATE prescription_detail SET dispensed_qty = v_qty WHERE detail_id = v_did;
    END LOOP;
    CLOSE cur_d;
    UPDATE prescription SET status = 'DISPENSED', updated_at = NOW() WHERE rx_id = p_rx_id;
    COMMIT;
    SELECT p_rx_id AS prescription_id, 'SUCCESS: Dispensed' AS message;
END$$

DELIMITER ;
SELECT '✓ 9 stored procedures created' AS status;

-- ============================================================================
-- STEP 7: Triggers (5 triggers)
-- ============================================================================
SELECT '[7/10] Creating triggers...' AS step;

DELIMITER $$

DROP TRIGGER IF EXISTS trg_before_appointment_overlap$$
CREATE TRIGGER trg_before_appointment_overlap
BEFORE INSERT ON appointment FOR EACH ROW
BEGIN
    DECLARE v_cnt INT DEFAULT 0; DECLARE v_doc VARCHAR(100); DECLARE v_ct DATETIME; DECLARE v_msg VARCHAR(500);
    IF NEW.status IN ('SCHEDULED','CONFIRMED') THEN
        IF NEW.appt_end <= NEW.appt_start THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'End time must be after start'; END IF;
        IF NEW.appt_start < NOW() THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot schedule in past'; END IF;
        SELECT COUNT(*), MAX(appt_start) INTO v_cnt, v_ct FROM appointment
        WHERE doctor_id = NEW.doctor_id AND status IN ('SCHEDULED','CONFIRMED')
          AND ((NEW.appt_start >= appt_start AND NEW.appt_start < appt_end)
            OR (NEW.appt_end > appt_start AND NEW.appt_end <= appt_end)
            OR (NEW.appt_start <= appt_start AND NEW.appt_end >= appt_end));
        IF v_cnt > 0 THEN
            SELECT name INTO v_doc FROM doctor WHERE doctor_id = NEW.doctor_id;
            SET v_msg = CONCAT('OVERLAP: Dr. ', IFNULL(v_doc,'?'), ' already booked at ', DATE_FORMAT(v_ct,'%Y-%m-%d %H:%i'));
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
        END IF;
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_after_appointment_status$$
CREATE TRIGGER trg_after_appointment_status
AFTER UPDATE ON appointment FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by)
        VALUES ('appointment', NEW.appt_id, 'UPDATE',
                JSON_OBJECT('status', OLD.status), JSON_OBJECT('status', NEW.status), 'SYSTEM');
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_before_medicine_stock$$
CREATE TRIGGER trg_before_medicine_stock
BEFORE UPDATE ON medicine FOR EACH ROW
BEGIN
    IF NEW.stock_qty < 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock cannot go negative'; END IF;
    IF NEW.stock_qty <= NEW.reorder_level AND OLD.stock_qty > OLD.reorder_level THEN
        INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by)
        VALUES ('medicine', NEW.medicine_id, 'UPDATE',
                JSON_OBJECT('stock_qty', OLD.stock_qty), JSON_OBJECT('stock_qty', NEW.stock_qty, 'alert', 'REORDER_NEEDED'), 'SYSTEM');
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_after_patient_update$$
CREATE TRIGGER trg_after_patient_update
AFTER UPDATE ON patient FOR EACH ROW
BEGIN
    IF OLD.phone != NEW.phone THEN
        INSERT INTO patient_history (patient_id, field_changed, old_value, new_value, change_description, changed_by)
        VALUES (NEW.patient_id, 'phone', OLD.phone, NEW.phone, 'Phone updated', 'SYSTEM');
    END IF;
    IF IFNULL(OLD.email,'') != IFNULL(NEW.email,'') THEN
        INSERT INTO patient_history (patient_id, field_changed, old_value, new_value, change_description, changed_by)
        VALUES (NEW.patient_id, 'email', OLD.email, NEW.email, 'Email updated', 'SYSTEM');
    END IF;
    INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by)
    VALUES ('patient', NEW.patient_id, 'UPDATE',
            JSON_OBJECT('phone', OLD.phone, 'email', OLD.email),
            JSON_OBJECT('phone', NEW.phone, 'email', NEW.email), 'SYSTEM');
END$$

DROP TRIGGER IF EXISTS trg_after_payment_insert$$
CREATE TRIGGER trg_after_payment_insert
AFTER INSERT ON payment FOR EACH ROW
BEGIN
    DECLARE v_tot DECIMAL(12,2); DECLARE v_np DECIMAL(12,2); DECLARE v_stat VARCHAR(20); DECLARE v_aid INT;
    SELECT total_amount, paid_amount + NEW.amount, appt_id INTO v_tot, v_np, v_aid FROM invoice WHERE invoice_id = NEW.invoice_id;
    SET v_stat = IF(v_np >= v_tot, 'PAID', 'PARTIALLY_PAID');
    UPDATE invoice SET paid_amount = v_np, payment_status = v_stat, updated_at = NOW() WHERE invoice_id = NEW.invoice_id;
    IF v_stat = 'PAID' AND v_aid IS NOT NULL THEN
        UPDATE appointment SET status='COMPLETED', updated_at=NOW() WHERE appt_id=v_aid AND status NOT IN ('COMPLETED','CANCELLED');
    END IF;
    INSERT INTO audit_log (table_name, record_id, action, new_values, changed_by)
    VALUES ('payment', NEW.payment_id, 'INSERT',
            JSON_OBJECT('invoice_id', NEW.invoice_id, 'amount', NEW.amount, 'invoice_status', v_stat),
            IFNULL(NEW.received_by, 'SYSTEM'));
END$$

DELIMITER ;
SELECT '✓ 5 triggers created (BEFORE + AFTER + complex)' AS status;

-- ============================================================================
-- STEP 8: Views (7 views)
-- ============================================================================
SELECT '[8/10] Creating views...' AS step;

CREATE OR REPLACE VIEW vw_upcoming_appointments AS
SELECT a.appt_id, a.appt_start, a.appt_end, a.status, a.appointment_type,
       p.patient_id, p.name AS patient_name, p.phone AS patient_phone,
       d.doctor_id, d.name AS doctor_name, d.specialization, dept.dept_name, a.reason,
       fn_patient_age(p.dob) AS patient_age,
       TIMESTAMPDIFF(MINUTE, NOW(), a.appt_start) AS minutes_until_appointment
FROM appointment a
JOIN patient p ON a.patient_id=p.patient_id JOIN doctor d ON a.doctor_id=d.doctor_id
LEFT JOIN department dept ON d.dept_id=dept.dept_id
WHERE a.appt_start >= CURDATE() AND a.status IN ('SCHEDULED','CONFIRMED') ORDER BY a.appt_start;

CREATE OR REPLACE VIEW vw_revenue_summary AS
SELECT DATE_FORMAT(i.invoice_date,'%Y-%m') AS month, COUNT(DISTINCT i.invoice_id) AS total_invoices,
       SUM(i.total_amount) AS total_billed, SUM(i.paid_amount) AS total_collected,
       SUM(i.total_amount - i.paid_amount) AS total_outstanding,
       ROUND(SUM(i.paid_amount)/SUM(i.total_amount)*100,2) AS collection_pct,
       COUNT(DISTINCT i.patient_id) AS unique_patients
FROM invoice i GROUP BY DATE_FORMAT(i.invoice_date,'%Y-%m') ORDER BY month DESC;

CREATE OR REPLACE VIEW vw_doctor_workload AS
SELECT d.doctor_id, d.name AS doctor_name, d.specialization, dept.dept_name,
       COUNT(a.appt_id) AS total_appointments,
       SUM(CASE WHEN a.status='COMPLETED' THEN 1 ELSE 0 END) AS completed,
       SUM(CASE WHEN a.status='CANCELLED' THEN 1 ELSE 0 END) AS cancelled,
       SUM(CASE WHEN a.status='NO_SHOW'   THEN 1 ELSE 0 END) AS no_shows,
       ROUND(SUM(CASE WHEN a.status='COMPLETED' THEN 1 ELSE 0 END)/NULLIF(COUNT(a.appt_id),0)*100,2) AS completion_rate
FROM doctor d LEFT JOIN department dept ON d.dept_id=dept.dept_id LEFT JOIN appointment a ON d.doctor_id=a.doctor_id
GROUP BY d.doctor_id, d.name, d.specialization, dept.dept_name, d.status ORDER BY total_appointments DESC;

CREATE OR REPLACE VIEW vw_bed_occupancy AS
SELECT r.room_type, r.ward, COUNT(b.bed_id) AS total_beds,
       SUM(CASE WHEN b.status='AVAILABLE' THEN 1 ELSE 0 END) AS available_beds,
       SUM(CASE WHEN b.status='OCCUPIED'  THEN 1 ELSE 0 END) AS occupied_beds,
       ROUND(SUM(CASE WHEN b.status='OCCUPIED' THEN 1 ELSE 0 END)/COUNT(b.bed_id)*100,2) AS occupancy_pct,
       r.daily_rate
FROM room r LEFT JOIN bed b ON r.room_id=b.room_id GROUP BY r.room_type, r.ward, r.daily_rate;

CREATE OR REPLACE VIEW vw_medicine_inventory AS
SELECT m.medicine_id, m.med_name, m.generic_name, m.category, m.form,
       m.stock_qty, m.reorder_level, m.unit_price, m.stock_qty*m.unit_price AS stock_value,
       fn_medicine_stock_status(m.medicine_id) AS stock_status,
       m.expiry_date, DATEDIFF(m.expiry_date,CURDATE()) AS days_until_expiry,
       CASE WHEN m.expiry_date<=CURDATE() THEN 'EXPIRED' WHEN DATEDIFF(m.expiry_date,CURDATE())<=30 THEN 'EXPIRING_SOON' ELSE 'VALID' END AS expiry_status
FROM medicine m;

CREATE OR REPLACE VIEW vw_patient_summary AS
SELECT p.patient_id, p.name, fn_patient_age(p.dob) AS age, p.gender, p.blood_group, p.phone, p.email, p.city,
       p.registration_date, COUNT(DISTINCT a.appt_id) AS total_appointments,
       COUNT(DISTINCT CASE WHEN a.status='COMPLETED' THEN a.appt_id END) AS completed_appointments,
       MAX(a.appt_start) AS last_visit_date, COUNT(DISTINCT rx.rx_id) AS total_prescriptions,
       fn_patient_total_bill(p.patient_id) AS outstanding_balance
FROM patient p LEFT JOIN appointment a ON p.patient_id=a.patient_id LEFT JOIN prescription rx ON p.patient_id=rx.patient_id
GROUP BY p.patient_id, p.name, p.dob, p.gender, p.blood_group, p.phone, p.email, p.city, p.registration_date;

CREATE OR REPLACE VIEW vw_daily_statistics AS
SELECT CURDATE() AS report_date,
       (SELECT COUNT(*) FROM appointment  WHERE DATE(appt_start)=CURDATE())                              AS today_appointments,
       (SELECT COUNT(*) FROM appointment  WHERE DATE(appt_start)=CURDATE() AND status='COMPLETED')       AS completed_today,
       (SELECT COUNT(*) FROM patient      WHERE DATE(registration_date)=CURDATE())                       AS new_patients_today,
       (SELECT COUNT(*) FROM bed          WHERE status='OCCUPIED')                                       AS current_bed_occupancy,
       (SELECT COUNT(*) FROM bed          WHERE status='AVAILABLE')                                      AS beds_available,
       (SELECT SUM(total_amount) FROM invoice WHERE DATE(invoice_date)=CURDATE())                        AS revenue_today,
       (SELECT SUM(amount)       FROM payment WHERE DATE(payment_date)=CURDATE())                        AS collections_today;

SELECT '✓ 7 views created' AS status;

-- ============================================================================
-- STEP 9: Seed Data
-- ============================================================================
SELECT '[9/10] Inserting seed data...' AS step;

INSERT INTO department (dept_name, dept_head, phone, location) VALUES
('Cardiology',       'Dr. Sarah Ahmed',    '+880-1711-111111', 'Building A, Floor 3'),
('Neurology',        'Dr. Rahman Khan',    '+880-1711-222222', 'Building A, Floor 4'),
('Orthopedics',      'Dr. Farhana Begum',  '+880-1711-333333', 'Building B, Floor 2'),
('Pediatrics',       'Dr. Mahmud Hasan',   '+880-1711-444444', 'Building C, Floor 1'),
('General Surgery',  'Dr. Kamal Uddin',    '+880-1711-555555', 'Building B, Floor 3'),
('Emergency Medicine','Dr. Nasrin Akter',  '+880-1711-666666', 'Building A, Ground Floor'),
('Radiology',        'Dr. Imran Sheikh',   '+880-1711-777777', 'Building C, Floor 2'),
('Pathology',        'Dr. Sabina Rahman',  '+880-1711-888888', 'Building C, Basement');

INSERT INTO doctor (dept_id, name, specialization, qualification, phone, email, room_no, status) VALUES
(1,'Dr. Ahmed Rahim',     'Interventional Cardiology','MBBS, MD (Cardiology)',        '+880-1811-111111','ahmed.rahim@hospital.com','A301','ACTIVE'),
(1,'Dr. Fatima Khan',     'Cardiac Electrophysiology','MBBS, MD, FACC',               '+880-1811-111112','fatima.khan@hospital.com','A302','ACTIVE'),
(2,'Dr. Habib Mahmud',    'Stroke Specialist',         'MBBS, MD (Neurology)',         '+880-1811-222221','habib.mahmud@hospital.com','A401','ACTIVE'),
(2,'Dr. Nusrat Jahan',    'Epilepsy Specialist',       'MBBS, MD, PhD',                '+880-1811-222222','nusrat.jahan@hospital.com','A402','ACTIVE'),
(3,'Dr. Karim Hossain',   'Joint Replacement',         'MBBS, MS (Ortho)',             '+880-1811-333331','karim.hossain@hospital.com','B201','ACTIVE'),
(3,'Dr. Shamima Begum',   'Spine Surgery',             'MBBS, MS, FRCS',               '+880-1811-333332','shamima.begum@hospital.com','B202','ACTIVE'),
(4,'Dr. Iqbal Ahmed',     'Neonatology',               'MBBS, DCH, MD',                '+880-1811-444441','iqbal.ahmed@hospital.com','C101','ACTIVE'),
(4,'Dr. Taslima Nasrin',  'Pediatric Cardiology',      'MBBS, MD (Peds), FAAP',        '+880-1811-444442','taslima.nasrin@hospital.com','C102','ACTIVE'),
(5,'Dr. Rafiq Uddin',     'Laparoscopic Surgery',      'MBBS, MS (Surgery)',           '+880-1811-555551','rafiq.uddin@hospital.com','B301','ACTIVE'),
(5,'Dr. Monira Khatun',   'Trauma Surgery',            'MBBS, MS, FACS',               '+880-1811-555552','monira.khatun@hospital.com','B302','ON_LEAVE'),
(6,'Dr. Salman Khan',     'Emergency Physician',       'MBBS, FCPS (EM)',              '+880-1811-666661','salman.khan@hospital.com','A001','ACTIVE'),
(6,'Dr. Rehana Parvin',   'Acute Care',                'MBBS, MD (EM)',                '+880-1811-666662','rehana.parvin@hospital.com','A002','ACTIVE'),
(7,'Dr. Tanvir Hasan',    'Interventional Radiology',  'MBBS, MD (Radiology)',         '+880-1811-777771','tanvir.hasan@hospital.com','C201','ACTIVE'),
(7,'Dr. Nazma Akhter',    'Diagnostic Imaging',        'MBBS, DMRD, FRCR',             '+880-1811-777772','nazma.akhter@hospital.com','C202','ACTIVE'),
(8,'Dr. Khalid Rahman',   'Clinical Pathology',        'MBBS, MD (Pathology)',         '+880-1811-888881','khalid.rahman@hospital.com','CB01','ACTIVE'),
(8,'Dr. Sultana Kamal',   'Hematopathology',           'MBBS, MD, FRCPath',            '+880-1811-888882','sultana.kamal@hospital.com','CB02','ACTIVE');

INSERT INTO patient (name, dob, gender, blood_group, address, phone, email, emergency_contact, emergency_name) VALUES
('Mohammad Ali',    '1985-03-15','M','A+', 'House 12, Dhanmondi',     '+880-1911-111111','mohammad.ali@email.com','+880-1711-111111','Fatima Ali'),
('Ayesha Rahman',   '1990-07-22','F','B+', 'Flat 3B, Gulshan-2',      '+880-1922-222222','ayesha.rahman@email.com','+880-1722-222222','Karim Rahman'),
('Jahangir Hossain','1978-11-30','M','O+', 'Sreepur, Gazipur',         '+880-1933-333333','jahangir.h@email.com','+880-1733-333333','Nasima Hossain'),
('Sumaiya Begum',   '2015-01-10','F','AB+','House 45, Mirpur-10',      '+880-1944-444444','sumaiya.parent@email.com','+880-1744-444444','Rafiq Begum'),
('Abdul Karim',     '1965-05-18','M','A-', 'Uttara Sector-7',          '+880-1955-555555','abdul.karim@email.com','+880-1755-555555','Halima Karim'),
('Tahmina Akter',   '1995-09-25','F','B-', 'Flat 5C, Banani',          '+880-1966-666666','tahmina.akter@email.com','+880-1766-666666','Shahin Akter'),
('Rahim Uddin',     '1982-12-08','M','O-', 'House 78, Mohammadpur',    '+880-1977-777777','rahim.uddin@email.com','+880-1777-777777','Salma Uddin'),
('Nadia Islam',     '2010-04-14','F','A+', 'Apartment 2A, Bashundhara','+880-1988-888888','nadia.parent@email.com','+880-1788-888888','Imran Islam'),
('Farhan Ahmed',    '1988-08-20','M','AB-','House 56, Lalmatia',        '+880-1999-999999','farhan.ahmed@email.com','+880-1799-999999','Sadia Ahmed'),
('Roxana Khatun',   '1992-02-28','F','B+', 'Kapasia, Gazipur',          '+880-1900-111111','roxana.khatun@email.com','+880-1700-111111','Mamun Khatun'),
('Shakib Hassan',   '2005-06-12','M','O+', 'Uttara Sector-3',           '+880-1900-222222','shakib.parent@email.com','+880-1700-222222','Nasrin Hassan'),
('Farzana Yasmin',  '1980-10-05','F','A-', 'House 23, Badda',           '+880-1900-333333','farzana.yasmin@email.com','+880-1700-333333','Harun Yasmin'),
('Tanvir Khan',     '1975-03-17','M','AB+','Holding 145, Rampura',      '+880-1900-444444','tanvir.khan@email.com','+880-1700-444444','Kulsum Khan'),
('Sabina Sultana',  '1998-11-22','F','B-', 'Apartment 4F, Motijheel',   '+880-1900-555555','sabina.sultana@email.com','+880-1700-555555','Jahir Sultana'),
('Mahbub Alam',     '1987-07-30','M','O-', 'House 89, Shyamoli',        '+880-1900-666666','mahbub.alam@email.com','+880-1700-666666','Rubina Alam');

INSERT INTO room (room_number, ward, room_type, floor_no, daily_rate) VALUES
('G101','General Ward A','GENERAL',1,1500),('G102','General Ward A','GENERAL',1,1500),
('G103','General Ward A','GENERAL',1,1500),('G201','General Ward B','GENERAL',2,1500),
('G202','General Ward B','GENERAL',2,1500),('SP301','Semi-Private Wing','SEMI_PRIVATE',3,3000),
('SP302','Semi-Private Wing','SEMI_PRIVATE',3,3000),('SP303','Semi-Private Wing','SEMI_PRIVATE',3,3000),
('P401','Private Wing','PRIVATE',4,5000),('P402','Private Wing','PRIVATE',4,5000),
('P403','Private Wing','PRIVATE',4,5000),('P404','Private Wing','PRIVATE',4,5000),
('ICU501','Intensive Care Unit','ICU',5,10000),('ICU502','Intensive Care Unit','ICU',5,10000),
('ICU503','Intensive Care Unit','ICU',5,10000),('ER001','Emergency Department','EMERGENCY',0,2000),
('ER002','Emergency Department','EMERGENCY',0,2000),('ER003','Emergency Department','EMERGENCY',0,2000);

INSERT INTO bed (room_id, bed_number, status) VALUES
(1,'A','AVAILABLE'),(1,'B','OCCUPIED'),(1,'C','AVAILABLE'),(1,'D','AVAILABLE'),
(2,'A','AVAILABLE'),(2,'B','AVAILABLE'),(2,'C','OCCUPIED'),(2,'D','AVAILABLE'),
(3,'A','OCCUPIED'),(3,'B','AVAILABLE'),(3,'C','AVAILABLE'),(3,'D','MAINTENANCE'),
(6,'A','AVAILABLE'),(6,'B','AVAILABLE'),(7,'A','OCCUPIED'),(7,'B','AVAILABLE'),
(9,'A','AVAILABLE'),(10,'A','OCCUPIED'),(11,'A','AVAILABLE'),(12,'A','RESERVED'),
(13,'A','OCCUPIED'),(14,'A','OCCUPIED'),(15,'A','AVAILABLE'),
(16,'A','AVAILABLE'),(17,'A','AVAILABLE'),(18,'A','OCCUPIED');

INSERT INTO medicine (med_name, generic_name, category, manufacturer, unit_price, stock_qty, reorder_level, expiry_date) VALUES
('Atorvastatin 20mg','Atorvastatin','Cardiovascular','Square Pharmaceuticals',5.50,500,100,'2027-12-31'),
('Amlodipine 5mg','Amlodipine','Cardiovascular','Beximco Pharma',3.20,750,150,'2027-06-30'),
('Aspirin 75mg','Acetylsalicylic Acid','Cardiovascular','Renata Limited',1.50,1000,200,'2028-03-15'),
('Amoxicillin 500mg','Amoxicillin','Antibiotic','Square Pharmaceuticals',6.00,600,120,'2026-11-30'),
('Azithromycin 500mg','Azithromycin','Antibiotic','Beximco Pharma',12.50,400,100,'2027-02-28'),
('Ciprofloxacin 500mg','Ciprofloxacin','Antibiotic','Renata Limited',7.80,350,80,'2027-07-15'),
('Paracetamol 500mg','Paracetamol','Analgesic','Square Pharmaceuticals',1.00,2000,400,'2028-06-30'),
('Ibuprofen 400mg','Ibuprofen','NSAID','Beximco Pharma',2.50,800,200,'2027-10-20'),
('Metformin 500mg','Metformin','Antidiabetic','Square Pharmaceuticals',2.80,900,180,'2028-01-31'),
('Omeprazole 20mg','Omeprazole','Proton Pump Inhibitor','Beximco Pharma',3.50,700,140,'2027-12-15'),
('Vitamin D3 60000IU','Cholecalciferol','Vitamin','Renata Limited',25.00,300,60,'2028-03-31'),
('Calcium + Vitamin D','Calcium Carbonate','Mineral Supplement','Square Pharmaceuticals',8.00,400,80,'2028-05-15'),
('Pregabalin 75mg','Pregabalin','Neuropathic Pain','Incepta Pharma',22.50,180,40,'2027-08-15'),
('Salbutamol Inhaler','Salbutamol','Bronchodilator','GSK Bangladesh',250.00,150,30,'2027-09-30'),
('Insulin Glargine 100IU/ml','Insulin Glargine','Antidiabetic','Novo Nordisk',850.00,50,15,'2026-12-31');

INSERT INTO appointment (patient_id, doctor_id, appt_start, appt_end, status, reason) VALUES
(1,1,'2026-01-15 09:00:00','2026-01-15 09:30:00','COMPLETED','Chest pain follow-up'),
(2,3,'2026-01-16 10:00:00','2026-01-16 10:30:00','COMPLETED','Migraine consultation'),
(3,5,'2026-01-17 14:00:00','2026-01-17 14:45:00','COMPLETED','Post-operative checkup'),
(5,2,'2026-01-19 15:00:00','2026-01-19 15:30:00','COMPLETED','Memory issues assessment'),
(7,6,'2026-01-25 10:30:00','2026-01-25 11:00:00','COMPLETED','Knee pain assessment'),
(8,8,'2026-01-25 14:00:00','2026-01-25 14:30:00','COMPLETED','Fever and cough'),
(9,9,'2026-01-26 11:00:00','2026-01-26 11:30:00','NO_SHOW','Hernia consultation'),
(10,1,'2026-01-26 15:00:00','2026-01-26 15:30:00','COMPLETED','Hypertension management'),
(1,2,'2026-02-10 09:00:00','2026-02-10 09:30:00','SCHEDULED','Neuro assessment'),
(2,6,'2026-02-11 10:00:00','2026-02-11 10:30:00','SCHEDULED','Arthritis follow-up'),
(4,7,'2026-02-12 13:00:00','2026-02-12 13:30:00','SCHEDULED','Asthma checkup'),
(5,1,'2026-02-13 14:00:00','2026-02-13 14:30:00','SCHEDULED','Cardiac stress test'),
(6,3,'2026-02-14 09:00:00','2026-02-14 09:30:00','SCHEDULED','Headache follow-up'),
(11,1,'2026-02-01 10:00:00','2026-02-01 10:30:00','CANCELLED','Annual checkup'),
(12,3,'2026-02-02 11:00:00','2026-02-02 11:30:00','CANCELLED','Neurology review');

INSERT INTO prescription (patient_id, doctor_id, appt_id, issued_date, diagnosis, status) VALUES
(1,1,1,'2026-01-15 09:25:00','Angina Pectoris','DISPENSED'),
(2,3,2,'2026-01-16 10:20:00','Chronic Migraine','DISPENSED'),
(3,5,3,'2026-01-17 14:40:00','Post-Appendectomy Recovery','DISPENSED'),
(5,2,4,'2026-01-19 15:25:00','Mild Cognitive Impairment','ACTIVE'),
(7,6,5,'2026-01-25 10:55:00','Osteoarthritis - Right Knee','DISPENSED'),
(10,1,8,'2026-01-26 15:25:00','Essential Hypertension','ACTIVE');

INSERT INTO encounter (patient_id, doctor_id, appt_id, encounter_date, encounter_type,
    chief_complaint, diagnosis, vital_signs, status) VALUES
(1,1,1,'2026-01-15 09:00:00','OUTPATIENT','Chest pain on exertion','Angina Pectoris - Stable',
 '{"bp":"140/90","hr":82,"temp":36.8,"spo2":98,"rr":16}','CLOSED'),
(5,2,4,'2026-01-19 15:00:00','OUTPATIENT','Forgetfulness','Mild Cognitive Impairment',
 '{"bp":"130/85","hr":70,"temp":36.7,"spo2":99,"rr":14}','CLOSED'),
(7,6,5,'2026-01-25 10:30:00','OUTPATIENT','Right knee pain','Osteoarthritis Grade II',
 '{"bp":"128/82","hr":78,"temp":36.5,"spo2":99,"rr":15}','CLOSED');

INSERT INTO invoice (patient_id, appt_id, invoice_number, invoice_date, due_date,
    subtotal, tax_amount, total_amount, paid_amount, payment_status) VALUES
(1,1,'INV-20260115-0001','2026-01-15','2026-02-14',1500,75,1575,1575,'PAID'),
(2,2,'INV-20260116-0002','2026-01-16','2026-02-15',1500,75,1575,1575,'PAID'),
(5,4,'INV-20260119-0004','2026-01-19','2026-02-18',2500,125,2625,1000,'PARTIALLY_PAID'),
(7,5,'INV-20260125-0005','2026-01-25','2026-02-24',1500,75,1575,0,'UNPAID');

INSERT INTO payment (invoice_id, patient_id, payment_date, amount, payment_method, received_by) VALUES
(1,1,'2026-01-15 10:00:00',1575,'CASH','Billing Desk 1'),
(2,2,'2026-01-16 11:00:00',1575,'MOBILE_PAYMENT','Billing Desk 2'),
(3,5,'2026-01-19 16:00:00',1000,'CASH','Billing Desk 3');

SELECT '✓ Seed data inserted' AS status;

-- ============================================================================
-- STEP 10: Verification
-- ============================================================================
SELECT '[10/10] Verifying setup...' AS step;

SELECT TABLE_NAME, TABLE_ROWS, TABLE_COMMENT
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'medicore_db' AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

SELECT ROUTINE_NAME AS name, ROUTINE_TYPE AS type
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = 'medicore_db'
ORDER BY ROUTINE_TYPE, ROUTINE_NAME;

SELECT TRIGGER_NAME, EVENT_MANIPULATION AS event, ACTION_TIMING AS timing
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = 'medicore_db'
ORDER BY TRIGGER_NAME;

SELECT TABLE_NAME AS view_name FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'medicore_db' ORDER BY TABLE_NAME;

-- Quick functional tests
SELECT fn_patient_age('1990-05-15')       AS test_fn_patient_age;
SELECT fn_bed_availability('ICU')         AS test_fn_bed_availability;
SELECT fn_medicine_stock_status(1)        AS test_fn_medicine_stock;
SELECT fn_patient_total_bill(5)           AS test_fn_patient_bill;
SELECT fn_doctor_schedule(1, NOW())       AS test_fn_doctor_schedule;

SET UNIQUE_CHECKS      = @OLD_UNIQUE_CHECKS;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET SQL_MODE           = @OLD_SQL_MODE;

SELECT '============================================================' AS '';
SELECT '  ✅ MediCore HMS setup complete!'                            AS '';
SELECT '  17 tables | 7 views | 6 functions | 9 procedures | 5 triggers' AS '';
SELECT '  2 indexing strategies | 16 SQL queries'                     AS '';
SELECT '============================================================' AS '';
