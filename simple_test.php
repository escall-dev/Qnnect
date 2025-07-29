<?php
// Simple test to check database tables

// Connect to the database directly
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'qr_attendance_db';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database Connection: Success<br>";

// List tables
echo "<h3>Tables in database:</h3>";
$tables = $conn->query("SHOW TABLES");
if ($tables) {
    echo "<ul>";
    while ($table = $tables->fetch_row()) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error listing tables: " . $conn->error;
}

// Check if attendance_grades table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'attendance_grades'");
if ($checkTable->num_rows > 0) {
    echo "<p>attendance_grades table exists!</p>";
    
    // Count records
    $count = $conn->query("SELECT COUNT(*) as count FROM attendance_grades");
    $countRow = $count->fetch_assoc();
    echo "<p>Total grades: " . $countRow['count'] . "</p>";
    
    // Check structure
    echo "<h3>Table structure:</h3>";
    $structure = $conn->query("DESCRIBE attendance_grades");
    if ($structure) {
        echo "<ul>";
        while ($field = $structure->fetch_assoc()) {
            echo "<li>" . $field['Field'] . " - " . $field['Type'] . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>attendance_grades table does not exist. Let's create it:</p>";
    
    // Create attendance_grades table
    $createTable = "CREATE TABLE IF NOT EXISTS attendance_grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        term VARCHAR(50) NOT NULL,
        section VARCHAR(10) NOT NULL,
        attendance_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
        attendance_grade DECIMAL(3,2) NOT NULL DEFAULT 5.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTable) === TRUE) {
        echo "<p>attendance_grades table created successfully!</p>";
    } else {
        echo "<p>Error creating table: " . $conn->error . "</p>";
    }
}

// Check if other required tables exist
$requiredTables = ['attendance_sessions', 'attendance_logs', 'tbl_student', 'tbl_subjects'];

echo "<h3>Required tables status:</h3>";
echo "<ul>";
foreach ($requiredTables as $tableName) {
    $checkTable = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($checkTable->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as count FROM $tableName");
        $countRow = $count->fetch_assoc();
        echo "<li>$tableName exists! (Records: " . $countRow['count'] . ")</li>";
    } else {
        echo "<li>$tableName does not exist!</li>";
    }
}
echo "</ul>";

// Safely close the connection
if (isset($conn) && $conn instanceof mysqli) {
    try {
        if ($conn->ping()) {
            $conn->close();
        }
    } catch (Throwable $e) {
        // Connection is already closed or invalid, do nothing
    }
}
?> 