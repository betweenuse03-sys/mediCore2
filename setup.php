<?php
require_once 'includes/auth_check.php';
require_admin(); // Only admins can run setup

require_once 'config/database.php';

$message = '';
$error   = '';
$log     = [];

function runSQLFile(PDO $pdo, string $filepath, array &$log): void {
    if (!file_exists($filepath)) {
        $log[] = ['type'=>'error', 'msg'=>"File not found: $filepath"];
        return;
    }

    $sql  = file_get_contents($filepath);
    $name = basename($filepath);

    // Handle DELIMITER changes for stored routines/triggers
    // Split on DELIMITER keyword
    if (preg_match('/DELIMITER\s+/i', $sql)) {
        // Run via pdo exec after stripping delimiter lines for basic parsing
        // Use a simpler approach: split on ;; and ; appropriately
        $chunks = preg_split('/DELIMITER\s+(\S+)/i', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
        $current_delim = ';';
        $buffer = '';

        $lines = explode("\n", $sql);
        $body  = '';
        $delim = ';';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^DELIMITER\s+(\S+)/i', $trimmed, $m)) {
                $delim = $m[1];
                continue;
            }
            $body .= $line . "\n";
            if ($delim !== ';' && str_ends_with(rtrim($trimmed), $delim)) {
                // end of block — strip delimiter and execute
                $stmt = rtrim(substr(rtrim($body), 0, -strlen($delim)));
                $stmt = trim($stmt);
                if (!empty($stmt) && !preg_match('/^(--|#|SELECT\s+[\'"])/i', $stmt)) {
                    try {
                        $pdo->exec($stmt);
                        $log[] = ['type'=>'ok', 'msg'=>"Executed block in $name"];
                    } catch (PDOException $e) {
                        $log[] = ['type'=>'warn', 'msg'=>"Warn in $name: ".$e->getMessage()];
                    }
                }
                $body = '';
            } elseif ($delim === ';' && str_ends_with(rtrim($trimmed), ';')) {
                $stmt = trim($body);
                if (!empty($stmt) && !preg_match('/^(--|#|SELECT\s+[\'"])/i', $stmt)) {
                    try {
                        $pdo->exec($stmt);
                    } catch (PDOException $e) {
                        $log[] = ['type'=>'warn', 'msg'=>"Warn in $name: ".$e->getMessage()];
                    }
                }
                $body = '';
            }
        }
    } else {
        // Plain SQL: split on ;
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        $ok = 0;
        foreach ($statements as $stmt) {
            if (empty($stmt) || preg_match('/^(--|#|SELECT\s+[\'"])/i', $stmt)) continue;
            try {
                $pdo->exec($stmt);
                $ok++;
            } catch (PDOException $e) {
                $log[] = ['type'=>'warn', 'msg'=>"Warn in $name: ".$e->getMessage()];
            }
        }
        $log[] = ['type'=>'ok', 'msg'=>"✓ $name — executed $ok statements"];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo = Database::getInstance()->getConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $action = $_POST['action'];

        if ($action === 'full_setup') {
            // Run the master.sql (new full version)
            $masterFile = __DIR__ . '/sql/master.sql';
            if (file_exists($masterFile)) {
                runSQLFile($pdo, $masterFile, $log);
                $message = 'Full setup (master.sql) completed!';
            } else {
                $error = 'master.sql not found.';
            }

        } elseif ($action === 'schema_only') {
            $files = [
                'sql/schemas/01_create_database.sql',
                'sql/schemas/02_create_tables.sql',
                'sql/schemas/03_constraints_and_fk.sql',
                'sql/schemas/04_indexes.sql',
                'sql/schemas/05_views.sql',
            ];
            foreach ($files as $f) runSQLFile($pdo, __DIR__."/$f", $log);
            $message = 'Schema created successfully!';

        } elseif ($action === 'routines') {
            $files = [
                'sql/routines/01_functions.sql',
                'sql/routines/02_procedures.sql',
                'sql/routines/03_triggers.sql',
            ];
            foreach ($files as $f) runSQLFile($pdo, __DIR__."/$f", $log);
            $message = 'Stored routines & triggers loaded!';

        } elseif ($action === 'seed_data') {
            $files = [
                'sql/data/01_seed_data.sql',
                'sql/data/02_extended_data.sql',
            ];
            foreach ($files as $f) runSQLFile($pdo, __DIR__."/$f", $log);
            $message = 'Seed data inserted!';

        } elseif ($action === 'legacy_data') {
            $files = [
                'sql/01_insert_departments.sql','sql/02_insert_doctors.sql',
                'sql/03_insert_patients.sql','sql/04_insert_rooms_beds.sql',
                'sql/05_insert_medicines.sql','sql/06_insert_appointments.sql',
                'sql/07_insert_prescriptions.sql',
            ];
            foreach ($files as $f) {
                if (file_exists(__DIR__."/$f")) runSQLFile($pdo, __DIR__."/$f", $log);
            }
            $message = 'Legacy Week 8 seed data loaded!';

        } elseif ($action === 'roles') {
            runSQLFile($pdo, __DIR__.'/sql/schemas/06_roles_access.sql', $log);
            $message = 'Database roles & access control applied!';
        }

    } catch (Exception $e) {
        $error = 'Error: '.$e->getMessage();
    }
}

// Stats
$db = Database::getInstance();
$tables = $db->fetchAll("SHOW TABLES") ?: [];
$tc = count($tables);
$routinesCount = count($db->fetchAll("SHOW PROCEDURE STATUS WHERE Db = DATABASE()") ?: [])
               + count($db->fetchAll("SHOW FUNCTION STATUS WHERE Db = DATABASE()") ?: []);
$triggerCount = count($db->fetchAll("SHOW TRIGGERS") ?: []);
$viewCount    = count($db->fetchAll("SHOW FULL TABLES WHERE Table_type = 'VIEW'") ?: []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Database Setup — MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
.setup-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:24px}
.setup-card{background:white;border:2px solid var(--gray-200);border-radius:12px;padding:20px;transition:all .2s}
.setup-card:hover{border-color:var(--primary-400);box-shadow:0 4px 16px rgba(21,101,192,.12)}
.setup-card h3{font-size:1rem;margin-bottom:6px;color:var(--gray-800)}
.setup-card p{font-size:.82rem;color:var(--gray-600);margin-bottom:14px;line-height:1.5}
.setup-card .btn{width:100%}
.log-box{background:#0d1117;color:#c9d1d9;border-radius:10px;padding:20px;max-height:340px;overflow-y:auto;font-family:monospace;font-size:.78rem;line-height:1.7}
.log-ok{color:#3fb950}.log-warn{color:#d29922}.log-error{color:#f85149}
.stat-mini{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px}
.smc{background:var(--primary-100);border-radius:10px;padding:12px 18px;text-align:center;flex:1;min-width:80px}
.smc strong{display:block;font-size:1.5rem;color:var(--primary-600)}
.smc span{font-size:.72rem;color:var(--gray-600)}
.badge-admin{display:inline-block;padding:3px 10px;background:#1565c0;color:white;border-radius:20px;font-size:.7rem;font-weight:700}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Database Setup <span class="badge-admin">Admin Only</span></h1>
                <p class="subtitle">Initialize MediCore HMS database with all schemas, routines & data</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><strong>✅ Done!</strong> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><strong>❌ Error!</strong> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- DB Stats -->
        <div class="stat-mini">
            <div class="smc"><strong><?= $tc ?></strong><span>Tables</span></div>
            <div class="smc"><strong><?= $routinesCount ?></strong><span>Routines</span></div>
            <div class="smc"><strong><?= $triggerCount ?></strong><span>Triggers</span></div>
            <div class="smc"><strong><?= $viewCount ?></strong><span>Views</span></div>
        </div>

        <!-- Action Cards -->
        <div class="setup-grid">
            <div class="setup-card">
                <h3>🚀 Full Setup (Recommended)</h3>
                <p>Runs master.sql — creates database, all 17 tables, constraints, indexes, views, 6 functions, 9 procedures, 5 triggers, and seeds all data.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="full_setup">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('This will DROP and recreate the database. Continue?')">Run Full Setup</button>
                </form>
            </div>
            <div class="setup-card">
                <h3>📐 Schema Only</h3>
                <p>Creates tables, constraints, foreign keys, indexes, and views. Does not load data or routines.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="schema_only">
                    <button type="submit" class="btn btn-secondary">Create Schema</button>
                </form>
            </div>
            <div class="setup-card">
                <h3>⚙️ Stored Routines & Triggers</h3>
                <p>Loads 6 stored functions, 9 stored procedures (with cursors & exception handling), and 5 triggers.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="routines">
                    <button type="submit" class="btn btn-secondary">Load Routines</button>
                </form>
            </div>
            <div class="setup-card">
                <h3>🌱 Seed Data (New)</h3>
                <p>Inserts full seed data: 8 departments, 16 doctors, 15 patients, 24 beds, 15 medicines, encounters, prescriptions, invoices, payments.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="seed_data">
                    <button type="submit" class="btn btn-secondary">Insert Seed Data</button>
                </form>
            </div>
            <div class="setup-card">
                <h3>📦 Legacy Week 8 Data</h3>
                <p>Loads original Week 8 insert files (departments, doctors, patients, rooms, medicines, appointments, prescriptions).</p>
                <form method="POST">
                    <input type="hidden" name="action" value="legacy_data">
                    <button type="submit" class="btn btn-secondary">Load Week 8 Data</button>
                </form>
            </div>
            <div class="setup-card">
                <h3>🔐 Roles & Access Control</h3>
                <p>Creates MySQL users with role-based permissions: admin, doctor, nurse, billing, readonly.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="roles">
                    <button type="submit" class="btn btn-secondary">Apply Roles</button>
                </form>
            </div>
        </div>

        <!-- SQL File Tree -->
        <div class="card">
            <div class="card-header"><h2>📂 Integrated SQL Files</h2></div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;padding:4px 0">
                <?php
                $groups = [
                    'schemas/'  => ['01_create_database.sql','02_create_tables.sql','03_constraints_and_fk.sql','04_indexes.sql','05_views.sql','06_roles_access.sql'],
                    'routines/' => ['01_functions.sql','02_procedures.sql','03_triggers.sql'],
                    'data/'     => ['01_seed_data.sql','02_extended_data.sql'],
                    'queries/'  => ['01_all_queries.sql','02_transactions_demo.sql'],
                ];
                $colors = ['schemas/'=>'#e3f2fd','routines/'=>'#f3e5f5','data/'=>'#e8f5e9','queries/'=>'#fff3e0'];
                foreach ($groups as $folder => $files):
                    foreach ($files as $fname):
                        $fullpath = __DIR__."/sql/$folder$fname";
                        $exists = file_exists($fullpath);
                        $size = $exists ? round(filesize($fullpath)/1024,1).'KB' : '—';
                ?>
                <div style="background:<?= $colors[$folder] ?>;border-radius:8px;padding:10px 14px;font-size:.78rem">
                    <div style="font-weight:700;color:#333"><?= $fname ?></div>
                    <div style="color:#666;margin-top:2px"><?= $folder ?> · <?= $size ?></div>
                    <?= $exists ? '<span style="color:#2e7d32">✓ Present</span>' : '<span style="color:#c62828">✗ Missing</span>' ?>
                </div>
                <?php endforeach; endforeach; ?>
            </div>
        </div>

        <?php if (!empty($log)): ?>
        <div class="card">
            <div class="card-header"><h2>Execution Log</h2></div>
            <div class="log-box">
                <?php foreach ($log as $entry): ?>
                    <div class="log-<?= $entry['type'] ?>"><?= htmlspecialchars($entry['msg']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
