<?php
// Normalize class_time_settings start_time values and reset session time variables
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../includes/session_config.php');
require_once('../conn/db_connect.php');

function normalize_time_to_hms($time) {
    if (!$time) return '08:00:00';
    $time = trim((string)$time);
    // If it's already HH:MM:SS
    if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) { return $time; }
    // If malformed like HH:MM:SS:SS, trim to 8 chars
    if (preg_match('/^\d{1,2}:\d{2}:\d{2}:\d{2}$/', $time)) { return substr($time, 0, 8); }
    // Try common formats
    $formats = ['H:i', 'h:i A', 'h:i:s A'];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $time);
        if ($dt) { return $dt->format('H:i:s'); }
    }
    $ts = @strtotime($time);
    if ($ts !== false) { return date('H:i:s', $ts); }
    return '08:00:00';
}

$school_id = $_SESSION['school_id'] ?? 1;
$updated = 0; $checked = 0; $created = false; $hadTable = false;

echo '<h2>Cleanup: Class Time Normalization</h2>';

if (!isset($conn_qr) || !($conn_qr instanceof mysqli)) {
    echo '<p style="color:red">No DB connection.</p>';
    exit;
}

// Ensure table exists (same shape as set-class-time)
$res = $conn_qr->query("SHOW TABLES LIKE 'class_time_settings'");
if ($res && $res->num_rows > 0) { $hadTable = true; }
if (!$hadTable) {
    $sql = "CREATE TABLE class_time_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT NOT NULL,
        start_time TIME NOT NULL,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_school_time (school_id)
    )";
    if ($conn_qr->query($sql)) { $created = true; echo '<p>Created table class_time_settings.</p>'; }
}

// Fetch all rows for this school
$rows = [];
$rs = $conn_qr->prepare("SELECT id, school_id, start_time, status FROM class_time_settings WHERE school_id = ?");
if ($rs) {
    $rs->bind_param('i', $school_id);
    if ($rs->execute()) {
        $result = $rs->get_result();
        while ($row = $result->fetch_assoc()) { $rows[] = $row; }
    }
    $rs->close();
}

if (!$rows) {
    echo '<p>No rows for school_id ' . htmlspecialchars((string)$school_id) . ' — inserting default 08:00:00.</p>';
    $ins = $conn_qr->prepare("INSERT INTO class_time_settings (school_id, start_time, status, created_at, updated_at) VALUES (?, '08:00:00', 'active', NOW(), NOW()) ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), status='active', updated_at=NOW()");
    if ($ins && $ins->execute()) { echo '<p>Inserted/updated default time.</p>'; }
}

// Normalize existing rows
$sel = $conn_qr->prepare("SELECT id, start_time FROM class_time_settings WHERE school_id = ?");
if ($sel) {
    $sel->bind_param('i', $school_id);
    if ($sel->execute()) {
        $res2 = $sel->get_result();
        while ($r = $res2->fetch_assoc()) {
            $checked++;
            $id = (int)$r['id'];
            $raw = (string)$r['start_time'];
            $norm = normalize_time_to_hms($raw);
            if ($norm !== $raw) {
                $upd = $conn_qr->prepare("UPDATE class_time_settings SET start_time=?, updated_at=NOW(), status='active' WHERE id=? AND school_id=?");
                if ($upd) {
                    $upd->bind_param('sii', $norm, $id, $school_id);
                    if ($upd->execute()) {
                        $updated++;
                        echo '<p>Row #' . $id . ': ' . htmlspecialchars($raw) . ' -> ' . htmlspecialchars($norm) . '</p>';
                    }
                    $upd->close();
                }
            }
        }
    }
    $sel->close();
}

// Reset session to consistent values from DB
$row = null;
$q = $conn_qr->prepare("SELECT start_time FROM class_time_settings WHERE school_id=? ORDER BY updated_at DESC LIMIT 1");
if ($q) {
    $q->bind_param('i', $school_id);
    if ($q->execute()) {
        $res3 = $q->get_result();
        $row = $res3->fetch_assoc();
    }
    $q->close();
}

if ($row) {
    $hms = normalize_time_to_hms($row['start_time']);
    $_SESSION['class_start_time'] = substr($hms, 0, 5);
    $_SESSION['class_start_time_formatted'] = $hms;
    echo '<p>Session updated: HM=' . htmlspecialchars($_SESSION['class_start_time']) . ', HMS=' . htmlspecialchars($_SESSION['class_start_time_formatted']) . '</p>';
}

echo '<hr><p>Checked rows: ' . $checked . ', Updated rows: ' . $updated . '</p>';
echo '<p><a href="../index.php">Back to Dashboard</a></p>';
?>
