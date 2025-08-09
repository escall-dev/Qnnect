<?php
// Simple test to debug the API endpoint
echo "<h2>Testing API Endpoint</h2>";

// Simulate session data
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['school_id'] = 1;
$_SESSION['email'] = 'test@example.com';

// Include the API file
ob_start();
include 'api/get-teacher-course-sections.php';
$output = ob_get_clean();

echo "<h3>API Output:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Try to decode as JSON
$json_data = json_decode($output, true);
if ($json_data) {
    echo "<h3>JSON Decoded Successfully:</h3>";
    echo "<pre>" . print_r($json_data, true) . "</pre>";
} else {
    echo "<h3>JSON Decode Failed:</h3>";
    echo "<p>Error: " . json_last_error_msg() . "</p>";
}
?> 