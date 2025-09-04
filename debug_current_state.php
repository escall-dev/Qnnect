<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

header('Content-Type: application/json');

// Check current session and database state
$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_data' => [
        'email' => $_SESSION['email'] ?? 'not set',
        'user_id' => $_SESSION['user_id'] ?? 'not set',
        'school_id' => $_SESSION['school_id'] ?? 'not set',
        'current_instructor_name' => $_SESSION['current_instructor_name'] ?? 'not set',
        'userData' => $_SESSION['userData'] ?? 'not set',
    ]
];

// Check user in database
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    
    // Get user from login database
    $user_query = "SELECT id, username, email, school_id FROM users WHERE email = ?";
    $stmt = $conn_login->prepare($user_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    $debug_info['database_user'] = $user;
    
    if ($user) {
        // Check schedules for this user
        $username = $user['username'];
        $user_id = $user['id'];
        $school_id = $user['school_id'];
        
        // Get all schedules for this teacher_username
        $schedule_query = "SELECT id, teacher_username, subject, section, day_of_week, status, user_id, school_id 
                          FROM teacher_schedules 
                          WHERE teacher_username = ? 
                          ORDER BY subject";
        $stmt = $conn_qr->prepare($schedule_query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $schedule_result = $stmt->get_result();
        
        $schedules = [];
        while ($row = $schedule_result->fetch_assoc()) {
            $schedules[] = $row;
        }
        
        $debug_info['schedules_for_username'] = [
            'username' => $username,
            'count' => count($schedules),
            'schedules' => $schedules
        ];
        
        // Test the actual API call that's failing
        require_once '../includes/schedule_helper.php';
        
        try {
            $scheduleData = getScheduleDropdownData($school_id, $username, $user_id);
            $debug_info['schedule_helper_result'] = $scheduleData;
        } catch (Exception $e) {
            $debug_info['schedule_helper_error'] = $e->getMessage();
        }
        
        // Check if there are any schedules with different usernames that might be the old data
        $orphan_query = "SELECT DISTINCT teacher_username, COUNT(*) as count 
                        FROM teacher_schedules 
                        WHERE teacher_username != ? 
                        AND (user_id = ? OR school_id = ?)
                        GROUP BY teacher_username";
        $stmt = $conn_qr->prepare($orphan_query);
        $stmt->bind_param("sii", $username, $user_id, $school_id);
        $stmt->execute();
        $orphan_result = $stmt->get_result();
        
        $orphan_schedules = [];
        while ($row = $orphan_result->fetch_assoc()) {
            $orphan_schedules[] = $row;
        }
        
        $debug_info['potential_orphan_schedules'] = $orphan_schedules;
    }
}

// Check recent controller.php activity
$log_file = '/xampp/apache/logs/error.log';
if (file_exists($log_file)) {
    $recent_logs = [];
    $handle = fopen($log_file, 'r');
    if ($handle) {
        $lines = [];
        while (($line = fgets($handle)) !== false) {
            $lines[] = $line;
        }
        fclose($handle);
        
        // Get last 20 lines that mention username updates
        $relevant_lines = array_filter($lines, function($line) {
            return strpos($line, 'Updated teacher_schedules') !== false || 
                   strpos($line, 'username') !== false;
        });
        
        $debug_info['recent_logs'] = array_slice($relevant_lines, -10);
    }
}

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>
