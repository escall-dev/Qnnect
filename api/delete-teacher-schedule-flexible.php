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
    error_log("FLEXIBLE DELETE - ID: $schedule_id, Teacher: $teacher_username, School: $school_id, User ID: $user_id");
    
    // First check if the schedule exists and belongs to this user
    $check_sql = "SELECT id, teacher_username, school_id, user_id, status FROM teacher_schedules WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $schedule_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $existing_schedule = $check_result->fetch_assoc();
    
    if (!$existing_schedule) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found']);
        exit;
    }
    
    // Check if user has permission to delete this schedule
    $can_delete = false;
    $reason = "";
    
    // Method 1: Check by teacher_username and school_id
    if ($existing_schedule['teacher_username'] === $teacher_username && 
        $existing_schedule['school_id'] == $school_id) {
        $can_delete = true;
        $reason = "matched by teacher_username and school_id";
    }
    
    // Method 2: Check by user_id if it exists and matches
    if (!$can_delete && $user_id && $existing_schedule['user_id'] == $user_id) {
        $can_delete = true;
        $reason = "matched by user_id";
    }
    
    // Method 3: If user_id is NULL in schedule but teacher_username matches (legacy data)
    if (!$can_delete && !$existing_schedule['user_id'] && 
        $existing_schedule['teacher_username'] === $teacher_username) {
        $can_delete = true;
        $reason = "matched by teacher_username (legacy data with NULL user_id)";
    }
    
    if (!$can_delete) {
        error_log("Delete permission denied for schedule $schedule_id: " . json_encode($existing_schedule));
        echo json_encode(['success' => false, 'message' => 'Not authorized to delete this schedule']);
        exit;
    }
    
    error_log("Delete permission granted: $reason");
    
    // Perform the soft delete
    $sql = "UPDATE teacher_schedules 
            SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    
    if ($stmt->execute()) {
        error_log("Flexible delete query executed. Affected rows: " . $stmt->affected_rows);
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => "Schedule deleted successfully ($reason)"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Schedule not found or already deleted']);
        }
    } else {
        error_log("Flexible delete query failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Error deleting schedule: ' . $conn->error]);
    }
    exit;
}

// Invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>
