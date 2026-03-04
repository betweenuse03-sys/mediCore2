-- ============================================================================
-- MediCore HMS - Complete Table Creation Script
-- Week 8 + Week 13 Combined Final Submission
-- 
-- NORMALIZATION DECISIONS:
--   - All tables are in 3NF:
--     * 1NF: Atomic values, no repeating groups, primary keys defined.
--     * 2NF: All non-key columns are fully functionally dependent on the PK.
--     * 3NF: No transitive dependencies (e.g., dept info separated from doctor).
--   - Denormalization Cases:
--     * invoice.balance_due is a GENERATED ALWAYS column (stored derived field)
--       for query performance on financial reporting dashboards.
--     * patient.registration_date retained separately from created_at for
--       business-level tracking independent of system timestamps.
--
-- ER DIAGRAM SUMMARY (17 Entities):
--   department  1─────<  doctor  >─────<  appointment  >─────<  patient
--   department  1─────<  room    >─────<  bed
--   doctor      1─────<  prescription  >─────<  prescription_detail  >─────<  medicine
--   doctor      1─────<  encounter
--   encounter   1─────<  lab_order  >─────<  lab_result
--   patient     1─────<  invoice   >─────<  payment
--   patient     1─────<  patient_history
--   ALL tables ───────────────────────────────>  audit_log  (via triggers)
--   department  1─────<  staff
-- ============================================================================

USE medicore_db;

-- ============================================================================
-- CORE TABLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS department (
    dept_id       INT AUTO_INCREMENT PRIMARY KEY,
    dept_name     VARCHAR(100) NOT NULL,
    dept_head     VARCHAR(100),
    phone         VARCHAR(20),
    email         VARCHAR(100),
    location      VARCHAR(100),
    floor_no      INT,
    description   TEXT,
    budget        DECIMAL(12, 2),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dept_name (dept_name),
    INDEX idx_dept_location (location)
) ENGINE=InnoDB COMMENT='Hospital departments and their information';

CREATE TABLE IF NOT EXISTS doctor (
    doctor_id         INT AUTO_INCREMENT PRIMARY KEY,
    dept_id           INT,
    name              VARCHAR(100) NOT NULL,
    specialization    VARCHAR(100),
    qualification     VARCHAR(200),
    phone             VARCHAR(20),
    email             VARCHAR(100),
    license_no        VARCHAR(50),
    room_no           VARCHAR(20),
    consultation_fee  DECIMAL(10, 2) DEFAULT 0.00,
    experience_years  INT DEFAULT 0,
    status            ENUM('ACTIVE', 'ON_LEAVE', 'INACTIVE', 'RETIRED') DEFAULT 'ACTIVE',
    joining_date      DATE,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_doctor_name (name),
    INDEX idx_doctor_status (status),
    INDEX idx_doctor_dept (dept_id),
    INDEX idx_doctor_specialization (specialization)
) ENGINE=InnoDB COMMENT='Doctor information and specializations';

CREATE TABLE IF NOT EXISTS patient (
    patient_id          INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    dob                 DATE NOT NULL,
    gender              ENUM('M', 'F', 'OTHER') NOT NULL,
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
    marital_status      ENUM('SINGLE', 'MARRIED', 'DIVORCED', 'WIDOWED'),
    insurance_provider  VARCHAR(100),
    insurance_policy_no VARCHAR(50),
    allergies           TEXT,
    medical_notes       TEXT,
    registration_date   DATE DEFAULT (CURRENT_DATE),
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_name (name),
    INDEX idx_patient_phone (phone),
    INDEX idx_patient_dob (dob),
    INDEX idx_patient_city (city),
    INDEX idx_patient_registration (registration_date)
) ENGINE=InnoDB COMMENT='Patient demographic and contact information';

CREATE TABLE IF NOT EXISTS appointment (
    appt_id           INT AUTO_INCREMENT PRIMARY KEY,
    patient_id        INT NOT NULL,
    doctor_id         INT NOT NULL,
    appt_start        DATETIME NOT NULL,
    appt_end          DATETIME NOT NULL,
    status            ENUM('SCHEDULED', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'NO_SHOW') DEFAULT 'SCHEDULED',
    reason            TEXT,
    symptoms          TEXT,
    appointment_type  ENUM('CONSULTATION', 'FOLLOW_UP', 'EMERGENCY', 'ROUTINE_CHECKUP') DEFAULT 'CONSULTATION',
    notes             TEXT,
    cancelled_reason  TEXT,
    cancelled_by      VARCHAR(100),
    reminder_sent     BOOLEAN DEFAULT FALSE,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_appt_doctor_date (doctor_id, appt_start),
    INDEX idx_appt_patient (patient_id),
    INDEX idx_appt_status (status),
    INDEX idx_appt_date (appt_start),
    INDEX idx_appt_type (appointment_type)
) ENGINE=InnoDB COMMENT='Patient appointments with doctors';

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
    salary        DECIMAL(10, 2),
    shift_type    ENUM('MORNING', 'EVENING', 'NIGHT', 'ROTATING') DEFAULT 'MORNING',
    status        ENUM('ACTIVE', 'ON_LEAVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_name (name),
    INDEX idx_staff_role (role),
    INDEX idx_staff_dept (dept_id),
    INDEX idx_staff_status (status)
) ENGINE=InnoDB COMMENT='Hospital staff members (nurses, technicians, admin)';

-- ============================================================================
-- MEDICAL RECORDS TABLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS encounter (
    encounter_id        INT AUTO_INCREMENT PRIMARY KEY,
    patient_id          INT NOT NULL,
    doctor_id           INT NOT NULL,
    appt_id             INT,
    encounter_date      DATETIME NOT NULL,
    encounter_type      ENUM('OUTPATIENT', 'INPATIENT', 'EMERGENCY', 'FOLLOWUP') DEFAULT 'OUTPATIENT',
    chief_complaint     TEXT,
    diagnosis           TEXT,
    treatment_plan      TEXT,
    vital_signs         JSON,
    physical_examination TEXT,
    doctor_notes        TEXT,
    follow_up_required  BOOLEAN DEFAULT FALSE,
    follow_up_date      DATE,
    status              ENUM('ACTIVE', 'CLOSED') DEFAULT 'ACTIVE',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_encounter_patient (patient_id),
    INDEX idx_encounter_doctor (doctor_id),
    INDEX idx_encounter_date (encounter_date),
    INDEX idx_encounter_type (encounter_type)
) ENGINE=InnoDB COMMENT='Medical encounter/visit records (JSON vital_signs: advanced feature)';

CREATE TABLE IF NOT EXISTS medicine (
    medicine_id         INT AUTO_INCREMENT PRIMARY KEY,
    med_name            VARCHAR(200) NOT NULL,
    generic_name        VARCHAR(200),
    brand_name          VARCHAR(200),
    category            VARCHAR(100),
    manufacturer        VARCHAR(150),
    form                ENUM('TABLET', 'CAPSULE', 'SYRUP', 'INJECTION', 'CREAM', 'DROPS', 'INHALER') DEFAULT 'TABLET',
    strength            VARCHAR(50),
    unit_price          DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    stock_qty           INT NOT NULL DEFAULT 0,
    reorder_level       INT DEFAULT 50,
    max_stock_level     INT DEFAULT 1000,
    expiry_date         DATE,
    batch_no            VARCHAR(50),
    supplier            VARCHAR(150),
    description         TEXT,
    side_effects        TEXT,
    contraindications   TEXT,
    storage_conditions  VARCHAR(200),
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_med_name (med_name),
    INDEX idx_med_generic (generic_name),
    INDEX idx_med_category (category),
    INDEX idx_med_stock (stock_qty),
    INDEX idx_med_expiry (expiry_date)
) ENGINE=InnoDB COMMENT='Medicine inventory and details';

CREATE TABLE IF NOT EXISTS prescription (
    rx_id           INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT NOT NULL,
    doctor_id       INT NOT NULL,
    appt_id         INT,
    encounter_id    INT,
    issued_date     DATETIME DEFAULT CURRENT_TIMESTAMP,
    diagnosis       TEXT,
    instructions    TEXT,
    follow_up_days  INT,
    status          ENUM('ACTIVE', 'DISPENSED', 'PARTIALLY_DISPENSED', 'CANCELLED', 'EXPIRED') DEFAULT 'ACTIVE',
    valid_until     DATE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rx_patient (patient_id),
    INDEX idx_rx_doctor (doctor_id),
    INDEX idx_rx_date (issued_date),
    INDEX idx_rx_status (status)
) ENGINE=InnoDB COMMENT='Prescription header information';

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
    INDEX idx_detail_rx (rx_id),
    INDEX idx_detail_medicine (medicine_id)
) ENGINE=InnoDB COMMENT='Individual medicines in a prescription';

CREATE TABLE IF NOT EXISTS lab_order (
    order_id               INT AUTO_INCREMENT PRIMARY KEY,
    patient_id             INT NOT NULL,
    doctor_id              INT NOT NULL,
    encounter_id           INT,
    test_name              VARCHAR(200) NOT NULL,
    test_type              VARCHAR(100),
    test_category          VARCHAR(100),
    priority               ENUM('ROUTINE', 'URGENT', 'STAT') DEFAULT 'ROUTINE',
    order_date             DATETIME DEFAULT CURRENT_TIMESTAMP,
    sample_collected_date  DATETIME,
    sample_type            VARCHAR(100),
    clinical_notes         TEXT,
    status                 ENUM('ORDERED', 'SAMPLE_COLLECTED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED') DEFAULT 'ORDERED',
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_lab_patient (patient_id),
    INDEX idx_lab_doctor (doctor_id),
    INDEX idx_lab_status (status),
    INDEX idx_lab_date (order_date)
) ENGINE=InnoDB COMMENT='Laboratory test orders';

CREATE TABLE IF NOT EXISTS lab_result (
    result_id     INT AUTO_INCREMENT PRIMARY KEY,
    order_id      INT NOT NULL,
    test_parameter VARCHAR(200),
    result_value  VARCHAR(500),
    unit          VARCHAR(50),
    normal_range  VARCHAR(100),
    abnormal_flag ENUM('NORMAL', 'HIGH', 'LOW', 'CRITICAL') DEFAULT 'NORMAL',
    result_date   DATETIME DEFAULT CURRENT_TIMESTAMP,
    performed_by  VARCHAR(100),
    verified_by   VARCHAR(100),
    comments      TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_result_order (order_id),
    INDEX idx_result_date (result_date)
) ENGINE=InnoDB COMMENT='Laboratory test results';

-- ============================================================================
-- RESOURCE MANAGEMENT TABLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS room (
    room_id       INT AUTO_INCREMENT PRIMARY KEY,
    room_number   VARCHAR(20) NOT NULL,
    ward          VARCHAR(50),
    room_type     ENUM('GENERAL', 'SEMI_PRIVATE', 'PRIVATE', 'ICU', 'CCU', 'NICU', 'EMERGENCY', 'OPERATION_THEATRE') DEFAULT 'GENERAL',
    floor_no      INT,
    dept_id       INT,
    bed_capacity  INT DEFAULT 1,
    daily_rate    DECIMAL(10, 2) DEFAULT 0.00,
    amenities     TEXT,
    status        ENUM('AVAILABLE', 'OCCUPIED', 'MAINTENANCE', 'RESERVED') DEFAULT 'AVAILABLE',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_type (room_type),
    INDEX idx_room_ward (ward),
    INDEX idx_room_floor (floor_no),
    INDEX idx_room_status (status)
) ENGINE=InnoDB COMMENT='Hospital rooms and wards';

CREATE TABLE IF NOT EXISTS bed (
    bed_id              INT AUTO_INCREMENT PRIMARY KEY,
    room_id             INT NOT NULL,
    bed_number          VARCHAR(10) NOT NULL,
    bed_type            ENUM('STANDARD', 'ELECTRIC', 'ICU', 'PEDIATRIC') DEFAULT 'STANDARD',
    status              ENUM('AVAILABLE', 'OCCUPIED', 'MAINTENANCE', 'RESERVED', 'CLEANING') DEFAULT 'AVAILABLE',
    current_patient_id  INT,
    admission_date      DATETIME,
    expected_discharge  DATE,
    notes               TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bed_status (status),
    INDEX idx_bed_room (room_id),
    INDEX idx_bed_patient (current_patient_id)
) ENGINE=InnoDB COMMENT='Individual beds within rooms';

-- ============================================================================
-- FINANCIAL TABLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS invoice (
    invoice_id      INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT NOT NULL,
    appt_id         INT,
    encounter_id    INT,
    invoice_number  VARCHAR(50) NOT NULL,
    invoice_date    DATE NOT NULL,
    due_date        DATE,
    subtotal        DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    tax_amount      DECIMAL(10, 2) DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    total_amount    DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    paid_amount     DECIMAL(12, 2) DEFAULT 0.00,
    -- Denormalization note: balance_due is GENERATED ALWAYS for reporting performance
    balance_due     DECIMAL(12, 2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED,
    payment_status  ENUM('UNPAID', 'PARTIALLY_PAID', 'PAID', 'OVERDUE', 'CANCELLED') DEFAULT 'UNPAID',
    billing_address TEXT,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice_patient (patient_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_invoice_status (payment_status)
) ENGINE=InnoDB COMMENT='Patient invoices and billing';

CREATE TABLE IF NOT EXISTS payment (
    payment_id       INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id       INT NOT NULL,
    patient_id       INT NOT NULL,
    payment_date     DATETIME DEFAULT CURRENT_TIMESTAMP,
    amount           DECIMAL(10, 2) NOT NULL,
    payment_method   ENUM('CASH', 'CARD', 'CHEQUE', 'BANK_TRANSFER', 'INSURANCE', 'MOBILE_PAYMENT') DEFAULT 'CASH',
    transaction_id   VARCHAR(100),
    reference_number VARCHAR(100),
    payment_notes    TEXT,
    received_by      VARCHAR(100),
    status           ENUM('PENDING', 'COMPLETED', 'FAILED', 'REFUNDED') DEFAULT 'COMPLETED',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_invoice (invoice_id),
    INDEX idx_payment_patient (patient_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_payment_method (payment_method)
) ENGINE=InnoDB COMMENT='Payment transactions';

-- ============================================================================
-- AUDIT AND HISTORY TABLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS audit_log (
    log_id           INT AUTO_INCREMENT PRIMARY KEY,
    table_name       VARCHAR(100) NOT NULL,
    record_id        INT,
    action           ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values       JSON,
    new_values       JSON,
    changed_by       VARCHAR(100),
    ip_address       VARCHAR(45),
    user_agent       VARCHAR(255),
    change_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_table (table_name),
    INDEX idx_audit_action (action),
    INDEX idx_audit_timestamp (change_timestamp),
    INDEX idx_audit_user (changed_by)
) ENGINE=InnoDB COMMENT='System audit trail - updated by triggers on key tables';

CREATE TABLE IF NOT EXISTS patient_history (
    history_id         INT AUTO_INCREMENT PRIMARY KEY,
    patient_id         INT NOT NULL,
    field_changed      VARCHAR(100),
    old_value          TEXT,
    new_value          TEXT,
    change_description TEXT,
    changed_by         VARCHAR(100),
    changed_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_history_patient (patient_id),
    INDEX idx_history_date (changed_at)
) ENGINE=InnoDB COMMENT='Patient record change history';

SELECT '✅ All 17 tables created successfully!' AS status;
