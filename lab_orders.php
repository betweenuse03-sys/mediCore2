<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Pre-fill from encounter link
$prefill_encounter = intval($_GET['encounter_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_order') {
        try {
            $sql = "INSERT INTO lab_order (patient_id, doctor_id, encounter_id, test_name, test_type, test_category, priority, sample_type, clinical_notes, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ORDERED')";
            $db->execute($sql, [
                $_POST['patient_id'],
                $_POST['doctor_id'],
                !empty($_POST['encounter_id']) ? $_POST['encounter_id'] : null,
                $_POST['test_name'],
                $_POST['test_type'],
                $_POST['test_category'],
                $_POST['priority'],
                $_POST['sample_type'],
                $_POST['clinical_notes'],
            ]);
            $message = "Lab order placed successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'update_status') {
        try {
            $db->execute("UPDATE lab_order SET status=? WHERE order_id=?", [$_POST['status'], $_POST['order_id']]);
            $message = "Order status updated!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'add_result') {
        try {
            $sql = "INSERT INTO lab_result (order_id, test_parameter, result_value, unit, normal_range, abnormal_flag, performed_by, verified_by, comments)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [
                $_POST['order_id'],
                $_POST['test_parameter'],
                $_POST['result_value'],
                $_POST['unit'],
                $_POST['normal_range'],
                $_POST['abnormal_flag'],
                $_POST['performed_by'],
                $_POST['verified_by'],
                $_POST['comments'],
            ]);
            // Auto-update order status to COMPLETED
            $db->execute("UPDATE lab_order SET status='COMPLETED' WHERE order_id=?", [$_POST['order_id']]);
            $message = "Lab result saved and order marked as Completed!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete_order') {
        try {
            $db->execute("DELETE FROM lab_result WHERE order_id=?", [$_POST['order_id']]);
            $db->execute("DELETE FROM lab_order WHERE order_id=?",  [$_POST['order_id']]);
            $message = "Lab order deleted.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$orders = $db->fetchAll("
    SELECT lo.order_id, lo.test_name, lo.test_type, lo.test_category, lo.priority,
           lo.order_date, lo.sample_type, lo.clinical_notes, lo.status,
           p.name AS patient_name, p.phone AS patient_phone,
           d.name AS doctor_name, dept.dept_name,
           lo.encounter_id,
           COUNT(lr.result_id) AS result_count
    FROM lab_order lo
    JOIN patient p ON lo.patient_id = p.patient_id
    JOIN doctor  d ON lo.doctor_id  = d.doctor_id
    JOIN department dept ON d.dept_id = dept.dept_id
    LEFT JOIN lab_result lr ON lo.order_id = lr.order_id
    GROUP BY lo.order_id
    ORDER BY lo.order_date DESC
    LIMIT 200
");

$patients = $db->fetchAll("SELECT patient_id, name, phone FROM patient ORDER BY name");
$doctors  = $db->fetchAll("SELECT d.doctor_id, d.name, d.specialization FROM doctor d WHERE d.status='ACTIVE' ORDER BY d.name");

// Results grouped by order_id for inline display
$all_results = $db->fetchAll("SELECT * FROM lab_result ORDER BY result_date DESC");
$results_by_order = [];
foreach ($all_results as $r) {
    $results_by_order[$r['order_id']][] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lab Orders & Results - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
.result-row { background:#f8fbff; }
.flag-high    { color:#c62828; font-weight:700; }
.flag-low     { color:#e65100; font-weight:700; }
.flag-critical{ color:#880e4f; font-weight:700; background:#fce4ec; padding:1px 6px; border-radius:4px; }
.flag-normal  { color:#2e7d32; }
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>Laboratory Orders &amp; Results</h1>
    <p class="subtitle">Order lab tests and record patient results</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- New Lab Order Form -->
<div class="card">
    <div class="card-header"><h2>Place New Lab Order</h2></div>
    <form method="POST">
        <input type="hidden" name="action" value="add_order">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Patient *</label>
                <select name="patient_id" class="form-control" required>
                    <option value="">Select Patient</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['name']) ?> — <?= $p['phone'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Ordering Doctor *</label>
                <select name="doctor_id" class="form-control" required>
                    <option value="">Select Doctor</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?= $d['doctor_id'] ?>"><?= htmlspecialchars($d['name']) ?> — <?= $d['specialization'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Encounter ID (optional)</label>
                <input type="number" name="encounter_id" class="form-control" value="<?= $prefill_encounter ?: '' ?>" placeholder="Link to encounter">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Test Name *</label>
                <input type="text" name="test_name" class="form-control" required placeholder="e.g. Complete Blood Count">
            </div>
            <div class="form-group">
                <label class="form-label">Test Type</label>
                <input type="text" name="test_type" class="form-control" placeholder="e.g. Hematology">
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="test_category" class="form-control" placeholder="e.g. Routine">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Priority *</label>
                <select name="priority" class="form-control">
                    <option value="ROUTINE">Routine</option>
                    <option value="URGENT">Urgent</option>
                    <option value="STAT">STAT</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Sample Type</label>
                <input type="text" name="sample_type" class="form-control" placeholder="e.g. Blood, Urine">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Clinical Notes</label>
            <textarea name="clinical_notes" class="form-control" rows="2" placeholder="Clinical context..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Place Lab Order</button>
    </form>
</div>

<!-- Lab Orders Table -->
<div class="card">
    <div class="card-header">
        <h2>All Lab Orders (<?= count($orders) ?>)</h2>
        <div style="display:flex;gap:.5rem;">
            <select id="filterPriority" class="form-control" style="max-width:160px;">
                <option value="">All Priorities</option>
                <option value="ROUTINE">Routine</option>
                <option value="URGENT">Urgent</option>
                <option value="STAT">STAT</option>
            </select>
            <select id="filterOrderStatus" class="form-control" style="max-width:200px;">
                <option value="">All Status</option>
                <option value="ORDERED">Ordered</option>
                <option value="SAMPLE_COLLECTED">Sample Collected</option>
                <option value="IN_PROGRESS">In Progress</option>
                <option value="COMPLETED">Completed</option>
                <option value="CANCELLED">Cancelled</option>
            </select>
            <input type="text" id="searchLab" class="form-control" style="max-width:250px;" placeholder="Search...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="labTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ordered</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Test</th>
                    <th>Priority</th>
                    <th>Sample</th>
                    <th>Status</th>
                    <th>Results</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $ord): ?>
                <tr data-priority="<?= $ord['priority'] ?>" data-status="<?= $ord['status'] ?>">
                    <td><?= $ord['order_id'] ?></td>
                    <td>
                        <strong><?= date('M d, Y', strtotime($ord['order_date'])) ?></strong><br>
                        <span class="text-small text-muted"><?= date('h:i A', strtotime($ord['order_date'])) ?></span>
                    </td>
                    <td><?= htmlspecialchars($ord['patient_name']) ?><br><span class="text-small text-muted"><?= $ord['patient_phone'] ?></span></td>
                    <td><?= htmlspecialchars($ord['doctor_name']) ?><br><span class="text-small text-muted"><?= htmlspecialchars($ord['dept_name']) ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($ord['test_name']) ?></strong><br>
                        <span class="text-small text-muted"><?= htmlspecialchars($ord['test_type'] ?? '') ?> <?= htmlspecialchars($ord['test_category'] ?? '') ?></span>
                    </td>
                    <td>
                        <span class="badge badge-<?= strtolower($ord['priority']) === 'stat' ? 'cancelled' : (strtolower($ord['priority']) === 'urgent' ? 'in_progress' : 'scheduled') ?>">
                            <?= $ord['priority'] ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($ord['sample_type'] ?? '—') ?></td>
                    <td>
                        <!-- Inline status update -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                            <select name="status" class="form-control" style="font-size:.78rem;padding:3px 6px;" onchange="this.form.submit()">
                                <?php foreach (['ORDERED','SAMPLE_COLLECTED','IN_PROGRESS','COMPLETED','CANCELLED'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $ord['status']===$st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <span style="font-weight:700;color:var(--primary-500)"><?= $ord['result_count'] ?></span> result(s)<br>
                        <button onclick="toggleResults(<?= $ord['order_id'] ?>)" class="btn btn-secondary btn-sm" style="margin-top:3px;">
                            View/Add
                        </button>
                    </td>
                    <td>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this order and all results?')">
                            <input type="hidden" name="action" value="delete_order">
                            <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Del</button>
                        </form>
                    </td>
                </tr>
                <!-- Inline Results Panel -->
                <tr id="results_panel_<?= $ord['order_id'] ?>" style="display:none;" class="result-row">
                    <td colspan="10" style="padding:1rem 1.5rem;">
                        <strong>Results for Order #<?= $ord['order_id'] ?> — <?= htmlspecialchars($ord['test_name']) ?></strong>
                        <?php if (!empty($results_by_order[$ord['order_id']])): ?>
                            <table class="data-table" style="margin-top:.5rem;background:white;">
                                <thead><tr><th>Parameter</th><th>Value</th><th>Unit</th><th>Normal Range</th><th>Flag</th><th>Date</th><th>By</th><th>Comments</th></tr></thead>
                                <tbody>
                                <?php foreach ($results_by_order[$ord['order_id']] as $res): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($res['test_parameter'] ?? '') ?></td>
                                        <td><strong><?= htmlspecialchars($res['result_value'] ?? '') ?></strong></td>
                                        <td><?= htmlspecialchars($res['unit'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($res['normal_range'] ?? '') ?></td>
                                        <td><span class="flag-<?= strtolower($res['abnormal_flag']) ?>"><?= $res['abnormal_flag'] ?></span></td>
                                        <td><?= date('M d, Y h:i A', strtotime($res['result_date'])) ?></td>
                                        <td><?= htmlspecialchars($res['performed_by'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($res['comments'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color:var(--gray-500);margin:.5rem 0;">No results recorded yet.</p>
                        <?php endif; ?>

                        <!-- Add Result Form -->
                        <form method="POST" style="margin-top:1rem;background:var(--gray-100);padding:1rem;border-radius:var(--radius-md);">
                            <input type="hidden" name="action" value="add_result">
                            <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                            <strong style="display:block;margin-bottom:.5rem;">+ Add Result Parameter</strong>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Parameter *</label>
                                    <input type="text" name="test_parameter" class="form-control" required placeholder="e.g. WBC Count">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Result Value *</label>
                                    <input type="text" name="result_value" class="form-control" required placeholder="e.g. 7.2">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Unit</label>
                                    <input type="text" name="unit" class="form-control" placeholder="e.g. x10³/μL">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Normal Range</label>
                                    <input type="text" name="normal_range" class="form-control" placeholder="e.g. 4.0–11.0">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Flag</label>
                                    <select name="abnormal_flag" class="form-control">
                                        <option value="NORMAL">Normal</option>
                                        <option value="HIGH">High</option>
                                        <option value="LOW">Low</option>
                                        <option value="CRITICAL">Critical</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Performed By</label>
                                    <input type="text" name="performed_by" class="form-control" placeholder="Lab technician">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Verified By</label>
                                    <input type="text" name="verified_by" class="form-control" placeholder="Supervisor">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Comments</label>
                                <input type="text" name="comments" class="form-control" placeholder="Optional notes">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Save Result</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
                <tr><td colspan="10" class="text-center">No lab orders found.</td></tr>
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
function toggleResults(orderId) {
    const panel = document.getElementById('results_panel_' + orderId);
    panel.style.display = panel.style.display === 'none' ? '' : 'none';
}

// Filters
const filterPriority = document.getElementById('filterPriority');
const filterOrderStatus = document.getElementById('filterOrderStatus');
const searchLab = document.getElementById('searchLab');

function applyFilters() {
    const p = filterPriority.value;
    const s = filterOrderStatus.value;
    const q = searchLab.value.toLowerCase();
    document.querySelectorAll('#labTable tbody tr').forEach(row => {
        if (row.id && row.id.startsWith('results_panel_')) return; // skip result panels
        const rowP = row.dataset.priority || '';
        const rowS = row.dataset.status || '';
        const text = row.textContent.toLowerCase();
        const ok = (!p || rowP === p) && (!s || rowS === s) && (!q || text.includes(q));
        row.style.display = ok ? '' : 'none';
        // also hide corresponding results panel when hiding row
        const oid = row.querySelector('[id]');
    });
}
filterPriority.addEventListener('change', applyFilters);
filterOrderStatus.addEventListener('change', applyFilters);
searchLab.addEventListener('keyup', applyFilters);
</script>
</body>
</html>
