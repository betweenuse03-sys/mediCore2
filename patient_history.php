<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Manual history entry (for non-trigger changes, e.g. admin notes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        try {
            $sql = "INSERT INTO patient_history (patient_id, field_changed, old_value, new_value, change_description, changed_by)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [
                $_POST['patient_id'],
                $_POST['field_changed'],
                $_POST['old_value'],
                $_POST['new_value'],
                $_POST['change_description'],
                $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System',
            ]);
            $message = "History entry recorded!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete') {
        try {
            $db->execute("DELETE FROM patient_history WHERE history_id=?", [$_POST['history_id']]);
            $message = "Record deleted.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$filter_patient = intval($_GET['patient_id'] ?? 0);

$where = $filter_patient ? "WHERE ph.patient_id = $filter_patient" : "";
$history = $db->fetchAll("
    SELECT ph.history_id, ph.field_changed, ph.old_value, ph.new_value,
           ph.change_description, ph.changed_by, ph.changed_at,
           p.name AS patient_name, p.phone AS patient_phone
    FROM patient_history ph
    JOIN patient p ON ph.patient_id = p.patient_id
    $where
    ORDER BY ph.changed_at DESC
    LIMIT 500
");

$patients = $db->fetchAll("SELECT patient_id, name, phone FROM patient ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient History - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
.diff-old { background:#ffebee; padding:2px 6px; border-radius:3px; font-size:.82rem; text-decoration:line-through; color:#c62828; }
.diff-new { background:#e8f5e9; padding:2px 6px; border-radius:3px; font-size:.82rem; color:#2e7d32; font-weight:600; }
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>Patient History</h1>
    <p class="subtitle">Track all changes to patient records</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Manual Entry Form -->
<div class="card">
    <div class="card-header"><h2>Add Manual History Note</h2></div>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Patient *</label>
                <select name="patient_id" class="form-control" required>
                    <option value="">Select Patient</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['patient_id'] ?>" <?= ($filter_patient==$p['patient_id'])?'selected':'' ?>>
                            <?= htmlspecialchars($p['name']) ?> — <?= $p['phone'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Field Changed</label>
                <input type="text" name="field_changed" class="form-control" placeholder="e.g. allergies, address">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Old Value</label>
                <textarea name="old_value" class="form-control" rows="2" placeholder="Previous value..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">New Value</label>
                <textarea name="new_value" class="form-control" rows="2" placeholder="New value..."></textarea>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description / Reason</label>
            <input type="text" name="change_description" class="form-control" required placeholder="Why was this changed?">
        </div>
        <button type="submit" class="btn btn-primary">Save History Note</button>
    </form>
</div>

<!-- Filter -->
<div class="card" style="padding:1rem 1.5rem;">
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <strong>Filter Patient:</strong>
        <select class="form-control" style="max-width:300px;" onchange="location.href='patient_history.php'+(this.value?'?patient_id='+this.value:'')">
            <option value="">All Patients</option>
            <?php foreach ($patients as $p): ?>
                <option value="<?= $p['patient_id'] ?>" <?= $filter_patient==$p['patient_id']?'selected':'' ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="searchHist" class="form-control" style="max-width:250px;" placeholder="Search history...">
        <span class="text-muted text-small"><?= count($history) ?> record(s)</span>
    </div>
</div>

<!-- History Table -->
<div class="card">
    <div class="card-header"><h2>Change History</h2></div>
    <div class="table-responsive">
        <table class="data-table" id="histTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Field</th>
                    <th>Old → New</th>
                    <th>Description</th>
                    <th>Changed By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= $h['history_id'] ?></td>
                    <td>
                        <strong><?= date('M d, Y', strtotime($h['changed_at'])) ?></strong><br>
                        <span class="text-small text-muted"><?= date('h:i A', strtotime($h['changed_at'])) ?></span>
                    </td>
                    <td>
                        <?= htmlspecialchars($h['patient_name']) ?><br>
                        <span class="text-small text-muted"><?= $h['patient_phone'] ?></span>
                    </td>
                    <td><strong><?= htmlspecialchars($h['field_changed'] ?? '—') ?></strong></td>
                    <td>
                        <?php if ($h['old_value']): ?><span class="diff-old"><?= htmlspecialchars(substr($h['old_value'], 0, 60)) ?></span><?php endif; ?>
                        <?php if ($h['new_value']): ?> → <span class="diff-new"><?= htmlspecialchars(substr($h['new_value'], 0, 60)) ?></span><?php endif; ?>
                        <?php if (!$h['old_value'] && !$h['new_value']): ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($h['change_description'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($h['changed_by'] ?? '—') ?></td>
                    <td>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this history entry?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="history_id" value="<?= $h['history_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Del</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <tr><td colspan="8" class="text-center">No patient history records found.</td></tr>
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
document.getElementById('searchHist').addEventListener('keyup', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#histTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
