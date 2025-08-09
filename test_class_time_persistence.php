<?php
/**
 * Test script to verify class time persistence across page refreshes
 */

// Start session
session_start();

// Set basic session variables for testing
$_SESSION['email'] = 'test@example.com';
$_SESSION['user_id'] = 1;
$_SESSION['school_id'] = 1;

echo "<h2>Class Time Persistence Test</h2>\n";

// Include database connection
require_once('conn/db_connect.php');

echo "<h3>1. Current Session State:</h3>\n";
echo "<pre>\n";
echo "class_start_time: " . (isset($_SESSION['class_start_time']) ? $_SESSION['class_start_time'] : 'NOT SET') . "\n";
echo "class_start_time_formatted: " . (isset($_SESSION['class_start_time_formatted']) ? $_SESSION['class_start_time_formatted'] : 'NOT SET') . "\n";
echo "school_id: " . (isset($_SESSION['school_id']) ? $_SESSION['school_id'] : 'NOT SET') . "\n";
echo "</pre>\n";

echo "<h3>2. Database State:</h3>\n";

// Check what's in the database
try {
    if (isset($conn_qr)) {
        $query = "SELECT start_time, status, updated_at FROM class_time_settings WHERE school_id = ? ORDER BY updated_at DESC LIMIT 1";
        $stmt = $conn_qr->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['school_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                echo "<pre>\n";
                echo "Database Record Found:\n";
                echo "start_time: " . ($row['start_time'] ?? 'NULL') . "\n";
                echo "status: " . ($row['status'] ?? 'NULL') . "\n";
                echo "updated_at: " . ($row['updated_at'] ?? 'NULL') . "\n";
                echo "</pre>\n";
                
                // Check if this would be considered active
                $is_active = (!empty($row['start_time']) && isset($row['status']) && $row['status'] === 'active');
                echo "<p><strong>Is Active:</strong> " . ($is_active ? 'YES' : 'NO') . "</p>\n";
                
                if ($is_active) {
                    echo "<div style='color: green;'><strong>✓ GOOD:</strong> Active class time found in database</div>\n";
                } else {
                    echo "<div style='color: orange;'><strong>⚠ WARNING:</strong> Class time exists but is not active</div>\n";
                }
            } else {
                echo "<p><strong>No records found in class_time_settings table for school_id " . $_SESSION['school_id'] . "</strong></p>\n";
                echo "<div style='color: orange;'><strong>⚠ INFO:</strong> No database records found</div>\n";
            }
            $stmt->close();
        }
    } else {
        echo "<p><strong>Database connection not available</strong></p>\n";
    }
} catch (Exception $e) {
    echo "<p><strong>Database error:</strong> " . $e->getMessage() . "</p>\n";
}

echo "<h3>3. Testing API Endpoints:</h3>\n";

// Test the get API
echo "<h4>Testing get-class-time.php:</h4>\n";
$api_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/get-class-time.php';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n"
    ]
]);

$api_response = file_get_contents($api_url, false, $context);
if ($api_response !== false) {
    $api_data = json_decode($api_response, true);
    echo "<pre>\n";
    echo "GET API Response:\n";
    echo json_encode($api_data, JSON_PRETTY_PRINT) . "\n";
    echo "</pre>\n";
    
    if ($api_data['success'] && isset($api_data['data']) && !empty($api_data['data'])) {
        echo "<div style='color: green;'><strong>✓ GOOD:</strong> GET API returns active class time data</div>\n";
    } else {
        echo "<div style='color: orange;'><strong>⚠ INFO:</strong> GET API returns no active class time (this is normal if no time is set)</div>\n";
    }
} else {
    echo "<p><strong>Failed to call GET API</strong></p>\n";
}

echo "<h3>4. Test Instructions:</h3>\n";
echo "<div class='instructions'>\n";
echo "<p><strong>To test class time persistence:</strong></p>\n";
echo "<ol>\n";
echo "<li>Open the main index.php page</li>\n";
echo "<li>Set a class time (e.g., 9:00 AM)</li>\n";
echo "<li>Verify that the time appears in the UI</li>\n";
echo "<li>Refresh this test page to see if the time was saved to database</li>\n";
echo "<li>Refresh the main index.php page to see if the time persists</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<h3>5. Expected Behavior After Setting Time:</h3>\n";
echo "<ul>\n";
echo "<li><strong>Session:</strong> Should contain class_start_time</li>\n";
echo "<li><strong>Database:</strong> Should have record with status='active'</li>\n";
echo "<li><strong>GET API:</strong> Should return success with class time data</li>\n";
echo "<li><strong>After Refresh:</strong> Time should be restored in UI</li>\n";
echo "</ul>\n";

echo "<h3>6. Troubleshooting:</h3>\n";
echo "<div style='background: #f8f9fa; padding: 10px; border-left: 4px solid #007bff;'>\n";
echo "<p><strong>If class time doesn't persist after refresh:</strong></p>\n";
echo "<ul>\n";
echo "<li>Check if the time was saved to database (status should be 'active')</li>\n";
echo "<li>Check if GET API returns the time data</li>\n";
echo "<li>Check browser console for JavaScript errors during page load</li>\n";
echo "<li>Verify that loadClassTimeFromDatabase() function is being called</li>\n";
echo "</ul>\n";
echo "</div>\n";

// Add a form to manually test setting time
echo "<h3>7. Manual Test Form:</h3>\n";
echo "<form method='post' action='api/set-class-time.php' target='_blank'>\n";
echo "<div style='margin: 10px 0;'>\n";
echo "<label>Test Time: <input type='time' name='classStartTime' value='09:00' required></label>\n";
echo "<button type='submit' style='margin-left: 10px;'>Set Test Time</button>\n";
echo "</div>\n";
echo "</form>\n";
echo "<p><em>This will open the API response in a new tab, then refresh this page to see if it was saved.</em></p>\n";

echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
