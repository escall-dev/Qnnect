<?php
// Start session to access instructor data
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Session expired or not logged in.']);
    exit();
}
$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

// Include database connection and helper functions
include('../conn/db_connect.php');
include('../includes/attendance_grade_helper.php');

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

try {
    // Get data from POST request
    $studentId = isset($_POST['student_id']) ? (int)$_POST['student_id'] : null;
    $instructorId = isset($_SESSION['current_instructor_id']) ? $_SESSION['current_instructor_id'] : null;
    $subjectId = isset($_SESSION['current_subject_id']) ? $_SESSION['current_subject_id'] : null;
    
    // Validate required data
    if (!$studentId || !$instructorId || !$subjectId) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required data: student, instructor, or subject'
        ]);
        exit;
    }
    
    // Get current date for finding active sessions
    $currentDate = date('Y-m-d');
    $currentTime = date('Y-m-d H:i:s');
    
    // Convert MySQL connection to PDO for use with helper functions
    $dsn = 'mysql:host=localhost;dbname=qr_attendance_db;charset=utf8mb4';
    $username = 'root';
    $password = '';
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Find active session
    $sessionQuery = "
        SELECT id, start_time, end_time, term, section 
        FROM attendance_sessions 
        WHERE instructor_id = :instructor_id 
        AND course_id = :course_id 
        AND DATE(start_time) = :current_date
        AND start_time <= :current_time 
        AND end_time >= :current_time
        ORDER BY start_time DESC 
        LIMIT 1
    ";
    
    $sessionStmt = $pdo->prepare($sessionQuery);
    $sessionStmt->execute([
        ':instructor_id' => $instructorId,
        ':course_id' => $subjectId,
        ':current_date' => $currentDate,
        ':current_time' => $currentTime,
        ':current_time' => $currentTime
    ]);
    
    $sessionData = $sessionStmt->fetch();
    
    if (!$sessionData) {
        // No active session found - create a new session using default values
        // Get academic settings from session
        $term = isset($_SESSION['semester']) ? $_SESSION['semester'] : '1st Semester';
        
        // Get section information
        $sectionQuery = "SELECT course_section FROM tbl_student WHERE tbl_student_id = ? LIMIT 1";
        $sectionStmt = $conn_qr->prepare($sectionQuery);
        $sectionStmt->bind_param("i", $studentId);
        $sectionStmt->execute();
        $sectionResult = $sectionStmt->get_result();
        $section = ($sectionResult && $sectionResult->num_rows > 0) 
                  ? $sectionResult->fetch_assoc()['course_section'] 
                  : 'DEFAULT';
        
        // Create default session times (current time +/- 1 hour)
        $startTime = date('Y-m-d H:i:s', strtotime($currentTime . ' - 15 minutes'));
        $endTime = date('Y-m-d H:i:s', strtotime($currentTime . ' + 45 minutes'));
        
        // Create a new session
        $sessionId = createAttendanceSession(
            $pdo,
            $instructorId,
            $subjectId,
            $term,
            $section,
            $startTime,
            $endTime
        );
        
        if (!$sessionId) {
            echo json_encode([
                'success' => false,
                'message' => 'No active session found and failed to create a new one'
            ]);
            exit;
        }
        
        // Use the created session
        $sessionData = [
            'id' => $sessionId,
            'term' => $term,
            'section' => $section
        ];
    }
    
    // Check if the student already has an attendance record for this session
    $checkQuery = "
        SELECT id FROM attendance_logs 
        WHERE session_id = :session_id AND student_id = :student_id
    ";
    
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([
        ':session_id' => $sessionData['id'],
        ':student_id' => $studentId
    ]);
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Attendance already recorded for this session',
            'data' => [
                'session_id' => $sessionData['id'],
                'student_id' => $studentId,
                'duplicate' => true
            ]
        ]);
        exit;
    }
    
    // Record attendance
    $result = recordAttendance($pdo, $sessionData['id'], $studentId, $currentTime, $school_id);
    
    if ($result) {
        // Calculate and update attendance grade
        $gradeResult = calculateAndUpdateAttendanceGrade(
            $pdo, 
            $studentId, 
            $subjectId, 
            $sessionData['term'], 
            $sessionData['section']
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'data' => [
                'session_id' => $sessionData['id'],
                'student_id' => $studentId,
                'scan_time' => $currentTime,
                'grade_data' => $gradeResult['data'] ?? null
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to record attendance'
        ]);
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Error in record-attendance-log.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
} 