<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $last_id = $_GET['last_id'] ?? 0;
    $request_school_id = $_GET['school_id'] ?? $school_id;
    $request_user_id = $_GET['user_id'] ?? $user_id;
    
    // Validate school_id and user_id match session for security
    if ($request_school_id != $school_id || $request_user_id != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid access.']);
        exit();
    }
    
    try {
        // Get new attendance records since last_id with school and user filtering
        $query = "SELECT a.tbl_attendance_id, a.time_in, a.status, 
                         s.student_name, s.course_section
                  FROM tbl_attendance a
                  JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id
                                     AND s.school_id = a.school_id
                                     AND s.user_id = a.user_id
                  WHERE a.tbl_attendance_id > ? 
                  AND a.school_id = ? 
                  AND a.user_id = ?
                  AND DATE(a.time_in) = CURDATE()
                  ORDER BY a.tbl_attendance_id DESC";
        
        $stmt = $conn_qr->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn_qr->error);
        }
        
        $stmt->bind_param("iii", $last_id, $school_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = [
                'tbl_attendance_id' => $row['tbl_attendance_id'],
                'student_name' => $row['student_name'],
                'course_section' => $row['course_section'],
                'formatted_date' => date('M d, Y', strtotime($row['time_in'])),
                'formatted_time' => date('h:i:s A', strtotime($row['time_in'])),
                'status' => $row['status'] ?: 'Unknown'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'records' => $records,
            'count' => count($records)
        ]);
        
    } catch (Exception $e) {
        error_log("Latest Attendance API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
