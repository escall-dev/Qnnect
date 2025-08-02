<?php
// Create class_time_settings table if it doesn't exist
require_once('conn/db_connect.php');

echo "<h2>Setting up class_time_settings table...</h2>";

if (!isset($conn_qr)) {
    die("Error: Database connection not available");
}

// Create table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS class_time_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    start_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_school_time (school_id)
)";

if ($conn_qr->query($createTableQuery)) {
    echo "<p style='color: green;'>✅ Table class_time_settings created/verified successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating table: " . $conn_qr->error . "</p>";
    exit;
}

// Check if table exists and show structure
$result = $conn_qr->query("SHOW TABLES LIKE 'class_time_settings'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Table exists and is accessible</p>";
    
    // Show table structure
    $structure = $conn_qr->query("DESCRIBE class_time_settings");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
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
    
    // Show current data
    $data = $conn_qr->query("SELECT * FROM class_time_settings ORDER BY updated_at DESC");
    echo "<h3>Current Data:</h3>";
    if ($data->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>ID</th><th>School ID</th><th>Start Time</th><th>Created At</th><th>Updated At</th></tr>";
        while ($row = $data->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['school_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['start_time']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($row['updated_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No data found in table (this is normal for a new setup)</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Table still doesn't exist after creation attempt</p>";
}

echo "<hr>";
echo "<h3>Session Information:</h3>";
session_start();
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p><strong>School ID:</strong> " . ($_SESSION['school_id'] ?? 'Not set') . "</p>";
echo "<p><strong>Current Class Time (Session):</strong> " . ($_SESSION['class_start_time'] ?? 'Not set') . "</p>";

if (!isset($_SESSION['school_id'])) {
    echo "<p style='color: red;'>❌ No school_id in session! This needs to be set for database storage to work.</p>";
    echo "<p>You may need to log in properly or set up your session.</p>";
}

echo "<hr>";
echo "<a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Back to Main Page</a>";
?>
