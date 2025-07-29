<?php
// Include database connection
include('../conn/db_connect.php');

// Set headers for JSON response
header('Content-Type: application/json');

// Get course parameter
$course = isset($_GET['course']) ? $_GET['course'] : '';

if (empty($course)) {
    echo json_encode([
        'success' => false,
        'message' => 'No course specified'
    ]);
    exit;
}

try {
    // Get sections for the specified course
    $query = "SELECT DISTINCT SUBSTRING_INDEX(course_section, '-', -1) AS section 
             FROM tbl_student 
             WHERE course_section LIKE ?
             ORDER BY section";
    
    $stmt = $conn_qr->prepare($query);
    $courseFilter = $course . '-%';
    $stmt->bind_param("s", $courseFilter);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row['section'];
    }
    
    echo json_encode([
        'success' => true,
        'sections' => $sections
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching sections: ' . $e->getMessage()
    ]);
}
?> 