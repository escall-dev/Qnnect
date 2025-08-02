<?php
// API endpoint to get sections linked to a specific subject
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
    $subject = $_GET['subject'] ?? '';
    
    if (empty($subject)) {
        echo json_encode([
            'success' => false,
            'message' => 'Subject parameter is required'
        ]);
        exit();
    }
    
    try {
        require_once('../conn/db_connect.php');
        
        if (!isset($conn_qr)) {
            throw new Exception("Database connection not available");
        }
        
        // Get sections linked to this subject from teacher_schedules
        $query = "SELECT DISTINCT section FROM teacher_schedules 
                  WHERE subject = ? AND school_id = ? AND status = 'active' 
                  ORDER BY section";
        
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("si", $subject, $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sections = [];
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row['section'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $sections,
            'count' => count($sections)
        ]);
        
    } catch (Exception $e) {
        error_log("Error in get-sections-by-subject.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'database_error',
            'message' => 'Unable to fetch sections for this subject.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 