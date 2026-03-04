<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        try {
            $sql = "INSERT INTO staff (dept_id, name, role, qualification, phone, email, address, date_of_birth, joining_date, salary, shift_type, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [
                !empty($_POST['dept_id']) ? $_POST['dept_id'] : null,
                $_POST['name'], $_POST['role'], $_POST['qualification'],
                $_POST['phone'], $_POST['email'], $_POST['address'],
                !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                !empty($_POST['joining_date'])  ? $_POST['joining_date']  : null,
                !empty($_POST['salary'])        ? $_POST['salary']        : null,
                $_POST['shift_type'], $_POST['status'],
            ]);
            $message = "Staff member added successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'edit') {
        try {
            $sql = "UPDATE staff SET dept_id=?, name=?, role=?, qualification=?, phone=?, email=?, address=?, date_of_birth=?, joining_date=?, salary=?, shift_type=?, status=? WHERE staff_id=?";
            $db->execute($sql, [
                !empty($_POST['dept_id']) ? $_POST['dept_id'] : null,
                $_POST['name'], $_POST['role'], $_POST['qualification'],
                $_POST['phone'], $_POST['email'], $_POST['address'],
                !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                !empty($_POST['joining_date'])  ? $_POST['joining_date']  : null,
                !empty($_POST['salary'])        ? $_POST['salary']        : null,
                $_POST['shift_type'], $_POST['status'], $_POST['staff_id'],
            ]);
            $message = "Staff record updated!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete') {
        try {
            $db->execute("DELETE FROM staff WHERE staff_id=?", [$_POST['staff_id']]);
            $message = "Staff member removed.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$staff = $db->fetchAll("
    SELECT s.*, dept.dept_name
    FROM staff s
    LEFT JOIN department dept ON s.dept_id = dept.dept_id
    ORDER BY s.name
");

$departments = $db->fetchAll("SELECT dept_id, dept_name FROM department ORDER BY dept_name");

$edit_staff = null;
if (isset($_GET['edit'])) {
    $edit_staff = $db->fetchOne("SELECT * FROM staff WHERE staff_id=?", [$_GET['edit']]);
}

// Stats
$active_count  = array_reduce($staff, fn($c, $s) => $c + ($s['status'] === 'ACTIVE' ? 1 : 0), 0);
$on_leave      = array_reduce($staff, fn($c, $s) => $c + ($s['status'] === 'ON_LEAVE' ? 1 : 0), 0);
$total_payroll = array_reduce($staff, fn($c, $s) => $c + floatval($s['salary'] ?? 0), 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>Staff Management</h1>
    <p class="subtitle">Manage nurses, technicians, administrative and support staff</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem;">
    <div class="stat-card"><div class="stat-content"><h3><?= count($staff) ?></h3><p>Total Staff</p></div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#e8f5e9,#fff);"><div class="stat-content"><h3><?= $active_count ?></h3><p>Active</p></div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#fff3e0,#fff);"><div class="stat-content"><h3><?= $on_leave ?></h3><p>On Leave</p></div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#e3f2fd,#fff);"><div class="stat-content"><h3>৳<?= number_format($total_payroll, 0) ?></h3><p>Monthly Payroll</p></div></div>
</div>

<!-- Add / Edit Form -->
<div class="card">
    <div class="card-header"><h2><?= $edit_staff ? 'Edit Staff Member' : 'Add New Staff Member' ?></h2></div>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $edit_staff ? 'edit' : 'add' ?>">
        <?php if ($edit_staff): ?>
            <input type="hidden" name="staff_id" value="<?= $edit_staff['staff_id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_staff['name'] ?? '') ?>" placeholder="Full name">
            </div>
            <div class="form-group">
                <label class="form-label">Role / Position *</label>
                <input type="text" name="role" class="form-control" required value="<?= htmlspecialchars($edit_staff['role'] ?? '') ?>" placeholder="e.g. Nurse, Receptionist">
            </div>
            <div class="form-group">
                <label class="form-label">Department</label>
                <select name="dept_id" class="form-control">
                    <option value="">No Department</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['dept_id'] ?>" <?= ($edit_staff && $edit_staff['dept_id']==$d['dept_id']) ? 'selected':'' ?>><?= htmlspecialchars($d['dept_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Qualification</label>
                <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($edit_staff['qualification'] ?? '') ?>" placeholder="e.g. BSc Nursing">
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($edit_staff['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_staff['email'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?= $edit_staff['date_of_birth'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Joining Date</label>
                <input type="date" name="joining_date" class="form-control" value="<?= $edit_staff['joining_date'] ?? date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Monthly Salary (৳)</label>
                <input type="number" step="0.01" name="salary" class="form-control" value="<?= $edit_staff['salary'] ?? '' ?>" placeholder="0.00">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Shift Type</label>
                <select name="shift_type" class="form-control">
                    <?php foreach (['MORNING','EVENING','NIGHT','ROTATING'] as $sh): ?>
                        <option value="<?= $sh ?>" <?= ($edit_staff && $edit_staff['shift_type']===$sh) ? 'selected':'' ?>><?= $sh ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <?php foreach (['ACTIVE','ON_LEAVE','INACTIVE'] as $st): ?>
                        <option value="<?= $st ?>" <?= ($edit_staff && $edit_staff['status']===$st) ? 'selected':'' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($edit_staff['address'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:1rem;">
            <button type="submit" class="btn btn-primary"><?= $edit_staff ? 'Update Staff' : 'Add Staff' ?></button>
            <?php if ($edit_staff): ?><a href="staff.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<!-- Staff Table -->
<div class="card">
    <div class="card-header">
        <h2>All Staff (<?= count($staff) ?>)</h2>
        <div style="display:flex;gap:.5rem;">
            <select id="filterStaffStatus" class="form-control" style="max-width:160px;">
                <option value="">All Status</option>
                <option value="ACTIVE">Active</option>
                <option value="ON_LEAVE">On Leave</option>
                <option value="INACTIVE">Inactive</option>
            </select>
            <select id="filterShift" class="form-control" style="max-width:160px;">
                <option value="">All Shifts</option>
                <option value="MORNING">Morning</option>
                <option value="EVENING">Evening</option>
                <option value="NIGHT">Night</option>
                <option value="ROTATING">Rotating</option>
            </select>
            <input type="text" id="searchStaff" class="form-control" style="max-width:250px;" placeholder="Search staff...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="staffTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Qualification</th>
                    <th>Phone</th>
                    <th>Shift</th>
                    <th>Salary</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($staff as $s): ?>
                <tr data-status="<?= $s['status'] ?>" data-shift="<?= $s['shift_type'] ?>">
                    <td><?= $s['staff_id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($s['name']) ?></strong><br>
                        <span class="text-small text-muted"><?= htmlspecialchars($s['email'] ?? '') ?></span>
                    </td>
                    <td><?= htmlspecialchars($s['role']) ?></td>
                    <td><?= htmlspecialchars($s['dept_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['qualification'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                    <td><?= $s['shift_type'] ?></td>
                    <td><?= $s['salary'] ? '৳'.number_format($s['salary'],2) : '—' ?></td>
                    <td><span class="badge badge-<?= strtolower(str_replace('_','-',$s['status'])) ?>"><?= $s['status'] ?></span></td>
                    <td>
                        <a href="staff.php?edit=<?= $s['staff_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Remove this staff member?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="staff_id" value="<?= $s['staff_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Del</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($staff)): ?>
                <tr><td colspan="10" class="text-center">No staff records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
</div>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
const fStatus = document.getElementById('filterStaffStatus');
const fShift  = document.getElementById('filterShift');
const fSearch = document.getElementById('searchStaff');

function applyStaffFilter() {
    const s = fStatus.value, sh = fShift.value, q = fSearch.value.toLowerCase();
    document.querySelectorAll('#staffTable tbody tr').forEach(row => {
        const ok = (!s || row.dataset.status === s) && (!sh || row.dataset.shift === sh) && (!q || row.textContent.toLowerCase().includes(q));
        row.style.display = ok ? '' : 'none';
    });
}
fStatus.addEventListener('change', applyStaffFilter);
fShift.addEventListener('change', applyStaffFilter);
fSearch.addEventListener('keyup', applyStaffFilter);
</script>
</body>
</html>
