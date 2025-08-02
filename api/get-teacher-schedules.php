<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$conn = $conn_qr;
$conn_users = $conn_login;

// Get user's information
$email = $_SESSION['email'];
$user_query = "SELECT school_id, role, username, id FROM users WHERE email = ?";
$stmt = $conn_users->prepare($user_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$school_id = $user['school_id'] ?? 1;
$teacher_username = $user['username'] ?? $_SESSION['email'];
$user_id = $user['id'] ?? null;

// Get all schedules for this teacher (both active and inactive)
$schedule_query = "SELECT * FROM teacher_schedules 
                   WHERE (teacher_username = ? AND school_id = ?) 
                   OR (user_id = ? AND user_id IS NOT NULL)
                   ORDER BY status DESC, day_of_week, start_time";

$stmt = $conn->prepare($schedule_query);
$stmt->bind_param("sii", $teacher_username, $school_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}

echo json_encode([
    'success' => true, 
    'schedules' => $schedules,
    'user_info' => [
        'teacher_username' => $teacher_username,
        'school_id' => $school_id,
        'user_id' => $user_id
    ]
]);
?>
