<?php
// Database integrity enhancer - Add constraints to prevent duplicates
// Use admin database connection for schools and users tables
require_once 'admin/database.php';

echo "<h2>Database Integrity Enhancement</h2>\n";

try {
    // Check if unique index exists on schools.name
    $check_index = mysqli_query($conn, "SHOW INDEX FROM schools WHERE Key_name = 'unique_school_name'");
    
    if (mysqli_num_rows($check_index) == 0) {
        echo "<p>Adding unique constraint on school names...</p>\n";
        
        // First, clean up any existing duplicates
        $duplicate_query = "SELECT name, COUNT(*) as count, MIN(id) as keep_id, GROUP_CONCAT(id ORDER BY id) as all_ids 
                           FROM schools 
                           GROUP BY LOWER(TRIM(name)) 
                           HAVING COUNT(*) > 1";
        
        $duplicates = mysqli_query($conn, $duplicate_query);
        
        if ($duplicates && mysqli_num_rows($duplicates) > 0) {
            echo "<p>Found duplicates that need to be merged first:</p>\n";
            while ($dup = mysqli_fetch_assoc($duplicates)) {
                $all_ids = explode(',', $dup['all_ids']);
                $keep_id = $dup['keep_id'];
                $remove_ids = array_filter($all_ids, function($id) use ($keep_id) {
                    return $id != $keep_id;
                });
                
                if (count($remove_ids) > 0) {
                    echo "<p>Merging '{$dup['name']}': keeping ID {$keep_id}, removing IDs " . implode(', ', $remove_ids) . "</p>\n";
                    
                    // Update users to point to the kept school
                    foreach ($remove_ids as $remove_id) {
                        $update_users = mysqli_prepare($conn, "UPDATE users SET school_id = ? WHERE school_id = ?");
                        mysqli_stmt_bind_param($update_users, 'ii', $keep_id, $remove_id);
                        mysqli_stmt_execute($update_users);
                        $affected = mysqli_stmt_affected_rows($update_users);
                        mysqli_stmt_close($update_users);
                        echo "<p>Moved {$affected} users from school ID {$remove_id} to {$keep_id}</p>\n";
                    }
                    
                    // Delete duplicate schools
                    $delete_ids = implode(',', array_map('intval', $remove_ids));
                    $delete_query = "DELETE FROM schools WHERE id IN ($delete_ids)";
                    mysqli_query($conn, $delete_query);
                    $deleted = mysqli_affected_rows($conn);
                    echo "<p>Deleted {$deleted} duplicate school records</p>\n";
                }
            }
        }
        
        // Add unique constraint on school name
        mysqli_query($conn, "ALTER TABLE schools ADD UNIQUE INDEX unique_school_name (name)");
        echo "<p style='color: green;'>✓ Added unique constraint on school names</p>\n";
    } else {
        echo "<p style='color: blue;'>ℹ Unique constraint on school names already exists</p>\n";
    }
    
    // Check if unique index exists on schools.code
    $check_code_index = mysqli_query($conn, "SHOW INDEX FROM schools WHERE Key_name = 'unique_school_code'");
    
    if (mysqli_num_rows($check_code_index) == 0) {
        echo "<p>Adding unique constraint on school codes...</p>\n";
        mysqli_query($conn, "ALTER TABLE schools ADD UNIQUE INDEX unique_school_code (code)");
        echo "<p style='color: green;'>✓ Added unique constraint on school codes</p>\n";
    } else {
        echo "<p style='color: blue;'>ℹ Unique constraint on school codes already exists</p>\n";
    }
    
    // Add foreign key constraint if it doesn't exist
    $db_name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT DATABASE() as db"))['db'];
    $check_fk = mysqli_query($conn, "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                                   WHERE TABLE_SCHEMA = '$db_name' 
                                   AND TABLE_NAME = 'users' 
                                   AND COLUMN_NAME = 'school_id' 
                                   AND REFERENCED_TABLE_NAME = 'schools'");
    
    if (mysqli_num_rows($check_fk) == 0) {
        echo "<p>Adding foreign key constraint...</p>\n";
        mysqli_query($conn, "ALTER TABLE users ADD CONSTRAINT fk_users_school_id 
                            FOREIGN KEY (school_id) REFERENCES schools(id) 
                            ON DELETE SET NULL ON UPDATE CASCADE");
        echo "<p style='color: green;'>✓ Added foreign key constraint</p>\n";
    } else {
        echo "<p style='color: blue;'>ℹ Foreign key constraint already exists</p>\n";
    }
    
    echo "<h3>Current Database Status:</h3>\n";
    
    // Show schools count
    $school_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM schools"));
    echo "<p>Total schools: {$school_count['count']}</p>\n";
    
    // Show users count
    $user_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"));
    echo "<p>Total users: {$user_count['count']}</p>\n";
    
    // Show users without schools
    $orphan_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE school_id IS NULL"));
    echo "<p>Users without schools: {$orphan_users['count']}</p>\n";
    
    echo "<p style='color: green;'><strong>Database integrity enhancement completed!</strong></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error: " . $e->getMessage() . "</strong></p>\n";
    echo "<p>This might happen if there are still duplicate entries. Please use the fix_duplicate_schools.php tool first.</p>\n";
}

mysqli_close($conn);
?>
