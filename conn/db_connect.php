<?php
// Guard against multiple inclusion
if (!defined('DB_CONNECT_INCLUDED')) {
    define('DB_CONNECT_INCLUDED', true);

// Database configuration
$hostName = "localhost";
$dbUser = "root";
$dbPassword = "";

// First database connection (login_register)
$loginDb = "login_register";
$conn_login = mysqli_connect($hostName, $dbUser, $dbPassword, $loginDb);
if (!$conn_login) {
    die("Connection failed to login_register: " . mysqli_connect_error());
}

// Second database connection (qr_attendance_db)
$qrDb = "qr_attendance_db";
$conn_qr = mysqli_connect($hostName, $dbUser, $dbPassword, $qrDb);
if (!$conn_qr) {
    die("Connection failed to qr_attendance_db: " . mysqli_connect_error());
}

// Create the face verification logs table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS tbl_face_verification_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100),
    status VARCHAR(20),
    verification_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    notes TEXT
)";

if (!mysqli_query($conn_qr, $create_table_query)) {
    die("Error creating table: " . mysqli_error($conn_qr));
}

// Make both connections available globally
$GLOBALS['conn_login'] = $conn_login;
$GLOBALS['conn_qr'] = $conn_qr;

// Function to get the appropriate connection
    if (!function_exists('getConnection')) {
function getConnection($database) {
    global $conn_login, $conn_qr;
    if ($database === 'login') {
        return $conn_login;
    }
    return $conn_qr;
        }
    }
}
?>
