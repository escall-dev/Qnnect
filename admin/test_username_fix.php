<?php
require_once '../includes/session_config.php';
require_once '../conn/db_connect.php';

echo "<h2>Username Change Impact Test</h2>";

// Check if there are any schedules that might be affected by username changes
$query = "SELECT DISTINCT teacher_username, COUNT(*) as schedule_count 
          FROM teacher_schedules 
          WHERE status = 'active' 
          GROUP BY teacher_username 
          ORDER BY teacher_username";

$result = $conn_qr->query($query);

echo "<h3>Current Schedules by Username:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Teacher Username</th><th>Schedule Count</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['teacher_username']) . "</td>";
    echo "<td>" . $row['schedule_count'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Fix Applied:</h3>";
echo "<p>✅ Updated <code>admin/controller.php</code> to automatically update related <code>teacher_schedules</code> records when a username is changed.</p>";

echo "<h3>How the Fix Works:</h3>";
echo "<ol>";
echo "<li>When a user changes their username in the admin panel, the system now:</li>";
echo "<ul>";
echo "<li>Captures the old username before making the update</li>";
echo "<li>Updates the username in the <code>users</code> table</li>";
echo "<li>Automatically updates all matching records in <code>teacher_schedules</code> table</li>";
echo "<li>Updates session variables to reflect the new username</li>";
echo "</ul>";
echo "<li>This ensures that schedules remain linked to the user even after username changes</li>";
echo "</ol>";

echo "<h3>Test This Fix:</h3>";
echo "<p>To test that the fix works:</p>";
echo "<ol>";
echo "<li>Create some schedules for a user</li>";
echo "<li>Change that user's username in the admin panel</li>";
echo "<li>Verify that the schedules still appear in the subject dropdown</li>";
echo "</ol>";

echo "<p style='background: #e8f5e8; padding: 10px; border: 1px solid #4CAF50;'>";
echo "<strong>Root Cause:</strong> The issue was that when usernames were changed in the admin panel, ";
echo "the <code>teacher_schedules</code> table still referenced the old username, causing schedule queries to fail. ";
echo "<br><strong>Solution:</strong> Added cascading updates to maintain referential integrity between tables.";
echo "</p>";
?>
