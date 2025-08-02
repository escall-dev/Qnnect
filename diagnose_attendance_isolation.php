<?php
// Diagnostic script to check user isolation
require_once 'conn/db_connect.php';

echo "<h2>Diagnosing Attendance User Isolation</h2>";

try {
    // Check current user session
    session_start();
    $current_user_id = $_SESSION['user_id'] ?? 'NOT SET';
    $current_school_id = $_SESSION['school_id'] ?? 'NOT SET';
    
    echo "<h3>Current Session:</h3>";
    echo "<p>User ID: $current_user_id</p>";
    echo "<p>School ID: $current_school_id</p>";
    
    // Check student distribution by user
    echo "<h3>Student Distribution by User:</h3>";
    $student_query = "SELECT user_id, school_id, COUNT(*) as student_count 
                     FROM tbl_student 
                     GROUP BY user_id, school_id 
                     ORDER BY user_id, school_id";
    
    $stmt = $conn_qr->prepare($student_query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>User ID</th><th>School ID</th><th>Student Count</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . ($row['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['school_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['student_count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check attendance distribution by user
    echo "<h3>Attendance Distribution by User:</h3>";
    $attendance_query = "SELECT user_id, school_id, COUNT(*) as attendance_count 
                        FROM tbl_attendance 
                        GROUP BY user_id, school_id 
                        ORDER BY user_id, school_id";
    
    $stmt = $conn_qr->prepare($attendance_query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>User ID</th><th>School ID</th><th>Attendance Count</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . ($row['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['school_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['attendance_count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for any attendance records that don't match their student's user_id
    echo "<h3>Attendance Records with Mismatched User IDs:</h3>";
    $mismatch_query = "SELECT a.tbl_attendance_id, a.tbl_student_id, 
                              a.user_id as attendance_user_id, s.user_id as student_user_id,
                              a.school_id as attendance_school_id, s.school_id as student_school_id
                       FROM tbl_attendance a
                       JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id
                       WHERE a.user_id != s.user_id OR a.school_id != s.school_id";
    
    $stmt = $conn_qr->prepare($mismatch_query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Attendance ID</th><th>Student ID</th><th>Attendance User ID</th><th>Student User ID</th><th>Attendance School ID</th><th>Student School ID</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['tbl_attendance_id'] . "</td>";
            echo "<td>" . $row['tbl_student_id'] . "</td>";
            echo "<td>" . ($row['attendance_user_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['student_user_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['attendance_school_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['student_school_id'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ No mismatched records found!</p>";
    }
    
    // Show sample attendance records for current user
    if ($current_user_id !== 'NOT SET') {
        echo "<h3>Your Attendance Records (User ID: $current_user_id):</h3>";
        $my_records_query = "SELECT a.tbl_attendance_id, a.tbl_student_id, s.student_name, 
                                   a.time_in, a.status, a.user_id, a.school_id
                            FROM tbl_attendance a
                            JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id
                            WHERE a.user_id = ?
                            ORDER BY a.time_in DESC
                            LIMIT 10";
        
        $stmt = $conn_qr->prepare($my_records_query);
        $stmt->bind_param("i", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Attendance ID</th><th>Student Name</th><th>Time In</th><th>Status</th><th>User ID</th><th>School ID</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['tbl_attendance_id'] . "</td>";
                echo "<td>" . $row['student_name'] . "</td>";
                echo "<td>" . $row['time_in'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>" . $row['user_id'] . "</td>";
                echo "<td>" . $row['school_id'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>No attendance records found for your user ID.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 