<?php
// Comprehensive delete test and diagnosis
require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';

echo "<h1>Schedule Delete Diagnosis</h1>";

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo "<p style='color:red;'>NOT LOGGED IN - Please log in first</p>";
    exit;
}

$conn = $conn_qr;
$conn_users = $conn_login;

// Get user information
$email = $_SESSION['email'];
echo "<h2>1. User Session Info:</h2>";
echo "Email: " . $email . "<br>";
echo "All session data: <pre>" . print_r($_SESSION, true) . "</pre>";

// Get user from database
$user_query = "SELECT school_id, role, username, id FROM users WHERE email = ?";
$stmt = $conn_users->prepare($user_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

echo "<h2>2. User Database Info:</h2>";
if ($user) {
    echo "Found user: <pre>" . print_r($user, true) . "</pre>";
} else {
    echo "<p style='color:red;'>USER NOT FOUND IN DATABASE</p>";
    exit;
}

$school_id = $user['school_id'] ?? 1;
$teacher_username = $user['username'] ?? $_SESSION['email'];
$user_id = $user['id'] ?? null;

// Show teacher schedules
echo "<h2>3. Teacher Schedules:</h2>";
$schedule_query = "SELECT * FROM teacher_schedules WHERE teacher_username = ? AND school_id = ? ORDER BY status, id";
$stmt = $conn->prepare($schedule_query);
$stmt->bind_param("si", $teacher_username, $school_id);
$stmt->execute();
$schedules = $stmt->get_result();

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>ID</th><th>Teacher</th><th>Subject</th><th>Section</th><th>Day</th><th>Time</th><th>Room</th><th>User ID</th><th>Status</th><th>Actions</th>";
echo "</tr>";

$schedule_count = 0;
while ($schedule = $schedules->fetch_assoc()) {
    $schedule_count++;
    $row_color = $schedule['status'] === 'active' ? '#e8f5e8' : '#ffe8e8';
    echo "<tr style='background-color: $row_color;'>";
    echo "<td>" . $schedule['id'] . "</td>";
    echo "<td>" . $schedule['teacher_username'] . "</td>";
    echo "<td>" . $schedule['subject'] . "</td>";
    echo "<td>" . $schedule['section'] . "</td>";
    echo "<td>" . $schedule['day_of_week'] . "</td>";
    echo "<td>" . $schedule['start_time'] . " - " . $schedule['end_time'] . "</td>";
    echo "<td>" . $schedule['room'] . "</td>";
    echo "<td>" . ($schedule['user_id'] ?? 'NULL') . "</td>";
    echo "<td>" . $schedule['status'] . "</td>";
    echo "<td>";
    if ($schedule['status'] === 'active') {
        echo "<button onclick='testDelete(" . $schedule['id'] . ")'>Test Delete</button>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

if ($schedule_count === 0) {
    echo "<p style='color: orange;'>NO SCHEDULES FOUND for teacher_username='$teacher_username' and school_id='$school_id'</p>";
}

// Check for any schedules with this user_id
if ($user_id) {
    echo "<h2>4. Schedules by User ID ($user_id):</h2>";
    $user_schedule_query = "SELECT * FROM teacher_schedules WHERE user_id = ? ORDER BY status, id";
    $stmt = $conn->prepare($user_schedule_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_schedules = $stmt->get_result();
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Teacher</th><th>Subject</th><th>Section</th><th>Day</th><th>Time</th><th>Room</th><th>User ID</th><th>Status</th>";
    echo "</tr>";
    
    $user_schedule_count = 0;
    while ($schedule = $user_schedules->fetch_assoc()) {
        $user_schedule_count++;
        $row_color = $schedule['status'] === 'active' ? '#e8f5e8' : '#ffe8e8';
        echo "<tr style='background-color: $row_color;'>";
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
    
    if ($user_schedule_count === 0) {
        echo "<p style='color: orange;'>NO SCHEDULES FOUND for user_id='$user_id'</p>";
    }
}

echo "<div id='testResult'></div>";
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function testDelete(scheduleId) {
    console.log('Testing delete for schedule ID:', scheduleId);
    
    $.ajax({
        url: 'api/delete-teacher-schedule.php',
        type: 'POST',
        data: JSON.stringify({id: parseInt(scheduleId)}),
        contentType: 'application/json',
        success: function(response) {
            console.log('Raw response:', response);
            
            try {
                var data = JSON.parse(response);
                console.log('Parsed response:', data);
                
                var resultDiv = $('#testResult');
                var color = data.success ? 'green' : 'red';
                resultDiv.html('<div style="color: ' + color + '; font-weight: bold; margin: 10px 0;">Result: ' + data.message + '</div>');
                
                if (data.success) {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                $('#testResult').html('<div style="color: red;">Failed to parse response: ' + response + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Status:', status);
            console.error('Response Text:', xhr.responseText);
            $('#testResult').html('<div style="color: red;">AJAX Error: ' + error + '</div>');
        }
    });
}
</script>
