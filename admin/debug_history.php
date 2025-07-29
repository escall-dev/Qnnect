<?php
session_start();
require_once "database.php";

header("Content-Type: text/html");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug History Query</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 5px; }
        h1 { color: #098744; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .action { background: #098744; color: white; padding: 10px 15px; border-radius: 3px; text-decoration: none; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug History Query</h1>
        
        <?php
        if (!$conn) {
            echo "<p class='error'>Database connection failed</p>";
            exit;
        }

        echo "<p class='info'>Debugging the exact same query used in history.php</p>";
        
        // Use the EXACT same query as history.php
        echo "<h3>1. Raw Query Results (EXACT same as history.php):</h3>";
        $logs_query = "SELECT * FROM tbl_user_logs ORDER BY log_in_time DESC";
        $logs_result = mysqli_query($conn, $logs_query);
        
        if (!$logs_result) {
            echo "<p class='error'>Query failed: " . mysqli_error($conn) . "</p>";
        } else {
            $logs = [];
            $count = 0;
            echo "<table>";
            echo "<tr><th>Row#</th><th>Log ID</th><th>Username</th><th>User Type</th><th>Log In Time</th><th>Log Out Time</th><th>IP</th></tr>";
            
            while ($row = mysqli_fetch_assoc($logs_result)) {
                $count++;
                $logs[] = $row;
                
                // Only show first 20 for readability
                if ($count <= 20) {
                    echo "<tr>";
                    echo "<td>$count</td>";
                    echo "<td>" . $row['log_id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . ($row['user_type'] ?? 'NULL') . "</td>";
                    echo "<td>" . $row['log_in_time'] . "</td>";
                    echo "<td>" . ($row['log_out_time'] ?? '<span style="color:green;">Active</span>') . "</td>";
                    echo "<td>" . htmlspecialchars($row['ip_address'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
            
            echo "<p class='success'>Total records found: $count</p>";
        }
        
        // Show last 10 records by ID
        echo "<h3>2. Last 10 Records by ID (most recent IDs):</h3>";
        $latest_query = "SELECT * FROM tbl_user_logs ORDER BY log_id DESC LIMIT 10";
        $latest_result = mysqli_query($conn, $latest_query);
        
        if ($latest_result) {
            echo "<table>";
            echo "<tr><th>Log ID</th><th>Username</th><th>User Type</th><th>Log In Time</th><th>Log Out Time</th><th>IP</th></tr>";
            
            while ($row = mysqli_fetch_assoc($latest_result)) {
                echo "<tr>";
                echo "<td>" . $row['log_id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo "<td>" . ($row['user_type'] ?? 'NULL') . "</td>";
                echo "<td>" . $row['log_in_time'] . "</td>";
                echo "<td>" . ($row['log_out_time'] ?? '<span style="color:green;">Active</span>') . "</td>";
                echo "<td>" . htmlspecialchars($row['ip_address'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check for any date/time issues
        echo "<h3>3. Date/Time Comparison:</h3>";
        $time_query = "SELECT NOW() as server_time, MAX(log_in_time) as latest_login, COUNT(*) as total_records FROM tbl_user_logs";
        $time_result = mysqli_query($conn, $time_query);
        
        if ($time_result) {
            $time_row = mysqli_fetch_assoc($time_result);
            echo "<p>Server time: " . $time_row['server_time'] . "</p>";
            echo "<p>Latest login time: " . $time_row['latest_login'] . "</p>";
            echo "<p>Total records: " . $time_row['total_records'] . "</p>";
        }
        
        // Show current session info
        echo "<h3>4. Current Session Info:</h3>";
        echo "<p>Username: " . ($_SESSION['username'] ?? 'Not set') . "</p>";
        echo "<p>Log ID: " . ($_SESSION['log_id'] ?? 'Not set') . "</p>";
        echo "<p>Login recorded: " . (isset($_SESSION['login_recorded']) ? 'Yes' : 'No') . "</p>";
        echo "<p>Forced log record: " . (isset($_SESSION['forced_log_record']) ? 'Yes' : 'No') . "</p>";
        
        // Test if current session has a record
        if (isset($_SESSION['log_id'])) {
            $session_query = "SELECT * FROM tbl_user_logs WHERE log_id = ?";
            $stmt = mysqli_prepare($conn, $session_query);
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['log_id']);
            mysqli_stmt_execute($stmt);
            $session_result = mysqli_stmt_get_result($stmt);
            
            if ($session_result && mysqli_num_rows($session_result) > 0) {
                $session_row = mysqli_fetch_assoc($session_result);
                echo "<h3>5. Current Session Record:</h3>";
                echo "<table>";
                echo "<tr><th>Log ID</th><th>Username</th><th>Login Time</th><th>Logout Time</th><th>IP</th></tr>";
                echo "<tr>";
                echo "<td>" . $session_row['log_id'] . "</td>";
                echo "<td>" . htmlspecialchars($session_row['username']) . "</td>";
                echo "<td>" . $session_row['log_in_time'] . "</td>";
                echo "<td>" . ($session_row['log_out_time'] ?? '<span style="color:green;">Active</span>') . "</td>";
                echo "<td>" . htmlspecialchars($session_row['ip_address'] ?? 'NULL') . "</td>";
                echo "</tr>";
                echo "</table>";
            } else {
                echo "<p class='error'>Session log ID " . $_SESSION['log_id'] . " not found in database!</p>";
            }
        }
        ?>
        
        <h3>Actions:</h3>
        <a href="history.php" class="action">Return to History Page</a>
        <a href="cleanup_sessions.php" class="action">Cleanup Sessions</a>
        <a href="?" class="action">Refresh Debug</a>
    </div>
</body>
</html> 