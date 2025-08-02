<?php
// Cleanup script to delete all inactive teacher schedules
require_once 'includes/session_config.php';
require_once 'conn/db_connect.php';

echo "<h1>Teacher Schedule Cleanup - Delete Inactive Records</h1>";

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['email'])) {
    echo "<p style='color:red;'>Please log in first</p>";
    exit;
}

$conn = $conn_qr;

// First, show current inactive records
echo "<h2>Current Inactive Records:</h2>";
$inactive_query = "SELECT * FROM teacher_schedules WHERE status = 'inactive' ORDER BY updated_at DESC";
$result = $conn->query($inactive_query);

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Teacher</th><th>Subject</th><th>Section</th><th>Day</th><th>Time</th><th>Room</th><th>Status</th><th>Updated</th>";
    echo "</tr>";
    
    $inactive_count = 0;
    while ($schedule = $result->fetch_assoc()) {
        $inactive_count++;
        echo "<tr style='background-color: #ffe8e8;'>";
        echo "<td>" . $schedule['id'] . "</td>";
        echo "<td>" . $schedule['teacher_username'] . "</td>";
        echo "<td>" . $schedule['subject'] . "</td>";
        echo "<td>" . $schedule['section'] . "</td>";
        echo "<td>" . $schedule['day_of_week'] . "</td>";
        echo "<td>" . $schedule['start_time'] . " - " . $schedule['end_time'] . "</td>";
        echo "<td>" . $schedule['room'] . "</td>";
        echo "<td>" . $schedule['status'] . "</td>";
        echo "<td>" . $schedule['updated_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Total inactive records: $inactive_count</strong></p>";
    
    // Add cleanup button
    echo "<br>";
    echo "<button onclick='cleanupInactive()' style='background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>
            Delete All Inactive Records Permanently
          </button>";
    echo "<div id='cleanupResult'></div>";
    
} else {
    echo "<p style='color: green;'>No inactive records found. Database is clean!</p>";
}

echo "<br><br>";
echo "<h2>Current Active Records:</h2>";
$active_query = "SELECT COUNT(*) as active_count FROM teacher_schedules WHERE status = 'active'";
$active_result = $conn->query($active_query);
$active_data = $active_result->fetch_assoc();
echo "<p><strong>Total active records: " . $active_data['active_count'] . "</strong></p>";
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function cleanupInactive() {
    if (!confirm('Are you sure you want to permanently delete ALL inactive schedule records? This action cannot be undone!')) {
        return;
    }
    
    if (!confirm('This will PERMANENTLY DELETE all inactive records from the database. Are you absolutely sure?')) {
        return;
    }
    
    $.ajax({
        url: 'api/cleanup-inactive-schedules.php',
        type: 'POST',
        data: JSON.stringify({action: 'cleanup_inactive'}),
        contentType: 'application/json',
        success: function(response) {
            console.log('Cleanup response:', response);
            
            try {
                var data = JSON.parse(response);
                var color = data.success ? 'green' : 'red';
                $('#cleanupResult').html('<div style="color: ' + color + '; font-weight: bold; margin: 10px 0;">Result: ' + data.message + '</div>');
                
                if (data.success) {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            } catch (e) {
                console.error('Failed to parse response:', e);
                $('#cleanupResult').html('<div style="color: red;">Error: ' + response + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Cleanup error:', error);
            $('#cleanupResult').html('<div style="color: red;">Error: ' + error + '</div>');
        }
    });
}
</script>
