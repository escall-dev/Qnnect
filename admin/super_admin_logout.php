<?php
// Dedicated logout for super admin session to ensure isolation from regular admin sessions.
require_once '../includes/session_config_superadmin.php';

// Mark logout in progress to avoid session recreation
$_SESSION['logging_out'] = true;

// Capture role (should be super_admin) for redirect decision
$role_before_logout = $_SESSION['role'] ?? null;

// Clear sensitive super admin markers explicitly before wiping
unset($_SESSION['superadmin_pin_verified']);
unset($_SESSION['superadmin_pin_verified_at']);
unset($_SESSION['super_admin_logged_in_at']);
unset($_SESSION['super_admin_last_activity']);
unset($_SESSION['super_admin_session_version']);

// Wipe all session data
$_SESSION = [];

// Delete super admin specific session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    // Explicitly clear both possible cookie names
    setcookie('QR_ATTENDANCE_SA_SESSION', '', time() - 42000, '/', '', false, true);
}

// Destroy the session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Extra defensive clear of generic PHP session cookie
setcookie('PHPSESSID', '', time() - 42000, '/');

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Always redirect to super admin PIN gate to enforce re-verification
header('Location: super_admin_pin.php?logout=1&t=' . time());
exit();
