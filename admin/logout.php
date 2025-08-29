<?php
require_once '../includes/session_config.php';

// Set a flag to prevent session recreation after logout
$_SESSION['logging_out'] = true;

// CRITICAL: Extract session data BEFORE any database operations
$username = isset($_SESSION['username']) ? trim($_SESSION['username']) : null;
$profile_image = isset($_SESSION['profile_image']) ? trim($_SESSION['profile_image']) : null;
// Capture role early for redirect decision later
$role_before_logout = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Include database connection
require_once "database.php";

// Only proceed if we have a valid username and database connection
if ($username && !empty($username) && $conn) {
    
    // Ensure recent_logins table exists
    $checkTable = "SHOW TABLES LIKE 'recent_logins'";
    $result = mysqli_query($conn, $checkTable);
    
    if (mysqli_num_rows($result) == 0) {
        // Create table if it doesn't exist
        $createTable = "CREATE TABLE recent_logins (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(255) UNIQUE NOT NULL,
            profile_image VARCHAR(255) DEFAULT NULL,
            last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_last_login (last_login),
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        mysqli_query($conn, $createTable);
    }
    
    // Store/update the profile
    $sql = "INSERT INTO recent_logins (username, profile_image, last_login) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            profile_image = VALUES(profile_image), 
            last_login = NOW()";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $profile_image);
        mysqli_stmt_execute($stmt);
    }
}

// Attempt to terminate any active class/attendance sessions before destroying the session
try {
    // Include attendance DB connection if available
    $dbPath = dirname(__DIR__) . '/conn/db_connect.php';
    if (file_exists($dbPath)) {
        require_once $dbPath; // provides $conn_qr (mysqli)
    }

    $school_id = isset($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $email = $_SESSION['email'] ?? $_SESSION['username'] ?? 'unknown';

    // Log the termination attempt
    error_log("[ADMIN-LOGOUT] Terminating class time activation for user: $email, school_id: $school_id");

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

    if (isset($conn_qr) && $conn_qr) {
        // Avoid DATE() in WHERE to leverage indexes
        $current_date = date('Y-m-d');
        $start_of_day = $current_date . ' 00:00:00';
        $start_of_next_day = date('Y-m-d', strtotime($current_date . ' +1 day')) . ' 00:00:00';
        $now_ts = date('Y-m-d H:i:s');

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

        // Clear class_time_settings start time (supports both 'start_time' and legacy 'class_start_time')
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
                $affected_rows = $stmt->affected_rows;
                error_log("[ADMIN-LOGOUT] Cleared class_time_settings.{$column_name} for $affected_rows row(s) (school_id: $school_id)");
                $stmt->close();
            }

            // If both columns exist, clear the other too
            $other = $column_name === 'start_time' ? 'class_start_time' : 'start_time';
            if ($res = mysqli_query($conn_qr, "SHOW COLUMNS FROM class_time_settings LIKE '{$other}'")) {
                if (mysqli_num_rows($res) > 0) {
                    $sql2 = "UPDATE class_time_settings SET {$other} = NULL, updated_at = NOW() WHERE school_id = ? AND {$other} IS NOT NULL";
                    if ($stmt2 = $conn_qr->prepare($sql2)) {
                        $stmt2->bind_param('i', $school_id);
                        $stmt2->execute();
                        $affected_rows2 = $stmt2->affected_rows;
                        error_log("[ADMIN-LOGOUT] Cleared class_time_settings.{$other} for $affected_rows2 row(s) (school_id: $school_id)");
                        $stmt2->close();
                    }
                }
                mysqli_free_result($res);
            }
        } else {
            error_log("[ADMIN-LOGOUT] class_time_settings table does not have start_time or class_start_time columns");
        }

        // NOTE: Teacher schedules should NOT be set to inactive on logout
        // They are permanent schedule templates, not active class sessions
        // Only class time settings and attendance sessions should be terminated
        error_log("[ADMIN-LOGOUT] Teacher schedules preserved (not terminated on logout)");

        // Remove blocking HTTP calls during logout to speed up UX
        // Admin logout already performs necessary DB cleanup directly above
    }
} catch (Throwable $e) {
    // Continue logout even if termination fails
    error_log("[ADMIN-LOGOUT] Error during class time termination: " . $e->getMessage());
}

// Log successful termination
error_log("[ADMIN-LOGOUT] Class time activation termination completed for user: $email, school_id: $school_id");

// Complete session destruction
$_SESSION = array();

// Delete the session cookie completely
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
    
    // Also clear the admin & super admin session cookies specifically
    setcookie('QR_ATTENDANCE_SESSION', '', time() - 42000, '/', '', false, true);
    setcookie('QR_ATTENDANCE_SA_SESSION', '', time() - 42000, '/', '', false, true);
}

// Destroy the session
session_destroy();

// Clear any other potential session cookies
setcookie('PHPSESSID', '', time() - 42000, '/');

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to appropriate login portal based on role captured before session was cleared
$redirect = ($role_before_logout === 'super_admin') ? 'super_admin_login.php' : 'login.php';
header("Location: " . $redirect . "?logout=1&t=" . time());
exit();
     