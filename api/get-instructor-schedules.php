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

try {
    // Get schedules for the current instructor
    $query = "SELECT DISTINCT 
                subject, 
                course_section as section,
                start_time,
                end_time,
                day,
                room
              FROM teacher_schedules 
              WHERE instructor_name = ? 
              AND user_id = ? 
              AND school_id = ?
              ORDER BY subject, course_section";
              
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("sii", $instructor_name, $user_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedules = [];
    $subjects = [];
    $sections = [];
    
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
        
        // Collect unique subjects and sections
        if (!in_array($row['subject'], $subjects)) {
            $subjects[] = $row['subject'];
        }
        if (!in_array($row['section'], $sections)) {
            $sections[] = $row['section'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules,
        'subjects' => $subjects,
        'sections' => $sections,
        'instructor_name' => $instructor_name
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching instructor schedules: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
