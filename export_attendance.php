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

// Get filter parameters from GET/POST (form in the future can pass these)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 week'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$selected_course_section = isset($_GET['course_section']) ? $_GET['course_section'] : '';
$selected_day = isset($_GET['selected_day']) ? $_GET['selected_day'] : '';

// Prepare the SQL statement with filtering
$sql = "
    SELECT 
        tbl_student.student_name,
        tbl_student.course_section,
        DATE_FORMAT(time_in, '%Y-%m-%d') AS attendance_date,
        DATE_FORMAT(time_in, '%W') AS day_of_week,
        DATE_FORMAT(time_in, '%r') AS formatted_time_in,
        CASE 
            WHEN tbl_attendance.status = 'late' THEN 'Late'
            ELSE 'On Time'
        END AS status
    FROM tbl_attendance 
    LEFT JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id 
    WHERE time_in IS NOT NULL
";

// Add date range filter
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND DATE(time_in) BETWEEN :start_date AND :end_date";
}

// Add course section filter
if (!empty($selected_course_section)) {
    $sql .= " AND tbl_student.course_section = :course_section";
}

// Add day of week filter
if (!empty($selected_day)) {
    $sql .= " AND DATE_FORMAT(time_in, '%W') = :selected_day";
}

$sql .= " ORDER BY time_in DESC";

$stmt = $conn->prepare($sql);

// Bind parameters
if (!empty($start_date) && !empty($end_date)) {
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
}

if (!empty($selected_course_section)) {
    $stmt->bindParam(':course_section', $selected_course_section);
}

if (!empty($selected_day)) {
    $stmt->bindParam(':selected_day', $selected_day);
}

$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Log the export action
logActivity(
    'data_export',
    "Exported attendance records in $format format",
    'tbl_attendance',
    null,
    [
        'format' => $format,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'course_section' => $selected_course_section,
        'day' => $selected_day,
        'record_count' => count($result)
    ]
);

// Function to export to CSV
function exportToCSV($data) {
    $filename = "attendance_report_" . date('Y-m-d_H-i-s') . ".csv";
    
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
        fputcsv($output, ['student_name', 'course_section', 'attendance_date', 'day_of_week', 'formatted_time_in', 'status']);
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
    $filename = "attendance_report_" . date('Y-m-d_H-i-s') . ".csv";
    
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
        fputcsv($output, ['student_name', 'course_section', 'attendance_date', 'day_of_week', 'formatted_time_in', 'status']);
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

// Function to export to PDF (simple CSV format for now)
function exportToPDF($data) {
    // For the simplified version, we'll just return a CSV
    $export = exportToCSV($data);
    $export['filename'] = "attendance_report_" . date('Y-m-d_H-i-s') . ".pdf";
    $export['type'] = 'application/pdf';
    
    return $export;
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
        case 'pdf':
            $export_data = exportToPDF($result);
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
    echo "Error exporting attendance data: " . $e->getMessage();
    exit();
}
?> 