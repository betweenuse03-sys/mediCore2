<?php
/**
 * auth_check.php
 * Include this at the top of every protected page.
 * Redirects to login if no valid session exists.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

// Helper: check if current user is admin
function is_admin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

// Helper: require admin or redirect with message
function require_admin(): void {
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Access denied. Admin privileges required.';
        header('Location: index.php');
        exit;
    }
}
