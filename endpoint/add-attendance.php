<?php
require_once("../conn/db_connect.php");
require_once("../includes/session_config.php");

// Enhanced session validation with better error handling
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    // Return JSON response instead of HTML for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'session_expired',
            'message' => 'Session expired. Please log in again.',
            'redirect' => 'admin/login.php'
        ]);
        exit();
    }
    
    // For regular form submissions, redirect with error
    echo "<script>
        localStorage.setItem('sessionError', 'Session expired. Please log in again.');
        window.location.href = 'http://localhost/personal-proj/Qnnect/admin/login.php';
    </script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

// Debug logging with enhanced security context
error_log("=== ADD ATTENDANCE DEBUG ===");
error_log("User ID: $user_id, School ID: $school_id");
error_log("IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
error_log("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['qr_code'])) {
        $qrCode = trim($_POST['qr_code']); // Trim input to remove extra spaces
        
        // Enhanced attendance mode detection
        $attendanceMode = $_SESSION['attendance_mode'] ?? 'general';
        
        // Get class time based on attendance mode
        if ($attendanceMode === 'room_subject' && isset($_SESSION['schedule_start_time'])) {
            $class_start_time = $_SESSION['schedule_start_time'];
        } else {
            $class_start_time = isset($_SESSION['class_start_time_formatted']) 
                ? $_SESSION['class_start_time_formatted'] 
                : (isset($_SESSION['class_start_time']) ? $_SESSION['class_start_time'] : '08:00:00');
        }
        
        // Get the actual class time from session or database
        // This will automatically use whatever time is set in the Class Time Settings form
        if (isset($_SESSION['class_start_time_formatted'])) {
            $class_start_time = $_SESSION['class_start_time_formatted'];
            error_log('USING SESSION CLASS TIME: ' . $class_start_time);
        } elseif (isset($_SESSION['class_start_time'])) {
            $class_start_time = $_SESSION['class_start_time'] . ':00';
            error_log('USING SESSION CLASS TIME (formatted): ' . $class_start_time);
        } else {
            // Fallback to database if session not available
            try {
                if (isset($conn_qr)) {
                    $school_id = $_SESSION['school_id'] ?? 1;
                    $query = "SELECT start_time FROM class_time_settings WHERE school_id = ? ORDER BY updated_at DESC LIMIT 1";
                    $stmt = $conn_qr->prepare($query);
                    $stmt->bind_param("i", $school_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($row = $result->fetch_assoc()) {
                        $class_start_time = $row['start_time'];
                        if (strlen($class_start_time) == 5) {
                            $class_start_time .= ':00';
                        }
                        error_log('USING DATABASE CLASS TIME: ' . $class_start_time);
                    } else {
                        $class_start_time = '08:00:00'; // Default fallback
                        error_log('USING DEFAULT CLASS TIME: ' . $class_start_time);
                    }
                } else {
                    $class_start_time = '08:00:00'; // Default fallback
                    error_log('USING DEFAULT CLASS TIME (no DB): ' . $class_start_time);
                }
            } catch (Exception $e) {
                $class_start_time = '08:00:00'; // Default fallback
                error_log('USING DEFAULT CLASS TIME (error): ' . $class_start_time);
            }
        }
        
        // Make sure class time is properly formatted no matter what
        if (strlen($class_start_time) == 5) {
            $class_start_time .= ':00';
        }
        
        // Ensure class time is in 24-hour format for comparison
        // Convert 12-hour format to 24-hour format if needed
        if (preg_match('/^(\d{1,2}):(\d{2})(:\d{2})?\s*(AM|PM)$/i', $class_start_time, $matches)) {
            $hour = intval($matches[1]);
            $minute = $matches[2];
            $second = isset($matches[3]) ? $matches[3] : ':00';
            $period = strtoupper($matches[4]);
            
            if ($period === 'PM' && $hour < 12) {
                $hour += 12;
            } elseif ($period === 'AM' && $hour == 12) {
                $hour = 0;
            }
            
            $class_start_time = sprintf('%02d:%s%s', $hour, $minute, $second);
            error_log('Converted 12-hour format to 24-hour: ' . $class_start_time);
        }
        
        // Get current instructor and subject from session based on mode
        if ($attendanceMode === 'room_subject') {
            $currentInstructorName = $_SESSION['current_instructor_name'] ?? 'Schedule Instructor';
            $currentSubjectName = $_SESSION['current_subject_name'] ?? 'Schedule Subject';
            $currentInstructorId = $_SESSION['current_instructor_id'] ?? null;
            $currentSubjectId = $_SESSION['current_subject_id'] ?? null;
        } else {
            $currentInstructorId = $_SESSION['current_instructor_id'] ?? null;
            $currentInstructorName = $_SESSION['current_instructor_name'] ?? 'Not Set';
            $currentSubjectId = $_SESSION['current_subject_id'] ?? null;
            $currentSubjectName = $_SESSION['current_subject_name'] ?? 'Not Set';
        }

        // Validate QR code input
        if (empty($qrCode)) {
            $errorParams = http_build_query([
                'error' => 'empty_qr',
                'message' => 'No QR code detected',
                'details' => 'Please scan a valid QR code to mark attendance.',
                'mode' => $attendanceMode,
                'timestamp' => time()
            ]);
            echo "<script>
                window.location.href = 'http://localhost/personal-proj/Qnnect/index.php?{$errorParams}';
            </script>";
            exit();
        }

        // Set the timezone globally
        date_default_timezone_set('Asia/Manila');
        
        // Enhanced debug logging
        error_log('=== ATTENDANCE CONTEXT ===');
        error_log('Attendance Mode: ' . $attendanceMode);
        error_log('Current timezone: ' . date_default_timezone_get());
        error_log('Current server time: ' . date('Y-m-d H:i:s'));
        error_log('Class start time: ' . $class_start_time);
        error_log('School ID: ' . $school_id);
        error_log('User ID: ' . $user_id);

        try {
            // Step 1: Verify student by QR code with strict multi-tenant filtering
            $selectStmt = $conn_qr->prepare("SELECT tbl_student_id, student_name FROM tbl_student 
                                        WHERE generated_code = ? 
                                        AND user_id = ? 
                                        AND school_id = ?");
            $selectStmt->bind_param("sii", $qrCode, $user_id, $school_id);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            $studentData = $result->fetch_assoc();

            if ($studentData !== null) {
                $studentID = $studentData["tbl_student_id"];
                $studentName = $studentData["student_name"];

                // Step 2: Enhanced duplicate check with compound key validation
                $checkStmt = $conn_qr->prepare("SELECT * FROM tbl_attendance 
                    WHERE tbl_student_id = ? 
                    AND DATE(time_in) = CURDATE() 
                    AND instructor_id = ? 
                    AND subject_id = ? 
                    AND user_id = ? 
                    AND school_id = ? 
                    LIMIT 1");
                $checkStmt->bind_param("iiiii", $studentID, $currentInstructorId, $currentSubjectId, $user_id, $school_id);
                $checkStmt->execute();
                $attendanceResult = $checkStmt->get_result();
                $attendanceRecord = $attendanceResult->fetch_assoc();

                if (!$attendanceRecord) {
                    // First scan: Insert new record with comprehensive data isolation
                    $today = date('Y-m-d');
                    $timeIn = date("Y-m-d H:i:s"); // Get fresh timestamp for time_in
                    
                    // Ensure class_start_time has seconds component for proper comparison
                    if (strlen($class_start_time) == 5) {
                        $class_start_time .= ':00';
                    }
                    
                    $class_start_datetime = new DateTime($today . ' ' . $class_start_time);
                    $time_in_datetime = new DateTime($timeIn);
                    
                    // Enhanced logging for status determination
                    error_log('=== STATUS DETERMINATION ===');
                    error_log('Student: ' . $studentName);
                    error_log('Class start time: ' . $class_start_time);
                    error_log('Class start timestamp: ' . $class_start_datetime->getTimestamp());
                    error_log('Time in: ' . $timeIn);
                    error_log('Time in timestamp: ' . $time_in_datetime->getTimestamp());
                    error_log('Session class_start_time: ' . ($_SESSION['class_start_time'] ?? 'Not set'));
                    error_log('Session class_start_time_formatted: ' . ($_SESSION['class_start_time_formatted'] ?? 'Not set'));
                    
                    // Enhanced attendance status determination with detailed logging
                    $timeDifference = $time_in_datetime->getTimestamp() - $class_start_datetime->getTimestamp();
                    $minutesDifference = round($timeDifference / 60);
                    
                    // EXTRA DEBUGGING FOR YOUR SPECIFIC CASE
                    error_log('=== EXTRA DEBUGGING ===');
                    error_log('Class time string: ' . $class_start_time);
                    error_log('Class datetime: ' . $class_start_datetime->format('Y-m-d H:i:s'));
                    error_log('Time in datetime: ' . $time_in_datetime->format('Y-m-d H:i:s'));
                    error_log('Class timestamp: ' . $class_start_datetime->getTimestamp());
                    error_log('Time in timestamp: ' . $time_in_datetime->getTimestamp());
                    error_log('Time difference: ' . $timeDifference . ' seconds');
                    error_log('Minutes difference: ' . $minutesDifference . ' minutes');
                    
                    if ($time_in_datetime->getTimestamp() <= $class_start_datetime->getTimestamp()) {
                        $status = 'On Time';
                        error_log('STATUS: ON TIME - Student arrived ' . abs($minutesDifference) . ' minutes before class starts');
                    } else {
                        $status = 'Late';
                        error_log('STATUS: LATE - Student arrived ' . $minutesDifference . ' minutes after class starts');
                    }
                    
                    // Log detailed comparison for debugging
                    error_log('=== DETAILED TIME COMPARISON ===');
                    error_log('Class Start Time: ' . $class_start_datetime->format('Y-m-d H:i:s'));
                    error_log('Student Arrival: ' . $time_in_datetime->format('Y-m-d H:i:s'));
                    error_log('Time Difference: ' . $minutesDifference . ' minutes');
                    error_log('Final Status: ' . $status);
                    
                    // Insert with complete multi-tenant isolation
                    $stmt = $conn_qr->prepare("INSERT INTO tbl_attendance 
                        (tbl_student_id, time_in, status, instructor_id, subject_id, user_id, school_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issiiii", $studentID, $timeIn, $status, $currentInstructorId, $currentSubjectId, $user_id, $school_id);
                    
                    // Enhanced debug logging before INSERT
                    error_log("=== INSERTING ATTENDANCE RECORD ===");
                    error_log("Student ID: $studentID");
                    error_log("Time In: $timeIn");
                    error_log("Status: $status");
                    error_log("Instructor ID: $currentInstructorId");
                    error_log("Subject ID: $currentSubjectId");
                    error_log("User ID: $user_id");
                    error_log("School ID: $school_id");
                    error_log("Attendance Mode: $attendanceMode");
                    
                    $result = $stmt->execute();
                    
                    // Enhanced success/failure logging
                    if ($result) {
                        $inserted_id = $conn_qr->insert_id;
                        error_log("SUCCESS: Inserted attendance ID: $inserted_id");
                        
                        // Success redirect with context
                        $successParams = http_build_query([
                            'success' => 'attendance_added',
                            'student' => $studentName,
                            'status' => $status,
                            'mode' => $attendanceMode,
                            'id' => $inserted_id
                        ]);
                        header("Location: http://localhost/personal-proj/Qnnect/index.php?$successParams");
                        exit();
                    } else {
                        error_log("FAILED: INSERT error: " . $stmt->error);
                        
                        $errorParams = http_build_query([
                            'error' => 'db_insert_failed',
                            'message' => 'Failed to save attendance',
                            'details' => 'Unable to save attendance record to database. Please try again.',
                            'mode' => $attendanceMode
                        ]);
                        header("Location: http://localhost/personal-proj/Qnnect/index.php?$errorParams");
                        exit();
                    }
                } else {
                    // Enhanced duplicate handling with error modal
                    $attendanceTime = date('h:i A', strtotime($attendanceRecord['time_in']));
                    $attendanceDate = date('M d, Y', strtotime($attendanceRecord['time_in']));
                    $attendanceStatus = $attendanceRecord['status'];
                    
                    $errorParams = http_build_query([
                        'error' => 'duplicate_scan',
                        'message' => 'Attendance already recorded',
                        'details' => "Student $studentName already marked $attendanceStatus on $attendanceDate at $attendanceTime for $currentSubjectName.",
                        'mode' => $attendanceMode
                    ]);
                    echo "<script>
                        window.location.href = 'http://localhost/personal-proj/Qnnect/index.php?{$errorParams}';
                    </script>";
                    exit();
                }
            } else {
                // Invalid QR code with enhanced error context
                error_log("INVALID QR: QR code '$qrCode' not found for user_id: $user_id, school_id: $school_id");
                
                $errorParams = http_build_query([
                    'error' => 'invalid_qr',
                    'message' => 'QR code not registered to this school',
                    'details' => 'This QR code is not associated with any student in this school system.',
                    'mode' => $attendanceMode,
                    'qr' => substr($qrCode, 0, 10) . '...' // Partial QR for debugging
                ]);
                echo "<script>
                    window.location.href = 'http://localhost/personal-proj/Qnnect/index.php?{$errorParams}';
                </script>";
                exit();
            }
        } catch (Exception $e) {
            error_log("Database Exception in add-attendance: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $errorParams = http_build_query([
                'error' => 'db_error',
                'message' => 'Database connection error',
                'details' => 'Unable to connect to the database. Please try again.',
                'mode' => $attendanceMode
            ]);
            echo "<script>
                window.location.href = 'http://localhost/personal-proj/Qnnect/index.php?{$errorParams}';
            </script>";
            exit();
        }
    } else {
        $errorParams = http_build_query([
            'error' => 'missing_qr',
            'message' => 'QR code data missing',
            'details' => 'No QR code data was received. Please try scanning again.',
            'mode' => $_SESSION['attendance_mode'] ?? 'general'
        ]);
        echo "<script>
            window.location.href = 'http://localhost/personal-proj/Qnnect/index.php?{$errorParams}';
        </script>";
        exit();
    }
}
?>
