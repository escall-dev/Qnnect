<?php
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once '../conn/db_connect.php';

header('Content-Type: application/json');

if (!hasRole('super_admin')) { echo json_encode(['success'=>false,'message'=>'Access denied']); exit(); }
$scopeSchoolId = getEffectiveScopeSchoolId();
$conn_qr = $GLOBALS['conn_qr'] ?? null;
$conn_login = $GLOBALS['conn_login'] ?? null;
if (!$conn_qr) { echo json_encode(['success'=>false,'message'=>'Database not available']); exit(); }

$op = $_POST['op'] ?? 'list';

if ($op === 'create') {
	$subject = trim($_POST['subject'] ?? '');
	$section = trim($_POST['section'] ?? '');
	$day = trim($_POST['day_of_week'] ?? 'Monday');
	$start = $_POST['start_time'] ?? '08:00:00';
	$end = $_POST['end_time'] ?? '09:00:00';
	$room = trim($_POST['room'] ?? '');
	$teacher = trim($_POST['teacher_username'] ?? ($_SESSION['username'] ?? 'system'));
	$school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : ($scopeSchoolId ?? null);
	if ($subject === '' || $section === '' || !$school_id) { echo json_encode(['success'=>false,'message'=>'Missing fields']); exit(); }
	$sql = "INSERT INTO teacher_schedules (subject, section, day_of_week, start_time, end_time, room, teacher_username, school_id, status) VALUES (?,?,?,?,?,?,?,?,'active')";
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, 'sssssssi', $subject, $section, $day, $start, $end, $room, $teacher, $school_id);
	$ok = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	echo json_encode(['success'=>$ok]);
	exit();
}

if ($op === 'update') {
	$id = (int)($_POST['id'] ?? 0);
	if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit(); }
	$fields=[];$types='';$params=[];
	foreach (['subject','section','day_of_week','start_time','end_time','room','teacher_username'] as $k) {
		if (isset($_POST[$k])) { $fields[] = "$k=?"; $types .= 's'; $params[] = $_POST[$k]; }
	}
	if (isset($_POST['school_id'])) { $fields[]='school_id=?'; $types.='i'; $params[]=(int)$_POST['school_id']; }
	if (isset($_POST['status'])) { $fields[]='status=?'; $types.='s'; $params[]=$_POST['status']; }
	if (!$fields) { echo json_encode(['success'=>false,'message'=>'No changes']); exit(); }
	$types.='i'; $params[]=$id;
	$sql = 'UPDATE teacher_schedules SET '.implode(',', $fields).' WHERE id = ?';
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, $types, ...$params);
	$ok = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	echo json_encode(['success'=>$ok]);
	exit();
}

if ($op === 'delete') {
	$id = (int)($_POST['id'] ?? 0);
	if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit(); }
	$stmt = mysqli_prepare($conn_qr, 'DELETE FROM teacher_schedules WHERE id = ?');
	mysqli_stmt_bind_param($stmt, 'i', $id);
	$ok = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	echo json_encode(['success'=>$ok]);
	exit();
}

$rows = [];
if ($scopeSchoolId) {
	$sql = "SELECT id, subject, section, day_of_week, start_time, end_time, room, teacher_username, school_id
	        FROM teacher_schedules
	        WHERE school_id = ? AND status = 'active'
	        ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time DESC
	        LIMIT 1000";
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, 'i', $scopeSchoolId);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
} else {
	$sql = "SELECT id, subject, section, day_of_week, start_time, end_time, room, teacher_username, school_id
	        FROM teacher_schedules
	        WHERE status = 'active'
	        ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time DESC
	        LIMIT 1000";
	$res = mysqli_query($conn_qr, $sql);
}
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; } }
// Map school names if available
if ($conn_login && !empty($rows)) {
    $ids = array_values(array_unique(array_map(function($r){ return (int)($r['school_id'] ?? 0); }, $rows)));
    $ids = array_filter($ids, function($v){ return $v > 0; });
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $sqlS = "SELECT id, name FROM schools WHERE id IN ($in)";
        $stmtS = mysqli_prepare($conn_login, $sqlS);
        mysqli_stmt_bind_param($stmtS, $types, ...$ids);
        mysqli_stmt_execute($stmtS);
        $resS = mysqli_stmt_get_result($stmtS);
        $map = [];
        if ($resS) { while ($s = mysqli_fetch_assoc($resS)) { $map[(int)$s['id']] = $s['name']; } }
        foreach ($rows as &$r) { $r['school_name'] = isset($map[(int)($r['school_id'] ?? 0)]) ? $map[(int)$r['school_id']] : null; }
    }
}
echo json_encode(['success'=>true,'schedules'=>$rows]);
exit();


