<?php
// One-time repair script: make tbl_subjects.subject_id a PRIMARY KEY + AUTO_INCREMENT
require_once(__DIR__ . '/../conn/db_connect.php');
header('Content-Type: text/plain');

// Safety: only run locally with explicit confirmation
if (php_sapi_name() !== 'cli' && (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes')) {
    echo "Visit this script with ?confirm=yes to run.\n";
    exit;
}

function db_first(mysqli $conn, string $sql) {
    $res = $conn->query($sql);
    if ($res && $res->num_rows) return $res->fetch_row()[0];
    return null;
}

try {
    // Determine current database name
    $dbName = db_first($conn_qr, 'SELECT DATABASE()');
    if (!$dbName) { throw new Exception('Unable to determine database'); }

    echo "Database: {$dbName}\n";

    // 1) If any rows have subject_id=0, give them unique positive IDs first
    $maxIdRes = $conn_qr->query("SELECT COALESCE(MAX(subject_id),0) AS mx FROM tbl_subjects");
    $maxId = ($maxIdRes && ($r = $maxIdRes->fetch_assoc())) ? (int)$r['mx'] : 0;

    $zeroCntRes = $conn_qr->query("SELECT COUNT(*) AS c FROM tbl_subjects WHERE subject_id = 0");
    $zeroCnt = ($zeroCntRes && ($zr = $zeroCntRes->fetch_assoc())) ? (int)$zr['c'] : 0;
    echo "Rows with subject_id=0: {$zeroCnt}\n";

    while ($zeroCnt > 0) {
        $pickRes = $conn_qr->query("SELECT subject_name, school_id, created_at FROM tbl_subjects WHERE subject_id = 0 LIMIT 1");
        if (!$pickRes || !$pickRes->num_rows) break;
        $row = $pickRes->fetch_assoc();
        $name = $conn_qr->real_escape_string($row['subject_name']);
        $schoolClause = 'IS NOT DISTINCT FROM'; // emulate null-safe equal for readability
        $schoolVal = isset($row['school_id']) ? (int)$row['school_id'] : null;
        $maxId++;
        // Use NULL-safe comparison <=> in MySQL to match one row reliably
        $updateSql = is_null($schoolVal)
            ? "UPDATE tbl_subjects SET subject_id = {$maxId} WHERE subject_id = 0 AND subject_name = '{$name}' ORDER BY created_at ASC LIMIT 1"
            : "UPDATE tbl_subjects SET subject_id = {$maxId} WHERE subject_id = 0 AND subject_name = '{$name}' AND school_id <=> {$schoolVal} ORDER BY created_at ASC LIMIT 1";
        // Note: ORDER BY ... LIMIT in UPDATE is supported by MySQL
        $conn_qr->query($updateSql);

        // Recount remaining zeros
        $zeroCntRes = $conn_qr->query("SELECT COUNT(*) AS c FROM tbl_subjects WHERE subject_id = 0");
        $zeroCnt = ($zeroCntRes && ($zr = $zeroCntRes->fetch_assoc())) ? (int)$zr['c'] : 0;
    }

    // 2) Ensure PRIMARY KEY is on subject_id (drop any existing PK if different)
    $pkColRes = $conn_qr->query("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='".$conn_qr->real_escape_string($dbName)."' AND TABLE_NAME='tbl_subjects' AND CONSTRAINT_NAME='PRIMARY'");
    $pkCols = [];
    if ($pkColRes) { while ($r = $pkColRes->fetch_assoc()) { $pkCols[] = $r['COLUMN_NAME']; } }
    $hasPK = count($pkCols) > 0;
    $pkIsSubject = $hasPK && count($pkCols) === 1 && $pkCols[0] === 'subject_id';

    if ($hasPK && !$pkIsSubject) {
        echo "Dropping existing PRIMARY KEY (not on subject_id) ...\n";
        if (!$conn_qr->query("ALTER TABLE tbl_subjects DROP PRIMARY KEY")) {
            echo "Warning: DROP PRIMARY KEY failed: ".$conn_qr->error."\n";
        } else {
            $hasPK = false;
        }
    }
    if (!$hasPK) {
        echo "Adding PRIMARY KEY (subject_id) ...\n";
        if (!$conn_qr->query("ALTER TABLE tbl_subjects ADD PRIMARY KEY (subject_id)")) {
            echo "Warning: ADD PRIMARY KEY failed: ".$conn_qr->error."\n";
        }
    }

    // 3) If another AUTO_INCREMENT column exists (unlikely), do not proceed to avoid the MySQL error
    $aiColsRes = $conn_qr->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$conn_qr->real_escape_string($dbName)."' AND TABLE_NAME='tbl_subjects' AND EXTRA LIKE '%auto_increment%'");
    $aiCols = [];
    if ($aiColsRes) { while ($r = $aiColsRes->fetch_assoc()) { $aiCols[] = $r['COLUMN_NAME']; } }
    $aiOnSubject = in_array('subject_id', $aiCols, true);

    if (!$aiOnSubject && count($aiCols) > 0) {
        echo "Another AUTO_INCREMENT column exists on tbl_subjects: ".implode(',', $aiCols).". Skipping change to avoid conflict.\n";
    } else {
        echo "Ensuring subject_id is AUTO_INCREMENT ...\n";
        if (!$conn_qr->query("ALTER TABLE tbl_subjects MODIFY COLUMN subject_id INT NOT NULL AUTO_INCREMENT")) {
            echo "Warning: MODIFY subject_id AUTO_INCREMENT failed: ".$conn_qr->error."\n";
        }
    }

    echo "Repair complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
