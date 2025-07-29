<?php
// Database Connection Test with Retry Logic
$hostName = "localhost";
$dbUser = "root";
$dbPassword = "";
$loginDb = "login_register";
$qrDb = "qr_attendance_db";

function testDatabaseConnection($database, $maxRetries = 5) {
    global $hostName, $dbUser, $dbPassword;
    
    for ($i = 0; $i < $maxRetries; $i++) {
        $conn = mysqli_connect($hostName, $dbUser, $dbPassword, $database);
        if ($conn) {
            echo "✅ Connected to $database successfully (attempt " . ($i + 1) . ")<br>";
            // Safely close the connection
            if (isset($conn) && $conn instanceof mysqli) {
                try {
                    if ($conn->ping()) {
                        mysqli_close($conn);
                    }
                } catch (Throwable $e) {
                    // Connection is already closed or invalid, do nothing
                }
            }
            return true;
        } else {
            echo "❌ Failed to connect to $database (attempt " . ($i + 1) . "): " . mysqli_connect_error() . "<br>";
            if ($i < $maxRetries - 1) {
                echo "Waiting 2 seconds before retry...<br>";
                sleep(2);
            }
        }
    }
    return false;
}

echo "<h2>Database Connection Test</h2>";
echo "<p>Testing database connections...</p>";

if (testDatabaseConnection($loginDb)) {
    echo "<p style='color: green;'>Login database is working!</p>";
} else {
    echo "<p style='color: red;'>Login database failed after all retries!</p>";
}

if (testDatabaseConnection($qrDb)) {
    echo "<p style='color: green;'>QR attendance database is working!</p>";
} else {
    echo "<p style='color: red;'>QR attendance database failed after all retries!</p>";
}

echo "<p>Test completed at " . date('Y-m-d H:i:s') . "</p>";
?>
