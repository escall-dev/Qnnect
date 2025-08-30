<?php
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once 'database.php';

// Only allow admin and super admin to access
if (!hasRole('admin') && !hasRole('super_admin')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    exit('Database connection not available');
}

$user_role = $_SESSION['role'] ?? 'admin';
$user_school_id = $_SESSION['school_id'] ?? null;
$is_super_admin = hasRole('super_admin');

// Get effective scope
$effective_scope_id = getEffectiveScopeSchoolId();

// Get export format
$format = $_GET['format'] ?? $_POST['format'] ?? 'excel';

// Validate format
$allowed_formats = ['excel', 'pdf', 'print', 'csv'];
if (!in_array($format, $allowed_formats)) {
    exit('Invalid export format');
}

try {
    // Get system logs with the same logic as admin_panel.php
    if ($is_super_admin) {
        if ($effective_scope_id) {
            $logs_sql = "SELECT sl.*, u.username, s.name as school_name FROM system_logs sl 
                       LEFT JOIN users u ON sl.user_id = u.id 
                       LEFT JOIN schools s ON sl.school_id = s.id 
                       WHERE sl.school_id = ? 
                       ORDER BY sl.created_at DESC";
            $stmt = mysqli_prepare($conn, $logs_sql);
            mysqli_stmt_bind_param($stmt, "i", $effective_scope_id);
            mysqli_stmt_execute($stmt);
            $logs_result = mysqli_stmt_get_result($stmt);
        } else {
            $logs_sql = "SELECT sl.*, u.username, s.name as school_name FROM system_logs sl 
                       LEFT JOIN users u ON sl.user_id = u.id 
                       LEFT JOIN schools s ON sl.school_id = s.id 
                       ORDER BY sl.created_at DESC";
            $logs_result = mysqli_query($conn, $logs_sql);
        }
    } else {
        $logs_sql = "SELECT sl.*, u.username, s.name as school_name FROM system_logs sl 
                   LEFT JOIN users u ON sl.user_id = u.id 
                   LEFT JOIN schools s ON sl.school_id = s.id 
                   WHERE sl.school_id = ? 
                   ORDER BY sl.created_at DESC";
        $stmt = mysqli_prepare($conn, $logs_sql);
        mysqli_stmt_bind_param($stmt, "i", $user_school_id);
        mysqli_stmt_execute($stmt);
        $logs_result = mysqli_stmt_get_result($stmt);
    }

    $logs = [];
    while ($row = mysqli_fetch_assoc($logs_result)) {
        $logs[] = $row;
    }

    // Export based on format
    switch ($format) {
        case 'csv':
        case 'excel':
            exportToExcel($logs, $is_super_admin);
            break;
        case 'pdf':
            exportToPDF($logs, $is_super_admin);
            break;
        case 'print':
            exportToPrint($logs, $is_super_admin);
            break;
        default:
            throw new Exception("Unsupported export format");
    }
} catch (Exception $e) {
    exit('Export error: ' . $e->getMessage());
}

function exportToExcel($logs, $is_super_admin) {
    $filename = "system_logs_" . date('Y-m-d_H-i-s') . ".csv";
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    $headers = ['Time', 'User', 'Action', 'Details'];
    if ($is_super_admin) {
        $headers[] = 'School';
    }
    fputcsv($output, $headers);
    
    // Add data
    foreach ($logs as $log) {
        $row = [
            date('M j, Y H:i', strtotime($log['created_at'])),
            $log['username'] ?? 'System',
            $log['action'],
            $log['details'] ?? ''
        ];
        if ($is_super_admin) {
            $row[] = $log['school_name'] ?? 'N/A';
        }
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

function exportToPDF($logs, $is_super_admin) {
    // Simple PDF export as text (can be enhanced with TCPDF if available)
    $filename = "system_logs_" . date('Y-m-d_H-i-s') . ".txt";
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    echo "SYSTEM LOGS REPORT\n";
    echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 80) . "\n\n";
    
    foreach ($logs as $log) {
        echo "Time: " . date('M j, Y H:i', strtotime($log['created_at'])) . "\n";
        echo "User: " . ($log['username'] ?? 'System') . "\n";
        echo "Action: " . $log['action'] . "\n";
        echo "Details: " . ($log['details'] ?? '') . "\n";
        if ($is_super_admin) {
            echo "School: " . ($log['school_name'] ?? 'N/A') . "\n";
        }
        echo str_repeat('-', 40) . "\n";
    }
    
    exit();
}

function exportToPrint($logs, $is_super_admin) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>System Logs Report</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h1 { text-align: center; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            @media print {
                .no-print { display: none; }
            }
        </style>
        <script>
            window.onload = function() { window.print(); };
        </script>
    </head>
    <body>
        <h1>System Logs Report</h1>
        <p><strong>Generated on:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <?php if ($is_super_admin): ?>
                    <th>School</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo date('M j, Y H:i', strtotime($log['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                    <td><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                    <?php if ($is_super_admin): ?>
                    <td><?php echo htmlspecialchars($log['school_name'] ?? 'N/A'); ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit();
}
?>
