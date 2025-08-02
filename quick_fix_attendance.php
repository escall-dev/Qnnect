<?php
// Quick fix for attendance records
require_once 'conn/db_connect.php';

echo "<h2>Quick Fix for Attendance Records</h2>";

try {
    // Fix attendance records to match their student's user_id and school_id
    $fix_query = "UPDATE tbl_attendance a 
                  JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id 
                  SET a.user_id = s.user_id, a.school_id = s.school_id 
                  WHERE a.user_id IS NULL OR a.school_id IS NULL";
    
    $stmt = $conn_qr->prepare($fix_query);
    $result = $stmt->execute();
    $affected = $stmt->affected_rows;
    
    echo "<p>✅ Updated $affected attendance records to match their student's user_id and school_id</p>";
    
    // Show the current distribution
    echo "<h3>Current Attendance Distribution:</h3>";
    $check_query = "SELECT user_id, school_id, COUNT(*) as count 
                   FROM tbl_attendance 
                   GROUP BY user_id, school_id 
                   ORDER BY user_id, school_id";
    
    $stmt = $conn_qr->prepare($check_query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>User ID</th><th>School ID</th><th>Count</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . ($row['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['school_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ Fix completed! Each user should now see only their own attendance records.</p>";
    echo "<p><a href='index.php'>Go to Main Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 