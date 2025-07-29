<?php
// Test script to check teacher schedule database setup
require_once 'conn/db_connect.php';

echo "<h2>Teacher Schedule Database Test</h2>";

try {
    // Check if teacher_schedules table exists
    $result = $conn_qr->query("SHOW TABLES LIKE 'teacher_schedules'");
    if ($result->num_rows > 0) {
        echo "✅ teacher_schedules table exists<br>";
        
        // Check table structure
        $result = $conn_qr->query("DESCRIBE teacher_schedules");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if there's any data
        $result = $conn_qr->query("SELECT COUNT(*) as count FROM teacher_schedules");
        $row = $result->fetch_assoc();
        echo "<br>📊 Total schedules in table: " . $row['count'] . "<br>";
        
    } else {
        echo "❌ teacher_schedules table does NOT exist<br>";
        echo "<p><a href='setup_teacher_schedule_db.php'>Click here to run the setup script</a></p>";
    }
    
    // Check if teacher_holidays table exists
    $result = $conn_qr->query("SHOW TABLES LIKE 'teacher_holidays'");
    if ($result->num_rows > 0) {
        echo "✅ teacher_holidays table exists<br>";
    } else {
        echo "❌ teacher_holidays table does NOT exist<br>";
    }
    
    // Check if teacher_schedule_logs table exists
    $result = $conn_qr->query("SHOW TABLES LIKE 'teacher_schedule_logs'");
    if ($result->num_rows > 0) {
        echo "✅ teacher_schedule_logs table exists<br>";
    } else {
        echo "❌ teacher_schedule_logs table does NOT exist<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><p><a href='teacher-schedule.php'>Go back to Teacher Schedule</a></p>";
?> 