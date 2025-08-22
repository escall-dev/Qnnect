<?php
/**
 * Functions to record user login and logout activities
 */

/**
 * Record a user login event
 */
function recordUserLogin($conn, $username, $email = '', $user_type = 'User') {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_id = $_SESSION['user_id'] ?? 0;
    $school_id = $_SESSION['school_id'] ?? 0;
    
    // Use query with user_id and school_id for data isolation
    $query = "INSERT INTO tbl_user_logs (username, user_type, log_in_time, ip_address, user_id, school_id) 
              VALUES (?, ?, NOW(), ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssii", $username, $user_type, $ip_address, $user_id, $school_id);
    
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        return mysqli_insert_id($conn);
    }
    return false;
}

/**
 * Record a user logout event
 */
function recordUserLogout($conn, $username) {
    // First try to use log_id if available (most reliable)
    if (isset($_SESSION['log_id'])) {
        $query = "UPDATE tbl_user_logs 
                 SET log_out_time = NOW() 
                 WHERE log_id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['log_id']);
    } else {
        // Fall back to username-based logout
        $query = "UPDATE tbl_user_logs 
                 SET log_out_time = NOW() 
                 WHERE username = ? 
                 AND log_out_time IS NULL 
                 ORDER BY log_id DESC 
                 LIMIT 1";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
    }
    
    $result = mysqli_stmt_execute($stmt);
    
    // For debugging
    if (!$result) {
        error_log("Error recording logout: " . mysqli_error($conn));
    } else {
        $affected = mysqli_stmt_affected_rows($stmt);
        error_log("Logout recorded for user: " . $username . " (Rows affected: " . $affected . ")");
    }
    
    return $result;
}

