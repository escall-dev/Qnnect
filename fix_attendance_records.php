<?php
// Fix existing attendance records
require_once 'conn/db_connect.php';

try {
    echo "Starting to fix attendance records...\n";
    
    // Fix user_id in tbl_attendance
    $query1 = "UPDATE tbl_attendance a 
                JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id 
                SET a.user_id = s.user_id 
                WHERE a.user_id IS NULL";
    
    $stmt1 = $conn_qr->prepare($query1);
    $result1 = $stmt1->execute();
    $affected1 = $stmt1->affected_rows;
    
    echo "Updated user_id for $affected1 records in tbl_attendance\n";
    
    // Fix school_id in tbl_attendance
    $query2 = "UPDATE tbl_attendance a 
                JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id 
                SET a.school_id = s.school_id 
                WHERE a.school_id IS NULL";
    
    $stmt2 = $conn_qr->prepare($query2);
    $result2 = $stmt2->execute();
    $affected2 = $stmt2->affected_rows;
    
    echo "Updated school_id for $affected2 records in tbl_attendance\n";
    
    // Fix school_id in attendance_logs
    $query3 = "UPDATE attendance_logs SET school_id = 1 WHERE school_id IS NULL";
    $stmt3 = $conn_qr->prepare($query3);
    $result3 = $stmt3->execute();
    $affected3 = $stmt3->affected_rows;
    
    echo "Updated school_id for $affected3 records in attendance_logs\n";
    
    // Verify the fixes
    $check1 = "SELECT COUNT(*) as total, 
                      COUNT(CASE WHEN user_id IS NULL THEN 1 END) as null_user_id,
                      COUNT(CASE WHEN school_id IS NULL THEN 1 END) as null_school_id
               FROM tbl_attendance";
    
    $stmt_check1 = $conn_qr->prepare($check1);
    $stmt_check1->execute();
    $result_check1 = $stmt_check1->get_result();
    $data1 = $result_check1->fetch_assoc();
    
    echo "\nVerification Results for tbl_attendance:\n";
    echo "Total records: " . $data1['total'] . "\n";
    echo "NULL user_id: " . $data1['null_user_id'] . "\n";
    echo "NULL school_id: " . $data1['null_school_id'] . "\n";
    
    echo "\nFix completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 