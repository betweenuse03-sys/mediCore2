<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Génération d'un token CSRF si nécessaire
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fonction de validation d'un identifiant patient
function validatePatientId($id, $db) {
    $patient = $db->fetchOne("SELECT patient_id FROM patient WHERE patient_id = ?", [$id]);
    return $patient !== false;
}

// Traitement des formulaires POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        try {
            if ($_POST['action'] === 'add') {
                // Validation des champs requis
                $patientId = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$patientId || !validatePatientId($patientId, $db)) {
                    throw new Exception("Patient invalide ou inexistant.");
                }
                $description = trim($_POST['change_description'] ?? '');
                if (empty($description)) {
                    throw new Exception("La description est requise.");
                }

                // Insertion manuelle dans l'historique
                $sql = "INSERT INTO patient_history (patient_id, field_changed, old_value, new_value, change_description, changed_by)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $db->execute($sql, [
                    $patientId,
                    !empty($_POST['field_changed']) ? trim($_POST['field_changed']) : null,
                    !empty($_POST['old_value']) ? trim($_POST['old_value']) : null,
                    !empty($_POST['new_value']) ? trim($_POST['new_value']) : null,
                    $description,
                    $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System',
                ]);
                $message = "Entrée d'historique ajoutée avec succès.";
            } elseif ($_POST['action'] === 'delete') {
                // Validation de l'ID de l'historique
                $historyId = filter_var($_POST['history_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$historyId) {
                    throw new Exception("ID d'historique invalide.");
                }

                // Suppression (vérification d'existence optionnelle mais sécuritaire)
                $existing = $db->fetchOne("SELECT history_id FROM patient_history WHERE history_id = ?", [$historyId]);
                if (!$existing) {
                    throw new Exception("Enregistrement introuvable.");
                }

                $db->execute("DELETE FROM patient_history WHERE history_id = ?", [$historyId]);
                $message = "Entrée supprimée.";
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupération du filtre patient
$filter_patient = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// Construction de la requête avec paramètres
$sql = "
    SELECT ph.history_id, ph.field_changed, ph.old_value, ph.new_value,
           ph.change_description, ph.changed_by, ph.changed_at,
           p.name AS patient_name, p.phone AS patient_phone
    FROM patient_history ph
    JOIN patient p ON ph.patient_id = p.patient_id
";
$params = [];
if ($filter_patient > 0) {
    $sql .= " WHERE ph.patient_id = ?";
    $params[] = $filter_patient;
}
$sql .= " ORDER BY ph.changed_at DESC LIMIT 500";

try {
    $history = $db->fetchAll($sql, $params);
} catch (Exception $e) {
    $error = "Erreur lors du chargement de l'historique : " . $e->getMessage();
    $history = [];
}

// Récupération de la liste des patients pour les sélecteurs
try {
    $patients = $db->fetchAll("SELECT patient_id, name, phone FROM patient ORDER BY name");
} catch (Exception $e) {
    $patients = [];
    $error = "Erreur lors du chargement des patients : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historique patient - MediCore HMS</title>
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
    <h1>Historique patient</h1>
    <p class="subtitle">Suivi des modifications des dossiers patients</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Formulaire d'ajout manuel -->
<div class="card">
    <div class="card-header"><h2>Ajouter une note manuelle</h2></div>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Patient *</label>
                <select name="patient_id" class="form-control" required>
                    <option value="">Sélectionner un patient</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['patient_id'] ?>" <?= ($filter_patient == $p['patient_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?> — <?= htmlspecialchars($p['phone'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Champ modifié</label>
                <input type="text" name="field_changed" class="form-control" placeholder="ex : allergies, adresse">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Ancienne valeur</label>
                <textarea name="old_value" class="form-control" rows="2" placeholder="Valeur précédente..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Nouvelle valeur</label>
                <textarea name="new_value" class="form-control" rows="2" placeholder="Nouvelle valeur..."></textarea>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description / Raison *</label>
            <input type="text" name="change_description" class="form-control" required placeholder="Pourquoi cette modification ?">
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer la note</button>
    </form>
</div>

<!-- Filtre -->
<div class="card" style="padding:1rem 1.5rem;">
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <strong>Filtrer par patient :</strong>
        <select class="form-control" style="max-width:300px;" onchange="location.href='patient_history.php'+(this.value?'?patient_id='+this.value:'')">
            <option value="">Tous les patients</option>
            <?php foreach ($patients as $p): ?>
                <option value="<?= $p['patient_id'] ?>" <?= $filter_patient == $p['patient_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="searchHist" class="form-control" style="max-width:250px;" placeholder="Rechercher dans l'historique...">
        <span class="text-muted text-small"><?= count($history) ?> enregistrement(s)</span>
    </div>
</div>

<!-- Tableau d'historique -->
<div class="card">
    <div class="card-header"><h2>Historique des modifications</h2></div>
    <div class="table-responsive">
        <table class="data-table" id="histTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Champ</th>
                    <th>Ancien → Nouveau</th>
                    <th>Description</th>
                    <th>Modifié par</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= $h['history_id'] ?></td>
                    <td>
                        <strong><?= date('d M Y', strtotime($h['changed_at'])) ?></strong><br>
                        <span class="text-small text-muted"><?= date('H:i', strtotime($h['changed_at'])) ?></span>
                    </td>
                    <td>
                        <?= htmlspecialchars($h['patient_name']) ?><br>
                        <span class="text-small text-muted"><?= htmlspecialchars($h['patient_phone'] ?? '') ?></span>
                    </td>
                    <td><strong><?= htmlspecialchars($h['field_changed'] ?? '—') ?></strong></td>
                    <td>
                        <?php if (!empty($h['old_value'])): ?>
                            <span class="diff-old"><?= htmlspecialchars(substr($h['old_value'], 0, 60)) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($h['new_value'])): ?>
                            → <span class="diff-new"><?= htmlspecialchars(substr($h['new_value'], 0, 60)) ?></span>
                        <?php endif; ?>
                        <?php if (empty($h['old_value']) && empty($h['new_value'])): ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($h['change_description'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($h['changed_by'] ?? '—') ?></td>
                    <td>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette entrée ?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="history_id" value="<?= $h['history_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Suppr.</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <tr><td colspan="8" class="text-center">Aucun enregistrement trouvé.</td></tr>
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
