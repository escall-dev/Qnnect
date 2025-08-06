<?php
include("conn/conn.php");

echo "<h1>Database Constraint Diagnostic</h1>";

// Add bootstrap styles for better presentation
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css'>";
echo "<div class='container'>";

try {
    // Step 1: Check if the tables exist
    $tables = ['tbl_courses', 'tbl_sections'];
    $table_exists = [];
    
    echo "<h3>Table Existence Check</h3>";
    echo "<ul class='list-group'>";
    
    foreach ($tables as $table) {
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = $check->rowCount() > 0;
        $table_exists[$table] = $exists;
        
        echo "<li class='list-group-item " . ($exists ? 'list-group-item-success' : 'list-group-item-danger') . "'>";
        echo "$table: " . ($exists ? 'Exists' : 'Does not exist');
        echo "</li>";
    }
    
    echo "</ul>";
    
    // Step 2: Check for foreign key constraints pointing to these tables
    echo "<h3>Foreign Key Constraints</h3>";
    
    $fk_query = "
        SELECT 
            TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_SCHEMA = DATABASE()
            AND (REFERENCED_TABLE_NAME = 'tbl_courses' OR REFERENCED_TABLE_NAME = 'tbl_sections')
    ";
    
    $fk_result = $conn->query($fk_query);
    $fk_constraints = $fk_result->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fk_constraints) > 0) {
        echo "<div class='alert alert-warning'>Found foreign key constraints referencing these tables!</div>";
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>Table</th><th>Column</th><th>Constraint Name</th><th>References</th><th>Referenced Column</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($fk_constraints as $constraint) {
            echo "<tr>";
            echo "<td>" . $constraint['TABLE_NAME'] . "</td>";
            echo "<td>" . $constraint['COLUMN_NAME'] . "</td>";
            echo "<td>" . $constraint['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>No foreign key constraints found pointing to these tables.</div>";
        
        // Additional checks since the system is reporting FKs but none are visible here
        echo "<h4>Additional constraint checks:</h4>";
        
        // Check with direct SHOW CREATE TABLE
        echo "<div class='card mb-3'>";
        echo "<div class='card-header'>Raw Table Definitions</div>";
        echo "<div class='card-body'>";
        
        $tables_to_check = [
            // Add tables that might have FKs to our tables
            'tbl_student', 'tbl_attendance', 'tbl_schedules', 'qr_attendance_db'
        ];
        
        // Try to find any table with 'attendance' in the name
        $attendance_tables = $conn->query("SHOW TABLES LIKE '%attendance%'")->fetchAll(PDO::FETCH_COLUMN);
        $tables_to_check = array_merge($tables_to_check, $attendance_tables);
        
        foreach ($tables_to_check as $table) {
            try {
                $create_table = $conn->query("SHOW CREATE TABLE $table");
                if ($create_table && $create_table->rowCount() > 0) {
                    $row = $create_table->fetch(PDO::FETCH_ASSOC);
                    $create_sql = isset($row['Create Table']) ? $row['Create Table'] : '';
                    
                    if (strpos($create_sql, 'FOREIGN KEY') !== false && 
                        (strpos($create_sql, 'tbl_courses') !== false || strpos($create_sql, 'tbl_sections') !== false)) {
                        echo "<h5>$table</h5>";
                        echo "<pre>" . htmlspecialchars($create_sql) . "</pre>";
                    }
                }
            } catch (Exception $e) {
                // Table might not exist, just continue
            }
        }
        echo "</div>";
        echo "</div>";
    }
    
    // Step 3: Solution plan
    echo "<h3>Solution Plan</h3>";
    
    echo "<div class='card mb-4'>";
    echo "<div class='card-header bg-primary text-white'>Recommended Actions</div>";
    echo "<div class='card-body'>";
    
    if (count($fk_constraints) > 0) {
        echo "<p>Before you can rebuild or modify these tables, you need to drop the foreign key constraints:</p>";
        echo "<pre class='bg-light p-3'>";
        echo "-- Disable foreign key checks\n";
        echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($fk_constraints as $constraint) {
            echo "ALTER TABLE `" . $constraint['TABLE_NAME'] . "` DROP FOREIGN KEY `" . $constraint['CONSTRAINT_NAME'] . "`;\n";
        }
        
        echo "\n-- After your table rebuilds, you can recreate the constraints\n";
        echo "-- Re-enable foreign key checks\n";
        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        echo "</pre>";
    } else {
        echo "<p>No direct foreign key constraints were found. Try the following:</p>";
        echo "<ol>";
        echo "<li>Temporarily disable foreign key checks before rebuilding tables</li>";
        echo "<li>Run the rebuild script with foreign key checks disabled</li>";
        echo "<li>Re-enable foreign key checks after the tables are rebuilt</li>";
        echo "</ol>";
        
        echo "<p>Here's a script to try:</p>";
        echo "<pre class='bg-light p-3'>";
        echo "&lt;?php\n";
        echo "include(\"conn/conn.php\");\n\n";
        echo "try {\n";
        echo "    // Disable foreign key checks\n";
        echo "    \$conn->exec(\"SET FOREIGN_KEY_CHECKS = 0\");\n";
        echo "    \n";
        echo "    // Drop and recreate tables here\n";
        echo "    \$conn->exec(\"DROP TABLE IF EXISTS tbl_courses\");\n";
        echo "    \$conn->exec(\"DROP TABLE IF EXISTS tbl_sections\");\n";
        echo "    \n";
        echo "    // Create table structure here\n";
        echo "    // ...\n";
        echo "    \n";
        echo "    // Re-enable foreign key checks\n";
        echo "    \$conn->exec(\"SET FOREIGN_KEY_CHECKS = 1\");\n";
        echo "    \n";
        echo "    echo \"Tables rebuilt successfully!\";\n";
        echo "} catch (PDOException \$e) {\n";
        echo "    echo \"Error: \" . \$e->getMessage();\n";
        echo "}\n";
        echo "?>\n";
        echo "</pre>";
    }
    echo "</div>";
    echo "</div>";
    
    // Create the actual fix script
    echo "<h3>Fix Script</h3>";
    
    // Create a button to run the fix
    echo "<form action='fix_course_section_fk.php' method='post'>";
    echo "<input type='hidden' name='action' value='run_fix'>";
    echo "<button type='submit' class='btn btn-success mb-3'>Run Auto-Fix Script</button>";
    echo "</form>";
    
    // Create the fix script
    $fix_script = '<?php
include("conn/conn.php");

// Enable error reporting for debugging
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

echo "<h1>Fix Course/Section Tables with Foreign Key Handling</h1>";
echo "<link rel=\'stylesheet\' href=\'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css\'>";
echo "<div class=\'container\'>";

if (isset($_POST["action"]) && $_POST["action"] == "run_fix") {
    try {
        // Step 1: Disable foreign key checks
        echo "<h3>Step 1: Disabling foreign key checks</h3>";
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
        echo "<div class=\'alert alert-success\'>Foreign key checks disabled successfully</div>";
        
        // Step 2: Backup existing data
        echo "<h3>Step 2: Backing up existing data</h3>";
        
        // Backup courses
        $courses_data = [];
        if ($conn->query("SHOW TABLES LIKE \'tbl_courses\'")->rowCount() > 0) {
            $courses_query = $conn->query("SELECT * FROM tbl_courses");
            $courses_data = $courses_query->fetchAll(PDO::FETCH_ASSOC);
            echo "<div class=\'alert alert-info\'>Backed up " . count($courses_data) . " courses</div>";
        }
        
        // Backup sections
        $sections_data = [];
        if ($conn->query("SHOW TABLES LIKE \'tbl_sections\'")->rowCount() > 0) {
            $sections_query = $conn->query("SELECT * FROM tbl_sections");
            $sections_data = $sections_query->fetchAll(PDO::FETCH_ASSOC);
            echo "<div class=\'alert alert-info\'>Backed up " . count($sections_data) . " sections</div>";
        }
        
        // Step 3: Drop existing tables
        echo "<h3>Step 3: Dropping existing tables</h3>";
        $conn->exec("DROP TABLE IF EXISTS tbl_courses");
        $conn->exec("DROP TABLE IF EXISTS tbl_sections");
        echo "<div class=\'alert alert-success\'>Tables dropped successfully</div>";
        
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
        
        echo "<div class=\'alert alert-success\'>Tables recreated successfully</div>";
        
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
                        echo "<div class=\'alert alert-warning\'>Could not restore course: " . htmlspecialchars($course["course_name"]) . " - " . $e2->getMessage() . "</div>";
                    }
                }
            }
            
            echo "<div class=\'alert alert-success\'>Restored $insert_count courses</div>";
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
                        echo "<div class=\'alert alert-warning\'>Could not restore section: " . htmlspecialchars($section["section_name"]) . " - " . $e2->getMessage() . "</div>";
                    }
                }
            }
            
            echo "<div class=\'alert alert-success\'>Restored $insert_count sections</div>";
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
            
            echo "<div class=\'alert alert-info\'>Added default courses</div>";
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
            
            echo "<div class=\'alert alert-info\'>Added default sections</div>";
        }
        
        // Step 7: Re-enable foreign key checks
        echo "<h3>Step 7: Re-enabling foreign key checks</h3>";
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "<div class=\'alert alert-success\'>Foreign key checks re-enabled</div>";
        
        echo "<div class=\'alert alert-success mt-4\'><strong>Success!</strong> The table rebuild process is complete.</div>";
        
    } catch (PDOException $e) {
        echo "<div class=\'alert alert-danger\'><strong>Error:</strong> " . $e->getMessage() . "</div>";
        
        // Make sure foreign keys are re-enabled even on error
        try {
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo "<div class=\'alert alert-info\'>Foreign key checks have been re-enabled.</div>";
        } catch (Exception $e2) {
            // Just ignore if this fails too
        }
    }
} else {
    echo "<div class=\'alert alert-info\'>Click the button above to run the fix script.</div>";
}

echo "<div class=\'mt-4 mb-5\'>";
echo "<a href=\'masterlist.php\' class=\'btn btn-primary mr-2\'>Return to Masterlist</a>";
echo "<a href=\'test_course_section.php\' class=\'btn btn-info\'>Test Course/Section Tables</a>";
echo "</div>";

echo "</div>";
?>';
    
    file_put_contents("fix_course_section_fk.php", $fix_script);
    echo "<div class='alert alert-success'>Created fix_course_section_fk.php script!</div>";
    
    // Navigation links
    echo "<div class='mt-4 mb-5'>";
    echo "<a href='fix_course_section_fk.php' class='btn btn-primary mr-2'>Run Fix Script</a>";
    echo "<a href='masterlist.php' class='btn btn-secondary mr-2'>Return to Masterlist</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . $e->getMessage() . "</div>";
}

echo "</div>"; // close container
?>
