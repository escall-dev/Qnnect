<?php
require_once '../includes/session_config.php';

// Check session validity
$response = ['valid' => false];

if (isset($_SESSION['user_id']) && isset($_SESSION['school_id']) && isset($_SESSION['email'])) {
    // Additional checks can be added here
    $current_time = time();
    $last_activity = $_SESSION['last_activity'] ?? $current_time;
    
    // Update last activity
    $_SESSION['last_activity'] = $current_time;
    
    // Session is valid if all required session variables exist
    $response['valid'] = true;
    $response['user_id'] = $_SESSION['user_id'];
    $response['school_id'] = $_SESSION['school_id'];
    $response['session_time'] = $current_time - $last_activity;
} else {
    $response['message'] = 'Session expired or invalid.';
}

header('Content-Type: application/json');
echo json_encode($response);
?>
