-- ============================================================================
-- MediCore HMS - Views
-- Week 8 + Week 13 Combined Final Submission
-- Total: 7 views (exceeds minimum of 2)
-- ============================================================================

USE medicore_db;

-- ============================================================================
-- VIEW 1: vw_upcoming_appointments
-- ============================================================================
CREATE OR REPLACE VIEW vw_upcoming_appointments AS
SELECT 
    a.appt_id,
    a.appt_start,
    a.appt_end,
    a.status,
    a.appointment_type,
    p.patient_id,
    p.name                                        AS patient_name,
    p.phone                                       AS patient_phone,
    d.doctor_id,
    d.name                                        AS doctor_name,
    d.specialization,
    dept.dept_name,
    a.reason,
    fn_patient_age(p.dob)                         AS patient_age,
    TIMESTAMPDIFF(MINUTE, NOW(), a.appt_start)    AS minutes_until_appointment
FROM appointment a
JOIN patient    p    ON a.patient_id = p.patient_id
JOIN doctor     d    ON a.doctor_id  = d.doctor_id
LEFT JOIN department dept ON d.dept_id = dept.dept_id
WHERE a.appt_start >= CURDATE()
  AND a.status IN ('SCHEDULED', 'CONFIRMED')
ORDER BY a.appt_start;

-- ============================================================================
-- VIEW 2: vw_revenue_summary
-- ============================================================================
CREATE OR REPLACE VIEW vw_revenue_summary AS
SELECT 
    DATE_FORMAT(i.invoice_date, '%Y-%m')                          AS month,
    COUNT(DISTINCT i.invoice_id)                                  AS total_invoices,
    SUM(i.total_amount)                                           AS total_billed,
    SUM(i.paid_amount)                                            AS total_collected,
    SUM(i.total_amount - i.paid_amount)                           AS total_outstanding,
    ROUND(SUM(i.paid_amount) / NULLIF(SUM(i.total_amount), 0) * 100, 2) AS collection_percentage,
    COUNT(DISTINCT i.patient_id)                                  AS unique_patients
FROM invoice i
GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m')
ORDER BY month DESC;

-- ============================================================================
-- VIEW 3: vw_doctor_workload
-- ============================================================================
CREATE OR REPLACE VIEW vw_doctor_workload AS
SELECT 
    d.doctor_id,
    d.name                                                                              AS doctor_name,
    d.specialization,
    dept.dept_name,
    COUNT(a.appt_id)                                                                    AS total_appointments,
    SUM(CASE WHEN a.status = 'COMPLETED'                                   THEN 1 ELSE 0 END) AS completed_appointments,
    SUM(CASE WHEN a.status = 'CANCELLED'                                   THEN 1 ELSE 0 END) AS cancelled_appointments,
    SUM(CASE WHEN a.status = 'NO_SHOW'                                     THEN 1 ELSE 0 END) AS no_show_appointments,
    SUM(CASE WHEN a.status IN ('SCHEDULED','CONFIRMED') AND a.appt_start >= NOW() THEN 1 ELSE 0 END) AS upcoming_appointments,
    ROUND(SUM(CASE WHEN a.status = 'COMPLETED' THEN 1 ELSE 0 END) /
          NULLIF(COUNT(a.appt_id), 0) * 100, 2)                                        AS completion_rate,
    d.status                                                                            AS doctor_status
FROM doctor d
LEFT JOIN department dept ON d.dept_id = dept.dept_id
LEFT JOIN appointment a   ON d.doctor_id = a.doctor_id
GROUP BY d.doctor_id, d.name, d.specialization, dept.dept_name, d.status
ORDER BY total_appointments DESC;

-- ============================================================================
-- VIEW 4: vw_bed_occupancy
-- ============================================================================
CREATE OR REPLACE VIEW vw_bed_occupancy AS
SELECT 
    r.room_type,
    r.ward,
    COUNT(b.bed_id)                                                                        AS total_beds,
    SUM(CASE WHEN b.status = 'AVAILABLE'   THEN 1 ELSE 0 END)                             AS available_beds,
    SUM(CASE WHEN b.status = 'OCCUPIED'    THEN 1 ELSE 0 END)                             AS occupied_beds,
    SUM(CASE WHEN b.status = 'MAINTENANCE' THEN 1 ELSE 0 END)                             AS maintenance_beds,
    SUM(CASE WHEN b.status = 'RESERVED'    THEN 1 ELSE 0 END)                             AS reserved_beds,
    ROUND(SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) / NULLIF(COUNT(b.bed_id), 0) * 100, 2) AS occupancy_percentage,
    r.daily_rate
FROM room r
LEFT JOIN bed b ON r.room_id = b.room_id
GROUP BY r.room_type, r.ward, r.daily_rate
ORDER BY r.room_type, r.ward;

-- ============================================================================
-- VIEW 5: vw_medicine_inventory (COMPLETELY REWRITTEN - no function in CASE)
-- ============================================================================
CREATE OR REPLACE VIEW vw_medicine_inventory AS
SELECT 
    m.medicine_id,
    m.med_name,
    m.generic_name,
    m.category,
    m.form,
    m.stock_qty,
    m.reorder_level,
    m.unit_price,
    m.stock_qty * m.unit_price                                                AS stock_value,
    -- Calculate stock status directly without using the function
    CASE 
        WHEN m.stock_qty = 0 THEN 'OUT_OF_STOCK'
        WHEN m.stock_qty <= m.reorder_level THEN 'LOW'
        WHEN m.stock_qty > m.reorder_level * 2 THEN 'ADEQUATE'
        ELSE 'NORMAL'
    END                                                                        AS stock_status,
    -- Numeric stock priority
    CASE 
        WHEN m.stock_qty = 0 THEN 1
        WHEN m.stock_qty <= m.reorder_level THEN 2
        ELSE 3
    END                                                                        AS stock_priority,
    m.expiry_date,
    DATEDIFF(m.expiry_date, CURDATE())                                        AS days_until_expiry,
    -- Numeric expiry priority
    CASE 
        WHEN m.expiry_date <= CURDATE() THEN 1
        WHEN DATEDIFF(m.expiry_date, CURDATE()) <= 30 THEN 2
        ELSE 3
    END                                                                        AS expiry_priority,
    -- Text expiry status
    CASE 
        WHEN m.expiry_date <= CURDATE() THEN 'EXPIRED'
        WHEN DATEDIFF(m.expiry_date, CURDATE()) <= 30 THEN 'EXPIRING_SOON'
        ELSE 'VALID'
    END                                                                        AS expiry_status,
    m.manufacturer,
    m.batch_no
FROM medicine m
ORDER BY stock_priority, expiry_priority, m.med_name;

-- ============================================================================
-- VIEW 6: vw_patient_summary
-- ============================================================================
CREATE OR REPLACE VIEW vw_patient_summary AS
SELECT 
    p.patient_id,
    p.name,
    fn_patient_age(p.dob)                                                              AS age,
    p.gender,
    p.blood_group,
    p.phone,
    p.email,
    p.city,
    p.registration_date,
    COUNT(DISTINCT a.appt_id)                                                          AS total_appointments,
    COUNT(DISTINCT CASE WHEN a.status = 'COMPLETED' THEN a.appt_id END)               AS completed_appointments,
    MAX(a.appt_start)                                                                  AS last_visit_date,
    COUNT(DISTINCT rx.rx_id)                                                           AS total_prescriptions,
    fn_patient_total_bill(p.patient_id)                                                AS outstanding_balance,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM appointment a2 
            WHERE a2.patient_id = p.patient_id 
              AND a2.appt_start >= NOW() 
              AND a2.status IN ('SCHEDULED', 'CONFIRMED')
        ) THEN 'HAS_UPCOMING'
        ELSE 'NO_UPCOMING'
    END                                                                                AS appointment_status
FROM patient p
LEFT JOIN appointment  a  ON p.patient_id = a.patient_id
LEFT JOIN prescription rx ON p.patient_id = rx.patient_id
GROUP BY p.patient_id, p.name, p.dob, p.gender, p.blood_group,
         p.phone, p.email, p.city, p.registration_date;

-- ============================================================================
-- VIEW 7: vw_daily_statistics
-- ============================================================================
CREATE OR REPLACE VIEW vw_daily_statistics AS
SELECT 
    CURDATE()                                                                                         AS report_date,
    (SELECT COUNT(*) FROM appointment  WHERE DATE(appt_start)    = CURDATE())                        AS today_appointments,
    (SELECT COUNT(*) FROM appointment  WHERE DATE(appt_start)    = CURDATE() AND status = 'COMPLETED') AS completed_today,
    (SELECT COUNT(*) FROM patient      WHERE DATE(registration_date) = CURDATE())                    AS new_patients_today,
    (SELECT COUNT(*) FROM prescription WHERE DATE(issued_date)   = CURDATE())                        AS prescriptions_today,
    (SELECT COUNT(*) FROM bed          WHERE status = 'OCCUPIED')                                    AS current_bed_occupancy,
    (SELECT COUNT(*) FROM bed          WHERE status = 'AVAILABLE')                                   AS beds_available,
    (SELECT SUM(total_amount) FROM invoice WHERE DATE(invoice_date) = CURDATE())                     AS revenue_today,
    (SELECT SUM(amount)       FROM payment WHERE DATE(payment_date) = CURDATE())                     AS collections_today;

SELECT '✅ All 7 views created successfully!' AS status;