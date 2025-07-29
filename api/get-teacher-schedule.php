<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Use database connections
$conn = $conn_qr;
$conn_users = $conn_login;

// Get user's school_id and username
$email = $_SESSION['email'];
$user_query = "SELECT school_id, role, username FROM users WHERE email = ?";
$stmt = $conn_users->prepare($user_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$school_id = $user['school_id'] ?? 1;
$teacher_username = $user['username'] ?? $_SESSION['email'];

// Get specific schedule by ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $schedule_id = intval($_GET['id']);
    
    $sql = "SELECT id, subject, section, day_of_week, start_time, end_time, room, status, created_at 
            FROM teacher_schedules 
            WHERE id = ? AND teacher_username = ? AND school_id = ? AND status = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $schedule_id, $teacher_username, $school_id);
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

// Get all schedules for the teacher
$sql = "SELECT id, subject, section, day_of_week, start_time, end_time, room, status, created_at 
        FROM teacher_schedules 
        WHERE teacher_username = ? AND school_id = ? AND status = 'active'
        ORDER BY 
            FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
            start_time";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $teacher_username, $school_id);
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
exit;
?> 