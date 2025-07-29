<?php
// Include your database connection files
include('./conn/conn.php');
include('./conn/db_connect.php');
include('./includes/activity_log_helper.php');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Get export format
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Get filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$course_filter = isset($_GET['course']) ? $_GET['course'] : '';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';

// Default date range values
$start_date = date('Y-m-d', strtotime('-7 days'));
$end_date = date('Y-m-d');

// Set date range based on selection
if ($date_range == 'today') {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d');
} elseif ($date_range == 'yesterday') {
    $start_date = date('Y-m-d', strtotime('-1 day'));
    $end_date = date('Y-m-d', strtotime('-1 day'));
} elseif ($date_range == 'week') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = date('Y-m-d');
} elseif ($date_range == 'month') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
} elseif ($date_range == 'custom' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}

// Prepare the SQL statement with filtering
$sql = "SELECT 
    tbl_student.student_name,
    tbl_student.course_section,
    DATE_FORMAT(tbl_attendance.time_in, '%Y-%m-%d') AS attendance_date,
    DATE_FORMAT(tbl_attendance.time_in, '%r') AS time_in,
    tbl_attendance.status,
    tbl_subjects.subject_name,
    tbl_instructors.instructor_name
FROM tbl_attendance 
LEFT JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id
LEFT JOIN tbl_subjects ON tbl_attendance.subject_id = tbl_subjects.subject_id
LEFT JOIN tbl_instructors ON tbl_attendance.instructor_id = tbl_instructors.instructor_id
WHERE DATE(tbl_attendance.time_in) BETWEEN :start_date AND :end_date";

// Add status filter if selected
if (!empty($status_filter)) {
    $sql .= " AND tbl_attendance.status = :status";
}

// Add course filter if selected
if (!empty($course_filter)) {
    $sql .= " AND tbl_student.course_section = :course";
}

$sql .= " ORDER BY tbl_attendance.time_in DESC LIMIT 1000";

$stmt = $conn->prepare($sql);

// Bind parameters
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);

if (!empty($status_filter)) {
    $stmt->bindParam(':status', $status_filter);
}

if (!empty($course_filter)) {
    $stmt->bindParam(':course', $course_filter);
}

$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Log the export action
logActivity(
    'data_export',
    "Exported attendance status records in $format format",
    'tbl_attendance',
    null,
    [
        'format' => $format,
        'status_filter' => $status_filter,
        'course_filter' => $course_filter,
        'date_range' => $date_range,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'record_count' => count($result)
    ]
);

// Function to export to CSV
function exportToCSV($data) {
    $filename = "attendance_status_report_" . date('Y-m-d_H-i-s') . ".csv";
    
    // Create temporary file
    $output = fopen('php://temp', 'r+');
    
    // Add headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    } else {
        // If no data, add headers anyway
        fputcsv($output, ['student_name', 'course_section', 'attendance_date', 'time_in', 'status', 'subject_name', 'instructor_name']);
    }
    
    // Get file contents
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    return [
        'content' => $csv,
        'filename' => $filename,
        'type' => 'text/csv'
    ];
}

// Function to export to Excel (simple CSV format)
function exportToExcel($data) {
    $filename = "attendance_status_report_" . date('Y-m-d_H-i-s') . ".csv";
    
    // Create temporary file
    $output = fopen('php://temp', 'r+');
    
    // Add headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    } else {
        // If no data, add headers anyway
        fputcsv($output, ['student_name', 'course_section', 'attendance_date', 'time_in', 'status', 'subject_name', 'instructor_name']);
    }
    
    // Get file contents
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    return [
        'content' => $csv,
        'filename' => $filename,
        'type' => 'application/vnd.ms-excel'
    ];
}

// Export the data based on format
try {
    switch ($format) {
        case 'csv':
            $export_data = exportToCSV($result);
            break;
        case 'excel':
            $export_data = exportToExcel($result);
            break;
        default:
            throw new Exception("Unsupported export format");
    }
    
    // Set headers for file download
    header('Content-Type: ' . $export_data['type']);
    header('Content-Disposition: attachment; filename="' . $export_data['filename'] . '"');
    header('Content-Length: ' . strlen($export_data['content']));
    echo $export_data['content'];
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
    exit();
}
?> 