<?php
/**
 * Script to add missing columns to tbl_user_logs table
 */

require_once 'conn/db_connect.php';

echo "<h2>Adding Missing Columns to tbl_user_logs</h2>";

// Check if user_id column exists
$check_user_id = mysqli_query($conn, "SHOW COLUMNS FROM tbl_user_logs LIKE 'user_id'");
if (mysqli_num_rows($check_user_id) == 0) {
    echo "<p>Adding user_id column...</p>";
    $alter_user_id = "ALTER TABLE tbl_user_logs ADD COLUMN user_id INT NOT NULL DEFAULT 1";
    if (mysqli_query($conn, $alter_user_id)) {
        echo "<p>✓ user_id column added successfully</p>";
    } else {
        echo "<p>✗ Error adding user_id column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>✓ user_id column already exists</p>";
}

// Check if school_id column exists
$check_school_id = mysqli_query($conn, "SHOW COLUMNS FROM tbl_user_logs LIKE 'school_id'");
if (mysqli_num_rows($check_school_id) == 0) {
    echo "<p>Adding school_id column...</p>";
    $alter_school_id = "ALTER TABLE tbl_user_logs ADD COLUMN school_id INT NOT NULL DEFAULT 1";
    if (mysqli_query($conn, $alter_school_id)) {
        echo "<p>✓ school_id column added successfully</p>";
    } else {
        echo "<p>✗ Error adding school_id column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>✓ school_id column already exists</p>";
}

// Show current table structure
echo "<h3>Current tbl_user_logs Structure:</h3>";
$structure = mysqli_query($conn, "DESCRIBE tbl_user_logs");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($structure)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test the query that was failing
echo "<h3>Testing the failing query:</h3>";
$test_query = "SELECT COUNT(*) as count FROM tbl_user_logs WHERE school_id = 1 AND user_id = 1";
$test_result = mysqli_query($conn, $test_query);
if ($test_result) {
    $row = mysqli_fetch_assoc($test_result);
    echo "<p>✓ Query successful! Records for school_id=1, user_id=1: " . $row['count'] . "</p>";
} else {
    echo "<p>✗ Query still failing: " . mysqli_error($conn) . "</p>";
}

echo "<h3>✓ Column addition completed!</h3>";
echo "<p><a href='admin/history.php'>Go to History Page</a></p>";
?> 