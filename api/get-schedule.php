<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $request_school_id = $_POST['school_id'] ?? $school_id;
    
    // Validate school_id matches session
    if ($request_school_id != $school_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid school access.']);
        exit();
    }
    
    if (empty($section) || empty($subject)) {
        echo json_encode(['success' => false, 'message' => 'Section and Subject are required.']);
        exit();
    }
    
    // Get current instructor from session
    $instructor = $_SESSION['current_instructor_name'] ?? $_SESSION['userData']['username'] ?? $_SESSION['email'] ?? 'Current Instructor';
    
    try {
        // Check if class_schedules table exists, create if not
        $createTableQuery = "CREATE TABLE IF NOT EXISTS class_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            instructor_name VARCHAR(100) NOT NULL,
            course_section VARCHAR(50) NOT NULL,
            subject VARCHAR(100) NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            day_of_week VARCHAR(20) NOT NULL,
            school_id INT NOT NULL DEFAULT 1,
            user_id INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_school_user (school_id, user_id),
            INDEX idx_schedule_lookup (instructor_name, course_section, subject, school_id)
        )";
        
        if (!$conn_qr->query($createTableQuery)) {
            throw new Exception("Error creating class_schedules table: " . $conn_qr->error);
        }
        
        // Search for matching schedule in teacher_schedules table
        $query = "SELECT * FROM teacher_schedules 
                  WHERE teacher_username = ? 
                  AND section = ? 
                  AND subject = ? 
                  AND school_id = ? 
                  AND status = 'active'
                  ORDER BY start_time 
                  LIMIT 1";
        
        $stmt = $conn_qr->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn_qr->error);
        }
        
        $stmt->bind_param("sssi", $instructor, $section, $subject, $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $schedule = $result->fetch_assoc();
            // Map teacher_schedules fields to expected frontend format
            $schedule['instructor_name'] = $schedule['teacher_username'];
            $schedule['course_section'] = $schedule['section'];
            echo json_encode([
                'success' => true,
                'schedule' => $schedule,
                'message' => 'Schedule found successfully.'
            ]);
        } else {
            // If no exact match found, create a sample schedule entry in teacher_schedules
            $default_start_time = '08:00:00';
            $default_end_time = '09:00:00';
            $default_day = date('l'); // Current day
            
            $insertQuery = "INSERT INTO teacher_schedules (teacher_username, section, subject, start_time, end_time, day_of_week, school_id, user_id, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
            $insertStmt = $conn_qr->prepare($insertQuery);
            $insertStmt->bind_param("ssssssii", $instructor, $section, $subject, $default_start_time, $default_end_time, $default_day, $school_id, $user_id);
            
            if ($insertStmt->execute()) {
                $new_schedule = [
                    'id' => $conn_qr->insert_id,
                    'instructor_name' => $instructor,
                    'course_section' => $section, // Map section to course_section for frontend compatibility
                    'subject' => $subject,
                    'start_time' => $default_start_time,
                    'end_time' => $default_end_time,
                    'day_of_week' => $default_day,
                    'school_id' => $school_id,
                    'user_id' => $user_id
                ];
                
                echo json_encode([
                    'success' => true,
                    'schedule' => $new_schedule,
                    'message' => 'New schedule created successfully.'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create schedule.']);
            }
        }
        
    } catch (Exception $e) {
        error_log("Schedule API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?> 