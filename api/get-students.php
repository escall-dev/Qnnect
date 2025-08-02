<?php
// Include database connection
include('../conn/conn.php');
include('../includes/data_isolation_helper.php');

// Start session to get user context
session_start();

// Set the response content type to JSON
header('Content-Type: application/json');

try {
    // Get user context for data isolation
    $context = getCurrentUserContext();
    
    // Fetch students with data isolation
    $stmt = $conn->prepare("SELECT * FROM tbl_student 
                           WHERE school_id = :school_id 
                           " . ($context['user_id'] ? "AND (user_id = :user_id OR user_id IS NULL)" : "") . "
                           ORDER BY tbl_student_id DESC");
    $stmt->bindParam(':school_id', $context['school_id'], PDO::PARAM_INT);
    if ($context['user_id']) {
        $stmt->bindParam(':user_id', $context['user_id'], PDO::PARAM_INT);
    }
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare the student data for JSON response
    $students = [];
    foreach ($result as $row) {
        $students[] = [
            'id' => $row['tbl_student_id'],
            'name' => $row['student_name'],
            'course_section' => $row['course_section'],
            'qr_code' => $row['generated_code']
        ];
    }
    
    // Return success response with student data
    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
} catch (PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching student data: ' . $e->getMessage()
    ]);
}
?> 