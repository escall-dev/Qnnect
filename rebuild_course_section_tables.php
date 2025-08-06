<?php
include("conn/conn.php");

echo "<h1>Rebuild Course and Section Tables</h1>";

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add bootstrap
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css'>";
echo "<div class='container'>";

try {
    echo "<h3>Starting database rebuild process</h3>";
    
    // Check if tables exist
    $courses_exists = $conn->query("SHOW TABLES LIKE 'tbl_courses'")->rowCount() > 0;
    $sections_exists = $conn->query("SHOW TABLES LIKE 'tbl_sections'")->rowCount() > 0;
    
    echo "<p>tbl_courses exists: " . ($courses_exists ? 'Yes' : 'No') . "</p>";
    echo "<p>tbl_sections exists: " . ($sections_exists ? 'Yes' : 'No') . "</p>";
    
    // Step 1: Backup existing data if tables exist
    $courses_data = [];
    $sections_data = [];
    
    if ($courses_exists) {
        echo "<h4>Backing up existing courses data</h4>";
        $courses_query = $conn->query("SELECT * FROM tbl_courses");
        $courses_data = $courses_query->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Backed up " . count($courses_data) . " courses</p>";
    }
    
    if ($sections_exists) {
        echo "<h4>Backing up existing sections data</h4>";
        $sections_query = $conn->query("SELECT * FROM tbl_sections");
        $sections_data = $sections_query->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Backed up " . count($sections_data) . " sections</p>";
    }
    
    // Step 2: Drop existing tables
    if ($courses_exists) {
        echo "<h4>Dropping existing courses table</h4>";
        $conn->exec("DROP TABLE tbl_courses");
        echo "<p>tbl_courses dropped successfully</p>";
    }
    
    if ($sections_exists) {
        echo "<h4>Dropping existing sections table</h4>";
        $conn->exec("DROP TABLE tbl_sections");
        echo "<p>tbl_sections dropped successfully</p>";
    }
    
    // Step 3: Recreate tables with proper structure
    echo "<h4>Creating new tables with proper structure</h4>";
    
    $create_courses_table = "CREATE TABLE tbl_courses (
        course_id INT AUTO_INCREMENT PRIMARY KEY,
        course_name VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        school_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_course_user_school (course_name, user_id, school_id)
    )";
    
    $conn->exec($create_courses_table);
    echo "<p>tbl_courses created successfully</p>";
    
    $create_sections_table = "CREATE TABLE tbl_sections (
        section_id INT AUTO_INCREMENT PRIMARY KEY,
        section_name VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        school_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_section_user_school (section_name, user_id, school_id)
    )";
    
    $conn->exec($create_sections_table);
    echo "<p>tbl_sections created successfully</p>";
    
    // Step 4: Restore data from backup
    if (count($courses_data) > 0) {
        echo "<h4>Restoring course data</h4>";
        
        $insert_course = $conn->prepare("INSERT IGNORE INTO tbl_courses 
            (course_id, course_name, user_id, school_id, created_at) 
            VALUES (:course_id, :course_name, :user_id, :school_id, :created_at)");
        
        $count = 0;
        foreach ($courses_data as $course) {
            try {
                $insert_course->execute([
                    ':course_id' => $course['course_id'],
                    ':course_name' => $course['course_name'],
                    ':user_id' => $course['user_id'],
                    ':school_id' => $course['school_id'],
                    ':created_at' => $course['created_at']
                ]);
                $count++;
            } catch (PDOException $e) {
                echo "<p class='text-danger'>Error restoring course ID " . $course['course_id'] . ": " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p>Restored $count courses successfully</p>";
    }
    
    if (count($sections_data) > 0) {
        echo "<h4>Restoring section data</h4>";
        
        $insert_section = $conn->prepare("INSERT IGNORE INTO tbl_sections 
            (section_id, section_name, user_id, school_id, created_at) 
            VALUES (:section_id, :section_name, :user_id, :school_id, :created_at)");
        
        $count = 0;
        foreach ($sections_data as $section) {
            try {
                $insert_section->execute([
                    ':section_id' => $section['section_id'],
                    ':section_name' => $section['section_name'],
                    ':user_id' => $section['user_id'],
                    ':school_id' => $section['school_id'],
                    ':created_at' => $section['created_at']
                ]);
                $count++;
            } catch (PDOException $e) {
                echo "<p class='text-danger'>Error restoring section ID " . $section['section_id'] . ": " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p>Restored $count sections successfully</p>";
    }
    
    // Step 5: Add default values if tables are empty
    $course_count = $conn->query("SELECT COUNT(*) FROM tbl_courses")->fetchColumn();
    
    if ($course_count == 0) {
        echo "<h4>Adding default courses</h4>";
        
        $default_courses = [
            ["BSIS", 1, 1],
            ["BSIT", 1, 1],
            ["BSCS", 1, 1],
            ["BSCpE", 1, 1]
        ];
        
        $insert_default_course = $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (?, ?, ?)");
        foreach ($default_courses as $course) {
            $insert_default_course->execute($course);
        }
        
        echo "<p>Added " . count($default_courses) . " default courses</p>";
    }
    
    $section_count = $conn->query("SELECT COUNT(*) FROM tbl_sections")->fetchColumn();
    
    if ($section_count == 0) {
        echo "<h4>Adding default sections</h4>";
        
        $default_sections = [
            ["101", 1, 1],
            ["102", 1, 1],
            ["201", 1, 1],
            ["202", 1, 1],
            ["301", 1, 1],
            ["302", 1, 1],
            ["401", 1, 1],
            ["402", 1, 1]
        ];
        
        $insert_default_section = $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id) VALUES (?, ?, ?)");
        foreach ($default_sections as $section) {
            $insert_default_section->execute($section);
        }
        
        echo "<p>Added " . count($default_sections) . " default sections</p>";
    }
    
    // Step 6: Process existing student records to ensure course/section entries exist
    echo "<h4>Processing existing student records</h4>";
    
    $student_query = "SELECT DISTINCT course_section FROM tbl_student WHERE course_section IS NOT NULL AND course_section != ''";
    $student_result = $conn->query($student_query);
    $student_courses = $student_result->fetchAll(PDO::FETCH_COLUMN);
    
    $added_count = 0;
    foreach ($student_courses as $course_section) {
        $parts = explode('-', $course_section);
        
        if (count($parts) === 2) {
            $course_name = trim($parts[0]);
            $section_name = trim($parts[1]);
            
            // Add course if it doesn't exist
            $check_course = $conn->prepare("SELECT COUNT(*) FROM tbl_courses WHERE course_name = ?");
            $check_course->execute([$course_name]);
            
            if ($check_course->fetchColumn() == 0) {
                $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (?, 1, 1)")
                     ->execute([$course_name]);
                $added_count++;
                echo "<p>Added missing course: $course_name</p>";
            }
            
            // Add section if it doesn't exist
            $check_section = $conn->prepare("SELECT COUNT(*) FROM tbl_sections WHERE section_name = ?");
            $check_section->execute([$section_name]);
            
            if ($check_section->fetchColumn() == 0) {
                $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id) VALUES (?, 1, 1)")
                     ->execute([$section_name]);
                $added_count++;
                echo "<p>Added missing section: $section_name</p>";
            }
        }
    }
    
    echo "<p>Added $added_count entries from existing student data</p>";
    
    echo "<div class='alert alert-success'><strong>Success!</strong> Database tables have been rebuilt successfully.</div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . $e->getMessage() . "</div>";
}

// Display navigation links
echo "<div class='mt-4 mb-4'>";
echo "<a href='masterlist.php' class='btn btn-primary mr-2'>Return to Masterlist</a>";
echo "<a href='test_course_section.php' class='btn btn-info'>Test Course/Section Tables</a>";
echo "</div>";

echo "</div>"; // Close container
?>
