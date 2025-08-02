<?php
require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';

// Get user's school_id and username
$email = $_SESSION['email'] ?? 'test@example.com';
$user_query = "SELECT school_id, role, username FROM users WHERE email = ?";
$stmt = $conn_login->prepare($user_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$school_id = $user['school_id'] ?? 1;
$teacher_username = $user['username'] ?? $email;

echo "<h3>Debug Information:</h3>";
echo "Email: " . $email . "<br>";
echo "School ID: " . $school_id . "<br>";
echo "Teacher Username: " . $teacher_username . "<br><br>";

// Check teacher_schedules table
echo "<h3>Teacher Schedules (Active):</h3>";
$query = "SELECT * FROM teacher_schedules WHERE teacher_username = ? AND school_id = ? AND status = 'active'";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("si", $teacher_username, $school_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Subject</th><th>Section</th><th>Day</th><th>Time</th><th>Status</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['subject'] . "</td>";
    echo "<td>" . $row['section'] . "</td>";
    echo "<td>" . $row['day_of_week'] . "</td>";
    echo "<td>" . $row['start_time'] . " - " . $row['end_time'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// Check for inactive schedules too
echo "<h3>Teacher Schedules (Inactive):</h3>";
$query = "SELECT * FROM teacher_schedules WHERE teacher_username = ? AND school_id = ? AND status = 'inactive'";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("si", $teacher_username, $school_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Subject</th><th>Section</th><th>Day</th><th>Time</th><th>Status</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['subject'] . "</td>";
    echo "<td>" . $row['section'] . "</td>";
    echo "<td>" . $row['day_of_week'] . "</td>";
    echo "<td>" . $row['start_time'] . " - " . $row['end_time'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
