<?php
/**
 * Database Setup Script for Recent Logins Table
 * Run this script once to create the recent_logins table for intelligent session tracking
 */

require_once '../admin/database.php';

// Function to create recent_logins table
function createRecentLoginsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS recent_logins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(255) UNIQUE NOT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_last_login (last_login),
        INDEX idx_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    return mysqli_query($conn, $sql);
}

// Function to check if table exists
function tableExists($conn, $tableName) {
    $sql = "SHOW TABLES LIKE ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $tableName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_num_rows($result) > 0;
}

echo "<h2>QR Attendance System - Database Setup</h2>";
echo "<h3>Creating Recent Logins Table...</h3>";

try {
    // Check if table already exists
    if (tableExists($conn, 'recent_logins')) {
        echo "<p style='color: orange;'>✓ Table 'recent_logins' already exists.</p>";
    } else {
        // Create the table
        if (createRecentLoginsTable($conn)) {
            echo "<p style='color: green;'>✓ Table 'recent_logins' created successfully!</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creating table: " . mysqli_error($conn) . "</p>";
        }
    }
    
    // Verify table structure
    $describe = mysqli_query($conn, "DESCRIBE recent_logins");
    if ($describe) {
        echo "<h4>Table Structure:</h4>";
        echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = mysqli_fetch_assoc($describe)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h4>Setup Complete!</h4>";
    echo "<p style='color: green;'>The intelligent session tracking system is now ready to use.</p>";
    echo "<p><a href='../admin/login.php'>← Back to Login</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

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
?> 