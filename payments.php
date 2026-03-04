<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Constantes pour les méthodes de paiement
const PAYMENT_METHODS = ['CASH', 'CARD', 'CHEQUE', 'BANK_TRANSFER', 'INSURANCE', 'MOBILE_PAYMENT'];

$prefill_invoice = intval($_GET['invoice_id'] ?? 0);

// Fonction de mise à jour du statut de paiement d'une facture
$updateInvoicePaymentStatus = function($invoiceId) use ($db) {
    $inv = $db->fetchOne("SELECT total_amount, paid_amount FROM invoice WHERE invoice_id=?", [$invoiceId]);
    if ($inv) {
        $paid = floatval($inv['paid_amount']);
        $total = floatval($inv['total_amount']);
        $pstatus = $paid <= 0 ? 'UNPAID' : ($paid >= $total ? 'PAID' : 'PARTIALLY_PAID');
        $db->execute("UPDATE invoice SET payment_status=? WHERE invoice_id=?", [$pstatus, $invoiceId]);
    }
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Vérification CSRF simple (à améliorer avec un token de session)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = "Erreur de sécurité (CSRF). Veuillez réessayer.";
    } else {
        try {
            if ($_POST['action'] === 'add') {
                // Validation
                $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
                if ($amount === false || $amount <= 0) {
                    throw new Exception("Le montant doit être un nombre positif.");
                }
                if (!in_array($_POST['payment_method'], PAYMENT_METHODS)) {
                    throw new Exception("Méthode de paiement invalide.");
                }

                // Insertion du paiement
                $sql = "INSERT INTO payment (invoice_id, patient_id, amount, payment_method, transaction_id, reference_number, payment_notes, received_by, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'COMPLETED')";
                $db->execute($sql, [
                    $_POST['invoice_id'],
                    $_POST['patient_id'],
                    $amount,
                    $_POST['payment_method'],
                    $_POST['transaction_id'] ?? null,
                    $_POST['reference_number'] ?? null,
                    $_POST['payment_notes'] ?? null,
                    $_POST['received_by'] ?? null,
                ]);

                // Mise à jour du montant payé sur la facture
                $db->execute("UPDATE invoice SET paid_amount = paid_amount + ? WHERE invoice_id = ?", [$amount, $_POST['invoice_id']]);

                // Mise à jour du statut de la facture
                $updateInvoicePaymentStatus($_POST['invoice_id']);

                $message = "Paiement de ৳" . number_format($amount, 2) . " enregistré avec succès !";
            } elseif ($_POST['action'] === 'refund') {
                // Vérifier que le paiement existe et est COMPLETED
                $payment = $db->fetchOne("SELECT * FROM payment WHERE payment_id = ? AND status = 'COMPLETED'", [$_POST['payment_id']]);
                if (!$payment) {
                    throw new Exception("Paiement introuvable ou déjà remboursé.");
                }

                // Passer le paiement en remboursé
                $db->execute("UPDATE payment SET status='REFUNDED' WHERE payment_id=?", [$_POST['payment_id']]);

                // Soustraire le montant de la facture
                $db->execute("UPDATE invoice SET paid_amount = paid_amount - ? WHERE invoice_id=?", [$payment['amount'], $payment['invoice_id']]);

                // Mise à jour du statut de la facture
                $updateInvoicePaymentStatus($payment['invoice_id']);

                $message = "Paiement remboursé.";
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Génération d'un token CSRF pour le formulaire
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupération des données
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

$open_invoices = $db->fetchAll("
    SELECT i.invoice_id, i.invoice_number, i.total_amount, i.balance_due, i.payment_status,
           p.patient_id, p.name AS patient_name
    FROM invoice i
    JOIN patient p ON i.patient_id = p.patient_id
    WHERE i.payment_status IN ('UNPAID','PARTIALLY_PAID','OVERDUE')
    ORDER BY i.invoice_date DESC
");

$sum = $db->fetchOne("SELECT SUM(amount) AS total_received, COUNT(*) AS total_count FROM payment WHERE status='COMPLETED'");
$refunded = $db->fetchOne("SELECT SUM(amount) AS total_refunded FROM payment WHERE status='REFUNDED'");

$prefill_inv_data = null;
if ($prefill_invoice) {
    $prefill_inv_data = $db->fetchOne("SELECT i.*, p.patient_id, p.name AS patient_name FROM invoice i JOIN patient p ON i.patient_id=p.patient_id WHERE i.invoice_id=?", [$prefill_invoice]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paiements - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>Paiements</h1>
    <p class="subtitle">Enregistrer et suivre les paiements des patients</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Résumé -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem;">
    <div class="stat-card" style="background:linear-gradient(135deg,#e8f5e9,#fff);">
        <div class="stat-content">
            <h3>৳<?= number_format($sum['total_received'] ?? 0, 2) ?></h3>
            <p>Total collecté (<?= $sum['total_count'] ?? 0 ?> paiements)</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#fff3e0,#fff);">
        <div class="stat-content">
            <h3><?= count($open_invoices) ?></h3>
            <p>Factures ouvertes</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#ffebee,#fff);">
        <div class="stat-content">
            <h3>৳<?= number_format($refunded['total_refunded'] ?? 0, 2) ?></h3>
            <p>Total remboursé</p>
        </div>
    </div>
</div>

<!-- Formulaire d'enregistrement -->
<div class="card">
    <div class="card-header"><h2>Enregistrer un paiement</h2></div>
    <form method="POST" id="payForm">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="patient_id" id="patient_id_hidden" value="<?= $prefill_inv_data['patient_id'] ?? '' ?>">

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Facture *</label>
                <select name="invoice_id" class="form-control" required id="invoice_select" onchange="loadInvoiceInfo(this)">
                    <option value="">Sélectionner une facture</option>
                    <?php foreach ($open_invoices as $oi): ?>
                        <option value="<?= $oi['invoice_id'] ?>"
                            data-patient="<?= $oi['patient_id'] ?>"
                            data-balance="<?= $oi['balance_due'] ?>"
                            data-name="<?= htmlspecialchars($oi['patient_name']) ?>"
                            <?= ($prefill_invoice && $prefill_invoice == $oi['invoice_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($oi['invoice_number']) ?> — <?= htmlspecialchars($oi['patient_name']) ?> — Solde : ৳<?= number_format($oi['balance_due'], 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small id="inv_balance_info" style="color:var(--warning-500);font-weight:600;"></small>
            </div>
            <div class="form-group">
                <label class="form-label">Montant (৳) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required id="pay_amount"
                    value="<?= $prefill_inv_data ? htmlspecialchars($prefill_inv_data['balance_due']) : '' ?>" placeholder="Entrez le montant">
            </div>
            <div class="form-group">
                <label class="form-label">Mode de paiement *</label>
                <select name="payment_method" class="form-control" required>
                    <?php foreach (PAYMENT_METHODS as $m): ?>
                        <option value="<?= $m ?>"><?= str_replace('_',' ',$m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">ID transaction</label>
                <input type="text" name="transaction_id" class="form-control" placeholder="Réf. banque/carte">
            </div>
            <div class="form-group">
                <label class="form-label">Numéro de référence</label>
                <input type="text" name="reference_number" class="form-control" placeholder="N° chèque / bordereau">
            </div>
            <div class="form-group">
                <label class="form-label">Reçu par</label>
                <input type="text" name="received_by" class="form-control" placeholder="Nom du personnel" value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="payment_notes" class="form-control" rows="2" placeholder="Notes optionnelles..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
    </form>
</div>

<!-- Historique -->
<div class="card">
    <div class="card-header">
        <h2>Historique des paiements (<?= count($payments) ?>)</h2>
        <div style="display:flex;gap:.5rem;">
            <select id="filterPayMethod" class="form-control" style="max-width:180px;">
                <option value="">Tous les modes</option>
                <?php foreach (PAYMENT_METHODS as $m): ?>
                    <option value="<?= $m ?>"><?= str_replace('_',' ',$m) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="searchPay" class="form-control" style="max-width:250px;" placeholder="Rechercher...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="payTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Facture</th>
                    <th>Montant</th>
                    <th>Mode</th>
                    <th>ID transaction</th>
                    <th>Reçu par</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $pay): ?>
                <tr data-method="<?= $pay['payment_method'] ?>">
                    <td><?= $pay['payment_id'] ?></td>
                    <td>
                        <strong><?= date('d M Y', strtotime($pay['payment_date'])) ?></strong><br>
                        <span class="text-small text-muted"><?= date('H:i', strtotime($pay['payment_date'])) ?></span>
                    </td>
                    <td><?= htmlspecialchars($pay['patient_name']) ?><br><span class="text-small text-muted"><?= htmlspecialchars($pay['patient_phone'] ?? '') ?></span></td>
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
                            <form method="POST" style="display:inline" onsubmit="return confirm('Rembourser ce paiement ?')">
                                <input type="hidden" name="action" value="refund">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="payment_id" value="<?= $pay['payment_id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Rembourser</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted text-small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
                <tr><td colspan="10" class="text-center">Aucun paiement trouvé.</td></tr>
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
    document.getElementById('inv_balance_info').textContent = 'Solde dû : ৳' + bal.toFixed(2);
}

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
