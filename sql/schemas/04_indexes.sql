-- ============================================================================
-- STRATEGY 1: Composite covering indexes
-- ============================================================================

-- Appointment schedule lookup: doctor + date range
CREATE INDEX idx_appt_doctor_start_status
    ON appointment (doctor_id, appt_start, status);

-- Patient billing lookup
CREATE INDEX idx_invoice_patient_status_date
    ON invoice (patient_id, payment_status, invoice_date);

-- Prescription by doctor+patient (clinical dashboard)
CREATE INDEX idx_rx_doctor_patient
    ON prescription (doctor_id, patient_id, issued_date);

-- Lab orders by priority and status (urgent queue)
CREATE INDEX idx_lab_priority_status
    ON lab_order (priority, status, order_date);

-- Medicine reorder watch
CREATE INDEX idx_med_stock_reorder
    ON medicine (stock_qty, reorder_level, expiry_date);

-- ============================================================================
-- STRATEGY 2: Standard indexes for date filtering (MariaDB compatible)
-- ============================================================================

-- Date index on appointment start
CREATE INDEX idx_appt_date_only
    ON appointment (appt_start);

-- Date index on invoices (monthly revenue reports)
CREATE INDEX idx_invoice_yearmonth
    ON invoice (invoice_date);

-- Encounter date index
CREATE INDEX idx_encounter_date_only
    ON encounter (encounter_date);

-- ============================================================================
-- ADDITIONAL PERFORMANCE INDEXES
-- ============================================================================

-- Full-text search on patient name (clinic search bar)
ALTER TABLE patient ADD FULLTEXT INDEX ft_patient_name (name);

-- Full-text search on medicine name and generic name
ALTER TABLE medicine ADD FULLTEXT INDEX ft_medicine_name (med_name, generic_name);

SELECT 'Indexing strategies applied successfully!' AS status;