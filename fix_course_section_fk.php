<?php
include("conn/conn.php");

// Enable error reporting for debugging
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

echo "<h1>Fix Course/Section Tables with Foreign Key Handling</h1>";
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css'>";
echo "<div class='container'>";

if (isset($_POST["action"]) && $_POST["action"] == "run_fix") {
    try {
        // Step 1: Disable foreign key checks
        echo "<h3>Step 1: Disabling foreign key checks</h3>";
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
        echo "<div class='alert alert-success'>Foreign key checks disabled successfully</div>";
        
        // Step 2: Backup existing data
        echo "<h3>Step 2: Backing up existing data</h3>";
        
        // Backup courses
        $courses_data = [];
        if ($conn->query("SHOW TABLES LIKE 'tbl_courses'")->rowCount() > 0) {
            $courses_query = $conn->query("SELECT * FROM tbl_courses");
            $courses_data = $courses_query->fetchAll(PDO::FETCH_ASSOC);
            echo "<div class='alert alert-info'>Backed up " . count($courses_data) . " courses</div>";
        }
        
        // Backup sections
        $sections_data = [];
        if ($conn->query("SHOW TABLES LIKE 'tbl_sections'")->rowCount() > 0) {
            $sections_query = $conn->query("SELECT * FROM tbl_sections");
            $sections_data = $sections_query->fetchAll(PDO::FETCH_ASSOC);
            echo "<div class='alert alert-info'>Backed up " . count($sections_data) . " sections</div>";
        }
        
        // Step 3: Drop existing tables
        echo "<h3>Step 3: Dropping existing tables</h3>";
        $conn->exec("DROP TABLE IF EXISTS tbl_courses");
        $conn->exec("DROP TABLE IF EXISTS tbl_sections");
        echo "<div class='alert alert-success'>Tables dropped successfully</div>";
        
        // Step 4: Recreate tables
        echo "<h3>Step 4: Recreating tables with proper structure</h3>";
        
        // Create courses table
        $create_courses_table = "CREATE TABLE tbl_courses (
            course_id INT AUTO_INCREMENT PRIMARY KEY,
            course_name VARCHAR(100) NOT NULL,
            user_id INT NOT NULL,
            school_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_course_user_school (course_name, user_id, school_id)
        )";
        $conn->exec($create_courses_table);
        
        // Create sections table
        $create_sections_table = "CREATE TABLE tbl_sections (
            section_id INT AUTO_INCREMENT PRIMARY KEY,
            section_name VARCHAR(100) NOT NULL,
            user_id INT NOT NULL,
            school_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_section_user_school (section_name, user_id, school_id)
        )";
        $conn->exec($create_sections_table);
        
        echo "<div class='alert alert-success'>Tables recreated successfully</div>";
        
        // Step 5: Restore data
        echo "<h3>Step 5: Restoring data</h3>";
        
        // Restore courses
        if (count($courses_data) > 0) {
            $insert_count = 0;
            
            foreach ($courses_data as $course) {
                try {
                    // First try with original ID to maintain relationships
                    $stmt = $conn->prepare("INSERT INTO tbl_courses 
                        (course_id, course_name, user_id, school_id, created_at) 
                        VALUES (:id, :name, :user_id, :school_id, :created_at)");
                    
                    $stmt->execute([
                        ":id" => $course["course_id"],
                        ":name" => $course["course_name"],
                        ":user_id" => $course["user_id"],
                        ":school_id" => $course["school_id"],
                        ":created_at" => $course["created_at"] ?? date("Y-m-d H:i:s")
                    ]);
                    
                    $insert_count++;
                } catch (PDOException $e) {
                    // If that fails (duplicate ID), try without specifying ID
                    try {
                        $stmt = $conn->prepare("INSERT INTO tbl_courses 
                            (course_name, user_id, school_id, created_at) 
                            VALUES (:name, :user_id, :school_id, :created_at)");
                        
                        $stmt->execute([
                            ":name" => $course["course_name"],
                            ":user_id" => $course["user_id"],
                            ":school_id" => $course["school_id"],
                            ":created_at" => $course["created_at"] ?? date("Y-m-d H:i:s")
                        ]);
                        
                        $insert_count++;
                    } catch (PDOException $e2) {
                        echo "<div class='alert alert-warning'>Could not restore course: " . htmlspecialchars($course["course_name"]) . " - " . $e2->getMessage() . "</div>";
                    }
                }
            }
            
            echo "<div class='alert alert-success'>Restored $insert_count courses</div>";
        }
        
        // Restore sections
        if (count($sections_data) > 0) {
            $insert_count = 0;
            
            foreach ($sections_data as $section) {
                try {
                    // First try with original ID to maintain relationships
                    $stmt = $conn->prepare("INSERT INTO tbl_sections 
                        (section_id, section_name, user_id, school_id, created_at) 
                        VALUES (:id, :name, :user_id, :school_id, :created_at)");
                    
                    $stmt->execute([
                        ":id" => $section["section_id"],
                        ":name" => $section["section_name"],
                        ":user_id" => $section["user_id"],
                        ":school_id" => $section["school_id"],
                        ":created_at" => $section["created_at"] ?? date("Y-m-d H:i:s")
                    ]);
                    
                    $insert_count++;
                } catch (PDOException $e) {
                    // If that fails (duplicate ID), try without specifying ID
                    try {
                        $stmt = $conn->prepare("INSERT INTO tbl_sections 
                            (section_name, user_id, school_id, created_at) 
                            VALUES (:name, :user_id, :school_id, :created_at)");
                        
                        $stmt->execute([
                            ":name" => $section["section_name"],
                            ":user_id" => $section["user_id"],
                            ":school_id" => $section["school_id"],
                            ":created_at" => $section["created_at"] ?? date("Y-m-d H:i:s")
                        ]);
                        
                        $insert_count++;
                    } catch (PDOException $e2) {
                        echo "<div class='alert alert-warning'>Could not restore section: " . htmlspecialchars($section["section_name"]) . " - " . $e2->getMessage() . "</div>";
                    }
                }
            }
            
            echo "<div class='alert alert-success'>Restored $insert_count sections</div>";
        }
        
        // Step 6: Add default values if needed
        echo "<h3>Step 6: Adding default values if needed</h3>";
        
        $course_count = $conn->query("SELECT COUNT(*) FROM tbl_courses")->fetchColumn();
        if ($course_count == 0) {
            $default_courses = [
                ["BSIS", 1, 1],
                ["BSIT", 1, 1],
                ["BSCS", 1, 1]
            ];
            
            $stmt = $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (?, ?, ?)");
            foreach ($default_courses as $course) {
                $stmt->execute($course);
            }
            
            echo "<div class='alert alert-info'>Added default courses</div>";
        }
        
        $section_count = $conn->query("SELECT COUNT(*) FROM tbl_sections")->fetchColumn();
        if ($section_count == 0) {
            $default_sections = [
                ["101", 1, 1],
                ["102", 1, 1],
                ["301", 1, 1],
                ["302", 1, 1]
            ];
            
            $stmt = $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id) VALUES (?, ?, ?)");
            foreach ($default_sections as $section) {
                $stmt->execute($section);
            }
            
            echo "<div class='alert alert-info'>Added default sections</div>";
        }
        
        // Step 7: Re-enable foreign key checks
        echo "<h3>Step 7: Re-enabling foreign key checks</h3>";
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "<div class='alert alert-success'>Foreign key checks re-enabled</div>";
        
        echo "<div class='alert alert-success mt-4'><strong>Success!</strong> The table rebuild process is complete.</div>";
        
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'><strong>Error:</strong> " . $e->getMessage() . "</div>";
        
        // Make sure foreign keys are re-enabled even on error
        try {
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo "<div class='alert alert-info'>Foreign key checks have been re-enabled.</div>";
        } catch (Exception $e2) {
            // Just ignore if this fails too
        }
    }
} else {
    echo "<div class='alert alert-info'>Click the button above to run the fix script.</div>";
}

echo "<div class='mt-4 mb-5'>";
echo "<a href='masterlist.php' class='btn btn-primary mr-2'>Return to Masterlist</a>";
echo "<a href='test_course_section.php' class='btn btn-info'>Test Course/Section Tables</a>";
echo "</div>";

echo "</div>";
?>