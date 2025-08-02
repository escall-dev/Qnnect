<?php
// Script to reset class time and clear cached data
session_start();

echo "<h2>Reset Class Time</h2>";

// Clear session variables
echo "<h3>Clearing Session Variables:</h3>";
$session_vars_to_clear = [
    'class_start_time',
    'class_start_time_formatted',
    'schedule_start_time'
];

foreach ($session_vars_to_clear as $var) {
    if (isset($_SESSION[$var])) {
        echo "<p>Clearing: $_SESSION[$var]</p>";
        unset($_SESSION[$var]);
    } else {
        echo "<p>Variable '$var' not set in session</p>";
    }
}

// Clear database class time settings
echo "<h3>Clearing Database Class Time Settings:</h3>";
try {
    require_once('conn/db_connect.php');
    
    if (isset($conn_qr)) {
        $school_id = $_SESSION['school_id'] ?? 1;
        
        // Delete existing class time settings
        $deleteQuery = "DELETE FROM class_time_settings WHERE school_id = ?";
        $stmt = $conn_qr->prepare($deleteQuery);
        $stmt->bind_param("i", $school_id);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Cleared class time settings from database</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to clear database settings</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Database connection not available</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Set a default class time
echo "<h3>Setting Default Class Time:</h3>";
$_SESSION['class_start_time'] = '08:00';
$_SESSION['class_start_time_formatted'] = '08:00:00';

echo "<p style='color: green;'>✅ Set default class time to 08:00 (8:00 AM)</p>";

// Test the time comparison logic
echo "<h3>Testing Time Comparison Logic:</h3>";
$today = date('Y-m-d');
$test_class_time = '22:00:00'; // 10:00 PM
$test_scan_time = '21:57:30';  // 09:57:30 PM

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

echo "<p style='color: $color;'>Test Case: Scan at 09:57:30 PM for class at 10:00 PM</p>";
echo "<ul>";
echo "<li>Class Time: " . $class_start_datetime->format('h:i A') . "</li>";
echo "<li>Scan Time: " . $time_in_datetime->format('h:i A') . "</li>";
echo "<li>Time Difference: $minutesDifference minutes</li>";
echo "<li>Status: $status</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Go back to the main page</li>";
echo "<li>Set your class time to 10:00 PM (22:00)</li>";
echo "<li>Test with a fresh QR scan</li>";
echo "<li>The student should now be marked as 'On Time' if they scan before 10:00 PM</li>";
echo "</ol>";

echo "<p><a href='index.php' class='btn btn-primary'>Go to Main Page</a></p>";
?> 