<?php
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get student ID and verification status
    $studentId = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $verified = isset($_POST['verified']) ? intval($_POST['verified']) : 0;
    
    // Validate input
    if ($studentId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid student ID'
        ]);
        exit;
    }
    
    // Store verification in session
    $_SESSION['face_verified'] = ($verified === 1);
    $_SESSION['verified_student_id'] = $studentId;
    $_SESSION['verification_time'] = time();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Verification status stored in session',
        'data' => [
            'student_id' => $studentId,
            'verified' => $_SESSION['face_verified'],
            'timestamp' => $_SESSION['verification_time']
        ]
    ]);
} else {
    // Return error for non-POST requests
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 