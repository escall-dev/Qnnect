<?php
// API endpoint to get sections from course_section field in tbl_student
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'session_expired',
        'message' => 'Session expired. Please log in again.'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

try {
    require_once('../conn/db_connect.php');
    
    if (!isset($conn_qr)) {
        throw new Exception("Database connection not available");
    }
    
    // Get unique sections from course_section field in tbl_student
    $query = "SELECT DISTINCT course_section 
              FROM tbl_student 
              WHERE user_id = ? AND school_id = ? 
              AND course_section IS NOT NULL 
              AND course_section != '' 
              ORDER BY course_section ASC";
    
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("ii", $user_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row['course_section'];
    }
    
    // Log for debugging
    error_log("Found " . count($sections) . " sections for user_id: $user_id, school_id: $school_id");
    
    echo json_encode([
        'success' => true,
        'data' => $sections,
        'count' => count($sections)
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-sections.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'database_error',
        'message' => 'Unable to fetch sections from database.'
    ]);
}
?> 