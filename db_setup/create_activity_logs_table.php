<?php
// Include database connection
include('../conn/db_connect.php');

// SQL to create the activity_logs table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11),
    action_type ENUM('attendance_scan', 'settings_change', 'file_action', 'user_action', 'system_change', 'data_export', 'offline_sync') NOT NULL,
    action_description TEXT NOT NULL,
    affected_table VARCHAR(50),
    affected_id INT(11),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME NOT NULL,
    additional_data JSON,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn_qr->query($sql) === TRUE) {
    echo "activity_logs table created successfully or already exists.<br>";
} else {
    echo "Error creating activity_logs table: " . $conn_qr->error . "<br>";
}

// Create offline_data table for caching attendance data
$sql_offline = "CREATE TABLE IF NOT EXISTS offline_data (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    action_type ENUM('insert', 'update', 'delete') NOT NULL,
    data JSON NOT NULL,
    status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    synced_at DATETIME,
    sync_attempts INT DEFAULT 0,
    error_message TEXT,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn_qr->query($sql_offline) === TRUE) {
    echo "offline_data table created successfully or already exists.<br>";
} else {
    echo "Error creating offline_data table: " . $conn_qr->error . "<br>";
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