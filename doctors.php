<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        try {
            $sql = "INSERT INTO doctor (dept_id, name, specialization, qualification, phone, email, room_no, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [
                $_POST['dept_id'],
                $_POST['name'],
                $_POST['specialization'],
                $_POST['qualification'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['room_no'],
                $_POST['status']
            ]);
            $message = "Doctor added successfully!";
        } catch (Exception $e) {
            $error = "Error adding doctor: " . $e->getMessage();
        }
    } elseif ($action === 'edit') {
        try {
            $sql = "UPDATE doctor SET dept_id=?, name=?, specialization=?, qualification=?, phone=?, email=?, room_no=?, status=? WHERE doctor_id=?";
            $db->execute($sql, [
                $_POST['dept_id'],
                $_POST['name'],
                $_POST['specialization'],
                $_POST['qualification'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['room_no'],
                $_POST['status'],
                $_POST['doctor_id']
            ]);
            $message = "Doctor updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating doctor: " . $e->getMessage();
        }
    }
}

// Get all doctors with department info
$doctors = $db->fetchAll("
    SELECT 
        d.doctor_id,
        d.name,
        d.specialization,
        d.qualification,
        d.phone,
        d.email,
        d.room_no,
        d.status,
        dept.dept_name,
        (SELECT COUNT(*) FROM appointment WHERE doctor_id = d.doctor_id) as total_appointments
    FROM doctor d
    LEFT JOIN department dept ON d.dept_id = dept.dept_id
    ORDER BY d.name
");

// Get departments for dropdown
$departments = $db->fetchAll("SELECT dept_id, dept_name FROM department ORDER BY dept_name");

// Get single doctor for editing
$edit_doctor = null;
if (isset($_GET['edit'])) {
    $edit_doctor = $db->fetchOne("SELECT * FROM doctor WHERE doctor_id = ?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors - MediCore HMS</title>
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
                <h1>Doctor Management</h1>
                <p class="subtitle">Manage medical staff and specialists</p>
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

            <!-- Add/Edit Doctor Form -->
            <div class="card">
                <div class="card-header">
                    <h2><?php
echo $edit_doctor ? 'Edit Doctor' : 'Add New Doctor'; ?></h2>
                    <?php
if ($edit_doctor): ?>
                        <a href="doctors.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
                    <?php
endif; ?>
                </div>
                <form method="POST" action="doctors.php">
                    <input type="hidden" name="action" value="<?php
echo $edit_doctor ? 'edit' : 'add'; ?>">
                    <?php
if ($edit_doctor): ?>
                        <input type="hidden" name="doctor_id" value="<?php
echo $edit_doctor['doctor_id']; ?>">
                    <?php
endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?php
echo $edit_doctor['name'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Department *</label>
                            <select name="dept_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php
foreach ($departments as $dept): ?>
                                    <option value="<?php
echo $dept['dept_id']; ?>" 
                                            <?php
echo ($edit_doctor['dept_id'] ?? '') == $dept['dept_id'] ? 'selected' : ''; ?>>
                                        <?php
echo htmlspecialchars($dept['dept_name']); ?>
                                    </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Specialization</label>
                            <input type="text" name="specialization" class="form-control" 
                                   value="<?php
echo $edit_doctor['specialization'] ?? ''; ?>"
                                   placeholder="e.g., Interventional Cardiology">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Qualification</label>
                            <input type="text" name="qualification" class="form-control" 
                                   value="<?php
echo $edit_doctor['qualification'] ?? ''; ?>"
                                   placeholder="e.g., MBBS, MD (Cardiology)">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?php
echo $edit_doctor['phone'] ?? ''; ?>"
                                   placeholder="+880-1XXX-XXXXXX">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php
echo $edit_doctor['email'] ?? ''; ?>"
                                   placeholder="doctor@hospital.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Room Number</label>
                            <input type="text" name="room_no" class="form-control" 
                                   value="<?php
echo $edit_doctor['room_no'] ?? ''; ?>"
                                   placeholder="e.g., A301">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="ACTIVE" <?php
echo ($edit_doctor['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                                <option value="ON_LEAVE" <?php
echo ($edit_doctor['status'] ?? '') === 'ON_LEAVE' ? 'selected' : ''; ?>>On Leave</option>
                                <option value="INACTIVE" <?php
echo ($edit_doctor['status'] ?? '') === 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php
echo $edit_doctor ? 'Update Doctor' : 'Add Doctor'; ?>
                    </button>
                </form>
            </div>

            <!-- Doctors List -->
            <div class="card">
                <div class="card-header">
                    <h2>All Doctors (<?php
echo count($doctors); ?>)</h2>
                    <input type="text" id="searchDoctor" class="form-control" style="max-width: 300px;" placeholder="Search doctors...">
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Specialization</th>
                                <th>Contact</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Appointments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="doctorTableBody">
                            <?php
foreach ($doctors as $doctor): ?>
                                <tr>
                                    <td><?php
echo $doctor['doctor_id']; ?></td>
                                    <td>
                                        <strong><?php
echo htmlspecialchars($doctor['name']); ?></strong><br>
                                        <span class="text-small text-muted"><?php
echo htmlspecialchars($doctor['qualification']); ?></span>
                                    </td>
                                    <td><?php
echo htmlspecialchars($doctor['dept_name']); ?></td>
                                    <td><?php
echo htmlspecialchars($doctor['specialization']); ?></td>
                                    <td>
                                        <?php
if ($doctor['phone']): ?>
                                            <?php
echo htmlspecialchars($doctor['phone']); ?><br>
                                        <?php
endif; ?>
                                        <span class="text-small"><?php
echo htmlspecialchars($doctor['email']); ?></span>
                                    </td>
                                    <td><?php
echo $doctor['room_no'] ?: 'N/A'; ?></td>
                                    <td><span class="badge badge-<?php
echo strtolower($doctor['status']); ?>"><?php echo $doctor['status']; ?></span></td>
                                    <td><?php
echo number_format($doctor['total_appointments']); ?></td>
                                    <td>
                                        <a href="doctors.php?edit=<?php
echo $doctor['doctor_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
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
        // Search functionality
        document.getElementById('searchDoctor').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#doctorTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>