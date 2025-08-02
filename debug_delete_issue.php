<?php
require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    die('Please log in first');
}

$conn = $conn_qr;
$conn_users = $conn_login;

// Get user's information
$email = $_SESSION['email'];
$user_query = "SELECT school_id, role, username, id FROM users WHERE email = ?";
$stmt = $conn_users->prepare($user_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

echo "<h2>User Information:</h2>";
echo "Email: " . $email . "<br>";
echo "User ID: " . ($user['id'] ?? 'NULL') . "<br>";
echo "Username: " . ($user['username'] ?? 'NULL') . "<br>";
echo "School ID: " . ($user['school_id'] ?? 'NULL') . "<br>";
echo "Role: " . ($user['role'] ?? 'NULL') . "<br><br>";

// Show current schedules for this user
echo "<h2>Teacher Schedules for this user:</h2>";
$schedule_query = "SELECT * FROM teacher_schedules WHERE teacher_username = ? AND school_id = ? ORDER BY status, id";
$stmt = $conn->prepare($schedule_query);
$stmt->bind_param("si", $user['username'], $user['school_id']);
$stmt->execute();
$schedules = $stmt->get_result();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Teacher</th><th>Subject</th><th>Section</th><th>Day</th><th>Time</th><th>Room</th><th>User ID</th><th>Status</th></tr>";
while ($schedule = $schedules->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $schedule['id'] . "</td>";
    echo "<td>" . $schedule['teacher_username'] . "</td>";
    echo "<td>" . $schedule['subject'] . "</td>";
    echo "<td>" . $schedule['section'] . "</td>";
    echo "<td>" . $schedule['day_of_week'] . "</td>";
    echo "<td>" . $schedule['start_time'] . " - " . $schedule['end_time'] . "</td>";
    echo "<td>" . $schedule['room'] . "</td>";
    echo "<td>" . ($schedule['user_id'] ?? 'NULL') . "</td>";
    echo "<td>" . $schedule['status'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// Show schedules with matching user_id
if ($user['id']) {
    echo "<h2>Schedules with matching user_id:</h2>";
    $user_schedule_query = "SELECT * FROM teacher_schedules WHERE user_id = ? ORDER BY status, id";
    $stmt = $conn->prepare($user_schedule_query);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $user_schedules = $stmt->get_result();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Teacher</th><th>Subject</th><th>Section</th><th>Day</th><th>Time</th><th>Room</th><th>User ID</th><th>Status</th></tr>";
    while ($schedule = $user_schedules->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $schedule['id'] . "</td>";
        echo "<td>" . $schedule['teacher_username'] . "</td>";
        echo "<td>" . $schedule['subject'] . "</td>";
        echo "<td>" . $schedule['section'] . "</td>";
        echo "<td>" . $schedule['day_of_week'] . "</td>";
        echo "<td>" . $schedule['start_time'] . " - " . $schedule['end_time'] . "</td>";
        echo "<td>" . $schedule['room'] . "</td>";
        echo "<td>" . ($schedule['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $schedule['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
