<?php
include("conn/conn.php");

echo "<h1>Fix Course and Section Tables (With Foreign Key Handling)</h1>";

// Add bootstrap for styling
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css'>";
echo "<div class='container'>";

try {
    // Step 1: Disable foreign key checks
    echo "<h3>Step 1: Disabling foreign key checks</h3>";
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "<div class='alert alert-success'>Foreign key checks disabled</div>";
    
    // Step 2: Backup existing data
    echo "<h3>Step 2: Backing up existing data</h3>";
    
    // Backup courses
    $courses_data = [];
    if ($conn->query("SHOW TABLES LIKE 'tbl_courses'")->rowCount() > 0) {
        $courses_query = $conn->query("SELECT * FROM tbl_courses");
        $courses_data = $courses_query->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Backed up " . count($courses_data) . " courses</p>";
    }
    
    // Backup sections
    $sections_data = [];
    if ($conn->query("SHOW TABLES LIKE 'tbl_sections'")->rowCount() > 0) {
        $sections_query = $conn->query("SELECT * FROM tbl_sections");
        $sections_data = $sections_query->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Backed up " . count($sections_data) . " sections</p>";
    }
    
    // Step 3: Drop existing tables
    echo "<h3>Step 3: Dropping existing tables</h3>";
    $conn->exec("DROP TABLE IF EXISTS tbl_courses");
    $conn->exec("DROP TABLE IF EXISTS tbl_sections");
    echo "<div class='alert alert-success'>Tables dropped successfully</div>";
    
    // Step 4: Recreate tables
    echo "<h3>Step 4: Creating new tables</h3>";
    
    $create_courses = "CREATE TABLE tbl_courses (
        course_id INT AUTO_INCREMENT PRIMARY KEY,
        course_name VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        school_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_course_user_school (course_name, user_id, school_id)
    )";
    
    $create_sections = "CREATE TABLE tbl_sections (
        section_id INT AUTO_INCREMENT PRIMARY KEY,
        section_name VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        school_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_section_user_school (section_name, user_id, school_id)
    )";
    
    $conn->exec($create_courses);
    $conn->exec($create_sections);
    echo "<div class='alert alert-success'>Tables created successfully</div>";
    
    // Step 5: Restore data
    echo "<h3>Step 5: Restoring data</h3>";
    
    // Restore courses
    $course_count = 0;
    foreach ($courses_data as $course) {
        try {
            $stmt = $conn->prepare("INSERT INTO tbl_courses 
                (course_id, course_name, user_id, school_id, created_at) 
                VALUES (:id, :name, :user_id, :school_id, :created_at)");
            
            $stmt->execute([
                ':id' => $course['course_id'],
                ':name' => $course['course_name'],
                ':user_id' => $course['user_id'],
                ':school_id' => $course['school_id'],
                ':created_at' => $course['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            
            $course_count++;
        } catch (PDOException $e) {
            // If the course ID already exists, try without specifying ID
            try {
                $stmt = $conn->prepare("INSERT INTO tbl_courses 
                    (course_name, user_id, school_id, created_at) 
                    VALUES (:name, :user_id, :school_id, :created_at)");
                
                $stmt->execute([
                    ':name' => $course['course_name'],
                    ':user_id' => $course['user_id'],
                    ':school_id' => $course['school_id'],
                    ':created_at' => $course['created_at'] ?? date('Y-m-d H:i:s')
                ]);
                
                $course_count++;
            } catch (PDOException $e2) {
                echo "<p class='text-danger'>Failed to restore course: " . htmlspecialchars($course['course_name']) . "</p>";
            }
        }
    }
    
    echo "<p>Restored $course_count courses</p>";
    
    // Restore sections
    $section_count = 0;
    foreach ($sections_data as $section) {
        try {
            $stmt = $conn->prepare("INSERT INTO tbl_sections 
                (section_id, section_name, user_id, school_id, created_at) 
                VALUES (:id, :name, :user_id, :school_id, :created_at)");
            
            $stmt->execute([
                ':id' => $section['section_id'],
                ':name' => $section['section_name'],
                ':user_id' => $section['user_id'],
                ':school_id' => $section['school_id'],
                ':created_at' => $section['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            
            $section_count++;
        } catch (PDOException $e) {
            // If the section ID already exists, try without specifying ID
            try {
                $stmt = $conn->prepare("INSERT INTO tbl_sections 
                    (section_name, user_id, school_id, created_at) 
                    VALUES (:name, :user_id, :school_id, :created_at)");
                
                $stmt->execute([
                    ':name' => $section['section_name'],
                    ':user_id' => $section['user_id'],
                    ':school_id' => $section['school_id'],
                    ':created_at' => $section['created_at'] ?? date('Y-m-d H:i:s')
                ]);
                
                $section_count++;
            } catch (PDOException $e2) {
                echo "<p class='text-danger'>Failed to restore section: " . htmlspecialchars($section['section_name']) . "</p>";
            }
        }
    }
    
    echo "<p>Restored $section_count sections</p>";
    
    // Step 6: Add default entries if needed
    echo "<h3>Step 6: Adding default entries if needed</h3>";
    
    $total_courses = $conn->query("SELECT COUNT(*) FROM tbl_courses")->fetchColumn();
    if ($total_courses == 0) {
        $default_courses = [
            ["BSIT", 1, 1],
            ["BSIS", 1, 1],
            ["BSCS", 1, 1],
            ["BSCpE", 1, 1]
        ];
        
        $stmt = $conn->prepare("INSERT INTO tbl_courses (course_name, user_id, school_id) VALUES (?, ?, ?)");
        foreach ($default_courses as $course) {
            $stmt->execute($course);
        }
        
        echo "<p>Added default courses</p>";
    }
    
    $total_sections = $conn->query("SELECT COUNT(*) FROM tbl_sections")->fetchColumn();
    if ($total_sections == 0) {
        $default_sections = [
            ["101", 1, 1],
            ["102", 1, 1],
            ["201", 1, 1],
            ["202", 1, 1],
            ["301", 1, 1],
            ["302", 1, 1]
        ];
        
        $stmt = $conn->prepare("INSERT INTO tbl_sections (section_name, user_id, school_id) VALUES (?, ?, ?)");
        foreach ($default_sections as $section) {
            $stmt->execute($section);
        }
        
        echo "<p>Added default sections</p>";
    }
    
    // Step 7: Re-enable foreign key checks
    echo "<h3>Step 7: Re-enabling foreign key checks</h3>";
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<div class='alert alert-success'>Foreign key checks re-enabled</div>";
    
    echo "<div class='alert alert-success mt-3'><strong>Success!</strong> Table rebuild complete.</div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . $e->getMessage() . "</div>";
    
    // Make sure foreign keys are re-enabled even on error
    try {
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "<div class='alert alert-warning'>Foreign key checks re-enabled after error</div>";
    } catch (Exception $e2) {
        // Ignore
    }
}

// Navigation buttons
echo "<div class='mt-4 mb-4'>";
echo "<a href='masterlist.php' class='btn btn-primary mr-2'>Return to Masterlist</a>";
echo "<a href='diagnose_constraints.php' class='btn btn-info mr-2'>Diagnose Issues</a>";
echo "<a href='test_course_section.php' class='btn btn-secondary'>Test Tables</a>";
echo "</div>";

echo "</div>"; // Close container
?>
