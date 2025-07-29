<?php
// Start session to access session variables
session_start();
require_once "database.php";
require_once "functions/log_functions.php";

// Set content type to plain text for easy reading in browser
header("Content-Type: text/plain");

echo "=== USER LOG SYSTEM DIAGNOSTIC ===\n\n";

// 1. Check if user is logged in
echo "1. SESSION CHECK:\n";
if (isset($_SESSION['username'])) {
    echo "- Username: " . $_SESSION['username'] . "\n";
    echo "- Email: " . ($_SESSION['email'] ?? 'Not set') . "\n";
    echo "- User Type: " . ($_SESSION['user_type'] ?? 'Not set') . "\n";
    echo "- Log ID in session: " . ($_SESSION['log_id'] ?? 'Not set') . "\n";
    echo "- Login recorded flag: " . (isset($_SESSION['login_recorded']) ? 'Yes' : 'No') . "\n";
    echo "- Forced log record flag: " . (isset($_SESSION['forced_log_record']) ? 'Yes' : 'No') . "\n";
} else {
    echo "User is not logged in or session data is missing.\n";
}

// 2. Check database connection
echo "\n2. DATABASE CONNECTION:\n";
if ($conn) {
    echo "- Connection successful\n";
    
    // Get server time
    $time_query = "SELECT NOW() as server_time";
    $time_result = mysqli_query($conn, $time_query);
    if ($time_result) {
        $row = mysqli_fetch_assoc($time_result);
        echo "- Server time: " . $row['server_time'] . "\n";
    } else {
        echo "- Failed to get server time: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- Connection failed: " . mysqli_connect_error() . "\n";
}

// 3. Check tbl_user_logs table
echo "\n3. TABLE CHECK:\n";
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_user_logs'");
if (mysqli_num_rows($check_table) > 0) {
    echo "- Table tbl_user_logs exists\n";
    
    // Check structure
    echo "\n   TABLE STRUCTURE:\n";
    $structure_query = mysqli_query($conn, "DESCRIBE tbl_user_logs");
    if ($structure_query) {
        while ($field = mysqli_fetch_assoc($structure_query)) {
            echo "   - " . $field['Field'] . " (" . $field['Type'] . ")\n";
        }
    } else {
        echo "   - Failed to get table structure: " . mysqli_error($conn) . "\n";
    }
    
    // Count records
    $count_query = "SELECT COUNT(*) as total FROM tbl_user_logs";
    $count_result = mysqli_query($conn, $count_query);
    if ($count_result) {
        $count = mysqli_fetch_assoc($count_result)['total'];
        echo "\n   Total records: $count\n";
        
        // Get most recent records
        $recent_query = "SELECT * FROM tbl_user_logs ORDER BY log_in_time DESC LIMIT 5";
        $recent_result = mysqli_query($conn, $recent_query);
        if ($recent_result && mysqli_num_rows($recent_result) > 0) {
            echo "\n   MOST RECENT RECORDS:\n";
            while ($log = mysqli_fetch_assoc($recent_result)) {
                echo "   - ID: " . $log['log_id'] . 
                     " | User: " . $log['username'] . 
                     " | Login: " . $log['log_in_time'] . 
                     " | Logout: " . ($log['log_out_time'] ?? 'Active') . "\n";
            }
        } else {
            echo "\n   No recent records found or query failed\n";
        }
    } else {
        echo "\n   Failed to count records: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- Table tbl_user_logs does not exist\n";
}

// 4. Test inserting a record
echo "\n4. TEST RECORD INSERTION:\n";

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $user_type = $_SESSION['user_type'] ?? 'User';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Test Script';
    
    $query = "INSERT INTO tbl_user_logs (username, user_type, ip_address, log_in_time) 
              VALUES (?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $username, $user_type, $ip);
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        $log_id = mysqli_insert_id($conn);
        echo "- Test record successfully inserted with ID: $log_id\n";
        
        // Test updating the record (simulating logout)
        $update_query = "UPDATE tbl_user_logs SET log_out_time = NOW() WHERE log_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $log_id);
        $update_result = mysqli_stmt_execute($update_stmt);
        
        if ($update_result) {
            echo "- Test record successfully updated with logout time\n";
        } else {
            echo "- Failed to update test record: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "- Failed to insert test record: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- Cannot test record insertion: no username in session\n";
}

// 5. Manual login recording
echo "\n5. FORCE LOGIN RECORD:\n";

if (isset($_GET['force']) && $_GET['force'] == 'yes' && isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $user_type = $_SESSION['user_type'] ?? 'User';
    
    $log_id = recordUserLogin($conn, $username, '', $user_type);
    
    if ($log_id) {
        $_SESSION['log_id'] = $log_id;
        $_SESSION['login_recorded'] = true;
        echo "- Successfully recorded login with ID: $log_id\n";
        echo "- Session updated with new log_id\n";
        echo "- To view history page, <a href='history.php'>click here</a>\n";
    } else {
        echo "- Failed to record login\n";
    }
} else {
    echo "- To force a login record, add ?force=yes to the URL\n";
}

echo "\n=== END OF DIAGNOSTIC ===\n";
?> 