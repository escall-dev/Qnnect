<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect_pdo.php';
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$login_pdo = $conn_login_pdo;
$pdo = $conn_qr_pdo;
$user_email = $_SESSION['email'];
$user_school_id = $_SESSION['school_id'] ?? 1;

// Get teacher username
$stmt = $login_pdo->prepare("SELECT username FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$teacher_username = $stmt->fetchColumn();

if (!$teacher_username) {
    echo json_encode(['success' => false, 'message' => 'Teacher not found']);
    exit;
}

$sourceWeek = $_POST['sourceWeek'] ?? '';
$targetWeek = $_POST['targetWeek'] ?? '';

if (!$sourceWeek || !$targetWeek || $sourceWeek == $targetWeek) {
    echo json_encode(['success' => false, 'message' => 'Invalid week selection']);
    exit;
}

try {
    // Get all schedules for the source week
    $stmt = $pdo->prepare("SELECT * FROM master_schedule WHERE instructor = ? AND school_id = ? AND week_number = ?");
    $stmt->execute([$teacher_username, $user_school_id, $sourceWeek]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$schedules) {
        echo json_encode(['success' => false, 'message' => 'No schedules to copy in Week ' . $sourceWeek]);
        exit;
    }
    
    $copied = 0;
    foreach ($schedules as $sched) {
        // Check if schedule already exists in target week
        $check = $pdo->prepare("SELECT COUNT(*) FROM master_schedule WHERE instructor = ? AND school_id = ? AND week_number = ? AND day_of_week = ? AND subject = ? AND section = ?");
        $check->execute([$teacher_username, $user_school_id, $targetWeek, $sched['day_of_week'], $sched['subject'], $sched['section']]);
        
        if ($check->fetchColumn() == 0) {
            // Insert the schedule into target week
            $ins = $pdo->prepare("INSERT INTO master_schedule (subject, section, instructor, day_of_week, start_time, end_time, room, week_number, school_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $ins->execute([
                $sched['subject'],
                $sched['section'],
                $teacher_username,
                $sched['day_of_week'],
                $sched['start_time'],
                $sched['end_time'],
                $sched['room'],
                $targetWeek,
                $user_school_id
            ]);
            $copied++;
        }
    }
    
    echo json_encode(['success' => true, 'message' => "Copied $copied schedules from Week $sourceWeek to Week $targetWeek"]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 