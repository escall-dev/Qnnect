<?php
/**
 * Simple session test script
 */

echo "<h2>Session Test</h2>";

// Test different session configurations
$session_configs = [
    ['name' => 'QR_ATTENDANCE_SESSION', 'path' => '/'],
    ['name' => 'PHPSESSID', 'path' => '/'],
    ['name' => 'QR_ATTENDANCE_SESSION', 'path' => '/Qnnect/'],
];

foreach ($session_configs as $config) {
    echo "<h3>Testing: " . $config['name'] . " with path: " . $config['path'] . "</h3>";
    
    // Close any existing session
    if (session_status() !== PHP_SESSION_NONE) {
        session_write_close();
    }
    
    // Set session parameters
    session_name($config['name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $config['path'],
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start session
    session_start();
    
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Session Name: " . session_name() . "</p>";
    echo "<p>Session Variables: " . count($_SESSION) . "</p>";
    
    if (!empty($_SESSION)) {
        echo "<p style='color: green;'>✓ Session has data!</p>";
        echo "<ul>";
        foreach ($_SESSION as $key => $value) {
            echo "<li><strong>$key:</strong> " . htmlspecialchars(print_r($value, true)) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ No session data found</p>";
    }
    
    session_write_close();
    echo "<hr>";
}

echo "<h3>Current Session Status:</h3>";
echo "<p><a href='admin/login.php'>Login Page</a></p>";
echo "<p><a href='index.php'>Dashboard</a></p>";
echo "<p><a href='debug_session.php'>Debug Session</a></p>";
?> 