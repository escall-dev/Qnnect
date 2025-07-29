<?php
include("../conn/conn.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['qr_code'])) {
        $qrCode = trim($_POST['qr_code']); // Trim input to remove extra spaces
        
        // Use the formatted class start time if available (has seconds appended)
        $class_start_time = isset($_SESSION['class_start_time_formatted']) 
            ? $_SESSION['class_start_time_formatted'] 
            : (isset($_SESSION['class_start_time']) ? $_SESSION['class_start_time'] : '08:00:00');
        
        // Make sure class time is properly formatted no matter what
        if (strlen($class_start_time) == 5) {
            $class_start_time .= ':00';
        }
        
        // Get current instructor and subject from session
        $currentInstructorId = isset($_SESSION['current_instructor_id']) ? $_SESSION['current_instructor_id'] : null;
        $currentInstructorName = isset($_SESSION['current_instructor_name']) ? $_SESSION['current_instructor_name'] : 'Not Set';
        $currentSubjectId = isset($_SESSION['current_subject_id']) ? $_SESSION['current_subject_id'] : null;
        $currentSubjectName = isset($_SESSION['current_subject_name']) ? $_SESSION['current_subject_name'] : 'Not Set';

        // Validate QR code input
        if (empty($qrCode)) {
            echo "<script>
                window.location.href = 'http://localhost/qr-code-attendance-system/index.php?error=empty_qr';
            </script>";
            exit();
        }

        // Set the timezone globally
        date_default_timezone_set('Asia/Manila');
        
        // Debug timezone setting
        error_log('Current timezone set to: ' . date_default_timezone_get());
        error_log('Current server time: ' . date('Y-m-d H:i:s'));
        error_log('Class start time from form: ' . $class_start_time);

        try {
            // Step 1: Verify student by QR code
            $selectStmt = $conn->prepare("SELECT tbl_student_id, student_name FROM tbl_student WHERE generated_code = :generated_code");
            $selectStmt->bindParam(":generated_code", $qrCode, PDO::PARAM_STR);
            $selectStmt->execute();
            $result = $selectStmt->fetch();

            if ($result !== false) {
                $studentID = $result["tbl_student_id"];
                $studentName = $result["student_name"];

                // Step 2: Check if the student already has an attendance record today for this specific instructor and subject
                $checkStmt = $conn->prepare("SELECT * FROM tbl_attendance 
                    WHERE tbl_student_id = :tbl_student_id 
                    AND DATE(time_in) = CURDATE() 
                    AND instructor_id = :instructor_id 
                    AND subject_id = :subject_id 
                    LIMIT 1");
                $checkStmt->bindParam(":tbl_student_id", $studentID, PDO::PARAM_INT);
                $checkStmt->bindParam(":instructor_id", $currentInstructorId, PDO::PARAM_INT);
                $checkStmt->bindParam(":subject_id", $currentSubjectId, PDO::PARAM_INT);
                $checkStmt->execute();
                $attendanceRecord = $checkStmt->fetch();

                if (!$attendanceRecord) {
                    // First scan for this instructor/subject: Insert new record with time_in and status
                    $today = date('Y-m-d');
                    $timeIn = date("Y-m-d H:i:s"); // Get fresh timestamp for time_in
                    
                    // Ensure class_start_time has seconds component for proper comparison
                    if (strlen($class_start_time) == 5) {
                        $class_start_time .= ':00';
                    }
                    
                    $class_start_datetime = new DateTime($today . ' ' . $class_start_time);
                    $time_in_datetime = new DateTime($timeIn);
                    
                    // Force debug - dump actual values
                    error_log('CRITICAL COMPARISON: ' . 
                        'Student: ' . $studentName .
                        ', Class start time: ' . $class_start_time . 
                        ', Class start timestamp: ' . $class_start_datetime->getTimestamp() . 
                        ', Time in: ' . $timeIn . 
                        ', Time in timestamp: ' . $time_in_datetime->getTimestamp());
                    
                    // Direct timestamp comparison with extra safety check
                    if ($time_in_datetime->getTimestamp() <= $class_start_datetime->getTimestamp()) {
                        $status = 'On Time';
                        error_log('SHOULD BE ON TIME: Student arrived before class starts');
                    } else {
                        $status = 'Late';
                        error_log('SHOULD BE LATE: Student arrived after class starts');
                    }
                    
                    // Extra safety check with string times
                    $time_in_time = date('H:i:s', strtotime($timeIn));
                    if (strtotime($time_in_time) <= strtotime($class_start_time)) {
                        error_log('SECONDARY CHECK CONFIRMS: Should be ON TIME');
                    } else {
                        error_log('SECONDARY CHECK CONFIRMS: Should be LATE');
                    }
                    
                    // Log the comparison for debugging
                    error_log('Direct Attendance: Class start: ' . $class_start_datetime->format('Y-m-d H:i:s') . 
                              ', Time in: ' . $time_in_datetime->format('Y-m-d H:i:s') . 
                              ', Status: ' . $status);
                    
                    // Store the status in the database along with instructor and subject IDs
                    $stmt = $conn->prepare("INSERT INTO tbl_attendance (tbl_student_id, time_in, status, instructor_id, subject_id) 
                        VALUES (:tbl_student_id, :time_in, :status, :instructor_id, :subject_id)");
                    $stmt->bindParam(":tbl_student_id", $studentID, PDO::PARAM_INT);
                    $stmt->bindParam(":time_in", $timeIn, PDO::PARAM_STR);
                    $stmt->bindParam(":status", $status, PDO::PARAM_STR);
                    $stmt->bindParam(":instructor_id", $currentInstructorId, PDO::PARAM_INT);
                    $stmt->bindParam(":subject_id", $currentSubjectId, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    // Redirect after successful operation
                    header("Location: http://localhost/qr-code-attendance-system/index.php?success=attendance_added");
                    exit();
                } else {
                    // Student already has attendance record today for this instructor/subject
                    // Return JavaScript to show the modal with student info
                    $attendanceTime = date('h:i A', strtotime($attendanceRecord['time_in']));
                    $attendanceDate = date('M d, Y', strtotime($attendanceRecord['time_in']));
                    $attendanceStatus = $attendanceRecord['status'];
                    
                    echo "<script>
                        localStorage.setItem('duplicateAttendance', JSON.stringify({
                            studentName: '".addslashes($studentName)."',
                            attendanceTime: '".addslashes($attendanceTime)."',
                            attendanceDate: '".addslashes($attendanceDate)."',
                            attendanceStatus: '".addslashes($attendanceStatus)."',
                            instructorName: '".addslashes($currentInstructorName)."',
                            subjectName: '".addslashes($currentSubjectName)."'
                        }));
                        window.location.href = 'http://localhost/qr-code-attendance-system/index.php?error=duplicate_scan';
                    </script>";
                    exit();
                }
            } else {
                echo "<script>
                    window.location.href = 'http://localhost/qr-code-attendance-system/index.php?error=invalid_qr';
                </script>";
                exit();
            }
        } catch (PDOException $e) {
            echo "<script>
                window.location.href = 'http://localhost/qr-code-attendance-system/index.php?error=db_error';
            </script>";
            exit();
        }
    } else {
        echo "<script>
            window.location.href = 'http://localhost/qr-code-attendance-system/index.php?error=missing_qr';
        </script>";
        exit();
    }
}
?>
