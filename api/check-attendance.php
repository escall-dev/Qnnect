<?php
// Use unified session configuration
require_once('../includes/session_config.php');

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

// Use the current user_id as instructor_id (overrides any POST data for instructor_id)
// This ensures the instructor_id is always a valid ID from the users table
$instructor_id = $user_id;

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
    // Check database connection first
    if (!$conn_qr || $conn_qr->connect_error) {
        error_log("Database connection error in check-attendance.php: " . ($conn_qr ? $conn_qr->connect_error : "Connection is null"));
        echo json_encode([
            'success' => false,
            'message' => 'Database connection error',
            'debug' => $conn_qr ? $conn_qr->connect_error : "Connection failed"
        ]);
        exit;
    }
    
    // Enhanced debug logging
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
        // For QR code scanning, find student by QR code with proper data isolation
        $query = "SELECT tbl_student_id, student_name, course_section, user_id, school_id 
                  FROM tbl_student 
                  WHERE generated_code = ? AND school_id = ? AND user_id = ?";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("sii", $qr_code, $school_id, $user_id);
    }
    
    if (!$stmt->execute()) {
        error_log("SQL Error: " . $stmt->error);
        echo json_encode([
            'success' => false,
            'message' => 'Database query failed',
            'debug' => $stmt->error
        ]);
        exit;
    }
    
    $result = $stmt->get_result();
    error_log("QR lookup result rows: " . $result->num_rows);
    
    if ($result->num_rows === 0) {
        error_log("No matching student found in database");
        if ($is_manual_entry) {
            echo json_encode([
                'success' => false,
                'message' => 'No student found for the specified course and section.',
                'error' => 'invalid_qr'
            ]);
        } else {
            // Log the actual query for debugging
            error_log("Query: $query with params: QR=$qr_code, SchoolID=$school_id, UserID=$user_id");
            
            // Alternative lookup to debug if there's any matching QR code at all
            $debug_stmt = $conn_qr->prepare("SELECT tbl_student_id, generated_code, school_id, user_id FROM tbl_student WHERE generated_code = ?");
            $debug_stmt->bind_param("s", $qr_code);
            $debug_stmt->execute();
            $debug_result = $debug_stmt->get_result();
            error_log("Alternative lookup (ignoring school/user): found " . $debug_result->num_rows . " matches");
            
            echo json_encode([
                'success' => false,
                'message' => 'Invalid QR code. Student not found.',
                'error' => 'invalid_qr',
                'debug' => [
                    'qr_code' => $qr_code,
                    'school_id' => $school_id,
                    'user_id' => $user_id
                ]
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
        // Fetch latest attendance details for this student today to show in modal
        $latestStmt = $conn_qr->prepare("SELECT time_in, status FROM tbl_attendance 
               WHERE tbl_student_id = ? AND DATE(time_in) = ? AND instructor_id = ? AND user_id = ? AND school_id = ?
               ORDER BY time_in DESC LIMIT 1");
        $latestStmt->bind_param("isiii", $student_id, $today, $instructor_id, $user_id, $school_id);
        $latestStmt->execute();
        $latest = $latestStmt->get_result()->fetch_assoc();
        $latest_time_in = $latest ? $latest['time_in'] : null;
        $latest_status = $latest ? $latest['status'] : null;
        $attendance_time = $latest_time_in ? date('h:i A', strtotime($latest_time_in)) : null;
        $attendance_date = $latest_time_in ? date('M d, Y', strtotime($latest_time_in)) : null;

        echo json_encode([
            'success' => false,
            'message' => 'Student has already checked in today for this class.',
            'error' => 'duplicate_scan',
            'data' => [
                'student_id' => $student_id,
                'student_name' => $student_name,
                'instructor_name' => ($_SESSION['current_instructor_name'] ?? ''),
                'subject_name' => ($_SESSION['current_subject_name'] ?? ''),
                'attendance_time' => $attendance_time,
                'attendance_date' => $attendance_date,
                'attendance_status' => $latest_status,
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
    
    // Verify database connection before proceeding
    if (!$conn_qr || mysqli_connect_errno()) {
        error_log("Database connection error before INSERT: " . mysqli_connect_error());
        echo json_encode([
            'success' => false,
            'message' => 'Database connection error: ' . mysqli_connect_error()
        ]);
        exit;
    }
    
    // Record attendance with robust error handling
    $query = "INSERT INTO tbl_attendance 
             (tbl_student_id, time_in, status, instructor_id, subject_id, user_id, school_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    // Enhanced error reporting before prepare
    error_log("About to prepare INSERT query: " . $query);
    
    $stmt = $conn_qr->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn_qr->error);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare statement: ' . $conn_qr->error
        ]);
        exit;
    }
    
    // Get subject_id from session or POST data (with default value)
    $subject_id = isset($_SESSION['current_subject_id']) ? intval($_SESSION['current_subject_id']) : 1;
    if ($subject_id <= 0) $subject_id = 1; // Ensure valid integer
    
    // Use safe values for user_id and school_id
    $current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;
    $current_school_id = isset($_SESSION['school_id']) ? intval($_SESSION['school_id']) : 1;
    
    // Always use the current user_id as the instructor_id
    // This ensures we always have a valid instructor_id that exists in the users table
    $instructor_id = $current_user_id;
    
    // Debug logging
    error_log("Attendance recording - User ID: $current_user_id, School ID: $current_school_id");
    error_log("Session variables: " . json_encode($_SESSION));
    error_log("Student ID: $student_id, Time: $currentTime, Status: $status, Instructor: $instructor_id, Subject: $subject_id");
    
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