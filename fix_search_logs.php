<?php
session_start();
require_once 'conn/db_connect.php';

// Check if user is logged in with admin privileges
if (!isset($_SESSION['email'])) {
    die("You must be logged in to view this page.");
}

// Find all search logs and update them
$updateQuery = "UPDATE activity_logs 
                SET action_type = 'settings_search' 
                WHERE action_description LIKE 'Searched for:%' 
                AND (action_type IS NULL OR action_type = '' OR action_type LIKE 'search:%')";

$result = $conn_qr->query($updateQuery);

if ($result) {
    $updatedRows = $conn_qr->affected_rows;
    echo "<h1>Search Logs Update</h1>";
    echo "<p>Successfully updated $updatedRows search logs with the correct action_type.</p>";
    echo "<p>All search logs now have 'settings_search' as their action_type.</p>";
    echo "<p><a href='test_search_logs.php'>View Updated Search Logs</a></p>";
} else {
    echo "<h1>Error</h1>";
    echo "<p>Failed to update search logs: " . $conn_qr->error . "</p>";
}
?> 