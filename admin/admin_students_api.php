<?php
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once '../conn/db_connect.php';

header('Content-Type: application/json');

// Ensure session is started and properly configured
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Additional session validation
if (!isset($_SESSION) || empty($_SESSION)) {
    echo json_encode(['success' => false, 'message' => 'Session not initialized']);
    exit();
}

// Check authentication - must be super admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
	echo json_encode(['success' => false, 'message' => 'Access denied - super admin role required']);
	exit();
}

$scopeSchoolId = getEffectiveScopeSchoolId();

// Use QR DB for student table, and join school name via login_register DB if available
$conn_qr = $GLOBALS['conn_qr'] ?? null;
$conn_login = $GLOBALS['conn_login'] ?? null;

if (!$conn_qr) {
	echo json_encode(['success' => false, 'message' => 'Database not available']);
	exit();
}

$op = $_POST['op'] ?? 'list';

if ($op === 'create') {
	$name = trim($_POST['student_name'] ?? '');
	$course_section = trim($_POST['course_section'] ?? '');
	$school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : ($scopeSchoolId ?? null);
	
	// Get a teacher/admin user_id for this school instead of using super admin's user_id
	$user_id = 1; // Default fallback
	if ($conn_login && $school_id) {
		$stmt_user = mysqli_prepare($conn_login, 'SELECT id FROM users WHERE school_id = ? AND role = "admin" LIMIT 1');
		mysqli_stmt_bind_param($stmt_user, 'i', $school_id);
		mysqli_stmt_execute($stmt_user);
		$res_user = mysqli_stmt_get_result($stmt_user);
		if ($res_user && $user_data = mysqli_fetch_assoc($res_user)) {
			$user_id = (int)$user_data['id'];
		}
		mysqli_stmt_close($stmt_user);
	}
	if ($name === '' || $course_section === '' || !$school_id) { echo json_encode(['success'=>false,'message'=>'Missing required fields']); exit(); }
	$sql = "INSERT INTO tbl_student (student_name, course_section, user_id, school_id) VALUES (?,?,?,?)";
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, 'ssii', $name, $course_section, $user_id, $school_id);
	$ok = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	echo json_encode(['success'=>$ok]);
	exit();
}

if ($op === 'update') {
	$id = (int)($_POST['tbl_student_id'] ?? 0);
	$name = isset($_POST['student_name']) ? trim($_POST['student_name']) : null;
	$course_section = isset($_POST['course_section']) ? trim($_POST['course_section']) : null;
	$school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : null;
	if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit(); }
	$fields = [];$types = '';$params = [];
	if ($name !== null) { $fields[]='student_name=?'; $types.='s'; $params[]=$name; }
	if ($course_section !== null) { $fields[]='course_section=?'; $types.='s'; $params[]=$course_section; }
	if ($school_id !== null) { 
		$fields[]='school_id=?'; $types.='i'; $params[]=$school_id; 
		// Also update user_id to match the school's admin
		if ($conn_login) {
			$stmt_user = mysqli_prepare($conn_login, 'SELECT id FROM users WHERE school_id = ? AND role = "admin" LIMIT 1');
			mysqli_stmt_bind_param($stmt_user, 'i', $school_id);
			mysqli_stmt_execute($stmt_user);
			$res_user = mysqli_stmt_get_result($stmt_user);
			if ($res_user && $user_data = mysqli_fetch_assoc($res_user)) {
				$fields[]='user_id=?'; $types.='i'; $params[]=(int)$user_data['id'];
			}
			mysqli_stmt_close($stmt_user);
		}
	}
	if (!$fields) { echo json_encode(['success'=>false,'message'=>'No changes']); exit(); }
	$types.='i'; $params[]=$id;
	$sql = 'UPDATE tbl_student SET '.implode(',', $fields).' WHERE tbl_student_id = ?';
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, $types, ...$params);
	$ok = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	echo json_encode(['success'=>$ok]);
	exit();
}

if ($op === 'delete') {
	$id = (int)($_POST['tbl_student_id'] ?? 0);
	if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit(); }
	$stmt = mysqli_prepare($conn_qr, 'DELETE FROM tbl_student WHERE tbl_student_id = ?');
	mysqli_stmt_bind_param($stmt, 'i', $id);
	$ok = mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	echo json_encode(['success'=>$ok]);
	exit();
}

// Default: list
if ($scopeSchoolId) {
	$sql = "SELECT s.tbl_student_id, s.student_name, s.course_section, s.school_id
	        FROM tbl_student s
	        WHERE s.school_id = ?
	        ORDER BY s.tbl_student_id DESC
	        LIMIT 500";
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, 'i', $scopeSchoolId);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
} else {
	$sql = "SELECT s.tbl_student_id, s.student_name, s.course_section, s.school_id
	        FROM tbl_student s
	        ORDER BY s.tbl_student_id DESC
	        LIMIT 500";
	$res = mysqli_query($conn_qr, $sql);
}

$students = [];
if ($res) {
	while ($row = mysqli_fetch_assoc($res)) {
		$students[] = $row;
	}
}

// Map school names if login DB is available
if ($conn_login && !empty($students)) {
	$ids = array_values(array_unique(array_map(function($r){ return (int)($r['school_id'] ?? 0); }, $students)));
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
		if ($resS) {
			while ($s = mysqli_fetch_assoc($resS)) { $map[(int)$s['id']] = $s['name']; }
		}
		foreach ($students as &$st) {
			$st['school_name'] = isset($st['school_id']) && isset($map[(int)$st['school_id']]) ? $map[(int)$st['school_id']] : null;
		}
	}
}

echo json_encode(['success' => true, 'students' => $students]);
exit();


