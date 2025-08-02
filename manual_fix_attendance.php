<?php
// Manual fix for existing attendance records
session_start();

echo "<h2>Manual Attendance Fix</h2>";

// Get current class time
$class_time = $_SESSION['class_start_time_formatted'] ?? '22:30:00'; // Default to 10:30 PM
echo "<p><strong>Current Class Time:</strong> $class_time</p>";

try {
    require_once('conn/db_connect.php');
    
    if (isset($conn_qr)) {
        $school_id = $_SESSION['school_id'] ?? 1;
        $today = date('Y-m-d');
        
        // Get all today's attendance records
        $query = "SELECT id, time_in, status FROM tbl_attendance 
                  WHERE DATE(time_in) = CURDATE() 
                  AND school_id = ? 
                  ORDER BY time_in DESC";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<h3>Today's Attendance Records:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th>";
        echo "<th>Time In</th>";
        echo "<th>Current Status</th>";
        echo "<th>Should Be</th>";
        echo "<th>Action</th>";
        echo "</tr>";
        
        $fixed_count = 0;
        $total_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $total_count++;
            $record_id = $row['id'];
            $time_in = $row['time_in'];
            $current_status = $row['status'];
            
            // Calculate what the status should be
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
            
            if ($needs_fix) {
                echo "<td><a href='?fix=$record_id&status=$should_be' style='color: blue;'>Fix</a></td>";
            } else {
                echo "<td>✓ Correct</td>";
            }
            echo "</tr>";
            
            // Handle the fix action
            if (isset($_GET['fix']) && $_GET['fix'] == $record_id) {
                $new_status = $_GET['status'];
                $update_query = "UPDATE tbl_attendance SET status = ? WHERE id = ?";
                $update_stmt = $conn_qr->prepare($update_query);
                $update_stmt->bind_param("si", $new_status, $record_id);
                
                if ($update_stmt->execute()) {
                    $fixed_count++;
                    echo "<script>alert('Fixed record #$record_id: $current_status → $new_status');</script>";
                } else {
                    echo "<script>alert('Failed to fix record #$record_id');</script>";
                }
                
                // Redirect to refresh the page
                echo "<script>window.location.href='manual_fix_attendance.php';</script>";
                exit;
            }
        }
        
        echo "</table>";
        
        echo "<h3>Summary:</h3>";
        echo "<p>Total records: $total_count</p>";
        echo "<p>Fixed records: $fixed_count</p>";
        
        if ($fixed_count > 0) {
            echo "<p style='color: green;'>✅ Successfully fixed $fixed_count records!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database connection not available</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Review the table above</li>";
echo "<li>Click 'Fix' on any records that show incorrect status</li>";
echo "<li>The system will automatically update the status</li>";
echo "<li>After fixing, test with a new QR scan</li>";
echo "</ol>";

echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Main Page</a></p>";
?> 