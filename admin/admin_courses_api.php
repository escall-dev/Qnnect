<?php
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once '../conn/db_connect.php';

header('Content-Type: application/json');

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication - must be super admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
	echo json_encode(['success' => false, 'message' => 'Access denied - super admin role required']);
	exit();
}
$scopeSchoolId = getEffectiveScopeSchoolId();
$conn_qr = $GLOBALS['conn_qr'] ?? null;
$conn_login = $GLOBALS['conn_login'] ?? null;
if (!$conn_qr) { echo json_encode(['success'=>false,'message'=>'Database not available']); exit(); }

$op = $_POST['op'] ?? 'list';
$entity = $_POST['entity'] ?? 'list';

if ($op !== 'list') {
	if ($entity === 'course') {
		if ($op === 'create') {
			$name = trim($_POST['course_name'] ?? '');
			$school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : ($scopeSchoolId ?? null);
			
			// Get the school's admin user_id instead of super admin's user_id
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
			if ($name === '' || !$school_id) { echo json_encode(array('success'=>false,'message'=>'Missing fields')); exit(); }
			$stmt = mysqli_prepare($conn_qr, 'INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (?,?,?)');
			mysqli_stmt_bind_param($stmt, 'sii', $name, $user_id, $school_id);
			$ok = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			echo json_encode(array('success'=>$ok)); exit();
		}
		if ($op === 'update') {
			$id = (int)($_POST['course_id'] ?? 0); 
			$name = trim($_POST['course_name'] ?? '');
			$school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : null;
			
			if ($id<=0 || $name==='') { echo json_encode(array('success'=>false,'message'=>'Invalid')); exit(); }
			
			// Get the school's admin user_id for updates
			$user_id = null;
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
			
			// Update query - include school_id and user_id if provided
			if ($school_id && $user_id) {
				$stmt = mysqli_prepare($conn_qr, 'UPDATE tbl_courses SET course_name=?, school_id=?, user_id=? WHERE course_id=?');
				mysqli_stmt_bind_param($stmt, 'siii', $name, $school_id, $user_id, $id);
			} else {
				$stmt = mysqli_prepare($conn_qr, 'UPDATE tbl_courses SET course_name=? WHERE course_id=?');
				mysqli_stmt_bind_param($stmt, 'si', $name, $id);
			}
			$ok = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			echo json_encode(array('success'=>$ok)); exit();
		}
		if ($op === 'delete') {
			$id = (int)($_POST['course_id'] ?? 0);
			if ($id<=0) { echo json_encode(array('success'=>false,'message'=>'Invalid')); exit(); }
			$stmt = mysqli_prepare($conn_qr, 'DELETE FROM tbl_courses WHERE course_id=?');
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$ok = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			echo json_encode(array('success'=>$ok)); exit();
		}
	}
	if ($entity === 'section') {
		if ($op === 'create') {
			$name = trim($_POST['section_name'] ?? '');
			$course_id = (int)($_POST['course_id'] ?? 0);
			$school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : ($scopeSchoolId ?? null);
			
			// Get the school's admin user_id instead of super admin's user_id
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
			if ($name === '' || $course_id<=0 || !$school_id) { echo json_encode(array('success'=>false,'message'=>'Missing fields')); exit(); }
			$stmt = mysqli_prepare($conn_qr, 'INSERT INTO tbl_sections (section_name, course_id, user_id, school_id) VALUES (?,?,?,?)');
			mysqli_stmt_bind_param($stmt, 'siii', $name, $course_id, $user_id, $school_id);
			$ok = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			echo json_encode(array('success'=>$ok)); exit();
		}
		if ($op === 'update') {
			$id = (int)($_POST['section_id'] ?? 0); 
			$name = trim($_POST['section_name'] ?? ''); 
			$course_id = (int)($_POST['course_id'] ?? 0);
			$school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : null;
			
			if ($id<=0 || $name==='') { echo json_encode(array('success'=>false,'message'=>'Invalid')); exit(); }
			
			// Get the school's admin user_id for updates
			$user_id = null;
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
			
			$fields=array('section_name=?'); $types='s'; $params=array($name);
			if ($course_id>0) { $fields[]='course_id=?'; $types.='i'; $params[]=$course_id; }
			if ($school_id && $user_id) { 
				$fields[]='school_id=?'; $types.='i'; $params[]=$school_id; 
				$fields[]='user_id=?'; $types.='i'; $params[]=$user_id; 
			}
			$stmt = mysqli_prepare($conn_qr, 'UPDATE tbl_sections SET '.implode(',', $fields).' WHERE section_id=?');
			$types.='i'; $params[]=$id;
			mysqli_stmt_bind_param($stmt, $types, ...$params);
			$ok = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			echo json_encode(array('success'=>$ok)); exit();
		}
		if ($op === 'delete') {
			$id = (int)($_POST['section_id'] ?? 0);
			if ($id<=0) { echo json_encode(array('success'=>false,'message'=>'Invalid')); exit(); }
			$stmt = mysqli_prepare($conn_qr, 'DELETE FROM tbl_sections WHERE section_id=?');
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$ok = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);
			echo json_encode(array('success'=>$ok)); exit();
		}
	}
}

// Courses
if ($scopeSchoolId) {
	$coursesSql = "SELECT course_id, course_name, user_id, school_id FROM tbl_courses WHERE school_id = ? ORDER BY course_name LIMIT 1000";
	$stmt = mysqli_prepare($conn_qr, $coursesSql);
	mysqli_stmt_bind_param($stmt, 'i', $scopeSchoolId);
	mysqli_stmt_execute($stmt);
	$coursesRes = mysqli_stmt_get_result($stmt);
} else {
	$coursesSql = "SELECT course_id, course_name, user_id, school_id FROM tbl_courses ORDER BY course_name LIMIT 1000";
	$coursesRes = mysqli_query($conn_qr, $coursesSql);
}
$courses = array();
if ($coursesRes) { while ($c = mysqli_fetch_assoc($coursesRes)) { $courses[] = $c; } }

// Map school names and theme colors for courses
if ($conn_login && !empty($courses)) {
    $ids = array_values(array_unique(array_map(function($r){ return (int)($r['school_id'] ?? 0); }, $courses)));
    $ids = array_filter($ids, function($v){ return $v > 0; });
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $sqlS = "SELECT id, name, theme_color FROM schools WHERE id IN ($in)";
        $stmtS = mysqli_prepare($conn_login, $sqlS);
        mysqli_stmt_bind_param($stmtS, $types, ...$ids);
        mysqli_stmt_execute($stmtS);
        $resS = mysqli_stmt_get_result($stmtS);
        $map = array();
        if ($resS) { while ($s = mysqli_fetch_assoc($resS)) { $map[(int)$s['id']] = $s; } }
        foreach ($courses as &$c) {
            $sid = (int)($c['school_id'] ?? 0);
            $c['school_name'] = isset($map[$sid]) ? $map[$sid]['name'] : null;
            $c['school_theme_color'] = isset($map[$sid]) ? ($map[$sid]['theme_color'] ?? null) : null;
        }
    }
}

// Sections
if ($scopeSchoolId) {
	$sectionsSql = "SELECT section_id, section_name, course_id, user_id, school_id FROM tbl_sections WHERE school_id = ? ORDER BY section_name LIMIT 2000";
	$stmt2 = mysqli_prepare($conn_qr, $sectionsSql);
	mysqli_stmt_bind_param($stmt2, 'i', $scopeSchoolId);
	mysqli_stmt_execute($stmt2);
	$sectionsRes = mysqli_stmt_get_result($stmt2);
} else {
	$sectionsSql = "SELECT section_id, section_name, course_id, user_id, school_id FROM tbl_sections ORDER BY section_name LIMIT 2000";
	$sectionsRes = mysqli_query($conn_qr, $sectionsSql);
}
$sections = array();
if ($sectionsRes) { while ($s = mysqli_fetch_assoc($sectionsRes)) { $sections[] = $s; } }

// Enrich sections with course_name and school_name
if (!empty($sections)) {
    // Build course id -> name map from already fetched courses
    $courseMap = array();
    foreach ($courses as $cc) { $courseMap[(int)$cc['course_id']] = $cc['course_name']; }
    foreach ($sections as &$sec) {
        $sec['course_name'] = isset($courseMap[(int)($sec['course_id'] ?? 0)]) ? $courseMap[(int)$sec['course_id']] : null;
    }
    if ($conn_login) {
        $sids = array_values(array_unique(array_map(function($r){ return (int)($r['school_id'] ?? 0); }, $sections)));
        $sids = array_filter($sids, function($v){ return $v > 0; });
        if ($sids) {
            $in2 = implode(',', array_fill(0, count($sids), '?'));
            $types2 = str_repeat('i', count($sids));
            $sqlS2 = "SELECT id, name, theme_color FROM schools WHERE id IN ($in2)";
            $stmtS2 = mysqli_prepare($conn_login, $sqlS2);
            mysqli_stmt_bind_param($stmtS2, $types2, ...$sids);
            mysqli_stmt_execute($stmtS2);
            $resS2 = mysqli_stmt_get_result($stmtS2);
            $smap = array();
            if ($resS2) { while ($srow = mysqli_fetch_assoc($resS2)) { $smap[(int)$srow['id']] = $srow; } }
            foreach ($sections as &$sec2) {
                $sid = (int)($sec2['school_id'] ?? 0);
                $sec2['school_name'] = isset($smap[$sid]) ? $smap[$sid]['name'] : null;
                $sec2['school_theme_color'] = isset($smap[$sid]) ? ($smap[$sid]['theme_color'] ?? null) : null;
            }
        }
    }
}

echo json_encode(array('success'=>true,'courses'=>$courses,'sections'=>$sections));
exit();


