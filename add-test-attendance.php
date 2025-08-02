<?php
require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo "Please log in first";
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

echo "<h3>Adding Test Attendance Records</h3>";
echo "<p><strong>User ID:</strong> $user_id</p>";
echo "<p><strong>School ID:</strong> $school_id</p>";

try {
    // First, ensure tbl_student table exists
    $createStudentTable = "CREATE TABLE IF NOT EXISTS tbl_student (
        tbl_student_id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(100) NOT NULL,
        course_section VARCHAR(50) NOT NULL,
        qr_code VARCHAR(100) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn_qr->query($createStudentTable);
    
    // Add some test students
    $testStudents = [
        ['John Doe', 'BSIT-3A', 'QR001'],
        ['Jane Smith', 'BSCS-2B', 'QR002'],
        ['Mike Johnson', 'BSIT-4A', 'QR003'],
        ['Sarah Williams', 'BSCS-3C', 'QR004'],
        ['David Brown', 'BSIT-2A', 'QR005']
    ];
    
    echo "<h4>Adding Test Students:</h4>";
    foreach ($testStudents as $student) {
        $checkStudent = "SELECT tbl_student_id FROM tbl_student WHERE qr_code = ?";
        $checkStmt = $conn_qr->prepare($checkStudent);
        $checkStmt->bind_param("s", $student[2]);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows == 0) {
            $insertStudent = "INSERT INTO tbl_student (student_name, course_section, qr_code) VALUES (?, ?, ?)";
            $insertStmt = $conn_qr->prepare($insertStudent);
            $insertStmt->bind_param("sss", $student[0], $student[1], $student[2]);
            $insertStmt->execute();
            echo "<p>✅ Added student: {$student[0]} - {$student[1]}</p>";
        } else {
            echo "<p>ℹ️ Student already exists: {$student[0]}</p>";
        }
    }
    
    // Now add attendance records for today
    echo "<h4>Adding Test Attendance Records:</h4>";
    $today = date('Y-m-d H:i:s');
    
    foreach ($testStudents as $index => $student) {
        // Get student ID
        $getStudentId = "SELECT tbl_student_id FROM tbl_student WHERE qr_code = ?";
        $getStmt = $conn_qr->prepare($getStudentId);
        $getStmt->bind_param("s", $student[2]);
        $getStmt->execute();
        $studentResult = $getStmt->get_result();
        $studentData = $studentResult->fetch_assoc();
        $student_id = $studentData['tbl_student_id'];
        
        // Create different times for variety
        $minutesOffset = $index * 5; // 5 minutes apart
        $timeIn = date('Y-m-d H:i:s', strtotime("$today + $minutesOffset minutes"));
        
        // Determine status based on class start time
        $classStartTime = $_SESSION['class_start_time'] ?? '08:00:00';
        $classStartDateTime = date('Y-m-d') . ' ' . $classStartTime;
        $status = (strtotime($timeIn) <= strtotime($classStartDateTime . ' +15 minutes')) ? 'On Time' : 'Late';
        
        // Check if attendance already exists
        $checkAttendance = "SELECT tbl_attendance_id FROM tbl_attendance WHERE tbl_student_id = ? AND DATE(time_in) = CURDATE() AND user_id = ? AND school_id = ?";
        $checkAttStmt = $conn_qr->prepare($checkAttendance);
        $checkAttStmt->bind_param("iii", $student_id, $user_id, $school_id);
        $checkAttStmt->execute();
        $attResult = $checkAttStmt->get_result();
        
        if ($attResult->num_rows == 0) {
            $insertAttendance = "INSERT INTO tbl_attendance (tbl_student_id, time_in, status, user_id, school_id) VALUES (?, ?, ?, ?, ?)";
            $attStmt = $conn_qr->prepare($insertAttendance);
            $attStmt->bind_param("issii", $student_id, $timeIn, $status, $user_id, $school_id);
            
            if ($attStmt->execute()) {
                echo "<p>✅ Added attendance for: {$student[0]} at $timeIn - Status: $status</p>";
            } else {
                echo "<p>❌ Failed to add attendance for: {$student[0]}</p>";
            }
        } else {
            echo "<p>ℹ️ Attendance already exists for: {$student[0]}</p>";
        }
    }
    
    // Check the data
    echo "<h4>Verification - Current Attendance Records:</h4>";
    $verifyQuery = "SELECT a.*, s.student_name, s.course_section 
                    FROM tbl_attendance a
                    LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
                    WHERE a.user_id = ? AND a.school_id = ?
                    ORDER BY a.time_in DESC";
    $verifyStmt = $conn_qr->prepare($verifyQuery);
    $verifyStmt->bind_param("ii", $user_id, $school_id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows > 0) {
        echo "<table class='table table-bordered'>";
        echo "<tr><th>ID</th><th>Student</th><th>Course</th><th>Time In</th><th>Status</th></tr>";
        while ($row = $verifyResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['tbl_attendance_id']}</td>";
            echo "<td>{$row['student_name']}</td>";
            echo "<td>{$row['course_section']}</td>";
            echo "<td>{$row['time_in']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No attendance records found for your user/school combination</p>";
    }
    
    echo "<h3>✅ Test data added successfully!</h3>";
    echo "<p><a href='index.php' class='btn btn-primary'>Go back to main page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Test Attendance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <!-- Results will be displayed above -->
    </div>
</body>
</html>
