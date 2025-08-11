<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';
require_once '../includes/schedule_helper.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$conn = $conn_qr;
$conn_users = $conn_login;

// Get user's information
$email = $_SESSION['email'];
$user_query = "SELECT school_id, role, username, id FROM users WHERE email = ?";
$stmt = $conn_users->prepare($user_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$school_id = $user['school_id'] ?? 1;
$teacher_username = $user['username'] ?? $_SESSION['email'];
$user_id = $user['id'] ?? null;

// Get request parameters
$instructor = $_GET['instructor'] ?? null;
$action = $_GET['action'] ?? 'get_dropdown_data';

if ($action === 'get_dropdown_data') {
    // Get user-scoped dropdown data
    $scheduleData = getScheduleDropdownData($school_id, $teacher_username, (int)$user_id);
    echo json_encode(['success' => true, 'data' => $scheduleData]);
    
} elseif ($action === 'get_teacher_subjects') {
    // Get subjects for specific teacher
    // Always scope by current user's user_id to avoid cross-user leakage
    $target_teacher = $instructor ?: $teacher_username;
    $subjects = getTeacherSubjects($target_teacher, $school_id, $user_id);
    
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);
    
} elseif ($action === 'get_all_subjects') {
    // Get all subjects for the current user only
    $subjects = getAllSubjectsForUser($school_id, $teacher_username, (int)$user_id);
    echo json_encode(['success' => true, 'subjects' => $subjects]);
    
} elseif ($action === 'get_filtered_schedules') {
    // Get filtered schedules based on parameters
    $instructor = $_GET['instructor'] ?? null;
    $section = $_GET['section'] ?? null;
    $subject = $_GET['subject'] ?? null;
    
    $schedules = getFilteredSchedules($school_id, $instructor, $section, $subject);
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
