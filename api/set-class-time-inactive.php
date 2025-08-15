<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use unified session configuration
require_once('../includes/session_config.php');

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Debug logging function
function debugLog($message) {
    error_log("[SET-CLASS-TIME-INACTIVE] " . $message);
}

debugLog("Script started. Request method: " . $_SERVER['REQUEST_METHOD']);

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

// Get school_id from session
$school_id = $_SESSION['school_id'] ?? 1; // Default to 1 if not set
$user_id = $_SESSION['user_id'] ?? null;
$email = $_SESSION['email'] ?? $_SESSION['username'] ?? 'unknown';

debugLog("School ID from session: " . $school_id);
debugLog("User ID from session: " . $user_id);
debugLog("Email from session: " . $email);

try {
    // Include database connection
    require_once('../conn/db_connect.php');
    
    if (!isset($conn_qr)) {
        throw new Exception("Database connection not available");
    }
    
    // First, check if the class_time_settings table exists
    $checkTableExists = "SHOW TABLES LIKE 'class_time_settings'";
    $tableExists = $conn_qr->query($checkTableExists)->num_rows > 0;
    
    if (!$tableExists) {
        debugLog("class_time_settings table does not exist, creating it");
        $createTableQuery = "CREATE TABLE class_time_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_id INT NOT NULL,
            start_time TIME NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_school_time (school_id)
        )";
        
        if (!$conn_qr->query($createTableQuery)) {
            throw new Exception("Failed to create class_time_settings table: " . $conn_qr->error);
        }
        debugLog("Successfully created class_time_settings table");
    }
    
    // Check if the table has a status column
    $checkStatusColumn = "SHOW COLUMNS FROM class_time_settings LIKE 'status'";
    $statusResult = $conn_qr->query($checkStatusColumn);
    $hasStatusColumn = $statusResult->num_rows > 0;
    
    if (!$hasStatusColumn) {
        // Add status column if it doesn't exist
        $addStatusColumn = "ALTER TABLE class_time_settings ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER start_time";
        if (!$conn_qr->query($addStatusColumn)) {
            debugLog("Failed to add status column: " . $conn_qr->error);
        } else {
            debugLog("Successfully added status column to class_time_settings table");
            $hasStatusColumn = true;
        }
    }
    
    // Store current class time before clearing
    $current_class_time = null;
    $current_instructor = null;
    $current_subject = null;
    
    // Check which columns exist in the table
    $checkStartTimeColumn = "SHOW COLUMNS FROM class_time_settings LIKE 'start_time'";
    $checkClassStartTimeColumn = "SHOW COLUMNS FROM class_time_settings LIKE 'class_start_time'";
    
    $startTimeExists = $conn_qr->query($checkStartTimeColumn)->num_rows > 0;
    $classStartTimeExists = $conn_qr->query($checkClassStartTimeColumn)->num_rows > 0;
    
    debugLog("start_time column exists: " . ($startTimeExists ? 'yes' : 'no'));
    debugLog("class_start_time column exists: " . ($classStartTimeExists ? 'yes' : 'no'));
    
    // Get current class time settings
    $getCurrentQuery = "SELECT start_time FROM class_time_settings WHERE school_id = ? AND start_time IS NOT NULL";
    if ($classStartTimeExists) {
        $getCurrentQuery = "SELECT start_time, class_start_time FROM class_time_settings WHERE school_id = ? AND (start_time IS NOT NULL OR class_start_time IS NOT NULL)";
    }
    
    $getStmt = $conn_qr->prepare($getCurrentQuery);
    if ($getStmt) {
        $getStmt->bind_param("i", $school_id);
        $getStmt->execute();
        $result = $getStmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $current_class_time = $row['start_time'] ?? $row['class_start_time'] ?? null;
            $current_instructor = $_SESSION['current_instructor_name'] ?? 'Unknown';
            $current_subject = $_SESSION['current_subject_name'] ?? 'Unknown';
        }
        $getStmt->close();
    }
    
    // Build update query based on existing columns
    $updateFields = ["status = 'inactive'", "updated_at = NOW()"];
    
    if ($startTimeExists) {
        $updateFields[] = "start_time = NULL";
    }
    
    if ($classStartTimeExists) {
        $updateFields[] = "class_start_time = NULL";
    }
    
    $updateQuery = "UPDATE class_time_settings SET " . implode(", ", $updateFields) . " WHERE school_id = ?";
    
    debugLog("Update query: " . $updateQuery);
    
    // Check if there's a record to update
    $checkRecordQuery = "SELECT COUNT(*) as count FROM class_time_settings WHERE school_id = ?";
    $checkStmt = $conn_qr->prepare($checkRecordQuery);
    if ($checkStmt) {
        $checkStmt->bind_param("i", $school_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $recordCount = $checkResult->fetch_assoc()['count'];
        $checkStmt->close();
        
        debugLog("Found $recordCount record(s) for school_id: $school_id");
        
        if ($recordCount == 0) {
            // No record exists, create one with inactive status
            $insertQuery = "INSERT INTO class_time_settings (school_id, status, created_at, updated_at) VALUES (?, 'inactive', NOW(), NOW())";
            $insertStmt = $conn_qr->prepare($insertQuery);
            if ($insertStmt) {
                $insertStmt->bind_param("i", $school_id);
                if ($insertStmt->execute()) {
                    debugLog("Created new record with inactive status for school_id: $school_id");
                    $affected_rows = 1;
                } else {
                    throw new Exception("Failed to create inactive record: " . $insertStmt->error);
                }
                $insertStmt->close();
            } else {
                throw new Exception("Failed to prepare insert statement: " . $conn_qr->error);
            }
        } else {
            // Record exists, update it
            $stmt = $conn_qr->prepare($updateQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare update statement: " . $conn_qr->error);
            }
            
            $stmt->bind_param("i", $school_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update class time settings: " . $stmt->error);
            }
            
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
        }
    } else {
        throw new Exception("Failed to prepare check statement: " . $conn_qr->error);
    }
    
    debugLog("Updated class_time_settings for $affected_rows row(s) (school_id: $school_id)");
    
    // Also clear session variables
    unset($_SESSION['class_start_time']);
    unset($_SESSION['class_start_time_formatted']);
    unset($_SESSION['current_instructor_id']);
    unset($_SESSION['current_instructor_name']);
    unset($_SESSION['current_subject_id']);
    unset($_SESSION['current_subject_name']);
    unset($_SESSION['attendance_session_id']);
    unset($_SESSION['attendance_session_start']);
    unset($_SESSION['attendance_session_end']);
    
    debugLog("Session variables cleared successfully");
    
    // Log the termination activity (optional - skip if table doesn't exist)
    try {
        // Check if activity_logs table exists
        $checkLogTable = "SHOW TABLES LIKE 'activity_logs'";
        $logTableExists = $conn_qr->query($checkLogTable)->num_rows > 0;
        
        if ($logTableExists) {
            // Check if the table has the required columns
            $checkActionColumn = "SHOW COLUMNS FROM activity_logs LIKE 'action'";
            $checkDetailsColumn = "SHOW COLUMNS FROM activity_logs LIKE 'details'";
            $checkUserIdColumn = "SHOW COLUMNS FROM activity_logs LIKE 'user_id'";
            $checkSchoolIdColumn = "SHOW COLUMNS FROM activity_logs LIKE 'school_id'";
            
            $hasActionColumn = $conn_qr->query($checkActionColumn)->num_rows > 0;
            $hasDetailsColumn = $conn_qr->query($checkDetailsColumn)->num_rows > 0;
            $hasUserIdColumn = $conn_qr->query($checkUserIdColumn)->num_rows > 0;
            $hasSchoolIdColumn = $conn_qr->query($checkSchoolIdColumn)->num_rows > 0;
            
            debugLog("Activity logs table structure - action: " . ($hasActionColumn ? 'yes' : 'no') . 
                    ", details: " . ($hasDetailsColumn ? 'yes' : 'no') . 
                    ", user_id: " . ($hasUserIdColumn ? 'yes' : 'no') . 
                    ", school_id: " . ($hasSchoolIdColumn ? 'yes' : 'no'));
            
            if ($hasActionColumn && $hasDetailsColumn && $hasUserIdColumn && $hasSchoolIdColumn) {
                $logQuery = "INSERT INTO activity_logs (user_id, school_id, action, details, created_at) VALUES (?, ?, 'class_time_inactive', ?, NOW())";
                $logStmt = $conn_qr->prepare($logQuery);
                if ($logStmt) {
                    $details = json_encode([
                        'previous_class_time' => $current_class_time,
                        'previous_instructor' => $current_instructor,
                        'previous_subject' => $current_subject,
                        'terminated_by' => $email
                    ]);
                    $logStmt->bind_param("iis", $user_id, $school_id, $details);
                    $logStmt->execute();
                    $logStmt->close();
                    debugLog("Activity logged successfully");
                }
            } else {
                debugLog("Activity logs table missing required columns, skipping logging");
            }
        } else {
            debugLog("Activity logs table does not exist, skipping logging");
        }
    } catch (Exception $e) {
        debugLog("Error during activity logging (non-critical): " . $e->getMessage());
        // Don't throw the exception - logging is optional
    }
    
    // Return success response
    $response = [
        'success' => true,
        'message' => 'Class time settings set to inactive successfully',
        'data' => [
            'terminated_at' => date('Y-m-d H:i:s'),
            'previous_class_time' => $current_class_time,
            'previous_instructor' => $current_instructor,
            'previous_subject' => $current_subject,
            'school_id' => $school_id,
            'user_id' => $user_id,
            'email' => $email,
            'affected_rows' => $affected_rows
        ]
    ];
    
    debugLog("Sending response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    debugLog("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error setting class time to inactive: ' . $e->getMessage()
    ]);
}
?> 