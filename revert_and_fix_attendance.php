<?php
// Revert and properly fix attendance records
require_once 'conn/db_connect.php';

echo "<h2>Reverting and Properly Fixing Attendance Records</h2>";

try {
    session_start();
    $current_user_id = $_SESSION['user_id'] ?? 'NOT SET';
    $current_school_id = $_SESSION['school_id'] ?? 'NOT SET';
    
    echo "<p>Current User ID: $current_user_id</p>";
    echo "<p>Current School ID: $current_school_id</p>";
    
    // First, let's see what the current state is
    echo "<h3>Current State:</h3>";
    $check_query = "SELECT user_id, school_id, COUNT(*) as count 
                   FROM tbl_attendance 
                   GROUP BY user_id, school_id";
    $stmt = $conn_qr->prepare($check_query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<table border='1'>";
    echo "<tr><th>User ID</th><th>School ID</th><th>Count</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . ($row['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['school_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // The issue is that we need to determine which user should own each attendance record
    // based on when it was created and who was logged in at that time
    // Since we can't determine this from the data, we need a different approach
    
    echo "<h3>Proper Fix Strategy:</h3>";
    echo "<p>Since we can't determine which user originally created each attendance record,</p>";
    echo "<p>we need to assign them based on the student's owner (user_id).</p>";
    
    // Fix attendance records to match their student's user_id and school_id
    $fix_query = "UPDATE tbl_attendance a 
                  JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id 
                  SET a.user_id = s.user_id, a.school_id = s.school_id 
                  WHERE a.user_id IS NULL OR a.school_id IS NULL";
    
    $stmt = $conn_qr->prepare($fix_query);
    $result = $stmt->execute();
    $affected = $stmt->affected_rows;
    
    echo "<p>✅ Updated $affected attendance records to match their student's user_id and school_id</p>";
    
    // Verify the fix
    echo "<h3>After Fix:</h3>";
    $check_query = "SELECT user_id, school_id, COUNT(*) as count 
                   FROM tbl_attendance 
                   GROUP BY user_id, school_id";
    $stmt = $conn_qr->prepare($check_query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<table border='1'>";
    echo "<tr><th>User ID</th><th>School ID</th><th>Count</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . ($row['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['school_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show records for current user
    if ($current_user_id !== 'NOT SET') {
        echo "<h3>Your Attendance Records:</h3>";
        $my_records = "SELECT a.tbl_attendance_id, s.student_name, a.time_in, a.status 
                      FROM tbl_attendance a 
                      JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id 
                      WHERE a.user_id = ? 
                      ORDER BY a.time_in DESC";
        
        $stmt = $conn_qr->prepare($my_records);
        $stmt->bind_param("i", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Student</th><th>Time</th><th>Status</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['tbl_attendance_id'] . "</td>";
                echo "<td>" . $row['student_name'] . "</td>";
                echo "<td>" . $row['time_in'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No attendance records found for your user ID.</p>";
        }
    }
    
    echo "<p><a href='attendance_status.php'>Go to Attendance Status Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 