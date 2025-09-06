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
    // First try: dynamic token flow (student_qr_tokens)
    $studentID = 0; $studentName = null; $courseSection = null; $usingDynamicToken = false;


    // Heuristic: our token contains two base64url segments separated by '-'
    // Updated to accept both timed tokens (6+ chars) and permanent tokens (-PERM)
    // But exclude static codes that start with 'STU-'
    if (preg_match('/^[A-Za-z0-9_-]{10,}\-[A-Za-z0-9_-]{4,}$/', $qr_code) && strpos($qr_code, 'STU-') !== 0) {
        // Validate the token and mark it used atomically
        // 1) Look up the token row, ensure not expired/used/revoked
        $tokSel = $conn_qr->prepare("SELECT id, student_id FROM student_qr_tokens WHERE token = ? AND user_id = ? AND school_id = ? AND used_at IS NULL AND revoked_at IS NULL AND expires_at > NOW() LIMIT 1");
        if ($tokSel) {
            $tokSel->bind_param('sii', $qr_code, $user_id, $school_id);
            $tokSel->execute();
            $tokRow = $tokSel->get_result()->fetch_assoc();
            if ($tokRow) {
                $tokenId = (int)$tokRow['id'];
                $studentID = (int)$tokRow['student_id'];
                // 2) Mark as used (guarding against race) - but not for permanent tokens
                // Check if this is a permanent token (expires_at = '2099-12-31 23:59:59')
                $isPermanent = false;
                $checkPerm = $conn_qr->prepare("SELECT expires_at FROM student_qr_tokens WHERE id = ? AND expires_at = '2099-12-31 23:59:59' LIMIT 1");
                if ($checkPerm) {
                    $checkPerm->bind_param('i', $tokenId);
                    $checkPerm->execute();
                    $isPermanent = $checkPerm->get_result()->num_rows > 0;
                }
                
                if ($isPermanent) {
                    // For permanent tokens, don't mark as used - they can be reused
                    $usingDynamicToken = true;
                } else {
                    // For timed tokens, mark as used
                    $tokUpd = $conn_qr->prepare("UPDATE student_qr_tokens SET used_at = NOW() WHERE id = ? AND used_at IS NULL AND revoked_at IS NULL AND expires_at > NOW()");
                    if ($tokUpd) {
                        $tokUpd->bind_param('i', $tokenId);
                        $tokUpd->execute();
                        if ($tokUpd->affected_rows === 1) {
                            $usingDynamicToken = true;
                        }
                    }
                }
                
                if ($usingDynamicToken) {
                    // Resolve student name and course section
                    $selStud = $conn_qr->prepare("SELECT student_name, course_section FROM tbl_student WHERE tbl_student_id = ? AND user_id = ? AND school_id = ? LIMIT 1");
                    if ($selStud) {
                        $selStud->bind_param('iii', $studentID, $user_id, $school_id);
                        $selStud->execute();
                        $rStud = $selStud->get_result()->fetch_assoc();
                        $studentName = $rStud ? $rStud['student_name'] : 'Student';
                        $courseSection = $rStud ? $rStud['course_section'] : '';
                    }
                }
            }
        }

        if (!$usingDynamicToken) {
            // Token invalid or already used/expired/revoked
            echo json_encode([
                'success' => false,
                'error' => 'invalid_qr',
                'message' => 'QR code expired or already used'
            ]);
            exit;
        }
    }

    // Fallback: legacy static generated_code path
    if (!$usingDynamicToken) {
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
    }

        // Duplicate check for same day within tenant and optional instructor/subject
        // Use IFNULL to normalize NULL to 0 for comparison (consistent with generated columns + unique index)
        $dupSql = "SELECT tbl_attendance_id, time_in, status FROM tbl_attendance
                             WHERE tbl_student_id = ?
                                 AND DATE(time_in) = CURDATE()
                                 AND user_id = ? AND school_id = ?
                                 AND IFNULL(instructor_id,0) = ?
                                 AND IFNULL(subject_id,0) = ?
                             LIMIT 1";
        $dupStmt = $conn_qr->prepare($dupSql);
        $dupStmt->bind_param('iiiii', $studentID, $user_id, $school_id, $instructorId, $subjectId);
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

    // Insert record (guard against race with unique index)
    $ins = $conn_qr->prepare("INSERT INTO tbl_attendance (tbl_student_id, time_in, status, instructor_id, subject_id, user_id, school_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $ins->bind_param('issiiii', $studentID, $now, $status, $instructorId, $subjectId, $user_id, $school_id);
    if (!$ins->execute()) {
        // If duplicate key due to concurrent insert, surface as duplicate
        if ($conn_qr->errno === 1062) {
            echo json_encode([
                'success' => false,
                'error' => 'duplicate_scan',
                'message' => 'Attendance already recorded for today',
                'data' => [
                    'duplicate' => true,
                    'student_name' => $studentName,
                    'attendance_time' => date('h:i A'),
                    'attendance_date' => date('M d, Y'),
                    'attendance_status' => $status,
                    'subject_name' => $subjectName
                ]
            ]);
            exit;
        }
        throw new Exception('Insert failed: ' . $ins->error);
    }

    $attendanceId = $conn_qr->insert_id;

    echo json_encode([
        'success' => true,
        'message' => 'Attendance recorded successfully',
        'data' => [
            'attendance_id' => $attendanceId,
            'student_id' => $studentID,
            'student_name' => $studentName,
            'course_section' => $courseSection,
            'status' => $status,
            'instructor_id' => $instructorId,
            'subject_id' => $subjectId
        ]
    ]);
} catch (Throwable $e) {
    error_log('check-attendance error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error', 'message' => 'Unexpected error']);
}
?>
