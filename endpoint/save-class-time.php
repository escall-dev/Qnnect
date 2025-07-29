<?php
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get class start time
    $class_start_time = isset($_POST['class_start_time']) ? $_POST['class_start_time'] : null;
    
    // Validate input
    if (empty($class_start_time)) {
        echo json_encode([
            'success' => false,
            'message' => 'Class start time is required'
        ]);
        exit;
    }
    
    // Store class time in session
    $_SESSION['class_start_time'] = $class_start_time;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Class time saved successfully',
        'data' => [
            'class_start_time' => $class_start_time
        ]
    ]);
} else {
    // Return error for non-POST requests
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 