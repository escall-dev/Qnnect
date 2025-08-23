<?php
// Dedicated entry point for Super Admin portal; reuses admin_panel with role checks
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';

requireLogin();
requireSuperAdmin();

// Redirect to the unified admin panel which already renders super admin features
header('Location: admin_panel.php');
exit();
?>
