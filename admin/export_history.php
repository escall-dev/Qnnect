<?php
// Use consistent session handling
require_once '../includes/session_config.php';
require_once "database.php";

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

// Get export parameters - accept both GET and POST methods
$format = $_POST['format'] ?? $_GET['format'] ?? 'csv';
$user = $_POST['user'] ?? $_GET['user'] ?? null;
$date = $_POST['date'] ?? $_GET['date'] ?? null;
$user_type = $_POST['user_type'] ?? $_GET['user_type'] ?? null;

// Build query conditions
$where_conditions = [];
$params = [];
$types = "";

if ($user) {
    $where_conditions[] = "username = ?";
    $params[] = $user;
    $types .= "s";
}

if ($user_type) {
    $where_conditions[] = "user_type = ?";
    $params[] = $user_type;
    $types .= "s";
}

if ($date) {
    // Format date for SQL search
    $date_obj = new DateTime($date);
    $formatted_date = $date_obj->format('Y-m-d');
    $where_conditions[] = "DATE(log_in_time) = ?";
    $params[] = $formatted_date;
    $types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Build the query
$sql = "SELECT 
    log_id,
    username,
    email,
    user_type,
    log_in_time,
    log_out_time,
    ip_address
    FROM tbl_user_logs
    $where_clause
    ORDER BY log_in_time DESC";

try {
    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
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
            'username' => '',
            'email' => '',
            'user_type' => '',
            'log_in_time' => '',
            'log_out_time' => '',
            'ip_address' => ''
        ];
    }

    // Log the export action if logging function exists
    if (function_exists('logActivity')) {
        logActivity(
            'data_export',
            "Exported user login history in $format format",
            'tbl_user_logs',
            null,
            [
                'format' => $format,
                'user' => $user,
                'date' => $date,
                'user_type' => $user_type
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
    $filename = "user_login_history_" . date('Y-m-d_H-i-s') . ".csv";
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
    $filename = "user_login_history_" . date('Y-m-d_H-i-s') . ".csv";
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
    $filename = "user_login_history_" . date('Y-m-d_H-i-s') . ".pdf";
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

// Function to record user login
function recordUserLogin($conn, $username, $email, $user_type) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_id = $_SESSION['user_id'] ?? 0;
    $school_id = $_SESSION['school_id'] ?? 0;
    
    $query = "INSERT INTO tbl_user_logs (username, email, user_type, ip_address, user_id, school_id) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssssii", $username, $email, $user_type, $ip, $user_id, $school_id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($result) {
        // Store the log ID in session for logout tracking
        $log_id = mysqli_insert_id($conn);
        $_SESSION['current_log_id'] = $log_id;
        return true;
    }
    
    return false;
}

// Function to record user logout
function recordUserLogout($conn, $log_id) {
    $query = "UPDATE tbl_user_logs SET log_out_time = NOW() WHERE log_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $log_id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Handle export requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['format'])) {
    $format = $_POST['format'];
    
    // Get filter values
    $user = $_POST['user'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    $date = $_POST['date'] ?? '';
    
    // Get session variables for filtering
    $current_user_id = $_SESSION['user_id'] ?? 0;
    $current_school_id = $_SESSION['school_id'] ?? 0;
    
    // Build query with filters (including data isolation)
    $query = "SELECT * FROM tbl_user_logs WHERE school_id = ? AND user_id = ?";
    $params = [$current_school_id, $current_user_id];
    $types = "ii";
    
    if (!empty($user)) {
        $query .= " AND username = ?";
        $params[] = $user;
        $types .= "s";
    }
    
    if (!empty($user_type)) {
        $query .= " AND user_type = ?";
        $params[] = $user_type;
        $types .= "s";
    }
    
    if (!empty($date)) {
        $query .= " AND DATE(log_in_time) = ?";
        $params[] = $date;
        $types .= "s";
    }
    
    $query .= " ORDER BY log_in_time DESC";
    
    // Execute query with prepared statement
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    
    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    // Handle different export formats
    switch ($format) {
        case 'print':
            // Just return to the page, will be handled by print button in JavaScript
            header("Location: history.php");
            exit;
            break;
            
        case 'excel':
            // Export as Excel
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="user_logs.xls"');
            header('Cache-Control: max-age=0');
            
            echo "Log ID\tUsername\tUser Type\tLog In Time\tLog Out Time\tIP Address\n";
            
            foreach ($logs as $log) {
                echo $log['log_id'] . "\t";
                echo $log['username'] . "\t";
                echo $log['user_type'] . "\t";
                echo $log['log_in_time'] . "\t";
                echo ($log['log_out_time'] ? $log['log_out_time'] : 'Currently Active') . "\t";
                echo ($log['ip_address'] ?? 'Unknown') . "\n";
            }
            exit;
            break;
            
        case 'pdf':
            // Redirect back with a message to implement PDF export
            $_SESSION['message'] = "PDF export functionality is under development.";
            header("Location: history.php");
            exit;
            break;
    }
}

// If we reach here, redirect back to history page
header("Location: history.php");
exit;
?> 