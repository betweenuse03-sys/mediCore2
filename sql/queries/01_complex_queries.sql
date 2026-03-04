-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Complex SQL Queries
-- ============================================================================
-- Purpose: Demonstrate advanced SQL techniques including joins, subqueries,
--          aggregations, and complex filtering
-- ============================================================================

USE hospital_db;

-- ============================================================================
-- QUERY 1: Upcoming Appointments with Full Details (Multi-table JOIN)
-- ============================================================================
-- Shows patient, doctor, department info for all future appointments
-- Demonstrates: INNER JOIN across multiple tables, date filtering
-- ============================================================================

SELECT 
    a.appt_id,
    a.appt_start AS appointment_time,
    CONCAT(DATE_FORMAT(a.appt_start, '%W'), ', ', 
           DATE_FORMAT(a.appt_start, '%M %d, %Y at %h:%i %p')) AS formatted_time,
    p.name AS patient_name,
    p.phone AS patient_phone,
    fn_patient_age(p.dob) AS patient_age,
    d.name AS doctor_name,
    d.specialization,
    dept.dept_name AS department,
    a.reason,
    a.status
FROM appointment a
INNER JOIN patient p ON p.patient_id = a.patient_id
INNER JOIN doctor d ON d.doctor_id = a.doctor_id
INNER JOIN department dept ON dept.dept_id = d.dept_id
WHERE a.appt_start >= NOW()
  AND a.status = 'SCHEDULED'
ORDER BY a.appt_start
LIMIT 20;

-- ============================================================================
-- QUERY 2: Doctors with Above-Average Appointment Count (Nested Subquery)
-- ============================================================================
-- Finds doctors who have more appointments than the average doctor
-- Demonstrates: Correlated subquery, aggregate functions
-- ============================================================================

SELECT 
    d.doctor_id,
    d.name AS doctor_name,
    d.specialization,
    dept.dept_name,
    (SELECT COUNT(*) 
     FROM appointment a 
     WHERE a.doctor_id = d.doctor_id) AS total_appointments,
    ROUND((SELECT AVG(appt_count) 
           FROM (SELECT COUNT(*) AS appt_count 
                 FROM appointment 
                 GROUP BY doctor_id) AS counts), 2) AS avg_appointments
FROM doctor d
LEFT JOIN department dept ON d.dept_id = dept.dept_id
WHERE (SELECT COUNT(*) FROM appointment a WHERE a.doctor_id = d.doctor_id) > 
      (SELECT AVG(appt_count) 
       FROM (SELECT COUNT(*) AS appt_count 
             FROM appointment 
             GROUP BY doctor_id) AS x)
ORDER BY total_appointments DESC;

-- ============================================================================
-- QUERY 3: Current Bed Occupancy by Ward (Aggregation + JOIN)
-- ============================================================================
-- Shows bed utilization statistics by ward
-- Demonstrates: GROUP BY, conditional aggregation, percentage calculation
-- ============================================================================

SELECT 
    r.ward,
    r.room_type,
    COUNT(DISTINCT r.room_id) AS total_rooms,
    COUNT(b.bed_id) AS total_beds,
    SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) AS occupied_beds,
    SUM(CASE WHEN b.status = 'AVAILABLE' THEN 1 ELSE 0 END) AS available_beds,
    SUM(CASE WHEN b.status = 'MAINTENANCE' THEN 1 ELSE 0 END) AS maintenance_beds,
    ROUND(100.0 * SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) / 
          COUNT(b.bed_id), 2) AS occupancy_percentage,
    SUM(CASE WHEN b.status = 'AVAILABLE' THEN r.daily_rate ELSE 0 END) AS potential_daily_revenue
FROM room r
LEFT JOIN bed b ON b.room_id = r.room_id
GROUP BY r.ward, r.room_type
ORDER BY occupancy_percentage DESC;

-- ============================================================================
-- QUERY 4: Patients with Multiple Appointments (Self-Join Alternative)
-- ============================================================================
-- Finds patients who have scheduled 2+ appointments
-- Demonstrates: GROUP BY with HAVING clause, aggregate filtering
-- ============================================================================

SELECT 
    p.patient_id,
    p.name AS patient_name,
    p.phone,
    fn_patient_age(p.dob) AS age,
    COUNT(a.appt_id) AS total_appointments,
    SUM(CASE WHEN a.status = 'SCHEDULED' THEN 1 ELSE 0 END) AS upcoming,
    SUM(CASE WHEN a.status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN a.status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled,
    MIN(a.appt_start) AS first_appointment,
    MAX(a.appt_start) AS latest_appointment
FROM patient p
INNER JOIN appointment a ON a.patient_id = p.patient_id
GROUP BY p.patient_id, p.name, p.phone, p.dob
HAVING COUNT(a.appt_id) >= 2
ORDER BY total_appointments DESC;

-- ============================================================================
-- QUERY 5: Medicine Low Stock Alert (Comparison Query)
-- ============================================================================
-- Identifies medicines that need reordering
-- Demonstrates: WHERE with calculated fields, sorting by priority
-- ============================================================================

SELECT 
    medicine_id,
    med_name,
    category,
    stock_qty AS current_stock,
    reorder_level AS min_stock,
    (reorder_level - stock_qty) AS qty_shortage,
    unit_price,
    ROUND((reorder_level - stock_qty) * unit_price * 1.5, 2) AS estimated_reorder_cost,
    manufacturer,
    expiry_date,
    DATEDIFF(expiry_date, CURDATE()) AS days_until_expiry,
    CASE 
        WHEN stock_qty = 0 THEN 'CRITICAL - OUT OF STOCK'
        WHEN stock_qty < reorder_level * 0.25 THEN 'URGENT'
        WHEN stock_qty < reorder_level * 0.5 THEN 'HIGH'
        ELSE 'MEDIUM'
    END AS priority_level
FROM medicine
WHERE stock_qty <= reorder_level
ORDER BY 
    CASE 
        WHEN stock_qty = 0 THEN 1
        WHEN stock_qty < reorder_level * 0.25 THEN 2
        WHEN stock_qty < reorder_level * 0.5 THEN 3
        ELSE 4
    END,
    (reorder_level - stock_qty) DESC;

-- ============================================================================
-- QUERY 6: Doctor Schedule for Today (Date Functions + JOIN)
-- ============================================================================
-- Shows complete schedule for all doctors today
-- Demonstrates: Date filtering, time formatting, LEFT JOIN
-- ============================================================================

SELECT 
    d.name AS doctor,
    dept.dept_name AS department,
    d.room_no AS office,
    COUNT(a.appt_id) AS total_appointments_today,
    GROUP_CONCAT(
        CONCAT(
            DATE_FORMAT(a.appt_start, '%H:%i'), 
            ' - ', 
            p.name
        ) 
        ORDER BY a.appt_start 
        SEPARATOR ' | '
    ) AS schedule,
    SUM(TIMESTAMPDIFF(MINUTE, a.appt_start, a.appt_end)) AS total_minutes_booked
FROM doctor d
LEFT JOIN department dept ON d.dept_id = dept.dept_id
LEFT JOIN appointment a ON a.doctor_id = d.doctor_id 
    AND DATE(a.appt_start) = CURDATE()
    AND a.status = 'SCHEDULED'
LEFT JOIN patient p ON p.patient_id = a.patient_id
WHERE d.status = 'ACTIVE'
GROUP BY d.doctor_id, d.name, dept.dept_name, d.room_no
ORDER BY total_appointments_today DESC, d.name;

-- ============================================================================
-- QUERY 7: Patient Visit History Summary (Complex Aggregation)
-- ============================================================================
-- Complete patient medical engagement summary
-- Demonstrates: Multiple LEFT JOINs, complex aggregation
-- ============================================================================

SELECT 
    p.patient_id,
    p.name AS patient_name,
    p.phone,
    fn_patient_age(p.dob) AS age,
    p.blood_group,
    COUNT(DISTINCT a.appt_id) AS total_appointments,
    COUNT(DISTINCT rx.rx_id) AS total_prescriptions,
    COUNT(DISTINCT CASE WHEN a.status = 'COMPLETED' THEN a.appt_id END) AS completed_visits,
    COUNT(DISTINCT CASE WHEN a.status = 'NO_SHOW' THEN a.appt_id END) AS missed_appointments,
    MIN(a.appt_start) AS first_visit_date,
    MAX(a.appt_start) AS last_visit_date,
    DATEDIFF(CURDATE(), MAX(a.appt_start)) AS days_since_last_visit
FROM patient p
LEFT JOIN appointment a ON a.patient_id = p.patient_id
LEFT JOIN prescription rx ON rx.patient_id = p.patient_id
GROUP BY p.patient_id, p.name, p.phone, p.dob, p.blood_group
HAVING total_appointments > 0
ORDER BY last_visit_date DESC
LIMIT 25;

-- ============================================================================
-- End of Complex Queries
-- ============================================================================
-- Total Queries: 7
-- Techniques Demonstrated:
--   - Multi-table INNER/LEFT JOINs
--   - Nested subqueries
--   - Correlated subqueries
--   - Aggregate functions (COUNT, SUM, AVG, MIN, MAX)
--   - Conditional aggregation (CASE WHEN in SUM)
--   - GROUP BY with HAVING
--   - Date/time functions
--   - String functions (CONCAT, GROUP_CONCAT)
--   - Calculated fields
--   - Complex sorting
-- ============================================================================