<?php
// Test database connection and teacher_schedules table
include("conn/conn.php");
include("includes/session_config.php");

echo "<h2>Database Connection Test</h2>";

try {
    // Test basic connection
    echo "<p>✅ Database connection successful</p>";
    
    // Check if teacher_schedules table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'teacher_schedules'");
    if ($check_table->rowCount() > 0) {
        echo "<p>✅ teacher_schedules table exists</p>";
        
        // Check table structure
        $structure = $conn->query("DESCRIBE teacher_schedules");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for data
        $data_query = "SELECT COUNT(*) as count FROM teacher_schedules WHERE status = 'active'";
        $data_result = $conn->query($data_query);
        $count = $data_result->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p>Active teacher schedules: $count</p>";
        
        if ($count > 0) {
            // Show sample data
            $sample_query = "SELECT subject, section FROM teacher_schedules WHERE status = 'active' LIMIT 5";
            $sample_result = $conn->query($sample_query);
            
            echo "<h3>Sample Data:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>Subject</th><th>Section (Course-Section)</th></tr>";
            
            while ($row = $sample_result->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
                echo "<td>" . htmlspecialchars($row['section']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ No active teacher schedules found</p>";
        }
        
    } else {
        echo "<p>❌ teacher_schedules table does not exist</p>";
    }
    
    // Check tbl_student table
    $check_student = $conn->query("SHOW TABLES LIKE 'tbl_student'");
    if ($check_student->rowCount() > 0) {
        echo "<p>✅ tbl_student table exists</p>";
        
        // Check for existing course_section data
        $student_query = "SELECT COUNT(*) as count FROM tbl_student WHERE course_section IS NOT NULL AND course_section != ''";
        $student_result = $conn->query($student_query);
        $student_count = $student_result->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p>Students with course_section data: $student_count</p>";
        
        if ($student_count > 0) {
            $sample_student = "SELECT student_name, course_section FROM tbl_student WHERE course_section IS NOT NULL AND course_section != '' LIMIT 5";
            $sample_student_result = $conn->query($sample_student);
            
            echo "<h3>Sample Student Data:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>Student Name</th><th>Course & Section</th></tr>";
            
            while ($row = $sample_student_result->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['course_section']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>❌ tbl_student table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?> 