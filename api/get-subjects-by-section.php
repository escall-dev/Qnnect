<?php
// API endpoint to get subjects linked to a specific section
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is logged in (with fallback for testing)
$user_id = $_SESSION['user_id'] ?? 1;
$school_id = $_SESSION['school_id'] ?? 1;

if (!isset($_SESSION['user_id']) && !isset($_SESSION['school_id'])) {
    // Only show error if both are missing (likely not logged in)
    echo json_encode([
        'success' => false,
        'error' => 'session_expired',
        'message' => 'Session expired. Please log in again.'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $section = $_GET['section'] ?? '';
    
    if (empty($section)) {
        echo json_encode([
            'success' => false,
            'message' => 'Section parameter is required'
        ]);
        exit();
    }
    
    try {
        require_once('../conn/db_connect.php');
        
        if (!isset($conn_qr)) {
            throw new Exception("Database connection not available");
        }
        
        // Get subjects linked to this section from teacher_schedules
        $query = "SELECT DISTINCT subject FROM teacher_schedules 
                  WHERE section = ? AND school_id = ? AND status = 'active' 
                  ORDER BY subject";
        
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("si", $section, $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row['subject'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $subjects,
            'count' => count($subjects)
        ]);
        
    } catch (Exception $e) {
        error_log("Error in get-subjects-by-section.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'database_error',
            'message' => 'Unable to fetch subjects for this section.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 