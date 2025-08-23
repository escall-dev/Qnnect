<?php
// Session Configuration dedicated to Super Admin portal

// Avoid starting a new session during logout
if (session_status() === PHP_SESSION_ACTIVE) {
    if (isset($_SESSION['logging_out']) && $_SESSION['logging_out'] === true) {
        return;
    }
}

// Start Super Admin session with its own cookie name and scope
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 when using HTTPS

    // Use a distinct session name and narrower path to isolate from regular session
    session_name('QR_ATTENDANCE_SA_SESSION');
    // Use root path so it works under /Qnnect/admin on localhost
    // Isolation is provided by the distinct session name
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();

    // Regenerate periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Activity timestamp (skip if logging out)
if (!isset($_SESSION['logging_out'])) {
    $_SESSION['last_activity'] = time();
}

?>


