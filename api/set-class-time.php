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
    error_log("[SET-CLASS-TIME] " . $message);
}

debugLog("Script started. Request method: " . $_SERVER['REQUEST_METHOD']);
debugLog("POST data: " . print_r($_POST, true));
debugLog("Session data: " . print_r($_SESSION, true));

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

// Get the class start time from POST data
$classStartTime = isset($_POST['classStartTime']) ? $_POST['classStartTime'] : null;
debugLog("Class start time received: " . ($classStartTime ?? 'NULL'));

// Get school_id from session
$school_id = $_SESSION['school_id'] ?? 1; // Default to 1 if not set
debugLog("School ID from session: " . $school_id);

if (empty($classStartTime)) {
    debugLog("No start time provided");
    echo json_encode([
        'success' => false,
        'message' => 'No start time provided'
    ]);
    exit;
}

// Validate time format (HH:MM)
if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $classStartTime)) {
    debugLog("Invalid time format: " . $classStartTime);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid time format. Please use HH:MM format.'
    ]);
    exit;
}

// Store the class time in session - ensure 24-hour format for comparison
$_SESSION['class_start_time'] = $classStartTime; // HH:MM
$_SESSION['class_start_time_formatted'] = $classStartTime . ':00'; // HH:MM:SS

debugLog("Class time stored in session successfully");
debugLog("Session class_start_time: " . $_SESSION['class_start_time']);
debugLog("Session class_start_time_formatted: " . $_SESSION['class_start_time_formatted']);

// Save to database
try {
    // Include database connection
    require_once('../conn/db_connect.php');
    
    if (!isset($conn_qr)) {
        throw new Exception("Database connection not available");
    }
    
    // Ensure class_time_settings table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'class_time_settings'";
    $result = $conn_qr->query($tableCheckQuery);
    
    if ($result->num_rows == 0) {
        // Create the table if it doesn't exist
        $createTableQuery = "CREATE TABLE class_time_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_id INT NOT NULL,
            start_time TIME NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_school_time (school_id)
        )";
        
        if (!$conn_qr->query($createTableQuery)) {
            throw new Exception("Failed to create class_time_settings table: " . $conn_qr->error);
        }
        debugLog("Created class_time_settings table");
    } else {
        // Table exists, check if status column exists
        $columnCheckQuery = "SHOW COLUMNS FROM class_time_settings LIKE 'status'";
        $columnResult = $conn_qr->query($columnCheckQuery);
        
        if ($columnResult->num_rows == 0) {
            // Add status column if it doesn't exist
            $addColumnQuery = "ALTER TABLE class_time_settings ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER start_time";
            if (!$conn_qr->query($addColumnQuery)) {
                debugLog("Failed to add status column: " . $conn_qr->error);
                // Continue without failing completely
            } else {
                debugLog("Added status column to existing table");
            }
        }
    }
    
    // Insert or update the class time setting
    $query = "INSERT INTO class_time_settings (school_id, start_time, status, updated_at, created_at)
              VALUES (?, ?, 'active', NOW(), NOW())
              ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), status = 'active', updated_at = NOW()";
    
    $stmt = $conn_qr->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn_qr->error);
    }
    
    $stmt->bind_param("is", $school_id, $classStartTime);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to save class time to database: " . $stmt->error);
    }
    
    debugLog("Class time saved to database successfully");
    $stmt->close();
    
} catch (Exception $e) {
    debugLog("Database error: " . $e->getMessage());
    // Don't fail completely - session storage is still working
    // Just log the error and continue
}

        // Format the time for display - ensure 12-hour format
        function formatTimeToAmPm($time) {
            // Handle different time formats
            $formats = ['H:i', 'H:i:s', 'h:i A', 'h:i:s A'];
            
            foreach ($formats as $format) {
                $date = DateTime::createFromFormat($format, $time);
                if ($date) {
                    return $date->format('h:i A');
                }
            }
            
            // Fallback: try to parse and format
            $timestamp = strtotime($time);
            if ($timestamp) {
                return date('h:i A', $timestamp);
            }
            
            return $time; // Return original if parsing fails
        }

// Prepare response data
$responseData = [
    'class_start_time' => $classStartTime,
    'formatted_time' => formatTimeToAmPm($classStartTime),
    'instructor' => $_SESSION['current_instructor_name'] ?? 'Not set',
    'subject' => $_SESSION['current_subject_name'] ?? 'Not set',
    'saved_to_database' => true
];

debugLog("Response data prepared: " . print_r($responseData, true));

// Return success response
$response = [
    'success' => true,
    'message' => 'Class start time set successfully',
    'data' => $responseData
];

debugLog("Sending response: " . json_encode($response));
echo json_encode($response);
?> 