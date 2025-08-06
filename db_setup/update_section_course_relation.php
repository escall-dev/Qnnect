<?php
include("../conn/conn.php");

try {
    // Add course_id column to tbl_sections if it doesn't exist
    $check_column = "SELECT COUNT(*) AS column_exists 
                     FROM information_schema.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'tbl_sections' 
                     AND COLUMN_NAME = 'course_id'";
    $stmt = $conn->prepare($check_column);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['column_exists'] == 0) {
        // Column doesn't exist, add it
        $add_column = "ALTER TABLE tbl_sections 
                       ADD COLUMN course_id INT,
                       ADD CONSTRAINT fk_section_course 
                       FOREIGN KEY (course_id) REFERENCES tbl_courses(course_id) 
                       ON DELETE CASCADE";
        $conn->exec($add_column);
        echo "Added course_id column to tbl_sections table.<br>";
        
        // Update existing records to link sections to default courses
        // This is just a basic approach - you might want to customize this logic
        // based on your specific naming conventions or requirements
        
        // Get all sections
        $get_sections = "SELECT * FROM tbl_sections";
        $section_stmt = $conn->query($get_sections);
        $sections = $section_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all courses
        $get_courses = "SELECT * FROM tbl_courses";
        $course_stmt = $conn->query($get_courses);
        $courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // For demo purposes, assign sections to courses in a round-robin fashion
        // In a production environment, you might want a more sophisticated mapping
        foreach ($sections as $index => $section) {
            $course_index = $index % count($courses);
            $course_id = $courses[$course_index]['course_id'];
            
            $update_section = "UPDATE tbl_sections 
                               SET course_id = :course_id 
                               WHERE section_id = :section_id";
            $update_stmt = $conn->prepare($update_section);
            $update_stmt->bindParam(':course_id', $course_id);
            $update_stmt->bindParam(':section_id', $section['section_id']);
            $update_stmt->execute();
        }
        
        echo "Linked existing sections to courses.<br>";
    } else {
        echo "The course_id column already exists in tbl_sections table.<br>";
    }
    
    echo "Database update completed successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
