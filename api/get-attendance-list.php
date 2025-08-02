<?php
// Include database connection
require_once '../conn/db_conn.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8; // Set to 8 records per page
$offset = ($page - 1) * $limit;

try {
    // Get paginated attendance records
    $query = "
        SELECT a.*, s.student_name, s.course_section 
        FROM tbl_attendance a
        LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
        WHERE a.time_in IS NOT NULL AND a.user_id = ? AND a.school_id = ?
        ORDER BY a.time_in DESC 
        LIMIT ?, ?
    ";
    
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("iiii", $user_id, $school_id, $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get total records for pagination
    $totalQuery = "SELECT COUNT(*) as total FROM tbl_attendance WHERE user_id = ? AND school_id = ?";
    $totalStmt = $conn_qr->prepare($totalQuery);
    $totalStmt->bind_param("ii", $user_id, $school_id);
    $totalStmt->execute();
    $totalResult = $totalStmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $totalRecords = $totalRow['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Return JSON response with records and pagination info
    echo json_encode([
        'records' => $result,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'limit' => $limit
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
