<?php
/**
 * Activity Log Helper - Centralized functions for logging system activities
 * 
 * Include this file in all PHP files where you want to log user actions
 */

// Include the ActivityLogger class if not already included
if (!class_exists('ActivityLogger')) {
    require_once(__DIR__ . '/ActivityLogger.php');
}

/**
 * Log a user activity
 * 
 * @param string $action_type The type of action (e.g., 'login', 'logout', 'update', 'delete', etc.)
 * @param string $action_description A descriptive message about the action
 * @param string $affected_table Optional. The database table affected by the action
 * @param int $affected_id Optional. The ID of the affected record
 * @param array $additional_data Optional. Additional data to log in JSON format
 * @return bool True if logging was successful, false otherwise
 */
function logActivity($action_type, $action_description, $affected_table = null, $affected_id = null, $additional_data = null) {
    global $conn_qr; // Use the global QR database connection
    
    // Check if the connection is available
    if (!isset($conn_qr) || !$conn_qr) {
        // Try to include the database connection if not already included
        if (!function_exists('getConnection')) {
            $base_path = dirname(__DIR__);
            require_once($base_path . '/conn/db_connect.php');
        }
        $conn_qr = getConnection('qr');
    }
    
    // Get current user ID from session
    $current_user_id = null;
    if (isset($_SESSION['email'])) {
        // Connect to login database to get user ID
        $conn_login = mysqli_connect("localhost", "root", "", "login_register");
        if ($conn_login) {
            $user_query = "SELECT id FROM users WHERE email = ?";
            $user_stmt = $conn_login->prepare($user_query);
            $user_stmt->bind_param("s", $_SESSION['email']);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            if ($user_result && $user_result->num_rows > 0) {
                $user_data = $user_result->fetch_assoc();
                $current_user_id = $user_data['id'];
            }
            // Safely close the connection
            if (isset($conn_login) && $conn_login instanceof mysqli) {
                try {
                    if ($conn_login->ping()) {
                        $conn_login->close();
                    }
                } catch (Throwable $e) {
                    // Connection is already closed or invalid, do nothing
                }
            }
        }
    }
    
    // Initialize ActivityLogger
    $activity_logger = new ActivityLogger($conn_qr, $current_user_id);
    
    // Log the activity
    return $activity_logger->log(
        $action_type,
        $action_description,
        $affected_table,
        $affected_id,
        $additional_data
    );
}

/**
 * Helper function to check if the activity_logs table exists and create it if not
 */
function ensureActivityLogsTableExists() {
    global $conn_qr;
    
    // Check if the connection is available
    if (!isset($conn_qr) || !$conn_qr) {
        // Try to include the database connection if not already included
        if (!function_exists('getConnection')) {
            $base_path = dirname(__DIR__);
            require_once($base_path . '/conn/db_connect.php');
        }
        $conn_qr = getConnection('qr');
    }
    
    // Check if table exists
    $check_table = mysqli_query($conn_qr, "SHOW TABLES LIKE 'activity_logs'");
    if (mysqli_num_rows($check_table) == 0) {
        // Create the table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action_type VARCHAR(50) NOT NULL,
            action_description VARCHAR(255) NOT NULL,
            affected_table VARCHAR(50) NULL,
            affected_id INT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            additional_data TEXT NULL,
            school_id INT DEFAULT 1
        )";
        mysqli_query($conn_qr, $create_table);
    }
}

// Ensure the activity_logs table exists whenever this file is included
ensureActivityLogsTableExists();
?> 