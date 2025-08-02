<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];
$instructor_name = $_SESSION['current_instructor_name'] ?? '';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$subject = $input['subject'] ?? '';
$section = $input['section'] ?? '';

if (empty($subject) || empty($section)) {
    echo json_encode(['success' => false, 'error' => 'Subject and section are required']);
    exit();
}

try {
    // Get specific schedule details
    $query = "SELECT 
                subject, 
                course_section as section,
                start_time,
                end_time,
                day,
                room,
                instructor_name
              FROM teacher_schedules 
              WHERE instructor_name = ? 
              AND subject = ?
              AND course_section = ?
              AND user_id = ? 
              AND school_id = ?
              LIMIT 1";
              
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("sssii", $instructor_name, $subject, $section, $user_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Set session variables for the loaded schedule
        $_SESSION['schedule_start_time'] = $row['start_time'];
        $_SESSION['schedule_end_time'] = $row['end_time'];
        $_SESSION['current_subject_name'] = $row['subject'];
        $_SESSION['current_section'] = $row['section'];
        $_SESSION['current_room'] = $row['room'];
        
        // Also set the class start time for attendance comparison
        $_SESSION['class_start_time'] = $row['start_time'];
        $_SESSION['class_start_time_formatted'] = $row['start_time'];
        
        // Find or create subject in tbl_subjects
        $subject_check_query = "SELECT subject_id FROM tbl_subjects WHERE subject_name = ?";
        $subject_stmt = $conn_qr->prepare($subject_check_query);
        $subject_stmt->bind_param("s", $row['subject']);
        $subject_stmt->execute();
        $subject_result = $subject_stmt->get_result();
        
        if ($subject_result && $subject_result->num_rows > 0) {
            $subject_data = $subject_result->fetch_assoc();
            $_SESSION['current_subject_id'] = $subject_data['subject_id'];
        } else {
            // Create new subject
            $create_subject_query = "INSERT INTO tbl_subjects (subject_name) VALUES (?)";
            $create_subject_stmt = $conn_qr->prepare($create_subject_query);
            $create_subject_stmt->bind_param("s", $row['subject']);
            if ($create_subject_stmt->execute()) {
                $_SESSION['current_subject_id'] = $conn_qr->insert_id;
            }
        }
        
        echo json_encode([
            'success' => true,
            'schedule' => $row,
            'message' => 'Schedule loaded successfully'
        ]);
        
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'No schedule found for the specified subject and section'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error loading schedule: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
