<?php
// Start session to access instructor data
session_start();

// Include database connection and helper functions
include('../conn/db_connect.php');
include('../includes/attendance_grade_helper.php');

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    // Get instructor and subject from session
    $instructorId = isset($_SESSION['current_instructor_id']) ? $_SESSION['current_instructor_id'] : null;
    $subjectId = isset($_SESSION['current_subject_id']) ? $_SESSION['current_subject_id'] : null;
    
    // Get class time information from POST data
    $startTimeStr = isset($_POST['start_time']) ? $_POST['start_time'] : null;
    $durationMinutes = isset($_POST['duration']) ? (int)$_POST['duration'] : 60; // Default 1 hour
    
    // Get academic settings from session
    $term = isset($_SESSION['semester']) ? $_SESSION['semester'] : '1st Semester';
    $schoolYear = isset($_SESSION['school_year']) ? $_SESSION['school_year'] : date('Y') . '-' . (date('Y') + 1);
    
    // Validate required data
    if (!$instructorId || !$subjectId || !$startTimeStr) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required data: instructor, subject, or start time'
        ]);
        exit;
    }
    
    // Get current date
    $currentDate = date('Y-m-d');
    
    // Parse start time (format: HH:MM)
    $startTime = $currentDate . ' ' . $startTimeStr . ':00';
    
    // Calculate end time based on duration
    $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' + ' . $durationMinutes . ' minutes'));
    
    // Get section information
    $sectionQuery = "SELECT DISTINCT course_section FROM tbl_student WHERE course_section LIKE ? LIMIT 1";
    $sectionStmt = $conn_qr->prepare($sectionQuery);
    $subjectCode = "%"; // Default wildcard if we can't determine
    
    // Try to get subject code from the subject name
    if ($subjectId) {
        $subjectQuery = "SELECT subject_name FROM tbl_subjects WHERE subject_id = ?";
        $subjectStmt = $conn_qr->prepare($subjectQuery);
        $subjectStmt->bind_param("i", $subjectId);
        $subjectStmt->execute();
        $subjectResult = $subjectStmt->get_result();
        
        if ($subjectResult && $subjectResult->num_rows > 0) {
            $subjectName = $subjectResult->fetch_assoc()['subject_name'];
            // Extract subject code (this is just a simple example, adjust as needed)
            $parts = explode(' ', $subjectName);
            if (!empty($parts[0])) {
                $subjectCode = $parts[0] . "%";
            }
        }
    }
    
    $sectionStmt->bind_param("s", $subjectCode);
    $sectionStmt->execute();
    $sectionResult = $sectionStmt->get_result();
    $section = ($sectionResult && $sectionResult->num_rows > 0) 
              ? $sectionResult->fetch_assoc()['course_section'] 
              : 'DEFAULT';
    
    // Convert MySQL connection to PDO for use with the helper functions
    $dsn = 'mysql:host=localhost;dbname=qr_attendance_db;charset=utf8mb4';
    $username = 'root';
    $password = '';
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Create attendance session
    $sessionId = createAttendanceSession(
        $pdo, 
        $instructorId, 
        $subjectId, // Using subject_id as course_id for simplicity
        $term,
        $section,
        $startTime,
        $endTime
    );
    
    if ($sessionId) {
        // Session created successfully
        echo json_encode([
            'success' => true,
            'message' => 'Attendance session created successfully',
            'data' => [
                'session_id' => $sessionId,
                'instructor_id' => $instructorId,
                'course_id' => $subjectId,
                'term' => $term,
                'section' => $section,
                'start_time' => $startTime,
                'end_time' => $endTime
            ]
        ]);
        
        // Log the creation of the attendance session
        $activityDescription = "Created attendance session for " . 
                              (isset($_SESSION['current_subject_name']) ? $_SESSION['current_subject_name'] : 'Unknown subject');
        
        // Check if activity logging function exists
        if (function_exists('logActivity')) {
            logActivity(
                'attendance_session',
                $activityDescription,
                'attendance_sessions',
                $sessionId
            );
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create attendance session'
        ]);
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Error in create-attendance-session.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
} 