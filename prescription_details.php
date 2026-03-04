<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Pre-filter by prescription if coming from prescriptions page
$filter_rx = intval($_GET['rx_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        try {
            $sql = "INSERT INTO prescription_detail (rx_id, medicine_id, dosage, frequency, duration, quantity, instructions, dispensed_qty)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [
                $_POST['rx_id'],
                $_POST['medicine_id'],
                $_POST['dosage'],
                $_POST['frequency'],
                $_POST['duration'],
                intval($_POST['quantity']),
                $_POST['instructions'],
                intval($_POST['dispensed_qty'] ?? 0),
            ]);
            // Auto-update prescription status if all quantities dispensed
            $message = "Medicine added to prescription!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'edit') {
        try {
            $sql = "UPDATE prescription_detail SET medicine_id=?, dosage=?, frequency=?, duration=?, quantity=?, instructions=?, dispensed_qty=? WHERE detail_id=?";
            $db->execute($sql, [
                $_POST['medicine_id'],
                $_POST['dosage'],
                $_POST['frequency'],
                $_POST['duration'],
                intval($_POST['quantity']),
                $_POST['instructions'],
                intval($_POST['dispensed_qty']),
                $_POST['detail_id'],
            ]);
            $message = "Prescription detail updated!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'dispense') {
        try {
            $detail = $db->fetchOne("SELECT * FROM prescription_detail WHERE detail_id=?", [$_POST['detail_id']]);
            if ($detail) {
                $new_dispensed = intval($detail['dispensed_qty']) + intval($_POST['dispense_qty']);
                if ($new_dispensed > intval($detail['quantity'])) {
                    $error = "Cannot dispense more than prescribed quantity (" . $detail['quantity'] . ").";
                } else {
                    $db->execute("UPDATE prescription_detail SET dispensed_qty=? WHERE detail_id=?", [$new_dispensed, $_POST['detail_id']]);
                    // Deduct from stock
                    $db->execute("UPDATE medicine SET stock_qty = stock_qty - ? WHERE medicine_id=?", [$_POST['dispense_qty'], $detail['medicine_id']]);
                    // Update prescription status
                    $rx_id = $detail['rx_id'];
                    $all_details = $db->fetchAll("SELECT quantity, dispensed_qty FROM prescription_detail WHERE rx_id=?", [$rx_id]);
                    $all_dispensed = !empty($all_details) && array_reduce($all_details, fn($ok, $d) => $ok && intval($d['dispensed_qty']) >= intval($d['quantity']), true);
                    $any_dispensed = array_reduce($all_details, fn($ok, $d) => $ok || intval($d['dispensed_qty']) > 0, false);
                    $rx_status = $all_dispensed ? 'DISPENSED' : ($any_dispensed ? 'PARTIALLY_DISPENSED' : 'ACTIVE');
                    $db->execute("UPDATE prescription SET status=? WHERE rx_id=?", [$rx_status, $rx_id]);
                    $message = "Dispensed " . $_POST['dispense_qty'] . " unit(s). Stock updated.";
                }
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete') {
        try {
            $db->execute("DELETE FROM prescription_detail WHERE detail_id=?", [$_POST['detail_id']]);
            $message = "Medicine removed from prescription.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch details (with optional filter)
$where  = $filter_rx ? "WHERE pd.rx_id = $filter_rx" : "";
$details = $db->fetchAll("
    SELECT pd.detail_id, pd.rx_id, pd.dosage, pd.frequency, pd.duration,
           pd.quantity, pd.dispensed_qty, pd.instructions,
           m.med_name, m.generic_name, m.strength, m.form, m.unit_price, m.stock_qty,
           p.name AS patient_name,
           d.name AS doctor_name,
           rx.issued_date, rx.status AS rx_status, rx.diagnosis
    FROM prescription_detail pd
    JOIN medicine m ON pd.medicine_id = m.medicine_id
    JOIN prescription rx ON pd.rx_id = rx.rx_id
    JOIN patient p ON rx.patient_id = p.patient_id
    JOIN doctor d ON rx.doctor_id = d.doctor_id
    $where
    ORDER BY pd.rx_id DESC, pd.detail_id
    LIMIT 500
");

// Group details by rx_id
$by_rx = [];
foreach ($details as $det) {
    $by_rx[$det['rx_id']][] = $det;
}

$prescriptions = $db->fetchAll("
    SELECT rx.rx_id, rx.issued_date, rx.diagnosis, rx.status,
           p.name AS patient_name, d.name AS doctor_name
    FROM prescription rx
    JOIN patient p ON rx.patient_id = p.patient_id
    JOIN doctor d ON rx.doctor_id = d.doctor_id
    ORDER BY rx.issued_date DESC
    LIMIT 200
");

$medicines = $db->fetchAll("SELECT medicine_id, med_name, generic_name, strength, form, unit_price, stock_qty FROM medicine WHERE stock_qty > 0 ORDER BY med_name");
$all_medicines = $db->fetchAll("SELECT medicine_id, med_name, generic_name, strength, form, unit_price, stock_qty FROM medicine ORDER BY med_name");

$edit_detail = null;
if (isset($_GET['edit'])) {
    $edit_detail = $db->fetchOne("SELECT * FROM prescription_detail WHERE detail_id=?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prescription Details - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
.rx-group-header { background: linear-gradient(135deg,#e3f2fd,#f5f5f5); font-weight: 700; }
.dispense-bar { height: 6px; border-radius: 3px; background: var(--gray-200); margin-top: 4px; }
.dispense-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg,#2e7d32,#66bb6a); transition: width .4s; }
.low-stock { color: var(--danger-500); font-weight:700; }
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>Prescription Details &amp; Dispensing</h1>
    <p class="subtitle">Add medicines to prescriptions and track dispensing</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Add Medicine to Prescription -->
<div class="card">
    <div class="card-header"><h2><?= $edit_detail ? 'Edit Prescription Line' : 'Add Medicine to Prescription' ?></h2></div>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $edit_detail ? 'edit' : 'add' ?>">
        <?php if ($edit_detail): ?>
            <input type="hidden" name="detail_id" value="<?= $edit_detail['detail_id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <?php if (!$edit_detail): ?>
            <div class="form-group">
                <label class="form-label">Prescription *</label>
                <select name="rx_id" class="form-control" required>
                    <option value="">Select Prescription</option>
                    <?php foreach ($prescriptions as $rx): ?>
                        <option value="<?= $rx['rx_id'] ?>" <?= ($filter_rx == $rx['rx_id']) ? 'selected':'' ?>>
                            #<?= $rx['rx_id'] ?> — <?= htmlspecialchars($rx['patient_name']) ?> — Dr. <?= htmlspecialchars($rx['doctor_name']) ?> (<?= date('M d, Y', strtotime($rx['issued_date'])) ?>) [<?= $rx['status'] ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label">Medicine *</label>
                <select name="medicine_id" class="form-control" required id="medSelect" onchange="updateMedInfo(this)">
                    <option value="">Select Medicine</option>
                    <?php foreach ($all_medicines as $med): ?>
                        <option value="<?= $med['medicine_id'] ?>"
                            data-price="<?= $med['unit_price'] ?>"
                            data-stock="<?= $med['stock_qty'] ?>"
                            <?= ($edit_detail && $edit_detail['medicine_id']==$med['medicine_id']) ? 'selected':'' ?>>
                            <?= htmlspecialchars($med['med_name']) ?> <?= htmlspecialchars($med['strength'] ?? '') ?> (<?= $med['form'] ?>) — Stock: <?= $med['stock_qty'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small id="med_info" style="color:var(--primary-500);"></small>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Dosage *</label>
                <input type="text" name="dosage" class="form-control" required value="<?= htmlspecialchars($edit_detail['dosage'] ?? '') ?>" placeholder="e.g. 1 tablet">
            </div>
            <div class="form-group">
                <label class="form-label">Frequency</label>
                <input type="text" name="frequency" class="form-control" value="<?= htmlspecialchars($edit_detail['frequency'] ?? '') ?>" placeholder="e.g. 3 times daily">
            </div>
            <div class="form-group">
                <label class="form-label">Duration</label>
                <input type="text" name="duration" class="form-control" value="<?= htmlspecialchars($edit_detail['duration'] ?? '') ?>" placeholder="e.g. 7 days">
            </div>
            <div class="form-group">
                <label class="form-label">Quantity *</label>
                <input type="number" name="quantity" class="form-control" required min="1" value="<?= $edit_detail['quantity'] ?? 1 ?>">
            </div>
            <?php if ($edit_detail): ?>
            <div class="form-group">
                <label class="form-label">Dispensed Qty</label>
                <input type="number" name="dispensed_qty" class="form-control" min="0" value="<?= $edit_detail['dispensed_qty'] ?? 0 ?>">
            </div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label class="form-label">Special Instructions</label>
            <input type="text" name="instructions" class="form-control" value="<?= htmlspecialchars($edit_detail['instructions'] ?? '') ?>" placeholder="e.g. Take after meals">
        </div>
        <div style="display:flex;gap:1rem;">
            <button type="submit" class="btn btn-primary"><?= $edit_detail ? 'Update' : 'Add Medicine' ?></button>
            <?php if ($edit_detail): ?><a href="prescription_details.php<?= $filter_rx ? '?rx_id='.$filter_rx:'' ?>" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:1rem 1.5rem;">
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <strong>Filter by Prescription:</strong>
        <select id="filterRx" class="form-control" style="max-width:350px;" onchange="location.href='prescription_details.php'+(this.value?'?rx_id='+this.value:'')">
            <option value="">All Prescriptions</option>
            <?php foreach ($prescriptions as $rx): ?>
                <option value="<?= $rx['rx_id'] ?>" <?= $filter_rx == $rx['rx_id'] ? 'selected':'' ?>>
                    #<?= $rx['rx_id'] ?> — <?= htmlspecialchars($rx['patient_name']) ?> (<?= date('M d', strtotime($rx['issued_date'])) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="searchDetail" class="form-control" style="max-width:250px;" placeholder="Search medicines...">
        <span class="text-muted text-small"><?= count($details) ?> line(s) found</span>
    </div>
</div>

<!-- Details Grouped by Prescription -->
<?php if (empty($by_rx)): ?>
<div class="card"><p class="text-center text-muted" style="padding:2rem;">No prescription details found.</p></div>
<?php endif; ?>

<?php foreach ($by_rx as $rx_id => $rx_details): ?>
<?php $first = $rx_details[0]; ?>
<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-header rx-group-header">
        <div>
            <h2 style="margin:0;font-size:1.1rem;">
                Prescription #<?= $rx_id ?>
                — <?= htmlspecialchars($first['patient_name']) ?>
                — Dr. <?= htmlspecialchars($first['doctor_name']) ?>
            </h2>
            <p style="margin:.2rem 0 0;font-size:.82rem;color:var(--gray-600);">
                <?= date('M d, Y', strtotime($first['issued_date'])) ?>
                <?php if ($first['diagnosis']): ?> | <?= htmlspecialchars(substr($first['diagnosis'],0,60)) ?><?php endif; ?>
                | Status: <span class="badge badge-<?= strtolower($first['rx_status']) ?>"><?= $first['rx_status'] ?></span>
            </p>
        </div>
        <a href="prescription_details.php?rx_id=<?= $rx_id ?>" class="btn btn-secondary btn-sm">Filter View</a>
    </div>
    <div class="table-responsive">
        <table class="data-table detail-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Qty</th>
                    <th>Dispensed</th>
                    <th>Progress</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Instructions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rx_details as $det): ?>
                <?php
                    $pct = $det['quantity'] > 0 ? round(($det['dispensed_qty']/$det['quantity'])*100) : 0;
                    $remaining = $det['quantity'] - $det['dispensed_qty'];
                    $line_total = floatval($det['unit_price']) * intval($det['quantity']);
                ?>
                <tr class="det-row" data-text="<?= strtolower(htmlspecialchars($det['med_name'].' '.$det['dosage'])) ?>">
                    <td><?= $det['detail_id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($det['med_name']) ?></strong><br>
                        <span class="text-small text-muted"><?= htmlspecialchars($det['generic_name'] ?? '') ?> <?= htmlspecialchars($det['strength'] ?? '') ?> | <?= $det['form'] ?></span><br>
                        <span class="text-small <?= $det['stock_qty'] < 10 ? 'low-stock' : '' ?>">Stock: <?= $det['stock_qty'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($det['dosage']) ?></td>
                    <td><?= htmlspecialchars($det['frequency'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($det['duration'] ?? '—') ?></td>
                    <td><strong><?= $det['quantity'] ?></strong></td>
                    <td style="color:<?= $det['dispensed_qty'] >= $det['quantity'] ? 'var(--success-500)' : 'var(--gray-700)' ?>">
                        <strong><?= $det['dispensed_qty'] ?></strong>
                    </td>
                    <td style="min-width:90px;">
                        <span style="font-size:.75rem;"><?= $pct ?>%</span>
                        <div class="dispense-bar"><div class="dispense-fill" style="width:<?= $pct ?>%"></div></div>
                    </td>
                    <td>৳<?= number_format($det['unit_price'], 2) ?></td>
                    <td>৳<?= number_format($line_total, 2) ?></td>
                    <td><?= htmlspecialchars($det['instructions'] ?? '—') ?></td>
                    <td>
                        <a href="prescription_details.php?edit=<?= $det['detail_id'] ?><?= $filter_rx ? '&rx_id='.$filter_rx:'' ?>" class="btn btn-secondary btn-sm">Edit</a>

                        <?php if ($remaining > 0): ?>
                        <!-- Quick Dispense -->
                        <button onclick="openDispense(<?= $det['detail_id'] ?>, <?= $remaining ?>)" class="btn btn-primary btn-sm">Dispense</button>
                        <?php endif; ?>

                        <form method="POST" style="display:inline" onsubmit="return confirm('Remove this medicine from prescription?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="detail_id" value="<?= $det['detail_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Del</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <!-- Rx Total Row -->
            <tr style="background:#f8fbff;font-weight:700;">
                <td colspan="9" style="text-align:right;padding-right:1rem;">Prescription Total:</td>
                <td>৳<?= number_format(array_reduce($rx_details, fn($sum,$d) => $sum + floatval($d['unit_price'])*intval($d['quantity']), 0), 2) ?></td>
                <td colspan="2"></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<!-- Dispense Modal -->
<div id="dispenseModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="max-width:400px;width:90%;margin:0;">
        <div class="card-header"><h2>Dispense Medicine</h2><button onclick="closeDispense()" class="btn btn-secondary btn-sm">Close</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="dispense">
            <input type="hidden" name="detail_id" id="disp_detail_id">
            <div class="form-group">
                <label class="form-label">Quantity to Dispense *</label>
                <input type="number" name="dispense_qty" id="disp_qty" class="form-control" min="1" required>
                <small id="disp_max" style="color:var(--gray-500);"></small>
            </div>
            <button type="submit" class="btn btn-primary">Confirm Dispense</button>
        </form>
    </div>
</div>

</main>
</div>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
function updateMedInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('med_info');
    if (opt.value) {
        info.textContent = 'Unit price: ৳' + parseFloat(opt.dataset.price).toFixed(2) + ' | Current stock: ' + opt.dataset.stock;
    } else {
        info.textContent = '';
    }
}

function openDispense(detailId, remaining) {
    document.getElementById('disp_detail_id').value = detailId;
    document.getElementById('disp_qty').value = remaining;
    document.getElementById('disp_qty').max = remaining;
    document.getElementById('disp_max').textContent = 'Max remaining: ' + remaining;
    document.getElementById('dispenseModal').style.display = 'flex';
}

function closeDispense() {
    document.getElementById('dispenseModal').style.display = 'none';
}

document.getElementById('searchDetail').addEventListener('keyup', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.det-row').forEach(row => {
        row.style.display = row.dataset.text.includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
