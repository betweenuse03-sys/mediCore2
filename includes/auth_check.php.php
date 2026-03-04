<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Optional: Check for specific role permissions
$allowed_roles = ['admin', 'doctor', 'staff']; // Default all roles

// You can customize per page by setting $required_role before including this file
if (isset($required_role)) {
    if ($_SESSION['role'] !== $required_role) {
        // User doesn't have required role
        header('Location: index.php');
        exit;
    }
}

// Set global user variables for easy access
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$current_role = $_SESSION['role'];
$current_full_name = $_SESSION['full_name'];
?>