<?php
// Persist current subject in session and (optionally) local state
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../includes/session_config.php');
require_once('../conn/db_connect.php');
header('Content-Type: application/json');

// Ensure tbl_subjects can generate non-zero IDs and repair zero-id rows when needed
function ensure_subject_id_pk_ai(mysqli $conn): void {
    // Determine current DB
    $resDb = $conn->query('SELECT DATABASE()');
    $dbName = ($resDb && ($r=$resDb->fetch_row())) ? $r[0] : null;
    if (!$dbName) return;

    // Check if subject_id already AUTO_INCREMENT
    $aiRes = $conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conn->real_escape_string($dbName)."' AND TABLE_NAME='tbl_subjects' AND EXTRA LIKE '%auto_increment%'");
    $aiCols = [];
    if ($aiRes) { while ($row = $aiRes->fetch_assoc()) { $aiCols[] = $row['COLUMN_NAME']; } }
    if (in_array('subject_id', $aiCols, true)) return; // already AI, good

    // If another AI column exists, don't change to avoid conflicts
    if (count($aiCols) > 0) return;

    // Ensure PRIMARY KEY is on subject_id (best-effort)
    @$conn->query("ALTER TABLE tbl_subjects ADD PRIMARY KEY (subject_id)");

    // Now try to make it AUTO_INCREMENT
    @$conn->query("ALTER TABLE tbl_subjects MODIFY COLUMN subject_id INT NOT NULL AUTO_INCREMENT");
}

// Ensure tbl_subjects can generate non-zero IDs and repair zero-id rows when needed
function resolve_subject_with_valid_id(mysqli $conn, string $name, ?int $schoolId, ?int $userId): int {
    ensure_subject_id_pk_ai($conn);
    // Try insert new row to get a fresh auto id
    if ($schoolId) {
        $stmt = $conn->prepare("INSERT INTO tbl_subjects (subject_name, school_id, user_id) VALUES (?, ?, ?)");
        $uid = $userId ?: null;
        $stmt->bind_param('sii', $name, $schoolId, $uid);
    } else {
        $stmt = $conn->prepare("INSERT INTO tbl_subjects (subject_name) VALUES (?)");
        $stmt->bind_param('s', $name);
    }
    if ($stmt && $stmt->execute()) {
        $newId = (int)$conn->insert_id;
        if ($newId > 0) {
            // Optionally clean old zero-id rows for same name/school
            if ($schoolId) {
                $del = $conn->prepare("DELETE FROM tbl_subjects WHERE subject_id = 0 AND subject_name = ? AND school_id = ?");
                $del->bind_param('si', $name, $schoolId);
                $del->execute();
            } else {
                $conn->query("DELETE FROM tbl_subjects WHERE subject_id = 0 AND subject_name = '" . $conn->real_escape_string($name) . "'");
            }
            return $newId;
        }
    }

    // If still zero, attempt best-effort to set PK+AUTO_INCREMENT and retry once
    ensure_subject_id_pk_ai($conn);
    if ($schoolId) {
        $stmt2 = $conn->prepare("INSERT INTO tbl_subjects (subject_name, school_id, user_id) VALUES (?, ?, ?)");
        $uid2 = $userId ?: null;
        $stmt2->bind_param('sii', $name, $schoolId, $uid2);
    } else {
        $stmt2 = $conn->prepare("INSERT INTO tbl_subjects (subject_name) VALUES (?)");
        $stmt2->bind_param('s', $name);
    }
    if ($stmt2 && $stmt2->execute()) {
        $newId2 = (int)$conn->insert_id;
        if ($newId2 > 0) return $newId2;
    }
    return 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$subjectName = isset($_POST['subjectName']) ? trim((string)$_POST['subjectName']) : '';
$subjectId   = isset($_POST['subjectId']) ? (int)$_POST['subjectId'] : null;

if ($subjectName === '') {
    echo json_encode(['success' => false, 'message' => 'Subject name is required']);
    exit;
}

// Always persist name
$_SESSION['current_subject_name'] = $subjectName;
error_log("Subject set in session: " . $subjectName);

// Ensure we have a subject_id: look up or create if not provided
try {
    if ($subjectId === null || $subjectId <= 0) {
        // Look up by exact name (consistent with save-instructor.php behavior)
        if (!isset($conn_qr) || !$conn_qr) {
            throw new Exception('DB connection not available');
        }

        $schoolId = isset($_SESSION['school_id']) ? (int)$_SESSION['school_id'] : null;
        $userId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

        // Find subject by name and school for isolation
        if ($schoolId) {
            $check = $conn_qr->prepare("SELECT subject_id FROM tbl_subjects WHERE subject_name = ? AND school_id = ? LIMIT 1");
            $check->bind_param('si', $subjectName, $schoolId);
        } else {
            $check = $conn_qr->prepare("SELECT subject_id FROM tbl_subjects WHERE subject_name = ? LIMIT 1");
            $check->bind_param('s', $subjectName);
        }
        $check->execute();
        $res = $check->get_result();
        if ($row = $res->fetch_assoc()) {
            $subjectId = (int)$row['subject_id'];
            if ($subjectId === 0) {
                // Repair: reinsert to get a real ID
                $subjectId = resolve_subject_with_valid_id($conn_qr, $subjectName, $schoolId, $userId);
            }
        } else {
            // Create subject scoped to school/user when possible
            if ($schoolId) {
                $ins = $conn_qr->prepare("INSERT INTO tbl_subjects (subject_name, school_id, user_id) VALUES (?, ?, ?)");
                $uid = $userId ?: null;
                $ins->bind_param('sii', $subjectName, $schoolId, $uid);
            } else {
                $ins = $conn_qr->prepare("INSERT INTO tbl_subjects (subject_name) VALUES (?)");
                $ins->bind_param('s', $subjectName);
            }
            if (!$ins->execute()) {
                throw new Exception('Failed to create subject');
            }
            $subjectId = (int)$conn_qr->insert_id;
            if ($subjectId === 0) {
                $subjectId = resolve_subject_with_valid_id($conn_qr, $subjectName, $schoolId, $userId);
            }
        }

        // Ensure relation with current instructor if available
        if (!empty($_SESSION['current_instructor_id'])) {
            $instructorId = (int)$_SESSION['current_instructor_id'];
            $rel = $conn_qr->prepare("SELECT id FROM tbl_instructor_subjects WHERE instructor_id=? AND subject_id=? LIMIT 1");
            $rel->bind_param('ii', $instructorId, $subjectId);
            $rel->execute();
            $relRes = $rel->get_result();
            if ($relRes->num_rows === 0) {
                $addRel = $conn_qr->prepare("INSERT INTO tbl_instructor_subjects (instructor_id, subject_id) VALUES (?, ?)");
                $addRel->bind_param('ii', $instructorId, $subjectId);
                $addRel->execute();
            }
        }
    }

    if ($subjectId !== null && $subjectId > 0) {
        $_SESSION['current_subject_id'] = $subjectId;
        error_log("Subject ID set in session: " . $subjectId);
    }
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Failed to set subject','error'=>$e->getMessage()]);
    exit;
}

// Response
echo json_encode([
    'success' => true,
    'message' => 'Subject set',
    'data' => [
        'subject_name' => $_SESSION['current_subject_name'],
        'subject_id'   => $_SESSION['current_subject_id'] ?? null
    ]
]);
