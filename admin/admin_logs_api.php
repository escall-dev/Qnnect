<?php
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once 'database.php';

header('Content-Type: application/json');

if (!hasRole('super_admin')) { echo json_encode(['success'=>false,'message'=>'Access denied']); exit(); }

$op = $_POST['op'] ?? 'list';
$conn = $GLOBALS['conn'] ?? null; // from admin/database.php
if (!$conn) { echo json_encode(['success'=>false,'message'=>'DB missing']); exit(); }

if ($op === 'create') {
    $action = trim($_POST['action'] ?? 'CUSTOM');
    $details = $_POST['details'] ?? '';
    $school_id = isset($_POST['school_id']) && $_POST['school_id'] !== '' ? (int)$_POST['school_id'] : (getEffectiveScopeSchoolId() ?? ($_SESSION['school_id'] ?? null));
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = mysqli_prepare($conn, 'INSERT INTO system_logs (user_id, school_id, action, details) VALUES (?,?,?,?)');
    mysqli_stmt_bind_param($stmt, 'iiss', $user_id, $school_id, $action, $details);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success'=>$ok]); exit();
}

if ($op === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id<=0) { echo json_encode(['success'=>false,'message'=>'Invalid']); exit(); }
    $fields=[];$types='';$params=[];
    foreach (['action','details'] as $k) { if (isset($_POST[$k])) { $fields[]="$k=?"; $types.='s'; $params[]=$_POST[$k]; }}
    if (isset($_POST['school_id'])) { $fields[]='school_id=?'; $types.='i'; $params[]=(int)$_POST['school_id']; }
    if (!$fields) { echo json_encode(['success'=>false,'message'=>'No changes']); exit(); }
    $types.='i'; $params[]=$id;
    $stmt = mysqli_prepare($conn, 'UPDATE system_logs SET '.implode(',', $fields).' WHERE id = ?');
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success'=>$ok]); exit();
}

if ($op === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id<=0) { echo json_encode(['success'=>false,'message'=>'Invalid']); exit(); }
    $stmt = mysqli_prepare($conn, 'DELETE FROM system_logs WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success'=>$ok]); exit();
}

// list with optional scope
$scope = getEffectiveScopeSchoolId();
if ($scope) {
    $sql = "SELECT sl.*, u.username, s.name as school_name FROM system_logs sl 
            LEFT JOIN users u ON sl.user_id = u.id 
            LEFT JOIN schools s ON sl.school_id = s.id 
            WHERE sl.school_id = ? ORDER BY sl.created_at DESC LIMIT 200";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $scope);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $sql = "SELECT sl.*, u.username, s.name as school_name FROM system_logs sl 
            LEFT JOIN users u ON sl.user_id = u.id 
            LEFT JOIN schools s ON sl.school_id = s.id 
            ORDER BY sl.created_at DESC LIMIT 200";
    $res = mysqli_query($conn, $sql);
}
$rows=[]; if ($res) { while ($r = mysqli_fetch_assoc($res)) { $rows[]=$r; } }
echo json_encode(['success'=>true,'logs'=>$rows]);
exit();


