<?php
// Fix table structure for tbl_user_logs
require_once "database.php";

header("Content-Type: text/html");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Table Structure</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 5px; }
        h1 { color: #098744; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .action { background: #098744; color: white; padding: 10px 15px; border-radius: 3px; text-decoration: none; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Table Structure Fix</h1>
        
        <?php
        if (!$conn) {
            echo "<p class='error'>Database connection failed: " . mysqli_connect_error() . "</p>";
            exit;
        }
        
        echo "<p class='info'>Database connection successful.</p>";
        
        // Check current table structure
        echo "<h3>Current Table Structure:</h3>";
        $describe = mysqli_query($conn, "DESCRIBE tbl_user_logs");
        if ($describe) {
            echo "<ul>";
            $columns = [];
            while ($row = mysqli_fetch_assoc($describe)) {
                $columns[] = $row['Field'];
                echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
            }
            echo "</ul>";
            
            // Check if email column exists
            if (!in_array('email', $columns)) {
                echo "<p class='error'>Email column is missing!</p>";
                
                if (isset($_GET['fix']) && $_GET['fix'] == 'yes') {
                    // Add the email column
                    $alter_query = "ALTER TABLE tbl_user_logs ADD COLUMN email VARCHAR(100) NOT NULL DEFAULT '' AFTER username";
                    
                    if (mysqli_query($conn, $alter_query)) {
                        echo "<p class='success'>Email column added successfully!</p>";
                        echo "<p><a href='?' class='action'>Refresh to see changes</a></p>";
                    } else {
                        echo "<p class='error'>Failed to add email column: " . mysqli_error($conn) . "</p>";
                    }
                } else {
                    echo "<p><a href='?fix=yes' class='action'>Add Email Column</a></p>";
                }
            } else {
                echo "<p class='success'>Email column exists!</p>";
            }
        } else {
            echo "<p class='error'>Failed to get table structure: " . mysqli_error($conn) . "</p>";
        }
        
        // Show sample data
        echo "<h3>Sample Data (Last 5 records):</h3>";
        $sample = mysqli_query($conn, "SELECT * FROM tbl_user_logs ORDER BY log_id DESC LIMIT 5");
        if ($sample && mysqli_num_rows($sample) > 0) {
            echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
            
            // Get column names for header
            $fields = mysqli_fetch_fields($sample);
            echo "<tr>";
            foreach ($fields as $field) {
                echo "<th>" . htmlspecialchars($field->name) . "</th>";
            }
            echo "</tr>";
            
            // Reset result pointer
            mysqli_data_seek($sample, 0);
            
            // Show data
            while ($row = mysqli_fetch_assoc($sample)) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No data found or table is empty.</p>";
        }
        ?>
        
        <h3>Actions:</h3>
        <a href="history.php" class="action">Return to History Page</a>
        <a href="direct_fix_login.php" class="action">Go to Login Fix Tool</a>
    </div>
</body>
</html> 