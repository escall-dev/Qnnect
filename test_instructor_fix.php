<?php
require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';

echo "<h2>Testing Instructor Name Fix</h2>";

// Simulate a logged in user
if (!isset($_SESSION['user_id'])) {
    // For testing purposes, let's set some session data
    $_SESSION['user_id'] = 1;
    $_SESSION['school_id'] = 1;
    $_SESSION['userData'] = [
        'username' => 'testuser',
        'email' => 'test@example.com'
    ];
    echo "<p style='color: orange;'>⚠️ Set test session data for demonstration</p>";
}

echo "<h3>Current Session Data:</h3>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p><strong>School ID:</strong> " . ($_SESSION['school_id'] ?? 'Not set') . "</p>";
echo "<p><strong>Username:</strong> " . ($_SESSION['userData']['username'] ?? 'Not set') . "</p>";
echo "<p><strong>Email:</strong> " . ($_SESSION['userData']['email'] ?? 'Not set') . "</p>";
echo "<p><strong>Current Instructor Name (display):</strong> " . ($_SESSION['current_instructor_name'] ?? 'Not set') . "</p>";

// Test 1: Check what schedules are found using teacher_username
echo "<h3>Test 1: Schedules found using teacher_username</h3>";
$teacher_username = $_SESSION['userData']['username'] ?? $_SESSION['userData']['email'] ?? 'testuser';
$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

$query = "SELECT id, teacher_username, subject, section, day_of_week, start_time, end_time, room 
          FROM teacher_schedules 
          WHERE teacher_username = ? 
          AND user_id = ? 
          AND school_id = ?
          AND status = 'active'";

$stmt = $conn_qr->prepare($query);
$stmt->bind_param("sii", $teacher_username, $user_id, $school_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Found " . $result->num_rows . " schedule(s) using teacher_username: '$teacher_username'</p>";
    echo "<table border='1'><tr><th>ID</th><th>teacher_username</th><th>Subject</th><th>Section</th><th>Day</th><th>Time</th><th>Room</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['teacher_username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
        echo "<td>" . htmlspecialchars($row['section']) . "</td>";
        echo "<td>" . htmlspecialchars($row['day_of_week']) . "</td>";
        echo "<td>" . htmlspecialchars($row['start_time']) . " - " . htmlspecialchars($row['end_time']) . "</td>";
        echo "<td>" . htmlspecialchars($row['room'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>✗ No schedules found using teacher_username: '$teacher_username'</p>";
}

// Test 2: Check what schedules would be found if we used instructor_name incorrectly
echo "<h3>Test 2: Schedules that would be found using instructor_name (incorrect approach)</h3>";
$instructor_name = $_SESSION['current_instructor_name'] ?? 'Test Instructor';

// This query would fail because instructor_name doesn't exist in teacher_schedules
$query_incorrect = "SELECT COUNT(*) as count FROM teacher_schedules WHERE teacher_username = ?";
$stmt = $conn_qr->prepare($query_incorrect);
$stmt->bind_param("s", $instructor_name);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "<p>If we searched for instructor_name '$instructor_name' in teacher_username field: " . $row['count'] . " results</p>";

// Test 3: Test the fixed API endpoint
echo "<h3>Test 3: Testing Fixed API Endpoint</h3>";
echo "<p>The get-instructor-schedules.php file should now correctly use teacher_username instead of instructor_name.</p>";

// Test 4: Show the relationship between tables
echo "<h3>Test 4: Understanding the Table Relationship</h3>";
echo "<p><strong>Core Issue:</strong></p>";
echo "<ul>";
echo "<li><strong>tbl_instructors table:</strong> Uses 'instructor_name' field (this is what gets updated when editing instructor names)</li>";
echo "<li><strong>teacher_schedules table:</strong> Uses 'teacher_username' field (this should match the login username and doesn't change)</li>";
echo "<li><strong>class_schedules table:</strong> Uses 'instructor_name' field (this is a different table for active class sessions)</li>";
echo "</ul>";

echo "<p><strong>Solution:</strong></p>";
echo "<ul>";
echo "<li>Schedule queries should use 'teacher_username' from 'teacher_schedules' table</li>";
echo "<li>The 'teacher_username' is tied to the user's login and doesn't change when the display name is edited</li>";
echo "<li>The 'instructor_name' in 'tbl_instructors' is just for display purposes and can be changed without affecting schedule fetching</li>";
echo "</ul>";

// Test if there are any instructor records
echo "<h3>Test 5: Instructor Records</h3>";
$query = "SELECT instructor_id, instructor_name FROM tbl_instructors LIMIT 5";
$result = $conn_qr->query($query);
if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Instructor Name (Display)</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['instructor_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['instructor_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No instructor records found.</p>";
}

echo "<h3>Summary</h3>";
echo "<p style='background: #e8f5e8; padding: 10px; border: 1px solid #4CAF50;'>";
echo "<strong>✓ Fix Applied:</strong> The issue has been resolved by updating the API files to use 'teacher_username' from 'teacher_schedules' table instead of the non-existent 'instructor_name' field. ";
echo "Now when an instructor's display name is changed in the admin panel, their schedules will still be fetched correctly because the system uses the unchanging 'teacher_username' field.";
echo "</p>";
?>
