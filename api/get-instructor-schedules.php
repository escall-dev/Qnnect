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

// Get the actual teacher username from the login system (this doesn't change when instructor display name is edited)
$teacher_username = $_SESSION['userData']['username'] ?? $_SESSION['email'] ?? '';

try {
    // Get schedules for the current instructor using teacher_username from teacher_schedules table
    $query = "SELECT DISTINCT 
                subject, 
                section as section,
                start_time,
                end_time,
                day_of_week as day,
                room
              FROM teacher_schedules 
              WHERE teacher_username = ? 
              AND user_id = ? 
              AND school_id = ?
              AND status = 'active'
              ORDER BY subject, section";
              
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("sii", $teacher_username, $user_id, $school_id);
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
        'instructor_name' => $teacher_username
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching instructor schedules: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
