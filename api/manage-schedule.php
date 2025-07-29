<?php
// api/manage-schedule.php
// Teacher Schedule Management API
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

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        addSchedule($pdo, $teacher_username, $user_school_id);
        break;
    case 'update':
        updateSchedule($pdo, $teacher_username, $user_school_id);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function addSchedule($pdo, $teacher_username, $school_id) {
    $subject = trim($_POST['subject'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $room = trim($_POST['room'] ?? '');
    
    // Validation
    if (empty($subject) || empty($section) || empty($day_of_week) || empty($start_time) || empty($end_time)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        return;
    }
    
    // Convert time format to 12-hour format for storage
    $start_time_12h = date('g:i A', strtotime($start_time));
    $end_time_12h = date('g:i A', strtotime($end_time));
    
    // Check for time conflicts (same day)
    $conflicts = checkTimeConflicts($pdo, $teacher_username, $school_id, $day_of_week, $start_time_12h, $end_time_12h);
    if (!empty($conflicts)) {
        echo json_encode(['success' => false, 'message' => 'Time conflict detected: ' . implode(', ', $conflicts)]);
        return;
    }
    
    try {
        $sql = "INSERT INTO master_schedule (subject, section, instructor, day_of_week, start_time, end_time, room, week_number, school_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$subject, $section, $teacher_username, $day_of_week, $start_time_12h, $end_time_12h, $room, $school_id]);
        
        // Log activity
        logActivity($pdo, 'SCHEDULE_CREATED', "Subject: $subject, Section: $section, Day: $day_of_week");
        
        echo json_encode(['success' => true, 'message' => 'Schedule added successfully']);
    } catch (Exception $e) {
        error_log("Error adding schedule: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function updateSchedule($pdo, $teacher_username, $school_id) {
    $schedule_id = $_POST['schedule_id'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $room = trim($_POST['room'] ?? '');
    
    // Validation
    if (empty($schedule_id) || empty($subject) || empty($section) || empty($day_of_week) || empty($start_time) || empty($end_time)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        return;
    }
    
    // Check if schedule belongs to this teacher
    $stmt = $pdo->prepare("SELECT id FROM master_schedule WHERE id = ? AND instructor = ? AND school_id = ?");
    $stmt->execute([$schedule_id, $teacher_username, $school_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found or access denied']);
        return;
    }
    
    // Convert time format to 12-hour format for storage
    $start_time_12h = date('g:i A', strtotime($start_time));
    $end_time_12h = date('g:i A', strtotime($end_time));
    
    // Check for time conflicts (excluding current schedule, same day)
    $conflicts = checkTimeConflicts($pdo, $teacher_username, $school_id, $day_of_week, $start_time_12h, $end_time_12h, $schedule_id);
    if (!empty($conflicts)) {
        echo json_encode(['success' => false, 'message' => 'Time conflict detected: ' . implode(', ', $conflicts)]);
        return;
    }
    
    try {
        $sql = "UPDATE master_schedule SET subject = ?, section = ?, day_of_week = ?, start_time = ?, end_time = ?, room = ?, updated_at = NOW() 
                WHERE id = ? AND instructor = ? AND school_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$subject, $section, $day_of_week, $start_time_12h, $end_time_12h, $room, $schedule_id, $teacher_username, $school_id]);
        
        // Log activity
        logActivity($pdo, 'SCHEDULE_UPDATED', "Schedule ID: $schedule_id, Subject: $subject");
        
        echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
    } catch (Exception $e) {
        error_log("Error updating schedule: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

function checkTimeConflicts($pdo, $teacher_username, $school_id, $day_of_week, $start_time, $end_time, $exclude_id = null) {
    $conflicts = [];
    
    $sql = "SELECT subject, section, start_time, end_time FROM master_schedule 
            WHERE instructor = ? AND school_id = ? AND day_of_week = ?";
    $params = [$teacher_username, $school_id, $day_of_week];
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $existing_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($existing_schedules as $schedule) {
        $new_start = strtotime($start_time);
        $new_end = strtotime($end_time);
        $existing_start = strtotime($schedule['start_time']);
        $existing_end = strtotime($schedule['end_time']);
        
        if (($new_start >= $existing_start && $new_start < $existing_end) ||
            ($new_end > $existing_start && $new_end <= $existing_end) ||
            ($new_start <= $existing_start && $new_end >= $existing_end)) {
            $conflicts[] = $schedule['subject'] . ' (' . $schedule['section'] . ') - ' . 
                          $schedule['start_time'] . ' to ' . $schedule['end_time'];
        }
    }
    
    return $conflicts;
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