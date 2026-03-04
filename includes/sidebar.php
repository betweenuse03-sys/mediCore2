<?php if (session_status() === PHP_SESSION_NONE) session_start();
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
function sb_link($href, $label, $icon_path) {
    $active = (basename($href) === basename($_SERVER['PHP_SELF'])) ? ' sidebar-link-active' : '';
    echo "<li><a href=\"$href\" class=\"sidebar-link$active\"><svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\">$icon_path</svg><span>$label</span></a></li>";
}
?>
<aside class="sidebar">

    <div class="sidebar-section">
        <h3>Quick Actions</h3>
        <ul class="sidebar-menu">
            <?php sb_link('patients.php?action=add',        'Add Patient',           '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>'); ?>
            <?php sb_link('appointments.php?action=schedule','Schedule Appointment',  '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>'); ?>
            <?php sb_link('prescriptions.php?action=add',   'New Prescription',      '<path d="M14 2H6a2 2 0 0 0-2 2v16h14V8z"/><polyline points="14 2 14 8 20 8"/>'); ?>
            <?php sb_link('encounters.php',                 'New Encounter',         '<circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/>'); ?>
            <?php sb_link('invoices.php',                   'New Invoice',           '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>'); ?>
        </ul>
    </div>

    <div class="sidebar-section">
        <h3>Core Management</h3>
        <ul class="sidebar-menu">
            <?php sb_link('patients.php',     'Patients',      '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'); ?>
            <?php sb_link('doctors.php',      'Doctors',       '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'); ?>
            <?php sb_link('appointments.php', 'Appointments',  '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>'); ?>
            <?php sb_link('departments.php',  'Departments',   '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>'); ?>
            <?php sb_link('rooms.php',        'Rooms &amp; Beds','<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>'); ?>
            <?php sb_link('medicines.php',    'Medicines',     '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/>'); ?>
            <?php sb_link('staff.php',        'Staff',         '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'); ?>
        </ul>
    </div>

    <div class="sidebar-section">
        <h3>Clinical</h3>
        <ul class="sidebar-menu">
            <?php sb_link('encounters.php',           'Encounters',            '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78z"/>'); ?>
            <?php sb_link('prescriptions.php',        'Prescriptions',         '<path d="M14 2H6a2 2 0 0 0-2 2v16h14V8z"/><polyline points="14 2 14 8 20 8"/>'); ?>
            <?php sb_link('prescription_details.php', 'Rx Details &amp; Dispense','<path d="M14 2H6a2 2 0 0 0-2 2v16h14V8z"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'); ?>
            <?php sb_link('lab_orders.php',           'Lab Orders &amp; Results','<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'); ?>
            <?php sb_link('patient_history.php',      'Patient History',       '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>'); ?>
        </ul>
    </div>

    <div class="sidebar-section">
        <h3>Billing</h3>
        <ul class="sidebar-menu">
            <?php sb_link('invoices.php', 'Invoices', '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>'); ?>
            <?php sb_link('payments.php', 'Payments', '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'); ?>
        </ul>
    </div>

    <div class="sidebar-section">
        <h3>System</h3>
        <ul class="sidebar-menu">
            <?php sb_link('reports.php', 'Reports &amp; Analytics', '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'); ?>
            <?php if ($isAdmin): ?>
                <?php sb_link('audit_log.php', '🔒 Audit Log',  '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'); ?>
                <?php sb_link('setup.php',     '⚙️ DB Setup',   '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>'); ?>
            <?php endif; ?>
        </ul>
    </div>

</aside>

<style>
.sidebar-link-active {
    background: var(--primary-100) !important;
    color: var(--primary-600) !important;
    font-weight: 700 !important;
    border-left: 3px solid var(--primary-500);
    padding-left: calc(var(--space-md) - 3px) !important;
}
</style>
