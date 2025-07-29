<?php
// Include database connection
include('./conn/db_connect.php');

// Display header
echo "<html><head><title>Setup Attendance Grade System</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    h1 { color: #098744; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .box { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
</style>";
echo "</head><body>";
echo "<h1>Setting Up Attendance Grade System</h1>";
echo "<div class='box'>";

// Load SQL file content
$sql_content = file_get_contents('./sql_attendance_grade.sql');

if ($sql_content === false) {
    echo "<p class='error'>Failed to read SQL file.</p>";
    exit;
}

// Split SQL into individual statements
$statements = explode(';', $sql_content);

// Execute each statement
$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    
    if (empty($statement)) {
        continue;
    }
    
    try {
        if ($conn_qr->query($statement)) {
            echo "<p class='success'>Success: " . substr($statement, 0, 80) . "...</p>";
            $success_count++;
        } else {
            echo "<p class='error'>Error: " . $conn_qr->error . "</p>";
            echo "<p>Statement: " . htmlspecialchars($statement) . "</p>";
            $error_count++;
        }
    } catch (Exception $e) {
        echo "<p class='error'>Exception: " . $e->getMessage() . "</p>";
        echo "<p>Statement: " . htmlspecialchars($statement) . "</p>";
        $error_count++;
    }
}

echo "</div>";

// Create API directories if they don't exist
$api_dir = './api';
if (!is_dir($api_dir)) {
    if (mkdir($api_dir, 0755)) {
        echo "<p class='success'>Created API directory.</p>";
    } else {
        echo "<p class='error'>Failed to create API directory.</p>";
    }
}

// Create includes directory if it doesn't exist
$includes_dir = './includes';
if (!is_dir($includes_dir)) {
    if (mkdir($includes_dir, 0755)) {
        echo "<p class='success'>Created includes directory.</p>";
    } else {
        echo "<p class='error'>Failed to create includes directory.</p>";
    }
}

// Summary
echo "<h2>Summary</h2>";
echo "<p>Executed $success_count statements successfully.</p>";
echo "<p>Encountered $error_count errors.</p>";

// Next steps
echo "<h2>Next Steps</h2>";
echo "<p>The database schema for the attendance grade system has been set up.</p>";
echo "<p>You can now:</p>";
echo "<ul>";
echo "<li>Start using the attendance grade system by navigating to the <a href='index.php'>home page</a>.</li>";
echo "<li>View attendance grades in the <a href='attendance-grades.php'>Attendance Grades</a> page.</li>";
echo "</ul>";

echo "</body></html>";
?> 