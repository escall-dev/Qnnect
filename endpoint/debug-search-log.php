<?php
session_start();
require_once('../conn/db_connect.php');
require_once('../includes/activity_log_helper.php');

// This is a debug script to directly insert a test search log
// Make sure you're logged in before running this script

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    die("You must be logged in to run this script.");
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

// Test search query
$search_query = "test search query";
$action_type = "search: " . $search_query;

// Check the action_type column definition
$checkColumnQuery = "SHOW COLUMNS FROM activity_logs WHERE Field = 'action_type'";
$columnResult = $conn_qr->query($checkColumnQuery);
$columnInfo = $columnResult->fetch_assoc();
echo "<p>action_type column definition: " . htmlspecialchars(print_r($columnInfo, true)) . "</p>";

// Direct database insert to avoid any potential issues with the helper function
$stmt = $conn_qr->prepare("INSERT INTO activity_logs 
    (user_id, action_type, action_description, ip_address, user_agent, created_at, additional_data)
    VALUES (?, ?, ?, ?, ?, NOW(), ?)");

$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$description = "Searched for: " . $search_query;
$additional_data = json_encode([
    'search_query' => $search_query,
    'search_time' => date('Y-m-d H:i:s'),
    'debug' => true
]);

$stmt->bind_param(
    "isssss",
    $user_id,
    $action_type,
    $description,
    $ip_address,
    $user_agent,
    $additional_data
);

$result = $stmt->execute();

if ($result) {
    echo "<p>Successfully inserted debug search log!</p>";
    
    // Get the inserted record
    $id = $conn_qr->insert_id;
    $query = "SELECT * FROM activity_logs WHERE id = $id";
    $check = $conn_qr->query($query);
    $row = $check->fetch_assoc();
    
    echo "<h3>Inserted Record:</h3>";
    echo "<pre>" . htmlspecialchars(print_r($row, true)) . "</pre>";
} else {
    echo "<p>Error inserting debug search log: " . htmlspecialchars($stmt->error) . "</p>";
}

// Also test using the helper function
echo "<h3>Testing with logActivity helper function:</h3>";
try {
    $result = logActivity(
        $action_type,             
        $description,
        'settings',
        1,
        [
            'search_query' => $search_query,
            'search_time' => date('Y-m-d H:i:s'),
            'helper_test' => true
        ],
        $user_id
    );
    
    if ($result) {
        echo "<p>Successfully logged activity using helper function!</p>";
    } else {
        echo "<p>Failed to log activity using helper function.</p>";
    }
} catch (Exception $e) {
    echo "<p>Exception in helper function: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 