<?php
require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';

echo "<h1>Teacher Schedules Table Analysis</h1>";

$conn = $conn_qr;

// First, let's check if the table exists and see its structure
echo "<h2>1. Table Structure:</h2>";
$structure_query = "DESCRIBE teacher_schedules";
$result = $conn->query($structure_query);

if ($result) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

// Now let's see all the data in the table
echo "<h2>2. All Data in teacher_schedules:</h2>";
$data_query = "SELECT * FROM teacher_schedules ORDER BY id";
$result = $conn->query($data_query);

if ($result) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Teacher Username</th><th>Subject</th><th>Section</th><th>Day</th><th>Start Time</th><th>End Time</th><th>Room</th><th>School ID</th><th>User ID</th><th>Status</th><th>Created</th><th>Updated</th>";
    echo "</tr>";
    
    $row_count = 0;
    while ($row = $result->fetch_assoc()) {
        $row_count++;
        $row_color = $row['status'] === 'active' ? '#e8f5e8' : '#ffe8e8';
        echo "<tr style='background-color: $row_color;'>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['teacher_username'] . "</td>";
        echo "<td><strong>" . $row['subject'] . "</strong></td>";
        echo "<td>" . $row['section'] . "</td>";
        echo "<td>" . $row['day_of_week'] . "</td>";
        echo "<td>" . $row['start_time'] . "</td>";
        echo "<td>" . $row['end_time'] . "</td>";
        echo "<td>" . ($row['room'] ?? 'N/A') . "</td>";
        echo "<td>" . $row['school_id'] . "</td>";
        echo "<td>" . ($row['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "<td>" . $row['updated_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Total records: $row_count</strong></p>";
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

// Get unique subjects
echo "<h2>3. Unique Subjects in teacher_schedules:</h2>";
$subjects_query = "SELECT DISTINCT subject FROM teacher_schedules WHERE status = 'active' ORDER BY subject";
$result = $conn->query($subjects_query);

if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li><strong>" . $row['subject'] . "</strong></li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

// Get subjects by school
echo "<h2>4. Subjects by School ID:</h2>";
$subjects_by_school_query = "SELECT school_id, subject, COUNT(*) as count FROM teacher_schedules WHERE status = 'active' GROUP BY school_id, subject ORDER BY school_id, subject";
$result = $conn->query($subjects_by_school_query);

if ($result) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>School ID</th><th>Subject</th><th>Schedule Count</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['school_id'] . "</td>";
        echo "<td><strong>" . $row['subject'] . "</strong></td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

// Check current user's session info
echo "<h2>5. Current Session Info:</h2>";
echo "<p><strong>Email:</strong> " . ($_SESSION['email'] ?? 'Not set') . "</p>";
echo "<p><strong>School ID:</strong> " . ($_SESSION['school_id'] ?? 'Not set') . "</p>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p><strong>Username:</strong> " . ($_SESSION['userData']['username'] ?? 'Not set') . "</p>";

// Get subjects for current user
if (isset($_SESSION['userData']['username']) && isset($_SESSION['school_id'])) {
    echo "<h2>6. Subjects for Current User:</h2>";
    $current_username = $_SESSION['userData']['username'];
    $current_school_id = $_SESSION['school_id'];
    
    $user_subjects_query = "SELECT DISTINCT subject FROM teacher_schedules WHERE teacher_username = ? AND school_id = ? AND status = 'active' ORDER BY subject";
    $stmt = $conn->prepare($user_subjects_query);
    $stmt->bind_param("si", $current_username, $current_school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<ul>";
    $subject_count = 0;
    while ($row = $result->fetch_assoc()) {
        $subject_count++;
        echo "<li><strong>" . $row['subject'] . "</strong></li>";
    }
    echo "</ul>";
    echo "<p><strong>Total subjects for current user: $subject_count</strong></p>";
}
?>

<style>
table {
    width: 100%;
    margin: 10px 0;
}
th {
    background-color: #f0f0f0;
    font-weight: bold;
}
td, th {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}
h1, h2 {
    color: #333;
    border-bottom: 2px solid #098744;
    padding-bottom: 5px;
}
</style>
