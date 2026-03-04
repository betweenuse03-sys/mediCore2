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
            $sql = "INSERT INTO department (dept_name, dept_head, phone, location) VALUES (?, ?, ?, ?)";
            $db->execute($sql, [
                $_POST['dept_name'],
                $_POST['dept_head'],
                $_POST['phone'],
                $_POST['location']
            ]);
            $message = "Department added successfully!";
        } catch (Exception $e) {
            $error = "Error adding department: " . $e->getMessage();
        }
    } elseif ($action === 'edit') {
        try {
            $sql = "UPDATE department SET dept_name=?, dept_head=?, phone=?, location=? WHERE dept_id=?";
            $db->execute($sql, [
                $_POST['dept_name'],
                $_POST['dept_head'],
                $_POST['phone'],
                $_POST['location'],
                $_POST['dept_id']
            ]);
            $message = "Department updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating department: " . $e->getMessage();
        }
    }
}

// Get all departments with doctor count
$departments = $db->fetchAll("
    SELECT 
        dept.dept_id,
        dept.dept_name,
        dept.dept_head,
        dept.phone,
        dept.location,
        COUNT(DISTINCT d.doctor_id) as total_doctors,
        COUNT(DISTINCT CASE WHEN d.status = 'ACTIVE' THEN d.doctor_id END) as active_doctors
    FROM department dept
    LEFT JOIN doctor d ON d.dept_id = dept.dept_id
    GROUP BY dept.dept_id, dept.dept_name, dept.dept_head, dept.phone, dept.location
    ORDER BY dept.dept_name
");

// Get single department for editing
$edit_dept = null;
if (isset($_GET['edit'])) {
    $edit_dept = $db->fetchOne("SELECT * FROM department WHERE dept_id = ?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - MediCore HMS</title>
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
                <h1>Department Management</h1>
                <p class="subtitle">Manage hospital departments and organizational structure</p>
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

            <!-- Add/Edit Department Form -->
            <div class="card">
                <div class="card-header">
                    <h2><?php
echo $edit_dept ? 'Edit Department' : 'Add New Department'; ?></h2>
                    <?php
if ($edit_dept): ?>
                        <a href="departments.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
                    <?php
endif; ?>
                </div>
                <form method="POST" action="departments.php">
                    <input type="hidden" name="action" value="<?php
echo $edit_dept ? 'edit' : 'add'; ?>">
                    <?php
if ($edit_dept): ?>
                        <input type="hidden" name="dept_id" value="<?php
echo $edit_dept['dept_id']; ?>">
                    <?php
endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Department Name *</label>
                            <input type="text" name="dept_name" class="form-control" required 
                                   value="<?php
echo $edit_dept['dept_name'] ?? ''; ?>"
                                   placeholder="e.g., Cardiology">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Department Head</label>
                            <input type="text" name="dept_head" class="form-control" 
                                   value="<?php
echo $edit_dept['dept_head'] ?? ''; ?>"
                                   placeholder="Dr. Name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?php
echo $edit_dept['phone'] ?? ''; ?>"
                                   placeholder="+880-1XXX-XXXXXX">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" 
                               value="<?php
echo $edit_dept['location'] ?? ''; ?>"
                               placeholder="Building A, Floor 3">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php
echo $edit_dept ? 'Update Department' : 'Add Department'; ?>
                    </button>
                </form>
            </div>

            <!-- Departments List -->
            <div class="card">
                <div class="card-header">
                    <h2>All Departments (<?php
echo count($departments); ?>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Department Name</th>
                                <th>Department Head</th>
                                <th>Location</th>
                                <th>Phone</th>
                                <th>Total Doctors</th>
                                <th>Active Doctors</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
foreach ($departments as $dept): ?>
                                <tr>
                                    <td><?php
echo $dept['dept_id']; ?></td>
                                    <td><strong><?php
echo htmlspecialchars($dept['dept_name']); ?></strong></td>
                                    <td><?php
echo htmlspecialchars($dept['dept_head'] ?: 'Not Assigned'); ?></td>
                                    <td><?php
echo htmlspecialchars($dept['location'] ?: 'N/A'); ?></td>
                                    <td><?php
echo htmlspecialchars($dept['phone'] ?: 'N/A'); ?></td>
                                    <td><?php
echo number_format($dept['total_doctors']); ?></td>
                                    <td>
                                        <span class="badge badge-active">
                                            <?php
echo number_format($dept['active_doctors']); ?> Active
                                        </span>
                                    </td>
                                    <td>
                                        <a href="departments.php?edit=<?php
echo $dept['dept_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
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
</body>
</html>