<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set a flag to prevent session recreation after logout
$_SESSION['logging_out'] = true;

// Attempt to terminate any active class/attendance sessions before destroying the session
try {
    // Include database connection for attendance DB if available
    $dbPath = __DIR__ . '/conn/db_connect.php';
    if (file_exists($dbPath)) {
        require_once $dbPath; // should provide $conn_qr (mysqli)
    }

    // Get identifiers from session if present
    $school_id = isset($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;

    // Clear class/attendance related session variables
    unset($_SESSION['class_start_time']);
    unset($_SESSION['class_start_time_formatted']);
    unset($_SESSION['current_instructor_id']);
    unset($_SESSION['current_instructor_name']);
    unset($_SESSION['current_subject_id']);
    unset($_SESSION['current_subject_name']);
    unset($_SESSION['attendance_session_id']);
    unset($_SESSION['attendance_session_start']);
    unset($_SESSION['attendance_session_end']);

    // If attendance DB connection is available, update any active sessions for this school
    if (isset($conn_qr) && $conn_qr) {
        // Use range filtering instead of DATE() for better index utilization
        $current_date = date('Y-m-d');
        $start_of_day = $current_date . ' 00:00:00';
        $start_of_next_day = date('Y-m-d', strtotime($current_date . ' +1 day')) . ' 00:00:00';
        $now_ts = date('Y-m-d H:i:s');

        // Determine if attendance_sessions has school_id column
        $has_school_id = false;
        if ($result = mysqli_query($conn_qr, "SHOW COLUMNS FROM attendance_sessions LIKE 'school_id'")) {
            $has_school_id = mysqli_num_rows($result) > 0;
            mysqli_free_result($result);
        }

        if ($has_school_id) {
            $update_query = "
                UPDATE attendance_sessions 
                SET end_time = NOW(), 
                    status = 'terminated' 
                WHERE school_id = ? 
                AND start_time >= ? 
                AND start_time < ? 
                AND end_time >= ? 
                AND status = 'active'
            ";
            if ($stmt = $conn_qr->prepare($update_query)) {
                $stmt->bind_param('isss', $school_id, $start_of_day, $start_of_next_day, $now_ts);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $update_query = "
                UPDATE attendance_sessions 
                SET end_time = NOW(), 
                    status = 'terminated' 
                WHERE start_time >= ? 
                AND start_time < ? 
                AND end_time >= ? 
                AND status = 'active'
            ";
            if ($stmt = $conn_qr->prepare($update_query)) {
                $stmt->bind_param('sss', $start_of_day, $start_of_next_day, $now_ts);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Also clear any class_time_settings start time (handles both start_time and legacy class_start_time)
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
            $sql = "UPDATE class_time_settings SET {$column_name} = NULL, updated_at = NOW() WHERE school_id = ? AND {$column_name} IS NOT NULL";
            if ($stmt = $conn_qr->prepare($sql)) {
                $stmt->bind_param('i', $school_id);
                $stmt->execute();
                $stmt->close();
            }

            // If both columns exist, clear the other as well
            $other = $column_name === 'start_time' ? 'class_start_time' : 'start_time';
            if ($res = mysqli_query($conn_qr, "SHOW COLUMNS FROM class_time_settings LIKE '{$other}'")) {
                if (mysqli_num_rows($res) > 0) {
                    $sql2 = "UPDATE class_time_settings SET {$other} = NULL, updated_at = NOW() WHERE school_id = ? AND {$other} IS NOT NULL";
                    if ($stmt2 = $conn_qr->prepare($sql2)) {
                        $stmt2->bind_param('i', $school_id);
                        $stmt2->execute();
                        $stmt2->close();
                    }
                }
                mysqli_free_result($res);
            }
        }

        // NOTE: Teacher schedules should NOT be set to inactive on logout
        // They are permanent schedule templates, not active class sessions
        // Only class time settings and attendance sessions should be terminated
    }
} catch (Throwable $e) {
    // Fail-safe: continue logout even if termination fails
}

// Complete session destruction
$_SESSION = array();

// Delete the session cookie completely
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
    
    // Also clear the QR_ATTENDANCE_SESSION cookie specifically
    setcookie('QR_ATTENDANCE_SESSION', '', time() - 42000, '/', '', false, true);
}

// Destroy the session
session_destroy();

// Clear any other potential session cookies
setcookie('PHPSESSID', '', time() - 42000, '/');

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page with cache-busting parameter
header("Location: admin/login.php?logout=1&t=" . time());
exit();
?> 