<?php
require_once 'includes/auth_check.php';
require_admin(); // Admin only
require_once 'config/database.php';

$db = Database::getInstance();

// Filters
$filter_table  = $_GET['table']  ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_user   = $_GET['user']   ?? '';
$filter_from   = $_GET['from']   ?? date('Y-m-d', strtotime('-7 days'));
$filter_to     = $_GET['to']     ?? date('Y-m-d');

$where_parts = ["DATE(change_timestamp) BETWEEN ? AND ?"];
$params = [$filter_from, $filter_to];

if ($filter_table)  { $where_parts[] = "table_name = ?";  $params[] = $filter_table;  }
if ($filter_action) { $where_parts[] = "action = ?";       $params[] = $filter_action; }
if ($filter_user)   { $where_parts[] = "changed_by LIKE ?";$params[] = '%'.$filter_user.'%'; }

$where_sql = implode(' AND ', $where_parts);

$logs = $db->fetchAll("SELECT * FROM audit_log WHERE $where_sql ORDER BY change_timestamp DESC LIMIT 500", $params);

// Distinct tables for filter
$tables = $db->fetchAll("SELECT DISTINCT table_name FROM audit_log ORDER BY table_name");

// Summary
$summary = $db->fetchOne("SELECT COUNT(*) AS total, SUM(action='INSERT') AS inserts, SUM(action='UPDATE') AS updates, SUM(action='DELETE') AS deletes FROM audit_log WHERE $where_sql", $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audit Log - MediCore HMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
.json-preview { font-family: monospace; font-size:.75rem; background:#f8f8f8; padding:4px 8px; border-radius:4px; max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; cursor:pointer; }
.json-preview:hover { white-space:pre-wrap; overflow:visible; position:relative; z-index:10; background:#fff; border:1px solid var(--gray-300); box-shadow:var(--shadow-md); }
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
<div class="page-header">
    <h1>🔒 Audit Log</h1>
    <p class="subtitle">System-wide change trail from database triggers</p>
</div>

<!-- Summary Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem;">
    <div class="stat-card"><div class="stat-content"><h3><?= number_format($summary['total'] ?? 0) ?></h3><p>Total Events</p></div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#e8f5e9,#fff);"><div class="stat-content"><h3><?= number_format($summary['inserts'] ?? 0) ?></h3><p>Inserts</p></div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#e3f2fd,#fff);"><div class="stat-content"><h3><?= number_format($summary['updates'] ?? 0) ?></h3><p>Updates</p></div></div>
    <div class="stat-card" style="background:linear-gradient(135deg,#ffebee,#fff);"><div class="stat-content"><h3><?= number_format($summary['deletes'] ?? 0) ?></h3><p>Deletes</p></div></div>
</div>

<!-- Filter Form -->
<div class="card">
    <div class="card-header"><h2>Filter Audit Log</h2></div>
    <form method="GET" action="audit_log.php">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Table</label>
                <select name="table" class="form-control">
                    <option value="">All Tables</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= htmlspecialchars($t['table_name']) ?>" <?= $filter_table===$t['table_name']?'selected':'' ?>><?= htmlspecialchars($t['table_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Action</label>
                <select name="action" class="form-control">
                    <option value="">All Actions</option>
                    <option value="INSERT" <?= $filter_action==='INSERT'?'selected':'' ?>>Insert</option>
                    <option value="UPDATE" <?= $filter_action==='UPDATE'?'selected':'' ?>>Update</option>
                    <option value="DELETE" <?= $filter_action==='DELETE'?'selected':'' ?>>Delete</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Changed By</label>
                <input type="text" name="user" class="form-control" value="<?= htmlspecialchars($filter_user) ?>" placeholder="Username...">
            </div>
            <div class="form-group">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control" value="<?= $filter_from ?>">
            </div>
            <div class="form-group">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control" value="<?= $filter_to ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Apply Filters</button>
        <a href="audit_log.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<!-- Log Table -->
<div class="card">
    <div class="card-header">
        <h2>Audit Events (<?= count($logs) ?> shown, up to 500)</h2>
        <input type="text" id="searchLog" class="form-control" style="max-width:280px;" placeholder="Search log...">
    </div>
    <div class="table-responsive">
        <table class="data-table" id="logTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Timestamp</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Action</th>
                    <th>Changed By</th>
                    <th>IP Address</th>
                    <th>Old Values</th>
                    <th>New Values</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['log_id'] ?></td>
                    <td>
                        <strong><?= date('M d, Y', strtotime($log['change_timestamp'])) ?></strong><br>
                        <span class="text-small text-muted"><?= date('h:i:s A', strtotime($log['change_timestamp'])) ?></span>
                    </td>
                    <td><strong><?= htmlspecialchars($log['table_name']) ?></strong></td>
                    <td><?= htmlspecialchars($log['record_id'] ?? '—') ?></td>
                    <td>
                        <span class="badge badge-<?= $log['action']==='INSERT'?'scheduled':($log['action']==='DELETE'?'cancelled':'in_progress') ?>">
                            <?= $log['action'] ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($log['changed_by'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                    <td>
                        <?php if ($log['old_values']): ?>
                            <div class="json-preview" title="<?= htmlspecialchars($log['old_values']) ?>"><?= htmlspecialchars(substr($log['old_values'], 0, 80)) ?></div>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['new_values']): ?>
                            <div class="json-preview" title="<?= htmlspecialchars($log['new_values']) ?>"><?= htmlspecialchars(substr($log['new_values'], 0, 80)) ?></div>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="9" class="text-center">No audit events found for the selected filters.</td></tr>
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
document.getElementById('searchLog').addEventListener('keyup', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#logTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
