<?php
session_start();
include('../conn/db_connect.php');
require_once('../includes/ActivityLogger.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if request is POST and has JSON content
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$activity_logger = new ActivityLogger($conn_qr, $_SESSION['user_id']);
$success = true;
$errors = [];

// Start transaction
$conn_qr->begin_transaction();

try {
    foreach ($data as $attendance) {
        // Insert attendance record
        $sql = "INSERT INTO attendance (
            student_id, 
            date, 
            time_in, 
            time_out, 
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn_qr->prepare($sql);
        $stmt->bind_param(
            "isssss",
            $attendance['student_id'],
            $attendance['date'],
            $attendance['time_in'],
            $attendance['time_out'],
            $attendance['status'],
            $attendance['timestamp']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting attendance record: " . $stmt->error);
        }

        $attendance_id = $conn_qr->insert_id;

        // Log the activity
        $activity_logger->log(
            'attendance_scan',
            "Synced offline attendance record for student ID: {$attendance['student_id']}",
            'attendance',
            $attendance_id,
            [
                'date' => $attendance['date'],
                'time_in' => $attendance['time_in'],
                'time_out' => $attendance['time_out'],
                'status' => $attendance['status']
            ]
        );
    }

    // Commit transaction
    $conn_qr->commit();

    // Log successful sync
    $activity_logger->log(
        'offline_sync',
        "Successfully synced " . count($data) . " offline attendance records",
        'attendance',
        null,
        ['record_count' => count($data)]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Successfully synced ' . count($data) . ' records'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn_qr->rollback();
    
    // Log the error
    $activity_logger->log(
        'offline_sync',
        "Error syncing offline attendance records: " . $e->getMessage(),
        'attendance',
        null,
        ['error' => $e->getMessage()]
    );

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error syncing data: ' . $e->getMessage()
    ]);
}

$conn_qr->close();
?> 