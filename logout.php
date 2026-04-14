<?php
/**
 * FitSense — Logout
 * Destroys the session and redirects to the appropriate login page.
 */
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

$role = $_SESSION['role'] ?? 'member';

$auth = new Auth();
$auth->logout();

// Redirect staff to staff-login, members to login
if (in_array($role, ['trainer', 'admin'], true)) {
    header('Location: staff-login.php');
} else {
    header('Location: login.php');
}
exit;
