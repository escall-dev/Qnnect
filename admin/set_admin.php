<?php
require_once "database.php";

// Check if user_type column exists in users table
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'user_type'");
if (mysqli_num_rows($check_column) == 0) {
    // Add user_type column if it doesn't exist
    $add_column = "ALTER TABLE users ADD COLUMN user_type VARCHAR(20) DEFAULT 'User'";
    
    if (mysqli_query($conn, $add_column)) {
        echo "Added user_type column to users table.<br>";
    } else {
        echo "Error adding user_type column: " . mysqli_error($conn) . "<br>";
    }
}

// Get the email of the user you want to set as admin
$admin_email = isset($_POST['admin_email']) ? $_POST['admin_email'] : '';

if (!empty($admin_email)) {
    // Set the specified user as admin
    $update_admin = "UPDATE users SET user_type = 'Admin' WHERE email = ?";
    $stmt = mysqli_prepare($conn, $update_admin);
    mysqli_stmt_bind_param($stmt, "s", $admin_email);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "User with email {$admin_email} has been set as Admin.<br>";
    } else {
        echo "Error setting admin: " . mysqli_error($conn) . "<br>";
    }
} else {
    // Set the first user as admin if no email specified
    $first_user = mysqli_query($conn, "SELECT id, email FROM users ORDER BY id LIMIT 1");
    if ($first_user && $first_row = mysqli_fetch_assoc($first_user)) {
        $update_admin = "UPDATE users SET user_type = 'Admin' WHERE id = " . $first_row['id'];
        
        if (mysqli_query($conn, $update_admin)) {
            echo "First user (email: {$first_row['email']}) has been set as Admin.<br>";
        } else {
            echo "Error setting admin: " . mysqli_error($conn) . "<br>";
        }
    }
}

// List all users and their types
$users_query = "SELECT id, username, email, user_type FROM users ORDER BY id";
$users_result = mysqli_query($conn, $users_query);

echo "<h3>Current Users:</h3>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>User Type</th></tr>";

while ($row = mysqli_fetch_assoc($users_result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . ($row['user_type'] ?? 'User') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Form to set a specific user as admin
echo "<h3>Set a User as Admin:</h3>";
echo "<form method='post'>";
echo "Email: <input type='email' name='admin_email' required>";
echo "<input type='submit' value='Set as Admin'>";
echo "</form>";

echo "<br><a href='history.php'>Go to History Page</a>";
?> 