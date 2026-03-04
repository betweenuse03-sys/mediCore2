-- ============================================================================
-- MediCore HMS - Constraints
-- Week 8 + Week 13 Combined Final Submission
-- ============================================================================

USE medicore_db;

-- ============================================================================
-- UNIQUE CONSTRAINTS
-- ============================================================================

ALTER TABLE department  ADD CONSTRAINT uq_dept_name       UNIQUE (dept_name);
ALTER TABLE doctor      ADD CONSTRAINT uq_doctor_email     UNIQUE (email);
ALTER TABLE doctor      ADD CONSTRAINT uq_doctor_license   UNIQUE (license_no);
ALTER TABLE patient     ADD CONSTRAINT uq_patient_phone    UNIQUE (phone);
ALTER TABLE room        ADD CONSTRAINT uq_room_number      UNIQUE (room_number);
ALTER TABLE bed         ADD CONSTRAINT uq_bed_room         UNIQUE (room_id, bed_number);
ALTER TABLE medicine    ADD CONSTRAINT uq_medicine_name    UNIQUE (med_name);
ALTER TABLE invoice     ADD CONSTRAINT uq_invoice_number   UNIQUE (invoice_number);

-- ============================================================================
-- CHECK CONSTRAINTS
-- ============================================================================

ALTER TABLE appointment ADD CONSTRAINT chk_appt_time          CHECK (appt_end > appt_start);
ALTER TABLE appointment ADD CONSTRAINT chk_appt_future        CHECK (appt_start <= DATE_ADD(CURDATE(), INTERVAL 1 YEAR));
ALTER TABLE room        ADD CONSTRAINT chk_room_rate          CHECK (daily_rate >= 0);
ALTER TABLE medicine    ADD CONSTRAINT chk_medicine_price     CHECK (unit_price >= 0);
ALTER TABLE medicine    ADD CONSTRAINT chk_medicine_stock     CHECK (stock_qty >= 0);
ALTER TABLE medicine    ADD CONSTRAINT chk_medicine_reorder   CHECK (reorder_level > 0);
ALTER TABLE invoice     ADD CONSTRAINT chk_invoice_amounts    CHECK (paid_amount >= 0 AND total_amount >= 0);
ALTER TABLE payment     ADD CONSTRAINT chk_payment_positive   CHECK (amount > 0);
ALTER TABLE staff       ADD CONSTRAINT chk_staff_salary       CHECK (salary >= 0);

SELECT 'Constraints added successfully!' AS status;

-- ============================================================================
-- FOREIGN KEYS
-- ============================================================================

ALTER TABLE doctor
    ADD CONSTRAINT fk_doctor_dept    FOREIGN KEY (dept_id) REFERENCES department(dept_id) ON DELETE SET NULL;

ALTER TABLE staff
    ADD CONSTRAINT fk_staff_dept     FOREIGN KEY (dept_id) REFERENCES department(dept_id) ON DELETE SET NULL;

ALTER TABLE appointment
    ADD CONSTRAINT fk_appt_patient   FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    ADD CONSTRAINT fk_appt_doctor    FOREIGN KEY (doctor_id)  REFERENCES doctor(doctor_id);

ALTER TABLE encounter
    ADD CONSTRAINT fk_enc_patient    FOREIGN KEY (patient_id)  REFERENCES patient(patient_id),
    ADD CONSTRAINT fk_enc_doctor     FOREIGN KEY (doctor_id)   REFERENCES doctor(doctor_id),
    ADD CONSTRAINT fk_enc_appt       FOREIGN KEY (appt_id)     REFERENCES appointment(appt_id) ON DELETE SET NULL;

ALTER TABLE prescription
    ADD CONSTRAINT fk_rx_patient     FOREIGN KEY (patient_id)   REFERENCES patient(patient_id),
    ADD CONSTRAINT fk_rx_doctor      FOREIGN KEY (doctor_id)    REFERENCES doctor(doctor_id),
    ADD CONSTRAINT fk_rx_appt        FOREIGN KEY (appt_id)      REFERENCES appointment(appt_id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_rx_encounter   FOREIGN KEY (encounter_id) REFERENCES encounter(encounter_id) ON DELETE SET NULL;

ALTER TABLE prescription_detail
    ADD CONSTRAINT fk_detail_rx      FOREIGN KEY (rx_id)         REFERENCES prescription(rx_id)  ON DELETE CASCADE,
    ADD CONSTRAINT fk_detail_med     FOREIGN KEY (medicine_id)   REFERENCES medicine(medicine_id);

ALTER TABLE lab_order
    ADD CONSTRAINT fk_lab_patient    FOREIGN KEY (patient_id)   REFERENCES patient(patient_id),
    ADD CONSTRAINT fk_lab_doctor     FOREIGN KEY (doctor_id)    REFERENCES doctor(doctor_id),
    ADD CONSTRAINT fk_lab_encounter  FOREIGN KEY (encounter_id) REFERENCES encounter(encounter_id) ON DELETE SET NULL;

ALTER TABLE lab_result
    ADD CONSTRAINT fk_result_order   FOREIGN KEY (order_id) REFERENCES lab_order(order_id) ON DELETE CASCADE;

ALTER TABLE room
    ADD CONSTRAINT fk_room_dept      FOREIGN KEY (dept_id) REFERENCES department(dept_id) ON DELETE SET NULL;

ALTER TABLE bed
    ADD CONSTRAINT fk_bed_room       FOREIGN KEY (room_id) REFERENCES room(room_id),
    ADD CONSTRAINT fk_bed_patient    FOREIGN KEY (current_patient_id) REFERENCES patient(patient_id) ON DELETE SET NULL;

ALTER TABLE invoice
    ADD CONSTRAINT fk_inv_patient    FOREIGN KEY (patient_id)   REFERENCES patient(patient_id),
    ADD CONSTRAINT fk_inv_appt       FOREIGN KEY (appt_id)      REFERENCES appointment(appt_id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_inv_encounter  FOREIGN KEY (encounter_id) REFERENCES encounter(encounter_id) ON DELETE SET NULL;

ALTER TABLE payment
    ADD CONSTRAINT fk_pay_invoice    FOREIGN KEY (invoice_id) REFERENCES invoice(invoice_id),
    ADD CONSTRAINT fk_pay_patient    FOREIGN KEY (patient_id) REFERENCES patient(patient_id);

ALTER TABLE patient_history
    ADD CONSTRAINT fk_hist_patient   FOREIGN KEY (patient_id) REFERENCES patient(patient_id) ON DELETE CASCADE;

SELECT 'Foreign key relationships created successfully!' AS status;
