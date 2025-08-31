<?php
/**
 * Repair Script: Fix Course-Section Relationships
 * 
 * This script will repair the course-section relationships based on student data
 * to ensure the super admin panel displays the hierarchical structure correctly.
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
echo "<html><head><title>Course & Section Relationship Repair</title><style>body{font-family:Arial,sans-serif;margin:40px;} h2{color:#098744;} h3{color:#333;} .success{background:#e8f5e8;padding:10px;border-radius:5px;margin:10px 0;} .info{background:#e3f2fd;padding:10px;border-radius:5px;margin:10px 0;} .warning{background:#fff3e0;padding:10px;border-radius:5px;margin:10px 0;}</style></head><body>";

$conn_qr = $GLOBALS['conn_qr'] ?? null;
$conn_login = $GLOBALS['conn_login'] ?? null;

if (!$conn_qr || !$conn_login) {
    die('Database connections not available');
}

echo "<h2>Course & Section Relationship Repair</h2>";

// Focus on Computer Site Inc. (school_id = 2)
$school_id = 2;

// Get school admin user_id
$admin_user_id = null;
$admin_query = "SELECT id FROM users WHERE school_id = ? AND role = 'admin' LIMIT 1";
$stmt_admin = mysqli_prepare($conn_login, $admin_query);
mysqli_stmt_bind_param($stmt_admin, 'i', $school_id);
mysqli_stmt_execute($stmt_admin);
$admin_result = mysqli_stmt_get_result($stmt_admin);
if ($admin_row = mysqli_fetch_assoc($admin_result)) {
    $admin_user_id = (int)$admin_row['id'];
}
mysqli_stmt_close($stmt_admin);

if (!$admin_user_id) {
    die("<div class='warning'>Could not find admin user for school_id $school_id</div>");
}

echo "<div class='info'>Using admin user_id: $admin_user_id for school_id: $school_id</div>";

echo "<h3>Step 1: Analyze Student Data to Extract Course-Section Relationships</h3>";

$students_query = "SELECT DISTINCT course_section FROM tbl_student WHERE school_id = ? AND course_section IS NOT NULL AND course_section != ''";
$stmt_students = mysqli_prepare($conn_qr, $students_query);
mysqli_stmt_bind_param($stmt_students, 'i', $school_id);
mysqli_stmt_execute($stmt_students);
$students_result = mysqli_stmt_get_result($stmt_students);

$course_section_map = [];
while ($row = mysqli_fetch_assoc($students_result)) {
    $course_section = trim($row['course_section']);
    $parts = explode(' - ', $course_section);
    
    if (count($parts) >= 2) {
        $course_name = trim($parts[0]);
        $section_name = trim($parts[1]);
        
        if (!isset($course_section_map[$course_name])) {
            $course_section_map[$course_name] = [];
        }
        
        if (!in_array($section_name, $course_section_map[$course_name])) {
            $course_section_map[$course_name][] = $section_name;
        }
    }
}
mysqli_stmt_close($stmt_students);

echo "<div class='info'>Found " . count($course_section_map) . " unique courses with their sections:</div>";
foreach ($course_section_map as $course => $sections) {
    echo "<div>• <strong>$course</strong>: " . implode(', ', $sections) . "</div>";
}

echo "<h3>Step 2: Create Missing Courses</h3>";

$courses_created = 0;
$course_id_map = [];

foreach ($course_section_map as $course_name => $sections) {
    // Check if course exists
    $check_course_query = "SELECT course_id FROM tbl_courses WHERE course_name = ? AND school_id = ?";
    $stmt_check = mysqli_prepare($conn_qr, $check_course_query);
    mysqli_stmt_bind_param($stmt_check, 'si', $course_name, $school_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if ($existing_course = mysqli_fetch_assoc($result_check)) {
        $course_id_map[$course_name] = (int)$existing_course['course_id'];
        echo "<div>Course '$course_name' already exists (ID: {$existing_course['course_id']})</div>";
    } else {
        // Create new course
        $insert_course_query = "INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (?, ?, ?)";
        $stmt_insert = mysqli_prepare($conn_qr, $insert_course_query);
        mysqli_stmt_bind_param($stmt_insert, 'sii', $course_name, $admin_user_id, $school_id);
        
        if (mysqli_stmt_execute($stmt_insert)) {
            $new_course_id = mysqli_insert_id($conn_qr);
            $course_id_map[$course_name] = $new_course_id;
            $courses_created++;
            echo "<div class='success'>✅ Created course '$course_name' (ID: $new_course_id)</div>";
        } else {
            echo "<div class='warning'>❌ Failed to create course '$course_name': " . mysqli_error($conn_qr) . "</div>";
        }
        mysqli_stmt_close($stmt_insert);
    }
    mysqli_stmt_close($stmt_check);
}

echo "<div class='info'>Created $courses_created new courses</div>";

echo "<h3>Step 3: Create Missing Sections and Link to Courses</h3>";

$sections_created = 0;
$sections_updated = 0;

foreach ($course_section_map as $course_name => $sections) {
    $course_id = $course_id_map[$course_name];
    
    foreach ($sections as $section_name) {
        // Check if section exists
        $check_section_query = "SELECT section_id, course_id FROM tbl_sections WHERE section_name = ? AND school_id = ?";
        $stmt_check_section = mysqli_prepare($conn_qr, $check_section_query);
        mysqli_stmt_bind_param($stmt_check_section, 'si', $section_name, $school_id);
        mysqli_stmt_execute($stmt_check_section);
        $result_check_section = mysqli_stmt_get_result($stmt_check_section);
        
        if ($existing_section = mysqli_fetch_assoc($result_check_section)) {
            // Section exists, check if it has correct course_id
            if (is_null($existing_section['course_id']) || $existing_section['course_id'] != $course_id) {
                $update_section_query = "UPDATE tbl_sections SET course_id = ?, user_id = ? WHERE section_id = ?";
                $stmt_update = mysqli_prepare($conn_qr, $update_section_query);
                mysqli_stmt_bind_param($stmt_update, 'iii', $course_id, $admin_user_id, $existing_section['section_id']);
                
                if (mysqli_stmt_execute($stmt_update)) {
                    $sections_updated++;
                    echo "<div class='success'>🔗 Linked section '$section_name' to course '$course_name' (Course ID: $course_id)</div>";
                } else {
                    echo "<div class='warning'>❌ Failed to link section '$section_name': " . mysqli_error($conn_qr) . "</div>";
                }
                mysqli_stmt_close($stmt_update);
            } else {
                echo "<div>Section '$section_name' already properly linked to course '$course_name'</div>";
            }
        } else {
            // Create new section
            $insert_section_query = "INSERT INTO tbl_sections (section_name, course_id, user_id, school_id) VALUES (?, ?, ?, ?)";
            $stmt_insert_section = mysqli_prepare($conn_qr, $insert_section_query);
            mysqli_stmt_bind_param($stmt_insert_section, 'siii', $section_name, $course_id, $admin_user_id, $school_id);
            
            if (mysqli_stmt_execute($stmt_insert_section)) {
                $new_section_id = mysqli_insert_id($conn_qr);
                $sections_created++;
                echo "<div class='success'>✅ Created section '$section_name' linked to course '$course_name' (Section ID: $new_section_id)</div>";
            } else {
                echo "<div class='warning'>❌ Failed to create section '$section_name': " . mysqli_error($conn_qr) . "</div>";
            }
            mysqli_stmt_close($stmt_insert_section);
        }
        mysqli_stmt_close($stmt_check_section);
    }
}

echo "<div class='info'>Created $sections_created new sections and updated $sections_updated existing sections</div>";

echo "<h3>Step 4: Verification</h3>";

// Verify the relationships are now correct
$verify_query = "SELECT c.course_name, s.section_name, s.course_id, c.course_id as expected_course_id
                 FROM tbl_courses c 
                 LEFT JOIN tbl_sections s ON c.course_id = s.course_id 
                 WHERE c.school_id = ? 
                 ORDER BY c.course_name, s.section_name";
$stmt_verify = mysqli_prepare($conn_qr, $verify_query);
mysqli_stmt_bind_param($stmt_verify, 'i', $school_id);
mysqli_stmt_execute($stmt_verify);
$verify_result = mysqli_stmt_get_result($stmt_verify);

echo "<div class='success'>";
echo "<h4>✅ Verification Results:</h4>";
$current_course = '';
while ($row = mysqli_fetch_assoc($verify_result)) {
    if ($current_course != $row['course_name']) {
        if ($current_course != '') echo "<br>";
        echo "<strong>{$row['course_name']}:</strong>";
        $current_course = $row['course_name'];
    }
    if ($row['section_name']) {
        echo " {$row['section_name']},";
    }
}
echo "</div>";

mysqli_stmt_close($stmt_verify);

echo "<h3>✅ Repair Complete!</h3>";
echo "<div class='success'>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>Created $courses_created new courses</li>";
echo "<li>Created $sections_created new sections</li>";  
echo "<li>Updated $sections_updated existing sections with proper course links</li>";
echo "</ul>";
echo "<p><strong>The courses and sections should now display properly in the super admin panel!</strong></p>";
echo "</div>";

echo "<p><a href='admin_panel.php' style='background:#098744;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>← Back to Admin Panel</a> ";
echo "<a href='diagnose_course_section_data.php' style='background:#2196f3;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Run Diagnostics Again</a></p>";
echo "</body></html>";
?>




