<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    error_log("Access denied - Admin ID: " . ($_SESSION['admin_id'] ?? 'not set') . ", Role: " . ($_SESSION['admin_role'] ?? 'not set'));
    header('Location: login.php');
    exit;
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}
?> 