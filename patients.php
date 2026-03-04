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
            $sql = "INSERT INTO patient (name, dob, gender, blood_group, address, phone, email, emergency_contact, emergency_name) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [
                $_POST['name'],
                $_POST['dob'],
                $_POST['gender'],
                $_POST['blood_group'],
                $_POST['address'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['emergency_contact'],
                $_POST['emergency_name']
            ]);
            $message = "Patient added successfully!";
        } catch (Exception $e) {
            $error = "Error adding patient: " . $e->getMessage();
        }
    } elseif ($action === 'edit') {
        try {
            $sql = "UPDATE patient SET name=?, dob=?, gender=?, blood_group=?, address=?, phone=?, email=?, emergency_contact=?, emergency_name=? WHERE patient_id=?";
            $db->execute($sql, [
                $_POST['name'],
                $_POST['dob'],
                $_POST['gender'],
                $_POST['blood_group'],
                $_POST['address'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['emergency_contact'],
                $_POST['emergency_name'],
                $_POST['patient_id']
            ]);
            $message = "Patient updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating patient: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        try {
            $sql = "DELETE FROM patient WHERE patient_id=?";
            $db->execute($sql, [$_POST['patient_id']]);
            $message = "Patient deleted successfully!";
        } catch (Exception $e) {
            $error = "Error deleting patient: " . $e->getMessage();
        }
    }
}

// Get all patients with age calculation
$patients = $db->fetchAll("
    SELECT 
        patient_id,
        name,
        dob,
        gender,
        blood_group,
        phone,
        email,
        fn_patient_age(dob) as age,
        emergency_contact,
        emergency_name,
        address
    FROM patient
    ORDER BY created_at DESC
");

// Get single patient for editing
$edit_patient = null;
if (isset($_GET['edit'])) {
    $edit_patient = $db->fetchOne("SELECT * FROM patient WHERE patient_id = ?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - MediCore HMS</title>
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
                <h1>Patient Management</h1>
                <p class="subtitle">Manage patient records and information</p>
            </div>

            <?php
if ($message): ?>
                <div class="alert alert-success">
                    <strong>Success!</strong> <?php
echo $message; ?>
                </div>
            <?php
endif; ?>

            <?php
if ($error): ?>
                <div class="alert alert-error">
                    <strong>Error!</strong> <?php
echo $error; ?>
                </div>
            <?php
endif; ?>

            <!-- Add/Edit Patient Form -->
            <div class="card">
                <div class="card-header">
                    <h2><?php
echo $edit_patient ? 'Edit Patient' : 'Add New Patient'; ?></h2>
                    <?php
if ($edit_patient): ?>
                        <a href="patients.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
                    <?php
endif; ?>
                </div>
                <form method="POST" action="patients.php">
                    <input type="hidden" name="action" value="<?php
echo $edit_patient ? 'edit' : 'add'; ?>">
                    <?php
if ($edit_patient): ?>
                        <input type="hidden" name="patient_id" value="<?php
echo $edit_patient['patient_id']; ?>">
                    <?php
endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?php
echo $edit_patient['name'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" name="dob" class="form-control" required 
                                   value="<?php
echo $edit_patient['dob'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Gender *</label>
                            <select name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="M" <?php
echo ($edit_patient['gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Male</option>
                                <option value="F" <?php
echo ($edit_patient['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Female</option>
                                <option value="OTHER" <?php
echo ($edit_patient['gender'] ?? '') === 'OTHER' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Blood Group</label>
                            <select name="blood_group" class="form-control">
                                <option value="">Select Blood Group</option>
                                <?php

                                $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                foreach ($blood_groups as $bg): 
                                ?>
                                    <option value="<?php
echo $bg; ?>" <?php echo ($edit_patient['blood_group'] ?? '') === $bg ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" name="phone" class="form-control" required 
                                   value="<?php
echo $edit_patient['phone'] ?? ''; ?>"
                                   placeholder="+880-1XXX-XXXXXX">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php
echo $edit_patient['email'] ?? ''; ?>"
                                   placeholder="patient@email.com">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?php
echo $edit_patient['address'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Emergency Contact Name</label>
                            <input type="text" name="emergency_name" class="form-control" 
                                   value="<?php
echo $edit_patient['emergency_name'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Emergency Contact Phone</label>
                            <input type="text" name="emergency_contact" class="form-control" 
                                   value="<?php
echo $edit_patient['emergency_contact'] ?? ''; ?>"
                                   placeholder="+880-1XXX-XXXXXX">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php
echo $edit_patient ? 'Update Patient' : 'Add Patient'; ?>
                    </button>
                </form>
            </div>

            <!-- Patients List -->
            <div class="card">
                <div class="card-header">
                    <h2>All Patients (<?php
echo count($patients); ?>)</h2>
                    <input type="text" id="searchPatient" class="form-control" style="max-width: 300px;" placeholder="Search patients...">
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Blood Group</th>
                                <th>Phone</th>
                                <th>Emergency Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="patientTableBody">
                            <?php
foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?php
echo $patient['patient_id']; ?></td>
                                    <td><strong><?php
echo htmlspecialchars($patient['name']); ?></strong></td>
                                    <td><?php
echo $patient['age']; ?> years</td>
                                    <td><?php
echo $patient['gender']; ?></td>
                                    <td><?php
echo $patient['blood_group'] ?: 'N/A'; ?></td>
                                    <td><?php
echo htmlspecialchars($patient['phone']); ?></td>
                                    <td>
                                        <?php
if ($patient['emergency_name']): ?>
                                            <?php
echo htmlspecialchars($patient['emergency_name']); ?><br>
                                            <span class="text-small text-muted"><?php
echo htmlspecialchars($patient['emergency_contact']); ?></span>
                                        <?php
else: ?>
                                            N/A
                                        <?php
endif; ?>
                                    </td>
                                    <td>
                                        <a href="patients.php?edit=<?php
echo $patient['patient_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this patient?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="patient_id" value="<?php
echo $patient['patient_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
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
        document.getElementById('searchPatient').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#patientTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>