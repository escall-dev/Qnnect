<?php
include("../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_GET['student_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Student ID is required'
    ]);
    exit;
}

$studentId = intval($_GET['student_id']);

try {
    $stmt = $conn->prepare("SELECT face_image_path FROM tbl_student WHERE tbl_student_id = :student_id");
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['face_image_path']) {
        echo json_encode([
            'success' => true,
            'face_image_path' => $result['face_image_path']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No face image found for this student'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 