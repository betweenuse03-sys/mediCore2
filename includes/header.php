<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<header class="header">
    <div class="header-content">
        <div class="logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
            </svg>
            <span>MediCore</span>
        </div>
        <nav class="nav">
            <?php
            $pages = [
                'index.php'       => 'Dashboard',
                'patients.php'    => 'Patients',
                'doctors.php'     => 'Doctors',
                'appointments.php'=> 'Appointments',
                'encounters.php'  => 'Encounters',
                'lab_orders.php'  => 'Lab',
                'invoices.php'    => 'Billing',
                'prescriptions.php'=> 'Prescriptions',
                'reports.php'     => 'Reports',
            ];
            $cur = basename($_SERVER['PHP_SELF']);
            foreach ($pages as $file => $label):
            ?>
                <a href="<?= $file ?>" class="nav-link <?= $cur === $file ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-actions">
            <?php
            $role     = $_SESSION['role']      ?? 'user';
            $fullname = $_SESSION['full_name']  ?? ucfirst($_SESSION['username'] ?? 'User');
            $isAdmin  = $role === 'admin';
            ?>
            <div class="user-info-header">
                <div class="role-badge <?= $isAdmin ? 'role-admin' : 'role-user' ?>">
                    <?= $isAdmin ? '🔐 Admin' : '👤 User' ?>
                </div>
                <div class="user-name-header"><?= htmlspecialchars($fullname) ?></div>
            </div>
            <a href="logout.php" class="btn-logout" title="Logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
            </a>
        </div>
    </div>
</header>

<style>
.user-info-header { display:flex; flex-direction:column; align-items:flex-end; gap:2px; margin-right:4px; }
.role-badge { font-size:.68rem; font-weight:700; padding:2px 10px; border-radius:20px; letter-spacing:.04em; text-transform:uppercase; }
.role-admin { background:linear-gradient(135deg,#1565c0,#0d2137); color:white; box-shadow:0 2px 8px rgba(21,101,192,.35); }
.role-user  { background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; }
.user-name-header { font-size:.78rem; font-weight:600; color:var(--gray-700); }
.btn-logout { display:flex; align-items:center; gap:6px; padding:7px 14px; background:#ffebee; color:#c62828; border:1px solid #ef9a9a; border-radius:8px; font-size:.82rem; font-weight:600; text-decoration:none; transition:all .2s; }
.btn-logout:hover { background:#c62828; color:white; border-color:#c62828; }
</style>
