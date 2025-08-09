<?php
// Returns a unified list of Course & Section options sourced from teacher_schedules table,
// falling back to unique values in tbl_student.course_section when needed.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$school_id = (int) $_SESSION['school_id'];

require_once('../conn/db_connect.php');

$conn = $conn_qr;

$items = [];

// Primary source: teacher_schedules (what teacher uses to teach)
$sql = "SELECT DISTINCT subject, section
        FROM teacher_schedules
        WHERE school_id = ? AND status = 'active'
        ORDER BY subject, section";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $school_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $subject = trim($row['subject'] ?? '');
        $section = trim($row['section'] ?? '');
        if ($subject !== '' && $section !== '') {
            $items[] = [
                'subject' => $subject,
                'section' => $section,
                'label' => $subject . ' - ' . $section,
            ];
        }
    }
}

// Fallback: derive from tbl_student.course_section (format like "Course - Section") for this user/school
if (count($items) === 0) {
    $fallback = "SELECT DISTINCT course_section
                 FROM tbl_student
                 WHERE user_id = ? AND school_id = ?
                 AND course_section IS NOT NULL AND course_section != ''
                 ORDER BY course_section";
    if ($stmt = $conn->prepare($fallback)) {
        $stmt->bind_param('ii', $user_id, $school_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $cs = trim($row['course_section']);
            if ($cs === '') continue;
            // Attempt to split by ' - '
            $parts = array_map('trim', explode('-', $cs, 2));
            $subject = $parts[0] ?? $cs;
            $section = $parts[1] ?? '';
            $items[] = [
                'subject' => $subject,
                'section' => $section,
                'label' => $section !== '' ? ($subject . ' - ' . $section) : $subject,
            ];
        }
    }
}

// Deduplicate final list by subject+section
$seen = [];
$unique = [];
foreach ($items as $it) {
    $key = strtolower($it['subject'] . '||' . $it['section']);
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $unique[] = $it;
}

echo json_encode([
    'success' => true,
    'items' => $unique,
    'count' => count($unique),
]);
exit;
?>


