<?php
// fix_teacher_schedules.php
// Script to fix teacher schedules that were incorrectly set to inactive during logout

require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';

echo "<h1>Teacher Schedule Status Fix</h1>";

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo "<p style='color:red;'>Please log in first</p>";
    exit;
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

if (!$user) {
    echo "<p style='color:red;'>User not found</p>";
    exit;
}

$school_id = $user['school_id'] ?? 1;
$teacher_username = $user['username'] ?? $_SESSION['email'];
$user_id = $user['id'] ?? null;

echo "<h2>Current Status:</h2>";
echo "Email: " . $email . "<br>";
echo "Username: " . $teacher_username . "<br>";
echo "School ID: " . $school_id . "<br>";
echo "User ID: " . $user_id . "<br><br>";

// Check current schedule statuses
echo "<h2>Current Schedule Statuses:</h2>";
$status_query = "SELECT id, subject, section, day_of_week, start_time, end_time, room, status, created_at 
                 FROM teacher_schedules 
                 WHERE (teacher_username = ? AND school_id = ?) 
                 OR (user_id = ? AND user_id IS NOT NULL)
                 ORDER BY status, day_of_week, start_time";

$stmt = $conn->prepare($status_query);
$stmt->bind_param("sii", $teacher_username, $school_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<p style='color:orange;'>No schedules found for this teacher</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Subject</th><th>Section</th><th>Day</th><th>Time</th><th>Room</th><th>Status</th><th>Created</th>";
    echo "</tr>";
    
    $active_count = 0;
    $inactive_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $status_color = $row['status'] === 'active' ? 'green' : 'red';
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['subject'] . "</td>";
        echo "<td>" . $row['section'] . "</td>";
        echo "<td>" . $row['day_of_week'] . "</td>";
        echo "<td>" . $row['start_time'] . " - " . $row['end_time'] . "</td>";
        echo "<td>" . $row['room'] . "</td>";
        echo "<td style='color: $status_color; font-weight: bold;'>" . $row['status'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
        
        if ($row['status'] === 'active') {
            $active_count++;
        } else {
            $inactive_count++;
        }
    }
    echo "</table>";
    
    echo "<br><p><strong>Summary:</strong> $active_count active, $inactive_count inactive schedules</p>";
}

// Check if there are inactive schedules that should be reactivated
if ($inactive_count > 0) {
    echo "<h2>Fix Inactive Schedules:</h2>";
    echo "<p>Found $inactive_count inactive schedules. These should be reactivated since teacher schedules are permanent templates.</p>";
    
    if (isset($_POST['fix_schedules'])) {
        // Reactivate all inactive schedules for this teacher
        $fix_query = "UPDATE teacher_schedules 
                      SET status = 'active', updated_at = NOW() 
                      WHERE ((teacher_username = ? AND school_id = ?) 
                      OR (user_id = ? AND user_id IS NOT NULL))
                      AND status = 'inactive'";
        
        $stmt = $conn->prepare($fix_query);
        $stmt->bind_param("sii", $teacher_username, $school_id, $user_id);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            echo "<p style='color:green;'>Successfully reactivated $affected schedules!</p>";
            echo "<p><a href='fix_teacher_schedules.php'>Refresh to see updated status</a></p>";
        } else {
            echo "<p style='color:red;'>Error reactivating schedules: " . $conn->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<form method='post'>";
        echo "<input type='submit' name='fix_schedules' value='Reactivate All Inactive Schedules' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "</form>";
    }
}

echo "<br><hr><br>";
echo "<h2>What This Fix Does:</h2>";
echo "<p>This script identifies and fixes teacher schedules that were incorrectly set to 'inactive' status during logout.</p>";
echo "<p><strong>Why this happened:</strong> The logout process was incorrectly treating teacher schedules as 'class sessions' that needed to be terminated.</p>";
echo "<p><strong>Why it's wrong:</strong> Teacher schedules are permanent schedule templates that should persist across sessions and only be marked inactive when actually deleted or deactivated by the user.</p>";
echo "<p><strong>What was fixed:</strong> Removed the incorrect teacher schedule inactivation from logout.php, admin/logout.php, and api/terminate-class-session.php</p>";

echo "<br><p><a href='teacher-schedule.php'>← Back to Teacher Schedule</a></p>";
?>
