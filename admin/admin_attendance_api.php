<?php
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once '../conn/db_connect.php';

header('Content-Type: application/json');

if (!hasRole('super_admin')) {
	echo json_encode(['success' => false, 'message' => 'Access denied']);
	exit();
}

$scopeSchoolId = getEffectiveScopeSchoolId();
$conn_qr = $GLOBALS['conn_qr'] ?? null;
$conn_login = $GLOBALS['conn_login'] ?? null;
if (!$conn_qr) { echo json_encode(['success'=>false,'message'=>'Database not available']); exit(); }

$op = $_POST['op'] ?? 'list';

if ($op === 'create') {
	$tbl_student_id = (int)($_POST['tbl_student_id'] ?? 0);
	$status = trim($_POST['status'] ?? 'On Time');
	$time_in = $_POST['time_in'] ?? date('Y-m-d H:i:s');
	$school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : ($scopeSchoolId ?? null);
	$user_id = $_SESSION['user_id'] ?? 1;
	if ($tbl_student_id <= 0 || !$school_id) { echo json_encode(['success'=>false,'message'=>'Missing fields']); exit(); }
	$sql = 'INSERT INTO tbl_attendance (tbl_student_id, time_in, status, user_id, school_id) VALUES (?,?,?,?,?)';
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, 'issii', $tbl_student_id, $time_in, $status, $user_id, $school_id);
	$ok = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	echo json_encode(['success'=>$ok]);
	exit();
}

if ($op === 'update') {
	$id = (int)($_POST['id'] ?? $_POST['tbl_attendance_id'] ?? 0);
	if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit(); }
	$fields=[];$types='';$params=[];
	if (isset($_POST['status'])) { $fields[]='status=?'; $types.='s'; $params[] = trim($_POST['status']); }
	if (isset($_POST['time_in'])) { $fields[]='time_in=?'; $types.='s'; $params[] = $_POST['time_in']; }
	if (isset($_POST['tbl_student_id'])) { $fields[]='tbl_student_id=?'; $types.='i'; $params[] = (int)$_POST['tbl_student_id']; }
	if (isset($_POST['school_id'])) { $fields[]='school_id=?'; $types.='i'; $params[] = (int)$_POST['school_id']; }
	if (!$fields) { echo json_encode(['success'=>false,'message'=>'No changes']); exit(); }
	$types.='i'; $params[]=$id;
	$sql = 'UPDATE tbl_attendance SET '.implode(',', $fields).' WHERE tbl_attendance_id = ?';
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, $types, ...$params);
	$ok = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	echo json_encode(['success'=>$ok]);
	exit();
}

if ($op === 'delete') {
	$id = (int)($_POST['id'] ?? $_POST['tbl_attendance_id'] ?? 0);
	if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit(); }
	$stmt = mysqli_prepare($conn_qr, 'DELETE FROM tbl_attendance WHERE tbl_attendance_id = ?');
	mysqli_stmt_bind_param($stmt, 'i', $id);
	$ok = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	echo json_encode(['success'=>$ok]);
	exit();
}

// Pagination support
$page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
$limit = max(1, min(100, (int)($_POST['limit'] ?? $_GET['limit'] ?? 10))); // Default 10 per page, max 100
$offset = ($page - 1) * $limit;

// Get total count for pagination
if ($scopeSchoolId) {
	$count_sql = "SELECT COUNT(*) as total FROM tbl_attendance a WHERE a.school_id = ?";
	$count_stmt = mysqli_prepare($conn_qr, $count_sql);
	mysqli_stmt_bind_param($count_stmt, 'i', $scopeSchoolId);
	mysqli_stmt_execute($count_stmt);
	$count_res = mysqli_stmt_get_result($count_stmt);
} else {
	$count_sql = "SELECT COUNT(*) as total FROM tbl_attendance a";
	$count_res = mysqli_query($conn_qr, $count_sql);
}

$total_records = 0;
if ($count_res) {
	$count_row = mysqli_fetch_assoc($count_res);
	$total_records = (int)($count_row['total'] ?? 0);
}

// Fetch latest attendance from tbl_attendance, join students for names with pagination
if ($scopeSchoolId) {
	$sql = "SELECT a.tbl_attendance_id AS id, a.tbl_student_id AS student_id, a.time_in, a.status, a.school_id,
	               s.student_name, s.course_section
	        FROM tbl_attendance a
	        LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id AND s.school_id = a.school_id
	        WHERE a.school_id = ?
	        ORDER BY a.time_in DESC
	        LIMIT ? OFFSET ?";
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, 'iii', $scopeSchoolId, $limit, $offset);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
} else {
	$sql = "SELECT a.tbl_attendance_id AS id, a.tbl_student_id AS student_id, a.time_in, a.status, a.school_id,
	               s.student_name, s.course_section
	        FROM tbl_attendance a
	        LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id AND s.school_id = a.school_id
	        ORDER BY a.time_in DESC
	        LIMIT ? OFFSET ?";
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
}

$rows = [];
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; } }
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
$total_pages = ceil($total_records / $limit);

echo json_encode([
	'success'=>true,
	'attendance'=>$rows,
	'pagination' => [
		'current_page' => $page,
		'total_pages' => $total_pages,
		'total_records' => $total_records,
		'limit' => $limit,
		'offset' => $offset
	]
]);
exit();


