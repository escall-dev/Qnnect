<?php
include("../conn/conn.php");
include("../includes/session_config.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'] ?? 1;

// Check if courseName is provided
if (!isset($_GET['course']) || empty($_GET['course'])) {
    echo json_encode(['success' => false, 'message' => 'Course name is required']);
    exit;
}

$courseName = $_GET['course'];

try {
    // Get sections for the selected course
    $sections_query = "SELECT s.section_id, s.section_name 
                      FROM tbl_sections s 
                      JOIN tbl_courses c ON s.course_id = c.course_id
                      WHERE c.course_name = :course_name 
                      AND ((s.user_id = :user_id OR s.user_id = 1)
                      AND (s.school_id = :school_id OR s.school_id = 1))
                      ORDER BY s.section_name";
                      
    $sections_stmt = $conn->prepare($sections_query);
    $sections_stmt->bindParam(':course_name', $courseName);
    $sections_stmt->bindParam(':user_id', $user_id);
    $sections_stmt->bindParam(':school_id', $school_id);
    $sections_stmt->execute();
    
    $sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return sections as JSON
    echo json_encode(['success' => true, 'sections' => $sections]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
