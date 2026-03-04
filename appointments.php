<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Handle appointment scheduling using stored procedure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'schedule') {
        try {
            $sql = "CALL sp_schedule_appointment(?, ?, ?, ?, ?)";
            $stmt = $db->query($sql, [
                $_POST['patient_id'],
                $_POST['doctor_id'],
                $_POST['appt_start'],
                $_POST['appt_end'],
                $_POST['reason']
            ]);
            $result = $stmt->fetch();
            $message = "Appointment scheduled successfully! (ID: " . $result['appointment_id'] . ")";
        } catch (Exception $e) {
            $error = "Error scheduling appointment: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'update_status') {
        try {
            $sql = "UPDATE appointment SET status = ?, notes = ? WHERE appt_id = ?";
            $db->execute($sql, [$_POST['status'], $_POST['notes'], $_POST['appt_id']]);
            $message = "Appointment status updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating appointment: " . $e->getMessage();
        }
    }
}

// Get all appointments with details
$appointments = $db->fetchAll("
    SELECT 
        a.appt_id,
        a.appt_start,
        a.appt_end,
        a.status,
        a.reason,
        a.notes,
        p.name as patient_name,
        p.phone as patient_phone,
        d.name as doctor_name,
        dept.dept_name,
        d.room_no
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    JOIN doctor d ON a.doctor_id = d.doctor_id
    JOIN department dept ON d.dept_id = dept.dept_id
    ORDER BY a.appt_start DESC
    LIMIT 100
");

// Get active patients for dropdown
$patients = $db->fetchAll("SELECT patient_id, name, phone FROM patient ORDER BY name");

// Get active doctors for dropdown
$doctors = $db->fetchAll("
    SELECT d.doctor_id, d.name, d.specialization, dept.dept_name 
    FROM doctor d 
    JOIN department dept ON d.dept_id = dept.dept_id 
    WHERE d.status = 'ACTIVE' 
    ORDER BY d.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - MediCore HMS</title>
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
                <h1>Appointment Management</h1>
                <p class="subtitle">Schedule and manage patient appointments</p>
            </div>

            <?php
if ($message): ?>
                <div class="alert alert-success"><?php
echo $message; ?></div>
            <?php
endif; ?>

            <?php
if ($error): ?>
                <div class="alert alert-error"><?php
echo $error; ?></div>
            <?php
endif; ?>

            <!-- Schedule New Appointment -->
            <div class="card">
                <div class="card-header">
                    <h2>Schedule New Appointment</h2>
                </div>
                <form method="POST" action="appointments.php">
                    <input type="hidden" name="action" value="schedule">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Patient *</label>
                            <select name="patient_id" class="form-control" required>
                                <option value="">Select Patient</option>
                                <?php
foreach ($patients as $patient): ?>
                                    <option value="<?php
echo $patient['patient_id']; ?>">
                                        <?php
echo htmlspecialchars($patient['name']); ?> - <?php echo $patient['phone']; ?>
                                    </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Doctor *</label>
                            <select name="doctor_id" class="form-control" required>
                                <option value="">Select Doctor</option>
                                <?php
foreach ($doctors as $doctor): ?>
                                    <option value="<?php
echo $doctor['doctor_id']; ?>">
                                        <?php
echo htmlspecialchars($doctor['name']); ?> - <?php echo $doctor['specialization']; ?> (<?php echo $doctor['dept_name']; ?>)
                                    </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Time *</label>
                            <input type="datetime-local" name="appt_start" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">End Time *</label>
                            <input type="datetime-local" name="appt_end" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Reason for Visit *</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Enter reason for appointment..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Schedule Appointment</button>
                </form>
            </div>

            <!-- Appointments List -->
            <div class="card">
                <div class="card-header">
                    <h2>All Appointments (<?php
echo count($appointments); ?>)</h2>
                    <div class="flex gap-2">
                        <select id="filterStatus" class="form-control" style="max-width: 200px;">
                            <option value="">All Status</option>
                            <option value="SCHEDULED">Scheduled</option>
                            <option value="COMPLETED">Completed</option>
                            <option value="CANCELLED">Cancelled</option>
                            <option value="NO_SHOW">No Show</option>
                        </select>
                        <input type="text" id="searchAppt" class="form-control" style="max-width: 300px;" placeholder="Search appointments...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date & Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Department</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="apptTableBody">
                            <?php
foreach ($appointments as $appt): ?>
                                <tr data-status="<?php
echo $appt['status']; ?>">
                                    <td><?php
echo $appt['appt_id']; ?></td>
                                    <td>
                                        <strong><?php
echo date('M d, Y', strtotime($appt['appt_start'])); ?></strong><br>
                                        <span class="text-small text-muted">
                                            <?php
echo date('h:i A', strtotime($appt['appt_start'])); ?> - 
                                            <?php
echo date('h:i A', strtotime($appt['appt_end'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php
echo htmlspecialchars($appt['patient_name']); ?></strong><br>
                                        <span class="text-small text-muted"><?php
echo $appt['patient_phone']; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php
echo htmlspecialchars($appt['doctor_name']); ?></strong><br>
                                        <span class="text-small text-muted">Room: <?php
echo $appt['room_no'] ?: 'N/A'; ?></span>
                                    </td>
                                    <td><?php
echo htmlspecialchars($appt['dept_name']); ?></td>
                                    <td><?php
echo htmlspecialchars(substr($appt['reason'], 0, 50)) . '...'; ?></td>
                                    <td><span class="badge badge-<?php
echo strtolower($appt['status']); ?>"><?php echo $appt['status']; ?></span></td>
                                    <td>
                                        <button onclick="updateStatus(<?php
echo $appt['appt_id']; ?>, '<?php echo $appt['status']; ?>')" class="btn btn-secondary btn-sm">
                                            Update
                                        </button>
                                    </td>
                                </tr>
                            <?php
endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="card" style="max-width: 500px; width: 90%; margin: 0;">
            <div class="card-header">
                <h2>Update Appointment Status</h2>
                <button onclick="closeModal()" class="btn btn-secondary btn-sm">Close</button>
            </div>
            <form method="POST" action="appointments.php">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="appt_id" id="modal_appt_id">
                
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" id="modal_status" class="form-control" required>
                        <option value="SCHEDULED">Scheduled</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="CANCELLED">Cancelled</option>
                        <option value="NO_SHOW">No Show</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
        </div>
    </div>

    <?php
include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script>
        // Filter and search
        const filterStatus = document.getElementById('filterStatus');
        const searchInput = document.getElementById('searchAppt');
        const tbody = document.getElementById('apptTableBody');
        
        function filterTable() {
            const status = filterStatus.value.toLowerCase();
            const search = searchInput.value.toLowerCase();
            const rows = tbody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const rowStatus = row.dataset.status.toLowerCase();
                const text = row.textContent.toLowerCase();
                const statusMatch = !status || rowStatus === status;
                const searchMatch = !search || text.includes(search);
                row.style.display = (statusMatch && searchMatch) ? '' : 'none';
            });
        }
        
        filterStatus.addEventListener('change', filterTable);
        searchInput.addEventListener('keyup', filterTable);
        
        // Modal functions
        function updateStatus(apptId, currentStatus) {
            document.getElementById('modal_appt_id').value = apptId;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('statusModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
    </script>
</body>
</html>