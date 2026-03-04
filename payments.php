<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

$prefill_invoice = intval($_GET['invoice_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        try {
            // Insert payment
            $sql = "INSERT INTO payment (invoice_id, patient_id, amount, payment_method, transaction_id, reference_number, payment_notes, received_by, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'COMPLETED')";
            $db->execute($sql, [
                $_POST['invoice_id'],
                $_POST['patient_id'],
                $_POST['amount'],
                $_POST['payment_method'],
                $_POST['transaction_id'],
                $_POST['reference_number'],
                $_POST['payment_notes'],
                $_POST['received_by'],
            ]);
            // Update paid_amount on invoice
            $db->execute("UPDATE invoice SET paid_amount = paid_amount + ? WHERE invoice_id = ?", [$_POST['amount'], $_POST['invoice_id']]);
            // Update payment_status
            $inv = $db->fetchOne("SELECT total_amount, paid_amount FROM invoice WHERE invoice_id=?", [$_POST['invoice_id']]);
            if ($inv) {
                $paid    = floatval($inv['paid_amount']);
                $total   = floatval($inv['total_amount']);
                $pstatus = $paid <= 0 ? 'UNPAID' : ($paid >= $total ? 'PAID' : 'PARTIALLY_PAID');
                $db->execute("UPDATE invoice SET payment_status=? WHERE invoice_id=?", [$pstatus, $_POST['invoice_id']]);
            }
            $message = "Payment of ৳" . number_format($_POST['amount'], 2) . " recorded successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'refund') {
        try {
            $db->execute("UPDATE payment SET status='REFUNDED' WHERE payment_id=?", [$_POST['payment_id']]);
            // Reverse the paid_amount
            $pay = $db->fetchOne("SELECT * FROM payment WHERE payment_id=?", [$_POST['payment_id']]);
            if ($pay) {
                $db->execute("UPDATE invoice SET paid_amount = paid_amount - ? WHERE invoice_id=?", [$pay['amount'], $pay['invoice_id']]);
            }
            $message = "Payment refunded.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all payments with invoice + patient info
$payments = $db->fetchAll("
    SELECT pay.payment_id, pay.payment_date, pay.amount, pay.payment_method,
           pay.transaction_id, pay.reference_number, pay.payment_notes,
           pay.received_by, pay.status,
           i.invoice_number, i.total_amount, i.balance_due,
           p.name AS patient_name, p.phone AS patient_phone, p.patient_id
    FROM payment pay
    JOIN invoice i ON pay.invoice_id = i.invoice_id
    JOIN patient p ON pay.patient_id = p.patient_id
    ORDER BY pay.payment_date DESC
    LIMIT 300
");

// For the payment form — list invoices with outstanding balance
$open_invoices = $db->fetchAll("
    SELECT i.invoice_id, i.invoice_number, i.total_amount, i.balance_due, i.payment_status,
           p.patient_id, p.name AS patient_name
    FROM invoice i
    JOIN patient p ON i.patient_id = p.patient_id
    WHERE i.payment_status IN ('UNPAID','PARTIALLY_PAID','OVERDUE')
    ORDER BY i.invoice_date DESC
");

// Summary
$sum = $db->fetchOne("SELECT SUM(amount) AS total_received, COUNT(*) AS total_count FROM payment WHERE status='COMPLETED'");
$refunded = $db->fetchOne("SELECT SUM(amount) AS total_refunded FROM payment WHERE status='REFUNDED'");

// Pre-load invoice data if coming from invoices page
$prefill_inv_data = null;
if ($prefill_invoice) {
    $prefill_inv_data = $db->fetchOne("SELECT i.*, p.patient_id, p.name AS patient_name FROM invoice i JOIN patient p ON i.patient_id=p.patient_id WHERE i.invoice_id=?", [$prefill_invoice]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>Payments</h1>
    <p class="subtitle">Record and track patient payments</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem;">
    <div class="stat-card" style="background:linear-gradient(135deg,#e8f5e9,#fff);">
        <div class="stat-content">
            <h3>৳<?= number_format($sum['total_received'] ?? 0, 2) ?></h3>
            <p>Total Collected (<?= $sum['total_count'] ?? 0 ?> payments)</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#fff3e0,#fff);">
        <div class="stat-content">
            <h3><?= count($open_invoices) ?></h3>
            <p>Open Invoices</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#ffebee,#fff);">
        <div class="stat-content">
            <h3>৳<?= number_format($refunded['total_refunded'] ?? 0, 2) ?></h3>
            <p>Total Refunded</p>
        </div>
    </div>
</div>

<!-- Record Payment Form -->
<div class="card">
    <div class="card-header"><h2>Record Payment</h2></div>
    <form method="POST" id="payForm">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="patient_id" id="patient_id_hidden" value="<?= $prefill_inv_data['patient_id'] ?? '' ?>">

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Invoice *</label>
                <select name="invoice_id" class="form-control" required id="invoice_select" onchange="loadInvoiceInfo(this)">
                    <option value="">Select Invoice</option>
                    <?php foreach ($open_invoices as $oi): ?>
                        <option value="<?= $oi['invoice_id'] ?>"
                            data-patient="<?= $oi['patient_id'] ?>"
                            data-balance="<?= $oi['balance_due'] ?>"
                            data-name="<?= htmlspecialchars($oi['patient_name']) ?>"
                            <?= ($prefill_invoice && $prefill_invoice == $oi['invoice_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($oi['invoice_number']) ?> — <?= htmlspecialchars($oi['patient_name']) ?> — Balance: ৳<?= number_format($oi['balance_due'], 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small id="inv_balance_info" style="color:var(--warning-500);font-weight:600;"></small>
            </div>
            <div class="form-group">
                <label class="form-label">Amount (৳) *</label>
                <input type="number" step="0.01" name="amount" class="form-control" required id="pay_amount"
                    value="<?= $prefill_inv_data ? $prefill_inv_data['balance_due'] : '' ?>" placeholder="Enter amount">
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method *</label>
                <select name="payment_method" class="form-control" required>
                    <?php foreach (['CASH','CARD','CHEQUE','BANK_TRANSFER','INSURANCE','MOBILE_PAYMENT'] as $m): ?>
                        <option value="<?= $m ?>"><?= str_replace('_',' ',$m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Transaction ID</label>
                <input type="text" name="transaction_id" class="form-control" placeholder="Bank/card transaction ref">
            </div>
            <div class="form-group">
                <label class="form-label">Reference Number</label>
                <input type="text" name="reference_number" class="form-control" placeholder="Cheque/slip number">
            </div>
            <div class="form-group">
                <label class="form-label">Received By</label>
                <input type="text" name="received_by" class="form-control" placeholder="Staff name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="payment_notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Record Payment</button>
    </form>
</div>

<!-- Payments History -->
<div class="card">
    <div class="card-header">
        <h2>Payment History (<?= count($payments) ?>)</h2>
        <div style="display:flex;gap:.5rem;">
            <select id="filterPayMethod" class="form-control" style="max-width:180px;">
                <option value="">All Methods</option>
                <?php foreach (['CASH','CARD','CHEQUE','BANK_TRANSFER','INSURANCE','MOBILE_PAYMENT'] as $m): ?>
                    <option value="<?= $m ?>"><?= str_replace('_',' ',$m) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="searchPay" class="form-control" style="max-width:250px;" placeholder="Search payments...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="payTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Invoice</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Transaction ID</th>
                    <th>Received By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $pay): ?>
                <tr data-method="<?= $pay['payment_method'] ?>">
                    <td><?= $pay['payment_id'] ?></td>
                    <td>
                        <strong><?= date('M d, Y', strtotime($pay['payment_date'])) ?></strong><br>
                        <span class="text-small text-muted"><?= date('h:i A', strtotime($pay['payment_date'])) ?></span>
                    </td>
                    <td><?= htmlspecialchars($pay['patient_name']) ?><br><span class="text-small text-muted"><?= $pay['patient_phone'] ?></span></td>
                    <td><?= htmlspecialchars($pay['invoice_number']) ?></td>
                    <td style="font-weight:700;color:var(--success-500)">৳<?= number_format($pay['amount'], 2) ?></td>
                    <td><?= str_replace('_',' ',$pay['payment_method']) ?></td>
                    <td><?= htmlspecialchars($pay['transaction_id'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($pay['received_by'] ?? '—') ?></td>
                    <td>
                        <span class="badge badge-<?= strtolower($pay['status']) === 'completed' ? 'scheduled' : strtolower($pay['status']) ?>">
                            <?= $pay['status'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($pay['status'] === 'COMPLETED'): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Refund this payment?')">
                                <input type="hidden" name="action" value="refund">
                                <input type="hidden" name="payment_id" value="<?= $pay['payment_id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Refund</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted text-small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
                <tr><td colspan="10" class="text-center">No payment records found.</td></tr>
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
function loadInvoiceInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) return;
    document.getElementById('patient_id_hidden').value = opt.dataset.patient;
    const bal = parseFloat(opt.dataset.balance) || 0;
    document.getElementById('pay_amount').value = bal.toFixed(2);
    document.getElementById('inv_balance_info').textContent = 'Balance due: ৳' + bal.toFixed(2);
}

// Pre-load on page load if invoice pre-selected
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('invoice_select');
    if (sel.value) loadInvoiceInfo(sel);
});

const filterPayMethod = document.getElementById('filterPayMethod');
const searchPay = document.getElementById('searchPay');
function applyPayFilter() {
    const m = filterPayMethod.value;
    const q = searchPay.value.toLowerCase();
    document.querySelectorAll('#payTable tbody tr').forEach(row => {
        const ok = (!m || row.dataset.method === m) && (!q || row.textContent.toLowerCase().includes(q));
        row.style.display = ok ? '' : 'none';
    });
}
filterPayMethod.addEventListener('change', applyPayFilter);
searchPay.addEventListener('keyup', applyPayFilter);
</script>
</body>
</html>
