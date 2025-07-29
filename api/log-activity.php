<?php
// Start the session
session_start();

// Include database connection
include('../conn/db_connect.php');
// Include activity logging helper
include('../includes/activity_log_helper.php');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Set headers for JSON response
header('Content-Type: application/json');

// Check if we have the required parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get parameters
    $action_type = $_POST['action_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $table = $_POST['table'] ?? null;
    $id = $_POST['id'] ?? null;
    $additional_data = $_POST['additional_data'] ?? null;
    
    // Decode JSON additional data if provided as string
    if ($additional_data && is_string($additional_data)) {
        $additional_data = json_decode($additional_data, true);
    }
    
    // Validate required fields
    if (empty($action_type) || empty($description)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Missing required parameters: action_type or description']);
        exit;
    }
    
    // Log the activity
    $result = logActivity($action_type, $description, $table, $id, $additional_data);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Activity logged successfully']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Failed to log activity']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
}
?> 