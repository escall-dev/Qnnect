<?php
// Offline attendance sync handler
session_start();
include('../conn/db_connect.php');
require_once('../includes/ActivityLogger.php');

header('Content-Type: application/json');

// AuthZ: requires logged-in user and tenant context
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Parse JSON payload
$raw = file_get_contents('php://input');
$items = json_decode($raw, true);
if (!is_array($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$schoolId = (int)$_SESSION['school_id'];
$instructorId = isset($_SESSION['current_instructor_id']) ? (int)$_SESSION['current_instructor_id'] : null;
$subjectId = isset($_SESSION['current_subject_id']) ? (int)$_SESSION['current_subject_id'] : null;

$activity = new ActivityLogger($conn_qr, $userId);

// Transaction boundary covers the batch
$conn_qr->begin_transaction();

$synced = 0;
$skipped = 0;
$results = [];

try {
    // Prepared statements reused in loop
    $checkSql = "SELECT tbl_attendance_id FROM tbl_attendance
                 WHERE tbl_student_id = ? AND DATE(time_in) = ?
                   AND user_id = ? AND school_id = ?
                   AND (
                        (instructor_id IS NULL AND ? IS NULL) OR instructor_id = ?
                   )
                   AND (
                        (subject_id IS NULL AND ? IS NULL) OR subject_id = ?
                   )
                 LIMIT 1";
    $checkStmt = $conn_qr->prepare($checkSql);

    $insertSql = "INSERT INTO tbl_attendance
                    (tbl_student_id, time_in, status, instructor_id, subject_id, user_id, school_id)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn_qr->prepare($insertSql);

    foreach ($items as $idx => $attendance) {
        // Basic validation per item
        $studentId = isset($attendance['student_id']) ? (int)$attendance['student_id'] : 0;
        $date = isset($attendance['date']) ? trim($attendance['date']) : '';
        $timeIn = isset($attendance['time_in']) ? trim($attendance['time_in']) : '';
        $status = isset($attendance['status']) ? trim($attendance['status']) : '';

        if ($studentId <= 0 || $date === '') {
            $results[] = ['index' => $idx, 'success' => false, 'error' => 'invalid_item'];
            $skipped++;
            continue;
        }

        // Normalize time_in into full datetime (fallback to midnight if missing)
        $timePart = $timeIn !== '' ? $timeIn : '00:00:00';
        // Avoid invalid formats; rely on MySQL to parse standard 'Y-m-d H:i:s'
        $timeInFull = $date . ' ' . $timePart;

        // Duplicate protection: same student + day + tenant (+ optional instructor/subject)
        $dateOnly = $date; // already yyyy-mm-dd from client
        $checkStmt->bind_param(
            'isiiiiii',
            $studentId,
            $dateOnly,
            $userId,
            $schoolId,
            $instructorId,
            $instructorId,
            $subjectId,
            $subjectId
        );
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if ($existing) {
            $results[] = ['index' => $idx, 'success' => true, 'duplicate' => true, 'tbl_attendance_id' => (int)$existing['tbl_attendance_id']];
            $skipped++;
            continue;
        }

        // Compute status if not provided
        if ($status === '') {
            // Try to use current class start time from session to derive status
            $classStart = isset($_SESSION['class_start_time']) ? $_SESSION['class_start_time'] : (isset($_SESSION['class_start_time_formatted']) ? $_SESSION['class_start_time_formatted'] : '08:00:00');
            if (strlen($classStart) === 5) { $classStart .= ':00'; }
            $status = (strtotime($timeInFull) <= strtotime($date . ' ' . $classStart)) ? 'On Time' : 'Late';
        }

        $insInstructor = $instructorId !== null ? $instructorId : null;
        $insSubject = $subjectId !== null ? $subjectId : null;

        $insertStmt->bind_param(
            'issiiii',
            $studentId,
            $timeInFull,
            $status,
            $insInstructor,
            $insSubject,
            $userId,
            $schoolId
        );

        if (!$insertStmt->execute()) {
            throw new Exception('Insert failed: ' . $insertStmt->error);
        }

        $newId = $conn_qr->insert_id;
        $synced++;
        $results[] = ['index' => $idx, 'success' => true, 'tbl_attendance_id' => $newId];

        // Activity log per record (keep light; server-side batching still tracked below)
        $activity->log(
            'attendance_scan',
            'Synced offline attendance record',
            'tbl_attendance',
            $newId,
            [
                'student_id' => $studentId,
                'time_in' => $timeInFull,
                'status' => $status,
                'instructor_id' => $insInstructor,
                'subject_id' => $insSubject
            ]
        );
    }

    $conn_qr->commit();

    // Batch summary log
    $activity->log(
        'offline_sync',
        "Offline sync completed: synced={$synced}, skipped={$skipped}",
        'tbl_attendance',
        null,
        ['synced' => $synced, 'skipped' => $skipped]
    );

    echo json_encode([
        'success' => true,
        'synced' => $synced,
        'skipped' => $skipped,
        'results' => $results
    ]);
} catch (Exception $e) {
    $conn_qr->rollback();

    // Error log and response
    $activity->log(
        'offline_sync',
        'Error syncing offline attendance records: ' . $e->getMessage(),
        'tbl_attendance',
        null,
        ['error' => $e->getMessage()]
    );

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error syncing data', 'error' => $e->getMessage()]);
}

// Do not close $conn_qr here; shared for request lifecycle
?>