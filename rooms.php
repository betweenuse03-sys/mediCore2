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
    // Si fetchAll retourne false (erreur), on force un tableau vide
    if (!is_array($rooms)) {
        $rooms = [];
    }
} catch (Exception $e) {
    $error = "Erreur lors du chargement des chambres : " . $e->getMessage();
    // $rooms est déjà un tableau vide
}

// Fonctions utilitaires pour les statistiques (évitent des warnings)
function safe_sum_column($array, $column) {
    if (!is_array($array)) return 0;
    return array_sum(array_column($array, $column));
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
                <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
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
                        <h3><?= safe_sum_column($rooms, 'total_beds') ?></h3>
                        <p>Total lits</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?= safe_sum_column($rooms, 'available_beds') ?></h3>
                        <p>Lits disponibles</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?= safe_sum_column($rooms, 'occupied_beds') ?></h3>
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
                                        // Sécurisation des valeurs avec des valeurs par défaut
                                        $room_id        = $room['room_id'] ?? '';
                                        $room_number    = htmlspecialchars($room['room_number'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $room_type      = htmlspecialchars(str_replace('_', ' ', $room['room_type'] ?? ''), ENT_QUOTES, 'UTF-8');
                                        $ward           = htmlspecialchars($room['ward'] ?? 'Général', ENT_QUOTES, 'UTF-8');
                                        // Affiche l'étage s'il existe, sinon "RDC". Préserve la valeur 0.
                                        $floor_no       = (isset($room['floor_no']) && $room['floor_no'] !== '') ? $room['floor_no'] : 'RDC';
                                        $daily_rate     = number_format($room['daily_rate'] ?? 0, 2);
                                        $total_beds     = $room['total_beds'] ?? 0;
                                        $available_beds = $room['available_beds'] ?? 0;
                                        $occupied_beds  = $room['occupied_beds'] ?? 0;

                                        // Détermination du statut de la chambre
                                        $availabilityClass = ($available_beds > 0) ? 'available' : 'cancelled';
                                        $statusText = ($available_beds > 0) ? 'Disponible' : 'Complet';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($room_id, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><strong><?= $room_number ?></strong></td>
                                        <td><?= $room_type ?></td>
                                        <td><?= $ward ?></td>
                                        <td><?= htmlspecialchars($floor_no, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>৳<?= $daily_rate ?></td>
                                        <td><strong><?= (int)$total_beds ?></strong></td>
                                        <td><span class="text-success"><?= (int)$available_beds ?></span></td>
                                        <td><span class="text-danger"><?= (int)$occupied_beds ?></span></td>
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
        // Fonction de recherche (identique, mais avec une vérification que l'élément existe)
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchRoom');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#roomTableBody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>
