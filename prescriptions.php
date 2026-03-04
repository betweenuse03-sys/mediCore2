<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

// Constantes pour les statuts autorisés
const PRESCRIPTION_STATUSES = ['ACTIVE', 'DISPENSED', 'CANCELLED'];

$db = Database::getInstance();
$message = '';
$error = '';

// Génération d'un token CSRF si nécessaire
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fonction de validation simple d'un ID
function validateId($id, $table, $idColumn, $db) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if (!$id) return false;
    $result = $db->fetchOne("SELECT $idColumn FROM $table WHERE $idColumn = ?", [$id]);
    return $result !== false;
}

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Erreur de sécurité. Veuillez réessayer.";
    } elseif ($_POST['action'] === 'add') {
        try {
            // Validation des champs requis
            $patientId = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$patientId || !validateId($patientId, 'patient', 'patient_id', $db)) {
                throw new Exception("Patient invalide.");
            }

            $doctorId = filter_var($_POST['doctor_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$doctorId || !validateId($doctorId, 'doctor', 'doctor_id', $db)) {
                throw new Exception("Médecin invalide.");
            }

            $diagnosis = trim($_POST['diagnosis'] ?? '');
            if (empty($diagnosis)) {
                throw new Exception("Le diagnostic est requis.");
            }

            $instructions = trim($_POST['instructions'] ?? '');
            if (empty($instructions)) {
                throw new Exception("Les instructions sont requises.");
            }

            $status = $_POST['status'] ?? '';
            if (!in_array($status, PRESCRIPTION_STATUSES)) {
                throw new Exception("Statut invalide.");
            }

            // Gestion de l'ID de rendez-vous (optionnel)
            $apptId = !empty($_POST['appt_id']) ? filter_var($_POST['appt_id'], FILTER_VALIDATE_INT) : null;
            // Si un appt_id est fourni mais invalide, on le force à null
            if ($apptId !== null && !validateId($apptId, 'appointment', 'appt_id', $db)) {
                $apptId = null;
            }

            // Insertion
            $sql = "INSERT INTO prescription (patient_id, doctor_id, appt_id, diagnosis, instructions, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [
                $patientId,
                $doctorId,
                $apptId,
                $diagnosis,
                $instructions,
                $status
            ]);
            $message = "Prescription créée avec succès !";
        } catch (Exception $e) {
            $error = "Erreur lors de la création : " . $e->getMessage();
        }
    }
}

// Récupération des prescriptions
try {
    $prescriptions = $db->fetchAll("
        SELECT 
            rx.rx_id,
            rx.issued_date,
            rx.diagnosis,
            rx.instructions,
            rx.status,
            p.name as patient_name,
            p.phone as patient_phone,
            fn_patient_age(p.dob) as patient_age,
            d.name as doctor_name,
            dept.dept_name
        FROM prescription rx
        JOIN patient p ON rx.patient_id = p.patient_id
        JOIN doctor d ON rx.doctor_id = d.doctor_id
        JOIN department dept ON d.dept_id = dept.dept_id
        ORDER BY rx.issued_date DESC
        LIMIT 100
    ");
} catch (Exception $e) {
    $error = "Erreur lors du chargement des prescriptions : " . $e->getMessage();
    $prescriptions = [];
}

// Récupération des patients pour la liste déroulante
try {
    $patients = $db->fetchAll("SELECT patient_id, name, phone FROM patient ORDER BY name");
} catch (Exception $e) {
    $patients = [];
    $error = "Erreur lors du chargement des patients : " . $e->getMessage();
}

// Récupération des médecins actifs
try {
    $doctors = $db->fetchAll("
        SELECT d.doctor_id, d.name, d.specialization, dept.dept_name 
        FROM doctor d 
        JOIN department dept ON d.dept_id = dept.dept_id 
        WHERE d.status = 'ACTIVE' 
        ORDER BY d.name
    ");
} catch (Exception $e) {
    $doctors = [];
    $error = "Erreur lors du chargement des médecins : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions - MediCore HMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Gestion des prescriptions</h1>
                <p class="subtitle">Créer et gérer les prescriptions médicales</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Formulaire de création -->
            <div class="card">
                <div class="card-header">
                    <h2>Créer une nouvelle prescription</h2>
                </div>
                <form method="POST" action="prescriptions.php">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Patient *</label>
                            <select name="patient_id" class="form-control" required>
                                <option value="">Sélectionner un patient</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?= $patient['patient_id'] ?>">
                                        <?= htmlspecialchars($patient['name']) ?> - <?= htmlspecialchars($patient['phone'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Médecin *</label>
                            <select name="doctor_id" class="form-control" required>
                                <option value="">Sélectionner un médecin</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?= $doctor['doctor_id'] ?>">
                                        <?= htmlspecialchars($doctor['name']) ?> - <?= htmlspecialchars($doctor['specialization'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">ID rendez-vous (optionnel)</label>
                            <input type="number" name="appt_id" class="form-control" placeholder="Laisser vide si non lié">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Diagnostic *</label>
                        <textarea name="diagnosis" class="form-control" rows="3" required placeholder="Saisir le diagnostic..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Instructions *</label>
                        <textarea name="instructions" class="form-control" rows="4" required placeholder="Instructions médicamenteuses et plan de traitement..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Statut *</label>
                        <select name="status" class="form-control" required>
                            <option value="ACTIVE">Active</option>
                            <option value="DISPENSED">Délivrée</option>
                            <option value="CANCELLED">Annulée</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Créer la prescription</button>
                </form>
            </div>

            <!-- Liste des prescriptions -->
            <div class="card">
                <div class="card-header">
                    <h2>Toutes les prescriptions (<?= count($prescriptions) ?>)</h2>
                    <input type="text" id="searchRx" class="form-control" style="max-width: 300px;" placeholder="Rechercher une prescription...">
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Médecin</th>
                                <th>Département</th>
                                <th>Diagnostic</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rxTableBody">
                            <?php foreach ($prescriptions as $rx): ?>
                                <tr>
                                    <td><strong>#<?= $rx['rx_id'] ?></strong></td>
                                    <td>
                                        <?= date('d M Y', strtotime($rx['issued_date'])) ?><br>
                                        <span class="text-small text-muted"><?= date('H:i', strtotime($rx['issued_date'])) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($rx['patient_name']) ?></strong><br>
                                        <span class="text-small text-muted">
                                            Âge: <?= htmlspecialchars($rx['patient_age'] ?? '?') ?> | <?= htmlspecialchars($rx['patient_phone'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($rx['doctor_name']) ?></td>
                                    <td><?= htmlspecialchars($rx['dept_name']) ?></td>
                                    <td><?= htmlspecialchars(substr($rx['diagnosis'], 0, 50)) . (strlen($rx['diagnosis']) > 50 ? '...' : '') ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($rx['status']) ?>">
                                            <?= $rx['status'] === 'ACTIVE' ? 'Active' : ($rx['status'] === 'DISPENSED' ? 'Délivrée' : 'Annulée') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="prescription_details.php?rx_id=<?= $rx['rx_id'] ?>" class="btn btn-primary btn-sm">Médicaments</a>
                                        <button onclick="viewPrescription(<?= $rx['rx_id'] ?>)" class="btn btn-secondary btn-sm">
                                            Voir
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($prescriptions)): ?>
                                <tr><td colspan="8" class="text-center">Aucune prescription trouvée.</td></tr>
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
        document.getElementById('searchRx').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#rxTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        function viewPrescription(rxId) {
            alert('Consultation de la prescription #' + rxId + '\n(Cette fonctionnalité ouvrirait une vue détaillée.)');
        }
    </script>
</body>
</html>
