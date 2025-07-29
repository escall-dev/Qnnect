<?php
// Simple test endpoint for academic settings
session_start();
header('Content-Type: application/json');

// Log the request
error_log("Test endpoint called - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("Session email: " . ($_SESSION['email'] ?? 'not set'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_year = $_POST['school_year'] ?? '';
    $semester = $_POST['semester'] ?? '';
    
    echo json_encode([
        'success' => true,
        'message' => 'Test endpoint working',
        'received_data' => [
            'school_year' => $school_year,
            'semester' => $semester,
            'email' => $_SESSION['email'] ?? 'not set'
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests allowed'
    ]);
}
?> 