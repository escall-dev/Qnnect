<?php
// Start session to access instructor data
session_start();

// Include database connection
include('../conn/db_connect.php');
include('../includes/activity_log_helper.php');

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

// Get data from POST request
$course_code = isset($_POST['course_code']) ? $_POST['course_code'] : '';
$section = isset($_POST['section']) ? $_POST['section'] : '';
$instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : 0;

// Get QR code from session (assuming it was stored there by a previous scan)
$qr_code = isset($_SESSION['last_scanned_qr']) ? $_SESSION['last_scanned_qr'] : '';

// Validate required data
if (empty($qr_code)) {
    echo json_encode([
        'success' => false,
        'message' => 'No QR code scanned'
    ]);
    exit;
}

try {
    // Look up student by QR code
    $query = "SELECT tbl_student_id, student_name, course_section 
              FROM tbl_student 
              WHERE generated_code = ?";
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("s", $qr_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid QR code. Student not found.'
        ]);
        exit;
    }
    
    // Get student data
    $student = $result->fetch_assoc();
    $student_id = $student['tbl_student_id'];
    $student_name = $student['student_name'];
    
    // Check if student has already checked in today for this instructor/subject
    $today = date('Y-m-d');
    $query = "SELECT tbl_attendance_id 
              FROM tbl_attendance 
              WHERE tbl_student_id = ? 
              AND DATE(time_in) = ? 
              AND instructor_id = ?";
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("isi", $student_id, $today, $instructor_id);
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
             (tbl_student_id, time_in, status, instructor_id, subject_id) 
             VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn_qr->prepare($query);
    
    // Get subject_id from session or POST data
    $subject_id = isset($_SESSION['current_subject_id']) ? $_SESSION['current_subject_id'] : 0;
    
    $stmt->bind_param("issii", $student_id, $currentTime, $status, $instructor_id, $subject_id);
    $result = $stmt->execute();
    
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