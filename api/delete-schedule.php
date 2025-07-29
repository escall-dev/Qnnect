<?php
// api/delete-schedule.php
// Delete Schedule API
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$schedule_id = $input['id'] ?? '';

if (empty($schedule_id)) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required']);
    exit;
}

try {
    // Check if schedule belongs to this teacher
    $stmt = $pdo->prepare("SELECT subject, section FROM master_schedule WHERE id = ? AND instructor = ? AND school_id = ?");
    $stmt->execute([$schedule_id, $teacher_username, $user_school_id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found or access denied']);
        exit;
    }
    
    // Delete the schedule
    $sql = "DELETE FROM master_schedule WHERE id = ? AND instructor = ? AND school_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$schedule_id, $teacher_username, $user_school_id]);
    
    // Log activity
    logActivity($pdo, 'SCHEDULE_DELETED', "Subject: {$schedule['subject']}, Section: {$schedule['section']}");
    
    echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
} catch (Exception $e) {
    error_log("Error deleting schedule: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

function logActivity($pdo, $action, $details) {
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $sql = "INSERT INTO activity_logs (user_id, action_type, action_description, created_at) 
                VALUES (?, 'settings_change', ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, "Schedule Management: $action - $details"]);
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}
?> 