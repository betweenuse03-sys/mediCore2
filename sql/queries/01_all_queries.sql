-- ============================================================================
-- MediCore HMS - SQL Queries
-- Week 8 + Week 13 Combined Final Submission
--
-- QUERY INVENTORY (15 total — exceeds minimum of 10):
--   Analytical/Reporting (5+):  Q01, Q02, Q03, Q04, Q05, Q11, Q12, Q13
--   Multi-table JOINs:          Q01, Q06, Q07, Q08, Q09
--   Nested Subqueries:          Q06, Q09, Q10, Q14, Q15
--   Analytical Window functions: Q02, Q11, Q12
--   ROLLUP / GROUPING SETS:     Q13
--   UNION:                      Q03
-- ============================================================================

USE medicore_db;

-- ============================================================================
-- Q01: Appointment Trends by Day of Week (Analytical - Week 8 Analytics 1)
-- Business Value: Staff scheduling optimization
-- Technique: Date functions, conditional aggregation
-- ============================================================================
SELECT 
    DAYNAME(appt_start)                                                           AS day_of_week,
    DAYOFWEEK(appt_start)                                                         AS day_num,
    COUNT(*)                                                                      AS total_appointments,
    SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END)                        AS completed,
    SUM(CASE WHEN status = 'NO_SHOW'   THEN 1 ELSE 0 END)                        AS no_shows,
    ROUND(100.0 * SUM(CASE WHEN status = 'NO_SHOW' THEN 1 ELSE 0 END) / COUNT(*), 2) AS no_show_rate,
    ROUND(100.0 * SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) / COUNT(*), 2) AS completion_rate
FROM appointment
WHERE appt_start >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DAYNAME(appt_start), DAYOFWEEK(appt_start)
ORDER BY day_num;

-- ============================================================================
-- Q02: Top Performing Doctors (Monthly Ranking) (Analytical - Week 8 Analytics 2)
-- Business Value: Performance measurement, workload distribution
-- Technique: Window functions (RANK, DENSE_RANK), derived table
-- ============================================================================
SELECT 
    doctor_id,
    doctor_name,
    department,
    appointments_completed,
    total_patients_seen,
    avg_appointment_duration_mins,
    RANK()       OVER (ORDER BY appointments_completed DESC)                    AS overall_rank,
    DENSE_RANK() OVER (PARTITION BY department ORDER BY appointments_completed DESC) AS dept_rank
FROM (
    SELECT 
        d.doctor_id,
        d.name                                                                  AS doctor_name,
        dept.dept_name                                                          AS department,
        COUNT(a.appt_id)                                                        AS appointments_completed,
        COUNT(DISTINCT a.patient_id)                                            AS total_patients_seen,
        ROUND(AVG(TIMESTAMPDIFF(MINUTE, a.appt_start, a.appt_end)), 2)         AS avg_appointment_duration_mins
    FROM doctor d
    LEFT JOIN department dept ON d.dept_id  = dept.dept_id
    LEFT JOIN appointment a   ON a.doctor_id = d.doctor_id
        AND a.status = 'COMPLETED'
        AND a.appt_start >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    WHERE d.status = 'ACTIVE'
    GROUP BY d.doctor_id, d.name, dept.dept_name
) AS doctor_stats
WHERE appointments_completed > 0
ORDER BY overall_rank;

-- ============================================================================
-- Q03: Appointment Efficiency Metrics (UNION - Analytical Week 8 Analytics 7)
-- Business Value: Process improvement, patient satisfaction
-- Technique: UNION ALL across different time ranges
-- ============================================================================
SELECT 
    'Overall' AS period, COUNT(*) AS total,
    SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END)    AS completed,
    SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END)    AS cancelled,
    SUM(CASE WHEN status = 'NO_SHOW'   THEN 1 ELSE 0 END)    AS no_shows,
    ROUND(100.0 * SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) / COUNT(*), 2) AS completion_pct,
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, appt_start, appt_end)), 2) AS avg_duration_mins
FROM appointment
UNION ALL
SELECT 
    'Last 30 Days', COUNT(*),
    SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END),
    SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END),
    SUM(CASE WHEN status = 'NO_SHOW'   THEN 1 ELSE 0 END),
    ROUND(100.0 * SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) / COUNT(*), 2),
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, appt_start, appt_end)), 2)
FROM appointment
WHERE appt_start >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);

-- ============================================================================
-- Q04: Patient Demographics Analysis (Analytical - Week 8 Analytics 4)
-- Business Value: Marketing, service planning, population health
-- Technique: Window SUM OVER(), nested subquery, CASE classification
-- ============================================================================
SELECT 
    age_group, gender, patient_count,
    ROUND(100.0 * patient_count / SUM(patient_count) OVER (), 2) AS pct_of_total,
    avg_age,
    total_appointments,
    ROUND(total_appointments * 1.0 / patient_count, 2)           AS avg_appts_per_patient
FROM (
    SELECT 
        CASE 
            WHEN fn_patient_age(p.dob) < 13           THEN 'Child (0-12)'
            WHEN fn_patient_age(p.dob) BETWEEN 13  AND 17 THEN 'Teen (13-17)'
            WHEN fn_patient_age(p.dob) BETWEEN 18  AND 35 THEN 'Young Adult (18-35)'
            WHEN fn_patient_age(p.dob) BETWEEN 36  AND 50 THEN 'Adult (36-50)'
            WHEN fn_patient_age(p.dob) BETWEEN 51  AND 65 THEN 'Middle Age (51-65)'
            ELSE 'Senior (65+)'
        END                                         AS age_group,
        p.gender,
        COUNT(DISTINCT p.patient_id)                AS patient_count,
        ROUND(AVG(fn_patient_age(p.dob)), 1)        AS avg_age,
        COUNT(a.appt_id)                            AS total_appointments
    FROM patient p
    LEFT JOIN appointment a ON a.patient_id = p.patient_id
    GROUP BY age_group, p.gender
) AS demographics
ORDER BY 
    CASE age_group
        WHEN 'Child (0-12)'       THEN 1
        WHEN 'Teen (13-17)'       THEN 2
        WHEN 'Young Adult (18-35)'THEN 3
        WHEN 'Adult (36-50)'      THEN 4
        WHEN 'Middle Age (51-65)' THEN 5
        ELSE 6
    END, gender;

-- ============================================================================
-- Q05: Department Performance Dashboard (Analytical - Week 8 Analytics 3)
-- Business Value: Strategic planning, resource allocation
-- Technique: Multi-table LEFT JOIN, conditional aggregation, NULLIF
-- ============================================================================
SELECT 
    dept.dept_name                                                                AS department,
    dept.dept_head,
    COUNT(DISTINCT d.doctor_id)                                                   AS total_doctors,
    COUNT(DISTINCT CASE WHEN d.status = 'ACTIVE' THEN d.doctor_id END)           AS active_doctors,
    COUNT(DISTINCT a.appt_id)                                                     AS total_appointments,
    COUNT(DISTINCT CASE WHEN a.appt_start >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                        THEN a.appt_id END)                                       AS appts_last_30d,
    COUNT(DISTINCT rx.rx_id)                                                      AS total_prescriptions,
    COUNT(DISTINCT a.patient_id)                                                  AS unique_patients,
    ROUND(100.0 * SUM(CASE WHEN a.status = 'COMPLETED' THEN 1 ELSE 0 END) /
          NULLIF(COUNT(a.appt_id), 0), 2)                                         AS completion_rate,
    ROUND(100.0 * SUM(CASE WHEN a.status = 'NO_SHOW' THEN 1 ELSE 0 END) /
          NULLIF(COUNT(a.appt_id), 0), 2)                                         AS no_show_rate
FROM department dept
LEFT JOIN doctor     d    ON d.dept_id  = dept.dept_id
LEFT JOIN appointment a   ON a.doctor_id = d.doctor_id
LEFT JOIN prescription rx ON rx.doctor_id = d.doctor_id
GROUP BY dept.dept_id, dept.dept_name, dept.dept_head
ORDER BY appts_last_30d DESC;

-- ============================================================================
-- Q06: Upcoming Appointments with Full Details (Multi-table JOIN - Week 8 Q1)
-- Technique: INNER JOIN across 4 tables, date filtering, computed columns
-- ============================================================================
SELECT 
    a.appt_id,
    a.appt_start,
    CONCAT(DATE_FORMAT(a.appt_start, '%W, %M %d, %Y'), ' at ',
           DATE_FORMAT(a.appt_start, '%h:%i %p'))                               AS formatted_time,
    p.name                                                                      AS patient_name,
    p.phone                                                                     AS patient_phone,
    fn_patient_age(p.dob)                                                       AS patient_age,
    d.name                                                                      AS doctor_name,
    d.specialization,
    dept.dept_name,
    a.reason,
    a.appointment_type,
    a.status
FROM appointment a
INNER JOIN patient     p    ON p.patient_id = a.patient_id
INNER JOIN doctor      d    ON d.doctor_id  = a.doctor_id
INNER JOIN department  dept ON dept.dept_id = d.dept_id
WHERE a.appt_start >= NOW()
  AND a.status IN ('SCHEDULED', 'CONFIRMED')
ORDER BY a.appt_start
LIMIT 20;

-- ============================================================================
-- Q07: Current Bed Occupancy by Ward (Aggregation + JOIN - Week 8 Q3)
-- Technique: GROUP BY, conditional aggregation, percentage calculation
-- ============================================================================
SELECT 
    r.ward,
    r.room_type,
    COUNT(DISTINCT r.room_id)                                                   AS total_rooms,
    COUNT(b.bed_id)                                                             AS total_beds,
    SUM(CASE WHEN b.status = 'OCCUPIED'    THEN 1 ELSE 0 END)                  AS occupied_beds,
    SUM(CASE WHEN b.status = 'AVAILABLE'   THEN 1 ELSE 0 END)                  AS available_beds,
    SUM(CASE WHEN b.status = 'MAINTENANCE' THEN 1 ELSE 0 END)                  AS maintenance_beds,
    ROUND(100.0 * SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) /
          COUNT(b.bed_id), 2)                                                   AS occupancy_pct,
    SUM(CASE WHEN b.status = 'AVAILABLE' THEN r.daily_rate ELSE 0 END)         AS potential_daily_revenue
FROM room r
LEFT JOIN bed b ON b.room_id = r.room_id
GROUP BY r.ward, r.room_type
ORDER BY occupancy_pct DESC;

-- ============================================================================
-- Q08: Doctor Schedule for Today (Week 8 Q6)
-- Technique: Date functions, GROUP_CONCAT, LEFT JOIN
-- ============================================================================
SELECT 
    d.name                                                                      AS doctor,
    dept.dept_name,
    d.room_no,
    COUNT(a.appt_id)                                                            AS appointments_today,
    GROUP_CONCAT(
        CONCAT(DATE_FORMAT(a.appt_start, '%H:%i'), ' – ', p.name)
        ORDER BY a.appt_start SEPARATOR ' | '
    )                                                                           AS schedule,
    SUM(TIMESTAMPDIFF(MINUTE, a.appt_start, a.appt_end))                       AS booked_minutes
FROM doctor d
LEFT JOIN department dept ON d.dept_id  = dept.dept_id
LEFT JOIN appointment a   ON a.doctor_id = d.doctor_id
    AND DATE(a.appt_start) = CURDATE()
    AND a.status IN ('SCHEDULED', 'CONFIRMED')
LEFT JOIN patient p ON p.patient_id = a.patient_id
WHERE d.status = 'ACTIVE'
GROUP BY d.doctor_id, d.name, dept.dept_name, d.room_no
ORDER BY appointments_today DESC, d.name;

-- ============================================================================
-- Q09: Patient Visit History Summary (Complex Aggregation - Week 8 Q7)
-- Technique: Multiple LEFT JOINs, complex aggregation, HAVING
-- ============================================================================
SELECT 
    p.patient_id,
    p.name,
    p.phone,
    fn_patient_age(p.dob)                                                       AS age,
    p.blood_group,
    COUNT(DISTINCT a.appt_id)                                                   AS total_appointments,
    COUNT(DISTINCT rx.rx_id)                                                    AS total_prescriptions,
    COUNT(DISTINCT CASE WHEN a.status = 'COMPLETED' THEN a.appt_id END)        AS completed_visits,
    COUNT(DISTINCT CASE WHEN a.status = 'NO_SHOW'   THEN a.appt_id END)        AS missed_visits,
    MIN(a.appt_start)                                                           AS first_visit,
    MAX(a.appt_start)                                                           AS last_visit,
    DATEDIFF(CURDATE(), MAX(a.appt_start))                                      AS days_since_last_visit
FROM patient p
LEFT JOIN appointment  a  ON a.patient_id = p.patient_id
LEFT JOIN prescription rx ON rx.patient_id = p.patient_id
GROUP BY p.patient_id, p.name, p.phone, p.dob, p.blood_group
HAVING total_appointments > 0
ORDER BY last_visit DESC
LIMIT 25;

-- ============================================================================
-- Q10: Doctors with Above-Average Appointments (Nested Subquery - Week 8 Q2)
-- Technique: Correlated subquery, derived table aggregate
-- ============================================================================
SELECT 
    d.doctor_id,
    d.name,
    d.specialization,
    dept.dept_name,
    (SELECT COUNT(*) FROM appointment a WHERE a.doctor_id = d.doctor_id) AS total_appts,
    ROUND((SELECT AVG(cnt) FROM (
                SELECT COUNT(*) AS cnt FROM appointment GROUP BY doctor_id
           ) AS t), 2)                                                    AS avg_appts
FROM doctor d
LEFT JOIN department dept ON d.dept_id = dept.dept_id
WHERE (SELECT COUNT(*) FROM appointment a WHERE a.doctor_id = d.doctor_id) >
      (SELECT AVG(cnt) FROM (SELECT COUNT(*) AS cnt FROM appointment GROUP BY doctor_id) x)
ORDER BY total_appts DESC;

-- ============================================================================
-- Q11: Resource Utilization – Room Revenue Potential (Analytical - Week 8 A6)
-- Technique: Conditional aggregation, revenue projections, multi-column GROUP BY
-- ============================================================================
SELECT 
    r.room_type,
    COUNT(DISTINCT r.room_id)                                                   AS total_rooms,
    COUNT(b.bed_id)                                                             AS total_beds,
    SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END)                     AS occupied_beds,
    ROUND(100.0 * SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) /
          COUNT(b.bed_id), 2)                                                   AS occupancy_rate,
    ROUND(AVG(r.daily_rate), 2)                                                 AS avg_daily_rate,
    SUM(CASE WHEN b.status = 'OCCUPIED' THEN r.daily_rate ELSE 0 END)          AS actual_daily_revenue,
    SUM(r.daily_rate)                                                           AS potential_daily_revenue,
    SUM(r.daily_rate) * 30                                                      AS monthly_revenue_potential,
    SUM(r.daily_rate) - SUM(CASE WHEN b.status = 'OCCUPIED' THEN r.daily_rate ELSE 0 END) AS daily_revenue_gap
FROM room r
LEFT JOIN bed b ON b.room_id = r.room_id
GROUP BY r.room_type
ORDER BY potential_daily_revenue DESC;

-- ============================================================================
-- Q12: Medicine Low Stock Alert (Week 8 Q5)
-- Technique: Calculated fields, CASE priority, ORDER BY expression
-- ============================================================================
SELECT 
    medicine_id,
    med_name,
    category,
    stock_qty                                                                   AS current_stock,
    reorder_level                                                               AS min_stock,
    (reorder_level - stock_qty)                                                 AS qty_shortage,
    unit_price,
    ROUND((reorder_level - stock_qty) * unit_price * 1.5, 2)                   AS estimated_reorder_cost,
    manufacturer,
    expiry_date,
    DATEDIFF(expiry_date, CURDATE())                                            AS days_until_expiry,
    CASE 
        WHEN stock_qty = 0                      THEN 'CRITICAL'
        WHEN stock_qty < reorder_level * 0.25   THEN 'URGENT'
        WHEN stock_qty < reorder_level * 0.5    THEN 'HIGH'
        ELSE 'MEDIUM'
    END                                                                         AS priority_level
FROM medicine
WHERE stock_qty <= reorder_level
ORDER BY 
    CASE 
        WHEN stock_qty = 0                    THEN 1
        WHEN stock_qty < reorder_level * 0.25 THEN 2
        WHEN stock_qty < reorder_level * 0.5  THEN 3
        ELSE 4
    END,
    (reorder_level - stock_qty) DESC;

-- ============================================================================
-- Q13: Revenue Rollup by Department and Month (ROLLUP)
-- Business Value: Financial summary with grand totals
-- Technique: ROLLUP, GROUPING(), conditional column labels
-- ============================================================================
SELECT 
    IFNULL(dept.dept_name, '★ TOTAL') AS department,
    IFNULL(DATE_FORMAT(i.invoice_date, '%Y-%m'), '★ ALL MONTHS') AS month,
    COUNT(DISTINCT i.invoice_id) AS invoices,
    ROUND(SUM(i.total_amount), 2) AS total_billed,
    ROUND(SUM(i.paid_amount), 2) AS total_collected,
    ROUND(SUM(i.total_amount - i.paid_amount), 2) AS outstanding
FROM invoice i
JOIN patient p ON i.patient_id = p.patient_id
LEFT JOIN appointment a ON i.appt_id = a.appt_id
LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
LEFT JOIN department dept ON d.dept_id = dept.dept_id
GROUP BY dept.dept_name, DATE_FORMAT(i.invoice_date, '%Y-%m') WITH ROLLUP;

-- ============================================================================
-- Q14: Prescription Pattern Analysis (Nested Subquery - Week 8 Analytics 5)
-- Business Value: Clinical insights, inventory planning
-- Technique: Correlated subquery for top department lookup
-- ============================================================================
SELECT 
    rx.diagnosis,
    COUNT(DISTINCT rx.rx_id)        AS prescription_count,
    COUNT(DISTINCT rx.patient_id)   AS unique_patients,
    COUNT(DISTINCT rx.doctor_id)    AS prescribing_doctors,
    MIN(rx.issued_date)             AS first_occurrence,
    MAX(rx.issued_date)             AS most_recent,
    (SELECT dept.dept_name
     FROM doctor d2 JOIN department dept ON d2.dept_id = dept.dept_id
     WHERE d2.doctor_id = (
         SELECT doctor_id FROM prescription
         WHERE diagnosis = rx.diagnosis
         GROUP BY doctor_id ORDER BY COUNT(*) DESC LIMIT 1
     )
    )                               AS primary_department
FROM prescription rx
WHERE rx.diagnosis IS NOT NULL AND rx.diagnosis != ''
GROUP BY rx.diagnosis
HAVING prescription_count >= 1
ORDER BY prescription_count DESC
LIMIT 15;

-- ============================================================================
-- Q15: Patients with Outstanding Invoices and Visit History (Nested Subquery)
-- Business Value: AR collection targeting, patient engagement
-- Technique: EXISTS subquery, nested aggregation, HAVING
-- ============================================================================
SELECT 
    p.patient_id,
    p.name,
    p.phone,
    fn_patient_age(p.dob)                                                       AS age,
    fn_patient_total_bill(p.patient_id)                                         AS outstanding_balance,
    COUNT(DISTINCT a.appt_id)                                                   AS total_appointments,
    MAX(a.appt_start)                                                           AS last_appointment,
    (SELECT COUNT(*) FROM invoice inv
     WHERE inv.patient_id = p.patient_id
       AND inv.payment_status IN ('UNPAID', 'PARTIALLY_PAID'))                  AS unpaid_invoices
FROM patient p
LEFT JOIN appointment a ON a.patient_id = p.patient_id
WHERE EXISTS (
    SELECT 1 FROM invoice i
    WHERE i.patient_id   = p.patient_id
      AND i.payment_status IN ('UNPAID', 'PARTIALLY_PAID')
)
GROUP BY p.patient_id, p.name, p.phone, p.dob
HAVING outstanding_balance > 0
ORDER BY outstanding_balance DESC;

-- ============================================================================
-- Q16: JSON vital_signs Extraction from Encounters (Advanced Feature)
-- Business Value: Clinical monitoring, vital sign trending
-- Technique: JSON column querying (JSON_EXTRACT / ->)
-- ============================================================================

SELECT 
    e.encounter_id,
    p.name AS patient_name,
    d.name AS doctor_name,
    e.encounter_date,
    e.diagnosis,
    JSON_UNQUOTE(JSON_EXTRACT(e.vital_signs, '$.bp')) AS blood_pressure,
    JSON_UNQUOTE(JSON_EXTRACT(e.vital_signs, '$.hr')) AS heart_rate,
    JSON_UNQUOTE(JSON_EXTRACT(e.vital_signs, '$.temp')) AS temperature,
    JSON_UNQUOTE(JSON_EXTRACT(e.vital_signs, '$.spo2')) AS oxygen_saturation,
    CASE 
        WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(e.vital_signs, '$.spo2')) AS UNSIGNED) < 95 THEN '⚠ LOW SpO2'
        WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(e.vital_signs, '$.hr')) AS UNSIGNED) > 100 THEN '⚠ TACHYCARDIA'
        ELSE 'Normal'
    END AS alert
FROM encounter e
JOIN patient p ON e.patient_id = p.patient_id
JOIN doctor d ON e.doctor_id = d.doctor_id
WHERE e.vital_signs IS NOT NULL
ORDER BY e.encounter_date DESC;

SELECT '✅ All 16 queries ready for execution!' AS status;
