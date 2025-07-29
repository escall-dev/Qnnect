<?php
session_start();
include('conn/db_connect.php');
require_once('includes/ActivityLogger.php');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

// Get user ID from email in the login_register database
$conn_login = mysqli_connect("localhost", "root", "", "login_register");
if (!$conn_login) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_query = "SELECT id, username, email, full_name FROM users WHERE email = ?";
$user_stmt = $conn_login->prepare($user_query);
$user_stmt->bind_param("s", $_SESSION['email']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$current_user_id = $user_data['id'] ?? null;
$username = $user_data['username'] ?: $user_data['full_name'] ?: $_SESSION['email']; // Use username, then full_name, then email as fallback

$activity_logger = new ActivityLogger($conn_qr, $current_user_id);

// Get export parameters - accept both GET and POST methods
$format = $_POST['format'] ?? $_GET['format'] ?? 'csv';
$start_date = $_POST['start_date'] ?? $_GET['start_date'] ?? null;
$end_date = $_POST['end_date'] ?? $_GET['end_date'] ?? null;
$action_type = $_POST['action_type'] ?? $_GET['action_type'] ?? null;
$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;

// Parse date range if provided
if (isset($_GET['date_range'])) {
    $date_range = explode(' - ', $_GET['date_range']);
    if (count($date_range) == 2) {
        $start_date = trim($date_range[0]);
        $end_date = trim($date_range[1]);
    }
}

try {
    // Log the export action
    $activity_logger->log(
        'data_export',
        "Exported activity logs in $format format",
        'activity_logs',
        null,
        [
            'format' => $format,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'action_type' => $action_type,
            'user_id' => $user_id,
            'username' => $username
        ]
    );
    
    // Export the logs
    $export_data = $activity_logger->exportActivityLogs($format, $start_date, $end_date, $action_type, $user_id);
    
    // Set headers for file download
    header('Content-Type: ' . $export_data['type']);
    header('Content-Disposition: attachment; filename="' . $export_data['filename'] . '"');
    header('Content-Length: ' . strlen($export_data['content']));
    echo $export_data['content'];
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo "Error exporting logs: " . $e->getMessage();
    exit();
}
?>
