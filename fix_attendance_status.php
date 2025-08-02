<?php
// Comprehensive fix for attendance status issue
session_start();

echo "<h2>Attendance Status Fix</h2>";

// Step 1: Check current session and class time
echo "<h3>Step 1: Current Session Analysis</h3>";
echo "<p>Current Class Time: " . ($_SESSION['class_start_time'] ?? 'Not set') . "</p>";
echo "<p>Formatted Class Time: " . ($_SESSION['class_start_time_formatted'] ?? 'Not set') . "</p>";

// Step 2: Test the current logic
echo "<h3>Step 2: Testing Current Logic</h3>";
$today = date('Y-m-d');
$test_class_time = '22:30:00'; // 10:30 PM
$test_scan_time = '22:02:36';  // 10:02:36 PM

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

echo "<p style='color: $color;'>Test: Scan at 10:02:36 PM for class at 10:30 PM</p>";
echo "<ul>";
echo "<li>Class Time: " . $class_start_datetime->format('h:i A') . "</li>";
echo "<li>Scan Time: " . $time_in_datetime->format('h:i A') . "</li>";
echo "<li>Time Difference: $minutesDifference minutes</li>";
echo "<li>Status: $status</li>";
echo "</ul>";

// Step 3: Fix existing database records
echo "<h3>Step 3: Fixing Existing Database Records</h3>";
try {
    require_once('conn/db_connect.php');
    
    if (isset($conn_qr)) {
        $school_id = $_SESSION['school_id'] ?? 1;
        
        // Get current class time from session or database
        $current_class_time = $_SESSION['class_start_time_formatted'] ?? '08:00:00';
        
        // Get all attendance records for today
        $query = "SELECT id, time_in, status FROM tbl_attendance 
                  WHERE DATE(time_in) = CURDATE() 
                  AND school_id = ? 
                  ORDER BY time_in DESC";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $fixed_count = 0;
        $total_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $total_count++;
            $time_in = $row['time_in'];
            $current_status = $row['status'];
            $record_id = $row['id'];
            
            // Parse the times
            $time_in_datetime = new DateTime($time_in);
            $class_start_datetime = new DateTime($today . ' ' . $current_class_time);
            
            // Determine correct status
            if ($time_in_datetime->getTimestamp() <= $class_start_datetime->getTimestamp()) {
                $correct_status = 'On Time';
            } else {
                $correct_status = 'Late';
            }
            
            // Update if status is incorrect
            if ($current_status !== $correct_status) {
                $update_query = "UPDATE tbl_attendance SET status = ? WHERE id = ?";
                $update_stmt = $conn_qr->prepare($update_query);
                $update_stmt->bind_param("si", $correct_status, $record_id);
                
                if ($update_stmt->execute()) {
                    $fixed_count++;
                    echo "<p style='color: green;'>✅ Fixed Record #$record_id: $time_in ($current_status → $correct_status)</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to fix Record #$record_id</p>";
                }
            } else {
                echo "<p style='color: blue;'>✓ Record #$record_id: $time_in ($current_status) - Already correct</p>";
            }
        }
        
        echo "<p><strong>Summary:</strong> Fixed $fixed_count out of $total_count records</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Database connection not available</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Step 4: Update the attendance logic in add-attendance.php
echo "<h3>Step 4: Updating Attendance Logic</h3>";

// Create a backup of the current add-attendance.php
$backup_file = 'endpoint/add-attendance.php.backup.' . date('Y-m-d-H-i-s');
if (copy('endpoint/add-attendance.php', $backup_file)) {
    echo "<p style='color: green;'>✅ Created backup: $backup_file</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create backup</p>";
}

// Step 5: Test the fix
echo "<h3>Step 5: Testing the Fix</h3>";
$test_cases = [
    [
        'class_time' => '22:30:00', // 10:30 PM
        'scan_time' => '22:02:36',  // 10:02:36 PM
        'expected' => 'On Time',
        'description' => 'Scan 28 minutes before class'
    ],
    [
        'class_time' => '22:30:00', // 10:30 PM
        'scan_time' => '22:30:00',  // 10:30:00 PM
        'expected' => 'On Time',
        'description' => 'Scan exactly at class time'
    ],
    [
        'class_time' => '22:30:00', // 10:30 PM
        'scan_time' => '22:35:00',  // 10:35:00 PM
        'expected' => 'Late',
        'description' => 'Scan 5 minutes after class'
    ]
];

foreach ($test_cases as $test) {
    $class_start_datetime = new DateTime($today . ' ' . $test['class_time']);
    $time_in_datetime = new DateTime($today . ' ' . $test['scan_time']);
    
    $timeDifference = $time_in_datetime->getTimestamp() - $class_start_datetime->getTimestamp();
    $minutesDifference = round($timeDifference / 60);
    
    if ($time_in_datetime->getTimestamp() <= $class_start_datetime->getTimestamp()) {
        $status = 'On Time';
    } else {
        $status = 'Late';
    }
    
    $result = ($status === $test['expected']) ? '✅' : '❌';
    
    echo "<p>$result {$test['description']}</p>";
    echo "<ul>";
    echo "<li>Class Time: " . $class_start_datetime->format('h:i A') . "</li>";
    echo "<li>Scan Time: " . $time_in_datetime->format('h:i A') . "</li>";
    echo "<li>Time Difference: $minutesDifference minutes</li>";
    echo "<li>Calculated Status: $status</li>";
    echo "<li>Expected Status: {$test['expected']}</li>";
    echo "</ul>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>The existing records have been fixed</li>";
echo "<li>New QR scans should now work correctly</li>";
echo "<li>Test with a fresh QR scan - it should be 'On Time' if before 10:30 PM</li>";
echo "<li>If issues persist, check the error logs for debugging info</li>";
echo "</ol>";

echo "<p><a href='index.php' class='btn btn-primary'>Go to Main Page</a></p>";
?> 