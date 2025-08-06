<?php
include("conn/conn.php");

echo "<h1>Fix Course and Section Tables</h1>";

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    echo "<h3>Checking table structures</h3>";
    
    // Check if tables exist
    $tables_exist = true;
    $check_courses = $conn->query("SHOW TABLES LIKE 'tbl_courses'");
    $check_sections = $conn->query("SHOW TABLES LIKE 'tbl_sections'");
    
    if ($check_courses->rowCount() == 0) {
        echo "Table tbl_courses does not exist.<br>";
        $tables_exist = false;
    }
    
    if ($check_sections->rowCount() == 0) {
        echo "Table tbl_sections does not exist.<br>";
        $tables_exist = false;
    }
    
    if (!$tables_exist) {
        echo "Tables do not exist. Please run setup-course-section-tables.php first.<br>";
    } else {
        // Check for the unique constraint structure and fix if needed
        echo "Tables exist. Checking structure...<br>";
        
        // Get the current table structures
        $describe_courses = $conn->query("DESCRIBE tbl_courses");
        $describe_sections = $conn->query("DESCRIBE tbl_sections");
        
        // Get the indices
        $indices_courses = $conn->query("SHOW INDEX FROM tbl_courses");
        $indices_sections = $conn->query("SHOW INDEX FROM tbl_sections");
        
        // Check for unique constraint on course_name column
        $has_unique_course_name = false;
        $has_composite_unique_course = false;
        
        while ($index = $indices_courses->fetch(PDO::FETCH_ASSOC)) {
            if ($index['Column_name'] == 'course_name' && $index['Non_unique'] == 0) {
                $has_unique_course_name = true;
                echo "Found UNIQUE constraint on course_name<br>";
            }
            
            if ($index['Key_name'] == 'unique_course_user_school') {
                $has_composite_unique_course = true;
                echo "Found composite UNIQUE constraint on course_name, user_id, school_id<br>";
            }
        }
        
        // Check for unique constraint on section_name column
        $has_unique_section_name = false;
        $has_composite_unique_section = false;
        
        while ($index = $indices_sections->fetch(PDO::FETCH_ASSOC)) {
            if ($index['Column_name'] == 'section_name' && $index['Non_unique'] == 0) {
                $has_unique_section_name = true;
                echo "Found UNIQUE constraint on section_name<br>";
            }
            
            if ($index['Key_name'] == 'unique_section_user_school') {
                $has_composite_unique_section = true;
                echo "Found composite UNIQUE constraint on section_name, user_id, school_id<br>";
            }
        }
        
        // Fix the tables if needed
        $changes_made = false;
        
        // Fix courses table
        if ($has_unique_course_name && !$has_composite_unique_course) {
            echo "Dropping UNIQUE constraint on course_name and adding composite UNIQUE constraint...<br>";
            
            // Get the actual index name for the course_name column
            $index_name_query = "SHOW INDEX FROM tbl_courses WHERE Column_name = 'course_name' AND Non_unique = 0";
            $index_result = $conn->query($index_name_query);
            
            if ($index_row = $index_result->fetch(PDO::FETCH_ASSOC)) {
                $index_name = $index_row['Key_name'];
                echo "Found index name: $index_name<br>";
                
                try {
                    $conn->exec("ALTER TABLE tbl_courses DROP INDEX `$index_name`");
                    echo "Successfully dropped index $index_name<br>";
                } catch (PDOException $e) {
                    echo "Error dropping index: " . $e->getMessage() . "<br>";
                    // Continue anyway and try to add the new index
                }
            } else {
                echo "Could not find the exact index name for course_name column<br>";
            }
            
            try {
                $conn->exec("ALTER TABLE tbl_courses ADD CONSTRAINT unique_course_user_school UNIQUE (course_name, user_id, school_id)");
                echo "Fixed tbl_courses structure with new composite index<br>";
                $changes_made = true;
            } catch (PDOException $e) {
                echo "Error adding new composite index: " . $e->getMessage() . "<br>";
            }
        } else if (!$has_composite_unique_course) {
            echo "Adding composite UNIQUE constraint to tbl_courses...<br>";
            try {
                $conn->exec("ALTER TABLE tbl_courses ADD CONSTRAINT unique_course_user_school UNIQUE (course_name, user_id, school_id)");
                echo "Added composite UNIQUE constraint to tbl_courses.<br>";
                $changes_made = true;
            } catch (PDOException $e) {
                echo "Error adding composite index: " . $e->getMessage() . "<br>";
            }
        }
        
        // Fix sections table
        if ($has_unique_section_name && !$has_composite_unique_section) {
            echo "Dropping UNIQUE constraint on section_name and adding composite UNIQUE constraint...<br>";
            
            // Get the actual index name for the section_name column
            $index_name_query = "SHOW INDEX FROM tbl_sections WHERE Column_name = 'section_name' AND Non_unique = 0";
            $index_result = $conn->query($index_name_query);
            
            if ($index_row = $index_result->fetch(PDO::FETCH_ASSOC)) {
                $index_name = $index_row['Key_name'];
                echo "Found index name: $index_name<br>";
                
                try {
                    $conn->exec("ALTER TABLE tbl_sections DROP INDEX `$index_name`");
                    echo "Successfully dropped index $index_name<br>";
                } catch (PDOException $e) {
                    echo "Error dropping index: " . $e->getMessage() . "<br>";
                    // Continue anyway and try to add the new index
                }
            } else {
                echo "Could not find the exact index name for section_name column<br>";
            }
            
            try {
                $conn->exec("ALTER TABLE tbl_sections ADD CONSTRAINT unique_section_user_school UNIQUE (section_name, user_id, school_id)");
                echo "Fixed tbl_sections structure with new composite index<br>";
                $changes_made = true;
            } catch (PDOException $e) {
                echo "Error adding new composite index: " . $e->getMessage() . "<br>";
            }
        } else if (!$has_composite_unique_section) {
            echo "Adding composite UNIQUE constraint to tbl_sections...<br>";
            try {
                $conn->exec("ALTER TABLE tbl_sections ADD CONSTRAINT unique_section_user_school UNIQUE (section_name, user_id, school_id)");
                echo "Added composite UNIQUE constraint to tbl_sections.<br>";
                $changes_made = true;
            } catch (PDOException $e) {
                echo "Error adding composite index: " . $e->getMessage() . "<br>";
            }
        }
        
        if ($changes_made) {
            echo "<strong>Table structures have been fixed successfully!</strong><br>";
        } else {
            echo "<strong>No changes needed to table structures.</strong><br>";
        }
    }
    
    echo "<div style='margin-top: 30px;'>";
    echo "<a href='masterlist.php' class='btn btn-primary'>Return to Masterlist</a> ";
    echo "<a href='rebuild_course_section_tables.php' class='btn btn-warning'>Rebuild Tables (Preserve Data)</a> ";
    echo "<a href='setup-course-section-tables.php' class='btn btn-danger'>Recreate Tables from Scratch</a>";
    echo "</div>";
    
    // Add bootstrap if not already included
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css'>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    
    echo "<div style='margin-top: 30px;'>";
    echo "<a href='masterlist.php' class='btn btn-primary'>Return to Masterlist</a> ";
    echo "<a href='rebuild_course_section_tables.php' class='btn btn-warning'>Rebuild Tables (Preserve Data)</a> ";
    echo "<a href='setup-course-section-tables.php' class='btn btn-danger'>Recreate Tables from Scratch</a>";
    echo "</div>";
}
?>
