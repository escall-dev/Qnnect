<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

echo "<h2>Debug: Why Username Change Fix Not Working</h2>";

// Check current schedules by username
echo "<h3>1. Current Schedules in teacher_schedules:</h3>";
$query = "SELECT teacher_username, subject, section, school_id, user_id, status FROM teacher_schedules ORDER BY teacher_username, subject";
$result = $conn_qr->query($query);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>teacher_username</th><th>Subject</th><th>Section</th><th>School ID</th><th>User ID</th><th>Status</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['teacher_username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
    echo "<td>" . htmlspecialchars($row['section']) . "</td>";
    echo "<td>" . htmlspecialchars($row['school_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check users table
echo "<h3>2. Current Users:</h3>";
$query = "SELECT id, username, email, school_id FROM users ORDER BY username";
$result = $conn_login->query($query);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>School ID</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['school_id']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test specific query that would be used
echo "<h3>3. Test Schedule Query for Different Usernames:</h3>";

// Test for arnold_aranaydo
$test_username = 'arnold_aranaydo';
$query = "SELECT COUNT(*) as count FROM teacher_schedules WHERE teacher_username = ? AND status = 'active'";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("s", $test_username);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];
echo "<p><strong>arnold_aranaydo:</strong> $count schedules found</p>";

// Test for SPCPC
$test_username = 'SPCPC';
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("s", $test_username);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];
echo "<p><strong>SPCPC:</strong> $count schedules found</p>";

// Check if our fix was applied by looking at the controller file
echo "<h3>4. Controller Fix Status:</h3>";
$controller_content = file_get_contents('../admin/controller.php');
if (strpos($controller_content, 'UPDATE teacher_schedules SET teacher_username') !== false) {
    echo "<p style='color: green;'>✓ Cascading update code is present in controller.php</p>";
} else {
    echo "<p style='color: red;'>✗ Cascading update code is NOT found in controller.php</p>";
}

// Check session data if available
echo "<h3>5. Current Session Data:</h3>";
echo "<pre>";
echo "email: " . ($_SESSION['email'] ?? 'not set') . "\n";
echo "user_id: " . ($_SESSION['user_id'] ?? 'not set') . "\n";
echo "school_id: " . ($_SESSION['school_id'] ?? 'not set') . "\n";
echo "userData username: " . ($_SESSION['userData']['username'] ?? 'not set') . "\n";
echo "</pre>";

echo "<h3>6. Hypothesis:</h3>";
echo "<p>The issue might be:</p>";
echo "<ul>";
echo "<li>The cascading update in controller.php isn't being triggered</li>";
echo "<li>The username change is happening through a different mechanism</li>";
echo "<li>There's a caching issue</li>";
echo "<li>The schedules exist but the query conditions don't match</li>";
echo "</ul>";
?>
