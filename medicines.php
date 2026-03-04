<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$error = '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    if ($search) {
        $medicines = $db->fetchAll("
            SELECT * FROM medicine 
            WHERE med_name LIKE ? OR generic_name LIKE ? OR category LIKE ?
            ORDER BY med_name
        ", ["%$search%", "%$search%", "%$search%"]);
    } else {
        $medicines = $db->fetchAll("SELECT * FROM medicine ORDER BY med_name");
    }
} catch (Exception $e) {
    $error = "Error fetching medicines: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy - MediCore HMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php
include 'includes/header.php'; ?>
    
    <div class="container">
        <?php
include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Pharmacy & Medicine Inventory</h1>
                <p class="subtitle">Manage medicine stock and inventory</p>
            </div>

            <?php
if ($error): ?>
                <div class="alert alert-error"><?php
echo $error; ?></div>
            <?php
endif; ?>

            <!-- Medicine Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?php
echo count($medicines); ?></h3>
                        <p>Total Medicines</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>
                            <?php

                                $outOfStock = array_filter($medicines, function($med) {
                                    return $med['stock_qty'] <= 0;
                                });
                                echo count($outOfStock);
                            ?>
                        </h3>
                        <p>Out of Stock</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>
                            <?php

                                $lowStock = array_filter($medicines, function($med) {
                                    return $med['stock_qty'] > 0 && $med['stock_qty'] <= ($med['reorder_level'] ?? 10);
                                });
                                echo count($lowStock);
                            ?>
                        </h3>
                        <p>Low Stock</p>
                    </div>
                </div>
            </div>

            <!-- Search and Action Bar -->
            <div class="card">
                <div class="card-header">
                    <h2>Medicine Inventory (<?php
echo count($medicines); ?>)</h2>
                    <div style="display: flex; gap: var(--space-sm); align-items: center;">
                        <form method="GET" style="display: flex; gap: var(--space-sm);">
                            <input type="text" name="search" class="form-control" style="width: 250px;" 
                                   placeholder="Search medicines..." value="<?php
echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php
if ($search): ?>
                                <a href="medicines.php" class="btn btn-secondary">Clear</a>
                            <?php
endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Medicines Table -->
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Medicine Name</th>
                                <th>Generic Name</th>
                                <th>Category</th>
                                <th>Manufacturer</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Reorder Level</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
if (empty($medicines)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">No medicines found.</td>
                                </tr>
                            <?php
else: ?>
                                <?php
foreach ($medicines as $med): ?>
                                    <?php
                                        $stockStatus = '';
                                        $stockClass = '';
                                        if ($med['stock_qty'] <= 0) {
                                            $stockStatus = 'Out of Stock';
                                            $stockClass = 'cancelled';
                                        } elseif ($med['stock_qty'] <= $med['reorder_level']) {
                                            $stockStatus = 'Low Stock';
                                            $stockClass = 'warning';
                                        } else {
                                            $stockStatus = 'In Stock';
                                            $stockClass = 'completed';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php
echo $med['medicine_id']; ?></td>
                                        <td><strong><?php
echo htmlspecialchars($med['med_name']); ?></strong></td>
                                        <td><?php
echo htmlspecialchars($med['generic_name'] ?? 'N/A'); ?></td>
                                        <td><?php
echo htmlspecialchars($med['category'] ?? 'N/A'); ?></td>
                                        <td><?php
echo htmlspecialchars($med['manufacturer'] ?? 'N/A'); ?></td>
                                        <td>৳<?php
echo number_format($med['unit_price'], 2); ?></td>
                                        <td><strong><?php
echo $med['stock_qty']; ?></strong></td>
                                        <td><?php
echo $med['reorder_level'] ?? 10; ?></td>
                                        <td>
                                            <?php

                                                if ($med['expiry_date']) {
                                                    $expiry = new DateTime($med['expiry_date']);
                                                    echo $expiry->format('M d, Y');
                                                } else {
                                                    echo 'N/A';
                                                }
                                            ?>
                                        </td>
                                        <td><span class="badge badge-<?php
echo $stockClass; ?>"><?php echo $stockStatus; ?></span></td>
                                    </tr>
                                <?php
endforeach; ?>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <?php
include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>