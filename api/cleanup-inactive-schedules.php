<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$conn = $conn_qr;

// Handle cleanup request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action !== 'cleanup_inactive') {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    // Log the cleanup attempt
    error_log("Cleanup inactive schedules requested by: " . $_SESSION['email']);
    
    // First count how many inactive records exist
    $count_sql = "SELECT COUNT(*) as inactive_count FROM teacher_schedules WHERE status = 'inactive'";
    $count_result = $conn->query($count_sql);
    $count_data = $count_result->fetch_assoc();
    $inactive_count = $count_data['inactive_count'];
    
    if ($inactive_count == 0) {
        echo json_encode(['success' => true, 'message' => 'No inactive records to delete']);
        exit;
    }
    
    // Delete all inactive records
    $delete_sql = "DELETE FROM teacher_schedules WHERE status = 'inactive'";
    
    if ($conn->query($delete_sql)) {
        $affected_rows = $conn->affected_rows;
        error_log("Cleanup completed. Deleted $affected_rows inactive schedule records");
        echo json_encode([
            'success' => true, 
            'message' => "Successfully deleted $affected_rows inactive schedule records"
        ]);
    } else {
        error_log("Cleanup failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Error during cleanup: ' . $conn->error]);
    }
    exit;
}

// Invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;
?>
