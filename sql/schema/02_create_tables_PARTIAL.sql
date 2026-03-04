-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Table Creation Script (PARTIAL - 8 of 15 entities)
-- ============================================================================
-- Purpose: Create core tables for Week 8 deliverable (50% implementation)
-- Entities: Department, Doctor, Patient, Appointment, Room, Bed, 
--           Medicine, Prescription
-- ============================================================================

USE hospital_db;

-- ============================================================================
-- 1. DEPARTMENT Table
-- ============================================================================
-- Stores hospital departments (Cardiology, Neurology, etc.)
-- ============================================================================

DROP TABLE IF EXISTS department;

CREATE TABLE department (
    dept_id         INT AUTO_INCREMENT PRIMARY KEY,
    dept_name       VARCHAR(100) NOT NULL,
    dept_head       VARCHAR(100),
    phone           VARCHAR(20),
    location        VARCHAR(100),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_dept_name (dept_name)
) ENGINE=InnoDB;

-- ============================================================================
-- 2. DOCTOR Table
-- ============================================================================
-- Stores doctor information linked to departments
-- ============================================================================

DROP TABLE IF EXISTS doctor;

CREATE TABLE doctor (
    doctor_id       INT AUTO_INCREMENT PRIMARY KEY,
    dept_id         INT,
    name            VARCHAR(100) NOT NULL,
    specialization  VARCHAR(100),
    qualification   VARCHAR(200),
    phone           VARCHAR(20),
    email           VARCHAR(100),
    room_no         VARCHAR(20),
    status          ENUM('ACTIVE', 'ON_LEAVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_doctor_name (name),
    INDEX idx_doctor_status (status)
) ENGINE=InnoDB;

-- ============================================================================
-- 3. PATIENT Table
-- ============================================================================
-- Stores patient demographic and contact information
-- ============================================================================

DROP TABLE IF EXISTS patient;

CREATE TABLE patient (
    patient_id      INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    dob             DATE NOT NULL,
    gender          ENUM('M', 'F', 'OTHER') NOT NULL,
    blood_group     VARCHAR(5),
    address         TEXT,
    phone           VARCHAR(20) NOT NULL,
    email           VARCHAR(100),
    emergency_contact VARCHAR(20),
    emergency_name  VARCHAR(100),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_patient_name (name),
    INDEX idx_patient_phone (phone),
    INDEX idx_patient_dob (dob)
) ENGINE=InnoDB;

-- ============================================================================
-- 4. APPOINTMENT Table
-- ============================================================================
-- Stores appointment scheduling between patients and doctors
-- ============================================================================

DROP TABLE IF EXISTS appointment;

CREATE TABLE appointment (
    appt_id         INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT NOT NULL,
    doctor_id       INT NOT NULL,
    appt_start      DATETIME NOT NULL,
    appt_end        DATETIME NOT NULL,
    status          ENUM('SCHEDULED', 'COMPLETED', 'CANCELLED', 'NO_SHOW') 
                    DEFAULT 'SCHEDULED',
    reason          TEXT,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_appt_doctor_date (doctor_id, appt_start),
    INDEX idx_appt_patient (patient_id),
    INDEX idx_appt_status (status),
    INDEX idx_appt_date (appt_start)
) ENGINE=InnoDB;

-- ============================================================================
-- 5. ROOM Table
-- ============================================================================
-- Stores hospital room information (wards, ICU, private rooms)
-- ============================================================================

DROP TABLE IF EXISTS room;

CREATE TABLE room (
    room_id         INT AUTO_INCREMENT PRIMARY KEY,
    room_number     VARCHAR(20) NOT NULL,
    ward            VARCHAR(50),
    room_type       ENUM('GENERAL', 'SEMI_PRIVATE', 'PRIVATE', 'ICU', 'EMERGENCY') 
                    DEFAULT 'GENERAL',
    floor_no        INT,
    daily_rate      DECIMAL(10, 2) DEFAULT 0.00,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_room_type (room_type),
    INDEX idx_room_ward (ward)
) ENGINE=InnoDB;

-- ============================================================================
-- 6. BED Table
-- ============================================================================
-- Stores individual bed information within rooms
-- ============================================================================

DROP TABLE IF EXISTS bed;

CREATE TABLE bed (
    bed_id          INT AUTO_INCREMENT PRIMARY KEY,
    room_id         INT NOT NULL,
    bed_number      VARCHAR(10) NOT NULL,
    status          ENUM('AVAILABLE', 'OCCUPIED', 'MAINTENANCE', 'RESERVED') 
                    DEFAULT 'AVAILABLE',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_bed_status (status),
    INDEX idx_bed_room (room_id)
) ENGINE=InnoDB;

-- ============================================================================
-- 7. MEDICINE Table
-- ============================================================================
-- Stores medicine/drug inventory information
-- ============================================================================

DROP TABLE IF EXISTS medicine;

CREATE TABLE medicine (
    medicine_id     INT AUTO_INCREMENT PRIMARY KEY,
    med_name        VARCHAR(200) NOT NULL,
    generic_name    VARCHAR(200),
    category        VARCHAR(100),
    manufacturer    VARCHAR(150),
    unit_price      DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    stock_qty       INT NOT NULL DEFAULT 0,
    reorder_level   INT DEFAULT 50,
    expiry_date     DATE,
    description     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_med_name (med_name),
    INDEX idx_med_stock (stock_qty),
    INDEX idx_med_expiry (expiry_date)
) ENGINE=InnoDB;

-- ============================================================================
-- 8. PRESCRIPTION Table
-- ============================================================================
-- Stores prescription headers (linked to appointments in Week 13)
-- ============================================================================

DROP TABLE IF EXISTS prescription;

CREATE TABLE prescription (
    rx_id           INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT NOT NULL,
    doctor_id       INT NOT NULL,
    appt_id         INT,
    issued_date     DATETIME DEFAULT CURRENT_TIMESTAMP,
    diagnosis       TEXT,
    instructions    TEXT,
    status          ENUM('ACTIVE', 'DISPENSED', 'CANCELLED') DEFAULT 'ACTIVE',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_rx_patient (patient_id),
    INDEX idx_rx_doctor (doctor_id),
    INDEX idx_rx_date (issued_date)
) ENGINE=InnoDB;

-- ============================================================================
-- Verify Table Creation
-- ============================================================================

SHOW TABLES;

SELECT 'All 8 tables created successfully for Week 8!' AS status;

-- Show table structure summary
SELECT 
    TABLE_NAME,
    ENGINE,
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'hospital_db'
ORDER BY TABLE_NAME;

-- ============================================================================
-- End of Table Creation Script (Week 8 Partial)
-- ============================================================================
-- Note: Remaining 7 entities (Prescription_Item, Admission, Encounter, 
--       Lab_Order, Lab_Result, Invoice, Payment) will be added in Week 13
-- ============================================================================