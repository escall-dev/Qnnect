<?php
// Web-based fix for attendance records
require_once 'conn/db_connect.php';

echo "<h2>Fixing Attendance Records</h2>";

try {
    echo "<p>Starting to fix attendance records...</p>";
    
    // Fix user_id in tbl_attendance
    $query1 = "UPDATE tbl_attendance a 
                JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id 
                SET a.user_id = s.user_id 
                WHERE a.user_id IS NULL";
    
    $stmt1 = $conn_qr->prepare($query1);
    $result1 = $stmt1->execute();
    $affected1 = $stmt1->affected_rows;
    
    echo "<p>✅ Updated user_id for $affected1 records in tbl_attendance</p>";
    
    // Fix school_id in tbl_attendance
    $query2 = "UPDATE tbl_attendance a 
                JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id 
                SET a.school_id = s.school_id 
                WHERE a.school_id IS NULL";
    
    $stmt2 = $conn_qr->prepare($query2);
    $result2 = $stmt2->execute();
    $affected2 = $stmt2->affected_rows;
    
    echo "<p>✅ Updated school_id for $affected2 records in tbl_attendance</p>";
    
    // Fix school_id in attendance_logs
    $query3 = "UPDATE attendance_logs SET school_id = 1 WHERE school_id IS NULL";
    $stmt3 = $conn_qr->prepare($query3);
    $result3 = $stmt3->execute();
    $affected3 = $stmt3->affected_rows;
    
    echo "<p>✅ Updated school_id for $affected3 records in attendance_logs</p>";
    
    // Verify the fixes
    $check1 = "SELECT COUNT(*) as total, 
                      COUNT(CASE WHEN user_id IS NULL THEN 1 END) as null_user_id,
                      COUNT(CASE WHEN school_id IS NULL THEN 1 END) as null_school_id
               FROM tbl_attendance";
    
    $stmt_check1 = $conn_qr->prepare($check1);
    $stmt_check1->execute();
    $result_check1 = $stmt_check1->get_result();
    $data1 = $result_check1->fetch_assoc();
    
    echo "<h3>Verification Results for tbl_attendance:</h3>";
    echo "<ul>";
    echo "<li>Total records: " . $data1['total'] . "</li>";
    echo "<li>NULL user_id: " . $data1['null_user_id'] . "</li>";
    echo "<li>NULL school_id: " . $data1['null_school_id'] . "</li>";
    echo "</ul>";
    
    if ($data1['null_user_id'] == 0 && $data1['null_school_id'] == 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ All attendance records have been fixed successfully!</p>";
        echo "<p><a href='attendance_status.php'>Go to Attendance Status Page</a></p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Some records still have NULL values. Please check manually.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 