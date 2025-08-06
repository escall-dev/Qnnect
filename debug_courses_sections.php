<?php
include("conn/conn.php");
include("includes/session_config.php");

echo "<h2>Debugging Course and Section Tables</h2>";

// Check if the tables exist
$check_courses_query = "SHOW TABLES LIKE 'tbl_courses'";
$courses_exists = $conn->query($check_courses_query)->rowCount() > 0;

$check_sections_query = "SHOW TABLES LIKE 'tbl_sections'";
$sections_exists = $conn->query($check_sections_query)->rowCount() > 0;

echo "<p>Table tbl_courses exists: " . ($courses_exists ? 'Yes' : 'No') . "</p>";
echo "<p>Table tbl_sections exists: " . ($sections_exists ? 'Yes' : 'No') . "</p>";

if ($courses_exists) {
    // Display all courses
    echo "<h3>All Courses</h3>";
    $courses_query = "SELECT * FROM tbl_courses";
    $courses_result = $conn->query($courses_query);
    
    if ($courses_result->rowCount() > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Course Name</th><th>User ID</th><th>School ID</th><th>Created At</th></tr>";
        
        while ($row = $courses_result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['course_id'] . "</td>";
            echo "<td>" . $row['course_name'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['school_id'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No courses found.</p>";
    }
}

if ($sections_exists) {
    // Display all sections
    echo "<h3>All Sections</h3>";
    $sections_query = "SELECT * FROM tbl_sections";
    $sections_result = $conn->query($sections_query);
    
    if ($sections_result->rowCount() > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Section Name</th><th>User ID</th><th>School ID</th><th>Created At</th></tr>";
        
        while ($row = $sections_result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['section_id'] . "</td>";
            echo "<td>" . $row['section_name'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['school_id'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No sections found.</p>";
    }
}

// Display table structures
echo "<h3>Table Structures</h3>";

if ($courses_exists) {
    echo "<h4>tbl_courses Structure</h4>";
    $courses_structure = $conn->query("DESCRIBE tbl_courses");
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $courses_structure->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

if ($sections_exists) {
    echo "<h4>tbl_sections Structure</h4>";
    $sections_structure = $conn->query("DESCRIBE tbl_sections");
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $sections_structure->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Test adding a custom course and section
echo "<h3>Test Adding Custom Values</h3>";

try {
    $user_id = $_SESSION['user_id'] ?? 1;
    $school_id = $_SESSION['school_id'] ?? 1;
    
    $test_course = "TEST-COURSE-" . time();
    $test_section = "TEST-SECTION-" . time();
    
    $insert_course = $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (?, ?, ?)");
    $result_course = $insert_course->execute([$test_course, $user_id, $school_id]);
    
    $insert_section = $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id) VALUES (?, ?, ?)");
    $result_section = $insert_section->execute([$test_section, $user_id, $school_id]);
    
    echo "<p>Test course added: " . ($result_course ? 'Success' : 'Failed') . "</p>";
    echo "<p>Test section added: " . ($result_section ? 'Success' : 'Failed') . "</p>";
    
    // Verify they were added
    $verify_course = $conn->prepare("SELECT * FROM tbl_courses WHERE course_name = ?");
    $verify_course->execute([$test_course]);
    
    $verify_section = $conn->prepare("SELECT * FROM tbl_sections WHERE section_name = ?");
    $verify_section->execute([$test_section]);
    
    echo "<p>Test course found: " . ($verify_course->rowCount() > 0 ? 'Yes' : 'No') . "</p>";
    echo "<p>Test section found: " . ($verify_section->rowCount() > 0 ? 'Yes' : 'No') . "</p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
