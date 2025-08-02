<?php
// Debug current session and database status
session_start();

echo "<h2>Current Status Debug</h2>";

// Check session variables
echo "<h3>Session Variables:</h3>";
echo "<p>class_start_time: " . ($_SESSION['class_start_time'] ?? 'Not set') . "</p>";
echo "<p>class_start_time_formatted: " . ($_SESSION['class_start_time_formatted'] ?? 'Not set') . "</p>";
echo "<p>attendance_mode: " . ($_SESSION['attendance_mode'] ?? 'Not set') . "</p>";

// Check database for recent attendance records
try {
    require_once('conn/db_connect.php');
    
    if (isset($conn_qr)) {
        $school_id = $_SESSION['school_id'] ?? 1;
        
        echo "<h3>Recent Attendance Records:</h3>";
        $query = "SELECT id, time_in, status FROM tbl_attendance 
                  WHERE DATE(time_in) = CURDATE() 
                  AND school_id = ? 
                  ORDER BY time_in DESC 
                  LIMIT 5";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th>";
        echo "<th>Time In</th>";
        echo "<th>Status</th>";
        echo "<th>Should Be</th>";
        echo "</tr>";
        
        $class_time = '23:40:00'; // 11:40 PM
        $today = date('Y-m-d');
        
        while ($row = $result->fetch_assoc()) {
            $record_id = $row['id'];
            $time_in = $row['time_in'];
            $current_status = $row['status'];
            
            // Calculate what it should be
            $time_in_datetime = new DateTime($time_in);
            $class_start_datetime = new DateTime($today . ' ' . $class_time);
            
            if ($time_in_datetime->getTimestamp() <= $class_start_datetime->getTimestamp()) {
                $should_be = 'On Time';
                $should_be_color = 'green';
            } else {
                $should_be = 'Late';
                $should_be_color = 'red';
            }
            
            $needs_fix = ($current_status !== $should_be);
            $row_color = $needs_fix ? '#ffe6e6' : '#e6ffe6';
            
            echo "<tr style='background-color: $row_color;'>";
            echo "<td>$record_id</td>";
            echo "<td>" . date('h:i A', strtotime($time_in)) . "</td>";
            echo "<td>$current_status</td>";
            echo "<td style='color: $should_be_color;'>$should_be</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check class time settings in database
        echo "<h3>Database Class Time Settings:</h3>";
        $class_time_query = "SELECT * FROM class_time_settings WHERE school_id = ?";
        $stmt = $conn_qr->prepare($class_time_query);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<p>Database Class Time: " . $row['start_time'] . " (Updated: " . $row['updated_at'] . ")</p>";
            }
        } else {
            echo "<p>No class time settings found in database.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database connection not available</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test the current logic
echo "<h3>Logic Test:</h3>";
$test_class_time = '23:40:00'; // 11:40 PM
$test_scan_time = '22:37:32';  // 10:37:32 PM

$class_start_datetime = new DateTime($today . ' ' . $test_class_time);
$time_in_datetime = new DateTime($today . ' ' . $test_scan_time);

$timeDifference = $time_in_datetime->getTimestamp() - $class_start_datetime->getTimestamp();
$minutesDifference = round($timeDifference / 60);

if ($time_in_datetime->getTimestamp() <= $class_start_datetime->getTimestamp()) {
    $status = 'On Time';
    $color = 'green';
} else {
    $status = 'Late';
    $color = 'red';
}

echo "<p style='color: $color;'>Test: Scan at 10:37:32 PM for class at 11:40 PM = $status</p>";
echo "<ul>";
echo "<li>Class Time: " . $class_start_datetime->format('h:i A') . "</li>";
echo "<li>Scan Time: " . $time_in_datetime->format('h:i A') . "</li>";
echo "<li>Time Difference: $minutesDifference minutes</li>";
echo "<li>Status: $status</li>";
echo "</ul>";

echo "<h3>Recommendation:</h3>";
echo "<p>The fix has been updated to use 11:40 PM as the class time.</p>";
echo "<p>All scans before 11:40 PM should now be marked as 'On Time'.</p>";
echo "<p>Test with a new QR scan to verify the fix works.</p>";

echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Main Page</a></p>";
?> 