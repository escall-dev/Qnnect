<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = $_POST['schedule_id'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $section = $_POST['section'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    
    // Store schedule session data
    $_SESSION['current_schedule_id'] = $schedule_id;
    $_SESSION['current_subject_name'] = $subject;
    $_SESSION['current_section_name'] = $section;
    $_SESSION['schedule_start_time'] = $start_time;
    $_SESSION['attendance_mode'] = 'room_subject';
    
    echo json_encode([
        'success' => true,
        'message' => 'Schedule session data stored successfully.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
