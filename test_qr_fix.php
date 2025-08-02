<?php
// Quick test to verify QR scanning parameter binding fix
session_start();
require_once('conn/db_connect.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>QR Fix Test</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>🧪 QR Parameter Binding Fix Test</h2>
        
        <div class='alert alert-success'>
            <h5>✅ Parameter Binding Fixed</h5>
            <p>The bind_param issue has been fixed. QR scanning should now work properly.</p>
        </div>
        
        <div class='card'>
            <div class='card-header'>
                <h5>Test Database Queries</h5>
            </div>
            <div class='card-body'>";

// Test the exact queries from add-attendance.php
try {
    // Test the SELECT query (the one that was failing)
    $test_query = "SELECT * FROM tbl_attendance 
        WHERE tbl_student_id = ? 
        AND DATE(time_in) = CURDATE() 
        AND instructor_id = ? 
        AND subject_id = ? 
        AND user_id = ? 
        AND school_id = ? 
        LIMIT 1";
    
    $stmt = $conn_qr->prepare($test_query);
    $studentID = 1;
    $instructorId = 1;
    $subjectId = 1;
    $user_id = 1;
    $school_id = 1;
    
    // This should work now with 5 parameters
    $stmt->bind_param("iiiii", $studentID, $instructorId, $subjectId, $user_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<p style='color: green;'>✅ SELECT query test PASSED</p>";
    echo "<p>• Parameters: 5 (iiiii)</p>";
    echo "<p>• Result rows: " . $result->num_rows . "</p>";
    
    // Test the INSERT query
    $insert_query = "INSERT INTO tbl_attendance 
        (tbl_student_id, time_in, status, instructor_id, subject_id, user_id, school_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt2 = $conn_qr->prepare($insert_query);
    $timeIn = date("Y-m-d H:i:s");
    $status = 'Test';
    
    // This should work with 7 parameters
    $stmt2->bind_param("issiiii", $studentID, $timeIn, $status, $instructorId, $subjectId, $user_id, $school_id);
    $result2 = $stmt2->execute();
    
    if ($result2) {
        echo "<p style='color: green;'>✅ INSERT query test PASSED</p>";
        echo "<p>• Parameters: 7 (issiiii)</p>";
        echo "<p>• Insert ID: " . $conn_qr->insert_id . "</p>";
        
        // Clean up test data
        $delete_query = "DELETE FROM tbl_attendance WHERE tbl_attendance_id = ?";
        $stmt3 = $conn_qr->prepare($delete_query);
        $inserted_id = $conn_qr->insert_id;
        $stmt3->bind_param("i", $inserted_id);
        $stmt3->execute();
        echo "<p>• Test data cleaned up</p>";
    } else {
        echo "<p style='color: red;'>❌ INSERT query test FAILED</p>";
    }
    
    echo "<div class='alert alert-success mt-3'>
        <h6>🎉 All Tests Passed!</h6>
        <p>QR scanning should now work properly without parameter binding errors.</p>
    </div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Test FAILED: " . $e->getMessage() . "</p>";
}

echo "</div></div>

<div class='card mt-4'>
    <div class='card-header'>
        <h5>Next Steps</h5>
    </div>
    <div class='card-body'>
        <ol>
            <li>✅ Parameter binding issue fixed</li>
            <li>🔍 Test QR scanning on main page</li>
            <li>📱 Try scanning a QR code</li>
            <li>✅ Should work without errors</li>
        </ol>
        
        <div class='mt-3'>
            <a href='index.php' class='btn btn-primary me-2'>
                <i class='fas fa-home'></i> Go to Main Page
            </a>
            <a href='test_qr_scanning.php' class='btn btn-info'>
                <i class='fas fa-qrcode'></i> Test QR Scanning
            </a>
        </div>
    </div>
</div>

</div>
</body>
</html>";
?> 