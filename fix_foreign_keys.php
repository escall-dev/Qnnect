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

/**
 * Helper function to get username from various possible columns in users table
 * @param mysqli $conn_login Database connection to login_register database
 * @param int $user_id User ID to look up
 * @return string Username or default value if not found
 */
function getUsernameFromDB($conn_login, $user_id) {
    $username = "User $user_id"; // Default fallback
    
    if (!$conn_login) {
        return $username;
    }
    
    // Check which columns exist in the users table
    $check_columns = $conn_login->query("SHOW COLUMNS FROM users");
    if (!$check_columns) {
        return $username;
    }
    
    $column_names = [];
    while($row = $check_columns->fetch_assoc()) {
        $column_names[] = $row['Field'];
    }
    
    // Try different column names that might contain user info
    $column_to_try = '';
    
    if (in_array('name', $column_names)) {
        $column_to_try = 'name';
    } elseif (in_array('username', $column_names)) {
        $column_to_try = 'username';
    } elseif (in_array('email', $column_names)) {
        $column_to_try = 'email';
    } else {
        return $username; // No usable column found
    }
    
    // Query the appropriate column
    $user_query = "SELECT $column_to_try FROM users WHERE id = $user_id LIMIT 1";
    $user_result = $conn_login->query($user_query);
    
    if ($user_result && $user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $username = $user_data[$column_to_try];
    }
    
    return $username;
}

// Function to fix foreign key constraints
function fixForeignKeyConstraints($conn) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-danger text-white'><h5>Foreign Key Constraint Fixes</h5></div>";
    echo "<div class='card-body'>";
    
    echo "<h6>Checking tbl_attendance Foreign Key Constraints</h6>";
    
    // Check if the foreign key constraint exists on instructor_id
    $check_fk_sql = "SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                     WHERE TABLE_NAME = 'tbl_attendance' 
                     AND CONSTRAINT_NAME LIKE '%fk_instructor%'
                     AND TABLE_SCHEMA = 'qr_attendance_db'";
    
    $result = $conn->query($check_fk_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<p><strong>Foreign key constraint found on tbl_attendance.instructor_id</strong></p>";
        echo "<p>Attempting to drop the constraint...</p>";
        
        // Get constraint name
        $row = $result->fetch_assoc();
        $constraint_name = $row['CONSTRAINT_NAME'];
        
        // Drop the constraint
        $drop_sql = "ALTER TABLE tbl_attendance DROP FOREIGN KEY $constraint_name";
        
        if ($conn->query($drop_sql)) {
            echo "<p class='text-success'><i class='fas fa-check-circle'></i> Successfully dropped the foreign key constraint: $constraint_name</p>";
            
            // Add instructor ID column to tbl_attendance if needed without constraint
            $alter_sql = "ALTER TABLE tbl_attendance MODIFY instructor_id INT DEFAULT NULL";
            if ($conn->query($alter_sql)) {
                echo "<p class='text-success'><i class='fas fa-check-circle'></i> Modified instructor_id column definition</p>";
            } else {
                echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to modify instructor_id column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to drop foreign key constraint: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='text-info'><i class='fas fa-info-circle'></i> No foreign key constraint found on instructor_id. No action needed.</p>";
    }
    
    // Check if tbl_instructors table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'tbl_instructors'");
    if ($table_check->num_rows == 0) {
        echo "<p>Creating tbl_instructors table...</p>";
        
        $create_sql = "CREATE TABLE tbl_instructors (
            instructor_id INT PRIMARY KEY,
            instructor_name VARCHAR(255),
            school_id INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_sql)) {
            echo "<p class='text-success'><i class='fas fa-check-circle'></i> Successfully created tbl_instructors table</p>";
        } else {
            echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to create tbl_instructors table: " . $conn->error . "</p>";
        }
    }
    
    // Add current user to tbl_instructors if needed
    global $user_id;
    $check_instructor = $conn->query("SELECT * FROM tbl_instructors WHERE instructor_id = $user_id");
    
    if ($check_instructor->num_rows == 0) {
        // Get user name from login_register database
        global $conn_login;
        $username = "User $user_id"; // Default name
        
        // Use the helper function to get username
        $username = getUsernameFromDB($conn_login, $user_id);
        
        global $school_id;
        $insert_sql = "INSERT INTO tbl_instructors (instructor_id, instructor_name, school_id) VALUES ($user_id, '$username', $school_id)";
        
        if ($conn->query($insert_sql)) {
            echo "<p class='text-success'><i class='fas fa-check-circle'></i> Added current user as instructor (ID: $user_id)</p>";
        } else {
            echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to add user as instructor: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='text-info'><i class='fas fa-info-circle'></i> Current user already exists as instructor (ID: $user_id)</p>";
    }
    
    echo "</div></div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Foreign Key Constraints - Qnnect</title>
    
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
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Fix Foreign Key Constraints</h1>
        <p class="lead">This tool fixes foreign key constraint issues in the tbl_attendance table.</p>
        
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle"></i> Important!</h5>
            <p>This tool will attempt to fix foreign key constraint issues by:</p>
            <ol>
                <li>Removing any foreign key constraints on the instructor_id column</li>
                <li>Creating the tbl_instructors table if it doesn't exist</li>
                <li>Adding the current user as an instructor</li>
            </ol>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <?php fixForeignKeyConstraints($conn_qr); ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h4>Next Steps</h4>
                    </div>
                    <div class="card-body">
                        <p>After fixing the foreign key constraints, you should:</p>
                        <ol>
                            <li>Return to the attendance scanner page</li>
                            <li>Try scanning a QR code again</li>
                            <li>Check that attendance records are being created successfully</li>
                        </ol>
                        
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-primary">Return to Attendance Scanner</a>
                            <a href="db_diagnostics.php" class="btn btn-secondary ml-2">Run Complete Diagnostics</a>
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
