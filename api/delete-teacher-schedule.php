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

// Get user's school_id and username
$email = $_SESSION['email'];
$user_query = "SELECT school_id, role, username FROM users WHERE email = ?";
$stmt = $conn_users->prepare($user_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$school_id = $user['school_id'] ?? 1;
$teacher_username = $user['username'] ?? $_SESSION['email'];

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $schedule_id = intval($_POST['id']);
    
    // Soft delete by setting status to inactive
    $sql = "UPDATE teacher_schedules 
            SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND teacher_username = ? AND school_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $schedule_id, $teacher_username, $school_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Schedule not found or already deleted']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting schedule: ' . $conn->error]);
    }
    exit;
}

// Invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?> 