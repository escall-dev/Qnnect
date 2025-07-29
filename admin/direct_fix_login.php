<?php
// Direct fix script for user login records
session_start();
require_once "database.php";

// Set content type to show nice output in browser
header("Content-Type: text/html");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Login Records</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #098744;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .info {
            color: blue;
        }
        .action {
            background: #098744;
            color: white;
            padding: 10px 15px;
            border-radius: 3px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login Records Fix Tool</h1>
        
        <?php
        // Check database connection
        if (!$conn) {
            echo "<p class='error'>Database connection failed: " . mysqli_connect_error() . "</p>";
            exit;
        }
        
        echo "<p class='info'>Database connection successful.</p>";
        
        // 1. Check if user is logged in
        if (!isset($_SESSION['username'])) {
            echo "<p class='error'>No active session found. Please log in first.</p>";
            echo "<a href='login.php' class='action'>Go to Login Page</a>";
            exit;
        }
        
        echo "<p>Current user: <strong>" . htmlspecialchars($_SESSION['username']) . "</strong></p>";
        
        // 2. Check if table exists
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_user_logs'");
        if (mysqli_num_rows($check_table) == 0) {
            echo "<p class='error'>Table tbl_user_logs does not exist. Creating table...</p>";
            
            $create_table = "CREATE TABLE IF NOT EXISTS tbl_user_logs (
                log_id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                user_type VARCHAR(20) DEFAULT 'User',
                log_in_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                log_out_time DATETIME NULL,
                ip_address VARCHAR(45)
            )";
            
            if (mysqli_query($conn, $create_table)) {
                echo "<p class='success'>Table created successfully!</p>";
            } else {
                echo "<p class='error'>Failed to create table: " . mysqli_error($conn) . "</p>";
                exit;
            }
        } else {
            echo "<p class='info'>Table tbl_user_logs exists.</p>";
        }
        
        // 3. Handle fixing actions
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'fix_active':
                    // Update any currently active sessions to logged out (except the current one)
                    $username = $_SESSION['username'];
                    $query = "UPDATE tbl_user_logs 
                              SET log_out_time = NOW() 
                              WHERE username = ? 
                              AND log_out_time IS NULL 
                              AND log_id != ?";
                    
                    $stmt = mysqli_prepare($conn, $query);
                    $current_log_id = $_SESSION['log_id'] ?? 0;
                    mysqli_stmt_bind_param($stmt, "si", $username, $current_log_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $affected = mysqli_stmt_affected_rows($stmt);
                        echo "<p class='success'>Fixed $affected stale active sessions for user $username</p>";
                    } else {
                        echo "<p class='error'>Failed to fix active sessions: " . mysqli_error($conn) . "</p>";
                    }
                    break;
                
                case 'create_record':
                    // Create a new login record for current session
                    $username = $_SESSION['username'];
                    $user_type = $_SESSION['user_type'] ?? 'User';
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Manual Fix';
                    
                    $query = "INSERT INTO tbl_user_logs (username, user_type, ip_address) 
                              VALUES (?, ?, ?)";
                    
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "sss", $username, $user_type, $ip);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $log_id = mysqli_insert_id($conn);
                        $_SESSION['log_id'] = $log_id;
                        $_SESSION['login_recorded'] = true;
                        echo "<p class='success'>Created new login record with ID: $log_id</p>";
                    } else {
                        echo "<p class='error'>Failed to create login record: " . mysqli_error($conn) . "</p>";
                    }
                    break;
                
                case 'test_record':
                    // Create test record and immediately log it out
                    $username = $_SESSION['username'] . '_TEST';
                    $user_type = $_SESSION['user_type'] ?? 'User';
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Test';
                    
                    $query = "INSERT INTO tbl_user_logs (username, user_type, ip_address) 
                              VALUES (?, ?, ?)";
                    
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "sss", $username, $user_type, $ip);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $log_id = mysqli_insert_id($conn);
                        
                        // Now immediately set the logout time
                        $update = "UPDATE tbl_user_logs SET log_out_time = NOW() WHERE log_id = ?";
                        $update_stmt = mysqli_prepare($conn, $update);
                        mysqli_stmt_bind_param($update_stmt, "i", $log_id);
                        
                        if (mysqli_stmt_execute($update_stmt)) {
                            echo "<p class='success'>Test record created and logged out with ID: $log_id</p>";
                        } else {
                            echo "<p class='error'>Failed to update test record: " . mysqli_error($conn) . "</p>";
                        }
                    } else {
                        echo "<p class='error'>Failed to create test record: " . mysqli_error($conn) . "</p>";
                    }
                    break;
            }
        }
        
        // 4. Get current active sessions
        $active_query = "SELECT * FROM tbl_user_logs WHERE log_out_time IS NULL ORDER BY log_in_time DESC";
        $active_result = mysqli_query($conn, $active_query);
        
        echo "<h3>Current Active Sessions:</h3>";
        if (mysqli_num_rows($active_result) > 0) {
            echo "<ul>";
            while ($row = mysqli_fetch_assoc($active_result)) {
                echo "<li>ID: " . $row['log_id'] . 
                     " | User: " . htmlspecialchars($row['username']) . 
                     " | Login time: " . $row['log_in_time'] . 
                     " | IP: " . htmlspecialchars($row['ip_address'] ?? 'Unknown') . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No active sessions found.</p>";
        }
        ?>
        
        <h3>Available Actions:</h3>
        <p><a href="?action=fix_active" class="action">Fix Stale Active Sessions</a></p>
        <p><a href="?action=create_record" class="action">Create New Login Record for Current Session</a></p>
        <p><a href="?action=test_record" class="action">Create Test Record (with immediate logout)</a></p>
        <p><a href="history.php" class="action">Return to History Page</a></p>
    </div>
</body>
</html> 