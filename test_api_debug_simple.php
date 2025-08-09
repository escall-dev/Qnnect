<?php
// Simple debug test for API
echo "<h2>API Debug Test</h2>";

// Start session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['school_id'] = 1;
$_SESSION['email'] = 'test@example.com';

echo "<p>Session started</p>";

// Test includes
echo "<p>Testing includes...</p>";

try {
    require_once 'includes/session_config.php';
    echo "<p>✅ session_config.php loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ session_config.php error: " . $e->getMessage() . "</p>";
}

try {
    require_once 'conn/db_connect.php';
    echo "<p>✅ db_connect.php loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ db_connect.php error: " . $e->getMessage() . "</p>";
}

// Test database connection
if (isset($conn_qr)) {
    echo "<p>✅ \$conn_qr is available</p>";
    
    // Test simple query
    $test_query = "SELECT 1 as test";
    $result = mysqli_query($conn_qr, $test_query);
    if ($result) {
        echo "<p>✅ Database query successful</p>";
    } else {
        echo "<p>❌ Database query failed: " . mysqli_error($conn_qr) . "</p>";
    }
} else {
    echo "<p>❌ \$conn_qr is not available</p>";
}

// Test teacher_schedules table
if (isset($conn_qr)) {
    $check_table = "SHOW TABLES LIKE 'teacher_schedules'";
    $result = mysqli_query($conn_qr, $check_table);
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<p>✅ teacher_schedules table exists</p>";
        
        // Test actual query
        $test_query = "SELECT COUNT(*) as count FROM teacher_schedules WHERE status = 'active'";
        $result = mysqli_query($conn_qr, $test_query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "<p>Active teacher schedules: " . $row['count'] . "</p>";
        }
    } else {
        echo "<p>❌ teacher_schedules table does not exist</p>";
    }
}

echo "<h3>Next Steps:</h3>";
echo "<p>If all tests pass above, try the API:</p>";
echo "<p><a href='api/get-teacher-course-sections.php' target='_blank'>api/get-teacher-course-sections.php</a></p>";
?> 