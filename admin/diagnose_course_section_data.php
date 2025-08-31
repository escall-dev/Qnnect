<?php
/**
 * Diagnostic Script: Check Course-Section Data Relationships
 * 
 * This script will examine the current course and section data to identify
 * why the super admin panel is not displaying the hierarchical structure correctly.
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
echo "<html><head><title>Course & Section Data Diagnosis</title><style>body{font-family:Arial,sans-serif;margin:40px;} h2{color:#098744;} h3{color:#333;} table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#098744;color:white;} .problem{background-color:#ffebee;} .ok{background-color:#e8f5e8;}</style></head><body>";

$conn_qr = $GLOBALS['conn_qr'] ?? null;
$conn_login = $GLOBALS['conn_login'] ?? null;

if (!$conn_qr || !$conn_login) {
    die('Database connections not available');
}

echo "<h2>Course & Section Data Diagnosis</h2>";

// Check for Computer Site Inc. (school_id = 2)
$school_id = 2;

echo "<h3>1. Current Courses for Computer Site Inc. (school_id = $school_id)</h3>";

$courses_query = "SELECT course_id, course_name, user_id, school_id FROM tbl_courses WHERE school_id = ? ORDER BY course_name";
$stmt_courses = mysqli_prepare($conn_qr, $courses_query);
mysqli_stmt_bind_param($stmt_courses, 'i', $school_id);
mysqli_stmt_execute($stmt_courses);
$courses_result = mysqli_stmt_get_result($stmt_courses);

echo "<table>";
echo "<tr><th>Course ID</th><th>Course Name</th><th>User ID</th><th>School ID</th></tr>";

$courses = [];
while ($course = mysqli_fetch_assoc($courses_result)) {
    $courses[] = $course;
    echo "<tr class='ok'>";
    echo "<td>{$course['course_id']}</td>";
    echo "<td>{$course['course_name']}</td>";
    echo "<td>{$course['user_id']}</td>";
    echo "<td>{$course['school_id']}</td>";
    echo "</tr>";
}
echo "</table>";

mysqli_stmt_close($stmt_courses);

echo "<p><strong>Found " . count($courses) . " courses</strong></p>";

echo "<h3>2. Current Sections for Computer Site Inc. (school_id = $school_id)</h3>";

$sections_query = "SELECT section_id, section_name, course_id, user_id, school_id FROM tbl_sections WHERE school_id = ? ORDER BY section_name";
$stmt_sections = mysqli_prepare($conn_qr, $sections_query);
mysqli_stmt_bind_param($stmt_sections, 'i', $school_id);
mysqli_stmt_execute($stmt_sections);
$sections_result = mysqli_stmt_get_result($stmt_sections);

echo "<table>";
echo "<tr><th>Section ID</th><th>Section Name</th><th>Course ID</th><th>User ID</th><th>School ID</th><th>Status</th></tr>";

$sections = [];
$orphaned_sections = 0;
while ($section = mysqli_fetch_assoc($sections_result)) {
    $sections[] = $section;
    $is_orphaned = is_null($section['course_id']) || $section['course_id'] == 0;
    if ($is_orphaned) $orphaned_sections++;
    
    $class = $is_orphaned ? 'problem' : 'ok';
    $status = $is_orphaned ? 'ORPHANED (No Course ID)' : 'OK';
    
    echo "<tr class='$class'>";
    echo "<td>{$section['section_id']}</td>";
    echo "<td>{$section['section_name']}</td>";
    echo "<td>" . ($section['course_id'] ?: 'NULL') . "</td>";
    echo "<td>{$section['user_id']}</td>";
    echo "<td>{$section['school_id']}</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

mysqli_stmt_close($stmt_sections);

echo "<p><strong>Found " . count($sections) . " sections, $orphaned_sections are orphaned (no course_id)</strong></p>";

echo "<h3>3. Student Data Analysis</h3>";

$students_query = "SELECT tbl_student_id, student_name, course_section FROM tbl_student WHERE school_id = ? ORDER BY student_name";
$stmt_students = mysqli_prepare($conn_qr, $students_query);
mysqli_stmt_bind_param($stmt_students, 'i', $school_id);
mysqli_stmt_execute($stmt_students);
$students_result = mysqli_stmt_get_result($stmt_students);

echo "<table>";
echo "<tr><th>Student ID</th><th>Student Name</th><th>Course & Section</th><th>Parsed Course</th><th>Parsed Section</th></tr>";

$course_section_combinations = [];
while ($student = mysqli_fetch_assoc($students_result)) {
    // Parse course-section combination
    $course_section = $student['course_section'];
    $parts = explode(' - ', $course_section);
    $parsed_course = count($parts) >= 1 ? trim($parts[0]) : '';
    $parsed_section = count($parts) >= 2 ? trim($parts[1]) : '';
    
    if (!isset($course_section_combinations[$parsed_course])) {
        $course_section_combinations[$parsed_course] = [];
    }
    if (!in_array($parsed_section, $course_section_combinations[$parsed_course])) {
        $course_section_combinations[$parsed_course][] = $parsed_section;
    }
    
    echo "<tr>";
    echo "<td>{$student['tbl_student_id']}</td>";
    echo "<td>{$student['student_name']}</td>";
    echo "<td>$course_section</td>";
    echo "<td>$parsed_course</td>";
    echo "<td>$parsed_section</td>";
    echo "</tr>";
}
echo "</table>";

mysqli_stmt_close($stmt_students);

echo "<h3>4. Expected Course-Section Structure Based on Students</h3>";

echo "<table>";
echo "<tr><th>Course</th><th>Sections</th></tr>";
foreach ($course_section_combinations as $course => $sections_list) {
    echo "<tr>";
    echo "<td><strong>$course</strong></td>";
    echo "<td>" . implode(', ', $sections_list) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>5. Recommendations</h3>";

if ($orphaned_sections > 0) {
    echo "<div class='problem' style='padding:15px;margin:20px 0;border-radius:5px;'>";
    echo "<h4>❌ CRITICAL ISSUE FOUND</h4>";
    echo "<p>$orphaned_sections sections have no course_id. This is why the super admin panel cannot group sections under courses.</p>";
    echo "<p><strong>Solution:</strong> Run the data repair script to link sections to their correct courses based on student data.</p>";
    echo "</div>";
} else {
    echo "<div class='ok' style='padding:15px;margin:20px 0;border-radius:5px;'>";
    echo "<h4>✅ Course-Section relationships look OK</h4>";
    echo "<p>All sections have proper course_id values. The issue might be elsewhere in the display logic.</p>";
    echo "</div>";
}

echo "<p><a href='admin_panel.php' style='background:#098744;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>← Back to Admin Panel</a></p>";
echo "</body></html>";
?>




