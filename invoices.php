<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

// Constantes pour les statuts autorisés
const INVOICE_STATUSES = ['UNPAID', 'PARTIALLY_PAID', 'PAID', 'OVERDUE', 'CANCELLED'];

$db = Database::getInstance();
$message = '';
$error = '';

// Génération d'un token CSRF si nécessaire
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fonction utilitaire de validation d'existence
function recordExists($db, $table, $idColumn, $id) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if (!$id) return false;
    $result = $db->fetchOne("SELECT $idColumn FROM $table WHERE $idColumn = ?", [$id]);
    return $result !== false;
}

// Traitement des formulaires POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        try {
            // ===================== AJOUT =====================
            if ($_POST['action'] === 'add') {
                // Validation patient
                $patientId = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$patientId || !recordExists($db, 'patient', 'patient_id', $patientId)) {
                    throw new Exception("Patient invalide.");
                }

                // Validation dates
                $invoiceDate = $_POST['invoice_date'] ?? '';
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $invoiceDate)) {
                    throw new Exception("Date de facture invalide.");
                }
                $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
                if ($dueDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
                    throw new Exception("Date d'échéance invalide.");
                }

                // Validation montants
                $subtotal = filter_var($_POST['subtotal'] ?? 0, FILTER_VALIDATE_FLOAT);
                $tax = filter_var($_POST['tax_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
                $discount = filter_var($_POST['discount_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
                if ($subtotal === false || $subtotal < 0) throw new Exception("Sous-total invalide.");
                if ($tax === false || $tax < 0) throw new Exception("Taxe invalide.");
                if ($discount === false || $discount < 0) throw new Exception("Remise invalide.");

                // Appt et encounter (optionnels)
                $apptId = !empty($_POST['appt_id']) ? filter_var($_POST['appt_id'], FILTER_VALIDATE_INT) : null;
                if ($apptId && !recordExists($db, 'appointment', 'appt_id', $apptId)) {
                    $apptId = null; // ignorer si invalide
                }
                $encounterId = !empty($_POST['encounter_id']) ? filter_var($_POST['encounter_id'], FILTER_VALIDATE_INT) : null;
                if ($encounterId && !recordExists($db, 'encounter', 'encounter_id', $encounterId)) {
                    $encounterId = null;
                }

                // Calcul du total
                $total = $subtotal + $tax - $discount;

                // Génération numéro de facture
                $inv_no = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

                // Insertion
                $sql = "INSERT INTO invoice (patient_id, appt_id, encounter_id, invoice_number, invoice_date, due_date, subtotal, tax_amount, discount_amount, total_amount, paid_amount, payment_status, billing_address, notes)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, 'UNPAID', ?, ?)";
                $db->execute($sql, [
                    $patientId,
                    $apptId,
                    $encounterId,
                    $inv_no,
                    $invoiceDate,
                    $dueDate,
                    $subtotal,
                    $tax,
                    $discount,
                    $total,
                    $_POST['billing_address'] ?? null,
                    $_POST['notes'] ?? null,
                ]);
                $message = "Facture $inv_no créée avec succès !";
            }

            // ===================== ÉDITION =====================
            elseif ($_POST['action'] === 'edit') {
                // Validation ID facture
                $invoiceId = filter_var($_POST['invoice_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$invoiceId || !recordExists($db, 'invoice', 'invoice_id', $invoiceId)) {
                    throw new Exception("Facture invalide.");
                }

                // Validation montants
                $subtotal = filter_var($_POST['subtotal'] ?? 0, FILTER_VALIDATE_FLOAT);
                $tax = filter_var($_POST['tax_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
                $discount = filter_var($_POST['discount_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
                if ($subtotal === false || $subtotal < 0) throw new Exception("Sous-total invalide.");
                if ($tax === false || $tax < 0) throw new Exception("Taxe invalide.");
                if ($discount === false || $discount < 0) throw new Exception("Remise invalide.");

                // Date d'échéance (optionnelle)
                $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
                if ($dueDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
                    throw new Exception("Date d'échéance invalide.");
                }

                // Statut
                $status = $_POST['payment_status'] ?? '';
                if (!in_array($status, INVOICE_STATUSES)) {
                    throw new Exception("Statut de paiement invalide.");
                }

                $total = $subtotal + $tax - $discount;

                $sql = "UPDATE invoice SET subtotal=?, tax_amount=?, discount_amount=?, total_amount=?, due_date=?, payment_status=?, billing_address=?, notes=? WHERE invoice_id=?";
                $db->execute($sql, [
                    $subtotal,
                    $tax,
                    $discount,
                    $total,
                    $dueDate,
                    $status,
                    $_POST['billing_address'] ?? null,
                    $_POST['notes'] ?? null,
                    $invoiceId
                ]);
                $message = "Facture mise à jour.";
            }

            // ===================== SUPPRESSION =====================
            elseif ($_POST['action'] === 'delete') {
                $invoiceId = filter_var($_POST['invoice_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$invoiceId || !recordExists($db, 'invoice', 'invoice_id', $invoiceId)) {
                    throw new Exception("Facture invalide.");
                }

                // Supprimer d'abord les paiements associés
                $db->execute("DELETE FROM payment WHERE invoice_id=?", [$invoiceId]);
                // Puis la facture
                $db->execute("DELETE FROM invoice WHERE invoice_id=?", [$invoiceId]);
                $message = "Facture et paiements supprimés.";
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupération des factures
try {
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
} catch (Exception $e) {
    $error = "Erreur lors du chargement des factures : " . $e->getMessage();
    $invoices = [];
}

// Récupération des patients pour la liste déroulante
try {
    $patients = $db->fetchAll("SELECT patient_id, name, phone FROM patient ORDER BY name");
} catch (Exception $e) {
    $patients = [];
    $error = "Erreur lors du chargement des patients : " . $e->getMessage();
}

// Préparation de l'édition
$edit_invoice = null;
if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId) {
        $edit_invoice = $db->fetchOne("SELECT * FROM invoice WHERE invoice_id=?", [$editId]);
    }
}

// Totaux récapitulatifs
try {
    $totals = $db->fetchOne("SELECT SUM(total_amount) AS total_billed, SUM(paid_amount) AS total_paid, SUM(balance_due) AS total_outstanding FROM invoice WHERE payment_status != 'CANCELLED'");
} catch (Exception $e) {
    $totals = ['total_billed' => 0, 'total_paid' => 0, 'total_outstanding' => 0];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Factures - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>Factures et facturation</h1>
    <p class="subtitle">Créer et gérer les factures patients</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Résumé financier -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem;">
    <div class="stat-card" style="background:linear-gradient(135deg,#e3f2fd,#fff);">
        <div class="stat-content">
            <h3>৳<?= number_format($totals['total_billed'] ?? 0, 2) ?></h3>
            <p>Total facturé</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#e8f5e9,#fff);">
        <div class="stat-content">
            <h3>৳<?= number_format($totals['total_paid'] ?? 0, 2) ?></h3>
            <p>Total encaissé</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#fff3e0,#fff);">
        <div class="stat-content">
            <h3>৳<?= number_format($totals['total_outstanding'] ?? 0, 2) ?></h3>
            <p>Solde impayé</p>
        </div>
    </div>
</div>

<!-- Formulaire d'ajout / édition -->
<div class="card">
    <div class="card-header"><h2><?= $edit_invoice ? 'Modifier la facture' : 'Créer une nouvelle facture' ?></h2></div>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $edit_invoice ? 'edit' : 'add' ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <?php if ($edit_invoice): ?>
            <input type="hidden" name="invoice_id" value="<?= $edit_invoice['invoice_id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <?php if (!$edit_invoice): ?>
            <div class="form-group">
                <label class="form-label">Patient *</label>
                <select name="patient_id" class="form-control" required>
                    <option value="">Sélectionner un patient</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['name']) ?> — <?= htmlspecialchars($p['phone'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Date de facture *</label>
                <input type="date" name="invoice_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label">Date d'échéance</label>
                <input type="date" name="due_date" class="form-control" value="<?= $edit_invoice['due_date'] ?? date('Y-m-d', strtotime('+30 days')) ?>">
            </div>
            <?php if (!$edit_invoice): ?>
            <div class="form-group">
                <label class="form-label">ID Rendez-vous (optionnel)</label>
                <input type="number" name="appt_id" class="form-control" placeholder="Lier un rendez-vous">
            </div>
            <div class="form-group">
                <label class="form-label">ID Consultation (optionnel)</label>
                <input type="number" name="encounter_id" class="form-control" placeholder="Lier une consultation">
            </div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Sous-total (৳) *</label>
                <input type="number" step="0.01" min="0" name="subtotal" class="form-control" required id="subtotal"
                    value="<?= htmlspecialchars($edit_invoice['subtotal'] ?? '') ?>" oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label class="form-label">Taxe (৳)</label>
                <input type="number" step="0.01" min="0" name="tax_amount" class="form-control" value="<?= htmlspecialchars($edit_invoice['tax_amount'] ?? 0) ?>" id="tax" oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label class="form-label">Remise (৳)</label>
                <input type="number" step="0.01" min="0" name="discount_amount" class="form-control" value="<?= htmlspecialchars($edit_invoice['discount_amount'] ?? 0) ?>" id="discount" oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label class="form-label">Total (৳)</label>
                <input type="text" class="form-control" id="total_display" readonly style="background:#f5f5f5;font-weight:700;">
            </div>
        </div>

        <?php if ($edit_invoice): ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Statut de paiement</label>
                <select name="payment_status" class="form-control">
                    <?php foreach (INVOICE_STATUSES as $s): ?>
                        <option value="<?= $s ?>" <?= ($edit_invoice['payment_status'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= str_replace('_', ' ', $s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">Adresse de facturation</label>
            <textarea name="billing_address" class="form-control" rows="2"><?= htmlspecialchars($edit_invoice['billing_address'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($edit_invoice['notes'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:1rem;">
            <button type="submit" class="btn btn-primary"><?= $edit_invoice ? 'Mettre à jour' : 'Créer la facture' ?></button>
            <?php if ($edit_invoice): ?>
                <a href="invoices.php" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Liste des factures -->
<div class="card">
    <div class="card-header">
        <h2>Toutes les factures (<?= count($invoices) ?>)</h2>
        <div style="display:flex;gap:.5rem;">
            <select id="filterInvStatus" class="form-control" style="max-width:180px;">
                <option value="">Tous les statuts</option>
                <option value="UNPAID">Impayé</option>
                <option value="PARTIALLY_PAID">Partiellement payé</option>
                <option value="PAID">Payé</option>
                <option value="OVERDUE">En retard</option>
            </select>
            <input type="text" id="searchInv" class="form-control" style="max-width:250px;" placeholder="Rechercher une facture...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="invTable">
            <thead>
                <tr>
                    <th>N° facture</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Sous-total</th>
                    <th>Taxe</th>
                    <th>Remise</th>
                    <th>Total</th>
                    <th>Payé</th>
                    <th>Solde</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
                <tr data-status="<?= $inv['payment_status'] ?>">
                    <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                    <td>
                        <?= date('d M Y', strtotime($inv['invoice_date'])) ?><br>
                        <?php if ($inv['due_date']): ?>
                            <span class="text-small text-muted">Échéance : <?= date('d M Y', strtotime($inv['due_date'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($inv['patient_name']) ?><br>
                        <span class="text-small text-muted"><?= htmlspecialchars($inv['patient_phone'] ?? '') ?></span>
                    </td>
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
                        <a href="invoices.php?edit=<?= $inv['invoice_id'] ?>" class="btn btn-secondary btn-sm">Modifier</a>
                        <a href="payments.php?invoice_id=<?= $inv['invoice_id'] ?>" class="btn btn-primary btn-sm">Payer</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer la facture et les paiements associés ?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="invoice_id" value="<?= $inv['invoice_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Suppr.</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="11" class="text-center">Aucune facture trouvée.</td></tr>
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
window.addEventListener('DOMContentLoaded', calcTotal);

const filterInvStatus = document.getElementById('filterInvStatus');
const searchInv = document.getElementById('searchInv');
function applyInvFilter() {
    const s = filterInvStatus.value;
    const q = searchInv.value.toLowerCase().trim();
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
