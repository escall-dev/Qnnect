<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use unified session configuration
require_once('../includes/session_config.php');

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Debug logging function
function debugLog($message) {
    error_log("[GET-CLASS-TIME] " . $message);
}

debugLog("Script started. Request method: " . $_SERVER['REQUEST_METHOD']);
debugLog("Session data: " . print_r($_SESSION, true));

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    debugLog("Invalid request method");
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method',
        'debug' => 'Expected GET, got ' . $_SERVER['REQUEST_METHOD']
    ]);
    exit;
}

// Get school_id from session
$school_id = $_SESSION['school_id'] ?? 1; // Default to 1 if not set
debugLog("School ID from session: " . $school_id);

try {
    // Include database connection
    require_once('../conn/db_connect.php');
    
    if (!isset($conn_qr)) {
        throw new Exception("Database connection not available");
    }
    
    // Check if class_time_settings table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'class_time_settings'";
    $result = $conn_qr->query($tableCheckQuery);
    
    if ($result->num_rows == 0) {
        debugLog("class_time_settings table does not exist");
        echo json_encode([
            'success' => false,
            'message' => 'No class time settings found',
            'data' => null
        ]);
        exit;
    }
    
    // Get the latest class time setting for this school, only if it's active
    $query = "SELECT start_time, status, updated_at FROM class_time_settings WHERE school_id = ? ORDER BY updated_at DESC LIMIT 1";
    $stmt = $conn_qr->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn_qr->error);
    }
    
    $stmt->bind_param("i", $school_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
    $classStartTime = $row['start_time'];
        $status = $row['status'] ?? null;
        $updatedAt = $row['updated_at'];

        // If no active class time or status is not 'active', clear any lingering session values and report no data
        if (empty($classStartTime) || $status !== 'active') {
            unset($_SESSION['class_start_time']);
            unset($_SESSION['class_start_time_formatted']);
            debugLog("No active class time found (start_time: " . ($classStartTime ?? 'null') . ", status: " . ($status ?? 'null') . "). Clearing session state.");
            echo json_encode([
                'success' => false,
                'message' => 'No active class time',
                'data' => null
            ]);
            exit;
        }

        // Restore to session - ensure consistent HH:MM and HH:MM:SS
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $classStartTime)) {
            $_SESSION['class_start_time'] = substr($classStartTime, 0, 5);    // HH:MM
            $_SESSION['class_start_time_formatted'] = $classStartTime;        // HH:MM:SS
        } elseif (preg_match('/^\d{1,2}:\d{2}$/', $classStartTime)) {
            $_SESSION['class_start_time'] = $classStartTime;                  // HH:MM
            $_SESSION['class_start_time_formatted'] = $classStartTime . ':00';// HH:MM:SS
        } else {
            // Fallback: try to parse; if fails, default
            $ts = strtotime($classStartTime);
            if ($ts !== false) {
                $_SESSION['class_start_time'] = date('H:i', $ts);
                $_SESSION['class_start_time_formatted'] = date('H:i:s', $ts);
            } else {
                $_SESSION['class_start_time'] = '08:00';
                $_SESSION['class_start_time_formatted'] = '08:00:00';
            }
        }
        
        debugLog("Class time restored from database: raw=" . $classStartTime . ", session HM=" . $_SESSION['class_start_time'] . ", session HMS=" . $_SESSION['class_start_time_formatted']);
        
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
            'updated_at' => $updatedAt,
            'instructor' => $_SESSION['current_instructor_name'] ?? 'Not set',
            'subject' => $_SESSION['current_subject_name'] ?? 'Not set'
        ];
        
        debugLog("Response data prepared: " . print_r($responseData, true));
        
        // Return success response
        $response = [
            'success' => true,
            'message' => 'Class time loaded from database',
            'data' => $responseData
        ];
        
        debugLog("Sending response: " . json_encode($response));
        echo json_encode($response);
        
    } else {
        debugLog("No class time found in database for school_id: " . $school_id);
        echo json_encode([
            'success' => false,
            'message' => 'No class time settings found for this school',
            'data' => null
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    debugLog("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => null
    ]);
}
?> 