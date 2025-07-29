<?php
session_start();
require_once './conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

// Get export parameters - accept both GET and POST methods
$format = $_POST['format'] ?? $_GET['format'] ?? 'csv';
$student = $_POST['student'] ?? $_GET['student'] ?? null;
$status = $_POST['status'] ?? $_GET['status'] ?? null;
$date = $_POST['date'] ?? $_GET['date'] ?? null;

// Build query conditions
$where_conditions = [];
$params = [];
$types = "";

if ($student) {
    $where_conditions[] = "student_name = ?";
    $params[] = $student;
    $types .= "s";
}

if ($status) {
    $where_conditions[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($date) {
    // Format date for SQL search
    $date_obj = new DateTime($date);
    $formatted_date = $date_obj->format('Y-m-d');
    $where_conditions[] = "DATE(verification_time) = ?";
    $params[] = $formatted_date;
    $types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Build the query
$sql = "SELECT 
    log_id,
    student_name,
    status,
    verification_time,
    ip_address,
    notes
    FROM tbl_face_verification_logs
    $where_clause
    ORDER BY verification_time DESC";

try {
    // Prepare and execute the query
    $stmt = $conn_qr->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all data
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // If no data, return empty file with headers
    if (empty($data)) {
        $data[] = [
            'log_id' => '',
            'student_name' => '',
            'status' => '',
            'verification_time' => '',
            'ip_address' => '',
            'notes' => ''
        ];
    }

    // Log the export action if logging function exists
    if (function_exists('logActivity')) {
        logActivity(
            'data_export',
            "Exported face verification logs in $format format",
            'tbl_face_verification_logs',
            null,
            [
                'format' => $format,
                'student' => $student,
                'status' => $status,
                'date' => $date
            ]
        );
    }

    // Create and output the file based on selected format
    switch ($format) {
        case 'csv':
            exportToCSV($data);
            break;
        case 'excel':
            exportToExcel($data);
            break;
        case 'pdf':
            exportToPDF($data);
            break;
        default:
            throw new Exception("Unsupported export format");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Error exporting logs: " . $e->getMessage();
    exit();
}

// Export functions
function exportToCSV($data) {
    $filename = "face_verification_logs_" . date('Y-m-d_H-i-s') . ".csv";
    $output = fopen('php://temp', 'r+');
    
    // Add headers
    fputcsv($output, array_keys($data[0]));
    
    // Skip adding data if it's our empty placeholder
    if (count($data) == 1 && empty($data[0]['log_id'])) {
        // Don't add the empty row
    } else {
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    // Output CSV data with headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($csv));
    echo $csv;
    exit();
}

function exportToExcel($data) {
    // Simple Excel export (actually CSV with Excel mime type)
    $filename = "face_verification_logs_" . date('Y-m-d_H-i-s') . ".csv";
    $output = fopen('php://temp', 'r+');
    
    // Add headers
    fputcsv($output, array_keys($data[0]));
    
    // Skip adding data if it's our empty placeholder
    if (count($data) == 1 && empty($data[0]['log_id'])) {
        // Don't add the empty row
    } else {
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    // Output Excel data with headers
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($csv));
    echo $csv;
    exit();
}

function exportToPDF($data) {
    // For the simplified version, we'll use CSV since TCPDF might not be available
    $filename = "face_verification_logs_" . date('Y-m-d_H-i-s') . ".pdf";
    $output = fopen('php://temp', 'r+');
    
    // Add headers
    fputcsv($output, array_keys($data[0]));
    
    // Skip adding data if it's our empty placeholder
    if (count($data) == 1 && empty($data[0]['log_id'])) {
        // Don't add the empty row
    } else {
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    // Output PDF data with headers (using CSV as fallback)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($csv));
    echo $csv;
    exit();
}
?> 