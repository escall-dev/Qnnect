<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session with fallback
if (session_status() === PHP_SESSION_NONE) {
    // Try to start session with different names to find existing session
    $session_names = ['QR_ATTENDANCE_SESSION', 'PHPSESSID'];
    $session_started = false;
    
    foreach ($session_names as $session_name) {
        session_name($session_name);
        session_start();
        if (!empty($_SESSION)) {
            debugLog("Found session with name: $session_name");
            $session_started = true;
            break;
        }
        session_write_close();
    }
    
    if (!$session_started) {
        // Start fresh session if none found
        session_name('QR_ATTENDANCE_SESSION');
        session_start();
        debugLog("Started fresh session");
    }
}

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Debug logging function
function debugLog($message) {
    error_log("[TERMINATE-CLASS-SESSION] " . $message);
}

debugLog("Script started. Request method: " . $_SERVER['REQUEST_METHOD']);
debugLog("Session ID: " . session_id());
debugLog("Session status: " . session_status());
debugLog("Session data before termination: " . print_r($_SESSION, true));

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog("Invalid request method");
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method',
        'debug' => 'Expected POST, got ' . $_SERVER['REQUEST_METHOD']
    ]);
    exit;
}

// Authorization: prefer allowing termination if we have a valid school context
$hasUserIdentity = isset($_SESSION['email']) || isset($_SESSION['username']) || isset($_SESSION['user_id']);
$hasSchoolContext = isset($_SESSION['school_id']);
if (!$hasUserIdentity && !$hasSchoolContext) {
    debugLog("No user identity or school context found in session");
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access - missing session context'
    ]);
    exit;
}

try {
    // Include database connection
    require_once('../conn/db_connect.php');
    
    // Get current user's school_id and user_id from session with fallbacks
    $school_id = $_SESSION['school_id'] ?? 1; // Default to 1 if not set
    $user_id = $_SESSION['user_id'] ?? null;
    $email = $_SESSION['email'] ?? $_SESSION['username'] ?? 'unknown';
    
    debugLog("Current user - School ID: $school_id, User ID: $user_id, Email/Username: $email");
    
    // If we don't have essential session data, try to recover or fail gracefully
    if (!$user_id && !$email) {
        debugLog("Critical: No user identification found in session");
        echo json_encode([
            'success' => false,
            'message' => 'Session error - Please refresh the page and try again'
        ]);
        exit;
    }
    
    // Store current session info for logging
    $previous_class_time = $_SESSION['class_start_time'] ?? null;
    $previous_instructor = $_SESSION['current_instructor_name'] ?? null;
    $previous_subject = $_SESSION['current_subject_name'] ?? null;
    
    // Clear class time related session variables (these are user-specific)
    unset($_SESSION['class_start_time']);
    unset($_SESSION['class_start_time_formatted']);
    unset($_SESSION['current_instructor_id']);
    unset($_SESSION['current_instructor_name']);
    unset($_SESSION['current_subject_id']);
    unset($_SESSION['current_subject_name']);
    
    // Also clear any attendance session related variables
    unset($_SESSION['attendance_session_id']);
    unset($_SESSION['attendance_session_start']);
    unset($_SESSION['attendance_session_end']);
    
    debugLog("Session variables cleared successfully");
    
    // Update database if there's an active attendance session
    if (isset($conn_qr)) {
        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');
        
        // Check if school_id column exists in attendance_sessions table
        $check_column = "SHOW COLUMNS FROM attendance_sessions LIKE 'school_id'";
        $column_result = mysqli_query($conn_qr, $check_column);
        $has_school_id = mysqli_num_rows($column_result) > 0;
        
        if ($has_school_id) {
            // Find and update any active attendance sessions for the current user's school
            $update_query = "
                UPDATE attendance_sessions 
                SET end_time = NOW(), 
                    status = 'terminated' 
                WHERE school_id = ? 
                AND DATE(start_time) = ? 
                AND end_time >= ? 
                AND status = 'active'
            ";
            
            $stmt = $conn_qr->prepare($update_query);
            $stmt->bind_param("iss", $school_id, $current_date, $current_time);
        } else {
            // Fallback: update all active sessions (for backward compatibility)
            $update_query = "
                UPDATE attendance_sessions 
                SET end_time = NOW(), 
                    status = 'terminated' 
                WHERE DATE(start_time) = ? 
                AND end_time >= ? 
                AND status = 'active'
            ";
            
            $stmt = $conn_qr->prepare($update_query);
            $stmt->bind_param("ss", $current_date, $current_time);
        }
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            debugLog("Updated $affected_rows active attendance sessions for school_id: $school_id");
        } else {
            debugLog("Database update error: " . $stmt->error);
        }
        
        // Also update class_time_settings table to clear any active class start time
        try {
            // Detect which column name is present: 'start_time' (new) or 'class_start_time' (legacy)
            $column_name = null;
            if ($res = mysqli_query($conn_qr, "SHOW COLUMNS FROM class_time_settings LIKE 'start_time'")) {
                if (mysqli_num_rows($res) > 0) { $column_name = 'start_time'; }
                mysqli_free_result($res);
            }
            if ($column_name === null) {
                if ($res = mysqli_query($conn_qr, "SHOW COLUMNS FROM class_time_settings LIKE 'class_start_time'")) {
                    if (mysqli_num_rows($res) > 0) { $column_name = 'class_start_time'; }
                    mysqli_free_result($res);
                }
            }

            if ($column_name !== null) {
                $class_time_update_query = "
                    UPDATE class_time_settings 
                    SET {$column_name} = NULL,
                        updated_at = NOW()
                    WHERE school_id = ? 
                    AND {$column_name} IS NOT NULL
                ";
                if ($class_time_stmt = $conn_qr->prepare($class_time_update_query)) {
                    $class_time_stmt->bind_param("i", $school_id);
                    if ($class_time_stmt->execute()) {
                        $class_time_affected = $class_time_stmt->affected_rows;
                        debugLog("Cleared class_time_settings.{$column_name} for $class_time_affected row(s) (school_id: $school_id)");
                    }
                    $class_time_stmt->close();
                }

                // If both columns exist (rare), clear the other one as well to avoid stale data
                $other_column = $column_name === 'start_time' ? 'class_start_time' : 'start_time';
                if ($res = mysqli_query($conn_qr, "SHOW COLUMNS FROM class_time_settings LIKE '{$other_column}'")) {
                    if (mysqli_num_rows($res) > 0) {
                        $sql = "UPDATE class_time_settings SET {$other_column} = NULL, updated_at = NOW() WHERE school_id = ? AND {$other_column} IS NOT NULL";
                        if ($stmt2 = $conn_qr->prepare($sql)) {
                            $stmt2->bind_param("i", $school_id);
                            $stmt2->execute();
                            $stmt2->close();
                        }
                    }
                    mysqli_free_result($res);
                }
            } else {
                debugLog("class_time_settings does not have start_time or class_start_time columns");
            }
        } catch (Exception $e) {
            debugLog("Class time settings update skipped (table may not exist): " . $e->getMessage());
        }

        // CRITICAL: Set teacher_schedules status to 'inactive' to fully terminate class sessions
        try {
            $teacher_schedule_update_query = "
                UPDATE teacher_schedules 
                SET status = 'inactive',
                    updated_at = NOW()
                WHERE school_id = ? 
                AND status = 'active'
            ";
            if ($teacher_stmt = $conn_qr->prepare($teacher_schedule_update_query)) {
                $teacher_stmt->bind_param("i", $school_id);
                if ($teacher_stmt->execute()) {
                    $teacher_affected = $teacher_stmt->affected_rows;
                    debugLog("Set teacher_schedules status to 'inactive' for $teacher_affected row(s) (school_id: $school_id)");
                }
                $teacher_stmt->close();
            }
        } catch (Exception $e) {
            debugLog("Teacher schedules status update skipped (table may not exist): " . $e->getMessage());
        }
    }
    
    debugLog("Session data after termination: " . print_r($_SESSION, true));
debugLog("Session cookie path: " . session_get_cookie_params()['path']);
debugLog("Session cookie domain: " . session_get_cookie_params()['domain']);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Class session terminated successfully',
        'data' => [
            'terminated_at' => date('Y-m-d H:i:s'),
            'previous_class_time' => $previous_class_time,
            'previous_instructor' => $previous_instructor,
            'previous_subject' => $previous_subject,
            'school_id' => $school_id,
            'user_id' => $user_id,
            'email' => $email
        ]
    ]);
    
} catch (Exception $e) {
    debugLog("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error terminating class session: ' . $e->getMessage()
    ]);
}
?> 