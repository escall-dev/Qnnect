<?php
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once '../conn/db_connect.php';

header('Content-Type: application/json');

if (!hasRole('super_admin')) { echo json_encode(['success'=>false,'message'=>'Access denied']); exit(); }
$scopeSchoolId = getEffectiveScopeSchoolId();
$conn_qr = $GLOBALS['conn_qr'] ?? null;
if (!$conn_qr) { echo json_encode(['success'=>false,'message'=>'Database not available']); exit(); }

// Join attendance_grades to tbl_student to scope by school
if ($scopeSchoolId) {
	$sql = "SELECT ag.id, ag.student_id, ag.course_id, ag.term, ag.section, ag.attendance_rate, ag.attendance_grade,
	               s.student_name, s.course_section, s.school_id
	        FROM attendance_grades ag
	        JOIN tbl_student s ON s.tbl_student_id = ag.student_id
	        WHERE s.school_id = ?
	        ORDER BY ag.id DESC
	        LIMIT 500";
	$stmt = mysqli_prepare($conn_qr, $sql);
	mysqli_stmt_bind_param($stmt, 'i', $scopeSchoolId);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
} else {
	$sql = "SELECT ag.id, ag.student_id, ag.course_id, ag.term, ag.section, ag.attendance_rate, ag.attendance_grade,
	               s.student_name, s.course_section, s.school_id
	        FROM attendance_grades ag
	        JOIN tbl_student s ON s.tbl_student_id = ag.student_id
	        ORDER BY ag.id DESC
	        LIMIT 500";
	$res = mysqli_query($conn_qr, $sql);
}

$rows = [];
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; } }
echo json_encode(['success'=>true,'grades'=>$rows]);
exit();


