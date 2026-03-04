-- ============================================================================
-- MediCore (HMS) - Week 8
-- MASTER EXECUTION SCRIPT (MariaDB/XAMPP Compatible)
-- DB: medicore_db
-- ============================================================================

-- ============================================================================
-- STEP 1: Create Database
-- ============================================================================
SELECT 'STEP 1: Creating database...' AS status;

DROP DATABASE IF EXISTS medicore_db;
CREATE DATABASE medicore_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medicore_db;

SELECT 'Database created successfully!' AS status;

-- ============================================================================
-- STEP 2: Create Tables
-- ============================================================================
SELECT 'STEP 2: Creating tables...' AS status;

-- Department
CREATE TABLE department (
    dept_id INT AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(100) NOT NULL,
    dept_head VARCHAR(100),
    phone VARCHAR(20),
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dept_name (dept_name)
) ENGINE=InnoDB;

-- Doctor
CREATE TABLE doctor (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    dept_id INT,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    qualification VARCHAR(200),
    phone VARCHAR(20),
    email VARCHAR(100),
    room_no VARCHAR(20),
    status ENUM('ACTIVE', 'ON_LEAVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_doctor_name (name),
    INDEX idx_doctor_status (status)
) ENGINE=InnoDB;

-- Patient
CREATE TABLE patient (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('M', 'F', 'OTHER') NOT NULL,
    blood_group VARCHAR(5),
    address TEXT,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    emergency_contact VARCHAR(20),
    emergency_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_name (name),
    INDEX idx_patient_phone (phone),
    INDEX idx_patient_dob (dob)
) ENGINE=InnoDB;

-- Appointment
CREATE TABLE appointment (
    appt_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appt_start DATETIME NOT NULL,
    appt_end DATETIME NOT NULL,
    status ENUM('SCHEDULED', 'COMPLETED', 'CANCELLED', 'NO_SHOW') DEFAULT 'SCHEDULED',
    reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_appt_doctor_date (doctor_id, appt_start),
    INDEX idx_appt_patient (patient_id),
    INDEX idx_appt_status (status),
    INDEX idx_appt_date (appt_start)
) ENGINE=InnoDB;

-- Room
CREATE TABLE room (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL,
    ward VARCHAR(50),
    room_type ENUM('GENERAL', 'SEMI_PRIVATE', 'PRIVATE', 'ICU', 'EMERGENCY') DEFAULT 'GENERAL',
    floor_no INT,
    daily_rate DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_type (room_type),
    INDEX idx_room_ward (ward)
) ENGINE=InnoDB;

-- Bed
CREATE TABLE bed (
    bed_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    bed_number VARCHAR(10) NOT NULL,
    status ENUM('AVAILABLE', 'OCCUPIED', 'MAINTENANCE', 'RESERVED') DEFAULT 'AVAILABLE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bed_status (status),
    INDEX idx_bed_room (room_id)
) ENGINE=InnoDB;

-- Medicine
CREATE TABLE medicine (
    medicine_id INT AUTO_INCREMENT PRIMARY KEY,
    med_name VARCHAR(200) NOT NULL,
    generic_name VARCHAR(200),
    category VARCHAR(100),
    manufacturer VARCHAR(150),
    unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    stock_qty INT NOT NULL DEFAULT 0,
    reorder_level INT DEFAULT 50,
    expiry_date DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_med_name (med_name),
    INDEX idx_med_stock (stock_qty),
    INDEX idx_med_expiry (expiry_date)
) ENGINE=InnoDB;

-- Prescription
CREATE TABLE prescription (
    rx_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appt_id INT,
    issued_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    diagnosis TEXT,
    instructions TEXT,
    status ENUM('ACTIVE', 'DISPENSED', 'CANCELLED') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rx_patient (patient_id),
    INDEX idx_rx_doctor (doctor_id),
    INDEX idx_rx_date (issued_date)
) ENGINE=InnoDB;

SELECT '8 tables created successfully!' AS status;

-- ============================================================================
-- STEP 3: Add Constraints
-- ============================================================================
SELECT 'STEP 3: Adding constraints...' AS status;

ALTER TABLE department ADD CONSTRAINT uq_dept_name UNIQUE (dept_name);
ALTER TABLE doctor ADD CONSTRAINT uq_doctor_email UNIQUE (email);
ALTER TABLE patient ADD CONSTRAINT uq_patient_phone UNIQUE (phone);
ALTER TABLE room ADD CONSTRAINT uq_room_number UNIQUE (room_number);
ALTER TABLE bed ADD CONSTRAINT uq_bed_room UNIQUE (room_id, bed_number);
ALTER TABLE medicine ADD CONSTRAINT uq_medicine_name UNIQUE (med_name);

-- MariaDB/XAMPP FIX:
-- CHECK constraints cannot contain CURDATE() or other non-deterministic functions.
-- We enforce patient DOB rule using triggers instead (see below).
-- ALTER TABLE patient ADD CONSTRAINT chk_patient_dob CHECK (dob <= CURDATE());

-- These CHECK constraints are OK (no CURDATE()):
ALTER TABLE appointment ADD CONSTRAINT chk_appt_time CHECK (appt_end > appt_start);
ALTER TABLE room ADD CONSTRAINT chk_room_rate CHECK (daily_rate >= 0);
ALTER TABLE medicine ADD CONSTRAINT chk_medicine_price CHECK (unit_price >= 0);
ALTER TABLE medicine ADD CONSTRAINT chk_medicine_stock CHECK (stock_qty >= 0);
ALTER TABLE medicine ADD CONSTRAINT chk_medicine_reorder CHECK (reorder_level > 0);

SELECT 'Constraints added successfully!' AS status;

-- ============================================================================
-- STEP 3B: DOB Validation Triggers (MariaDB-compatible replacement for CHECK)
-- ============================================================================
SELECT 'STEP 3B: Adding DOB validation triggers...' AS status;

DROP TRIGGER IF EXISTS trg_patient_dob_validate_ins;
DROP TRIGGER IF EXISTS trg_patient_dob_validate_upd;

DELIMITER $$

CREATE TRIGGER trg_patient_dob_validate_ins
BEFORE INSERT ON patient
FOR EACH ROW
BEGIN
    IF NEW.dob > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Invalid DOB: date of birth cannot be in the future';
    END IF;
END$$

CREATE TRIGGER trg_patient_dob_validate_upd
BEFORE UPDATE ON patient
FOR EACH ROW
BEGIN
    IF NEW.dob > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Invalid DOB: date of birth cannot be in the future';
    END IF;
END$$

DELIMITER ;

SELECT 'DOB validation triggers added!' AS status;

-- ============================================================================
-- STEP 4: Add Foreign Keys
-- ============================================================================
SELECT 'STEP 4: Adding foreign key relationships...' AS status;

ALTER TABLE doctor ADD CONSTRAINT fk_doctor_department
    FOREIGN KEY (dept_id) REFERENCES department(dept_id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE appointment ADD CONSTRAINT fk_appointment_patient
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE appointment ADD CONSTRAINT fk_appointment_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctor(doctor_id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE bed ADD CONSTRAINT fk_bed_room
    FOREIGN KEY (room_id) REFERENCES room(room_id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE prescription ADD CONSTRAINT fk_prescription_patient
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE prescription ADD CONSTRAINT fk_prescription_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctor(doctor_id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE prescription ADD CONSTRAINT fk_prescription_appointment
    FOREIGN KEY (appt_id) REFERENCES appointment(appt_id)
    ON DELETE SET NULL ON UPDATE CASCADE;

SELECT 'Foreign keys added successfully!' AS status;

-- ============================================================================
-- STEP 5: Load Sample Data (Minimal)
-- ============================================================================
SELECT 'STEP 5: Loading sample data...' AS status;

INSERT INTO department (dept_name, dept_head) VALUES
('Cardiology', 'Dr. Sarah Ahmed'),
('Neurology', 'Dr. Rahman Khan'),
('Pediatrics', 'Dr. Mahmud Hasan');

INSERT INTO doctor (dept_id, name, specialization, email, status) VALUES
(1, 'Dr. Ahmed Rahim', 'Interventional Cardiology', 'ahmed.rahim@hospital.com', 'ACTIVE'),
(2, 'Dr. Habib Mahmud', 'Stroke Specialist', 'habib.mahmud@hospital.com', 'ACTIVE');

INSERT INTO patient (name, dob, gender, phone) VALUES
('Mohammad Ali', '1985-03-15', 'M', '+880-1911-111111'),
('Ayesha Rahman', '1990-07-22', 'F', '+880-1922-222222');

SELECT 'Minimal sample data loaded.' AS status;

-- ============================================================================
-- STEP 6: Create Function
-- ============================================================================
SELECT 'STEP 6: Creating function...' AS status;

DROP FUNCTION IF EXISTS fn_patient_age;

DELIMITER $$
CREATE FUNCTION fn_patient_age(p_dob DATE)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN TIMESTAMPDIFF(YEAR, p_dob, CURDATE());
END$$
DELIMITER ;

SELECT 'Function fn_patient_age created!' AS status;

-- ============================================================================
-- STEP 7: Create Procedure
-- ============================================================================
SELECT 'STEP 7: Creating procedure...' AS status;

DROP PROCEDURE IF EXISTS sp_schedule_appointment;

DELIMITER $$
CREATE PROCEDURE sp_schedule_appointment(
    IN p_patient_id INT,
    IN p_doctor_id INT,
    IN p_start_time DATETIME,
    IN p_end_time DATETIME,
    IN p_reason TEXT
)
BEGIN
    DECLARE v_overlap_count INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'ERROR: Transaction rolled back' AS message;
    END;

    START TRANSACTION;

    -- Extra validation (also covered by CHECK)
    IF p_end_time <= p_start_time THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: End time must be after start time';
    END IF;

    SELECT COUNT(*) INTO v_overlap_count
    FROM appointment
    WHERE doctor_id = p_doctor_id AND status = 'SCHEDULED'
      AND ((p_start_time >= appt_start AND p_start_time < appt_end)
           OR (p_end_time > appt_start AND p_end_time <= appt_end)
           OR (p_start_time <= appt_start AND p_end_time >= appt_end));

    IF v_overlap_count > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: Doctor has overlapping appointment';
    END IF;

    INSERT INTO appointment (patient_id, doctor_id, appt_start, appt_end, status, reason)
    VALUES (p_patient_id, p_doctor_id, p_start_time, p_end_time, 'SCHEDULED', p_reason);

    COMMIT;
    SELECT LAST_INSERT_ID() AS appointment_id, 'SUCCESS' AS message;
END$$
DELIMITER ;

SELECT 'Procedure sp_schedule_appointment created!' AS status;

-- ============================================================================
-- STEP 8: Create Trigger (Overlap Prevention)
-- ============================================================================
SELECT 'STEP 8: Creating trigger...' AS status;

DROP TRIGGER IF EXISTS trg_before_appointment_overlap;

DELIMITER $$
CREATE TRIGGER trg_before_appointment_overlap
BEFORE INSERT ON appointment
FOR EACH ROW
BEGIN
    DECLARE v_overlap_count INT DEFAULT 0;

    IF NEW.status = 'SCHEDULED' THEN
        SELECT COUNT(*) INTO v_overlap_count
        FROM appointment
        WHERE doctor_id = NEW.doctor_id AND status = 'SCHEDULED'
          AND ((NEW.appt_start >= appt_start AND NEW.appt_start < appt_end)
               OR (NEW.appt_end > appt_start AND NEW.appt_end <= appt_end)
               OR (NEW.appt_start <= appt_start AND NEW.appt_end >= appt_end));

        IF v_overlap_count > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'OVERLAP: Doctor already has appointment';
        END IF;
    END IF;
END$$
DELIMITER ;

SELECT 'Trigger trg_before_appointment_overlap created!' AS status;

-- ============================================================================
-- VERIFICATION
-- ============================================================================
SELECT '============================================' AS '';
SELECT 'SETUP COMPLETE! Verifying installation...' AS status;
SELECT '============================================' AS '';

SELECT 'Tables:' AS '';
SHOW TABLES;

SELECT '' AS '';
SELECT 'Record counts:' AS '';
SELECT 'departments' AS table_name, COUNT(*) AS records FROM department
UNION ALL SELECT 'doctors', COUNT(*) FROM doctor
UNION ALL SELECT 'patients', COUNT(*) FROM patient;

SELECT '' AS '';
SELECT 'Testing function:' AS '';
SELECT fn_patient_age('1990-05-15') AS test_age;

SELECT '' AS '';
SELECT 'Procedural objects created:' AS '';
SHOW FUNCTION STATUS WHERE Db = 'medicore_db';
SHOW PROCEDURE STATUS WHERE Db = 'medicore_db';
SHOW TRIGGERS FROM medicore_db;

SELECT '============================================' AS '';
SELECT 'MediCore Week 8 database setup COMPLETE!' AS status;
SELECT 'Next: Execute individual insert scripts for full sample data' AS next_step;
SELECT '============================================' AS '';
