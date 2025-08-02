<?php
/**
 * Debug script to check session status and variables
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Session Debug Information</h2>";

echo "<h3>Session Status:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session Name: " . session_name() . "</p>";

echo "<h3>Session Variables:</h3>";
if (empty($_SESSION)) {
    echo "<p style='color: red;'>No session variables found!</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Variable</th><th>Value</th></tr>";
    foreach ($_SESSION as $key => $value) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($key) . "</td>";
        echo "<td>" . htmlspecialchars(print_r($value, true)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Required Session Variables Check:</h3>";
$required_vars = ['email', 'username', 'user_id', 'school_id'];
$missing_vars = [];

foreach ($required_vars as $var) {
    if (isset($_SESSION[$var])) {
        echo "<p style='color: green;'>✓ $var: " . htmlspecialchars($_SESSION[$var]) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ $var: NOT SET</p>";
        $missing_vars[] = $var;
    }
}

if (!empty($missing_vars)) {
    echo "<h3>Missing Variables:</h3>";
    echo "<p style='color: orange;'>The following variables are missing: " . implode(', ', $missing_vars) . "</p>";
    echo "<p>This might cause the 'Unauthorized access' error in terminate session.</p>";
}

echo "<h3>Test Terminate Session API:</h3>";
echo "<button onclick='testTerminateSession()'>Test Terminate Session</button>";
echo "<div id='result'></div>";

echo "<h3>Actions:</h3>";
echo "<p><a href='admin/login.php'>Go to Login Page</a></p>";
echo "<p><a href='admin/logout.php'>Logout</a></p>";
echo "<p><a href='index.php'>Go to Dashboard</a></p>";

?>

<script>
function testTerminateSession() {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '<p>Testing terminate session API...</p>';
    
    fetch('api/terminate-class-session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.innerHTML = '<h4>API Response:</h4><pre>' + JSON.stringify(data, null, 2) + '</pre>';
    })
    .catch(error => {
        resultDiv.innerHTML = '<h4>Error:</h4><p style="color: red;">' + error.message + '</p>';
    });
}
</script>
