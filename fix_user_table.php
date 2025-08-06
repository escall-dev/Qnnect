<?php
// Use consistent session handling
require_once 'includes/session_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    header("Location: admin/login.php");
    exit;
}

// Include database connections
include('./conn/db_connect.php');

// Get user's school_id and user_id from session
$school_id = $_SESSION['school_id'];
$user_id = $_SESSION['user_id'];

// Function to check and fix the users table in login_register database
function checkAndFixUsersTable($conn_login) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-primary text-white'><h5>Checking Users Table</h5></div>";
    echo "<div class='card-body'>";
    
    if (!$conn_login) {
        echo "<p class='text-danger'><i class='fas fa-times-circle'></i> No connection to login_register database</p>";
        echo "</div></div>";
        return false;
    }
    
    // Check if users table exists
    $result = $conn_login->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Users table does not exist in login_register database</p>";
        echo "</div></div>";
        return false;
    }
    
    // Get current columns in the users table
    $columns_result = $conn_login->query("SHOW COLUMNS FROM users");
    $existing_columns = array();
    
    while ($row = $columns_result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    echo "<p>Found users table with columns: " . implode(", ", $existing_columns) . "</p>";
    
    // Check if name column exists, if not try to add it
    if (!in_array('name', $existing_columns)) {
        echo "<p>Name column does not exist. Attempting to add it...</p>";
        
        // First check if username exists, so we can copy data from there
        if (in_array('username', $existing_columns)) {
            $alter_sql = "ALTER TABLE users ADD COLUMN name VARCHAR(255) AFTER username";
            
            if ($conn_login->query($alter_sql)) {
                echo "<p class='text-success'><i class='fas fa-check-circle'></i> Added name column successfully</p>";
                
                // Copy data from username to name
                $copy_sql = "UPDATE users SET name = username WHERE name IS NULL";
                if ($conn_login->query($copy_sql)) {
                    echo "<p class='text-success'><i class='fas fa-check-circle'></i> Copied username data to name column</p>";
                } else {
                    echo "<p class='text-warning'><i class='fas fa-exclamation-triangle'></i> Failed to copy username data: " . $conn_login->error . "</p>";
                }
            } else {
                echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to add name column: " . $conn_login->error . "</p>";
            }
        } 
        // If email exists, use that instead
        else if (in_array('email', $existing_columns)) {
            $alter_sql = "ALTER TABLE users ADD COLUMN name VARCHAR(255) AFTER email";
            
            if ($conn_login->query($alter_sql)) {
                echo "<p class='text-success'><i class='fas fa-check-circle'></i> Added name column successfully</p>";
                
                // Copy data from email to name (before the @ symbol)
                $copy_sql = "UPDATE users SET name = SUBSTRING_INDEX(email, '@', 1) WHERE name IS NULL";
                if ($conn_login->query($copy_sql)) {
                    echo "<p class='text-success'><i class='fas fa-check-circle'></i> Generated names from email addresses</p>";
                } else {
                    echo "<p class='text-warning'><i class='fas fa-exclamation-triangle'></i> Failed to generate names: " . $conn_login->error . "</p>";
                }
            } else {
                echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to add name column: " . $conn_login->error . "</p>";
            }
        }
        // Otherwise just add the column
        else {
            $alter_sql = "ALTER TABLE users ADD COLUMN name VARCHAR(255) AFTER id";
            
            if ($conn_login->query($alter_sql)) {
                echo "<p class='text-success'><i class='fas fa-check-circle'></i> Added name column successfully</p>";
                
                // Set default values based on user ID
                $update_sql = "UPDATE users SET name = CONCAT('User ', id) WHERE name IS NULL";
                if ($conn_login->query($update_sql)) {
                    echo "<p class='text-success'><i class='fas fa-check-circle'></i> Set default names for all users</p>";
                } else {
                    echo "<p class='text-warning'><i class='fas fa-exclamation-triangle'></i> Failed to set default names: " . $conn_login->error . "</p>";
                }
            } else {
                echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to add name column: " . $conn_login->error . "</p>";
            }
        }
    } else {
        echo "<p class='text-success'><i class='fas fa-check-circle'></i> Name column already exists</p>";
    }
    
    echo "</div></div>";
    return true;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix User Table - Qnnect</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Fix User Table Structure</h1>
        <p class="lead">This tool checks and fixes the user table structure in the login_register database.</p>
        
        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Important!</h5>
            <p>This tool will examine the users table in your login_register database and add a 'name' column if needed. This helps ensure that user names can be properly retrieved for attendance records.</p>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <?php checkAndFixUsersTable($conn_login); ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h4>Next Steps</h4>
                    </div>
                    <div class="card-body">
                        <p>After fixing the user table, you should:</p>
                        <ol>
                            <li>Go to <a href="fix_foreign_keys.php">Fix Foreign Keys</a> to ensure the instructor-attendance relationship is correct</li>
                            <li>Run <a href="db_diagnostics.php">Database Diagnostics</a> to check if everything is working properly</li>
                            <li>Return to the <a href="index.php">Attendance Scanner</a> to try scanning QR codes again</li>
                        </ol>
                        
                        <div class="mt-3">
                            <a href="fix_foreign_keys.php" class="btn btn-primary">Fix Foreign Keys</a>
                            <a href="db_diagnostics.php" class="btn btn-info ml-2">Run Diagnostics</a>
                            <a href="index.php" class="btn btn-secondary ml-2">Return to Scanner</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
