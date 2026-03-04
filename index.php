<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();

// Flash message
$flash_error = '';
if (isset($_SESSION['flash_error'])) {
    $flash_error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Get dashboard statistics
$stats = [
    'total_patients' => $db->fetchOne("SELECT COUNT(*) as count FROM patient")['count'] ?? 0,
    'total_doctors' => $db->fetchOne("SELECT COUNT(*) as count FROM doctor WHERE status = 'ACTIVE'")['count'] ?? 0,
    'today_appointments' => $db->fetchOne("SELECT COUNT(*) as count FROM appointment WHERE DATE(appt_start) = CURDATE() AND status = 'SCHEDULED'")['count'] ?? 0,
    'available_beds' => $db->fetchOne("SELECT COUNT(*) as count FROM bed WHERE status = 'AVAILABLE'")['count'] ?? 0,
    'open_invoices'  => $db->fetchOne("SELECT COUNT(*) as count FROM invoice WHERE payment_status IN ('UNPAID','OVERDUE')")['count'] ?? 0,
    'pending_labs'   => $db->fetchOne("SELECT COUNT(*) as count FROM lab_order WHERE status IN ('ORDERED','SAMPLE_COLLECTED','IN_PROGRESS')")['count'] ?? 0,
    'low_stock_meds' => $db->fetchOne("SELECT COUNT(*) as count FROM medicine WHERE stock_qty <= reorder_level")['count'] ?? 0,
    'active_staff'   => $db->fetchOne("SELECT COUNT(*) as count FROM staff WHERE status = 'ACTIVE'")['count'] ?? 0,
];

// Get today's appointments
$today_appointments = $db->fetchAll("
    SELECT 
        a.appt_id,
        a.appt_start,
        p.name as patient_name,
        d.name as doctor_name,
        dept.dept_name,
        a.reason,
        a.status
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    JOIN doctor d ON a.doctor_id = d.doctor_id
    JOIN department dept ON d.dept_id = dept.dept_id
    WHERE DATE(a.appt_start) = CURDATE()
    ORDER BY a.appt_start
    LIMIT 10
");

// Get recent prescriptions
$recent_prescriptions = $db->fetchAll("
    SELECT 
        rx.rx_id,
        p.name as patient_name,
        d.name as doctor_name,
        rx.diagnosis,
        rx.issued_date,
        rx.status
    FROM prescription rx
    JOIN patient p ON rx.patient_id = p.patient_id
    JOIN doctor d ON rx.doctor_id = d.doctor_id
    ORDER BY rx.issued_date DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCore HMS - Hospital Management System</title>
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
                <h1>Dashboard</h1>
                <p class="subtitle">Welcome to MediCore Hospital Management System</p>
            </div>

            <?php if ($flash_error): ?>
                <div class="flash-error"><strong>⚠️ Access Denied:</strong> <?= htmlspecialchars($flash_error) ?></div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card patients">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?php
echo number_format($stats['total_patients']); ?></h3>
                        <p>Total Patients</p>
                    </div>
                </div>

                <div class="stat-card doctors">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?php
echo number_format($stats['total_doctors']); ?></h3>
                        <p>Active Doctors</p>
                    </div>
                </div>

                <div class="stat-card appointments">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?php
echo number_format($stats['today_appointments']); ?></h3>
                        <p>Today's Appointments</p>
                    </div>
                </div>

                <div class="stat-card beds">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?php
echo number_format($stats['available_beds']); ?></h3>
                        <p>Available Beds</p>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="card">
                <div class="card-header">
                    <h2>Today's Appointments</h2>
                    <a href="appointments.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Department</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
if (empty($today_appointments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No appointments scheduled for today</td>
                                </tr>
                            <?php
else: ?>
                                <?php
foreach ($today_appointments as $appt): ?>
                                    <tr>
                                        <td><?php
echo date('h:i A', strtotime($appt['appt_start'])); ?></td>
                                        <td><?php
echo htmlspecialchars($appt['patient_name']); ?></td>
                                        <td><?php
echo htmlspecialchars($appt['doctor_name']); ?></td>
                                        <td><?php
echo htmlspecialchars($appt['dept_name']); ?></td>
                                        <td><?php
echo htmlspecialchars(substr($appt['reason'], 0, 30)) . '...'; ?></td>
                                        <td><span class="badge badge-<?php
echo strtolower($appt['status']); ?>"><?php echo $appt['status']; ?></span></td>
                                    </tr>
                                <?php
endforeach; ?>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Prescriptions -->
            <div class="card">
                <div class="card-header">
                    <h2>Recent Prescriptions</h2>
                    <a href="prescriptions.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Diagnosis</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
if (empty($recent_prescriptions)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No recent prescriptions</td>
                                </tr>
                            <?php
else: ?>
                                <?php
foreach ($recent_prescriptions as $rx): ?>
                                    <tr>
                                        <td><?php
echo date('M d, Y', strtotime($rx['issued_date'])); ?></td>
                                        <td><?php
echo htmlspecialchars($rx['patient_name']); ?></td>
                                        <td><?php
echo htmlspecialchars($rx['doctor_name']); ?></td>
                                        <td><?php
echo htmlspecialchars(substr($rx['diagnosis'], 0, 40)) . '...'; ?></td>
                                        <td><span class="badge badge-<?php
echo strtolower($rx['status']); ?>"><?php echo $rx['status']; ?></span></td>
                                    </tr>
                                <?php
endforeach; ?>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <?php
include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>