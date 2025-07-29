<?php
// Start the session to store the time setting
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get the class start time from POST data
$classStartTime = isset($_POST['classStartTime']) ? $_POST['classStartTime'] : null;

if (empty($classStartTime)) {
    echo json_encode([
        'success' => false,
        'message' => 'No start time provided'
    ]);
    exit;
}

// Validate time format (HH:MM)
if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $classStartTime)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid time format. Please use HH:MM format.'
    ]);
    exit;
}

// Store the class time in session
$_SESSION['class_start_time'] = $classStartTime;

// Ensure the time is properly formatted for comparisons
if (strlen($classStartTime) == 5) {
    // If time is in HH:MM format, add seconds for consistent comparisons
    $_SESSION['class_start_time_formatted'] = $classStartTime . ':00';
} else {
    $_SESSION['class_start_time_formatted'] = $classStartTime;
}

// Force immediate session write
session_write_close();
session_start();

error_log('Class time set to: ' . $_SESSION['class_start_time'] . 
          ' - Formatted as: ' . $_SESSION['class_start_time_formatted']);

// Log this action if the activity logging function exists
if (function_exists('logActivity')) {
    include_once('../includes/activity_log_helper.php');
    
    logActivity(
        'settings_change',
        "Updated class start time to $classStartTime",
        'user_settings',
        null,
        ['class_start_time' => $classStartTime]
    );
}

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Class start time set successfully',
    'data' => [
        'class_start_time' => $classStartTime
    ]
]);
?> 