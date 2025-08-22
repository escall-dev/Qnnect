<?php
// Session Configuration for Role-Based System

// Check if we're in a logout process - if so, don't create new sessions
if (session_status() === PHP_SESSION_ACTIVE) {
    if (isset($_SESSION['logging_out']) && $_SESSION['logging_out'] === true) {
        // Don't recreate session if we're logging out
        return;
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings for security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    
    // Set session name
    session_name('QR_ATTENDANCE_SESSION');
    
    // Set session cookie path to root of project - use simpler approach
    // Use root path to ensure sessions work across all subdirectories
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start the session
    session_start();
    
    // Debug: Log session start
    error_log('Session started. Session ID: ' . session_id());
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// TEMPORARILY DISABLE SESSION TIMEOUT FOR DEBUGGING
// Comment out the timeout check for now
/*
$timeout_duration = 1800;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Session has expired
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['session_expired'] = true;
}
*/

// Only update activity if we're not logging out
if (!isset($_SESSION['logging_out'])) {
    $_SESSION['last_activity'] = time();
    
    // Debug: Log session contents
    error_log('Session config loaded. Session contents: ' . print_r($_SESSION, true));
}

