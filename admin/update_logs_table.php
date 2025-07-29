<?php
require_once "database.php";

// Check if the table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_user_logs'");
if (mysqli_num_rows($check_table) == 0) {
    // Create the table if it doesn't exist
    $create_table = "CREATE TABLE tbl_user_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        user_type VARCHAR(20) DEFAULT 'User',
        log_in_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        log_out_time DATETIME NULL,
        ip_address VARCHAR(45)
    )";
    
    if (mysqli_query($conn, $create_table)) {
        echo "Table tbl_user_logs created successfully.<br>";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "<br>";
    }
}

// Check if email column exists
$check_email = mysqli_query($conn, "SHOW COLUMNS FROM tbl_user_logs LIKE 'email'");
if (mysqli_num_rows($check_email) == 0) {
    // Add email column if it doesn't exist
    $add_email = "ALTER TABLE tbl_user_logs ADD COLUMN email VARCHAR(100) AFTER username";
    
    if (mysqli_query($conn, $add_email)) {
        echo "Email column added to tbl_user_logs successfully.<br>";
    } else {
        echo "Error adding email column: " . mysqli_error($conn) . "<br>";
    }
}

echo "Table structure check completed.<br>";
echo "<a href='history.php'>Go to History Page</a>";
?> 