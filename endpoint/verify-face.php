<?php
include("../conn/conn.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

if (!isset($_POST['student_id']) || !isset($_POST['current_face'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$studentId = intval($_POST['student_id']);
$currentFace = $_POST['current_face'];

try {
    // Get the registered face image path
    $stmt = $conn->prepare("SELECT face_image_path FROM tbl_student WHERE tbl_student_id = :student_id");
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || !$result['face_image_path']) {
        echo json_encode([
            'success' => false,
            'message' => 'No registered face found for this student'
        ]);
        exit;
    }
    
    // Save the current face image temporarily
    $currentFaceData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $currentFace));
    $tempDir = "../temp/";
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $tempCurrentFace = $tempDir . "current_" . time() . ".jpg";
    file_put_contents($tempCurrentFace, $currentFaceData);
    
    // Compare the faces using face-api.js (this will be done on the client side)
    // For now, we'll return the paths for client-side comparison
    echo json_encode([
        'success' => true,
        'registered_face' => $result['face_image_path'],
        'current_face' => $tempCurrentFace
    ]);
    
    // Clean up the temporary file after a delay
    register_shutdown_function(function() use ($tempCurrentFace) {
        if (file_exists($tempCurrentFace)) {
            unlink($tempCurrentFace);
        }
    });
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 