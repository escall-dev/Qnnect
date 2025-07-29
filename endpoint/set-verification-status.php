<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['student_id']) || !isset($_POST['verified'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$studentId = intval($_POST['student_id']);
$verified = intval($_POST['verified']);

// Store verification status in session
$_SESSION['face_verified'] = $verified;
$_SESSION['verified_student_id'] = $studentId;
$_SESSION['verification_time'] = time();

echo json_encode([
    'success' => true,
    'message' => 'Verification status updated'
]);
?> 