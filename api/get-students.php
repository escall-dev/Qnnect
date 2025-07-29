<?php
// Include database connection
include('../conn/conn.php');

// Set the response content type to JSON
header('Content-Type: application/json');

try {
    // Fetch all students from the database
    $stmt = $conn->prepare("SELECT * FROM tbl_student ORDER BY tbl_student_id DESC");
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