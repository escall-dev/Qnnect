<?php
// api/get-schedule.php
// Get Schedule Details API
require_once '../includes/session_config.php';
require_once '../conn/db_connect_pdo.php';

// Turn off output buffering and error display for clean JSON
if (function_exists('ob_clean')) ob_clean();
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Use login_register database for user lookup, qr_attendance_db for schedules
$login_pdo = $conn_login_pdo; // For login_register table
$pdo = $conn_qr_pdo; // For master_schedule table

$user_email = $_SESSION['email'];
$user_school_id = $_SESSION['school_id'] ?? 1;

// Get teacher username from users table
$stmt = $login_pdo->prepare("SELECT username FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$teacher_username = $stmt->fetchColumn();

if (!$teacher_username) {
    echo json_encode(['success' => false, 'message' => 'Teacher not found']);
    exit;
}

$schedule_id = $_GET['id'] ?? '';

if (empty($schedule_id)) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required']);
    exit;
}

try {
    // Get schedule details (only if it belongs to this teacher)
    $sql = "SELECT * FROM master_schedule WHERE id = ? AND instructor = ? AND school_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$schedule_id, $teacher_username, $user_school_id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found or access denied']);
        exit;
    }
    
    echo json_encode(['success' => true, 'schedule' => $schedule]);
} catch (Exception $e) {
    error_log("Error getting schedule: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 