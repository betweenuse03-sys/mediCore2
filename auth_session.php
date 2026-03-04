<?php
/**
 * auth_session.php
 * Receives role/username/full_name from JS login form
 * and creates a PHP session so all pages can use session guards.
 */
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role      = trim($_POST['role']      ?? '');
    $username  = trim($_POST['username']  ?? '');
    $full_name = trim($_POST['full_name'] ?? '');

    if ($role && $username) {
        // Set a deterministic user_id from username hash (good enough for session)
        $_SESSION['user_id']   = crc32($username);
        $_SESSION['username']  = htmlspecialchars($username, ENT_QUOTES);
        $_SESSION['role']      = in_array($role, ['admin','user']) ? $role : 'user';
        $_SESSION['full_name'] = htmlspecialchars($full_name, ENT_QUOTES);
        $_SESSION['logged_in_at'] = date('Y-m-d H:i:s');

        header('Location: index.php');
        exit;
    }
}

// Fallback
header('Location: login.php');
exit;
