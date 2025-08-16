<?php
require_once("../conn/db_connect.php");
require_once("../includes/session_config.php");

// Ensure database connections are available
if (!isset($conn_qr)) {
    $hostName = "127.0.0.1"; $dbUser = "root"; $dbPassword = ""; $qrDb = "qr_attendance_db";
    $conn_qr = mysqli_connect($hostName, $dbUser, $dbPassword, $qrDb);
    if (!$conn_qr) {
        $temp_conn = mysqli_connect($hostName, $dbUser, $dbPassword);
        if ($temp_conn) { mysqli_query($temp_conn, "CREATE DATABASE IF NOT EXISTS $qrDb"); mysqli_close($temp_conn); }
        $conn_qr = mysqli_connect($hostName, $dbUser, $dbPassword, $qrDb);
    }
}

function ensureQrConnection() {
    $hostName = "127.0.0.1"; $dbUser = "root"; $dbPassword = ""; $qrDb = "qr_attendance_db";
    if (!isset($GLOBALS['conn_qr']) || !$GLOBALS['conn_qr'] || !@mysqli_ping($GLOBALS['conn_qr'])) {
        if (isset($GLOBALS['conn_qr']) && $GLOBALS['conn_qr']) { @mysqli_close($GLOBALS['conn_qr']); }
        $GLOBALS['conn_qr'] = @mysqli_connect($hostName, $dbUser, $dbPassword, $qrDb);
    }
    return $GLOBALS['conn_qr'];
}

$conn_qr = ensureQrConnection();
if (!$conn_qr) {
    $errorParams = http_build_query([
        'error' => 'db_error', 'message' => 'Database connection error',
        'details' => mysqli_connect_error() ?: 'Unable to connect to the database. Please try again.'
    ]);
    echo "<script>window.location.href='http://localhost/Qnnect/index.php?{$errorParams}';</script>"; exit();
}

// Session guard
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json'); echo json_encode(['success'=>false,'error'=>'session_expired','message'=>'Session expired. Please log in again.','redirect'=>'admin/login.php']); exit();
    }
    echo "<script>localStorage.setItem('sessionError','Session expired. Please log in again.');window.location.href='http://localhost/Qnnect/admin/login.php';</script>"; exit();
}

$user_id = (int)$_SESSION['user_id'];
$school_id = (int)$_SESSION['school_id'];
$attendanceMode = $_SESSION['attendance_mode'] ?? 'general';

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qrCode = isset($_POST['qr_code']) ? trim($_POST['qr_code']) : '';
    if ($qrCode === '') {
        $errorParams = http_build_query(['error'=>'empty_qr','message'=>'No QR code detected','details'=>'Please scan a valid QR code to mark attendance.','timestamp'=>time()]);
        echo "<script>window.location.href='http://localhost/Qnnect/index.php?{$errorParams}';</script>"; exit();
    }

    // Resolve class start time from session/DB
    $class_start_time = $_SESSION['class_start_time_formatted'] ?? ($_SESSION['class_start_time'] ?? '08:00:00');
    if (strlen($class_start_time) == 5) { $class_start_time .= ':00'; }
    if (preg_match('/^(\d{1,2}):(\d{2})(:\d{2})?\s*(AM|PM)$/i', $class_start_time, $m)) {
        $h = (int)$m[1]; $min = $m[2]; $sec = isset($m[3]) ? $m[3] : ':00'; $p = strtoupper($m[4]);
        if ($p==='PM' && $h<12) $h+=12; elseif ($p==='AM' && $h==12) $h=0; $class_start_time = sprintf('%02d:%s%s',$h,$min,$sec);
    }

    $currentInstructorId = isset($_SESSION['current_instructor_id']) ? (int)$_SESSION['current_instructor_id'] : 0;
    $currentSubjectId    = isset($_SESSION['current_subject_id']) ? (int)$_SESSION['current_subject_id'] : 0;
    $currentSubjectName  = $_SESSION['current_subject_name'] ?? ($_SESSION['current_subject'] ?? 'Not Set');

    // Resolve instructor_id if missing but name present
    if ($currentInstructorId <= 0 && !empty($_SESSION['current_instructor_name'])) {
        $name = trim($_SESSION['current_instructor_name']);
        $stmtI = $conn_qr->prepare("SELECT instructor_id FROM tbl_instructors WHERE instructor_name = ? LIMIT 1");
        if ($stmtI) {
            $stmtI->bind_param('s', $name);
            $stmtI->execute();
            $resI = $stmtI->get_result();
            if ($rowI = $resI->fetch_assoc()) {
                $currentInstructorId = (int)$rowI['instructor_id'];
            } else {
                $insI = $conn_qr->prepare("INSERT INTO tbl_instructors (instructor_name) VALUES (?)");
                $insI->bind_param('s', $name);
                if ($insI->execute()) { $currentInstructorId = (int)$conn_qr->insert_id; }
            }
            $_SESSION['current_instructor_id'] = $currentInstructorId;
        }
    }

    // Resolve subject_id if missing but subject name present
    if ($currentSubjectId <= 0 && !empty($currentSubjectName) && $currentSubjectName !== 'Not Set') {
        // Helper: ensure tbl_subjects.subject_id is PK+AUTO_INCREMENT without causing conflicts
        $ensurePkAi = function(mysqli $c) {
            $dbRes = $c->query('SELECT DATABASE()');
            $dbName = ($dbRes && ($r=$dbRes->fetch_row())) ? $r[0] : null;
            if (!$dbName) return;
            $aiRes = $c->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$c->real_escape_string($dbName)."' AND TABLE_NAME='tbl_subjects' AND EXTRA LIKE '%auto_increment%'");
            $aiCols = [];
            if ($aiRes) { while ($row = $aiRes->fetch_assoc()) { $aiCols[] = $row['COLUMN_NAME']; } }
            if (in_array('subject_id', $aiCols, true)) return;
            if (count($aiCols) > 0) return; // don't create conflict
            @$c->query("ALTER TABLE tbl_subjects ADD PRIMARY KEY (subject_id)");
            @$c->query("ALTER TABLE tbl_subjects MODIFY COLUMN subject_id INT NOT NULL AUTO_INCREMENT");
        };

        if (isset($_SESSION['school_id']) && $_SESSION['school_id']) {
            $stmtS = $conn_qr->prepare("SELECT subject_id FROM tbl_subjects WHERE subject_name = ? AND school_id = ? LIMIT 1");
            $stmtS->bind_param('si', $currentSubjectName, $_SESSION['school_id']);
        } else {
            $stmtS = $conn_qr->prepare("SELECT subject_id FROM tbl_subjects WHERE subject_name = ? LIMIT 1");
            $stmtS->bind_param('s', $currentSubjectName);
        }
        if ($stmtS) {
            $stmtS->execute();
            $resS = $stmtS->get_result();
            if ($rowS = $resS->fetch_assoc()) {
                $currentSubjectId = (int)$rowS['subject_id'];
                if ($currentSubjectId === 0) {
                    // Attempt table fix and reinsert
                    $ensurePkAi($conn_qr);
                    if (isset($_SESSION['school_id']) && $_SESSION['school_id']) {
                        $fixIns = $conn_qr->prepare("INSERT INTO tbl_subjects (subject_name, school_id, user_id) VALUES (?, ?, ?)");
                        $uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
                        $fixIns->bind_param('sii', $currentSubjectName, $_SESSION['school_id'], $uid);
                    } else {
                        $fixIns = $conn_qr->prepare("INSERT INTO tbl_subjects (subject_name) VALUES (?)");
                        $fixIns->bind_param('s', $currentSubjectName);
                    }
                    if ($fixIns && $fixIns->execute()) {
                        $currentSubjectId = (int)$conn_qr->insert_id;
                    }
                }
            } else {
                if (isset($_SESSION['school_id']) && $_SESSION['school_id']) {
                    $insS = $conn_qr->prepare("INSERT INTO tbl_subjects (subject_name, school_id, user_id) VALUES (?, ?, ?)");
                    $uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
                    $insS->bind_param('sii', $currentSubjectName, $_SESSION['school_id'], $uid);
                } else {
                    $insS = $conn_qr->prepare("INSERT INTO tbl_subjects (subject_name) VALUES (?)");
                    $insS->bind_param('s', $currentSubjectName);
                }
                if ($insS->execute()) {
                    $currentSubjectId = (int)$conn_qr->insert_id;
                    if ($currentSubjectId === 0) {
                        // Retry after DDL
                        $ensurePkAi($conn_qr);
                        if (isset($_SESSION['school_id']) && $_SESSION['school_id']) {
                            $fixIns2 = $conn_qr->prepare("INSERT INTO tbl_subjects (subject_name, school_id, user_id) VALUES (?, ?, ?)");
                            $uid2 = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
                            $fixIns2->bind_param('sii', $currentSubjectName, $_SESSION['school_id'], $uid2);
                        } else {
                            $fixIns2 = $conn_qr->prepare("INSERT INTO tbl_subjects (subject_name) VALUES (?)");
                            $fixIns2->bind_param('s', $currentSubjectName);
                        }
                        if ($fixIns2 && $fixIns2->execute()) {
                            $currentSubjectId = (int)$conn_qr->insert_id;
                        }
                    }
                }
            }
            $_SESSION['current_subject_id'] = $currentSubjectId;
            $_SESSION['current_subject_name'] = $currentSubjectName;
        }
    }

    try {
        // STRICT student identification: only student QR (generated_code)
        $studentData = null;
        if (strpos($qrCode, 'STU-') === 0) {
            $parts = explode('-', $qrCode);
            if (count($parts) >= 5) {
                $sel = $conn_qr->prepare("SELECT tbl_student_id, student_name FROM tbl_student WHERE generated_code=? AND user_id=? AND school_id=? LIMIT 1");
                $sel->bind_param('sii', $qrCode, $user_id, $school_id); $sel->execute();
                $studentData = $sel->get_result()->fetch_assoc();
            }
        } else {
            // Some deployments may print the generated_code as-is
            $sel = $conn_qr->prepare("SELECT tbl_student_id, student_name FROM tbl_student WHERE generated_code=? AND user_id=? AND school_id=? LIMIT 1");
            $sel->bind_param('sii', $qrCode, $user_id, $school_id); $sel->execute();
            $studentData = $sel->get_result()->fetch_assoc();
        }

        if (!$studentData) {
            $errorParams = http_build_query([
                'error'=>'invalid_qr_format', 'message'=>'Invalid QR code for attendance',
                'details'=>'This QR does not identify a student. Please scan the student QR generated from Masterlist.',
                'timestamp'=>time()
            ]);
            echo "<script>window.location.href='http://localhost/Qnnect/index.php?{$errorParams}';</script>"; exit();
        }

        $studentID = (int)$studentData['tbl_student_id'];
        $studentName = $studentData['student_name'];

        // Compound duplicate rule: student + day + instructor + subject + tenant
        $conn_qr = ensureQrConnection();
        $chk = $conn_qr->prepare("SELECT * FROM tbl_attendance WHERE tbl_student_id=? AND DATE(time_in)=CURDATE() AND instructor_id=? AND subject_id=? AND user_id=? AND school_id=? LIMIT 1");
        $chk->bind_param('iiiii', $studentID, $currentInstructorId, $currentSubjectId, $user_id, $school_id);
        $chk->execute(); $attendanceRecord = $chk->get_result()->fetch_assoc();

        if (!$attendanceRecord) {
            $today = date('Y-m-d');
            $timeIn = date('Y-m-d H:i:s');
            if (strlen($class_start_time) == 5) { $class_start_time .= ':00'; }
            $class_dt = new DateTime($today.' '.$class_start_time);
            $in_dt    = new DateTime($timeIn);
            $status   = ($in_dt->getTimestamp() <= $class_dt->getTimestamp()) ? 'On Time' : 'Late';

            $conn_qr = ensureQrConnection();
            $ins = $conn_qr->prepare("INSERT INTO tbl_attendance (tbl_student_id, time_in, status, instructor_id, subject_id, user_id, school_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param('issiiii', $studentID, $timeIn, $status, $currentInstructorId, $currentSubjectId, $user_id, $school_id);
            $ok = $ins->execute();
            if ($ok) {
                $successParams = http_build_query(['success'=>'attendance_added','student'=>$studentName,'status'=>$status,'id'=>$conn_qr->insert_id]);
                header("Location: http://localhost/Qnnect/index.php?$successParams"); exit();
            } else {
                $errorParams = http_build_query(['error'=>'db_insert_failed','message'=>'Failed to save attendance','details'=>'Unable to save attendance record to database. Please try again.']);
                header("Location: http://localhost/Qnnect/index.php?$errorParams"); exit();
            }
        } else {
            $attendanceTime = date('h:i A', strtotime($attendanceRecord['time_in']));
            $attendanceDate = date('M d, Y', strtotime($attendanceRecord['time_in']));
            $attendanceStatus = $attendanceRecord['status'];
            $errorParams = http_build_query([
                'error'=>'duplicate_scan','message'=>'Attendance already recorded',
                'details'=>"Student $studentName already marked $attendanceStatus on $attendanceDate at $attendanceTime for $currentSubjectName."
            ]);
            echo "<script>window.location.href='http://localhost/Qnnect/index.php?{$errorParams}';</script>"; exit();
        }
    } catch (Exception $e) {
        error_log('add-attendance exception: '.$e->getMessage());
        $errorParams = http_build_query(['error'=>'db_error','message'=>'Database error','details'=>'Please try again.']);
        echo "<script>window.location.href='http://localhost/Qnnect/index.php?{$errorParams}';</script>"; exit();
    }
} else {
    $errorParams = http_build_query(['error'=>'missing_qr','message'=>'QR code data missing','details'=>'No QR code data was received. Please try scanning again.']);
    echo "<script>window.location.href='http://localhost/Qnnect/index.php?{$errorParams}';</script>"; exit();
}

?>
