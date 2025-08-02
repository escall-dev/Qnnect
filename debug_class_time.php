<?php
session_start();

// Include database connection
require_once('conn/db_connect.php');

echo "<h2>Class Time Debugging</h2>";

// Check if database connection exists
if (!isset($conn_qr)) {
    echo "<p style='color: red;'>Database connection not available</p>";
    exit;
}

echo "<p style='color: green;'>Database connection: OK</p>";

// Check if class_time_settings table exists
$tableCheckQuery = "SHOW TABLES LIKE 'class_time_settings'";
$result = $conn_qr->query($tableCheckQuery);
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>class_time_settings table: EXISTS</p>";
    
    // Show table structure
    $structureQuery = "DESCRIBE class_time_settings";
    $structure = $conn_qr->query($structureQuery);
    echo "<h3>Table Structure:</h3><table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Show current data
    $dataQuery = "SELECT * FROM class_time_settings ORDER BY updated_at DESC LIMIT 10";
    $data = $conn_qr->query($dataQuery);
    echo "<h3>Current Data:</h3>";
    if ($data->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>School ID</th><th>Start Time</th><th>Created At</th><th>Updated At</th></tr>";
        while ($row = $data->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['school_id'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['start_time'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['updated_at'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No data found in table</p>";
    }
} else {
    echo "<p style='color: red;'>class_time_settings table: DOES NOT EXIST</p>";
    
    // Create the table
    echo "<h3>Creating table...</h3>";
    $createTableQuery = "CREATE TABLE class_time_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_id INT NOT NULL,
            user_id INT NOT NULL,
            start_time TIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_school_user_time (school_id, user_id)
        )
    )";
    
    if ($conn_qr->query($createTableQuery)) {
        echo "<p style='color: green;'>Table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error creating table: " . $conn_qr->error . "</p>";
    }
}

// Show current session values
echo "<h3>Current Session Values:</h3>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p>School ID: " . ($_SESSION['school_id'] ?? 'Not set') . "</p>";
echo "<p>Class Start Time: " . ($_SESSION['class_start_time'] ?? 'Not set') . "</p>";
echo "<p>Current Instructor ID: " . ($_SESSION['current_instructor_id'] ?? 'Not set') . "</p>";
echo "<p>Current Subject: " . ($_SESSION['current_subject_name'] ?? 'Not set') . "</p>";

// Test inserting a record
if (isset($_SESSION['school_id']) && $_SESSION['school_id']) {
    echo "<h3>Testing Insert:</h3>";
    $testTime = '09:00:00';
    $school_id = $_SESSION['school_id'];
    
    $query = "INSERT INTO class_time_settings (school_id, user_id, start_time, updated_at, created_at)
              VALUES (?, ?, ?, NOW(), NOW())
              ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), updated_at = NOW()";
    $stmt = $conn_qr->prepare($query);
    $stmt->bind_param("iis", $school_id, $testTime);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>Test insert successful!</p>";
    } else {
        echo "<p style='color: red;'>Test insert failed: " . $stmt->error . "</p>";
    }
}
?>
