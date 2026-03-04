<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

// Constante pour le niveau de réapprovisionnement par défaut
const DEFAULT_REORDER_LEVEL = 10;

$db = Database::getInstance();
$error = '';

// Récupération et nettoyage du terme de recherche
$search = trim($_GET['search'] ?? '');

// Échappement des caractères spéciaux LIKE pour la recherche
$searchLike = $search;
if ($searchLike !== '') {
    // Échappe % et _ pour éviter qu'ils soient interprétés comme des wildcards
    $searchLike = str_replace(['%', '_'], ['\\%', '\\_'], $searchLike);
}

try {
    if ($search !== '') {
        // Utilisation du terme échappé dans la requête
        $medicines = $db->fetchAll("
            SELECT * FROM medicine 
            WHERE med_name LIKE ? OR generic_name LIKE ? OR category LIKE ?
            ORDER BY med_name
        ", ["%$searchLike%", "%$searchLike%", "%$searchLike%"]);
    } else {
        $medicines = $db->fetchAll("SELECT * FROM medicine ORDER BY med_name");
    }

    // Calcul des statistiques via des requêtes SQL (plus efficace)
    $totalMedicines = count($medicines);
    // Compter les ruptures de stock
    $outOfStockCount = $db->fetchOne("SELECT COUNT(*) FROM medicine WHERE stock_qty <= 0")['COUNT(*)'] ?? 0;
    // Compter les stocks faibles (stock > 0 et <= seuil)
    $lowStockCount = $db->fetchOne("SELECT COUNT(*) FROM medicine WHERE stock_qty > 0 AND stock_qty <= COALESCE(reorder_level, ?)", [DEFAULT_REORDER_LEVEL])['COUNT(*)'] ?? 0;

} catch (Exception $e) {
    $error = "Erreur lors du chargement des médicaments : " . $e->getMessage();
    // En production, on pourrait logger l'erreur sans l'afficher
    $medicines = [];
    $totalMedicines = 0;
    $outOfStockCount = 0;
    $lowStockCount = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacie - MediCore HMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Pharmacie et inventaire des médicaments</h1>
                <p class="subtitle">Gérer les stocks de médicaments</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?= $totalMedicines ?></h3>
                        <p>Total médicaments</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?= $outOfStockCount ?></h3>
                        <p>Rupture de stock</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?= $lowStockCount ?></h3>
                        <p>Stock faible</p>
                    </div>
                </div>
            </div>

            <!-- Barre de recherche et actions -->
            <div class="card">
                <div class="card-header">
                    <h2>Inventaire des médicaments (<?= $totalMedicines ?>)</h2>
                    <div style="display: flex; gap: var(--space-sm); align-items: center;">
                        <form method="GET" style="display: flex; gap: var(--space-sm);">
                            <label for="search" class="sr-only">Rechercher un médicament</label>
                            <input type="text" name="search" id="search" class="form-control" style="width: 250px;" 
                                   placeholder="Rechercher un médicament..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">Rechercher</button>
                            <?php if ($search !== ''): ?>
                                <a href="medicines.php" class="btn btn-secondary">Effacer</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Tableau des médicaments -->
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom du médicament</th>
                                <th>Nom générique</th>
                                <th>Catégorie</th>
                                <th>Fabricant</th>
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Seuil de réappro.</th>
                                <th>Date d'expiration</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($medicines)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">Aucun médicament trouvé.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($medicines as $med): ?>
                                    <?php
                                        // Détermination du statut et de la classe CSS associée
                                        $reorderLevel = $med['reorder_level'] ?? DEFAULT_REORDER_LEVEL;
                                        if ($med['stock_qty'] <= 0) {
                                            $stockStatus = 'Rupture';
                                            $stockClass = 'cancelled';
                                        } elseif ($med['stock_qty'] <= $reorderLevel) {
                                            $stockStatus = 'Stock faible';
                                            $stockClass = 'warning';
                                        } else {
                                            $stockStatus = 'En stock';
                                            $stockClass = 'completed';
                                        }

                                        // Formatage de la date d'expiration
                                        $expiryDisplay = 'N/A';
                                        if (!empty($med['expiry_date'])) {
                                            try {
                                                $expiry = new DateTime($med['expiry_date']);
                                                $expiryDisplay = $expiry->format('d M Y');
                                            } catch (Exception $e) {
                                                // Si la date est invalide, on garde 'N/A'
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $med['medicine_id'] ?></td>
                                        <td><strong><?= htmlspecialchars($med['med_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($med['generic_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($med['category'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($med['manufacturer'] ?? 'N/A') ?></td>
                                        <td>৳<?= number_format($med['unit_price'], 2) ?></td>
                                        <td><strong><?= $med['stock_qty'] ?></strong></td>
                                        <td><?= $reorderLevel ?></td>
                                        <td><?= htmlspecialchars($expiryDisplay) ?></td>
                                        <td><span class="badge badge-<?= $stockClass ?>"><?= $stockStatus ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
