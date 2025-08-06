<?php
include("./conn/conn.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Section Course Relationships</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1 {
            color: #336699;
        }
        table {
            border-collapse: collapse;
            margin: 20px 0;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Fix Section-Course Relationships</h1>

    <?php
    // Handle form submission
    if (isset($_POST['update'])) {
        try {
            // Update section 12 to ICT
            $stmt1 = $conn->prepare("UPDATE tbl_sections SET course_id = 27 WHERE section_id = 195");
            $stmt1->execute();
            
            // Update VERSI to STEM 12
            $stmt2 = $conn->prepare("UPDATE tbl_sections SET course_id = 31 WHERE section_id = 198");
            $stmt2->execute();
            
            echo '<div class="message success">Sections have been successfully updated with course assignments!</div>';
        } catch(PDOException $e) {
            echo '<div class="message error">Error updating sections: ' . $e->getMessage() . '</div>';
        }
    }
    
    // Display current state
    try {
        // Get all courses
        $courses_query = "SELECT course_id, course_name FROM tbl_courses ORDER BY course_name";
        $courses_stmt = $conn->query($courses_query);
        $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a lookup array for course names
        $course_names = [];
        foreach ($courses as $course) {
            $course_names[$course['course_id']] = $course['course_name'];
        }
        
        // Get all sections with their course info
        $sections_query = "SELECT s.section_id, s.section_name, s.course_id, s.user_id, s.school_id, 
                           c.course_name 
                           FROM tbl_sections s 
                           LEFT JOIN tbl_courses c ON s.course_id = c.course_id
                           ORDER BY s.section_name";
        $sections_stmt = $conn->query($sections_query);
        $sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count sections without course_id
        $null_count = 0;
        foreach ($sections as $section) {
            if ($section['course_id'] === NULL) {
                $null_count++;
            }
        }
        
        // Display summary
        echo "<p>Found " . count($courses) . " courses and " . count($sections) . " sections.</p>";
        echo "<p>" . $null_count . " sections have NULL course_id values.</p>";
        
        // Display courses
        echo "<h2>Available Courses:</h2>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Course Name</th></tr>";
        foreach ($courses as $course) {
            echo "<tr><td>" . $course['course_id'] . "</td><td>" . $course['course_name'] . "</td></tr>";
        }
        echo "</table>";
        
        // Display sections
        echo "<h2>Available Sections:</h2>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Section Name</th><th>Assigned Course</th></tr>";
        foreach ($sections as $section) {
            $course_name = $section['course_id'] ? $course_names[$section['course_id']] : "None (NULL)";
            echo "<tr><td>" . $section['section_id'] . "</td><td>" . $section['section_name'] . "</td><td>" . $course_name . "</td></tr>";
        }
        echo "</table>";
        
    } catch(PDOException $e) {
        echo '<div class="message error">Database error: ' . $e->getMessage() . '</div>';
    }
    ?>
    
    <h2>Fix Missing Relationships</h2>
    <p>This will update your sections to have the following relationships:</p>
    <ul>
        <li>Section "12" (ID: 195) → ICT (ID: 27)</li>
        <li>Section "VERSI" (ID: 198) → STEM 12 (ID: 31)</li>
    </ul>
    
    <form method="post">
        <button type="submit" name="update" class="button">Update Section-Course Relationships</button>
    </form>
    
    <p><a href="masterlist.php" class="button" style="background-color: #336699;">Return to Masterlist</a></p>
</body>
</html>
