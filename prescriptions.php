<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        try {
            $sql = "INSERT INTO prescription (patient_id, doctor_id, appt_id, diagnosis, instructions, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [
                $_POST['patient_id'],
                $_POST['doctor_id'],
                $_POST['appt_id'] ?: null,
                $_POST['diagnosis'],
                $_POST['instructions'],
                $_POST['status']
            ]);
            $message = "Prescription created successfully!";
        } catch (Exception $e) {
            $error = "Error creating prescription: " . $e->getMessage();
        }
    }
}

// Get all prescriptions
$prescriptions = $db->fetchAll("
    SELECT 
        rx.rx_id,
        rx.issued_date,
        rx.diagnosis,
        rx.instructions,
        rx.status,
        p.name as patient_name,
        p.phone as patient_phone,
        fn_patient_age(p.dob) as patient_age,
        d.name as doctor_name,
        dept.dept_name
    FROM prescription rx
    JOIN patient p ON rx.patient_id = p.patient_id
    JOIN doctor d ON rx.doctor_id = d.doctor_id
    JOIN department dept ON d.dept_id = dept.dept_id
    ORDER BY rx.issued_date DESC
    LIMIT 100
");

// Get patients for dropdown
$patients = $db->fetchAll("SELECT patient_id, name, phone FROM patient ORDER BY name");

// Get doctors for dropdown
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
    <title>Prescriptions - MediCore HMS</title>
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
                <h1>Prescription Management</h1>
                <p class="subtitle">Create and manage medical prescriptions</p>
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

            <!-- Create New Prescription -->
            <div class="card">
                <div class="card-header">
                    <h2>Create New Prescription</h2>
                </div>
                <form method="POST" action="prescriptions.php">
                    <input type="hidden" name="action" value="add">
                    
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
echo htmlspecialchars($doctor['name']); ?> - <?php echo $doctor['specialization']; ?>
                                    </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Appointment ID (Optional)</label>
                            <input type="number" name="appt_id" class="form-control" placeholder="Leave empty if not linked">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Diagnosis *</label>
                        <textarea name="diagnosis" class="form-control" rows="3" required placeholder="Enter diagnosis..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Instructions *</label>
                        <textarea name="instructions" class="form-control" rows="4" required placeholder="Enter medication instructions and treatment plan..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="ACTIVE">Active</option>
                            <option value="DISPENSED">Dispensed</option>
                            <option value="CANCELLED">Cancelled</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Prescription</button>
                </form>
            </div>

            <!-- Prescriptions List -->
            <div class="card">
                <div class="card-header">
                    <h2>All Prescriptions (<?php
echo count($prescriptions); ?>)</h2>
                    <input type="text" id="searchRx" class="form-control" style="max-width: 300px;" placeholder="Search prescriptions...">
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>RX ID</th>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Department</th>
                                <th>Diagnosis</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rxTableBody">
                            <?php
foreach ($prescriptions as $rx): ?>
                                <tr>
                                    <td><strong>#<?php
echo $rx['rx_id']; ?></strong></td>
                                    <td><?php
echo date('M d, Y', strtotime($rx['issued_date'])); ?><br>
                                        <span class="text-small text-muted"><?php
echo date('h:i A', strtotime($rx['issued_date'])); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php
echo htmlspecialchars($rx['patient_name']); ?></strong><br>
                                        <span class="text-small text-muted">Age: <?php
echo $rx['patient_age']; ?> | <?php echo $rx['patient_phone']; ?></span>
                                    </td>
                                    <td><?php
echo htmlspecialchars($rx['doctor_name']); ?></td>
                                    <td><?php
echo htmlspecialchars($rx['dept_name']); ?></td>
                                    <td><?php
echo htmlspecialchars(substr($rx['diagnosis'], 0, 50)) . '...'; ?></td>
                                    <td><span class="badge badge-<?php
echo strtolower($rx['status']); ?>"><?php echo $rx['status']; ?></span></td>
                                    <td>
                                        <a href="prescription_details.php?rx_id=<?php echo $rx['rx_id']; ?>" class="btn btn-primary btn-sm">Medicines</a>
                                        <button onclick="viewPrescription(<?php
echo $rx['rx_id']; ?>)" class="btn btn-secondary btn-sm">
                                            View
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

    <?php
include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
    <script>
        document.getElementById('searchRx').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#rxTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        function viewPrescription(rxId) {
            alert('Viewing prescription #' + rxId + '\nThis would open a detailed view or print dialog.');
        }
    </script>
</body>
</html>