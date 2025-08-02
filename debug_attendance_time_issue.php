<?php
// Debug script to identify attendance time comparison issue
session_start();

echo "<h2>Attendance Time Comparison Debug</h2>";

// Check session variables
echo "<h3>Session Variables:</h3>";
echo "<p>class_start_time: " . ($_SESSION['class_start_time'] ?? 'Not set') . "</p>";
echo "<p>class_start_time_formatted: " . ($_SESSION['class_start_time_formatted'] ?? 'Not set') . "</p>";
echo "<p>attendance_mode: " . ($_SESSION['attendance_mode'] ?? 'Not set') . "</p>";

// Simulate the exact logic from add-attendance.php
$attendanceMode = $_SESSION['attendance_mode'] ?? 'general';

// Get class time based on attendance mode
if ($attendanceMode === 'room_subject' && isset($_SESSION['schedule_start_time'])) {
    $class_start_time = $_SESSION['schedule_start_time'];
} else {
    $class_start_time = isset($_SESSION['class_start_time_formatted']) 
        ? $_SESSION['class_start_time_formatted'] 
        : (isset($_SESSION['class_start_time']) ? $_SESSION['class_start_time'] : '08:00:00');
}

echo "<h3>Retrieved Class Time:</h3>";
echo "<p>Raw class_start_time: '$class_start_time'</p>";

// Make sure class time is properly formatted no matter what
if (strlen($class_start_time) == 5) {
    $class_start_time .= ':00';
}

echo "<p>After formatting: '$class_start_time'</p>";

// Test with the specific times from your example
$today = date('Y-m-d');
$test_scan_time = '2025-08-01 21:57:30'; // 09:57:30 PM
$test_class_time = '22:00:00'; // 10:00 PM

echo "<h3>Test Comparison:</h3>";
echo "<p>Today: $today</p>";
echo "<p>Test Scan Time: $test_scan_time</p>";
echo "<p>Test Class Time: $test_class_time</p>";

// Create DateTime objects
$class_start_datetime = new DateTime($today . ' ' . $test_class_time);
$time_in_datetime = new DateTime($test_scan_time);

echo "<p>Class Start DateTime: " . $class_start_datetime->format('Y-m-d H:i:s') . "</p>";
echo "<p>Time In DateTime: " . $time_in_datetime->format('Y-m-d H:i:s') . "</p>";

// Compare timestamps
$timeDifference = $time_in_datetime->getTimestamp() - $class_start_datetime->getTimestamp();
$minutesDifference = round($timeDifference / 60);

echo "<p>Time Difference: $timeDifference seconds ($minutesDifference minutes)</p>";

if ($time_in_datetime->getTimestamp() <= $class_start_datetime->getTimestamp()) {
    $status = 'On Time';
    echo "<p style='color: green;'>✅ STATUS: ON TIME - Student arrived " . abs($minutesDifference) . " minutes before class starts</p>";
} else {
    $status = 'Late';
    echo "<p style='color: red;'>❌ STATUS: LATE - Student arrived $minutesDifference minutes after class starts</p>";
}

// Test with actual session values
echo "<h3>Actual Session Values Test:</h3>";
if (isset($_SESSION['class_start_time'])) {
    $actual_class_time = $_SESSION['class_start_time'];
    if (strlen($actual_class_time) == 5) {
        $actual_class_time .= ':00';
    }
    
    echo "<p>Actual Class Time from Session: '$actual_class_time'</p>";
    
    $actual_class_datetime = new DateTime($today . ' ' . $actual_class_time);
    $actual_time_in_datetime = new DateTime($test_scan_time);
    
    echo "<p>Actual Class DateTime: " . $actual_class_datetime->format('Y-m-d H:i:s') . "</p>";
    echo "<p>Actual Time In DateTime: " . $actual_time_in_datetime->format('Y-m-d H:i:s') . "</p>";
    
    $actualTimeDifference = $actual_time_in_datetime->getTimestamp() - $actual_class_datetime->getTimestamp();
    $actualMinutesDifference = round($actualTimeDifference / 60);
    
    echo "<p>Actual Time Difference: $actualTimeDifference seconds ($actualMinutesDifference minutes)</p>";
    
    if ($actual_time_in_datetime->getTimestamp() <= $actual_class_datetime->getTimestamp()) {
        $actualStatus = 'On Time';
        echo "<p style='color: green;'>✅ ACTUAL STATUS: ON TIME</p>";
    } else {
        $actualStatus = 'Late';
        echo "<p style='color: red;'>❌ ACTUAL STATUS: LATE</p>";
    }
} else {
    echo "<p>No class_start_time in session</p>";
}

// Check if there's a timezone issue
echo "<h3>Timezone Information:</h3>";
echo "<p>Current timezone: " . date_default_timezone_get() . "</p>";
echo "<p>Current server time: " . date('Y-m-d H:i:s') . "</p>";

// Test with different time formats
echo "<h3>Time Format Tests:</h3>";
$testTimes = [
    '22:00' => '22:00:00',
    '22:00:00' => '22:00:00',
    '10:00 PM' => '22:00:00',
    '10:00:00 PM' => '22:00:00'
];

foreach ($testTimes as $input => $expected) {
    $date = DateTime::createFromFormat('H:i', $input);
    if ($date) {
        $formatted = $date->format('H:i:s');
        $status = ($formatted === $expected) ? '✅' : '❌';
        echo "<p>$status Input: '$input' → Output: '$formatted' (Expected: '$expected')</p>";
    } else {
        echo "<p>❌ Input: '$input' → Failed to parse</p>";
    }
}

echo "<h3>Recommendation:</h3>";
echo "<p>Based on this debug, the issue is likely:</p>";
echo "<ol>";
echo "<li>Class time stored in wrong format in session</li>";
echo "<li>Timezone mismatch</li>";
echo "<li>Time comparison logic error</li>";
echo "</ol>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Check the error logs for the actual values being compared</li>";
echo "<li>Verify the class time is stored correctly in the database</li>";
echo "<li>Test with a fresh class time setting</li>";
echo "</ol>";
?> 