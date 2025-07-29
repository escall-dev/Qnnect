<?php
// Guard against multiple inclusion
if (!defined('DB_CONNECT_PDO_INCLUDED')) {
    define('DB_CONNECT_PDO_INCLUDED', true);

    // Database configuration
    $hostName = "localhost";
    $dbUser = "root";
    $dbPassword = "";

    // Database connections using PDO
    try {
        // First database connection (login_register)
        $loginDb = "login_register";
        $conn_login_pdo = new PDO("mysql:host=$hostName;dbname=$loginDb", $dbUser, $dbPassword);
        $conn_login_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Second database connection (qr_attendance_db)
        $qrDb = "qr_attendance_db";
        $conn_qr_pdo = new PDO("mysql:host=$hostName;dbname=$qrDb", $dbUser, $dbPassword);
        $conn_qr_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create the face verification logs table if it doesn't exist
        $create_table_query = "CREATE TABLE IF NOT EXISTS tbl_face_verification_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            student_name VARCHAR(100),
            status VARCHAR(20),
            verification_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            notes TEXT
        )";
        
        $conn_qr_pdo->exec($create_table_query);
        
        // Make both connections available globally
        $GLOBALS['conn_login_pdo'] = $conn_login_pdo;
        $GLOBALS['conn_qr_pdo'] = $conn_qr_pdo;
        
        // Function to get the appropriate PDO connection
        if (!function_exists('getConnectionPDO')) {
            function getConnectionPDO($database) {
                global $conn_login_pdo, $conn_qr_pdo;
                if ($database === 'login') {
                    return $conn_login_pdo;
                }
                return $conn_qr_pdo;
            }
        }
        
    } catch(PDOException $e) {
        echo "<div style='color:red; padding:10px; margin:10px; border:1px solid red;'>";
        echo "<h3>Database Connection Error</h3>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        
        if (strpos($e->getMessage(), 'could not find driver') !== false) {
            echo "<p>The PDO MySQL driver is not enabled. Please check your PHP configuration:</p>";
            echo "<ol>";
            echo "<li>Open php.ini file</li>";
            echo "<li>Find and uncomment the line ;extension=pdo_mysql by removing the semicolon</li>";
            echo "<li>Set the extension_dir to point to your extensions directory (usually ext/ in your PHP installation folder)</li>";
            echo "<li>Save the file and restart your web server</li>";
            echo "</ol>";
        }
        
        echo "<p>As an alternative, try using XAMPP's PHP by accessing your project through:</p>";
        echo "<p><a href='http://localhost/qr-code-attendance-system/attendance-grades.php'>http://localhost/qr-code-attendance-system/attendance-grades.php</a></p>";
        echo "</div>";
        die();
    }
}
?> 