<?php
// Start session to access instructor data
session_start();

// Include database connection
include('../conn/db_connect.php');
include('../includes/activity_log_helper.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired or not logged in.'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

// Set headers for JSON response
header('Content-Type: application/json');

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get QR code from POST data (the scanned QR code contains the student information)
$qr_code = isset($_POST['qr_code']) ? $_POST['qr_code'] : '';

// Also get instructor_id from POST for attendance recording
$instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;

// Check if this is a manual entry
$is_manual_entry = isset($_POST['manual_entry']) && $_POST['manual_entry'] === 'true';

// For manual entry, get course and section
$course_code = isset($_POST['course_code']) ? $_POST['course_code'] : '';
$section = isset($_POST['section']) ? $_POST['section'] : '';

// Validate required data
if (!$is_manual_entry && empty($qr_code)) {
    echo json_encode([
        'success' => false,
        'message' => 'No QR code provided'
    ]);
    exit;
}

if ($is_manual_entry && (empty($course_code) || empty($section))) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required data for manual entry (course_code, section)'
    ]);
    exit;
}

try {
    // Debug logging
    error_log("=== QR CODE SCANNING DEBUG ===");
    error_log("POST data: " . json_encode($_POST));
    error_log("Session data: " . json_encode($_SESSION));
    error_log("User ID: $user_id, School ID: $school_id");
    error_log("Is manual entry: " . ($is_manual_entry ? 'true' : 'false'));
    if (!$is_manual_entry) {
        error_log("QR Code: $qr_code");
    } else {
        error_log("Course: $course_code, Section: $section");
    }

    // Look up student based on the method
    if ($is_manual_entry) {
        // For manual entry, find a student by course and section
        $query = "SELECT tbl_student_id, student_name, course_section, user_id, school_id 
                  FROM tbl_student 
                  WHERE course_section = ? AND user_id = ? AND school_id = ?
                  LIMIT 1";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("sii", $section, $user_id, $school_id);
    } else {
        // For QR code scanning, find student by QR code
        $query = "SELECT tbl_student_id, student_name, course_section, user_id, school_id 
                  FROM tbl_student 
                  WHERE generated_code = ?";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("s", $qr_code);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        if ($is_manual_entry) {
            echo json_encode([
                'success' => false,
                'message' => 'No student found for the specified course and section.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid QR code. Student not found.'
            ]);
        }
        exit;
    }
    
    // Get student data
    $student = $result->fetch_assoc();
    $student_id = $student['tbl_student_id'];
    $student_name = $student['student_name'];
    $student_user_id = $student['user_id'];
    $student_school_id = $student['school_id'];
    
    // Check if student belongs to current user
    if ($student_user_id != $user_id || $student_school_id != $school_id) {
        echo json_encode([
            'success' => false,
            'message' => 'This student does not belong to your account. Access denied.'
        ]);
        exit;
    }
    
    // Check if student has already checked in today for this instructor/subject
    $today = date('Y-m-d');
    $query = "SELECT tbl_attendance_id 
              FROM tbl_attendance 
              WHERE tbl_student_id = ? 
              AND DATE(time_in) = ? 
              AND instructor_id = ?
              AND user_id = ?
              AND school_id = ?";
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("isiii", $student_id, $today, $instructor_id, $user_id, $school_id);
    $stmt->execute();
    $attendanceResult = $stmt->get_result();
    
    if ($attendanceResult->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Student has already checked in today for this class.',
            'data' => [
                'student_id' => $student_id,
                'student_name' => $student_name,
                'duplicate' => true
            ]
        ]);
        exit;
    }
    
    // Get class start time from session
    $class_start_time = isset($_SESSION['class_start_time_formatted']) 
        ? $_SESSION['class_start_time_formatted'] 
        : (isset($_SESSION['class_start_time']) ? $_SESSION['class_start_time'] : '08:00:00');
    
    // Calculate attendance status
    $currentTime = date('Y-m-d H:i:s');
    
    // Ensure class_start_time has seconds component for proper comparison
    if (strlen($class_start_time) == 5) {
        $class_start_time .= ':00';
    }
    
    $class_start_datetime = new DateTime($today . ' ' . $class_start_time);
    $time_in_datetime = new DateTime($currentTime);
    
    // Print actual times and values for debugging
    error_log('----- ATTENDANCE API COMPARISON -----');
    error_log('Student: ' . $student_name);
    error_log('Current time: ' . $currentTime);
    error_log('Class start time: ' . $class_start_time);
    error_log('Class timestamp: ' . $class_start_datetime->getTimestamp());
    error_log('Check-in timestamp: ' . $time_in_datetime->getTimestamp());
    
    // Explicitly compare timestamps for clear comparison
    if ($time_in_datetime->getTimestamp() <= $class_start_datetime->getTimestamp()) {
        $status = 'On Time';
        error_log('DECISION: ON TIME - Arrived before class starts');
    } else {
        $status = 'Late';
        error_log('DECISION: LATE - Arrived after class starts');
    }
    
    // For debugging - log the final decision
    $debug_info = [
        'class_start_time' => $class_start_time,
        'class_start_datetime' => $class_start_datetime->format('Y-m-d H:i:s'),
        'time_in_datetime' => $time_in_datetime->format('Y-m-d H:i:s'),
        'status' => $status
    ];
    error_log('Attendance time debug info: ' . json_encode($debug_info));
    
    // Record attendance
    $query = "INSERT INTO tbl_attendance 
             (tbl_student_id, time_in, status, instructor_id, subject_id, user_id, school_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn_qr->prepare($query);
    
    // Get subject_id from session or POST data
    $subject_id = isset($_SESSION['current_subject_id']) ? $_SESSION['current_subject_id'] : 0;
    
    // Get current user's user_id and school_id from session
    $current_user_id = $_SESSION['user_id'] ?? 1;
    $current_school_id = $_SESSION['school_id'] ?? 1;
    
    // Debug logging
    error_log("Attendance recording - User ID: $current_user_id, School ID: $current_school_id");
    error_log("Session variables: " . json_encode($_SESSION));
    error_log("Student ID: $student_id, Time: $currentTime, Status: $status");
    
    $stmt->bind_param("issiiii", $student_id, $currentTime, $status, $instructor_id, $subject_id, $current_user_id, $current_school_id);
    $result = $stmt->execute();
    
    error_log("INSERT result: " . ($result ? 'SUCCESS' : 'FAILED'));
    if (!$result) {
        error_log("INSERT error: " . $conn_qr->error);
    }
    
    if ($result) {
        // Log the successful attendance
        if (function_exists('logActivity')) {
            logActivity(
                'attendance_scan',
                "Recorded attendance for student $student_name",
                'tbl_attendance',
                $conn_qr->insert_id,
                [
                    'student_id' => $student_id,
                    'instructor_id' => $instructor_id,
                    'subject_id' => $subject_id,
                    'status' => $status
                ]
            );
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'data' => [
                'student_id' => $student_id,
                'student_name' => $student_name,
                'time_in' => $currentTime,
                'status' => $status,
                'attendance_id' => $conn_qr->insert_id
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to record attendance: ' . $conn_qr->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
} 