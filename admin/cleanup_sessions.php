<?php
session_start();
require_once "database.php";
require_once "functions/log_functions.php";

header("Content-Type: text/html");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Cleanup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 5px; }
        h1 { color: #098744; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .action { background: #098744; color: white; padding: 10px 15px; border-radius: 3px; text-decoration: none; display: inline-block; margin: 10px 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Session Cleanup Tool</h1>
        
        <?php
        if (!$conn) {
            echo "<p class='error'>Database connection failed</p>";
            exit;
        }

        echo "<p class='info'>Current user: " . ($_SESSION['username'] ?? 'Not logged in') . "</p>";
        echo "<p class='info'>Current log ID: " . ($_SESSION['log_id'] ?? 'Not set') . "</p>";
        
        // Show all active sessions
        echo "<h3>All Active Sessions:</h3>";
        $active_query = "SELECT * FROM tbl_user_logs WHERE log_out_time IS NULL ORDER BY log_in_time DESC";
        $active_result = mysqli_query($conn, $active_query);
        
        if (mysqli_num_rows($active_result) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Username</th><th>Login Time</th><th>IP</th></tr>";
            while ($row = mysqli_fetch_assoc($active_result)) {
                echo "<tr>";
                echo "<td>" . $row['log_id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo "<td>" . $row['log_in_time'] . "</td>";
                echo "<td>" . htmlspecialchars($row['ip_address'] ?? 'Unknown') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Handle cleanup actions
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'cleanup_old':
                    // Close all sessions except the current one
                    $current_log_id = $_SESSION['log_id'] ?? 0;
                    $current_username = $_SESSION['username'];
                    
                    $cleanup_query = "UPDATE tbl_user_logs 
                                     SET log_out_time = NOW() 
                                     WHERE username = ? 
                                     AND log_out_time IS NULL 
                                     AND log_id != ?";
                    
                    $stmt = mysqli_prepare($conn, $cleanup_query);
                    mysqli_stmt_bind_param($stmt, "si", $current_username, $current_log_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $affected = mysqli_stmt_affected_rows($stmt);
                        echo "<p class='success'>Cleaned up $affected old active sessions</p>";
                    } else {
                        echo "<p class='error'>Failed to cleanup: " . mysqli_error($conn) . "</p>";
                    }
                    break;
                    
                case 'show_recent':
                    echo "<h3>Most Recent 20 Records (All Time):</h3>";
                    $recent_query = "SELECT * FROM tbl_user_logs ORDER BY log_in_time DESC LIMIT 20";
                    $recent_result = mysqli_query($conn, $recent_query);
                    
                    if (mysqli_num_rows($recent_result) > 0) {
                        echo "<table>";
                        echo "<tr><th>ID</th><th>Username</th><th>Login Time</th><th>Logout Time</th><th>IP</th></tr>";
                        while ($row = mysqli_fetch_assoc($recent_result)) {
                            echo "<tr>";
                            echo "<td>" . $row['log_id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td>" . $row['log_in_time'] . "</td>";
                            echo "<td>" . ($row['log_out_time'] ?? '<span style="color:green;">Active</span>') . "</td>";
                            echo "<td>" . htmlspecialchars($row['ip_address'] ?? 'Unknown') . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                    break;
                    
                case 'force_logout_all':
                    // Force logout ALL active sessions
                    $logout_all_query = "UPDATE tbl_user_logs SET log_out_time = NOW() WHERE log_out_time IS NULL";
                    
                    if (mysqli_query($conn, $logout_all_query)) {
                        $affected = mysqli_affected_rows($conn);
                        echo "<p class='success'>Force logged out $affected sessions</p>";
                        
                        // Create a new login for current user
                        if (isset($_SESSION['username'])) {
                            $new_log_id = recordUserLogin($conn, $_SESSION['username'], '', $_SESSION['user_type'] ?? 'User');
                            $_SESSION['log_id'] = $new_log_id;
                            echo "<p class='success'>Created new session with ID: $new_log_id</p>";
                        }
                    } else {
                        echo "<p class='error'>Failed to force logout all: " . mysqli_error($conn) . "</p>";
                    }
                    break;
            }
        }
        ?>
        
        <h3>Actions:</h3>
        <a href="?action=cleanup_old" class="action">Cleanup My Old Sessions</a>
        <a href="?action=show_recent" class="action">Show Recent 20 Records</a>
        <a href="?action=force_logout_all" class="action">Force Logout ALL & Create New Session</a>
        <a href="history.php" class="action">Return to History Page</a>
        
        <h3>Server Info:</h3>
        <?php
        $time_query = "SELECT NOW() as server_time";
        $time_result = mysqli_query($conn, $time_query);
        if ($time_result) {
            $time_row = mysqli_fetch_assoc($time_result);
            echo "<p>Server time: " . $time_row['server_time'] . "</p>";
        }
        
        $count_query = "SELECT COUNT(*) as total FROM tbl_user_logs";
        $count_result = mysqli_query($conn, $count_query);
        if ($count_result) {
            $count_row = mysqli_fetch_assoc($count_result);
            echo "<p>Total records in table: " . $count_row['total'] . "</p>";
        }
        ?>
    </div>
</body>
</html> 