<?php
// Check database structure for attendance table
session_start();

echo "<h2>Database Structure Check</h2>";

try {
    require_once('conn/db_connect.php');
    
    if (isset($conn_qr)) {
        $school_id = $_SESSION['school_id'] ?? 1;
        
        // Check table structure
        echo "<h3>Table Structure:</h3>";
        $structure_query = "DESCRIBE tbl_attendance";
        $result = $conn_qr->query($structure_query);
        
        if ($result) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>Field</th>";
            echo "<th>Type</th>";
            echo "<th>Null</th>";
            echo "<th>Key</th>";
            echo "<th>Default</th>";
            echo "<th>Extra</th>";
            echo "</tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check sample data
        echo "<h3>Sample Data:</h3>";
        $sample_query = "SELECT * FROM tbl_attendance WHERE DATE(time_in) = CURDATE() AND school_id = ? LIMIT 5";
        $stmt = $conn_qr->prepare($sample_query);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>ID</th>";
            echo "<th>Student ID</th>";
            echo "<th>Time In</th>";
            echo "<th>Status</th>";
            echo "<th>School ID</th>";
            echo "</tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['tbl_student_id'] . "</td>";
                echo "<td>" . $row['time_in'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>" . $row['school_id'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No attendance records found for today.</p>";
        }
        
        // Check class time settings
        echo "<h3>Class Time Settings:</h3>";
        $class_time_query = "SELECT * FROM class_time_settings WHERE school_id = ?";
        $stmt = $conn_qr->prepare($class_time_query);
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<p>Class Time: " . $row['start_time'] . " (Updated: " . $row['updated_at'] . ")</p>";
            }
        } else {
            echo "<p>No class time settings found for this school.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database connection not available</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>Session Variables:</h3>";
echo "<p>class_start_time: " . ($_SESSION['class_start_time'] ?? 'Not set') . "</p>";
echo "<p>class_start_time_formatted: " . ($_SESSION['class_start_time_formatted'] ?? 'Not set') . "</p>";

echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Main Page</a></p>";
?> 