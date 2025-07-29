<?php
// api/export-teacher-schedule.php
// Export Teacher Schedule API
require_once '../includes/session_config.php';
require_once '../conn/db_connect_pdo.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = $conn_qr_pdo;
$user_email = $_SESSION['email'];
$user_school_id = $_SESSION['school_id'] ?? 1;

// Get teacher username
$stmt = $pdo->prepare("SELECT username FROM login_register WHERE email = ?");
$stmt->execute([$user_email]);
$teacher_username = $stmt->fetchColumn();

if (!$teacher_username) {
    echo json_encode(['success' => false, 'message' => 'Teacher not found']);
    exit;
}

try {
    // Get teacher's schedules
    $sql = "SELECT * FROM master_schedule 
            WHERE instructor = ? AND school_id = ? 
            ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacher_username, $user_school_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get school info
    $school_stmt = $pdo->prepare("SELECT school_name FROM school_info WHERE school_id = ?");
    $school_stmt->execute([$user_school_id]);
    $school_name = $school_stmt->fetchColumn() ?: 'Unknown School';
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="teacher_schedule_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Create Excel content
    echo "<table border='1'>";
    echo "<tr><th colspan='6' style='background-color: #098744; color: white; font-size: 16px; padding: 10px;'>";
    echo "Teacher Schedule - " . htmlspecialchars($teacher_username) . "<br>";
    echo "School: " . htmlspecialchars($school_name) . "<br>";
    echo "Generated on: " . date('F j, Y g:i A');
    echo "</th></tr>";
    
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Day</th>";
    echo "<th>Subject</th>";
    echo "<th>Section</th>";
    echo "<th>Time</th>";
    echo "<th>Room</th>";
    echo "<th>Duration</th>";
    echo "</tr>";
    
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    foreach ($days as $day) {
        $day_schedules = array_filter($schedules, function($schedule) use ($day) {
            return $schedule['day_of_week'] === $day;
        });
        
        if (!empty($day_schedules)) {
            foreach ($day_schedules as $schedule) {
                $start_time = formatTimeForExport($schedule['start_time']);
                $end_time = formatTimeForExport($schedule['end_time']);
                $duration = calculateDuration($schedule['start_time'], $schedule['end_time']);
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($day) . "</td>";
                echo "<td>" . htmlspecialchars($schedule['subject']) . "</td>";
                echo "<td>" . htmlspecialchars($schedule['section']) . "</td>";
                echo "<td>" . $start_time . " - " . $end_time . "</td>";
                echo "<td>" . htmlspecialchars($schedule['room'] ?: 'TBA') . "</td>";
                echo "<td>" . $duration . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($day) . "</td>";
            echo "<td colspan='5' style='text-align: center; color: #999;'>No classes scheduled</td>";
            echo "</tr>";
        }
    }
    
    // Add summary
    echo "<tr><td colspan='6' style='background-color: #f8f9fa; padding: 10px;'>";
    echo "<strong>Summary:</strong><br>";
    echo "Total classes: " . count($schedules) . "<br>";
    echo "Days with classes: " . count(array_unique(array_column($schedules, 'day_of_week'))) . "<br>";
    echo "Total teaching hours per week: " . calculateTotalHours($schedules);
    echo "</td></tr>";
    
    echo "</table>";
    
    // Log activity
    logActivity($pdo, 'SCHEDULE_EXPORTED', "Teacher: $teacher_username, Total classes: " . count($schedules));
    
} catch (Exception $e) {
    error_log("Error exporting schedule: " . $e->getMessage());
    echo "Error occurred while generating the export.";
}

function calculateDuration($start_time, $end_time) {
    $start = strtotime($start_time);
    $end = strtotime($end_time);
    $diff = $end - $start;
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . "h " . $minutes . "m";
    } else {
        return $minutes . " minutes";
    }
}

function formatTimeForExport($time) {
    // Handle different time formats
    if (strpos($time, 'PM') !== false || strpos($time, 'AM') !== false) {
        // Already in 12-hour format
        return $time;
    } else {
        // Convert from 24-hour format to 12-hour format
        return date('g:i A', strtotime($time));
    }
}

function calculateTotalHours($schedules) {
    $total_minutes = 0;
    foreach ($schedules as $schedule) {
        $start = strtotime($schedule['start_time']);
        $end = strtotime($schedule['end_time']);
        $total_minutes += ($end - $start) / 60;
    }
    
    $hours = floor($total_minutes / 60);
    $minutes = $total_minutes % 60;
    
    if ($hours > 0) {
        return $hours . "h " . $minutes . "m";
    } else {
        return $minutes . " minutes";
    }
}

function logActivity($pdo, $action, $details) {
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $sql = "INSERT INTO activity_logs (user_id, action_type, action_description, created_at) 
                VALUES (?, 'data_export', ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, "Schedule Export: $action - $details"]);
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}
?> 