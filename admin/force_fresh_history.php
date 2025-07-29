<?php
// Use consistent session handling
require_once '../includes/session_config.php';
require_once "database.php";
require_once "functions/log_functions.php";

// Clear cache to ensure fresh data
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// FORCE RECORD CURRENT SESSION
if (isset($_SESSION['username']) && !isset($_SESSION['forced_log_record'])) {
    $username = $_SESSION['username'];
    $user_type = $_SESSION['user_type'] ?? 'User';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $log_id = recordUserLogin($conn, $username, '', $user_type);
    
    if ($log_id) {
        $_SESSION['log_id'] = $log_id;
        $_SESSION['forced_log_record'] = true;
    }
}

// Get history logs - FRESH QUERY
$logs_query = "SELECT * FROM tbl_user_logs ORDER BY log_id DESC"; // Sort by ID descending
$logs_result = mysqli_query($conn, $logs_query);

if (!$logs_result) {
    die("Error fetching logs: " . mysqli_error($conn));
}

$logs = [];
while ($row = mysqli_fetch_assoc($logs_result)) {
    $logs[] = $row;
}

echo "<!-- FRESH QUERY RETURNED: " . count($logs) . " records -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fresh User Logs - QR Code Attendance System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #098744 0%, #098744 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 25px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }
        
        .title {
            text-align: center;
            color: #098744;
            margin-bottom: 20px;
        }
        
        .table-container {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .badge-active {
            background-color: #28a745;
            color: white;
        }
        
        .btn-back {
            background-color: #098744;
            border-color: #098744;
            color: white;
            margin-bottom: 15px;
        }
        
        .btn-back:hover {
            background-color: #076a34;
            border-color: #076a34;
        }
        
        .record-info {
            background-color: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #098744;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">
            <h4><i class="fas fa-history"></i> Fresh User Logs (No Cache)</h4>
        </div>
        
        <a href="history.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Regular History
        </a>
        
        <div class="record-info">
            <strong>Records Found:</strong> <?php echo count($logs); ?><br>
            <strong>Current User:</strong> <?php echo $_SESSION['username'] ?? 'Unknown'; ?><br>
            <strong>Current Log ID:</strong> <?php echo $_SESSION['log_id'] ?? 'Not set'; ?><br>
            <strong>Query Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>
        
        <div class="table-container">
            <table class="table table-striped table-sm">
                <thead style="background-color: #098744; color: white; position: sticky; top: 0;">
                    <tr>
                        <th>Log ID</th>
                        <th>Username</th>
                        <th>User Type</th>
                        <th>Log In Time</th>
                        <th>Log Out Time</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="alert alert-warning">
                                    No user logs found in database.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $index => $log): ?>
                            <tr <?php echo ($index < 5) ? 'style="background-color: #fff2cc;"' : ''; ?>>
                                <td><strong><?php echo $log['log_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($log['username']); ?></td>
                                <td>
                                    <?php if ($log['user_type'] === 'Admin'): ?>
                                        <span class="badge badge-primary">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?php echo htmlspecialchars($log['user_type'] ?? 'User'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $log['log_in_time']; ?></td>
                                <td>
                                    <?php if ($log['log_out_time']): ?>
                                        <?php echo $log['log_out_time']; ?>
                                    <?php else: ?>
                                        <span class="badge badge-active">Currently Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'Unknown'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-muted">
                Total Records: <?php echo count($logs); ?> | 
                Fresh query executed at <?php echo date('Y-m-d H:i:s'); ?>
            </small>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds to show latest data
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html> 