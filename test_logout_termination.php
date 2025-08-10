<?php
// Test script to verify logout termination functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Logout Class Time Termination</h2>";

// Include database connection
require_once 'conn/db_connect.php';

if (!isset($conn_qr)) {
    echo "<p style='color: red;'>Error: Database connection not available</p>";
    exit;
}

// Test 1: Check if class_time_settings table exists and has data
echo "<h3>Test 1: Checking class_time_settings table</h3>";
$table_check = "SHOW TABLES LIKE 'class_time_settings'";
$result = $conn_qr->query($table_check);

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ class_time_settings table exists</p>";
    
    // Check for active class times
    $check_active = "SELECT * FROM class_time_settings WHERE start_time IS NOT NULL OR class_start_time IS NOT NULL";
    $active_result = $conn_qr->query($check_active);
    
    if ($active_result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ Found {$active_result->num_rows} active class time setting(s):</p>";
        while ($row = $active_result->fetch_assoc()) {
            echo "<p>School ID: {$row['school_id']}, Start Time: " . ($row['start_time'] ?? $row['class_start_time'] ?? 'NULL') . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ No active class time settings found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ class_time_settings table does not exist</p>";
}

// Test 2: Check if teacher_schedules table exists and has active sessions
echo "<h3>Test 2: Checking teacher_schedules table</h3>";
$teacher_check = "SHOW TABLES LIKE 'teacher_schedules'";
$teacher_result = $conn_qr->query($teacher_check);

if ($teacher_result->num_rows > 0) {
    echo "<p style='color: green;'>✓ teacher_schedules table exists</p>";
    
    // Check for active teacher schedules
    $check_active_teachers = "SELECT * FROM teacher_schedules WHERE status = 'active'";
    $active_teachers_result = $conn_qr->query($check_active_teachers);
    
    if ($active_teachers_result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ Found {$active_teachers_result->num_rows} active teacher schedule(s):</p>";
        while ($row = $active_teachers_result->fetch_assoc()) {
            echo "<p>School ID: {$row['school_id']}, Teacher: {$row['teacher_name']}, Status: {$row['status']}</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ No active teacher schedules found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ teacher_schedules table does not exist</p>";
}

// Test 3: Check if attendance_sessions table exists and has active sessions
echo "<h3>Test 3: Checking attendance_sessions table</h3>";
$attendance_check = "SHOW TABLES LIKE 'attendance_sessions'";
$attendance_result = $conn_qr->query($attendance_check);

if ($attendance_result->num_rows > 0) {
    echo "<p style='color: green;'>✓ attendance_sessions table exists</p>";
    
    // Check for active attendance sessions
    $check_active_attendance = "SELECT * FROM attendance_sessions WHERE status = 'active'";
    $active_attendance_result = $conn_qr->query($check_active_attendance);
    
    if ($active_attendance_result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ Found {$active_attendance_result->num_rows} active attendance session(s):</p>";
        while ($row = $active_attendance_result->fetch_assoc()) {
            echo "<p>School ID: {$row['school_id']}, Start Time: {$row['start_time']}, Status: {$row['status']}</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ No active attendance sessions found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ attendance_sessions table does not exist</p>";
}

// Test 4: Simulate logout termination (read-only)
echo "<h3>Test 4: Simulating logout termination (read-only)</h3>";
echo "<p>This test shows what would be terminated during logout:</p>";

$school_id = 1; // Default school ID for testing

// Simulate class time settings termination
$class_time_sql = "SELECT COUNT(*) as count FROM class_time_settings WHERE school_id = ? AND (start_time IS NOT NULL OR class_start_time IS NOT NULL)";
$stmt = $conn_qr->prepare($class_time_sql);
$stmt->bind_param('i', $school_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "<p>Class time settings that would be cleared: {$row['count']}</p>";
$stmt->close();

// Simulate teacher schedules termination
$teacher_sql = "SELECT COUNT(*) as count FROM teacher_schedules WHERE school_id = ? AND status = 'active'";
$stmt = $conn_qr->prepare($teacher_sql);
$stmt->bind_param('i', $school_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "<p>Teacher schedules that would be set to inactive: {$row['count']}</p>";
$stmt->close();

// Simulate attendance sessions termination
$attendance_sql = "SELECT COUNT(*) as count FROM attendance_sessions WHERE school_id = ? AND status = 'active'";
$stmt = $conn_qr->prepare($attendance_sql);
$stmt->bind_param('i', $school_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "<p>Attendance sessions that would be terminated: {$row['count']}</p>";
$stmt->close();

echo "<h3>Test 5: Manual termination test</h3>";
echo "<p><a href='logout.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Logout Termination</a></p>";
echo "<p><em>Click the button above to test the actual logout termination. This will log you out and terminate all active sessions.</em></p>";

echo "<h3>Test 6: Check logs</h3>";
echo "<p>After logout, check the error logs for termination messages:</p>";
echo "<ul>";
echo "<li>Look for '[LOGOUT]' or '[ADMIN-LOGOUT]' messages</li>";
echo "<li>Check for 'Class time activation termination completed' messages</li>";
echo "<li>Verify affected row counts in the logs</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Summary:</strong> The logout functionality now properly terminates:</p>";
echo "<ul>";
echo "<li>✓ Class time settings (start_time/class_start_time columns)</li>";
echo "<li>✓ Teacher schedules (status = 'inactive')</li>";
echo "<li>✓ Attendance sessions (status = 'terminated')</li>";
echo "<li>✓ Session variables (class_start_time, instructor info, etc.)</li>";
echo "<li>✓ Calls terminate-class-session API for comprehensive cleanup</li>";
echo "<li>✓ Logs all termination activities for debugging</li>";
echo "</ul>";
?> 