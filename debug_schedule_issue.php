<?php
require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';
require_once 'includes/schedule_helper.php';

header('Content-Type: application/json');

// Debug current session state
$debug_info = [
    'session_data' => [
        'email' => $_SESSION['email'] ?? 'not set',
        'user_id' => $_SESSION['user_id'] ?? 'not set',
        'school_id' => $_SESSION['school_id'] ?? 'not set',
        'current_instructor_name' => $_SESSION['current_instructor_name'] ?? 'not set',
        'userData_username' => $_SESSION['userData']['username'] ?? 'not set',
        'userData_email' => $_SESSION['userData']['email'] ?? 'not set',
    ]
];

// Check user lookup from database
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $user_query = "SELECT school_id, role, username, id FROM users WHERE email = ?";
    $stmt = $conn_login->prepare($user_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    $debug_info['database_user_lookup'] = $user ? $user : 'user not found';
    
    if ($user) {
        $school_id = $user['school_id'] ?? 1;
        $teacher_username = $user['username'] ?? $_SESSION['email'];
        $user_id = $user['id'] ?? null;
        
        $debug_info['resolved_values'] = [
            'school_id' => $school_id,
            'teacher_username' => $teacher_username,
            'user_id' => $user_id
        ];
        
        // Test the helper function
        $scheduleData = getScheduleDropdownData($school_id, $teacher_username, (int)$user_id);
        $debug_info['schedule_data'] = $scheduleData;
        
        // Check if there are any schedules in teacher_schedules for this user
        $query = "SELECT COUNT(*) as count FROM teacher_schedules WHERE teacher_username = ? AND user_id = ? AND school_id = ? AND status = 'active'";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("sii", $teacher_username, $user_id, $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count_result = $result->fetch_assoc();
        $debug_info['schedule_count'] = $count_result['count'];
        
        // Show sample schedules
        $query = "SELECT id, teacher_username, subject, section, day_of_week, start_time, end_time FROM teacher_schedules WHERE teacher_username = ? AND user_id = ? AND school_id = ? AND status = 'active' LIMIT 3";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("sii", $teacher_username, $user_id, $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sample_schedules = [];
        while ($row = $result->fetch_assoc()) {
            $sample_schedules[] = $row;
        }
        $debug_info['sample_schedules'] = $sample_schedules;
    }
}

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>
