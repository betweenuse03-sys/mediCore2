<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

$db = Database::getInstance();
$error = '';

try {
    // Get all rooms with bed count
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
    $error = "Error fetching rooms: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - MediCore HMS</title>
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
                <h1>Room & Bed Management</h1>
                <p class="subtitle">View room availability and bed occupancy</p>
            </div>

            <?php
if ($error): ?>
                <div class="alert alert-error"><?php
echo $error; ?></div>
            <?php
endif; ?>

            <!-- Room Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?php
echo count($rooms); ?></h3>
                        <p>Total Rooms</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?php
echo array_sum(array_column($rooms, 'total_beds')); ?></h3>
                        <p>Total Beds</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?php
echo array_sum(array_column($rooms, 'available_beds')); ?></h3>
                        <p>Available Beds</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-content">
                        <h3><?php
echo array_sum(array_column($rooms, 'occupied_beds')); ?></h3>
                        <p>Occupied Beds</p>
                    </div>
                </div>
            </div>

            <!-- Rooms Table -->
            <div class="card">
                <div class="card-header">
                    <h2>Room Availability (<?php
echo count($rooms); ?>)</h2>
                    <input type="text" id="searchRoom" class="form-control" style="max-width: 300px;" placeholder="Search rooms...">
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Room ID</th>
                                <th>Room Number</th>
                                <th>Type</th>
                                <th>Ward</th>
                                <th>Floor</th>
                                <th>Daily Rate</th>
                                <th>Total Beds</th>
                                <th>Available</th>
                                <th>Occupied</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="roomTableBody">
                            <?php
if (empty($rooms)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">No rooms found.</td>
                                </tr>
                            <?php
else: ?>
                                <?php
foreach ($rooms as $room): ?>
                                    <?php
                                        $availabilityClass = '';
                                        $statusText = '';
                                        if ($room['available_beds'] > 0) {
                                            $availabilityClass = 'available';
                                            $statusText = 'Available';
                                        } else {
                                            $availabilityClass = 'cancelled';
                                            $statusText = 'Full';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php
echo $room['room_id']; ?></td>
                                        <td><strong><?php
echo htmlspecialchars($room['room_number']); ?></strong></td>
                                        <td><?php
echo str_replace('_', ' ', $room['room_type']); ?></td>
                                        <td><?php
echo htmlspecialchars($room['ward'] ?? 'General'); ?></td>
                                        <td><?php
echo $room['floor_no'] ?: 'G'; ?></td>
                                        <td>৳<?php
echo number_format($room['daily_rate'], 2); ?></td>
                                        <td><strong><?php
echo $room['total_beds'] ?: '0'; ?></strong></td>
                                        <td><span class="text-success"><?php
echo $room['available_beds'] ?: '0'; ?></span></td>
                                        <td><span class="text-danger"><?php
echo $room['occupied_beds'] ?: '0'; ?></span></td>
                                        <td><span class="badge badge-<?php
echo $availabilityClass; ?>"><?php echo $statusText; ?></span></td>
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
    <script>
        // Search functionality
        document.getElementById('searchRoom').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#roomTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>