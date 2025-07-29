<?php
session_start();
require_once('../conn/db_connect.php');
require_once('../includes/activity_log_helper.php');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Check if this is a POST request with search data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_query']) && isset($_POST['search_type'])) {
    $search_query = trim($_POST['search_query']);
    $search_type = $_POST['search_type'];
    
    // Validate search query
    if (empty($search_query) || strlen($search_query) < 2) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid search query']);
        exit;
    }
    
    // Get user ID from session
    $user_email = $_SESSION['email'];
    $user_id = null;
    
    // Connect to login database to get user ID
    $conn_login = mysqli_connect("localhost", "root", "", "login_register");
    if ($conn_login) {
        $stmt = $conn_login->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_id = $row['id'];
        }
        $stmt->close();
        // Safely close the connection
        if (isset($conn_login) && $conn_login instanceof mysqli) {
            try {
                if ($conn_login->ping()) {
                    $conn_login->close();
                }
            } catch (Throwable $e) {
                // Connection is already closed or invalid, do nothing
            }
        }
    }
    
    // Log the search activity - always use settings_search as the action_type
    if ($search_type === 'settings_search') {
        // Use a consistent action_type for all settings searches
        $action_type = 'settings_search';
        
        // Use the activity log helper function
        logActivity(
            $action_type,                      // Always use 'settings_search'
            "Searched for: " . $search_query,  // Full search query in the description
            'settings',                        // Module
            null,                              // No affected_id for searches
            [                                  // Additional data
                'search_query' => $search_query,
                'search_time' => date('Y-m-d H:i:s'),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ],
            $user_id                           // User ID
        );
        
        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Search logged successfully']);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid search type']);
        exit;
    }
} else {
    // Return error for invalid request
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
?> 