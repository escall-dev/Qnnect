<?php
require_once 'database.php';

echo "<h1>🕵️ Duplicate School Investigation</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.duplicate { background-color: #ffebee; }
.normal { background-color: #e8f5e8; }
.warning { color: orange; font-weight: bold; }
.error { color: red; font-weight: bold; }
.success { color: green; font-weight: bold; }
</style>";

echo "<h2>📊 Current Database State</h2>";

// 1. Show all schools
echo "<h3>All Schools in Database:</h3>";
$schools_query = "SELECT * FROM schools ORDER BY id";
$schools_result = mysqli_query($conn, $schools_query);

if (!$schools_result) {
    echo "<p class='error'>Error querying schools: " . mysqli_error($conn) . "</p>";
    exit;
}

echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Status</th><th>Theme</th><th>Created</th><th>Updated</th></tr>";

$schools = [];
while ($school = mysqli_fetch_assoc($schools_result)) {
    $schools[] = $school;
    echo "<tr>";
    echo "<td>" . $school['id'] . "</td>";
    echo "<td>" . htmlspecialchars($school['name']) . "</td>";
    echo "<td>" . htmlspecialchars($school['code']) . "</td>";
    echo "<td>" . $school['status'] . "</td>";
    echo "<td>" . $school['theme_color'] . "</td>";
    echo "<td>" . $school['created_at'] . "</td>";
    echo "<td>" . $school['updated_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Identify duplicates
echo "<h3>🔍 Duplicate Analysis:</h3>";
$duplicate_names = [];
$name_counts = [];

foreach ($schools as $school) {
    $name_lower = strtolower(trim($school['name']));
    if (!isset($name_counts[$name_lower])) {
        $name_counts[$name_lower] = [];
    }
    $name_counts[$name_lower][] = $school;
}

$has_duplicates = false;
foreach ($name_counts as $name => $school_list) {
    if (count($school_list) > 1) {
        $has_duplicates = true;
        echo "<div class='error'>❌ DUPLICATE FOUND: \"" . htmlspecialchars($school_list[0]['name']) . "\" appears " . count($school_list) . " times:</div>";
        echo "<table class='duplicate'>";
        echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Created</th><th>User Count</th></tr>";
        
        foreach ($school_list as $school) {
            // Count users for each school
            $user_count_query = "SELECT COUNT(*) as count FROM users WHERE school_id = " . $school['id'];
            $user_count_result = mysqli_query($conn, $user_count_query);
            $user_count = mysqli_fetch_assoc($user_count_result)['count'];
            
            echo "<tr>";
            echo "<td>" . $school['id'] . "</td>";
            echo "<td>" . htmlspecialchars($school['name']) . "</td>";
            echo "<td>" . htmlspecialchars($school['code']) . "</td>";
            echo "<td>" . $school['created_at'] . "</td>";
            echo "<td>" . $user_count . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
}

if (!$has_duplicates) {
    echo "<div class='success'>✅ No duplicate schools found!</div>";
}

// 3. Show users and their school assignments
echo "<h3>👥 User-School Assignments:</h3>";
$users_query = "SELECT u.id, u.username, u.email, u.role, u.school_id, s.name as school_name 
                FROM users u 
                LEFT JOIN schools s ON u.school_id = s.id 
                ORDER BY s.name, u.username";
$users_result = mysqli_query($conn, $users_query);

echo "<table>";
echo "<tr><th>User ID</th><th>Username</th><th>Email</th><th>Role</th><th>School ID</th><th>School Name</th></tr>";

while ($user = mysqli_fetch_assoc($users_result)) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . htmlspecialchars($user['username'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>" . $user['role'] . "</td>";
    echo "<td>" . ($user['school_id'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($user['school_name'] ?? 'No School') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Show recent activity logs to identify when duplicates were created
echo "<h3>📋 Recent System Logs (School/User Activity):</h3>";
$logs_query = "SELECT * FROM system_logs 
               WHERE action IN ('SCHOOL_ADDED', 'USER_CREATED', 'USER_UPDATED') 
               ORDER BY created_at DESC 
               LIMIT 20";
$logs_result = mysqli_query($conn, $logs_query);

if ($logs_result && mysqli_num_rows($logs_result) > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>User ID</th><th>School ID</th><th>Action</th><th>Details</th><th>IP</th><th>Created</th></tr>";
    
    while ($log = mysqli_fetch_assoc($logs_result)) {
        echo "<tr>";
        echo "<td>" . $log['id'] . "</td>";
        echo "<td>" . ($log['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($log['school_id'] ?? 'NULL') . "</td>";
        echo "<td>" . $log['action'] . "</td>";
        echo "<td>" . htmlspecialchars($log['details'] ?? '') . "</td>";
        echo "<td>" . $log['ip_address'] . "</td>";
        echo "<td>" . $log['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No recent logs found for school/user activity.</p>";
}

// 5. If duplicates exist, provide fix recommendations
if ($has_duplicates) {
    echo "<h2>🛠️ Recommended Fix Actions:</h2>";
    echo "<div class='warning'>";
    echo "<p><strong>⚠️ WARNING: Before making any changes, backup your database!</strong></p>";
    echo "</div>";
    
    echo "<h3>Manual Fix Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Identify Primary School:</strong> Choose which school record to keep (usually the oldest or the one with most users)</li>";
    echo "<li><strong>Migrate Users:</strong> Move all users from duplicate schools to the primary school</li>";
    echo "<li><strong>Update Related Data:</strong> Update any students, courses, or other data that references the duplicate schools</li>";
    echo "<li><strong>Delete Duplicates:</strong> Remove the duplicate school records</li>";
    echo "</ol>";
    
    echo "<h3>Example SQL Commands:</h3>";
    foreach ($name_counts as $name => $school_list) {
        if (count($school_list) > 1) {
            $primary_school = $school_list[0]; // Assume first one is primary (you may want to change this logic)
            echo "<h4>For \"" . htmlspecialchars($primary_school['name']) . "\":</h4>";
            echo "<pre>";
            echo "-- Keep school ID " . $primary_school['id'] . " as primary\n";
            
            for ($i = 1; $i < count($school_list); $i++) {
                $duplicate = $school_list[$i];
                echo "-- Move users from duplicate school ID " . $duplicate['id'] . " to primary school ID " . $primary_school['id'] . "\n";
                echo "UPDATE users SET school_id = " . $primary_school['id'] . " WHERE school_id = " . $duplicate['id'] . ";\n";
                echo "-- Delete duplicate school ID " . $duplicate['id'] . "\n";
                echo "DELETE FROM schools WHERE id = " . $duplicate['id'] . ";\n\n";
            }
            echo "</pre>";
        }
    }
    
    echo "<h3>🔧 Auto-Fix Option:</h3>";
    echo "<p>If you want to automatically fix the duplicates, add this parameter: <strong>?autofix=1</strong></p>";
    echo "<p><strong>Example:</strong> diagnostic_duplicate_schools.php?autofix=1</p>";
    
    // Auto-fix option
    if (isset($_GET['autofix']) && $_GET['autofix'] == '1') {
        echo "<h3>🚀 Auto-Fix in Progress...</h3>";
        
        foreach ($name_counts as $name => $school_list) {
            if (count($school_list) > 1) {
                // Keep the first school (oldest by ID) as primary
                $primary_school = $school_list[0];
                echo "<p>Processing duplicates for \"" . htmlspecialchars($primary_school['name']) . "\"...</p>";
                
                for ($i = 1; $i < count($school_list); $i++) {
                    $duplicate = $school_list[$i];
                    
                    // Move users
                    $move_users_query = "UPDATE users SET school_id = " . $primary_school['id'] . " WHERE school_id = " . $duplicate['id'];
                    if (mysqli_query($conn, $move_users_query)) {
                        echo "<p class='success'>✅ Moved users from school ID " . $duplicate['id'] . " to " . $primary_school['id'] . "</p>";
                    } else {
                        echo "<p class='error'>❌ Failed to move users: " . mysqli_error($conn) . "</p>";
                    }
                    
                    // Delete duplicate school
                    $delete_school_query = "DELETE FROM schools WHERE id = " . $duplicate['id'];
                    if (mysqli_query($conn, $delete_school_query)) {
                        echo "<p class='success'>✅ Deleted duplicate school ID " . $duplicate['id'] . "</p>";
                    } else {
                        echo "<p class='error'>❌ Failed to delete duplicate school: " . mysqli_error($conn) . "</p>";
                    }
                }
            }
        }
        
        echo "<p class='success'>🎉 Auto-fix completed! Please refresh the page to see the results.</p>";
    }
}

// 6. Prevention recommendations
echo "<h2>🛡️ Prevention Recommendations:</h2>";
echo "<div class='success'>";
echo "<ul>";
echo "<li>✅ Always use existing school selection when creating users</li>";
echo "<li>✅ Implement proper validation in user creation forms</li>";
echo "<li>✅ Add unique constraints to prevent duplicate school names</li>";
echo "<li>✅ Regular monitoring of database integrity</li>";
echo "</ul>";
echo "</div>";

echo "<p><em>Investigation completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
