<?php
// Test QR scanning functionality
session_start();
require_once('conn/db_connect.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test QR Scanning</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>🧪 Test QR Scanning</h2>
        
        <div class='alert alert-info'>
            <h5>Testing QR Code Scanning</h5>
            <p>This will test if QR code scanning works properly after the database connection fix.</p>
        </div>
        
        <div class='row'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Test QR Code</h5>
                    </div>
                    <div class='card-body'>
                        <form method='post'>
                            <div class='form-group mb-3'>
                                <label>Select School ID:</label>
                                <select name='test_school_id' class='form-control'>
                                    <option value='1'>School ID 1</option>
                                    <option value='2'>School ID 2</option>
                                    <option value='3'>School ID 3</option>
                                </select>
                            </div>
                            <div class='form-group mb-3'>
                                <label>QR Code to Test:</label>
                                <input type='text' name='test_qr_code' class='form-control' placeholder='Enter QR code here'>
                            </div>
                            <button type='submit' name='test_qr' class='btn btn-primary'>
                                <i class='fas fa-qrcode'></i> Test QR Code
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Database Connection Test</h5>
                    </div>
                    <div class='card-body'>";

// Test database connection
try {
    $test_query = "SELECT COUNT(*) as count FROM tbl_student WHERE school_id = 1";
    $stmt = $conn_qr->prepare($test_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    echo "<p style='color: green;'>✅ Database connection working</p>";
    echo "<p>• Students in School ID 1: <strong>$count</strong></p>";
    
    // Test class time settings
    $class_time_query = "SELECT COUNT(*) as count FROM class_time_settings WHERE school_id = 1";
    $stmt = $conn_qr->prepare($class_time_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $class_time_count = $result->fetch_assoc()['count'];
    
    echo "<p>• Class time settings in School ID 1: <strong>$class_time_count</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "</div></div></div>";

if (isset($_POST['test_qr']) && $_POST['test_school_id'] && $_POST['test_qr_code']) {
    $school_id = intval($_POST['test_school_id']);
    $qr_code = trim($_POST['test_qr_code']);
    $_SESSION['school_id'] = $school_id;
    $_SESSION['user_id'] = 1; // Default user ID for testing
    
    echo "<div class='card mt-4'>
        <div class='card-header'>
            <h5>QR Code Test Results</h5>
        </div>
        <div class='card-body'>";
    
    try {
        // Test the exact query from add-attendance.php
        $selectStmt = $conn_qr->prepare("SELECT tbl_student_id, student_name FROM tbl_student 
                                    WHERE generated_code = ? 
                                    AND user_id = ? 
                                    AND school_id = ?");
        $user_id = 1; // Default for testing
        $selectStmt->bind_param("sii", $qr_code, $user_id, $school_id);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        $studentData = $result->fetch_assoc();
        
        if ($studentData !== null) {
            $studentID = $studentData["tbl_student_id"];
            $studentName = $studentData["student_name"];
            
            echo "<p style='color: green;'>✅ QR Code Valid!</p>";
            echo "<p>• Student ID: <strong>$studentID</strong></p>";
            echo "<p>• Student Name: <strong>$studentName</strong></p>";
            echo "<p>• School ID: <strong>$school_id</strong></p>";
            
            // Test if attendance would be recorded
            $timeIn = date("Y-m-d H:i:s");
            $currentInstructorId = 1; // Default for testing
            $currentSubjectId = 1; // Default for testing
            
            $checkStmt = $conn_qr->prepare("SELECT * FROM tbl_attendance 
                WHERE tbl_student_id = ? 
                AND DATE(time_in) = CURDATE() 
                AND instructor_id = ? 
                AND subject_id = ? 
                AND user_id = ? 
                AND school_id = ? 
                LIMIT 1");
            $checkStmt->bind_param("iiiiii", $studentID, $currentInstructorId, $currentSubjectId, $user_id, $school_id);
            $checkStmt->execute();
            $attendanceResult = $checkStmt->get_result();
            $attendanceRecord = $attendanceResult->fetch_assoc();
            
            if (!$attendanceRecord) {
                echo "<p style='color: green;'>✅ No duplicate attendance found</p>";
                echo "<p>• Would record attendance: <strong>YES</strong></p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Duplicate attendance found</p>";
                echo "<p>• Would record attendance: <strong>NO (Duplicate)</strong></p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ QR Code Invalid!</p>";
            echo "<p>• QR Code: <strong>$qr_code</strong></p>";
            echo "<p>• School ID: <strong>$school_id</strong></p>";
            echo "<p>• User ID: <strong>$user_id</strong></p>";
            
            // Show available QR codes for this school
            $available_query = "SELECT generated_code, student_name FROM tbl_student WHERE school_id = ? LIMIT 5";
            $stmt = $conn_qr->prepare($available_query);
            $stmt->bind_param("i", $school_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo "<p><strong>Available QR codes for School ID $school_id:</strong></p>";
                echo "<ul>";
                while ($row = $result->fetch_assoc()) {
                    echo "<li>" . $row['student_name'] . " - " . $row['generated_code'] . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color: orange;'>⚠️ No students found for School ID $school_id</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div></div>";
}

echo "<div class='card mt-4'>
    <div class='card-header'>
        <h5>Quick Actions</h5>
    </div>
    <div class='card-body'>
        <a href='index.php' class='btn btn-primary me-2'>
            <i class='fas fa-home'></i> Go to Main Page
        </a>
        <a href='force_fix_session.php' class='btn btn-warning me-2'>
            <i class='fas fa-tools'></i> Force Fix Session
        </a>
        <a href='debug_active_session.php' class='btn btn-info'>
            <i class='fas fa-search'></i> Debug Active Session
        </a>
    </div>
</div>

</div>
</body>
</html>";
?> 