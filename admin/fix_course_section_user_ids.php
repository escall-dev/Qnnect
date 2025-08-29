<?php
/**
 * Migration Script: Fix Course and Section user_id values
 * 
 * This script updates existing courses and sections to use the correct user_id
 * (school admin's user_id instead of super admin's user_id)
 * 
 * Run this once through your web browser: http://localhost/Qnnect/admin/fix_course_section_user_ids.php
 */

require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once '../conn/db_connect.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is super admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    die('<h2>Access denied - super admin login required</h2><p><a href="super_admin_login.php">Login as Super Admin</a></p>');
}

// Add some basic HTML styling
echo "<html><head><title>Course & Section Migration</title><style>body{font-family:Arial,sans-serif;margin:40px;} h2{color:#098744;} h3{color:#333;} ul{background:#f8f9fa;padding:10px;border-left:4px solid #098744;}</style></head><body>";

$conn_qr = $GLOBALS['conn_qr'] ?? null;
$conn_login = $GLOBALS['conn_login'] ?? null;

if (!$conn_qr || !$conn_login) {
    die('Database connections not available');
}

echo "<h2>Fixing Course and Section user_id values</h2>\n";

// Get all schools and their admin users
$schools_query = "SELECT s.id as school_id, s.name as school_name, u.id as admin_user_id, u.username 
                  FROM schools s 
                  LEFT JOIN users u ON s.id = u.school_id AND u.role = 'admin' 
                  WHERE s.status = 'active' AND u.id IS NOT NULL
                  ORDER BY s.name";

$stmt = mysqli_prepare($conn_login, $schools_query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$schools_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $schools_data[] = $row;
}
mysqli_stmt_close($stmt);

echo "<p>Found " . count($schools_data) . " schools with admin users:</p>\n";

foreach ($schools_data as $school) {
    echo "<h3>Processing {$school['school_name']} (ID: {$school['school_id']}, Admin: {$school['username']})</h3>\n";
    
    // Fix courses for this school
    $courses_updated = 0;
    $courses_query = "UPDATE tbl_courses 
                      SET user_id = ? 
                      WHERE school_id = ? AND user_id != ? AND user_id != 1";
    $stmt_courses = mysqli_prepare($conn_qr, $courses_query);
    mysqli_stmt_bind_param($stmt_courses, 'iii', $school['admin_user_id'], $school['school_id'], $school['admin_user_id']);
    if (mysqli_stmt_execute($stmt_courses)) {
        $courses_updated = mysqli_stmt_affected_rows($stmt_courses);
    }
    mysqli_stmt_close($stmt_courses);
    
    // Fix sections for this school
    $sections_updated = 0;
    $sections_query = "UPDATE tbl_sections 
                       SET user_id = ? 
                       WHERE school_id = ? AND user_id != ? AND user_id != 1";
    $stmt_sections = mysqli_prepare($conn_qr, $sections_query);
    mysqli_stmt_bind_param($stmt_sections, 'iii', $school['admin_user_id'], $school['school_id'], $school['admin_user_id']);
    if (mysqli_stmt_execute($stmt_sections)) {
        $sections_updated = mysqli_stmt_affected_rows($stmt_sections);
    }
    mysqli_stmt_close($stmt_sections);
    
    // Fix students for this school
    $students_updated = 0;
    $students_query = "UPDATE tbl_student 
                       SET user_id = ? 
                       WHERE school_id = ? AND user_id != ? AND user_id != 1";
    $stmt_students = mysqli_prepare($conn_qr, $students_query);
    mysqli_stmt_bind_param($stmt_students, 'iii', $school['admin_user_id'], $school['school_id'], $school['admin_user_id']);
    if (mysqli_stmt_execute($stmt_students)) {
        $students_updated = mysqli_stmt_affected_rows($stmt_students);
    }
    mysqli_stmt_close($stmt_students);
    
    echo "<ul>\n";
    echo "<li>Courses updated: {$courses_updated}</li>\n";
    echo "<li>Sections updated: {$sections_updated}</li>\n";
    echo "<li>Students updated: {$students_updated}</li>\n";
    echo "</ul>\n";
}

echo "<h3>Migration Complete!</h3>\n";
echo "<p>All courses, sections, and students have been updated to use the correct user_id values.</p>\n";
echo "<p><strong>The courses and sections should now appear correctly in masterlist.php!</strong></p>\n";
echo "<p><em>You can now delete this file (fix_course_section_user_ids.php) as it's no longer needed.</em></p>\n";
echo "<p><a href='admin_panel.php' class='btn' style='background:#098744;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Return to Admin Panel</a></p>\n";
echo "</body></html>";
?>
