<?php
// Utility: Ensure tables with log_id in qr_attendance_db have PRIMARY KEY and AUTO_INCREMENT
// Safe checks using information_schema; will not modify if duplicates exist

require_once(__DIR__ . '/../conn/db_connect.php');
require_once(__DIR__ . '/../includes/session_config.php');

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo '<h3>Unauthorized</h3><p>Please login first.</p>';
    exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function getDbName(mysqli $conn){
    $res = $conn->query('SELECT DATABASE()');
    if ($res && ($row = $res->fetch_row())) return $row[0];
    return null;
}

$db = getDbName($conn_qr);
if (!$db) { echo '<p>Cannot detect database name.</p>'; exit; }

// Find tables in this DB that have a column named log_id
$sql = "SELECT TABLE_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'log_id'";
$stmt = $conn_qr->prepare($sql);
$stmt->bind_param('s', $db);
$stmt->execute();
$res = $stmt->get_result();

$tables = [];
while ($row = $res->fetch_assoc()) { $tables[] = $row['TABLE_NAME']; }

if (!$tables) { echo '<p>No tables with log_id found in ' . h($db) . '.</p>'; exit; }

echo '<h2>Fix PRIMARY KEY on log_id (DB: ' . h($db) . ')</h2><ul>';

foreach ($tables as $table) {
    // Check if log_id is already the PRIMARY KEY
    $q = "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
          WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME='log_id' AND CONSTRAINT_NAME='PRIMARY' LIMIT 1";
    $s = $conn_qr->prepare($q);
    $s->bind_param('ss', $db, $table);
    $s->execute();
    $s->store_result();
    if ($s->num_rows > 0) {
        echo '<li>' . h($table) . ': log_id already PRIMARY KEY.</li>';
        continue;
    }

    // Ensure values are unique to allow PK add
    $dupQ = "SELECT COUNT(*) c_all, COUNT(DISTINCT log_id) c_dist FROM `{$table}`";
    $dupRes = $conn_qr->query($dupQ);
    if ($dupRes && ($row = $dupRes->fetch_assoc())) {
        if ((int)$row['c_all'] !== (int)$row['c_dist']) {
            echo '<li style="color:#a00">' . h($table) . ': Cannot set PRIMARY KEY. Duplicate log_id values found.</li>';
            continue;
        }
    }

    // Detect column type and whether it's already AUTO_INCREMENT
    $colQ = "SELECT COLUMN_TYPE, EXTRA FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME='log_id'";
    $cs = $conn_qr->prepare($colQ);
    $cs->bind_param('ss', $db, $table);
    $cs->execute();
    $cr = $cs->get_result()->fetch_assoc();
    $colType = $cr ? $cr['COLUMN_TYPE'] : 'INT';
    $extra = strtolower($cr['EXTRA'] ?? '');

    // See if some other column already has AUTO_INCREMENT
    $aiOtherQ = "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND EXTRA LIKE '%auto_increment%' AND COLUMN_NAME!='log_id' LIMIT 1";
    $aiStmt = $conn_qr->prepare($aiOtherQ);
    $aiStmt->bind_param('ss', $db, $table);
    $aiStmt->execute();
    $aiOther = $aiStmt->get_result()->fetch_assoc();

    $hasAuto = (strpos($extra, 'auto_increment') !== false);
    $otherAutoExists = (bool)$aiOther;

    // If log_id already AUTO_INCREMENT, just add PRIMARY KEY
    if ($hasAuto) {
        $sql = "ALTER TABLE `{$table}` ADD PRIMARY KEY (`log_id`)";
        if (!$conn_qr->query($sql)) {
            echo '<li style="color:#a00">' . h($table) . ': Failed to add PRIMARY KEY: ' . h($conn_qr->error) . '</li>';
            continue;
        }
        echo '<li>' . h($table) . ': Added PRIMARY KEY on existing AUTO_INCREMENT log_id.</li>';
        continue;
    }

    // If another AI column exists, avoid setting log_id to AUTO_INCREMENT; just add PK
    if ($otherAutoExists) {
        $sql = "ALTER TABLE `{$table}` ADD PRIMARY KEY (`log_id`)";
        if (!$conn_qr->query($sql)) {
            echo '<li style="color:#a00">' . h($table) . ': Failed to add PRIMARY KEY (another AUTO_INCREMENT exists): ' . h($conn_qr->error) . '</li>';
            continue;
        }
        echo '<li>' . h($table) . ': Added PRIMARY KEY on log_id (left other AUTO_INCREMENT intact).</li>';
        continue;
    }

    // Add PRIMARY KEY together with AUTO_INCREMENT in a single ALTER to satisfy engine rule
    $sql = "ALTER TABLE `{$table}` MODIFY `log_id` {$colType} NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`log_id`)";
    if (!$conn_qr->query($sql)) {
        echo '<li style="color:#a00">' . h($table) . ': Failed to set AUTO_INCREMENT + PRIMARY KEY: ' . h($conn_qr->error) . '</li>';
        continue;
    }
    echo '<li>' . h($table) . ': Set log_id AUTO_INCREMENT and PRIMARY KEY.</li>';
}

echo '</ul><p>Done.</p>';

?>
