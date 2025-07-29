<?php
// Start session to access stored data
session_start();

// Include database connection
include('./conn/db_connect.php');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Get class start time from session
$class_start_time = isset($_SESSION['class_start_time']) ? $_SESSION['class_start_time'] : '08:00';

// Ensure it has seconds
if (strlen($class_start_time) == 5) {
    $class_start_time .= ':00';
}

echo "<h1>Attendance Status Manual Edit Tool</h1>";
echo "<p>Class start time: $class_start_time</p>";
echo "<p><strong>Note:</strong> This tool allows you to manually change attendance statuses if needed. Changing the class time will NOT automatically affect existing statuses.</p>";

// Get all attendance records from today
$today = date('Y-m-d');
$query = "SELECT tbl_attendance_id, tbl_student_id, student_name, time_in, status 
          FROM tbl_attendance 
          LEFT JOIN tbl_student ON tbl_attendance.tbl_student_id = tbl_student.tbl_student_id 
          WHERE DATE(time_in) = ?
          ORDER BY time_in DESC";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' cellpadding='5'>
<tr>
    <th>ID</th>
    <th>Student</th>
    <th>Time In</th>
    <th>Current Status</th>
    <th>Change Status</th>
</tr>";

$recordCount = 0;
while ($row = $result->fetch_assoc()) {
    $attendanceId = $row['tbl_attendance_id'];
    $timeIn = $row['time_in'];
    $currentStatus = $row['status'] ?: 'Unknown';
    $studentName = $row['student_name'];
    $recordCount++;
    
    echo "<tr>";
    echo "<td>$attendanceId</td>";
    echo "<td>$studentName</td>";
    echo "<td>$timeIn</td>";
    echo "<td>$currentStatus</td>";
    
    // Provide options to manually change status
    echo "<td>
            <a href='?fix=$attendanceId&status=On Time'>Set On Time</a> | 
            <a href='?fix=$attendanceId&status=Late'>Set Late</a>
         </td>";
    echo "</tr>";
    
    // If the fix parameter is set, update the record
    if (isset($_GET['fix']) && $_GET['fix'] == $attendanceId) {
        $newStatus = $_GET['status'];
        $updateQuery = "UPDATE tbl_attendance SET status = ? WHERE tbl_attendance_id = ?";
        $updateStmt = $conn_qr->prepare($updateQuery);
        $updateStmt->bind_param("si", $newStatus, $attendanceId);
        $updateStmt->execute();
        
        echo "<script>alert('Updated attendance record #$attendanceId to $newStatus'); window.location.href='fix_attendance_status.php';</script>";
        exit;
    }
}

echo "</table>";

if ($recordCount == 0) {
    echo "<p>No attendance records found for today.</p>";
} else {
    echo "<p>Found $recordCount attendance records for today.</p>";
}

echo "<p><a href='index.php'>Return to attendance page</a></p>";
?> 