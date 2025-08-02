<?php
/**
 * Data Isolation Test Script
 * 
 * This script tests the data isolation implementation to ensure
 * that users can only access their own school's data.
 */

require_once 'includes/data_isolation_helper.php';
include('./conn/db_connect.php');

// Start session for testing
session_start();

echo "<h1>Data Isolation Test Results</h1>";

// Test 1: Check if helper functions are available
echo "<h2>Test 1: Helper Functions</h2>";
if (function_exists('getCurrentUserContext')) {
    echo "✓ getCurrentUserContext() function exists<br>";
} else {
    echo "✗ getCurrentUserContext() function missing<br>";
}

if (function_exists('addDataIsolationFilters')) {
    echo "✓ addDataIsolationFilters() function exists<br>";
} else {
    echo "✗ addDataIsolationFilters() function missing<br>";
}

// Test 2: Check user context
echo "<h2>Test 2: User Context</h2>";
$context = getCurrentUserContext();
echo "School ID: " . $context['school_id'] . "<br>";
echo "User ID: " . ($context['user_id'] ?? 'NULL') . "<br>";

// Test 3: Test attendance isolation
echo "<h2>Test 3: Attendance Data Isolation</h2>";
try {
    $query = "SELECT COUNT(*) as total FROM tbl_attendance 
              WHERE school_id = {$context['school_id']}
              " . ($context['user_id'] ? "AND (user_id = {$context['user_id']} OR user_id IS NULL)" : "");
    
    $result = $conn_qr->query($query);
    $row = $result->fetch_assoc();
    echo "Total attendance records for this school: " . $row['total'] . "<br>";
    
    // Compare with total records
    $total_query = "SELECT COUNT(*) as total FROM tbl_attendance";
    $total_result = $conn_qr->query($total_query);
    $total_row = $total_result->fetch_assoc();
    echo "Total attendance records in database: " . $total_row['total'] . "<br>";
    
    if ($row['total'] < $total_row['total']) {
        echo "✓ Data isolation is working - filtered records<br>";
    } else {
        echo "⚠ All records visible - check isolation filters<br>";
    }
} catch (Exception $e) {
    echo "✗ Error testing attendance isolation: " . $e->getMessage() . "<br>";
}

// Test 4: Test student isolation
echo "<h2>Test 4: Student Data Isolation</h2>";
try {
    $query = "SELECT COUNT(*) as total FROM tbl_student 
              WHERE school_id = {$context['school_id']}
              " . ($context['user_id'] ? "AND (user_id = {$context['user_id']} OR user_id IS NULL)" : "");
    
    $result = $conn_qr->query($query);
    $row = $result->fetch_assoc();
    echo "Total students for this school: " . $row['total'] . "<br>";
    
    // Compare with total records
    $total_query = "SELECT COUNT(*) as total FROM tbl_student";
    $total_result = $conn_qr->query($total_query);
    $total_row = $total_result->fetch_assoc();
    echo "Total students in database: " . $total_row['total'] . "<br>";
    
    if ($row['total'] < $total_row['total']) {
        echo "✓ Student data isolation is working<br>";
    } else {
        echo "⚠ All students visible - check isolation filters<br>";
    }
} catch (Exception $e) {
    echo "✗ Error testing student isolation: " . $e->getMessage() . "<br>";
}

// Test 5: Test activity logs isolation
echo "<h2>Test 5: Activity Logs Isolation</h2>";
try {
    $query = "SELECT COUNT(*) as total FROM activity_logs 
              WHERE school_id = {$context['school_id']}
              " . ($context['user_id'] ? "AND (user_id = {$context['user_id']} OR user_id IS NULL)" : "");
    
    $result = $conn_qr->query($query);
    $row = $result->fetch_assoc();
    echo "Total activity logs for this school: " . $row['total'] . "<br>";
    
    // Compare with total records
    $total_query = "SELECT COUNT(*) as total FROM activity_logs";
    $total_result = $conn_qr->query($total_query);
    $total_row = $total_result->fetch_assoc();
    echo "Total activity logs in database: " . $total_row['total'] . "<br>";
    
    if ($row['total'] < $total_row['total']) {
        echo "✓ Activity logs isolation is working<br>";
    } else {
        echo "⚠ All activity logs visible - check isolation filters<br>";
    }
} catch (Exception $e) {
    echo "✗ Error testing activity logs isolation: " . $e->getMessage() . "<br>";
}

// Test 6: Test verification logs isolation
echo "<h2>Test 6: Verification Logs Isolation</h2>";
try {
    $query = "SELECT COUNT(*) as total FROM tbl_face_verification_logs 
              WHERE school_id = {$context['school_id']}";
    
    $result = $conn_qr->query($query);
    $row = $result->fetch_assoc();
    echo "Total verification logs for this school: " . $row['total'] . "<br>";
    
    // Compare with total records
    $total_query = "SELECT COUNT(*) as total FROM tbl_face_verification_logs";
    $total_result = $conn_qr->query($total_query);
    $total_row = $total_result->fetch_assoc();
    echo "Total verification logs in database: " . $total_row['total'] . "<br>";
    
    if ($row['total'] < $total_row['total']) {
        echo "✓ Verification logs isolation is working<br>";
    } else {
        echo "⚠ All verification logs visible - check isolation filters<br>";
    }
} catch (Exception $e) {
    echo "✗ Error testing verification logs isolation: " . $e->getMessage() . "<br>";
}

// Test 7: Check database schema
echo "<h2>Test 7: Database Schema Check</h2>";
$tables_to_check = [
    'tbl_attendance' => ['school_id', 'user_id'],
    'tbl_student' => ['school_id', 'user_id'],
    'tbl_instructors' => ['school_id', 'user_id'],
    'teacher_schedules' => ['school_id', 'user_id'],
    'activity_logs' => ['school_id', 'user_id'],
    'tbl_face_verification_logs' => ['school_id'],
    'tbl_face_recognition_logs' => ['school_id']
];

foreach ($tables_to_check as $table => $columns) {
    try {
        $describe_query = "DESCRIBE $table";
        $describe_result = $conn_qr->query($describe_query);
        $table_columns = [];
        
        while ($row = $describe_result->fetch_assoc()) {
            $table_columns[] = $row['Field'];
        }
        
        $missing_columns = [];
        foreach ($columns as $column) {
            if (!in_array($column, $table_columns)) {
                $missing_columns[] = $column;
            }
        }
        
        if (empty($missing_columns)) {
            echo "✓ Table '$table' has all required isolation columns<br>";
        } else {
            echo "✗ Table '$table' missing columns: " . implode(', ', $missing_columns) . "<br>";
        }
    } catch (Exception $e) {
        echo "✗ Error checking table '$table': " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Test Summary</h2>";
echo "This test script verifies that data isolation is properly implemented.<br>";
echo "If you see mostly ✓ marks, the isolation is working correctly.<br>";
echo "If you see ✗ marks, there may be issues that need to be addressed.<br>";

echo "<h3>Recommendations:</h3>";
echo "1. Ensure all users are properly assigned to schools<br>";
echo "2. Verify session variables are set correctly<br>";
echo "3. Check that database tables have the required isolation columns<br>";
echo "4. Test with multiple schools to ensure complete isolation<br>";
?> 