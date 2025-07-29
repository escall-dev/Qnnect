<?php
session_start();
require_once "database.php";
require_once "functions/log_functions.php";

// IMMEDIATE CLEANUP - NO QUESTIONS ASKED
echo "<!DOCTYPE html><html><head><title>Immediate Cleanup</title></head><body>";
echo "<h1>🔧 IMMEDIATE CLEANUP IN PROGRESS...</h1>";

if (!$conn) {
    echo "<p style='color:red;'>Database connection failed</p>";
    exit;
}

// Step 1: Show current mess
echo "<h3>BEFORE CLEANUP:</h3>";
$before_query = "SELECT COUNT(*) as active_count FROM tbl_user_logs WHERE log_out_time IS NULL";
$before_result = mysqli_query($conn, $before_query);
$before_count = mysqli_fetch_assoc($before_result)['active_count'];
echo "<p>Active sessions before: <strong>$before_count</strong></p>";

// Step 2: FORCE CLOSE ALL ACTIVE SESSIONS
echo "<h3>CLOSING ALL ACTIVE SESSIONS...</h3>";
$close_all = "UPDATE tbl_user_logs SET log_out_time = NOW() WHERE log_out_time IS NULL";
if (mysqli_query($conn, $close_all)) {
    $closed = mysqli_affected_rows($conn);
    echo "<p style='color:green;'>✅ CLOSED $closed active sessions</p>";
} else {
    echo "<p style='color:red;'>❌ Failed: " . mysqli_error($conn) . "</p>";
    exit;
}

// Step 3: CREATE ONE NEW CLEAN SESSION
echo "<h3>CREATING NEW CLEAN SESSION...</h3>";
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $user_type = $_SESSION['user_type'] ?? 'User';
    
    $new_log_id = recordUserLogin($conn, $username, '', $user_type);
    
    if ($new_log_id) {
        $_SESSION['log_id'] = $new_log_id;
        $_SESSION['login_recorded'] = true;
        $_SESSION['forced_log_record'] = true;
        echo "<p style='color:green;'>✅ NEW SESSION CREATED: ID $new_log_id</p>";
    } else {
        echo "<p style='color:red;'>❌ Failed to create new session</p>";
    }
} else {
    echo "<p style='color:red;'>❌ No username in session</p>";
}

// Step 4: Verify cleanup
echo "<h3>AFTER CLEANUP:</h3>";
$after_query = "SELECT COUNT(*) as active_count FROM tbl_user_logs WHERE log_out_time IS NULL";
$after_result = mysqli_query($conn, $after_query);
$after_count = mysqli_fetch_assoc($after_result)['active_count'];
echo "<p>Active sessions now: <strong>$after_count</strong></p>";

if ($after_count == 1) {
    echo "<p style='color:green; font-size:20px;'>🎉 SUCCESS! Only 1 active session remaining!</p>";
} else {
    echo "<p style='color:red; font-size:20px;'>⚠️ Still $after_count active sessions - something's wrong!</p>";
}

// Step 5: Force browser cache clear
echo "<h3>CLEARING BROWSER CACHE...</h3>";
echo "<script>
    // Clear browser cache
    if ('caches' in window) {
        caches.keys().then(function(names) {
            names.forEach(function(name) {
                caches.delete(name);
            });
        });
    }
    
    // Force reload in 3 seconds
    setTimeout(function() {
        window.location.href = 'history.php?cache_bust=' + new Date().getTime();
    }, 3000);
</script>";

echo "<p style='color:blue;'>🔄 Redirecting to history page in 3 seconds with cache cleared...</p>";
echo "<p><a href='history.php?cache_bust=" . time() . "' style='background:#098744;color:white;padding:10px;text-decoration:none;'>GO TO HISTORY NOW</a></p>";

echo "</body></html>";
?> 