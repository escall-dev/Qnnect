<?php
include("../conn/conn.php");

// Set headers for JSON response
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get verification data
    $studentId = isset($_POST['student_id']) ? intval($_POST['student_id']) : null;
    $studentName = isset($_POST['student_name']) ? $_POST['student_name'] : '';
    $status = isset($_POST['status']) && in_array($_POST['status'], ['Success', 'Failed']) ? $_POST['status'] : 'Failed';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : null;
    
    // Get client IP address
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    // Get user agent
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    
    try {
        // Insert log entry
        $stmt = $conn->prepare("
            INSERT INTO tbl_face_verification_logs 
            (student_id, student_name, status, ip_address, user_agent, notes) 
            VALUES (:student_id, :student_name, :status, :ip_address, :user_agent, :notes)
        ");
        
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindParam(':student_name', $studentName, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':ip_address', $ipAddress, PDO::PARAM_STR);
        $stmt->bindParam(':user_agent', $userAgent, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        
        $stmt->execute();
        $logId = $conn->lastInsertId();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Verification logged successfully',
            'data' => [
                'log_id' => $logId,
                'student_id' => $studentId,
                'student_name' => $studentName,
                'status' => $status,
                'verification_time' => date('Y-m-d H:i:s'),
                'ip_address' => $ipAddress
            ]
        ]);
    } catch (PDOException $e) {
        // Return error response
        echo json_encode([
            'success' => false,
            'message' => 'Error logging verification: ' . $e->getMessage()
        ]);
    }
} else {
    // Return error for non-POST requests
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 