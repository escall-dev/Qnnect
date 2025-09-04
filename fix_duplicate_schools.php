<?php
// Quick diagnostic and fix for duplicate schools
// Use admin database connection for schools and users tables
require_once 'admin/database.php';

echo "<h2>Duplicate Schools Analysis and Fix</h2>\n";

// Step 1: Find all duplicate school names
$query = "SELECT name, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids, GROUP_CONCAT(created_at ORDER BY id) as dates 
          FROM schools 
          GROUP BY LOWER(TRIM(name)) 
          HAVING COUNT(*) > 1 
          ORDER BY name";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h3>Found Duplicate Schools:</h3>\n";
    echo "<table border='1'>\n";
    echo "<tr><th>School Name</th><th>Duplicate Count</th><th>School IDs</th><th>Creation Dates</th><th>Action</th></tr>\n";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $ids = explode(',', $row['ids']);
        $dates = explode(',', $row['dates']);
        
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>\n";
        echo "<td>" . $row['count'] . "</td>\n";
        echo "<td>" . $row['ids'] . "</td>\n";
        echo "<td>" . $row['dates'] . "</td>\n";
        echo "<td><form method='post' style='display:inline;'>";
        echo "<input type='hidden' name='merge_school' value='" . htmlspecialchars($row['name']) . "'>";
        echo "<input type='hidden' name='school_ids' value='" . $row['ids'] . "'>";
        echo "<input type='submit' value='Merge Duplicates' onclick='return confirm(\"Merge all duplicate entries for " . htmlspecialchars($row['name']) . "?\");'>";
        echo "</form></td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p style='color: green;'>No duplicate schools found!</p>\n";
}

// Process merge request
if (isset($_POST['merge_school']) && isset($_POST['school_ids'])) {
    $school_name = $_POST['merge_school'];
    $school_ids = explode(',', $_POST['school_ids']);
    
    if (count($school_ids) > 1) {
        // Keep the first (oldest) school ID
        $keep_id = $school_ids[0];
        $merge_ids = array_slice($school_ids, 1);
        
        echo "<h3>Merging Duplicates for: " . htmlspecialchars($school_name) . "</h3>\n";
        echo "<p>Keeping School ID: $keep_id</p>\n";
        echo "<p>Merging School IDs: " . implode(', ', $merge_ids) . "</p>\n";
        
        // Start transaction
        mysqli_autocommit($conn, false);
        
        try {
            // Update all users from duplicate schools to the main school
            foreach ($merge_ids as $merge_id) {
                $update_query = "UPDATE users SET school_id = ? WHERE school_id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, 'ii', $keep_id, $merge_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $affected = mysqli_stmt_affected_rows($stmt);
                    echo "<p>Moved $affected users from school ID $merge_id to school ID $keep_id</p>\n";
                } else {
                    throw new Exception("Failed to update users for school ID $merge_id");
                }
                mysqli_stmt_close($stmt);
            }
            
            // Delete duplicate school records
            $delete_ids = implode(',', array_map('intval', $merge_ids));
            $delete_query = "DELETE FROM schools WHERE id IN ($delete_ids)";
            
            if (mysqli_query($conn, $delete_query)) {
                $deleted = mysqli_affected_rows($conn);
                echo "<p>Deleted $deleted duplicate school records</p>\n";
            } else {
                throw new Exception("Failed to delete duplicate schools");
            }
            
            // Commit transaction
            mysqli_commit($conn);
            echo "<p style='color: green;'><strong>Successfully merged duplicates for '$school_name'!</strong></p>\n";
            
            // Log the action (if activity_logs table exists in this database)
            $log_message = "Merged duplicate schools for '$school_name'. Kept ID: $keep_id, Removed IDs: " . implode(', ', $merge_ids);
            $log_query = "INSERT INTO system_logs (username, action, details, timestamp) VALUES (?, 'SCHOOL_MERGE', ?, NOW())";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $admin_user = 'System Admin';
            mysqli_stmt_bind_param($log_stmt, 'ss', $admin_user, $log_message);
            @mysqli_stmt_execute($log_stmt); // Use @ to suppress errors if table doesn't exist
            mysqli_stmt_close($log_stmt);
            
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            echo "<p style='color: red;'><strong>Error: " . $e->getMessage() . "</strong></p>\n";
        }
        
        // Re-enable autocommit
        mysqli_autocommit($conn, true);
        
        // Refresh the page to show updated results
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>\n";
    }
}

// Step 2: Show all schools for verification
echo "<h3>All Schools in Database:</h3>\n";
$all_schools = mysqli_query($conn, "SELECT id, name, created_at, (SELECT COUNT(*) FROM users WHERE school_id = schools.id) as user_count FROM schools ORDER BY name, id");

if ($all_schools && mysqli_num_rows($all_schools) > 0) {
    echo "<table border='1'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Created</th><th>Users</th></tr>\n";
    
    while ($school = mysqli_fetch_assoc($all_schools)) {
        echo "<tr>\n";
        echo "<td>" . $school['id'] . "</td>\n";
        echo "<td>" . htmlspecialchars($school['name']) . "</td>\n";
        echo "<td>" . $school['created_at'] . "</td>\n";
        echo "<td>" . $school['user_count'] . "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

mysqli_close($conn);
?>
