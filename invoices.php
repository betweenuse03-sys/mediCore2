<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        try {
            $inv_no = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $sql = "INSERT INTO invoice (patient_id, appt_id, encounter_id, invoice_number, invoice_date, due_date, subtotal, tax_amount, discount_amount, total_amount, paid_amount, payment_status, billing_address, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, 'UNPAID', ?, ?)";
            $subtotal  = floatval($_POST['subtotal']);
            $tax       = floatval($_POST['tax_amount']);
            $discount  = floatval($_POST['discount_amount']);
            $total     = $subtotal + $tax - $discount;
            $db->execute($sql, [
                $_POST['patient_id'],
                !empty($_POST['appt_id'])      ? $_POST['appt_id']      : null,
                !empty($_POST['encounter_id']) ? $_POST['encounter_id'] : null,
                $inv_no,
                $_POST['invoice_date'],
                !empty($_POST['due_date'])     ? $_POST['due_date']     : null,
                $subtotal,
                $tax,
                $discount,
                $total,
                $_POST['billing_address'],
                $_POST['notes'],
            ]);
            $message = "Invoice $inv_no created successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }

    } elseif ($_POST['action'] === 'edit') {
        try {
            $subtotal = floatval($_POST['subtotal']);
            $tax      = floatval($_POST['tax_amount']);
            $discount = floatval($_POST['discount_amount']);
            $total    = $subtotal + $tax - $discount;
            $sql = "UPDATE invoice SET subtotal=?, tax_amount=?, discount_amount=?, total_amount=?, due_date=?, payment_status=?, billing_address=?, notes=? WHERE invoice_id=?";
            $db->execute($sql, [$subtotal, $tax, $discount, $total, $_POST['due_date'] ?: null, $_POST['payment_status'], $_POST['billing_address'], $_POST['notes'], $_POST['invoice_id']]);
            $message = "Invoice updated!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }

    } elseif ($_POST['action'] === 'delete') {
        try {
            $db->execute("DELETE FROM payment WHERE invoice_id=?", [$_POST['invoice_id']]);
            $db->execute("DELETE FROM invoice WHERE invoice_id=?",  [$_POST['invoice_id']]);
            $message = "Invoice and payments deleted.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$invoices = $db->fetchAll("
    SELECT i.invoice_id, i.invoice_number, i.invoice_date, i.due_date,
           i.subtotal, i.tax_amount, i.discount_amount, i.total_amount,
           i.paid_amount, i.balance_due, i.payment_status,
           i.billing_address, i.notes, i.appt_id, i.encounter_id,
           p.name AS patient_name, p.phone AS patient_phone,
           COUNT(pay.payment_id) AS payment_count
    FROM invoice i
    JOIN patient p ON i.patient_id = p.patient_id
    LEFT JOIN payment pay ON i.invoice_id = pay.invoice_id
    GROUP BY i.invoice_id
    ORDER BY i.invoice_date DESC
    LIMIT 200
");

$patients  = $db->fetchAll("SELECT patient_id, name, phone FROM patient ORDER BY name");

$edit_invoice = null;
if (isset($_GET['edit'])) {
    $edit_invoice = $db->fetchOne("SELECT * FROM invoice WHERE invoice_id=?", [$_GET['edit']]);
}

// Summary totals
$totals = $db->fetchOne("SELECT SUM(total_amount) AS total_billed, SUM(paid_amount) AS total_paid, SUM(balance_due) AS total_outstanding FROM invoice WHERE payment_status != 'CANCELLED'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoices - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>Invoices &amp; Billing</h1>
    <p class="subtitle">Create and manage patient invoices</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Financial Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem;">
    <div class="stat-card" style="background:linear-gradient(135deg,#e3f2fd,#fff);">
        <div class="stat-content">
            <h3>৳<?= number_format($totals['total_billed'] ?? 0, 2) ?></h3>
            <p>Total Billed</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#e8f5e9,#fff);">
        <div class="stat-content">
            <h3>৳<?= number_format($totals['total_paid'] ?? 0, 2) ?></h3>
            <p>Total Collected</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#fff3e0,#fff);">
        <div class="stat-content">
            <h3>৳<?= number_format($totals['total_outstanding'] ?? 0, 2) ?></h3>
            <p>Outstanding Balance</p>
        </div>
    </div>
</div>

<!-- Add / Edit Invoice -->
<div class="card">
    <div class="card-header"><h2><?= $edit_invoice ? 'Edit Invoice' : 'Create New Invoice' ?></h2></div>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $edit_invoice ? 'edit' : 'add' ?>">
        <?php if ($edit_invoice): ?>
            <input type="hidden" name="invoice_id" value="<?= $edit_invoice['invoice_id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <?php if (!$edit_invoice): ?>
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
                <label class="form-label">Invoice Date *</label>
                <input type="date" name="invoice_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" value="<?= $edit_invoice['due_date'] ?? date('Y-m-d', strtotime('+30 days')) ?>">
            </div>
            <?php if (!$edit_invoice): ?>
            <div class="form-group">
                <label class="form-label">Appt ID (optional)</label>
                <input type="number" name="appt_id" class="form-control" placeholder="Link appointment">
            </div>
            <div class="form-group">
                <label class="form-label">Encounter ID (optional)</label>
                <input type="number" name="encounter_id" class="form-control" placeholder="Link encounter">
            </div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Subtotal (৳) *</label>
                <input type="number" step="0.01" name="subtotal" class="form-control" required id="subtotal"
                    value="<?= $edit_invoice['subtotal'] ?? '' ?>" oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label class="form-label">Tax (৳)</label>
                <input type="number" step="0.01" name="tax_amount" class="form-control" value="<?= $edit_invoice['tax_amount'] ?? 0 ?>" id="tax" oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label class="form-label">Discount (৳)</label>
                <input type="number" step="0.01" name="discount_amount" class="form-control" value="<?= $edit_invoice['discount_amount'] ?? 0 ?>" id="discount" oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label class="form-label">Total (৳)</label>
                <input type="text" class="form-control" id="total_display" readonly style="background:#f5f5f5;font-weight:700;">
            </div>
        </div>

        <?php if ($edit_invoice): ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Payment Status</label>
                <select name="payment_status" class="form-control">
                    <?php foreach (['UNPAID','PARTIALLY_PAID','PAID','OVERDUE','CANCELLED'] as $s): ?>
                        <option value="<?= $s ?>" <?= $edit_invoice['payment_status']===$s ? 'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">Billing Address</label>
            <textarea name="billing_address" class="form-control" rows="2"><?= htmlspecialchars($edit_invoice['billing_address'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($edit_invoice['notes'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:1rem;">
            <button type="submit" class="btn btn-primary"><?= $edit_invoice ? 'Update Invoice' : 'Create Invoice' ?></button>
            <?php if ($edit_invoice): ?>
                <a href="invoices.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Invoice List -->
<div class="card">
    <div class="card-header">
        <h2>All Invoices (<?= count($invoices) ?>)</h2>
        <div style="display:flex;gap:.5rem;">
            <select id="filterInvStatus" class="form-control" style="max-width:180px;">
                <option value="">All Status</option>
                <option value="UNPAID">Unpaid</option>
                <option value="PARTIALLY_PAID">Partially Paid</option>
                <option value="PAID">Paid</option>
                <option value="OVERDUE">Overdue</option>
            </select>
            <input type="text" id="searchInv" class="form-control" style="max-width:250px;" placeholder="Search invoices...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="invTable">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Subtotal</th>
                    <th>Tax</th>
                    <th>Discount</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
                <tr data-status="<?= $inv['payment_status'] ?>">
                    <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                    <td>
                        <?= date('M d, Y', strtotime($inv['invoice_date'])) ?><br>
                        <?php if ($inv['due_date']): ?>
                            <span class="text-small text-muted">Due: <?= date('M d, Y', strtotime($inv['due_date'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($inv['patient_name']) ?><br><span class="text-small text-muted"><?= $inv['patient_phone'] ?></span></td>
                    <td>৳<?= number_format($inv['subtotal'], 2) ?></td>
                    <td>৳<?= number_format($inv['tax_amount'], 2) ?></td>
                    <td>৳<?= number_format($inv['discount_amount'], 2) ?></td>
                    <td><strong>৳<?= number_format($inv['total_amount'], 2) ?></strong></td>
                    <td style="color:var(--success-500)">৳<?= number_format($inv['paid_amount'], 2) ?></td>
                    <td style="color:<?= $inv['balance_due'] > 0 ? 'var(--danger-500)' : 'var(--success-500)' ?>">
                        <strong>৳<?= number_format($inv['balance_due'], 2) ?></strong>
                    </td>
                    <td><span class="badge badge-<?= strtolower(str_replace('_','-',$inv['payment_status'])) ?>"><?= $inv['payment_status'] ?></span></td>
                    <td>
                        <a href="invoices.php?edit=<?= $inv['invoice_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <a href="payments.php?invoice_id=<?= $inv['invoice_id'] ?>" class="btn btn-primary btn-sm">Pay</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete invoice and payments?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="invoice_id" value="<?= $inv['invoice_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Del</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="11" class="text-center">No invoices found.</td></tr>
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
function calcTotal() {
    const sub = parseFloat(document.getElementById('subtotal').value) || 0;
    const tax = parseFloat(document.getElementById('tax').value) || 0;
    const dis = parseFloat(document.getElementById('discount').value) || 0;
    document.getElementById('total_display').value = '৳' + (sub + tax - dis).toFixed(2);
}
calcTotal();

const filterInvStatus = document.getElementById('filterInvStatus');
const searchInv = document.getElementById('searchInv');
function applyInvFilter() {
    const s = filterInvStatus.value;
    const q = searchInv.value.toLowerCase();
    document.querySelectorAll('#invTable tbody tr').forEach(row => {
        const ok = (!s || row.dataset.status === s) && (!q || row.textContent.toLowerCase().includes(q));
        row.style.display = ok ? '' : 'none';
    });
}
filterInvStatus.addEventListener('change', applyInvFilter);
searchInv.addEventListener('keyup', applyInvFilter);
</script>
</body>
</html>
