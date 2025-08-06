<?php
include("../conn/conn.php");

try {
    // Create tbl_courses table if not exists
    $create_courses_table = "CREATE TABLE IF NOT EXISTS tbl_courses (
        course_id INT AUTO_INCREMENT PRIMARY KEY,
        course_name VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        school_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_course_user_school (course_name, user_id, school_id)
    )";
    
    $conn->exec($create_courses_table);
    echo "Table tbl_courses created successfully or already exists.<br>";
    
    // Create tbl_sections table if not exists
    $create_section_table = "CREATE TABLE IF NOT EXISTS tbl_sections (
        section_id INT AUTO_INCREMENT PRIMARY KEY,
        section_name VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        school_id INT NOT NULL,
        course_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_section_user_school (section_name, user_id, school_id),
        CONSTRAINT fk_section_course FOREIGN KEY (course_id) REFERENCES tbl_courses(course_id) ON DELETE CASCADE
    )";
    
    $conn->exec($create_section_table);
    echo "Table tbl_sections created successfully or already exists.<br>";
    
    // Add default courses if table is empty
    $check_courses = $conn->query("SELECT COUNT(*) FROM tbl_courses");
    if ($check_courses->fetchColumn() == 0) {
        $default_courses = [
            ["BSIS", 1, 1],
            ["BSIT", 1, 1]
        ];
        
        $insert_course = $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (?, ?, ?)");
        foreach ($default_courses as $course) {
            $insert_course->execute($course);
        }
        echo "Default courses added.<br>";
    }
    
    // Add default sections if table is empty
    $check_sections = $conn->query("SELECT COUNT(*) FROM tbl_sections");
    if ($check_sections->fetchColumn() == 0) {
        // Get the course IDs for BSIS and BSIT
        $bsis_course = $conn->prepare("SELECT course_id FROM tbl_courses WHERE course_name = 'BSIS' AND user_id = 1 LIMIT 1");
        $bsis_course->execute();
        $bsis_id = $bsis_course->fetchColumn();
        
        $bsit_course = $conn->prepare("SELECT course_id FROM tbl_courses WHERE course_name = 'BSIT' AND user_id = 1 LIMIT 1");
        $bsit_course->execute();
        $bsit_id = $bsit_course->fetchColumn();
        
        // Assign sections to specific courses
        $default_sections = [
            ["301", 1, 1, $bsis_id], // BSIS sections
            ["302", 1, 1, $bsis_id],
            ["401", 1, 1, $bsit_id], // BSIT sections
            ["402", 1, 1, $bsit_id]
        ];
        
        $insert_section = $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id, course_id) VALUES (?, ?, ?, ?)");
        foreach ($default_sections as $section) {
            $insert_section->execute($section);
        }
        echo "Default sections added with course relationships.<br>";
    }
    
    echo "Setup completed successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
