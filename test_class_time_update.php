<?php
session_start();
require_once('conn/db_connect.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Class Time Update Test</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>🧪 Class Time Update Test</h2>
        
        <div class='row'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Current Session Data</h5>
                    </div>
                    <div class='card-body'>";

// Display current session data
echo "<h6>Session Variables:</h6>";
echo "<ul>";
echo "<li><strong>class_start_time:</strong> " . ($_SESSION['class_start_time'] ?? 'Not set') . "</li>";
echo "<li><strong>class_start_time_formatted:</strong> " . ($_SESSION['class_start_time_formatted'] ?? 'Not set') . "</li>";
echo "<li><strong>school_id:</strong> " . ($_SESSION['school_id'] ?? 'Not set') . "</li>";
echo "<li><strong>user_id:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</li>";
echo "</ul>";

// Check database
echo "<h6>Database Data:</h6>";
try {
    if (isset($_SESSION['school_id']) && isset($conn_qr)) {
        $query = "SELECT start_time, updated_at FROM class_time_settings WHERE school_id = ? ORDER BY updated_at DESC LIMIT 1";
        $stmt = $conn_qr->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['school_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                echo "<ul>";
                echo "<li><strong>Database start_time:</strong> " . $row['start_time'] . "</li>";
                echo "<li><strong>Database updated_at:</strong> " . $row['updated_at'] . "</li>";
                echo "</ul>";
            } else {
                echo "<p style='color: orange;'>⚠️ No class time settings found in database</p>";
            }
            $stmt->close();
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "</div></div>
            </div>
            
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Test Class Time Update</h5>
                    </div>
                    <div class='card-body'>
                        <form id='testForm'>
                            <div class='form-group mb-3'>
                                <label for='testTime'>New Class Time:</label>
                                <input type='time' class='form-control' id='testTime' name='testTime' value='09:00' required>
                            </div>
                            <button type='submit' class='btn btn-primary'>Update Class Time</button>
                        </form>
                        
                        <div id='result' class='mt-3'></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class='row mt-4'>
            <div class='col-12'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Actions</h5>
                    </div>
                    <div class='card-body'>
                        <a href='index.php' class='btn btn-success me-2'>
                            <i class='fas fa-home'></i> Go to Main Page
                        </a>
                        <button onclick='location.reload()' class='btn btn-info me-2'>
                            <i class='fas fa-refresh'></i> Refresh Test
                        </button>
                        <button onclick='clearClassTime()' class='btn btn-warning'>
                            <i class='fas fa-trash'></i> Clear Class Time
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const timeInput = document.getElementById('testTime');
            const resultDiv = document.getElementById('result');
            
            // Show loading
            resultDiv.innerHTML = '<div class=\"alert alert-info\">Updating class time...</div>';
            
            // Make API call
            fetch('api/set-class-time.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'classStartTime=' + encodeURIComponent(timeInput.value)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class=\"alert alert-success\">✅ Class time updated successfully!<br>New time: ' + (data.data.formatted_time || timeInput.value) + '</div>';
                    // Reload after 2 seconds to show updated data
                    setTimeout(() => location.reload(), 2000);
                } else {
                    resultDiv.innerHTML = '<div class=\"alert alert-danger\">❌ Failed to update class time: ' + (data.message || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class=\"alert alert-danger\">❌ Error: ' + error.message + '</div>';
            });
        });
        
        function clearClassTime() {
            if (confirm('Are you sure you want to clear the class time?')) {
                fetch('api/terminate-class-session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Class time cleared successfully!');
                        location.reload();
                    } else {
                        alert('Failed to clear class time: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error clearing class time: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>";
?> 