<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

// Constantes pour les valeurs autorisées
const STAFF_STATUSES = ['ACTIVE', 'ON_LEAVE', 'INACTIVE'];
const SHIFT_TYPES = ['MORNING', 'EVENING', 'NIGHT', 'ROTATING'];

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
                // Champs obligatoires
                $name = trim($_POST['name'] ?? '');
                $role = trim($_POST['role'] ?? '');
                if (empty($name)) throw new Exception("Le nom est requis.");
                if (empty($role)) throw new Exception("Le rôle est requis.");

                // Département (optionnel)
                $deptId = !empty($_POST['dept_id']) ? filter_var($_POST['dept_id'], FILTER_VALIDATE_INT) : null;
                if ($deptId && !recordExists($db, 'department', 'dept_id', $deptId)) {
                    $deptId = null; // ignorer si invalide
                }

                // Email (optionnel)
                $email = trim($_POST['email'] ?? '');
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Adresse email invalide.");
                }

                // Téléphone (simple nettoyage)
                $phone = trim($_POST['phone'] ?? '') ?: null;

                // Dates (optionnelles)
                $dob = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
                if ($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                    throw new Exception("Format de date de naissance invalide.");
                }
                $joiningDate = !empty($_POST['joining_date']) ? $_POST['joining_date'] : null;
                if ($joiningDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $joiningDate)) {
                    throw new Exception("Format de date d'embauche invalide.");
                }

                // Salaire (optionnel)
                $salary = !empty($_POST['salary']) ? filter_var($_POST['salary'], FILTER_VALIDATE_FLOAT) : null;
                if ($salary !== null && $salary < 0) {
                    throw new Exception("Le salaire ne peut pas être négatif.");
                }

                // Shift et status
                $shift = $_POST['shift_type'] ?? '';
                if (!in_array($shift, SHIFT_TYPES)) {
                    throw new Exception("Type de shift invalide.");
                }
                $status = $_POST['status'] ?? '';
                if (!in_array($status, STAFF_STATUSES)) {
                    throw new Exception("Statut invalide.");
                }

                $address = trim($_POST['address'] ?? '') ?: null;

                // Insertion
                $sql = "INSERT INTO staff (dept_id, name, role, qualification, phone, email, address, date_of_birth, joining_date, salary, shift_type, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $db->execute($sql, [
                    $deptId,
                    $name,
                    $role,
                    !empty($_POST['qualification']) ? trim($_POST['qualification']) : null,
                    $phone,
                    $email ?: null,
                    $address,
                    $dob,
                    $joiningDate,
                    $salary,
                    $shift,
                    $status
                ]);
                $message = "Membre du personnel ajouté avec succès !";
            }

            // ===================== ÉDITION =====================
            elseif ($_POST['action'] === 'edit') {
                // Validation ID
                $staffId = filter_var($_POST['staff_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$staffId || !recordExists($db, 'staff', 'staff_id', $staffId)) {
                    throw new Exception("Membre du personnel invalide.");
                }

                // Mêmes validations que pour l'ajout
                $name = trim($_POST['name'] ?? '');
                $role = trim($_POST['role'] ?? '');
                if (empty($name)) throw new Exception("Le nom est requis.");
                if (empty($role)) throw new Exception("Le rôle est requis.");

                $deptId = !empty($_POST['dept_id']) ? filter_var($_POST['dept_id'], FILTER_VALIDATE_INT) : null;
                if ($deptId && !recordExists($db, 'department', 'dept_id', $deptId)) {
                    $deptId = null;
                }

                $email = trim($_POST['email'] ?? '');
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Adresse email invalide.");
                }

                $phone = trim($_POST['phone'] ?? '') ?: null;

                $dob = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
                if ($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                    throw new Exception("Format de date de naissance invalide.");
                }
                $joiningDate = !empty($_POST['joining_date']) ? $_POST['joining_date'] : null;
                if ($joiningDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $joiningDate)) {
                    throw new Exception("Format de date d'embauche invalide.");
                }

                $salary = !empty($_POST['salary']) ? filter_var($_POST['salary'], FILTER_VALIDATE_FLOAT) : null;
                if ($salary !== null && $salary < 0) {
                    throw new Exception("Le salaire ne peut pas être négatif.");
                }

                $shift = $_POST['shift_type'] ?? '';
                if (!in_array($shift, SHIFT_TYPES)) {
                    throw new Exception("Type de shift invalide.");
                }
                $status = $_POST['status'] ?? '';
                if (!in_array($status, STAFF_STATUSES)) {
                    throw new Exception("Statut invalide.");
                }

                $address = trim($_POST['address'] ?? '') ?: null;

                $sql = "UPDATE staff SET dept_id=?, name=?, role=?, qualification=?, phone=?, email=?, address=?, date_of_birth=?, joining_date=?, salary=?, shift_type=?, status=? WHERE staff_id=?";
                $db->execute($sql, [
                    $deptId,
                    $name,
                    $role,
                    !empty($_POST['qualification']) ? trim($_POST['qualification']) : null,
                    $phone,
                    $email ?: null,
                    $address,
                    $dob,
                    $joiningDate,
                    $salary,
                    $shift,
                    $status,
                    $staffId
                ]);
                $message = "Fiche personnel mise à jour.";
            }

            // ===================== SUPPRESSION =====================
            elseif ($_POST['action'] === 'delete') {
                $staffId = filter_var($_POST['staff_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$staffId || !recordExists($db, 'staff', 'staff_id', $staffId)) {
                    throw new Exception("Membre du personnel invalide.");
                }
                $db->execute("DELETE FROM staff WHERE staff_id=?", [$staffId]);
                $message = "Membre du personnel supprimé.";
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupération de la liste du personnel
try {
    $staff = $db->fetchAll("
        SELECT s.*, dept.dept_name
        FROM staff s
        LEFT JOIN department dept ON s.dept_id = dept.dept_id
        ORDER BY s.name
    ");
} catch (Exception $e) {
    $error = "Erreur lors du chargement du personnel : " . $e->getMessage();
    $staff = [];
}

// Récupération des départements pour le sélecteur
try {
    $departments = $db->fetchAll("SELECT dept_id, dept_name FROM department ORDER BY dept_name");
} catch (Exception $e) {
    $departments = [];
    $error = "Erreur lors du chargement des départements : " . $e->getMessage();
}

// Préparation de l'édition
$edit_staff = null;
if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId) {
        $edit_staff = $db->fetchOne("SELECT * FROM staff WHERE staff_id=?", [$editId]);
    }
}

// Statistiques
$active_count = array_reduce($staff, fn($c, $s) => $c + ($s['status'] === 'ACTIVE' ? 1 : 0), 0);
$on_leave     = array_reduce($staff, fn($c, $s) => $c + ($s['status'] === 'ON_LEAVE' ? 1 : 0), 0);
$total_payroll = array_reduce($staff, fn($c, $s) => $c + floatval($s['salary'] ?? 0), 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Personnel - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>Gestion du personnel</h1>
    <p class="subtitle">Gérer les infirmiers, techniciens, administratifs et autres</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Statistiques -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem;">
    <div class="stat-card"><div class="stat-content"><h3><?= count($staff) ?></h3><p>Total personnel</p></div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#e8f5e9,#fff);"><div class="stat-content"><h3><?= $active_count ?></h3><p>Actifs</p></div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#fff3e0,#fff);"><div class="stat-content"><h3><?= $on_leave ?></h3><p>En congé</p></div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#e3f2fd,#fff);"><div class="stat-content"><h3>৳<?= number_format($total_payroll, 0) ?></h3><p>Masse salariale mensuelle</p></div></div>
</div>

<!-- Formulaire d'ajout / édition -->
<div class="card">
    <div class="card-header"><h2><?= $edit_staff ? 'Modifier un membre' : 'Ajouter un membre' ?></h2></div>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $edit_staff ? 'edit' : 'add' ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <?php if ($edit_staff): ?>
            <input type="hidden" name="staff_id" value="<?= $edit_staff['staff_id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nom complet *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_staff['name'] ?? '') ?>" placeholder="Nom complet">
            </div>
            <div class="form-group">
                <label class="form-label">Rôle / Poste *</label>
                <input type="text" name="role" class="form-control" required value="<?= htmlspecialchars($edit_staff['role'] ?? '') ?>" placeholder="ex : Infirmier, Réceptionniste">
            </div>
            <div class="form-group">
                <label class="form-label">Département</label>
                <select name="dept_id" class="form-control">
                    <option value="">Aucun département</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['dept_id'] ?>" <?= ($edit_staff && $edit_staff['dept_id'] == $d['dept_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['dept_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Qualification</label>
                <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($edit_staff['qualification'] ?? '') ?>" placeholder="ex : Diplôme d'infirmier">
            </div>
            <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($edit_staff['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_staff['email'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Date de naissance</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($edit_staff['date_of_birth'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Date d'embauche</label>
                <input type="date" name="joining_date" class="form-control" value="<?= htmlspecialchars($edit_staff['joining_date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Salaire mensuel (৳)</label>
                <input type="number" step="0.01" min="0" name="salary" class="form-control" value="<?= htmlspecialchars($edit_staff['salary'] ?? '') ?>" placeholder="0.00">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Type de shift</label>
                <select name="shift_type" class="form-control">
                    <?php foreach (SHIFT_TYPES as $sh): ?>
                        <option value="<?= $sh ?>" <?= ($edit_staff && $edit_staff['shift_type'] === $sh) ? 'selected' : '' ?>>
                            <?= str_replace('_', ' ', $sh) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Statut</label>
                <select name="status" class="form-control">
                    <?php foreach (STAFF_STATUSES as $st): ?>
                        <option value="<?= $st ?>" <?= ($edit_staff && $edit_staff['status'] === $st) ? 'selected' : '' ?>>
                            <?= str_replace('_', ' ', $st) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Adresse</label>
            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($edit_staff['address'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:1rem;">
            <button type="submit" class="btn btn-primary"><?= $edit_staff ? 'Mettre à jour' : 'Ajouter' ?></button>
            <?php if ($edit_staff): ?>
                <a href="staff.php" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tableau du personnel -->
<div class="card">
    <div class="card-header">
        <h2>Personnel (<?= count($staff) ?>)</h2>
        <div style="display:flex;gap:.5rem;">
            <select id="filterStaffStatus" class="form-control" style="max-width:160px;">
                <option value="">Tous statuts</option>
                <option value="ACTIVE">Actif</option>
                <option value="ON_LEAVE">En congé</option>
                <option value="INACTIVE">Inactif</option>
            </select>
            <select id="filterShift" class="form-control" style="max-width:160px;">
                <option value="">Tous shifts</option>
                <?php foreach (SHIFT_TYPES as $sh): ?>
                    <option value="<?= $sh ?>"><?= str_replace('_', ' ', $sh) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="searchStaff" class="form-control" style="max-width:250px;" placeholder="Rechercher...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="staffTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Rôle</th>
                    <th>Département</th>
                    <th>Qualification</th>
                    <th>Téléphone</th>
                    <th>Shift</th>
                    <th>Salaire</th>
                    <th>Statut</th>
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
                    <td><?= str_replace('_', ' ', $s['shift_type']) ?></td>
                    <td><?= $s['salary'] ? '৳'.number_format($s['salary'], 2) : '—' ?></td>
                    <td><span class="badge badge-<?= strtolower(str_replace('_','-',$s['status'])) ?>"><?= str_replace('_', ' ', $s['status']) ?></span></td>
                    <td>
                        <a href="staff.php?edit=<?= $s['staff_id'] ?>" class="btn btn-secondary btn-sm">Modifier</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce membre du personnel ?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="staff_id" value="<?= $s['staff_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Suppr.</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($staff)): ?>
                <tr><td colspan="10" class="text-center">Aucun personnel trouvé.</td></tr>
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
    const s = fStatus.value;
    const sh = fShift.value;
    const q = fSearch.value.toLowerCase().trim();
    document.querySelectorAll('#staffTable tbody tr').forEach(row => {
        const matchStatus = !s || row.dataset.status === s;
        const matchShift  = !sh || row.dataset.shift === sh;
        const matchSearch = !q || row.textContent.toLowerCase().includes(q);
        row.style.display = (matchStatus && matchShift && matchSearch) ? '' : 'none';
    });
}
fStatus.addEventListener('change', applyStaffFilter);
fShift.addEventListener('change', applyStaffFilter);
fSearch.addEventListener('keyup', applyStaffFilter);
</script>
</body>
</html>
