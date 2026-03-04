<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        try {
            $vital_signs = null;
            if (!empty($_POST['temperature']) || !empty($_POST['blood_pressure']) || !empty($_POST['heart_rate']) || !empty($_POST['spo2'])) {
                $vital_signs = json_encode([
                    'temperature'    => $_POST['temperature'] ?? null,
                    'blood_pressure' => $_POST['blood_pressure'] ?? null,
                    'heart_rate'     => $_POST['heart_rate'] ?? null,
                    'spo2'           => $_POST['spo2'] ?? null,
                    'weight_kg'      => $_POST['weight_kg'] ?? null,
                    'height_cm'      => $_POST['height_cm'] ?? null,
                ]);
            }
            $sql = "INSERT INTO encounter (patient_id, doctor_id, appt_id, encounter_date, encounter_type, chief_complaint, diagnosis, treatment_plan, vital_signs, physical_examination, doctor_notes, follow_up_required, follow_up_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [
                $_POST['patient_id'],
                $_POST['doctor_id'],
                !empty($_POST['appt_id']) ? $_POST['appt_id'] : null,
                $_POST['encounter_date'],
                $_POST['encounter_type'],
                $_POST['chief_complaint'],
                $_POST['diagnosis'],
                $_POST['treatment_plan'],
                $vital_signs,
                $_POST['physical_examination'],
                $_POST['doctor_notes'],
                isset($_POST['follow_up_required']) ? 1 : 0,
                !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null,
                $_POST['status'] ?? 'ACTIVE',
            ]);
            $message = "Encounter record created successfully!";
        } catch (Exception $e) {
            $error = "Error creating encounter: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'edit') {
        try {
            $sql = "UPDATE encounter SET patient_id=?, doctor_id=?, encounter_date=?, encounter_type=?, chief_complaint=?, diagnosis=?, treatment_plan=?, physical_examination=?, doctor_notes=?, follow_up_required=?, follow_up_date=?, status=? WHERE encounter_id=?";
            $db->execute($sql, [
                $_POST['patient_id'],
                $_POST['doctor_id'],
                $_POST['encounter_date'],
                $_POST['encounter_type'],
                $_POST['chief_complaint'],
                $_POST['diagnosis'],
                $_POST['treatment_plan'],
                $_POST['physical_examination'],
                $_POST['doctor_notes'],
                isset($_POST['follow_up_required']) ? 1 : 0,
                !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null,
                $_POST['status'],
                $_POST['encounter_id'],
            ]);
            $message = "Encounter updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating encounter: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete') {
        try {
            $db->execute("DELETE FROM encounter WHERE encounter_id=?", [$_POST['encounter_id']]);
            $message = "Encounter deleted successfully!";
        } catch (Exception $e) {
            $error = "Error deleting encounter: " . $e->getMessage();
        }
    }
}

// Fetch encounters
$encounters = $db->fetchAll("
    SELECT e.encounter_id, e.encounter_date, e.encounter_type, e.chief_complaint,
           e.diagnosis, e.treatment_plan, e.follow_up_required, e.follow_up_date,
           e.status, e.vital_signs, e.doctor_notes,
           p.name AS patient_name, p.phone AS patient_phone,
           d.name AS doctor_name, dept.dept_name,
           a.appt_id
    FROM encounter e
    JOIN patient p ON e.patient_id = p.patient_id
    JOIN doctor  d ON e.doctor_id  = d.doctor_id
    JOIN department dept ON d.dept_id = dept.dept_id
    LEFT JOIN appointment a ON e.appt_id = a.appt_id
    ORDER BY e.encounter_date DESC
    LIMIT 200
");

$patients  = $db->fetchAll("SELECT patient_id, name, phone FROM patient ORDER BY name");
$doctors   = $db->fetchAll("SELECT d.doctor_id, d.name, d.specialization, dept.dept_name FROM doctor d JOIN department dept ON d.dept_id=dept.dept_id WHERE d.status='ACTIVE' ORDER BY d.name");

$edit_encounter = null;
if (isset($_GET['edit'])) {
    $edit_encounter = $db->fetchOne("SELECT * FROM encounter WHERE encounter_id=?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Encounters - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>Clinical Encounters</h1>
    <p class="subtitle">Manage patient visit records, diagnoses, and treatment plans</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Add / Edit Form -->
<div class="card">
    <div class="card-header">
        <h2><?= $edit_encounter ? 'Edit Encounter' : 'New Encounter Record' ?></h2>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $edit_encounter ? 'edit' : 'add' ?>">
        <?php if ($edit_encounter): ?>
            <input type="hidden" name="encounter_id" value="<?= $edit_encounter['encounter_id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Patient *</label>
                <select name="patient_id" class="form-control" required>
                    <option value="">Select Patient</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['patient_id'] ?>" <?= ($edit_encounter && $edit_encounter['patient_id']==$p['patient_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?> — <?= $p['phone'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Doctor *</label>
                <select name="doctor_id" class="form-control" required>
                    <option value="">Select Doctor</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?= $d['doctor_id'] ?>" <?= ($edit_encounter && $edit_encounter['doctor_id']==$d['doctor_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?> — <?= $d['specialization'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Encounter Date & Time *</label>
                <input type="datetime-local" name="encounter_date" class="form-control" required
                    value="<?= $edit_encounter ? date('Y-m-d\TH:i', strtotime($edit_encounter['encounter_date'])) : date('Y-m-d\TH:i') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Type *</label>
                <select name="encounter_type" class="form-control" required>
                    <?php foreach (['OUTPATIENT','INPATIENT','EMERGENCY','FOLLOWUP'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($edit_encounter && $edit_encounter['encounter_type']==$t) ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="ACTIVE"  <?= ($edit_encounter && $edit_encounter['status']=='ACTIVE')  ? 'selected' : '' ?>>Active</option>
                    <option value="CLOSED"  <?= ($edit_encounter && $edit_encounter['status']=='CLOSED')  ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Chief Complaint *</label>
            <textarea name="chief_complaint" class="form-control" rows="2" required placeholder="Primary reason for visit..."><?= htmlspecialchars($edit_encounter['chief_complaint'] ?? '') ?></textarea>
        </div>

        <!-- Vital Signs section -->
        <fieldset style="border:1px solid var(--gray-200);border-radius:var(--radius-md);padding:1rem 1.25rem;margin-bottom:1rem;">
            <legend style="font-weight:700;font-size:.9rem;padding:0 .5rem;color:var(--primary-600)">📊 Vital Signs (JSON)</legend>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Temperature (°C)</label>
                    <input type="text" name="temperature" class="form-control" placeholder="e.g. 37.2"
                        value="<?= $edit_encounter ? (json_decode($edit_encounter['vital_signs'],true)['temperature'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Blood Pressure</label>
                    <input type="text" name="blood_pressure" class="form-control" placeholder="e.g. 120/80"
                        value="<?= $edit_encounter ? (json_decode($edit_encounter['vital_signs'],true)['blood_pressure'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Heart Rate (bpm)</label>
                    <input type="text" name="heart_rate" class="form-control" placeholder="e.g. 72"
                        value="<?= $edit_encounter ? (json_decode($edit_encounter['vital_signs'],true)['heart_rate'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">SpO₂ (%)</label>
                    <input type="text" name="spo2" class="form-control" placeholder="e.g. 98"
                        value="<?= $edit_encounter ? (json_decode($edit_encounter['vital_signs'],true)['spo2'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Weight (kg)</label>
                    <input type="text" name="weight_kg" class="form-control" placeholder="e.g. 70"
                        value="<?= $edit_encounter ? (json_decode($edit_encounter['vital_signs'],true)['weight_kg'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Height (cm)</label>
                    <input type="text" name="height_cm" class="form-control" placeholder="e.g. 175"
                        value="<?= $edit_encounter ? (json_decode($edit_encounter['vital_signs'],true)['height_cm'] ?? '') : '' ?>">
                </div>
            </div>
        </fieldset>

        <div class="form-group">
            <label class="form-label">Physical Examination</label>
            <textarea name="physical_examination" class="form-control" rows="2" placeholder="Examination findings..."><?= htmlspecialchars($edit_encounter['physical_examination'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Diagnosis</label>
                <textarea name="diagnosis" class="form-control" rows="3" placeholder="Clinical diagnosis..."><?= htmlspecialchars($edit_encounter['diagnosis'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Treatment Plan</label>
                <textarea name="treatment_plan" class="form-control" rows="3" placeholder="Planned treatment..."><?= htmlspecialchars($edit_encounter['treatment_plan'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Doctor Notes</label>
            <textarea name="doctor_notes" class="form-control" rows="2" placeholder="Additional notes..."><?= htmlspecialchars($edit_encounter['doctor_notes'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group" style="flex:0 0 auto;display:flex;align-items:center;gap:.5rem;margin-top:1.5rem;">
                <input type="checkbox" name="follow_up_required" id="fu_req" value="1" <?= ($edit_encounter && $edit_encounter['follow_up_required']) ? 'checked' : '' ?>>
                <label for="fu_req" class="form-label" style="margin:0">Follow-up Required</label>
            </div>
            <div class="form-group">
                <label class="form-label">Follow-up Date</label>
                <input type="date" name="follow_up_date" class="form-control"
                    value="<?= htmlspecialchars($edit_encounter['follow_up_date'] ?? '') ?>">
            </div>
        </div>

        <div style="display:flex;gap:1rem;margin-top:1rem;">
            <button type="submit" class="btn btn-primary"><?= $edit_encounter ? 'Update Encounter' : 'Save Encounter' ?></button>
            <?php if ($edit_encounter): ?>
                <a href="encounters.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Encounters Table -->
<div class="card">
    <div class="card-header">
        <h2>All Encounters (<?= count($encounters) ?>)</h2>
        <input type="text" id="searchEnc" class="form-control" style="max-width:300px;" placeholder="Search encounters...">
    </div>
    <div class="table-responsive">
        <table class="data-table" id="encTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Type</th>
                    <th>Complaint</th>
                    <th>Diagnosis</th>
                    <th>Follow-up</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($encounters as $enc): ?>
                <?php
                    $vitals = $enc['vital_signs'] ? json_decode($enc['vital_signs'], true) : [];
                    $vitals_str = implode(' | ', array_filter([
                        $vitals['temperature']    ? 'T:'.$vitals['temperature'].'°C' : '',
                        $vitals['blood_pressure']  ? 'BP:'.$vitals['blood_pressure']  : '',
                        $vitals['heart_rate']      ? 'HR:'.$vitals['heart_rate'].'bpm': '',
                        $vitals['spo2']            ? 'SpO₂:'.$vitals['spo2'].'%'      : '',
                    ]));
                ?>
                <tr>
                    <td><?= $enc['encounter_id'] ?></td>
                    <td>
                        <strong><?= date('M d, Y', strtotime($enc['encounter_date'])) ?></strong><br>
                        <span class="text-small text-muted"><?= date('h:i A', strtotime($enc['encounter_date'])) ?></span>
                        <?php if ($vitals_str): ?>
                            <br><span class="text-small" style="color:var(--primary-500)"><?= htmlspecialchars($vitals_str) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($enc['patient_name']) ?></strong><br>
                        <span class="text-small text-muted"><?= $enc['patient_phone'] ?></span>
                    </td>
                    <td>
                        <?= htmlspecialchars($enc['doctor_name']) ?><br>
                        <span class="text-small text-muted"><?= htmlspecialchars($enc['dept_name']) ?></span>
                    </td>
                    <td><span class="badge badge-<?= strtolower($enc['encounter_type']) ?>"><?= $enc['encounter_type'] ?></span></td>
                    <td><?= htmlspecialchars(substr($enc['chief_complaint'] ?? '', 0, 50)) ?></td>
                    <td><?= htmlspecialchars(substr($enc['diagnosis'] ?? '', 0, 50)) ?></td>
                    <td>
                        <?php if ($enc['follow_up_required']): ?>
                            <span style="color:var(--warning-500)">✅ <?= $enc['follow_up_date'] ?? 'TBD' ?></span>
                        <?php else: ?>
                            <span class="text-muted">No</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?= strtolower($enc['status']) ?>"><?= $enc['status'] ?></span></td>
                    <td>
                        <a href="encounters.php?edit=<?= $enc['encounter_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <a href="lab_orders.php?encounter_id=<?= $enc['encounter_id'] ?>" class="btn btn-primary btn-sm">Lab</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this encounter?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="encounter_id" value="<?= $enc['encounter_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Del</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($encounters)): ?>
                <tr><td colspan="10" class="text-center">No encounter records found.</td></tr>
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
document.getElementById('searchEnc').addEventListener('keyup', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#encTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
