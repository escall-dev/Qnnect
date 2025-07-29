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
$sourceMonth = $_POST['sourceMonth'] ?? '';
$targetMonth = $_POST['targetMonth'] ?? '';
if (!$sourceMonth || !$targetMonth) {
    echo json_encode(['success' => false, 'message' => 'Missing month(s)']);
    exit;
}
try {
    // Get all schedules for the source month
    $stmt = $pdo->prepare("SELECT * FROM master_schedule WHERE instructor = ? AND school_id = ? AND date LIKE ?");
    $stmt->execute([$teacher_username, $user_school_id, "$sourceMonth-%"]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$schedules) {
        echo json_encode(['success' => false, 'message' => 'No schedules to copy.']);
        exit;
    }
    $copied = 0;
    foreach ($schedules as $sched) {
        $orig_date = $sched['date'];
        $orig_dow = date('N', strtotime($orig_date)); // 1=Mon, 7=Sun
        $orig_dom = date('j', strtotime($orig_date));
        // Find the same weekday in the target month (first week, then next week, etc.)
        $target_year = substr($targetMonth, 0, 4);
        $target_mon = substr($targetMonth, 5, 2);
        // Find all dates in target month with same weekday
        $target_dates = [];
        $days_in_month = date('t', strtotime("$target_year-$target_mon-01"));
        for ($d = 1; $d <= $days_in_month; $d++) {
            $date = "$target_year-$target_mon-" . str_pad($d, 2, '0', STR_PAD_LEFT);
            if (date('N', strtotime($date)) == $orig_dow) {
                $target_dates[] = $date;
            }
        }
        // Find the nth occurrence in the source month
        $nth = 1;
        $tmp = date('Y-m-01', strtotime($orig_date));
        for ($d = 1; $d <= $orig_dom; $d++) {
            $date = date('Y-m-d', strtotime("$tmp +".($d-1)." days"));
            if (date('N', strtotime($date)) == $orig_dow && $date <= $orig_date) {
                if ($date != $orig_date) $nth++;
            }
        }
        // Get the nth occurrence in the target month
        $target_date = $target_dates[$nth-1] ?? null;
        if ($target_date) {
            // Prevent duplicate
            $check = $pdo->prepare("SELECT COUNT(*) FROM master_schedule WHERE instructor = ? AND school_id = ? AND date = ? AND subject = ? AND section = ?");
            $check->execute([$teacher_username, $user_school_id, $target_date, $sched['subject'], $sched['section']]);
            if ($check->fetchColumn() == 0) {
                $ins = $pdo->prepare("INSERT INTO master_schedule (subject, section, instructor, date, start_time, end_time, room, school_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $ins->execute([
                    $sched['subject'],
                    $sched['section'],
                    $teacher_username,
                    $target_date,
                    $sched['start_time'],
                    $sched['end_time'],
                    $sched['room'],
                    $user_school_id
                ]);
                $copied++;
            }
        }
    }
    echo json_encode(['success' => true, 'message' => "Copied $copied schedules."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} 