<?php
// Include session configuration first to ensure same session is used
include('../includes/session_config.php');

// Include database connection
include('../conn/db_connect.php');
// Include activity logging helper
include('../includes/activity_log_helper.php');

// Set error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Academic settings save attempt initiated");

// Set headers for JSON response
header('Content-Type: application/json');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the submitted form data
    $school_year = $_POST['school_year'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $current_tab = $_POST['current_tab'] ?? 'academic';
    $user_email = $_SESSION['email'] ?? '';
    
    error_log("Received data - School Year: $school_year, Semester: $semester, Email: $user_email");
    
    // Validate inputs
    if (empty($school_year) || empty($semester)) {
        error_log("Validation failed - empty school year or semester");
        echo json_encode(['success' => false, 'message' => 'Please provide both school year and semester']);
        exit;
    }
    
    // Validate school year format (YYYY-YYYY)
    if (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
        error_log("Validation failed - invalid school year format: $school_year");
        echo json_encode([
            'success' => false,
            'message' => 'School year must be in format YYYY-YYYY'
        ]);
        exit;
    }
    
    // Validate semester
    $allowed_semesters = ['1st Semester', '2nd Semester'];
    if (!in_array($semester, $allowed_semesters)) {
        error_log("Validation failed - invalid semester: $semester");
        echo json_encode([
            'success' => false,
            'message' => 'Semester must be one of: ' . implode(', ', $allowed_semesters)
        ]);
        exit;
    }
    
    // Check for previous settings to log changes
    $old_school_year = $_SESSION['school_year'] ?? '';
    $old_semester = $_SESSION['semester'] ?? '';
    
    // Save values to session
    $_SESSION['school_year'] = $school_year;
    $_SESSION['semester'] = $semester;
    
    error_log("Session updated - School Year: $school_year, Semester: $semester");
    
    // Save to database
    try {
        error_log("Starting database save process");
        
        // Check if user_settings table exists, if not create it
        $create_table_sql = "CREATE TABLE IF NOT EXISTS user_settings (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            school_year VARCHAR(10) NOT NULL,
            semester VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY unique_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $create_result = $conn_qr->query($create_table_sql);
        if (!$create_result) {
            error_log("Failed to create user_settings table: " . $conn_qr->error);
        } else {
            error_log("user_settings table created/verified successfully");
        }
        
        // Check if user already has settings
        $check_sql = "SELECT id FROM user_settings WHERE email = ?";
        $check_stmt = $conn_qr->prepare($check_sql);
        if (!$check_stmt) {
            error_log("Failed to prepare check statement: " . $conn_qr->error);
            echo json_encode(['success' => false, 'message' => 'Database preparation error']);
            exit;
        }
        
        $check_stmt->bind_param("s", $user_email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing settings
            error_log("Updating existing settings for user: $user_email");
            $update_sql = "UPDATE user_settings SET school_year = ?, semester = ?, updated_at = NOW() WHERE email = ?";
            $update_stmt = $conn_qr->prepare($update_sql);
            if (!$update_stmt) {
                error_log("Failed to prepare update statement: " . $conn_qr->error);
                echo json_encode(['success' => false, 'message' => 'Database update error']);
                exit;
            }
            $update_stmt->bind_param("sss", $school_year, $semester, $user_email);
            $success = $update_stmt->execute();
            if (!$success) {
                error_log("Failed to execute update: " . $update_stmt->error);
            } else {
                error_log("Update successful - affected rows: " . $update_stmt->affected_rows);
            }
            $update_stmt->close();
        } else {
            // Insert new settings
            error_log("Inserting new settings for user: $user_email");
            $insert_sql = "INSERT INTO user_settings (email, school_year, semester, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
            $insert_stmt = $conn_qr->prepare($insert_sql);
            if (!$insert_stmt) {
                error_log("Failed to prepare insert statement: " . $conn_qr->error);
                echo json_encode(['success' => false, 'message' => 'Database insert error']);
                exit;
            }
            $insert_stmt->bind_param("sss", $user_email, $school_year, $semester);
            $success = $insert_stmt->execute();
            if (!$success) {
                error_log("Failed to execute insert: " . $insert_stmt->error);
            } else {
                error_log("Insert successful - affected rows: " . $insert_stmt->affected_rows);
            }
            $insert_stmt->close();
        }
        
        $check_stmt->close();
        
        if (!$success) {
            error_log("Database operation failed");
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
            exit;
        }
        
        error_log("Database save completed successfully");
        
    } catch (Exception $e) {
        error_log("Exception saving academic settings: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
        exit;
    }
    
    // Log the academic settings change
    logActivity(
        'settings_change',
        "Updated academic settings: School Year: $school_year, Semester: $semester",
        'user_settings',
        null,
        [
            'school_year' => [
                'old' => $old_school_year,
                'new' => $school_year
            ],
            'semester' => [
                'old' => $old_semester,
                'new' => $semester
            ]
        ]
    );
    
    error_log("Academic settings save completed successfully - returning success response");
    
    // Return success response
    echo json_encode(['success' => true, 'tab' => $current_tab]);
} else {
    // Return error for non-POST requests
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?> 