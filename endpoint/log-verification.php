<?php
// Clean endpoint: record face verification attempts during registration.
// Fix: avoid inserting 0 as student_id when not yet known; include tenant fields.

include("../includes/session_config.php");
include("../conn/conn.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Inputs
$studentId = (isset($_POST['student_id']) && $_POST['student_id'] !== '') ? (int)$_POST['student_id'] : null;
$studentName = isset($_POST['student_name']) ? trim($_POST['student_name']) : '';
$status = (isset($_POST['status']) && in_array($_POST['status'], ['Success', 'Failed'])) ? $_POST['status'] : 'Failed';
$notes = isset($_POST['notes']) ? $_POST['notes'] : null;

// Tenant
$school_id = isset($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : 1;
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

// Client info
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
}
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

try {
    $sql = "
        INSERT INTO tbl_face_verification_logs
            (student_id, student_name, status, ip_address, user_agent, notes, school_id, user_id)
        VALUES
            (:student_id, :student_name, :status, :ip_address, :user_agent, :notes, :school_id, :user_id)
    ";
    $stmt = $conn->prepare($sql);

    // Bind student_id correctly (NULL when not provided)
    if ($studentId === null) {
        $stmt->bindValue(':student_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
    }
    $stmt->bindValue(':student_name', $studentName, PDO::PARAM_STR);
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    $stmt->bindValue(':ip_address', $ipAddress, PDO::PARAM_STR);
    $stmt->bindValue(':user_agent', $userAgent, $userAgent === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':notes', $notes, $notes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':school_id', $school_id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();
    $logId = $conn->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Verification logged successfully',
        'data' => [
            'log_id' => $logId,
            'student_id' => $studentId,
            'student_name' => $studentName,
            'status' => $status,
            'verification_time' => date('Y-m-d H:i:s'),
            'ip_address' => $ipAddress,
            'school_id' => $school_id,
            'user_id' => $user_id,
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error logging verification: ' . $e->getMessage()]);
}