<?php
// Test to see raw API output
echo "<h2>Raw API Output Test</h2>";

// Start session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['school_id'] = 1;
$_SESSION['email'] = 'test@example.com';

echo "<p>Session data set</p>";

// Capture the API output
ob_start();
include 'api/get-teacher-course-sections.php';
$raw_output = ob_get_clean();

echo "<h3>Raw API Output:</h3>";
echo "<pre>" . htmlspecialchars($raw_output) . "</pre>";

echo "<h3>Output Length: " . strlen($raw_output) . " characters</h3>";

// Check if it starts with JSON
if (strpos($raw_output, '{') === 0) {
    echo "<p>✅ Output appears to be JSON</p>";
} else {
    echo "<p>❌ Output does not start with JSON</p>";
}

// Try to decode
$json_data = json_decode($raw_output, true);
if ($json_data) {
    echo "<p>✅ JSON decode successful</p>";
    echo "<pre>" . print_r($json_data, true) . "</pre>";
} else {
    echo "<p>❌ JSON decode failed: " . json_last_error_msg() . "</p>";
}

echo "<h3>First 200 characters:</h3>";
echo "<pre>" . htmlspecialchars(substr($raw_output, 0, 200)) . "</pre>";
?> 