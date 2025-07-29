<?php
session_start();
require_once "database.php";
require_once "functions/log_functions.php";

echo "<!DOCTYPE html><html><head><title>Cleanup Tool</title>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .action{background:#098744;color:white;padding:10px 15px;border-radius:3px;text-decoration:none;display:inline-block;margin:10px 5px;}</style>";
echo "</head><body>";

echo "<h1>🔧 Complete Cleanup Tool</h1>";

if (!$conn) {
    echo "<p class='error'>Database connection failed</p>";
    exit;
}

// Show current active sessions
echo "<h3>Current Active Sessions:</h3>";
$active_query = "SELECT * FROM tbl_user_logs WHERE log_out_time IS NULL ORDER BY log_id DESC";
$active_result = mysqli_query($conn, $active_query);
$active_count = mysqli_num_rows($active_result);

echo "<p>Found $active_count active sessions</p>";

// Handle cleanup
if (isset($_GET['cleanup']) && $_GET['cleanup'] == 'yes') {
    echo "<h3>🧹 CLEANING UP...</h3>";
    
    // Close ALL active sessions
    $close_all = "UPDATE tbl_user_logs SET log_out_time = NOW() WHERE log_out_time IS NULL";
    if (mysqli_query($conn, $close_all)) {
        $closed = mysqli_affected_rows($conn);
        echo "<p class='success'>✅ Closed $closed active sessions</p>";
    }
    
    // Create ONE new session for current user
    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
        $user_type = $_SESSION['user_type'] ?? 'User';
        
        $new_log_id = recordUserLogin($conn, $username, '', $user_type);
        
        if ($new_log_id) {
            $_SESSION['log_id'] = $new_log_id;
            $_SESSION['login_recorded'] = true;
            $_SESSION['forced_log_record'] = true;
            echo "<p class='success'>✅ Created new clean session: ID $new_log_id</p>";
        }
    }
    
    echo "<p class='success'>🎉 CLEANUP COMPLETE!</p>";
    echo "<a href='history.php' class='action'>Return to History</a>";
    echo "<a href='force_fresh_history.php' class='action'>View Fresh Data</a>";
} else {
    echo "<h3>Actions:</h3>";
    echo "<a href='?cleanup=yes' class='action' onclick='return confirm(\"This will close all active sessions and create one clean session. Continue?\")'>🧹 CLEANUP ALL SESSIONS</a>";
    echo "<a href='history.php' class='action'>Back to History</a>";
}

echo "</body></html>";
?> 