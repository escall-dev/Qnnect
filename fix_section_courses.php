<?php
include("./conn/conn.php");

// Display header
echo "<h1>Fix Section-Course Relationships</h1>";

try {
    // Get all courses
    $courses_query = "SELECT course_id, course_name FROM tbl_courses ORDER BY course_name";
    $courses_stmt = $conn->query($courses_query);
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all sections
    $sections_query = "SELECT section_id, section_name, course_id FROM tbl_sections ORDER BY section_name";
    $sections_stmt = $conn->query($sections_query);
    $sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($courses) . " courses and " . count($sections) . " sections.</p>";
    
    // Check for sections with NULL course_id
    $null_query = "SELECT COUNT(*) FROM tbl_sections WHERE course_id IS NULL";
    $null_count = $conn->query($null_query)->fetchColumn();
    
    echo "<p>" . $null_count . " sections have NULL course_id values.</p>";
    
    if ($null_count > 0) {
        // Update sections with NULL course_id
        echo "<h2>Updating Sections:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Section ID</th><th>Section Name</th><th>Assigned Course</th></tr>";
        
        foreach ($sections as $section) {
            if ($section['course_id'] === NULL) {
                // For this example, we'll assign section "12" to ICT (27) and "VERSI" to STEM 12 (31)
                // You can modify this logic based on your requirements
                $course_id = NULL;
                
                if ($section['section_name'] === "12") {
                    $course_id = 27; // ICT
                } elseif ($section['section_name'] === "VERSI") {
                    $course_id = 31; // STEM 12
                } else {
                    // Default to the first course if no specific match
                    $course_id = $courses[0]['course_id'];
                }
                
                // Update the section
                $update_query = "UPDATE tbl_sections SET course_id = :course_id WHERE section_id = :section_id";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':course_id', $course_id);
                $update_stmt->bindParam(':section_id', $section['section_id']);
                $update_stmt->execute();
                
                // Find course name for display
                $course_name = "Unknown";
                foreach ($courses as $course) {
                    if ($course['course_id'] == $course_id) {
                        $course_name = $course['course_name'];
                        break;
                    }
                }
                
                echo "<tr><td>" . $section['section_id'] . "</td><td>" . $section['section_name'] . "</td><td>" . $course_name . "</td></tr>";
            }
        }
        
        echo "</table>";
        echo "<p>All sections have been updated with course assignments.</p>";
    } else {
        echo "<p>No updates needed - all sections already have course assignments.</p>";
    }
    
    echo "<p><a href='masterlist.php'>Return to Masterlist</a></p>";
    
} catch(PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
