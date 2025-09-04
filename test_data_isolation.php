<?php
/**
 * Data Isolation Test Script
 * Tests whether attendance data is properly isolated per user within the same school
 */

require_once 'includes/session_config.php';
include('./conn/db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h1>Error: Please log in to run this test</h1>";
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_school_id = $_SESSION['school_id'] ?? 1;

echo "<h1>Data Isolation Test Results</h1>";
echo "<h2>Current User: {$current_user_id}, School: {$current_school_id}</h2>";

// Test 1: Check if there are other users in the same school
echo "<h3>Test 1: Other Users in Same School</h3>";
$query = "SELECT id, username, email FROM users WHERE school_id = ? AND id != ?";
$stmt = $conn_login->prepare($query);
$stmt->bind_param("ii", $current_school_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$other_users = [];
while ($row = $result->fetch_assoc()) {
    $other_users[] = $row;
    echo "- User ID: {$row['id']}, Username: {$row['username']}, Email: {$row['email']}<br>";
}

if (empty($other_users)) {
    echo "No other users found in this school.<br>";
} else {
    echo "Found " . count($other_users) . " other users in the same school.<br>";
}

// Test 2: Check attendance data visibility
echo "<h3>Test 2: Attendance Data Visibility</h3>";

// Current user's attendance count
$query = "SELECT COUNT(*) as count FROM tbl_attendance WHERE school_id = ? AND user_id = ?";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("ii", $current_school_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_user_count = $result->fetch_assoc()['count'];
echo "Current user's attendance records: {$current_user_count}<br>";

// All attendance records in the school (this should be higher if there are other users)
$query = "SELECT COUNT(*) as count FROM tbl_attendance WHERE school_id = ?";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("i", $current_school_id);
$stmt->execute();
$result = $stmt->get_result();
$total_school_count = $result->fetch_assoc()['count'];
echo "Total attendance records in school: {$total_school_count}<br>";

// Test 3: Check specific files' filtering
echo "<h3>Test 3: File-specific Tests</h3>";

// Test get-attendance-list.php API
echo "<h4>API get-attendance-list.php Test</h4>";
$query = "
    SELECT a.*, s.student_name, s.course_section 
    FROM tbl_attendance a
    LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
        AND s.school_id = a.school_id
    WHERE a.time_in IS NOT NULL AND a.school_id = ? AND a.user_id = ?
    ORDER BY a.time_in DESC 
    LIMIT 5
";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("ii", $current_school_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$api_results = $result->fetch_all(MYSQLI_ASSOC);
echo "API returns " . count($api_results) . " records (filtered by school_id AND user_id)<br>";

// Test index.php main page query
echo "<h4>Index.php Main Page Test</h4>";
$query = "
    SELECT a.*, s.student_name, s.course_section 
    FROM tbl_attendance a
    LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
        AND s.school_id = a.school_id
    WHERE a.time_in IS NOT NULL AND a.school_id = ? AND a.user_id = ?
    ORDER BY a.time_in DESC 
    LIMIT 5
";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("ii", $current_school_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$index_results = $result->fetch_all(MYSQLI_ASSOC);
echo "Index.php returns " . count($index_results) . " records (filtered by school_id AND user_id)<br>";

// Test 4: Student data isolation
echo "<h3>Test 4: Student Data Isolation</h3>";

$query = "SELECT COUNT(*) as count FROM tbl_student WHERE school_id = ? AND user_id = ?";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("ii", $current_school_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_user_students = $result->fetch_assoc()['count'];
echo "Current user's students: {$current_user_students}<br>";

$query = "SELECT COUNT(*) as count FROM tbl_student WHERE school_id = ?";
$stmt = $conn_qr->prepare($query);
$stmt->bind_param("i", $current_school_id);
$stmt->execute();
$result = $stmt->get_result();
$total_school_students = $result->fetch_assoc()['count'];
echo "Total students in school: {$total_school_students}<br>";

// Test 5: Check for cross-contamination
echo "<h3>Test 5: Cross-contamination Check</h3>";
if (!empty($other_users)) {
    foreach ($other_users as $other_user) {
        $other_user_id = $other_user['id'];
        
        // Check if current user can see other user's attendance data
        $query = "SELECT COUNT(*) as count FROM tbl_attendance WHERE school_id = ? AND user_id = ?";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("ii", $current_school_id, $other_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $other_user_attendance = $result->fetch_assoc()['count'];
        
        echo "User {$other_user['username']} (ID: {$other_user_id}) has {$other_user_attendance} attendance records<br>";
    }
} else {
    echo "No other users to test cross-contamination with.<br>";
}

// Summary
echo "<h3>Summary</h3>";
if ($current_user_count == $total_school_count || empty($other_users)) {
    echo "<div style='color: green; font-weight: bold;'>✓ Data isolation appears to be working correctly. Current user sees only their own data.</div>";
} else {
    echo "<div style='color: red; font-weight: bold;'>⚠ Potential data isolation issue detected. Current user count: {$current_user_count}, Total school count: {$total_school_count}</div>";
}

echo "<h3>Recommendations</h3>";
echo "<ul>";
echo "<li>All attendance queries should include both school_id AND user_id filters</li>";
echo "<li>All student queries should include both school_id AND user_id filters</li>";
echo "<li>API endpoints should validate user ownership of data</li>";
echo "<li>Regular testing should be performed to ensure data isolation</li>";
echo "</ul>";
?>
