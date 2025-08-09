<?php
// Simple test for the API with proper session data
echo "<h2>API Test with Session Data</h2>";

// Start session and set test data
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['school_id'] = 1;
$_SESSION['email'] = 'test@example.com';

echo "<p>Session data set:</p>";
echo "<ul>";
echo "<li>user_id: " . $_SESSION['user_id'] . "</li>";
echo "<li>school_id: " . $_SESSION['school_id'] . "</li>";
echo "<li>email: " . $_SESSION['email'] . "</li>";
echo "</ul>";

// Test database connection
include("conn/conn.php");

try {
    // Test if we can connect to database
    echo "<p>✅ Database connection successful</p>";
    
    // Test teacher_schedules query
    $sql = "SELECT DISTINCT subject, section FROM teacher_schedules WHERE school_id = ? AND status = 'active' LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $_SESSION['school_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<p>✅ Teacher schedules query successful</p>";
    echo "<p>Found " . $result->num_rows . " active schedules</p>";
    
    if ($result->num_rows > 0) {
        echo "<h3>Sample Teacher Schedules:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Subject</th><th>Section (Course-Section)</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
            echo "<td>" . htmlspecialchars($row['section']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No active teacher schedules found</p>";
    }
    
    // Test student data query
    $sql_student = "SELECT DISTINCT course_section FROM tbl_student WHERE school_id = ? AND user_id = ? LIMIT 5";
    $stmt_student = $conn->prepare($sql_student);
    $stmt_student->bind_param('ii', $_SESSION['school_id'], $_SESSION['user_id']);
    $stmt_student->execute();
    $result_student = $stmt_student->get_result();
    
    echo "<p>✅ Student data query successful</p>";
    echo "<p>Found " . $result_student->num_rows . " student course-sections</p>";
    
    if ($result_student->num_rows > 0) {
        echo "<h3>Sample Student Course-Sections:</h3>";
        echo "<ul>";
        while ($row = $result_student->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row['course_section']) . "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<p>If the database queries work above, the API should work. Try accessing:</p>";
echo "<p><a href='api/get-teacher-course-sections.php' target='_blank'>api/get-teacher-course-sections.php</a></p>";
?> 