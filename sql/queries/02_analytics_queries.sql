-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Analytics and Reporting Queries
-- ============================================================================
-- Purpose: Business intelligence and operational reporting queries
-- Focus: Trends, patterns, KPIs, and decision support
-- ============================================================================

USE hospital_db;

-- ============================================================================
-- ANALYTICS QUERY 1: Appointment Trends by Day of Week
-- ============================================================================
-- Shows which days of the week are busiest for appointments
-- Business Value: Helps with staff scheduling and resource allocation
-- ============================================================================

SELECT 
    DAYNAME(appt_start) AS day_of_week,
    DAYOFWEEK(appt_start) AS day_number,
    COUNT(*) AS total_appointments,
    SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN status = 'SCHEDULED' THEN 1 ELSE 0 END) AS scheduled,
    SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled,
    SUM(CASE WHEN status = 'NO_SHOW' THEN 1 ELSE 0 END) AS no_shows,
    ROUND(100.0 * SUM(CASE WHEN status = 'NO_SHOW' THEN 1 ELSE 0 END) / 
          COUNT(*), 2) AS no_show_rate,
    ROUND(100.0 * SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) / 
          COUNT(*), 2) AS completion_rate
FROM appointment
WHERE appt_start >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DAYNAME(appt_start), DAYOFWEEK(appt_start)
ORDER BY day_number;

-- ============================================================================
-- ANALYTICS QUERY 2: Top Performing Doctors (Monthly Ranking)
-- ============================================================================
-- Ranks doctors by number of completed appointments this month
-- Business Value: Performance measurement, workload distribution
-- Demonstrates: Window functions (RANK), date filtering
-- ============================================================================

SELECT 
    doctor_id,
    doctor_name,
    department,
    appointments_completed,
    total_patients_seen,
    avg_appointment_duration_mins,
    RANK() OVER (ORDER BY appointments_completed DESC) AS performance_rank,
    DENSE_RANK() OVER (PARTITION BY department ORDER BY appointments_completed DESC) AS dept_rank
FROM (
    SELECT 
        d.doctor_id,
        d.name AS doctor_name,
        dept.dept_name AS department,
        COUNT(a.appt_id) AS appointments_completed,
        COUNT(DISTINCT a.patient_id) AS total_patients_seen,
        ROUND(AVG(TIMESTAMPDIFF(MINUTE, a.appt_start, a.appt_end)), 2) AS avg_appointment_duration_mins
    FROM doctor d
    LEFT JOIN department dept ON d.dept_id = dept.dept_id
    LEFT JOIN appointment a ON a.doctor_id = d.doctor_id
        AND a.status = 'COMPLETED'
        AND a.appt_start >= DATE_FORMAT(CURDATE(), '%Y-%m-01')  -- This month
    WHERE d.status = 'ACTIVE'
    GROUP BY d.doctor_id, d.name, dept.dept_name
) AS doctor_stats
WHERE appointments_completed > 0
ORDER BY performance_rank;

-- ============================================================================
-- ANALYTICS QUERY 3: Department Performance Dashboard
-- ============================================================================
-- Comprehensive department-level metrics
-- Business Value: Strategic planning, resource allocation
-- ============================================================================

SELECT 
    dept.dept_name AS department,
    dept.dept_head AS department_head,
    COUNT(DISTINCT d.doctor_id) AS total_doctors,
    COUNT(DISTINCT CASE WHEN d.status = 'ACTIVE' THEN d.doctor_id END) AS active_doctors,
    COUNT(DISTINCT a.appt_id) AS total_appointments_all_time,
    COUNT(DISTINCT CASE 
        WHEN a.appt_start >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        THEN a.appt_id 
    END) AS appointments_last_30_days,
    COUNT(DISTINCT rx.rx_id) AS total_prescriptions,
    COUNT(DISTINCT a.patient_id) AS unique_patients_served,
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, a.appt_start, a.appt_end)), 2) AS avg_appt_duration,
    -- Appointment status breakdown
    ROUND(100.0 * SUM(CASE WHEN a.status = 'COMPLETED' THEN 1 ELSE 0 END) / 
          NULLIF(COUNT(a.appt_id), 0), 2) AS completion_rate,
    ROUND(100.0 * SUM(CASE WHEN a.status = 'NO_SHOW' THEN 1 ELSE 0 END) / 
          NULLIF(COUNT(a.appt_id), 0), 2) AS no_show_rate
FROM department dept
LEFT JOIN doctor d ON d.dept_id = dept.dept_id
LEFT JOIN appointment a ON a.doctor_id = d.doctor_id
LEFT JOIN prescription rx ON rx.doctor_id = d.doctor_id
GROUP BY dept.dept_id, dept.dept_name, dept.dept_head
ORDER BY appointments_last_30_days DESC;

-- ============================================================================
-- ANALYTICS QUERY 4: Patient Demographics Analysis
-- ============================================================================
-- Shows patient distribution by age group and gender
-- Business Value: Marketing, service planning, population health
-- ============================================================================

SELECT 
    age_group,
    gender,
    patient_count,
    ROUND(100.0 * patient_count / SUM(patient_count) OVER (), 2) AS percentage_of_total,
    avg_age,
    total_appointments,
    ROUND(total_appointments * 1.0 / patient_count, 2) AS avg_appointments_per_patient
FROM (
    SELECT 
        CASE 
            WHEN fn_patient_age(p.dob) < 13 THEN 'Child (0-12)'
            WHEN fn_patient_age(p.dob) BETWEEN 13 AND 17 THEN 'Teen (13-17)'
            WHEN fn_patient_age(p.dob) BETWEEN 18 AND 35 THEN 'Young Adult (18-35)'
            WHEN fn_patient_age(p.dob) BETWEEN 36 AND 50 THEN 'Adult (36-50)'
            WHEN fn_patient_age(p.dob) BETWEEN 51 AND 65 THEN 'Middle Age (51-65)'
            ELSE 'Senior (65+)'
        END AS age_group,
        p.gender,
        COUNT(DISTINCT p.patient_id) AS patient_count,
        ROUND(AVG(fn_patient_age(p.dob)), 1) AS avg_age,
        COUNT(a.appt_id) AS total_appointments
    FROM patient p
    LEFT JOIN appointment a ON a.patient_id = p.patient_id
    GROUP BY age_group, p.gender
) AS demographics
ORDER BY 
    CASE age_group
        WHEN 'Child (0-12)' THEN 1
        WHEN 'Teen (13-17)' THEN 2
        WHEN 'Young Adult (18-35)' THEN 3
        WHEN 'Adult (36-50)' THEN 4
        WHEN 'Middle Age (51-65)' THEN 5
        ELSE 6
    END,
    gender;

-- ============================================================================
-- ANALYTICS QUERY 5: Prescription Patterns Analysis
-- ============================================================================
-- Analyzes prescription trends by diagnosis
-- Business Value: Clinical insights, inventory planning
-- ============================================================================

SELECT 
    rx.diagnosis,
    COUNT(DISTINCT rx.rx_id) AS prescription_count,
    COUNT(DISTINCT rx.patient_id) AS unique_patients,
    COUNT(DISTINCT rx.doctor_id) AS prescribing_doctors,
    MIN(rx.issued_date) AS first_occurrence,
    MAX(rx.issued_date) AS most_recent,
    ROUND(AVG(DATEDIFF(CURDATE(), rx.issued_date)), 2) AS avg_days_ago,
    -- Top prescribing department
    (SELECT dept.dept_name 
     FROM doctor d2 
     JOIN department dept ON d2.dept_id = dept.dept_id
     WHERE d2.doctor_id = (
         SELECT doctor_id 
         FROM prescription 
         WHERE diagnosis = rx.diagnosis 
         GROUP BY doctor_id 
         ORDER BY COUNT(*) DESC 
         LIMIT 1
     )
    ) AS primary_treating_department
FROM prescription rx
WHERE rx.diagnosis IS NOT NULL 
  AND rx.diagnosis != ''
GROUP BY rx.diagnosis
HAVING prescription_count >= 2
ORDER BY prescription_count DESC
LIMIT 15;

-- ============================================================================
-- ANALYTICS QUERY 6: Resource Utilization - Room Revenue Potential
-- ============================================================================
-- Calculates potential and actual revenue from room inventory
-- Business Value: Financial planning, pricing strategy
-- ============================================================================

SELECT 
    r.room_type,
    COUNT(DISTINCT r.room_id) AS total_rooms,
    COUNT(b.bed_id) AS total_beds,
    SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) AS occupied_beds,
    ROUND(100.0 * SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) / 
          COUNT(b.bed_id), 2) AS occupancy_rate,
    AVG(r.daily_rate) AS avg_daily_rate,
    -- Revenue calculations
    SUM(CASE WHEN b.status = 'OCCUPIED' THEN r.daily_rate ELSE 0 END) AS daily_revenue_actual,
    SUM(r.daily_rate) AS daily_revenue_potential,
    SUM(r.daily_rate) * 30 AS monthly_revenue_potential,
    SUM(CASE WHEN b.status = 'OCCUPIED' THEN r.daily_rate ELSE 0 END) * 30 AS monthly_revenue_projected,
    SUM(r.daily_rate) - SUM(CASE WHEN b.status = 'OCCUPIED' THEN r.daily_rate ELSE 0 END) AS daily_revenue_loss
FROM room r
LEFT JOIN bed b ON b.room_id = r.room_id
GROUP BY r.room_type
ORDER BY daily_revenue_potential DESC;

-- ============================================================================
-- ANALYTICS QUERY 7: Appointment Efficiency Metrics
-- ============================================================================
-- Measures operational efficiency of appointment system
-- Business Value: Process improvement, patient satisfaction
-- ============================================================================

SELECT 
    'Overall Metrics' AS metric_category,
    COUNT(*) AS total_appointments,
    SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN status = 'SCHEDULED' THEN 1 ELSE 0 END) AS upcoming,
    SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled,
    SUM(CASE WHEN status = 'NO_SHOW' THEN 1 ELSE 0 END) AS no_shows,
    -- Efficiency rates
    ROUND(100.0 * SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) / 
          COUNT(*), 2) AS completion_rate,
    ROUND(100.0 * SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) / 
          COUNT(*), 2) AS cancellation_rate,
    ROUND(100.0 * SUM(CASE WHEN status = 'NO_SHOW' THEN 1 ELSE 0 END) / 
          COUNT(*), 2) AS no_show_rate,
    -- Time metrics
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, appt_start, appt_end)), 2) AS avg_duration_minutes,
    ROUND(AVG(TIMESTAMPDIFF(DAY, created_at, appt_start)), 2) AS avg_lead_time_days
FROM appointment

UNION ALL

SELECT 
    'Last 30 Days' AS metric_category,
    COUNT(*),
    SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END),
    SUM(CASE WHEN status = 'SCHEDULED' THEN 1 ELSE 0 END),
    SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END),
    SUM(CASE WHEN status = 'NO_SHOW' THEN 1 ELSE 0 END),
    ROUND(100.0 * SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) / 
          COUNT(*), 2),
    ROUND(100.0 * SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) / 
          COUNT(*), 2),
    ROUND(100.0 * SUM(CASE WHEN status = 'NO_SHOW' THEN 1 ELSE 0 END) / 
          COUNT(*), 2),
    ROUND(AVG(TIMESTAMPDIFF(MINUTE, appt_start, appt_end)), 2),
    ROUND(AVG(TIMESTAMPDIFF(DAY, created_at, appt_start)), 2)
FROM appointment
WHERE appt_start >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);

-- ============================================================================
-- End of Analytics Queries
-- ============================================================================
-- Total Analytics Queries: 7 (meets Week 8 requirement of 2+ analytics)
-- 
-- Business Intelligence Value:
--   1. Appointment Trends - Operational planning
--   2. Doctor Performance - HR and quality metrics
--   3. Department Dashboard - Strategic overview
--   4. Patient Demographics - Market analysis
--   5. Prescription Patterns - Clinical insights
--   6. Room Revenue - Financial planning
--   7. Efficiency Metrics - Process improvement
-- 
-- Techniques Used:
--   - Window functions (RANK, DENSE_RANK, OVER)
--   - Time-series analysis
--   - Percentage calculations
--   - Multi-level aggregations
--   - Revenue projections
--   - Efficiency KPIs
-- ============================================================================