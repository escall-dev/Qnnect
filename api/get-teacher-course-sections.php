<?php
// Prevent any output before JSON response
ob_start();

// API endpoint to get course-section combinations from teacher schedules
require_once '../includes/session_config.php';
require_once '../conn/conn.php';

// Clear any previous output
ob_clean();

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Session expired or not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

try {
    // Get distinct course-section combinations from teacher schedules
    $sql = "SELECT DISTINCT subject, section 
            FROM teacher_schedules 
            WHERE school_id = :school_id AND status = 'active'
            ORDER BY subject, section";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $course_sections = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $subject = trim($row['subject'] ?? '');
        $section = trim($row['section'] ?? '');
        
        if ($subject !== '' && $section !== '') {
            $course_sections[] = $section; // Use section field directly as it contains course-section
        }
    }
    
    // Also get from existing student data as fallback
    $sql_fallback = "SELECT DISTINCT course_section 
                     FROM tbl_student 
                     WHERE school_id = :school_id AND user_id = :user_id
                     ORDER BY course_section";
    
    $stmt_fallback = $conn->prepare($sql_fallback);
    $stmt_fallback->bindParam(':school_id', $school_id, PDO::PARAM_INT);
    $stmt_fallback->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_fallback->execute();
    
    $existing_combinations = [];
    while ($row = $stmt_fallback->fetch(PDO::FETCH_ASSOC)) {
        $course_section = trim($row['course_section'] ?? '');
        if ($course_section !== '') {
            $existing_combinations[] = $course_section;
        }
    }
    
    // Combine both sources and remove duplicates
    $all_combinations = [];
    
    // Add teacher schedule combinations
    foreach ($course_sections as $section) {
        if (!in_array($section, $all_combinations)) {
            $all_combinations[] = $section;
        }
    }
    
    // Add existing student combinations
    foreach ($existing_combinations as $combination) {
        if (!in_array($combination, $all_combinations)) {
            $all_combinations[] = $combination;
        }
    }
    
    // Sort the combinations
    sort($all_combinations);
    
    // Format response
    $response = [
        'success' => true,
        'course_sections' => $all_combinations,
        'source' => 'teacher_schedules_and_student_data',
        'count' => count($all_combinations)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error fetching course-sections: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching course-section data',
        'message' => $e->getMessage()
    ]);
}
?> 