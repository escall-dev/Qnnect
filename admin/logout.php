<?php
require_once '../includes/session_config.php';

// CRITICAL: Extract session data BEFORE any database operations
$username = isset($_SESSION['username']) ? trim($_SESSION['username']) : null;
$profile_image = isset($_SESSION['profile_image']) ? trim($_SESSION['profile_image']) : null;

// Include database connection
require_once "database.php";

// Only proceed if we have a valid username and database connection
if ($username && !empty($username) && $conn) {
    
    // Ensure recent_logins table exists
    $checkTable = "SHOW TABLES LIKE 'recent_logins'";
    $result = mysqli_query($conn, $checkTable);
    
    if (mysqli_num_rows($result) == 0) {
        // Create table if it doesn't exist
        $createTable = "CREATE TABLE recent_logins (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(255) UNIQUE NOT NULL,
            profile_image VARCHAR(255) DEFAULT NULL,
            last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_last_login (last_login),
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        mysqli_query($conn, $createTable);
    }
    
    // Store/update the profile
    $sql = "INSERT INTO recent_logins (username, profile_image, last_login) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            profile_image = VALUES(profile_image), 
            last_login = NOW()";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $profile_image);
        mysqli_stmt_execute($stmt);
    }
}

// NOW destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>      