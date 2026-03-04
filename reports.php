<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();

// ── NEW: Billing Summary ────────────────────────────────────────────
$billing_summary = $db->fetchOne("
    SELECT
        COUNT(*) AS total_invoices,
        SUM(total_amount) AS total_billed,
        SUM(paid_amount)  AS total_paid,
        SUM(balance_due)  AS total_outstanding,
        SUM(CASE WHEN payment_status='PAID' THEN 1 ELSE 0 END) AS paid_count,
        SUM(CASE WHEN payment_status='OVERDUE' THEN 1 ELSE 0 END) AS overdue_count
    FROM invoice WHERE payment_status != 'CANCELLED'
");

// ── NEW: Top Payers ─────────────────────────────────────────────────
$top_payers = $db->fetchAll("
    SELECT p.name AS patient_name, p.phone,
           SUM(pay.amount) AS total_paid,
           COUNT(pay.payment_id) AS payment_count,
           MAX(pay.payment_date) AS last_payment
    FROM payment pay
    JOIN patient p ON pay.patient_id = p.patient_id
    WHERE pay.status = 'COMPLETED'
    GROUP BY pay.patient_id
    ORDER BY total_paid DESC
    LIMIT 10
");

// ── NEW: Encounter Breakdown ────────────────────────────────────────
$encounter_stats = $db->fetchAll("
    SELECT encounter_type,
           COUNT(*) AS total,
           SUM(follow_up_required) AS needs_followup,
           ROUND(AVG(CASE WHEN follow_up_required THEN 1 ELSE 0 END)*100,1) AS followup_rate
    FROM encounter
    GROUP BY encounter_type ORDER BY total DESC
");

// ── NEW: Lab Order Stats ────────────────────────────────────────────
$lab_stats = $db->fetchAll("
    SELECT status,
           COUNT(*) AS total,
           SUM(CASE WHEN priority='STAT' THEN 1 ELSE 0 END) AS stat_count
    FROM lab_order
    GROUP BY status ORDER BY total DESC
");

// ── NEW: Top Prescribed Medicines ──────────────────────────────────
$top_medicines = $db->fetchAll("
    SELECT m.med_name, m.generic_name, m.form, m.stock_qty,
           COUNT(pd.detail_id) AS times_prescribed,
           SUM(pd.quantity) AS total_qty_prescribed
    FROM prescription_detail pd
    JOIN medicine m ON pd.medicine_id = m.medicine_id
    GROUP BY m.medicine_id
    ORDER BY times_prescribed DESC
    LIMIT 10
");

// ── NEW: Low Stock Alert ────────────────────────────────────────────
$low_stock = $db->fetchAll("
    SELECT med_name, generic_name, form, strength, stock_qty, reorder_level, expiry_date
    FROM medicine
    WHERE stock_qty <= reorder_level
    ORDER BY stock_qty ASC
    LIMIT 20
");


// Department Performance
$dept_performance = $db->fetchAll("
    SELECT 
        dept.dept_name AS department,
        dept.dept_head,
        COUNT(DISTINCT d.doctor_id) AS total_doctors,
        COUNT(DISTINCT CASE WHEN d.status = 'ACTIVE' THEN d.doctor_id END) AS active_doctors,
        COUNT(DISTINCT a.appt_id) AS total_appointments,
        COUNT(DISTINCT rx.rx_id) AS total_prescriptions,
        ROUND(100.0 * SUM(CASE WHEN a.status = 'COMPLETED' THEN 1 ELSE 0 END) / 
              NULLIF(COUNT(a.appt_id), 0), 2) AS completion_rate
    FROM department dept
    LEFT JOIN doctor d ON d.dept_id = dept.dept_id
    LEFT JOIN appointment a ON a.doctor_id = d.doctor_id
    LEFT JOIN prescription rx ON rx.doctor_id = d.doctor_id
    GROUP BY dept.dept_id, dept.dept_name, dept.dept_head
    ORDER BY total_appointments DESC
");

// Appointment Trends by Day of Week
$appointment_trends = $db->fetchAll("
    SELECT 
        DAYNAME(appt_start) AS day_of_week,
        COUNT(*) AS total_appointments,
        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'SCHEDULED' THEN 1 ELSE 0 END) AS scheduled,
        SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled,
        SUM(CASE WHEN status = 'NO_SHOW' THEN 1 ELSE 0 END) AS no_shows,
        ROUND(100.0 * SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) / COUNT(*), 2) AS completion_rate
    FROM appointment
    WHERE appt_start >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DAYNAME(appt_start), DAYOFWEEK(appt_start)
    ORDER BY DAYOFWEEK(appt_start)
");

// Patient Demographics
$demographics = $db->fetchAll("
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
        ROUND(AVG(fn_patient_age(p.dob)), 1) AS avg_age
    FROM patient p
    GROUP BY age_group, p.gender
    ORDER BY 
        CASE age_group
            WHEN 'Child (0-12)' THEN 1
            WHEN 'Teen (13-17)' THEN 2
            WHEN 'Young Adult (18-35)' THEN 3
            WHEN 'Adult (36-50)' THEN 4
            WHEN 'Middle Age (51-65)' THEN 5
            ELSE 6
        END,
        gender
");

// Top Performing Doctors
$top_doctors = $db->fetchAll("
    SELECT 
        d.name AS doctor_name,
        dept.dept_name AS department,
        d.specialization,
        COUNT(DISTINCT a.appt_id) AS appointments_completed,
        COUNT(DISTINCT a.patient_id) AS unique_patients,
        COUNT(DISTINCT rx.rx_id) AS prescriptions_issued
    FROM doctor d
    LEFT JOIN department dept ON d.dept_id = dept.dept_id
    LEFT JOIN appointment a ON a.doctor_id = d.doctor_id AND a.status = 'COMPLETED'
    LEFT JOIN prescription rx ON rx.doctor_id = d.doctor_id
    WHERE d.status = 'ACTIVE'
    GROUP BY d.doctor_id, d.name, dept.dept_name, d.specialization
    HAVING appointments_completed > 0
    ORDER BY appointments_completed DESC
    LIMIT 10
");

// Room Utilization
$room_stats = $db->fetchAll("
    SELECT 
        r.room_type,
        COUNT(DISTINCT r.room_id) AS total_rooms,
        COUNT(b.bed_id) AS total_beds,
        SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) AS occupied_beds,
        SUM(CASE WHEN b.status = 'AVAILABLE' THEN 1 ELSE 0 END) AS available_beds,
        ROUND(100.0 * SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) / COUNT(b.bed_id), 2) AS occupancy_rate,
        AVG(r.daily_rate) AS avg_daily_rate
    FROM room r
    LEFT JOIN bed b ON b.room_id = r.room_id
    GROUP BY r.room_type
    ORDER BY r.room_type
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - MediCore HMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php
include 'includes/header.php'; ?>
    
    <div class="container">
        <?php
include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Reports & Analytics</h1>
                <p class="subtitle">Comprehensive insights and performance metrics</p>
            </div>

            <!-- Department Performance -->
            <div class="card">
                <div class="card-header">
                    <h2>Department Performance Dashboard</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Department Head</th>
                                <th>Total Doctors</th>
                                <th>Active Doctors</th>
                                <th>Appointments</th>
                                <th>Prescriptions</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
foreach ($dept_performance as $dept): ?>
                                <tr>
                                    <td><strong><?php
echo htmlspecialchars($dept['department']); ?></strong></td>
                                    <td><?php
echo htmlspecialchars($dept['dept_head']); ?></td>
                                    <td><?php
echo number_format($dept['total_doctors']); ?></td>
                                    <td><?php
echo number_format($dept['active_doctors']); ?></td>
                                    <td><?php
echo number_format($dept['total_appointments']); ?></td>
                                    <td><?php
echo number_format($dept['total_prescriptions']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
echo $dept['completion_rate'] >= 75 ? 'completed' : 'scheduled'; ?>">
                                            <?php
echo $dept['completion_rate'] ?: '0'; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php
endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Appointment Trends -->
            <div class="card">
                <div class="card-header">
                    <h2>Appointment Trends (Last 30 Days)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Day of Week</th>
                                <th>Total</th>
                                <th>Completed</th>
                                <th>Scheduled</th>
                                <th>Cancelled</th>
                                <th>No Shows</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
foreach ($appointment_trends as $trend): ?>
                                <tr>
                                    <td><strong><?php
echo $trend['day_of_week']; ?></strong></td>
                                    <td><?php
echo number_format($trend['total_appointments']); ?></td>
                                    <td><?php
echo number_format($trend['completed']); ?></td>
                                    <td><?php
echo number_format($trend['scheduled']); ?></td>
                                    <td><?php
echo number_format($trend['cancelled']); ?></td>
                                    <td><?php
echo number_format($trend['no_shows']); ?></td>
                                    <td><?php
echo $trend['completion_rate']; ?>%</td>
                                </tr>
                            <?php
endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Performing Doctors -->
            <div class="card">
                <div class="card-header">
                    <h2>Top Performing Doctors</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Doctor</th>
                                <th>Department</th>
                                <th>Specialization</th>
                                <th>Completed Appointments</th>
                                <th>Unique Patients</th>
                                <th>Prescriptions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
$rank = 1; foreach ($top_doctors as $doctor): ?>
                                <tr>
                                    <td><strong>#<?php
echo $rank++; ?></strong></td>
                                    <td><strong><?php
echo htmlspecialchars($doctor['doctor_name']); ?></strong></td>
                                    <td><?php
echo htmlspecialchars($doctor['department']); ?></td>
                                    <td><?php
echo htmlspecialchars($doctor['specialization']); ?></td>
                                    <td><?php
echo number_format($doctor['appointments_completed']); ?></td>
                                    <td><?php
echo number_format($doctor['unique_patients']); ?></td>
                                    <td><?php
echo number_format($doctor['prescriptions_issued']); ?></td>
                                </tr>
                            <?php
endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Patient Demographics -->
            <div class="card">
                <div class="card-header">
                    <h2>Patient Demographics</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Age Group</th>
                                <th>Gender</th>
                                <th>Patient Count</th>
                                <th>Average Age</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
foreach ($demographics as $demo): ?>
                                <tr>
                                    <td><strong><?php
echo $demo['age_group']; ?></strong></td>
                                    <td><?php
echo $demo['gender']; ?></td>
                                    <td><?php
echo number_format($demo['patient_count']); ?></td>
                                    <td><?php
echo $demo['avg_age']; ?> years</td>
                                </tr>
                            <?php
endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Room Utilization -->
            <div class="card">
                <div class="card-header">
                    <h2>Room & Bed Utilization</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Room Type</th>
                                <th>Total Rooms</th>
                                <th>Total Beds</th>
                                <th>Occupied</th>
                                <th>Available</th>
                                <th>Occupancy Rate</th>
                                <th>Avg Daily Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
foreach ($room_stats as $room): ?>
                                <tr>
                                    <td><strong><?php
echo $room['room_type']; ?></strong></td>
                                    <td><?php
echo number_format($room['total_rooms']); ?></td>
                                    <td><?php
echo number_format($room['total_beds']); ?></td>
                                    <td><?php
echo number_format($room['occupied_beds']); ?></td>
                                    <td><?php
echo number_format($room['available_beds']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
echo $room['occupancy_rate'] >= 75 ? 'danger' : ($room['occupancy_rate'] >= 50 ? 'warning' : 'completed'); ?>">
                                            <?php
echo $room['occupancy_rate']; ?>%
                                        </span>
                                    </td>
                                    <td>৳<?php
echo number_format($room['avg_daily_rate'], 2); ?></td>
                                </tr>
                            <?php
endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Billing Summary -->
            <div class="card">
                <div class="card-header"><h2>Billing Summary</h2></div>
                <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);padding:1rem;">
                    <div class="stat-card" style="background:linear-gradient(135deg,#e3f2fd,#fff);">
                        <div class="stat-content"><h3>৳<?= number_format($billing_summary['total_billed'] ?? 0, 2) ?></h3><p>Total Billed</p></div>
                    </div>
                    <div class="stat-card" style="background:linear-gradient(135deg,#e8f5e9,#fff);">
                        <div class="stat-content"><h3>৳<?= number_format($billing_summary['total_paid'] ?? 0, 2) ?></h3><p>Collected</p></div>
                    </div>
                    <div class="stat-card" style="background:linear-gradient(135deg,#fff3e0,#fff);">
                        <div class="stat-content"><h3>৳<?= number_format($billing_summary['total_outstanding'] ?? 0, 2) ?></h3><p>Outstanding</p></div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <?php if (!empty($low_stock)): ?>
            <div class="card" style="border-left:4px solid var(--warning-500);">
                <div class="card-header"><h2>⚠️ Low Stock Alert (<?= count($low_stock) ?> medicines)</h2></div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Medicine</th><th>Form</th><th>Strength</th><th>Stock</th><th>Reorder Level</th><th>Expiry</th></tr></thead>
                        <tbody>
                        <?php foreach ($low_stock as $m): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($m['med_name']) ?></strong><br><span class="text-small text-muted"><?= htmlspecialchars($m['generic_name'] ?? '') ?></span></td>
                                <td><?= $m['form'] ?></td>
                                <td><?= htmlspecialchars($m['strength'] ?? '—') ?></td>
                                <td style="color:<?= $m['stock_qty'] == 0 ? 'var(--danger-500)' : 'var(--warning-500)' ?>;font-weight:700;"><?= $m['stock_qty'] ?></td>
                                <td><?= $m['reorder_level'] ?></td>
                                <td><?= $m['expiry_date'] ? date('M d, Y', strtotime($m['expiry_date'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Top Prescribed Medicines -->
            <?php if (!empty($top_medicines)): ?>
            <div class="card">
                <div class="card-header"><h2>Top Prescribed Medicines</h2></div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Medicine</th><th>Form</th><th>Current Stock</th><th>Times Prescribed</th><th>Total Qty</th></tr></thead>
                        <tbody>
                        <?php foreach ($top_medicines as $m): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($m['med_name']) ?></strong><br><span class="text-small text-muted"><?= htmlspecialchars($m['generic_name'] ?? '') ?></span></td>
                                <td><?= $m['form'] ?></td>
                                <td><?= number_format($m['stock_qty']) ?></td>
                                <td><strong><?= number_format($m['times_prescribed']) ?></strong></td>
                                <td><?= number_format($m['total_qty_prescribed']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Encounter & Lab Stats -->
            <div class="stats-grid" style="grid-template-columns:1fr 1fr;">
                <?php if (!empty($encounter_stats)): ?>
                <div class="card">
                    <div class="card-header"><h2>Encounter Types</h2></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Type</th><th>Total</th><th>Needs Follow-up</th><th>Follow-up Rate</th></tr></thead>
                            <tbody>
                            <?php foreach ($encounter_stats as $e): ?>
                                <tr>
                                    <td><span class="badge badge-<?= strtolower($e['encounter_type']) ?>"><?= $e['encounter_type'] ?></span></td>
                                    <td><strong><?= number_format($e['total']) ?></strong></td>
                                    <td><?= $e['needs_followup'] ?></td>
                                    <td><?= $e['followup_rate'] ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($lab_stats)): ?>
                <div class="card">
                    <div class="card-header"><h2>Lab Order Status</h2></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Status</th><th>Total</th><th>STAT Orders</th></tr></thead>
                            <tbody>
                            <?php foreach ($lab_stats as $l): ?>
                                <tr>
                                    <td><span class="badge badge-<?= strtolower(str_replace('_','-',$l['status'])) ?>"><?= $l['status'] ?></span></td>
                                    <td><strong><?= number_format($l['total']) ?></strong></td>
                                    <td><?= $l['stat_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <?php
include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>