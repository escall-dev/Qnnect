<?php
// Debug script for QR code scanning
session_start();
require_once 'conn/db_connect.php';

echo "<h2>QR Code Scanning Debug</h2>";

// Check session variables
echo "<h3>Session Variables:</h3>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
echo "<p>School ID: " . ($_SESSION['school_id'] ?? 'NOT SET') . "</p>";
echo "<p>Email: " . ($_SESSION['email'] ?? 'NOT SET') . "</p>";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo "<p style='color: red;'>❌ User not properly logged in!</p>";
    exit;
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

echo "<p style='color: green;'>✅ User properly logged in</p>";

// Test QR code scanning
if (isset($_POST['test_qr'])) {
    $test_qr = $_POST['test_qr'];
    
    echo "<h3>Testing QR Code: $test_qr</h3>";
    
    // Simulate the API call
    echo "<h4>Step 1: Looking up student by QR code</h4>";
    
    $query = "SELECT tbl_student_id, student_name, course_section, user_id, school_id 
              FROM tbl_student 
              WHERE generated_code = ?";
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("s", $test_qr);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<p style='color: red;'>❌ Student not found for QR code: $test_qr</p>";
        
        // Show all students for this user
        echo "<h4>Your Students:</h4>";
        $student_query = "SELECT student_name, course_section, generated_code 
                         FROM tbl_student 
                         WHERE user_id = ? AND school_id = ?
                         ORDER BY student_name";
        $stmt = $conn_qr->prepare($student_query);
        $stmt->bind_param("ii", $user_id, $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<table border='1'>";
            echo "<tr><th>Name</th><th>Course</th><th>QR Code</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['student_name'] . "</td>";
                echo "<td>" . $row['course_section'] . "</td>";
                echo "<td>" . $row['generated_code'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No students found for your account.</p>";
        }
    } else {
        $student = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Student found: " . $student['student_name'] . "</p>";
        echo "<p>Student User ID: " . $student['user_id'] . " (Your User ID: $user_id)</p>";
        echo "<p>Student School ID: " . $student['school_id'] . " (Your School ID: $school_id)</p>";
        
        if ($student['user_id'] == $user_id && $student['school_id'] == $school_id) {
            echo "<p style='color: green;'>✅ Student belongs to your account</p>";
            
            // Test attendance creation
            echo "<h4>Step 2: Testing attendance creation</h4>";
            
            $currentTime = date('Y-m-d H:i:s');
            $status = 'On Time';
            $instructor_id = 1; // Default instructor
            $subject_id = 1; // Default subject
            
            $insert_query = "INSERT INTO tbl_attendance 
                           (tbl_student_id, time_in, status, instructor_id, subject_id, user_id, school_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn_qr->prepare($insert_query);
            $stmt->bind_param("issiiii", 
                $student['tbl_student_id'], 
                $currentTime, 
                $status, 
                $instructor_id, 
                $subject_id, 
                $user_id, 
                $school_id
            );
            
            $result = $stmt->execute();
            
            if ($result) {
                $attendance_id = $conn_qr->insert_id;
                echo "<p style='color: green;'>✅ Attendance record created successfully!</p>";
                echo "<p>Attendance ID: $attendance_id</p>";
                echo "<p>Time: $currentTime</p>";
                echo "<p>Status: $status</p>";
                
                // Check if it appears in the table
                echo "<h4>Step 3: Checking if record appears in table</h4>";
                $check_query = "SELECT a.tbl_attendance_id, s.student_name, a.time_in, a.status, a.user_id, a.school_id
                               FROM tbl_attendance a
                               JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id
                               WHERE a.user_id = ? AND a.school_id = ?
                               ORDER BY a.time_in DESC
                               LIMIT 5";
                
                $stmt = $conn_qr->prepare($check_query);
                $stmt->bind_param("ii", $user_id, $school_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo "<table border='1'>";
                    echo "<tr><th>ID</th><th>Student</th><th>Time</th><th>Status</th><th>User ID</th><th>School ID</th></tr>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['tbl_attendance_id'] . "</td>";
                        echo "<td>" . $row['student_name'] . "</td>";
                        echo "<td>" . $row['time_in'] . "</td>";
                        echo "<td>" . $row['status'] . "</td>";
                        echo "<td>" . $row['user_id'] . "</td>";
                        echo "<td>" . $row['school_id'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p style='color: red;'>❌ No attendance records found for your account!</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Failed to create attendance record: " . $conn_qr->error . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Student does not belong to your account</p>";
        }
    }
} else {
    // Show form to test QR code
    echo "<h3>Test QR Code Scanning</h3>";
    echo "<form method='POST'>";
    echo "<p>Enter a QR code to test:</p>";
    echo "<input type='text' name='test_qr' placeholder='Enter QR code here' style='width: 300px;'>";
    echo "<button type='submit'>Test QR Code</button>";
    echo "</form>";
    
    // Show your students' QR codes
    echo "<h3>Your Students' QR Codes:</h3>";
    $student_query = "SELECT student_name, course_section, generated_code 
                     FROM tbl_student 
                     WHERE user_id = ? AND school_id = ?
                     ORDER BY student_name";
    $stmt = $conn_qr->prepare($student_query);
    $stmt->bind_param("ii", $user_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Name</th><th>Course</th><th>QR Code</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['student_name'] . "</td>";
            echo "<td>" . $row['course_section'] . "</td>";
            echo "<td>" . $row['generated_code'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No students found for your account.</p>";
    }
}

echo "<p><a href='index.php'>Go to Main Page</a></p>";
?> 