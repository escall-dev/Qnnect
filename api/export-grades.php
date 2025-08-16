<?php
// Include necessary files
include('../conn/db_connect.php');
include('../includes/attendance_grade_helper.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Convert MySQL connection to PDO for helper functions
$dsn = 'mysql:host=127.0.0.1;dbname=qr_attendance_db;charset=utf8mb4';
$username = 'root';
$password = '';
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Get filter parameters
$selectedCourse = isset($_GET['course']) ? $_GET['course'] : '';
$selectedSection = isset($_GET['section']) ? $_GET['section'] : '';
$selectedTerm = isset($_GET['term']) ? $_GET['term'] : (isset($_SESSION['semester']) ? $_SESSION['semester'] : '');

// Prepare query to get attendance grades data
$gradesQuery = "
    SELECT 
        s.tbl_student_id, 
        s.student_name, 
        s.course_section,
        subj.subject_name,
        IFNULL(g.attendance_rate, 0) AS attendance_rate,
        IFNULL(g.attendance_grade, 5.00) AS attendance_grade
    FROM 
        tbl_student s
    LEFT JOIN 
        attendance_grades g ON s.tbl_student_id = g.student_id
    LEFT JOIN
        tbl_subjects subj ON g.course_id = subj.subject_id
    WHERE 1=1
";

$gradesParams = [];

if (!empty($selectedCourse)) {
    $gradesQuery .= " AND s.course_section LIKE ?";
    $gradesParams[] = $selectedCourse . '-%';
}

if (!empty($selectedSection)) {
    $gradesQuery .= " AND s.course_section LIKE ?";
    $gradesParams[] = '%-' . $selectedSection;
}

if (!empty($selectedTerm)) {
    $gradesQuery .= " AND g.term = ?";
    $gradesParams[] = $selectedTerm;
}

$gradesQuery .= " ORDER BY s.course_section, s.student_name";

$gradesStmt = $conn_qr->prepare($gradesQuery);

if (!empty($gradesParams)) {
    $gradesTypes = str_repeat('s', count($gradesParams));
    $gradesStmt->bind_param($gradesTypes, ...$gradesParams);
}

$gradesStmt->execute();
$gradesResult = $gradesStmt->get_result();

$attendanceGrades = [];
while ($row = $gradesResult->fetch_assoc()) {
    $attendanceGrades[] = [
        'Student ID' => $row['tbl_student_id'],
        'Student Name' => $row['student_name'],
        'Course-Section' => $row['course_section'],
        'Subject' => $row['subject_name'] ?? 'N/A',
        'Attendance Rate (%)' => number_format($row['attendance_rate'], 2),
        'Attendance Grade' => number_format($row['attendance_grade'], 2)
    ];
}

// Get the timestamp for the file name
$timestamp = date('Y-m-d_H-i-s');
$filename = "attendance_grades_report_$timestamp.csv";

// Set headers for file download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM (Byte Order Mark) for Excel UTF-8 compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Add header row
if (!empty($attendanceGrades)) {
    fputcsv($output, array_keys($attendanceGrades[0]));
}

// Add data rows
foreach ($attendanceGrades as $row) {
    fputcsv($output, $row);
}

// Close the output stream
fclose($output);

// Log the export if the activity logging function exists
if (function_exists('logActivity')) {
    include_once('../includes/activity_log_helper.php');
    
    $filterInfo = [
        'course' => $selectedCourse ?: 'All',
        'section' => $selectedSection ?: 'All',
        'term' => $selectedTerm ?: 'All'
    ];
    
    logActivity(
        'data_export',
        "Exported attendance grades report in CSV format",
        'attendance_grades',
        null,
        [
            'format' => 'csv',
            'filters' => $filterInfo,
            'record_count' => count($attendanceGrades)
        ]
    );
}

exit;
?> 