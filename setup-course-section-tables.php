<?php
include("./conn/conn.php");

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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_section_user_school (section_name, user_id, school_id)
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
        $default_sections = [
            ["301", 1, 1],
            ["302", 1, 1],
            ["401", 1, 1],
            ["402", 1, 1]
        ];
        
        $insert_section = $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id) VALUES (?, ?, ?)");
        foreach ($default_sections as $section) {
            $insert_section->execute($section);
        }
        echo "Default sections added.<br>";
    }
    
    // Parse existing course sections from tbl_student
    echo "Processing existing student records to populate course and section tables...<br>";
    $existing_query = "SELECT DISTINCT course_section, user_id, school_id FROM tbl_student WHERE course_section IS NOT NULL AND course_section != ''";
    $existing_result = $conn->query($existing_query);
    
    $count_added = 0;
    while ($row = $existing_result->fetch(PDO::FETCH_ASSOC)) {
        $course_section = $row['course_section'];
        $user_id = $row['user_id'];
        $school_id = $row['school_id'] ?? 1;
        
        // Parse out course and section
        $parts = explode('-', $course_section);
        
        if (count($parts) === 2) {
            $course_name = trim($parts[0]);
            $section_name = trim($parts[1]);
            
            // Add course if it doesn't exist
            $course_check = $conn->prepare("SELECT COUNT(*) FROM tbl_courses WHERE course_name = ?");
            $course_check->execute([$course_name]);
            if ($course_check->fetchColumn() == 0) {
                $insert_course = $conn->prepare("INSERT IGNORE INTO tbl_courses (course_name, user_id, school_id) VALUES (?, ?, ?)");
                $insert_course->execute([$course_name, $user_id, $school_id]);
                $count_added++;
            }
            
            // Add section if it doesn't exist
            $section_check = $conn->prepare("SELECT COUNT(*) FROM tbl_sections WHERE section_name = ?");
            $section_check->execute([$section_name]);
            if ($section_check->fetchColumn() == 0) {
                $insert_section = $conn->prepare("INSERT IGNORE INTO tbl_sections (section_name, user_id, school_id) VALUES (?, ?, ?)");
                $insert_section->execute([$section_name, $user_id, $school_id]);
                $count_added++;
            }
        }
    }
    
    echo "Added $count_added new entries from existing data.<br>";
    echo "<br><strong>Setup completed successfully!</strong>";
    echo "<br><br><a href='masterlist.php'>Return to Masterlist</a>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
