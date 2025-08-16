<?php
// Auto schema fixer for attendance tables
// - Adds generated columns for dedupe
// - Deduplicates existing data (keeps the earliest time_in per group)
// - Adds unique and supporting indexes
// Run in browser while logged in. Outputs a simple report.

require_once(__DIR__ . '/../conn/db_connect.php');
require_once(__DIR__ . '/../includes/session_config.php');

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo '<h3>Unauthorized</h3><p>Please login first.</p>';
    exit;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function getDatabaseName(mysqli $conn) {
    $res = $conn->query('SELECT DATABASE()');
    if ($res && ($row = $res->fetch_row())) return $row[0];
    return null;
}

function columnExists(mysqli $conn, $db, $table, $column) {
    $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $db, $table, $column);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

function indexExists(mysqli $conn, $db, $table, $indexName) {
    $sql = "SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $db, $table, $indexName);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

function tableExists(mysqli $conn, $db, $table) {
    $sql = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $db, $table);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

function ensureColumn(mysqli $conn, $db, $table, $column, $definition, &$report, &$errors) {
    if (!columnExists($conn, $db, $table, $column)) {
        $err = safeQuery($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        if ($err) { $errors[] = "$table.$column add failed: $err"; }
        else { $report[] = "Added column $table.$column"; }
    }
}

function safeQuery(mysqli $conn, $sql) {
    if (!$conn->query($sql)) {
        return $conn->error;
    }
    return null;
}

$dbName = getDatabaseName($conn_qr);
if (!$dbName) {
    echo '<h3>Database error</h3><p>Could not detect database name.</p>';
    exit;
}

$report = [];
$errors = [];

// 1) Ensure helper columns on tbl_attendance
// Prefer STORED generated columns; fallback to physical columns + triggers
$table = 'tbl_attendance';

// a) time_in_date
if (!columnExists($conn_qr, $dbName, $table, 'time_in_date')) {
    $err = safeQuery($conn_qr, "ALTER TABLE `$table` ADD COLUMN `time_in_date` DATE AS (DATE(`time_in`)) STORED");
    if ($err) {
        // fallback to physical column
        $report[] = 'Generated column not supported for time_in_date; using physical column with trigger.';
        $err2 = safeQuery($conn_qr, "ALTER TABLE `$table` ADD COLUMN `time_in_date` DATE NULL");
        if ($err2) { $errors[] = "time_in_date add failed: $err2"; }
        else {
            // backfill
            safeQuery($conn_qr, "UPDATE `$table` SET `time_in_date` = DATE(`time_in`) WHERE `time_in` IS NOT NULL AND (`time_in_date` IS NULL OR `time_in_date` <> DATE(`time_in`))");
            // trigger before insert
            safeQuery($conn_qr, "DROP TRIGGER IF EXISTS trg_{$table}_bid");
            $errT1 = safeQuery($conn_qr, "CREATE TRIGGER trg_{$table}_bid BEFORE INSERT ON `$table` FOR EACH ROW SET NEW.time_in_date = DATE(NEW.time_in)");
            if ($errT1) { $errors[] = "trigger before insert time_in_date: $errT1"; }
            // trigger before update
            safeQuery($conn_qr, "DROP TRIGGER IF EXISTS trg_{$table}_bud");
            $errT2 = safeQuery($conn_qr, "CREATE TRIGGER trg_{$table}_bud BEFORE UPDATE ON `$table` FOR EACH ROW SET NEW.time_in_date = DATE(NEW.time_in)");
            if ($errT2) { $errors[] = "trigger before update time_in_date: $errT2"; }
        }
    } else {
        $report[] = 'Added STORED generated column: tbl_attendance.time_in_date';
    }
}

// b) subject_id_nz
if (!columnExists($conn_qr, $dbName, $table, 'subject_id_nz')) {
    $err = safeQuery($conn_qr, "ALTER TABLE `$table` ADD COLUMN `subject_id_nz` INT AS (IFNULL(`subject_id`,0)) STORED");
    if ($err) {
        $report[] = 'Generated column not supported for subject_id_nz; using physical column with trigger.';
        $err2 = safeQuery($conn_qr, "ALTER TABLE `$table` ADD COLUMN `subject_id_nz` INT NULL DEFAULT 0");
        if ($err2) { $errors[] = "subject_id_nz add failed: $err2"; }
        else {
            safeQuery($conn_qr, "UPDATE `$table` SET `subject_id_nz` = IFNULL(`subject_id`,0)");
            safeQuery($conn_qr, "DROP TRIGGER IF EXISTS trg_{$table}_sub_bid");
            $errT1 = safeQuery($conn_qr, "CREATE TRIGGER trg_{$table}_sub_bid BEFORE INSERT ON `$table` FOR EACH ROW SET NEW.subject_id_nz = IFNULL(NEW.subject_id,0)");
            if ($errT1) { $errors[] = "trigger before insert subject_id_nz: $errT1"; }
            safeQuery($conn_qr, "DROP TRIGGER IF EXISTS trg_{$table}_sub_bud");
            $errT2 = safeQuery($conn_qr, "CREATE TRIGGER trg_{$table}_sub_bud BEFORE UPDATE ON `$table` FOR EACH ROW SET NEW.subject_id_nz = IFNULL(NEW.subject_id,0)");
            if ($errT2) { $errors[] = "trigger before update subject_id_nz: $errT2"; }
        }
    } else {
        $report[] = 'Added STORED generated column: tbl_attendance.subject_id_nz';
    }
}

// c) instructor_id_nz
if (!columnExists($conn_qr, $dbName, $table, 'instructor_id_nz')) {
    $err = safeQuery($conn_qr, "ALTER TABLE `$table` ADD COLUMN `instructor_id_nz` INT AS (IFNULL(`instructor_id`,0)) STORED");
    if ($err) {
        $report[] = 'Generated column not supported for instructor_id_nz; using physical column with trigger.';
        $err2 = safeQuery($conn_qr, "ALTER TABLE `$table` ADD COLUMN `instructor_id_nz` INT NULL DEFAULT 0");
        if ($err2) { $errors[] = "instructor_id_nz add failed: $err2"; }
        else {
            safeQuery($conn_qr, "UPDATE `$table` SET `instructor_id_nz` = IFNULL(`instructor_id`,0)");
            safeQuery($conn_qr, "DROP TRIGGER IF EXISTS trg_{$table}_ins_bid");
            $errT1 = safeQuery($conn_qr, "CREATE TRIGGER trg_{$table}_ins_bid BEFORE INSERT ON `$table` FOR EACH ROW SET NEW.instructor_id_nz = IFNULL(NEW.instructor_id,0)");
            if ($errT1) { $errors[] = "trigger before insert instructor_id_nz: $errT1"; }
            safeQuery($conn_qr, "DROP TRIGGER IF EXISTS trg_{$table}_ins_bud");
            $errT2 = safeQuery($conn_qr, "CREATE TRIGGER trg_{$table}_ins_bud BEFORE UPDATE ON `$table` FOR EACH ROW SET NEW.instructor_id_nz = IFNULL(NEW.instructor_id,0)");
            if ($errT2) { $errors[] = "trigger before update instructor_id_nz: $errT2"; }
        }
    } else {
        $report[] = 'Added STORED generated column: tbl_attendance.instructor_id_nz';
    }
}

// 2) Deduplicate tbl_attendance
// Find duplicates (same student, tenant, subject/instructor normalized, same date)
$dupCount = 0;
$dupSql = "SELECT tbl_student_id, user_id, school_id, subject_id_nz, instructor_id_nz, time_in_date, COUNT(*) c
           FROM `$table`
           GROUP BY tbl_student_id, user_id, school_id, subject_id_nz, instructor_id_nz, time_in_date
           HAVING c > 1";
if ($res = $conn_qr->query($dupSql)) {
    while ($g = $res->fetch_assoc()) {
        $dupCount += (int)$g['c'] - 1;
        // delete older duplicates, keep earliest by time_in
        $sel = $conn_qr->prepare(
            "SELECT tbl_attendance_id FROM `$table`
             WHERE tbl_student_id=? AND user_id=? AND school_id=? AND subject_id_nz=? AND instructor_id_nz=? AND time_in_date=?
             ORDER BY time_in ASC, tbl_attendance_id ASC"
        );
        $sel->bind_param('iiiiis', $g['tbl_student_id'], $g['user_id'], $g['school_id'], $g['subject_id_nz'], $g['instructor_id_nz'], $g['time_in_date']);
        $sel->execute();
        $rs = $sel->get_result();
        $keep = true;
        while ($row = $rs->fetch_assoc()) {
            if ($keep) { $keep = false; continue; }
            $id = (int)$row['tbl_attendance_id'];
            safeQuery($conn_qr, "DELETE FROM `$table` WHERE tbl_attendance_id=" . $id);
        }
    }
}
if ($dupCount > 0) $report[] = "Removed duplicate rows in tbl_attendance: " . (int)$dupCount;

// 3) Unique index for dedupe on tbl_attendance
$uqName = 'uq_attendance_unique_day';
if (!indexExists($conn_qr, $dbName, $table, $uqName)) {
    $err = safeQuery($conn_qr, "ALTER TABLE `$table` ADD UNIQUE KEY `$uqName` (`tbl_student_id`,`user_id`,`school_id`,`subject_id_nz`,`instructor_id_nz`,`time_in_date`)");
    if ($err) { $errors[] = "unique index creation failed: $err"; }
    else { $report[] = 'Created unique index on tbl_attendance to prevent duplicates per day.'; }
}

// 4) Ensure attendance_sessions and attendance_logs exist and are indexed
$sessTable = 'attendance_sessions';
$logsTable = 'attendance_logs';
$logsUq = 'uq_logs_session_student';

// attendance_sessions: create if missing, else ensure columns/indexes
if (!tableExists($conn_qr, $dbName, $sessTable)) {
    $err = safeQuery($conn_qr, "CREATE TABLE `$sessTable` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `instructor_id` INT NOT NULL,
        `course_id` INT NOT NULL,
        `term` VARCHAR(100) NOT NULL,
        `section` VARCHAR(100) NOT NULL,
        `start_time` DATETIME NOT NULL,
        `end_time` DATETIME NOT NULL,
        `school_id` INT DEFAULT 1,
        KEY `idx_sessions_instr_course_time` (`instructor_id`,`course_id`,`start_time`,`end_time`),
        KEY `idx_sessions_school` (`school_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if ($err) { $errors[] = "create $sessTable failed: $err"; }
    else { $report[] = "Created table $sessTable"; }
} else {
    ensureColumn($conn_qr, $dbName, $sessTable, 'school_id', 'INT DEFAULT 1', $report, $errors);
    if (!indexExists($conn_qr, $dbName, $sessTable, 'idx_sessions_instr_course_time')) {
        $err = safeQuery($conn_qr, "CREATE INDEX idx_sessions_instr_course_time ON `$sessTable` (instructor_id, course_id, start_time, end_time)");
        if ($err) { $errors[] = "attendance_sessions index failed: $err"; }
        else { $report[] = 'Created index on attendance_sessions (instructor_id, course_id, start_time, end_time).'; }
    }
    if (!indexExists($conn_qr, $dbName, $sessTable, 'idx_sessions_school')) {
        $err = safeQuery($conn_qr, "CREATE INDEX idx_sessions_school ON `$sessTable` (school_id)");
        if ($err) { $errors[] = "attendance_sessions school index failed: $err"; }
        else { $report[] = 'Created index on attendance_sessions (school_id).'; }
    }
}

// attendance_logs: create if missing, else ensure columns, dedupe and unique index
if (!tableExists($conn_qr, $dbName, $logsTable)) {
    $err = safeQuery($conn_qr, "CREATE TABLE `$logsTable` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `session_id` INT NOT NULL,
        `student_id` INT NOT NULL,
        `scan_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `school_id` INT DEFAULT 1,
        UNIQUE KEY `uq_logs_session_student` (`session_id`,`student_id`),
        KEY `idx_logs_session` (`session_id`),
        KEY `idx_logs_student` (`student_id`),
        CONSTRAINT `fk_logs_session` FOREIGN KEY (`session_id`) REFERENCES `$sessTable`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if ($err) { $errors[] = "create $logsTable failed: $err"; }
    else { $report[] = "Created table $logsTable"; }
} else {
    ensureColumn($conn_qr, $dbName, $logsTable, 'school_id', 'INT DEFAULT 1', $report, $errors);
    // Deduplicate existing rows
    $dupLogs = 0;
    $sqlDL = "SELECT session_id, student_id, COUNT(*) c FROM `$logsTable` GROUP BY session_id, student_id HAVING c>1";
    if ($resL = $conn_qr->query($sqlDL)) {
        while ($g = $resL->fetch_assoc()) {
            $dupLogs += (int)$g['c'] - 1;
            $sel = $conn_qr->prepare("SELECT id FROM `$logsTable` WHERE session_id=? AND student_id=? ORDER BY scan_time ASC, id ASC");
            $sel->bind_param('ii', $g['session_id'], $g['student_id']);
            $sel->execute();
            $rs = $sel->get_result();
            $keep = true;
            while ($row = $rs->fetch_assoc()) {
                if ($keep) { $keep = false; continue; }
                $id = (int)$row['id'];
                safeQuery($conn_qr, "DELETE FROM `$logsTable` WHERE id=" . $id);
            }
        }
    }
    if ($dupLogs > 0) $report[] = 'Removed duplicate rows in attendance_logs: ' . (int)$dupLogs;

    if (!indexExists($conn_qr, $dbName, $logsTable, $logsUq)) {
        $err = safeQuery($conn_qr, "ALTER TABLE `$logsTable` ADD UNIQUE KEY `$logsUq` (`session_id`,`student_id`)");
        if ($err) { $errors[] = "attendance_logs unique index failed: $err"; }
        else { $report[] = 'Created unique index on attendance_logs (session_id, student_id).'; }
    }
}

echo '<h2>Schema Fix Report</h2>';
if ($report) {
    echo '<ul>';
    foreach ($report as $line) echo '<li>' . h($line) . '</li>';
    echo '</ul>';
} else {
    echo '<p>No changes were necessary.</p>';
}

if ($errors) {
    echo '<h3>Warnings/Errors</h3><ul style="color:#a00">';
    foreach ($errors as $line) echo '<li>' . h($line) . '</li>';
    echo '</ul>';
}

echo '<p>Done.</p>';
?>
