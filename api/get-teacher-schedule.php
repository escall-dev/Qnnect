<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Use database connection
$conn = $conn_qr;

try {
    // Get specific schedule by ID
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $schedule_id = intval($_GET['id']);
        
        if ($schedule_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid schedule ID']);
            exit;
        }
        
        // Simple query like admin panel - just get the schedule data
        $sql = "SELECT id, subject, section, day_of_week, start_time, end_time, room, teacher_username, school_id, status, created_at 
                FROM teacher_schedules 
                WHERE id = ? AND status = 'active'";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare schedule query: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $schedule = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $schedule]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Schedule not found']);
        }
        exit;
    }

    // Get all schedules for the logged-in user (simplified)
    $email = $_SESSION['email'];
    
    $sql = "SELECT id, subject, section, day_of_week, start_time, end_time, room, status, created_at 
            FROM teacher_schedules 
            WHERE status = 'active'
            ORDER BY 
                FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                start_time";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare schedules query: ' . $conn->error);
    }
    
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

} catch (Exception $e) {
    error_log("Error in get-teacher-schedule.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
?> 