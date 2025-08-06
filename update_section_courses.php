<?php
// This script populates the course_id field in tbl_sections for existing records
include("./conn/conn.php");

echo "<h2>Course-Section Relationship Update Utility</h2>";

try {
    // Check if the course_id column exists in tbl_sections
    $check_column = "SELECT COUNT(*) AS column_exists 
                     FROM information_schema.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'tbl_sections' 
                     AND COLUMN_NAME = 'course_id'";
    $stmt = $conn->prepare($check_column);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['column_exists'] == 0) {
        echo "<p>The course_id column does not exist in tbl_sections table.</p>";
        echo "<p>Running the table creation script to add this column...</p>";
        
        // Add the course_id column
        include('./db_setup/update_section_course_relation.php');
    } else {
        echo "<p>The course_id column already exists in tbl_sections table.</p>";
    }
    
    // Count sections without a course_id
    $count_null = "SELECT COUNT(*) AS null_count FROM tbl_sections WHERE course_id IS NULL";
    $null_stmt = $conn->prepare($count_null);
    $null_stmt->execute();
    $null_count = $null_stmt->fetch()['null_count'];
    
    echo "<p>Found {$null_count} sections without a course assignment.</p>";
    
    if ($null_count > 0) {
        // Get all courses
        $courses_query = "SELECT course_id, course_name FROM tbl_courses ORDER BY course_name";
        $courses_stmt = $conn->query($courses_query);
        $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($courses) == 0) {
            echo "<p>Error: No courses found in the database.</p>";
            exit;
        }
        
        // Get all sections without a course_id
        $sections_query = "SELECT section_id, section_name FROM tbl_sections WHERE course_id IS NULL";
        $sections_stmt = $conn->query($sections_query);
        $sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Assigning sections to courses:</p>";
        echo "<ul>";
        
        // For each section without a course, assign it to a course in round-robin fashion
        foreach ($sections as $index => $section) {
            $course_index = $index % count($courses);
            $course = $courses[$course_index];
            
            // Update the section with a course_id
            $update = "UPDATE tbl_sections SET course_id = :course_id WHERE section_id = :section_id";
            $update_stmt = $conn->prepare($update);
            $update_stmt->bindParam(':course_id', $course['course_id']);
            $update_stmt->bindParam(':section_id', $section['section_id']);
            $update_stmt->execute();
            
            echo "<li>Assigned section '{$section['section_name']}' to course '{$course['course_name']}'</li>";
        }
        
        echo "</ul>";
        echo "<p>All sections have been assigned to courses.</p>";
    } else {
        echo "<p>All sections already have course assignments. No updates needed.</p>";
    }
    
    echo "<p>Update completed successfully!</p>";
    echo "<p><a href='masterlist.php'>Return to Masterlist</a></p>";
    
} catch(PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
