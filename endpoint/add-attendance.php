<?php
require_once("../conn/db_connect.php");
require_once("../includes/session_config.php");

// Ensure database connections are available
if (!isset($conn_qr)) {
    // Try to establish connection manually if not available
    $hostName = "localhost";
    $dbUser = "root";
    $dbPassword = "";
    $qrDb = "qr_attendance_db";
    
    $conn_qr = mysqli_connect($hostName, $dbUser, $dbPassword, $qrDb);
    if (!$conn_qr) {
        // Try to create database if it doesn't exist
        $temp_conn = mysqli_connect($hostName, $dbUser, $dbPassword);
        if ($temp_conn) {
            mysqli_query($temp_conn, "CREATE DATABASE IF NOT EXISTS $qrDb");
            mysqli_close($temp_conn);
            $conn_qr = mysqli_connect($hostName, $dbUser, $dbPassword, $qrDb);
        }
    }
}

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
        window.location.href = 'http://localhost/Qnnect/admin/login.php';
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
        
        // Get class time (attendance mode removed)
        $class_start_time = isset($_SESSION['class_start_time_formatted']) 
            ? $_SESSION['class_start_time_formatted'] 
            : (isset($_SESSION['class_start_time']) ? $_SESSION['class_start_time'] : '08:00:00');
        
        // Get the actual class time from session or database
        // This will automatically use whatever time is set in the Class Time Settings form
        if (isset($_SESSION['class_start_time_formatted'])) {
            $class_start_time = $_SESSION['class_start_time_formatted'];
            error_log('USING SESSION CLASS TIME: ' . $class_start_time);
        } elseif (isset($_SESSION['class_start_time'])) {
            $class_start_time = $_SESSION['class_start_time'];
            // Only append :00 if the time doesn't already have seconds
            if (strlen($class_start_time) == 5) {
                $class_start_time .= ':00';
            }
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
        
        // Get current instructor and subject from session
        $currentInstructorId = $_SESSION['current_instructor_id'] ?? 1;
        $currentInstructorName = $_SESSION['current_instructor_name'] ?? 'Not Set';
        $currentSubjectId = $_SESSION['current_subject_id'] ?? 1;
        $currentSubjectName = $_SESSION['current_subject_name'] ?? 'Not Set';

        // Validate QR code input
        if (empty($qrCode)) {
            $errorParams = http_build_query([
                'error' => 'empty_qr',
                'message' => 'No QR code detected',
                'details' => 'Please scan a valid QR code to mark attendance.',
                'timestamp' => time()
            ]);
            echo "<script>
                window.location.href = 'http://localhost/Qnnect/index.php?{$errorParams}';
            </script>";
            exit();
        }

        // Set the timezone globally
        date_default_timezone_set('Asia/Manila');
        
        // Enhanced debug logging
        error_log('=== ATTENDANCE CONTEXT ===');
        error_log('Current timezone: ' . date_default_timezone_get());
        error_log('Current server time: ' . date('Y-m-d H:i:s'));
        error_log('Class start time: ' . $class_start_time);
        error_log('School ID: ' . $school_id);
        error_log('User ID: ' . $user_id);
        error_log('Current Instructor ID: ' . $currentInstructorId);
        error_log('Current Subject ID: ' . $currentSubjectId);

        try {
            // Step 1: Parse QR code format - support both old and new formats
            $qr_parts = explode('|', $qrCode);
            
            // Check if it's the new format: course|section|instructor_id
            if (count($qr_parts) >= 3) {
                $course_code = $qr_parts[0];
                $section = $qr_parts[1];
                $qr_instructor_id = $qr_parts[2];
            } 
            // Check if it's the old format: STU-user_id-school_id-hash-random
            elseif (strpos($qrCode, 'STU-') === 0) {
                $stu_parts = explode('-', $qrCode);
                if (count($stu_parts) >= 5) {
                    // This is the old format, we need to look up the student by generated_code
                    $selectStmt = $conn_qr->prepare("SELECT tbl_student_id, student_name, course_section FROM tbl_student 
                                                WHERE generated_code = ? 
                                                AND user_id = ? 
                                                AND school_id = ?
                                                LIMIT 1");
                    $selectStmt->bind_param("sii", $qrCode, $user_id, $school_id);
                    $selectStmt->execute();
                    $result = $selectStmt->get_result();
                    $studentData = $result->fetch_assoc();
                    
                    if ($studentData !== null) {
                        // Found student with old QR format, proceed with attendance recording
                        $studentID = $studentData["tbl_student_id"];
                        $studentName = $studentData["student_name"];
                        
                        // Use default instructor_id for old format
                        $currentInstructorId = $_SESSION['current_instructor_id'] ?? 1;
                        
                        // Skip to attendance recording
                        goto record_attendance;
                    } else {
                        $errorParams = http_build_query([
                            'error' => 'invalid_qr',
                            'message' => 'QR code not found',
                            'details' => 'This QR code is not registered in the system.',
                            'timestamp' => time()
                        ]);
                        echo "<script>
                            window.location.href = 'http://localhost/Qnnect/index.php?{$errorParams}';
                        </script>";
                        exit();
                    }
                } else {
                    $errorParams = http_build_query([
                        'error' => 'invalid_qr_format',
                        'message' => 'Invalid QR code format',
                        'details' => 'Expected format: course|section|instructor_id or STU-user_id-school_id-hash-random',
                        'timestamp' => time()
                    ]);
                    echo "<script>
                        window.location.href = 'http://localhost/Qnnect/index.php?{$errorParams}';
                    </script>";
                    exit();
                }
            } else {
                $errorParams = http_build_query([
                    'error' => 'invalid_qr_format',
                    'message' => 'Invalid QR code format',
                    'details' => 'Expected format: course|section|instructor_id or STU-user_id-school_id-hash-random',
                    'timestamp' => time()
                ]);
                echo "<script>
                    window.location.href = 'http://localhost/Qnnect/index.php?{$errorParams}';
                </script>";
                exit();
            }
            
            $course_code = $qr_parts[0];
            $section = $qr_parts[1];
            $qr_instructor_id = $qr_parts[2];
            
            // Use instructor_id from QR code if available, otherwise use session
            if (!empty($qr_instructor_id)) {
                $currentInstructorId = intval($qr_instructor_id);
                error_log('Using instructor_id from QR code: ' . $currentInstructorId);
            }
            
            // Step 1: Verify student by course and section with strict multi-tenant filtering
            // Try multiple approaches to find the student based on QR code format
            
            // Approach 1: Look for exact course_section match (e.g., "BSIT-1A")
            $course_section_match = $course_code . '-' . $section;
            $selectStmt = $conn_qr->prepare("SELECT tbl_student_id, student_name FROM tbl_student 
                                        WHERE course_section = ? 
                                        AND user_id = ? 
                                        AND school_id = ?
                                        LIMIT 1");
            $selectStmt->bind_param("sii", $course_section_match, $user_id, $school_id);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            $studentData = $result->fetch_assoc();
            
            // Approach 2: If no exact match, try with space format (e.g., "BSIT 1A")
            if ($studentData === null) {
                $course_section_space = $course_code . ' ' . $section;
                $selectStmt = $conn_qr->prepare("SELECT tbl_student_id, student_name FROM tbl_student 
                                            WHERE course_section = ? 
                                            AND user_id = ? 
                                            AND school_id = ?
                                            LIMIT 1");
                $selectStmt->bind_param("sii", $course_section_space, $user_id, $school_id);
                $selectStmt->execute();
                $result = $selectStmt->get_result();
                $studentData = $result->fetch_assoc();
            }
            
            // Approach 3: If still no match, try looking for section only (for database-driven entries)
            if ($studentData === null) {
                $selectStmt = $conn_qr->prepare("SELECT tbl_student_id, student_name FROM tbl_student 
                                            WHERE course_section = ? 
                                            AND user_id = ? 
                                            AND school_id = ?
                                            LIMIT 1");
                $selectStmt->bind_param("sii", $section, $user_id, $school_id);
                $selectStmt->execute();
                $result = $selectStmt->get_result();
                $studentData = $result->fetch_assoc();
            }
            
            // Approach 4: If still no match, try looking for course_code only
            if ($studentData === null) {
                $selectStmt = $conn_qr->prepare("SELECT tbl_student_id, student_name FROM tbl_student 
                                            WHERE course_section LIKE ? 
                                            AND user_id = ? 
                                            AND school_id = ?
                                            LIMIT 1");
                $course_pattern = $course_code . '%';
                $selectStmt->bind_param("sii", $course_pattern, $user_id, $school_id);
                $selectStmt->execute();
                $result = $selectStmt->get_result();
                $studentData = $result->fetch_assoc();
            }

            if ($studentData !== null) {
                $studentID = $studentData["tbl_student_id"];
                $studentName = $studentData["student_name"];

                record_attendance:
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
                    error_log("Attendance processing");
                    
                    $result = $stmt->execute();
                    
                    // Enhanced success/failure logging
                    if ($result) {
                        error_log("SUCCESS: INSERT executed successfully");
                    } else {
                        error_log("FAILED: INSERT error: " . $stmt->error);
                        error_log("FAILED: MySQL error: " . $conn_qr->error);
                    }
                    
                    if ($result) {
                        $inserted_id = $conn_qr->insert_id;
                        error_log("SUCCESS: Inserted attendance ID: $inserted_id");
                        
                        // Success redirect with context
                        $successParams = http_build_query([
                            'success' => 'attendance_added',
                            'student' => $studentName,
                            'status' => $status,
                            'id' => $inserted_id
                        ]);
                        header("Location: http://localhost/Qnnect/index.php?$successParams");
                        exit();
                    } else {
                        error_log("FAILED: INSERT error: " . $stmt->error);
                        
                        $errorParams = http_build_query([
                            'error' => 'db_insert_failed',
                            'message' => 'Failed to save attendance',
                            'details' => 'Unable to save attendance record to database. Please try again.',
                            'mode' => $attendanceMode
                        ]);
                        header("Location: http://localhost/Qnnect/index.php?$errorParams");
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
                        'details' => "Student $studentName already marked $attendanceStatus on $attendanceDate at $attendanceTime for $currentSubjectName."
                    ]);
                    echo "<script>
                        window.location.href = 'http://localhost/Qnnect/index.php?{$errorParams}';
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
                    window.location.href = 'http://localhost/Qnnect/index.php?{$errorParams}';
                </script>";
                exit();
            }
        } catch (Exception $e) {
            error_log("Database Exception in add-attendance: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Check if it's a database connection error
            $errorMessage = 'Database connection error';
            $errorDetails = 'Unable to connect to the database. Please try again.';
            
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                $errorMessage = 'Database not found';
                $errorDetails = 'The required database does not exist. Please run the database setup script.';
            } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
                $errorMessage = 'Database access denied';
                $errorDetails = 'Unable to access the database. Please check database credentials.';
            } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
                $errorMessage = 'Database connection refused';
                $errorDetails = 'Unable to connect to MySQL. Please check if XAMPP is running.';
            }
            
            $errorParams = http_build_query([
                'error' => 'db_error',
                'message' => $errorMessage,
                'details' => $errorDetails,
                'mode' => $attendanceMode
            ]);
            echo "<script>
                window.location.href = 'http://localhost/Qnnect/index.php?{$errorParams}';
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
            window.location.href = 'http://localhost/Qnnect/index.php?{$errorParams}';
        </script>";
        exit();
    }
}
?>
