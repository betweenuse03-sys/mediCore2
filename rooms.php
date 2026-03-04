<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$error = '';
$rooms = []; // Initialisation par défaut

try {
    // Récupération de toutes les chambres avec le nombre de lits
    $rooms = $db->fetchAll("
        SELECT 
            r.room_id,
            r.room_number,
            r.room_type,
            r.ward,
            r.floor_no,
            r.daily_rate,
            COUNT(b.bed_id) as total_beds,
            SUM(CASE WHEN b.status = 'AVAILABLE' THEN 1 ELSE 0 END) as available_beds,
            SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) as occupied_beds
        FROM room r
        LEFT JOIN bed b ON r.room_id = b.room_id
        GROUP BY r.room_id, r.room_number, r.room_type, r.ward, r.floor_no, r.daily_rate
        ORDER BY r.room_number
    ");
} catch (Exception $e) {
    $error = "Erreur lors du chargement des chambres : " . $e->getMessage();
    // $rooms est déjà un tableau vide
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des chambres - MediCore HMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Gestion des chambres et lits</h1>
                <p class="subtitle">Visualiser la disponibilité des chambres et l'occupation des lits</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Statistiques des chambres -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?= count($rooms) ?></h3>
                        <p>Total chambres</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?= array_sum(array_column($rooms, 'total_beds')) ?></h3>
                        <p>Total lits</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?= array_sum(array_column($rooms, 'available_beds')) ?></h3>
                        <p>Lits disponibles</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?= array_sum(array_column($rooms, 'occupied_beds')) ?></h3>
                        <p>Lits occupés</p>
                    </div>
                </div>
            </div>

            <!-- Tableau des chambres -->
            <div class="card">
                <div class="card-header">
                    <h2>Disponibilité des chambres (<?= count($rooms) ?>)</h2>
                    <input type="text" id="searchRoom" class="form-control" style="max-width: 300px;" placeholder="Rechercher une chambre...">
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>N° chambre</th>
                                <th>Type</th>
                                <th>Service</th>
                                <th>Étage</th>
                                <th>Tarif journalier</th>
                                <th>Lits totaux</th>
                                <th>Disponibles</th>
                                <th>Occupés</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody id="roomTableBody">
                            <?php if (empty($rooms)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">Aucune chambre trouvée.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rooms as $room): ?>
                                    <?php
                                        // Détermination du statut de la chambre
                                        $availabilityClass = ($room['available_beds'] > 0) ? 'available' : 'cancelled';
                                        $statusText = ($room['available_beds'] > 0) ? 'Disponible' : 'Complet';
                                    ?>
                                    <tr>
                                        <td><?= $room['room_id'] ?></td>
                                        <td><strong><?= htmlspecialchars($room['room_number']) ?></strong></td>
                                        <td><?= htmlspecialchars(str_replace('_', ' ', $room['room_type'])) ?></td>
                                        <td><?= htmlspecialchars($room['ward'] ?? 'Général') ?></td>
                                        <td><?= $room['floor_no'] ?: 'RDC' ?></td>
                                        <td>৳<?= number_format($room['daily_rate'], 2) ?></td>
                                        <td><strong><?= $room['total_beds'] ?: '0' ?></strong></td>
                                        <td><span class="text-success"><?= $room['available_beds'] ?: '0' ?></span></td>
                                        <td><span class="text-danger"><?= $room['occupied_beds'] ?: '0' ?></span></td>
                                        <td><span class="badge badge-<?= $availabilityClass ?>"><?= $statusText ?></span></td>
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
    <script>
        // Fonction de recherche
        document.getElementById('searchRoom').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#roomTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
