<?php
require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';

echo "<h2>Debugging Instructor Name Issue</h2>";

// Check teacher_schedules table structure
echo "<h3>1. teacher_schedules Table Structure:</h3>";
$result = $conn_qr->query("DESCRIBE teacher_schedules");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $val) {
        echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Check if instructor_name column exists in teacher_schedules
$columns = $conn_qr->query("SHOW COLUMNS FROM teacher_schedules LIKE 'instructor_name'");
if ($columns->num_rows > 0) {
    echo "<p style='color: green;'>✓ instructor_name column EXISTS in teacher_schedules</p>";
} else {
    echo "<p style='color: red;'>✗ instructor_name column DOES NOT EXIST in teacher_schedules</p>";
}

$columns = $conn_qr->query("SHOW COLUMNS FROM teacher_schedules LIKE 'teacher_username'");
if ($columns->num_rows > 0) {
    echo "<p style='color: green;'>✓ teacher_username column EXISTS in teacher_schedules</p>";
} else {
    echo "<p style='color: red;'>✗ teacher_username column DOES NOT EXIST in teacher_schedules</p>";
}

// Check sample data from teacher_schedules
echo "<h3>2. Sample Data from teacher_schedules:</h3>";
$result = $conn_qr->query("SELECT id, teacher_username, subject, section, user_id, school_id FROM teacher_schedules LIMIT 5");
echo "<table border='1'><tr><th>ID</th><th>teacher_username</th><th>Subject</th><th>Section</th><th>user_id</th><th>school_id</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['teacher_username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
    echo "<td>" . htmlspecialchars($row['section']) . "</td>";
    echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['school_id']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check tbl_instructors table structure
echo "<h3>3. tbl_instructors Table Structure:</h3>";
$result = $conn_qr->query("DESCRIBE tbl_instructors");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $val) {
        echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Check sample data from tbl_instructors
echo "<h3>4. Sample Data from tbl_instructors:</h3>";
$result = $conn_qr->query("SELECT instructor_id, instructor_name FROM tbl_instructors LIMIT 5");
echo "<table border='1'><tr><th>instructor_id</th><th>instructor_name</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['instructor_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['instructor_name']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check session data
echo "<h3>5. Current Session Data:</h3>";
echo "<p><strong>current_instructor_name:</strong> " . htmlspecialchars($_SESSION['current_instructor_name'] ?? 'Not set') . "</p>";
echo "<p><strong>userData username:</strong> " . htmlspecialchars($_SESSION['userData']['username'] ?? 'Not set') . "</p>";
echo "<p><strong>userData email:</strong> " . htmlspecialchars($_SESSION['userData']['email'] ?? 'Not set') . "</p>";

// Check if class_schedules table exists
echo "<h3>6. class_schedules Table Status:</h3>";
$result = $conn_qr->query("SHOW TABLES LIKE 'class_schedules'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ class_schedules table EXISTS</p>";
    
    // Check structure
    $result = $conn_qr->query("DESCRIBE class_schedules");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>✗ class_schedules table DOES NOT EXIST</p>";
}

echo "<h3>7. Summary:</h3>";
echo "<p>The issue is likely that some code is trying to query 'instructor_name' from 'teacher_schedules' table, ";
echo "but that table uses 'teacher_username' field instead. The 'instructor_name' field exists in 'tbl_instructors' table.</p>";
?>
