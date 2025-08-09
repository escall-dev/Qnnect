<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Session expired or not logged in.']);
    exit();
}
$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

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
$is_super_admin = ($user['role'] === 'super_admin');

// Validate and sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input));
}

// Check for schedule conflicts for teacher schedules
function hasTeacherScheduleConflict($conn, $teacher_username, $school_id, $user_id, $day_of_week, $start_time, $end_time, $exclude_id = null) {
    $sql = "SELECT COUNT(*) as conflict_count FROM teacher_schedules 
            WHERE teacher_username = ? 
            AND school_id = ? 
            AND user_id = ?
            AND day_of_week = ? 
            AND status = 'active'
            AND (
                (start_time < ? AND end_time > ?) OR
                (start_time < ? AND end_time > ?) OR
                (start_time >= ? AND end_time <= ?)
            )";
    
    // Add exclusion for updates
    if ($exclude_id !== null) {
        $sql .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($exclude_id !== null) {
        // 11 parameters: teacher_username(s), school_id(i), user_id(i), day_of_week(s), 
        // end_time(s), start_time(s), end_time(s), start_time(s), start_time(s), end_time(s), exclude_id(i)
        $stmt->bind_param("siisssssssi", $teacher_username, $school_id, $user_id, $day_of_week, 
                         $end_time, $start_time, $end_time, $start_time, 
                         $start_time, $end_time, $exclude_id);
    } else {
        // 10 parameters: teacher_username(s), school_id(i), user_id(i), day_of_week(s), 
        // end_time(s), start_time(s), end_time(s), start_time(s), start_time(s), end_time(s)
        $stmt->bind_param("siisssssss", $teacher_username, $school_id, $user_id, $day_of_week, 
                         $end_time, $start_time, $end_time, $start_time, 
                         $start_time, $end_time);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['conflict_count'] > 0;
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log the POST data
    error_log("Teacher Schedule POST data received: " . print_r($_POST, true));
    
    // Debug: Check if teacher_schedules table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'teacher_schedules'");
    if ($check_table->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Database table teacher_schedules does not exist. Please run the setup script first.']);
        exit;
    }
    
    // Check if this is an add or update action
    $is_update = isset($_POST['schedule_id']) && !empty($_POST['schedule_id']);
    
    if ($is_update) {
        // Update existing schedule
        $schedule_id = sanitizeInput($_POST['schedule_id']);
        $subject = sanitizeInput($_POST['subject']);
        $section = sanitizeInput($_POST['course_section']);
        $day_of_week = sanitizeInput($_POST['day_of_week']);
        $start_time = sanitizeInput($_POST['start_time']);
        $end_time = sanitizeInput($_POST['end_time']);
        $room = sanitizeInput($_POST['room'] ?? '');
        
        // Validate inputs
        if (empty($subject) || empty($section) || empty($day_of_week) || 
            empty($start_time) || empty($end_time)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        // Validate time format and logic
        if (strtotime($start_time) >= strtotime($end_time)) {
            echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
            exit;
        }
        
        // Check for schedule conflicts, excluding current schedule
        if (hasTeacherScheduleConflict($conn, $teacher_username, $school_id, $user_id, $day_of_week, $start_time, $end_time, $schedule_id)) {
            echo json_encode(['success' => false, 'message' => 'Schedule conflicts with an existing schedule']);
            exit;
        }
        
        // Prepare and execute update
        $sql = "UPDATE teacher_schedules 
                SET subject = ?, section = ?, day_of_week = ?, 
                    start_time = ?, end_time = ?, room = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND teacher_username = ? AND school_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssisi", $subject, $section, $day_of_week, 
                          $start_time, $end_time, $room, $schedule_id, $teacher_username, $school_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes made or schedule not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating schedule: ' . $conn->error]);
        }
        exit;
        
    } else {
        // Add new schedule
        $subject = sanitizeInput($_POST['subject']);
        $section = sanitizeInput($_POST['course_section']);
        $day_of_week = sanitizeInput($_POST['day_of_week']);
        $start_time = sanitizeInput($_POST['start_time']);
        $end_time = sanitizeInput($_POST['end_time']);
        $room = sanitizeInput($_POST['room'] ?? '');
        
        // Validate inputs
        if (empty($subject) || empty($section) || empty($day_of_week) || 
            empty($start_time) || empty($end_time)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        // Validate time format and logic
        if (strtotime($start_time) >= strtotime($end_time)) {
            echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
            exit;
        }
        
        // Check for schedule conflicts
        if (hasTeacherScheduleConflict($conn, $teacher_username, $school_id, $user_id, $day_of_week, $start_time, $end_time)) {
            echo json_encode(['success' => false, 'message' => 'Schedule conflicts with an existing schedule']);
            exit;
        }
        
        // Prepare and execute insert
        $sql = "INSERT INTO teacher_schedules 
                (teacher_username, subject, section, day_of_week, start_time, end_time, room, school_id, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssii", $teacher_username, $subject, $section, $day_of_week, 
                          $start_time, $end_time, $room, $school_id, $user_id);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'Schedule added successfully',
                'schedule_id' => $new_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding schedule: ' . $conn->error]);
        }
        exit;
    }
}

// Handle GET requests for retrieving schedules
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, subject, section, day_of_week, start_time, end_time, room, status, created_at 
            FROM teacher_schedules 
            WHERE teacher_username = ? AND school_id = ? AND status = 'active'
            ORDER BY 
                FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                start_time";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $teacher_username, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = [
            'id' => $row['id'],
            'subject' => $row['subject'],
            'section' => $row['section'],
            'day_of_week' => $row['day_of_week'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'room' => $row['room'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode(['success' => true, 'schedules' => $schedules]);
    exit;
}

// Invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?> 