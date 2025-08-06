<?php
// Use consistent session handling
require_once 'includes/session_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    header("Location: adm                // Get user name from login_register database using the helper function
                global $conn_login;
                $username = getUsernameFromDB($conn_login, $user_id);");
    exit;
}

// Include database connections
include('./conn/db_connect.php');

// Get user's school_id from session
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

// Function to test database connection
function testConnection($conn, $name) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h5>Testing $name Connection</h5></div>";
    echo "<div class='card-body'>";
    
    if ($conn) {
        echo "<p class='text-success'><i class='fas fa-check-circle'></i> Connection successful</p>";
        echo "<p>Server info: " . mysqli_get_server_info($conn) . "</p>";
        echo "<p>Host info: " . mysqli_get_host_info($conn) . "</p>";
    } else {
        echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Connection failed: " . mysqli_connect_error() . "</p>";
    }
    
    echo "</div></div>";
}

// Function to check table structure
function checkTableStructure($conn, $tableName) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h5>Table Structure: $tableName</h5></div>";
    echo "<div class='card-body'>";
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($result->num_rows == 0) {
        echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Table '$tableName' does not exist!</p>";
        echo "</div></div>";
        return;
    }
    
    // Get table structure
    $result = $conn->query("DESCRIBE $tableName");
    if ($result->num_rows > 0) {
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
        echo "<tbody>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<p class='text-danger'>Error getting table structure: " . $conn->error . "</p>";
    }
    
    echo "</div></div>";
}

// Function to test INSERT into a table
function testInsert($conn, $tableName) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h5>Test INSERT into $tableName</h5></div>";
    echo "<div class='card-body'>";
    
    // If it's tbl_attendance, try a test insert
    if ($tableName == 'tbl_attendance') {
        global $school_id, $user_id;
        
        // First, find a valid student from tbl_student
        $findStudentQuery = "SELECT tbl_student_id FROM tbl_student WHERE school_id = $school_id AND user_id = $user_id LIMIT 1";
        $studentResult = $conn->query($findStudentQuery);
        
        if ($studentResult->num_rows == 0) {
            echo "<p class='text-warning'><i class='fas fa-exclamation-triangle'></i> No students found for testing. Please add a student first.</p>";
            echo "</div></div>";
            return;
        }
        
        $student = $studentResult->fetch_assoc();
        $student_id = $student['tbl_student_id'];
        $currentTime = date('Y-m-d H:i:s');
        $status = 'Test';
        
        // Get current user's ID from login_register database as instructor_id
        $instructor_id = $user_id; // Use the current user_id as instructor_id
        $subject_id = 1;
        
        // Prepare test INSERT
        $query = "INSERT INTO tbl_attendance 
                 (tbl_student_id, time_in, status, instructor_id, subject_id, user_id, school_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Prepare failed: " . $conn->error . "</p>";
            echo "</div></div>";
            return;
        }
        
        $stmt->bind_param("issiiii", $student_id, $currentTime, $status, $instructor_id, $subject_id, $user_id, $school_id);
        $result = $stmt->execute();
        
        if ($result) {
            echo "<p class='text-success'><i class='fas fa-check-circle'></i> Test INSERT successful! New record ID: " . $conn->insert_id . "</p>";
            echo "<p>Values: student_id=$student_id, time_in=$currentTime, status=$status, instructor_id=$instructor_id, subject_id=$subject_id, user_id=$user_id, school_id=$school_id</p>";
            
            // Clean up test record
            $deleteQuery = "DELETE FROM tbl_attendance WHERE tbl_attendance_id = " . $conn->insert_id;
            if ($conn->query($deleteQuery)) {
                echo "<p class='text-info'><i class='fas fa-info-circle'></i> Test record deleted successfully.</p>";
            } else {
                echo "<p class='text-warning'><i class='fas fa-exclamation-triangle'></i> Could not delete test record: " . $conn->error . "</p>";
            }
        } else {
            echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Test INSERT failed: " . $stmt->error . "</p>";
            echo "<p>SQL Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='text-info'><i class='fas fa-info-circle'></i> INSERT test only available for tbl_attendance</p>";
    }
    
    echo "</div></div>";
}

// Function to fix common issues
function fixCommonIssues($conn, $tableName) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-warning'><h5>Attempting to Fix Common Issues with $tableName</h5></div>";
    echo "<div class='card-body'>";
    
    $fixed = false;
    
    // First, check if tbl_instructors exists and create it if needed
    if ($tableName == 'tbl_attendance') {
        $result = $conn->query("SHOW TABLES LIKE 'tbl_instructors'");
        if ($result->num_rows == 0) {
            echo "<p class='text-info'><i class='fas fa-tools'></i> Creating missing table 'tbl_instructors'</p>";
            
            // Create the instructors table with user_id as primary key
            $sql = "CREATE TABLE tbl_instructors (
                instructor_id INT PRIMARY KEY,
                instructor_name VARCHAR(255),
                school_id INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if ($conn->query($sql)) {
                echo "<p class='text-success'><i class='fas fa-check-circle'></i> Created tbl_instructors table successfully</p>";
                
                // Now insert current user as an instructor
                global $user_id;
                
                // Get user's name from login_register database if possible
                global $conn_login;
                $username = "User $user_id";
                
                if ($conn_login) {
                    $user_query = "SELECT name FROM users WHERE id = $user_id LIMIT 1";
                    $user_result = $conn_login->query($user_query);
                    if ($user_result && $user_result->num_rows > 0) {
                        $user_data = $user_result->fetch_assoc();
                        $username = $user_data['name'];
                    }
                }
                
                // Insert the current user as an instructor
                $insert_sql = "INSERT INTO tbl_instructors (instructor_id, instructor_name, school_id) VALUES ($user_id, '$username', 1)";
                if ($conn->query($insert_sql)) {
                    echo "<p class='text-success'><i class='fas fa-check-circle'></i> Added current user as instructor with ID: $user_id</p>";
                    $fixed = true;
                } else {
                    echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to add current user as instructor: " . $conn->error . "</p>";
                }
            } else {
                echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to create tbl_instructors table: " . $conn->error . "</p>";
            }
        } else {
            // Check if current user exists as instructor
            global $user_id;
            $check_sql = "SELECT * FROM tbl_instructors WHERE instructor_id = $user_id";
            $result = $conn->query($check_sql);
            
            if ($result->num_rows == 0) {
                // Get user's name from login_register database using the helper function
                global $conn_login;
                $username = getUsernameFromDB($conn_login, $user_id);
                
                // Insert the current user as an instructor
                $insert_sql = "INSERT INTO tbl_instructors (instructor_id, instructor_name, school_id) VALUES ($user_id, '$username', 1)";
                if ($conn->query($insert_sql)) {
                    echo "<p class='text-success'><i class='fas fa-check-circle'></i> Added current user as instructor with ID: $user_id</p>";
                    $fixed = true;
                } else {
                    echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to add current user as instructor: " . $conn->error . "</p>";
                }
            } else {
                echo "<p class='text-info'><i class='fas fa-info-circle'></i> Current user already exists as instructor with ID: $user_id</p>";
            }
        }
    }
    
    // For tbl_attendance: check required columns and add if missing
    if ($tableName == 'tbl_attendance') {
        $requiredColumns = [
            'tbl_attendance_id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'tbl_student_id' => 'INT NOT NULL',
            'time_in' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'status' => 'VARCHAR(20) DEFAULT NULL',
            'time_out' => 'TIMESTAMP NULL DEFAULT NULL',
            'instructor_id' => 'INT DEFAULT NULL',
            'subject_id' => 'INT DEFAULT NULL',
            'school_id' => 'INT DEFAULT 1',
            'user_id' => 'INT DEFAULT NULL'
        ];
        
        // Get existing columns
        $existingColumns = [];
        $result = $conn->query("DESCRIBE $tableName");
        while ($row = $result->fetch_assoc()) {
            $existingColumns[] = $row['Field'];
        }
        
        // Check each required column
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $existingColumns)) {
                echo "<p class='text-info'><i class='fas fa-tools'></i> Adding missing column '$column' to $tableName</p>";
                $sql = "ALTER TABLE $tableName ADD COLUMN $column $definition";
                if ($conn->query($sql)) {
                    echo "<p class='text-success'><i class='fas fa-check-circle'></i> Added column $column successfully</p>";
                    $fixed = true;
                } else {
                    echo "<p class='text-danger'><i class='fas fa-times-circle'></i> Failed to add column $column: " . $conn->error . "</p>";
                }
            }
        }
    }
    
    if (!$fixed) {
        echo "<p class='text-info'><i class='fas fa-info-circle'></i> No issues detected that could be automatically fixed.</p>";
    }
    
    echo "</div></div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Diagnostics - Qnnect</title>
    
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
        .success-indicator {
            color: #28a745;
        }
        .error-indicator {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Database Diagnostics Tool</h1>
        <p class="lead">This tool helps diagnose and fix issues with your Qnnect database connections and tables.</p>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4>Connection Tests</h4>
                    </div>
                    <div class="card-body">
                        <?php 
                        testConnection($conn_login, "Login Database"); 
                        testConnection($conn_qr, "QR Attendance Database");
                        ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h4>Table Structure</h4>
                    </div>
                    <div class="card-body">
                        <?php 
                        checkTableStructure($conn_qr, "tbl_student"); 
                        checkTableStructure($conn_qr, "tbl_attendance");
                        ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h4>INSERT Tests</h4>
                    </div>
                    <div class="card-body">
                        <?php testInsert($conn_qr, "tbl_attendance"); ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h4>Fix Common Issues</h4>
                    </div>
                    <div class="card-body">
                        <?php fixCommonIssues($conn_qr, "tbl_attendance"); ?>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-secondary mr-2">Back to Scanner</a>
                    <a href="masterlist.php" class="btn btn-secondary">Back to Masterlist</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
