<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Use database connections
$conn = $conn_qr;
$conn_users = $conn_login;

// Get user's school_id, username, and user_id
$email = $_SESSION['email'];
$user_query = "SELECT school_id, role, username, id FROM users WHERE email = ?";
$stmt = $conn_users->prepare($user_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$school_id = $user['school_id'] ?? 1;
$teacher_username = $user['username'] ?? $_SESSION['email'];
$user_id = $user['id'] ?? null;

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = null;
    
    // Check if data is sent as JSON
    $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $schedule_id = isset($input['id']) ? intval($input['id']) : null;
    } else {
        // Check for form data
        $schedule_id = isset($_POST['id']) ? intval($_POST['id']) : null;
    }
    
    if ($schedule_id === null) {
        echo json_encode(['success' => false, 'message' => 'Schedule ID is required']);
        exit;
    }
    
    // Debug logging
    error_log("SIMPLE DELETE - ID: $schedule_id, Teacher: $teacher_username, School: $school_id, User ID: $user_id");
    
    // Simple delete using just the ID and basic validation
    $sql = "UPDATE teacher_schedules 
            SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND status = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    
    if ($stmt->execute()) {
        error_log("Simple delete query executed. Affected rows: " . $stmt->affected_rows);
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully (simple method)']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Schedule not found or already deleted']);
        }
    } else {
        error_log("Simple delete query failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Error deleting schedule: ' . $conn->error]);
    }
    exit;
}

// Invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>
