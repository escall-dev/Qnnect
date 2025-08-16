<?php
// JSON API to process QR attendance scans
require_once('../conn/db_connect.php');
require_once('../includes/session_config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'session_expired', 'message' => 'Session expired. Please log in again.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$school_id = (int)$_SESSION['school_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$qr_code = isset($_POST['qr_code']) ? trim((string)$_POST['qr_code']) : '';
if ($qr_code === '') {
    echo json_encode(['success' => false, 'error' => 'empty_qr', 'message' => 'No QR code detected']);
    exit;
}

date_default_timezone_set('Asia/Manila');

// Ensure DB connection ($conn_qr) from db_connect.php
if (!isset($conn_qr) || !$conn_qr) {
    echo json_encode(['success' => false, 'error' => 'db_error', 'message' => 'Database connection error']);
    exit;
}

// Resolve instructor and subject from session
$instructorId = isset($_SESSION['current_instructor_id']) ? (int)$_SESSION['current_instructor_id'] : 0;
$subjectId = isset($_SESSION['current_subject_id']) ? (int)$_SESSION['current_subject_id'] : 0;
$subjectName = $_SESSION['current_subject_name'] ?? ($_SESSION['current_subject'] ?? '');

try {
    // Find student by generated_code scoped to user and school
    $studentSel = $conn_qr->prepare("SELECT tbl_student_id, student_name, course_section FROM tbl_student WHERE generated_code = ? AND user_id = ? AND school_id = ? LIMIT 1");
    $studentSel->bind_param('sii', $qr_code, $user_id, $school_id);
    $studentSel->execute();
    $student = $studentSel->get_result()->fetch_assoc();

    if (!$student) {
        echo json_encode([
            'success' => false,
            'error' => 'invalid_qr',
            'message' => 'QR code not found for this school/user'
        ]);
        exit;
    }

    $studentID = (int)$student['tbl_student_id'];
    $studentName = $student['student_name'];
    $courseSection = $student['course_section'];

    // Duplicate check for same day within tenant and optional instructor/subject
    $dupSql = "SELECT tbl_attendance_id, time_in, status FROM tbl_attendance
               WHERE tbl_student_id = ? AND DATE(time_in) = CURDATE()
                 AND user_id = ? AND school_id = ?
                 AND ( (instructor_id IS NULL AND ? IS NULL) OR instructor_id = ? )
                 AND ( (subject_id IS NULL AND ? IS NULL) OR subject_id = ? )
               LIMIT 1";
    $dupStmt = $conn_qr->prepare($dupSql);
    $dupStmt->bind_param('iiiiiii', $studentID, $user_id, $school_id, $instructorId, $instructorId, $subjectId, $subjectId);
    $dupStmt->execute();
    $existing = $dupStmt->get_result()->fetch_assoc();

    if ($existing) {
        echo json_encode([
            'success' => false,
            'error' => 'duplicate_scan',
            'message' => 'Attendance already recorded for today',
            'data' => [
                'duplicate' => true,
                'student_name' => $studentName,
                'attendance_time' => date('h:i A', strtotime($existing['time_in'])),
                'attendance_date' => date('M d, Y', strtotime($existing['time_in'])),
                'attendance_status' => $existing['status'],
                'subject_name' => $subjectName
            ]
        ]);
        exit;
    }

    // Compute status based on class start time
    $class_start_time = $_SESSION['class_start_time_formatted'] ?? ($_SESSION['class_start_time'] ?? '08:00:00');
    if (strlen($class_start_time) == 5) { $class_start_time .= ':00'; }
    $now = date('Y-m-d H:i:s');
    $status = (strtotime($now) <= strtotime(date('Y-m-d') . ' ' . $class_start_time)) ? 'On Time' : 'Late';

    // Insert record
    $ins = $conn_qr->prepare("INSERT INTO tbl_attendance (tbl_student_id, time_in, status, instructor_id, subject_id, user_id, school_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $ins->bind_param('issiiii', $studentID, $now, $status, $instructorId, $subjectId, $user_id, $school_id);
    if (!$ins->execute()) {
        throw new Exception('Insert failed: ' . $ins->error);
    }

    $attendanceId = $conn_qr->insert_id;

    echo json_encode([
        'success' => true,
        'message' => 'Attendance recorded successfully',
        'data' => [
            'attendance_id' => $attendanceId,
            'student_name' => $studentName,
            'course_section' => $courseSection,
            'status' => $status
        ]
    ]);
} catch (Throwable $e) {
    error_log('check-attendance error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error', 'message' => 'Unexpected error']);
}
?>
