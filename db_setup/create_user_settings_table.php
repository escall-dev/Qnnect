<?php
// Include database connection
include('../conn/db_connect.php');

// SQL to create the user_settings table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS user_settings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    school_year VARCHAR(10) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn_qr->query($sql) === TRUE) {
    echo "user_settings table created successfully or already exists.<br>";
} else {
    echo "Error creating user_settings table: " . $conn_qr->error . "<br>";
}

// Safely close the connection
if (isset($conn_qr) && $conn_qr instanceof mysqli) {
    try {
        if ($conn_qr->ping()) {
            $conn_qr->close();
        }
    } catch (Throwable $e) {
        // Connection is already closed or invalid, do nothing
    }
}

echo "Database setup completed.";
?> 