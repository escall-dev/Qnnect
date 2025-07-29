<?php
require_once 'includes/asset_helper.php';
require_once 'includes/session_config.php';
include('./conn/db_connect.php');
// Include activity logging helper
include('./includes/activity_log_helper.php');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: admin/login.php");
    exit;
}

// Use the connection already established in db_connect.php
// $conn_login is already available from the included file

// Determine which tab to display - initialize before using it
$current_tab = 'academic';
if (isset($_GET['tab'])) {
    $current_tab = $_GET['tab'];
}

// Database configuration

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "login_register"; // Primary database
$qr_db_name = "qr_attendance_db"; // QR attendance database

// Multi-school: Load current school info from session
$school_id = isset($_SESSION['school_id']) ? intval($_SESSION['school_id']) : 1;
$school_info = null;
$school_query = "SELECT * FROM school_info WHERE school_id = $school_id";
$school_result = $conn_qr->query($school_query);
if ($school_result && $school_result->num_rows > 0) {
    $school_info = $school_result->fetch_assoc();
}

// Create backup directory if it doesn't exist
$backupDir = __DIR__ . '/backup/';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Create backup_restore class
class backup_restore {
    private $host;
    private $username;
    private $passwd;
    private $dbName;
    private $charset;
    private $conn;
    private $backupDir;
    private $backupFile;
    
    public function __construct($host, $dbName, $username, $passwd, $charset = 'utf8') {
        $this->host = $host;
        $this->dbName = $dbName;
        $this->username = $username;
        $this->passwd = $passwd;
        $this->charset = $charset;
        $this->conn = $this->connectDB();
        $this->backupDir = 'backup/';
        $this->backupFile = 'database_'.$this->dbName.'_'.date('Y-m-d_H-i-s').'.sql';
        
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
    }
    
    protected function connectDB() {
        try {
            $conn = new mysqli($this->host, $this->username, $this->passwd, $this->dbName);
            $conn->set_charset($this->charset);
            
            if ($conn->connect_error) {
                throw new Exception('Error connecting to database: ' . $conn->connect_error);
            }
            
            return $conn;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
    
    public function backup() {
        try {
            $this->conn->query("SET NAMES '".$this->charset."'");
            
            $tables = array();
            $result = $this->conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            $sqlScript = "-- Database Backup for ".$this->dbName." - ".date('Y-m-d H:i:s')."\n\n";
            $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $sqlScript .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $sqlScript .= "SET time_zone = \"+00:00\";\n\n";
            
            foreach ($tables as $table) {
                // Get table structure
                $result = $this->conn->query("SHOW CREATE TABLE $table");
                $row = $result->fetch_row();
                
                $sqlScript .= "\n\n-- Table structure for table `$table`\n\n";
                $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
                $sqlScript .= $row[1].";\n\n";
                
                // Get table data
                $result = $this->conn->query("SELECT * FROM $table");
                $columnCount = $result->field_count;
                
                if ($result->num_rows > 0) {
                    $sqlScript .= "-- Dumping data for table `$table`\n";
                    $sqlScript .= "INSERT INTO `$table` VALUES";
                    
                    $rowCount = 0;
                    while ($row = $result->fetch_row()) {
                        $sqlScript .= "\n(";
                        for ($i = 0; $i < $columnCount; $i++) {
                            if (isset($row[$i])) {
                                $sqlScript .= "'".$this->conn->real_escape_string($row[$i])."'";
                            } else {
                                $sqlScript .= "NULL";
                            }
                            
                            if ($i < ($columnCount - 1)) {
                                $sqlScript .= ",";
                            }
                        }
                        
                        if (++$rowCount < $result->num_rows) {
                            $sqlScript .= "),";
                        } else {
                            $sqlScript .= ");";
                        }
                    }
                }
                
                $sqlScript .= "\n\n";
            }
            
            $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            $backupFilePath = $this->backupDir . $this->backupFile;
            file_put_contents($backupFilePath, $sqlScript);
            
            return $this->backupFile;
        } catch (Exception $e) {
            return "Error taking backup: " . $e->getMessage();
        }
    }
    
    public function restore($uploadedFile) {
        try {
            if (!file_exists($uploadedFile)) {
                return "Backup file does not exist.";
            }
            
            $sql = file_get_contents($uploadedFile);
            
            // Clean up the SQL content before processing
            $sql = $this->cleanSQLContentForRestore($sql);
            
            // Pre-emptively drop all tables to avoid conflicts
            $this->dropAllTables();
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // Disable foreign key checks temporarily
            $this->conn->query("SET FOREIGN_KEY_CHECKS=0");
            $this->conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
            
            foreach ($statements as $statement) {
                $cleanStatement = trim($statement);
                
                // Skip empty statements and comments
                if (empty($cleanStatement) || 
                    preg_match('/^--/', $cleanStatement) || 
                    preg_match('/^\/\*/', $cleanStatement) ||
                    preg_match('/^#/', $cleanStatement)) {
                    continue;
                }
                
                // Execute the statement
                $result = $this->conn->query($cleanStatement . ';');
                if ($result) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errorMsg = $this->conn->error;
                    $errors[] = $errorMsg;
                    
                    // Log errors but continue processing
                    if (strpos($errorMsg, 'Unknown table') === false &&
                        strpos($errorMsg, 'Duplicate entry') === false &&
                        strpos($errorMsg, 'already exists') === false) {
                        error_log("SQL Error in restore: " . $errorMsg . " | Statement: " . substr($cleanStatement, 0, 100));
                    }
                }
            }
            
            // Re-enable foreign key checks
            $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
            
            if ($errorCount == 0) {
                return "Database restored successfully! Executed $successCount statements. Your old data has been restored.";
            } else {
                return "Database restored with some warnings. Executed $successCount statements, $errorCount warnings. Your old data has been restored.";
            }
            
        } catch (Exception $e) {
            return "Error restoring database: " . $e->getMessage();
        }
    }
    
    private function dropAllTables() {
        try {
            // Get list of all tables in the database
            $result = $this->conn->query("SHOW TABLES");
            $tables = [];
            
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            // Disable foreign key checks
            $this->conn->query("SET FOREIGN_KEY_CHECKS=0");
            
            // Drop all tables
            foreach ($tables as $table) {
                $this->conn->query("DROP TABLE IF EXISTS `$table`");
            }
            
            // Re-enable foreign key checks
            $this->conn->query("SET FOREIGN_KEY_CHECKS=1");
            
        } catch (Exception $e) {
            error_log("Error dropping tables: " . $e->getMessage());
        }
    }
    
    private function cleanSQLContentForRestore($sql) {
        // Clean SQL but preserve important DROP and CREATE statements
        $lines = explode("\n", $sql);
        $cleanLines = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Skip completely empty lines
            if (empty($trimmed)) {
                $cleanLines[] = $line;
                continue;
            }
            
            // Skip phpMyAdmin headers but keep SQL statements
            if (stripos($trimmed, 'phpMyAdmin') !== false ||
                stripos($trimmed, 'Win64') !== false ||
                stripos($trimmed, 'Generation Time') !== false ||
                stripos($trimmed, 'Server version') !== false ||
                stripos($trimmed, 'PHP Version') !== false ||
                stripos($trimmed, 'Host:') !== false ||
                preg_match('/^--\s*(phpMyAdmin|Version|Host|Generation|Server|PHP)/i', $trimmed) ||
                preg_match('/^--\s*https?:\/\//', $trimmed)) {
                continue;
            }
            
            // Keep important SQL statements (DROP, CREATE, INSERT, ALTER, UPDATE, DELETE, SET)
            if (preg_match('/^(DROP|CREATE|INSERT|ALTER|UPDATE|DELETE|SET)/i', $trimmed) ||
                preg_match('/^(START\s+TRANSACTION|COMMIT)/i', $trimmed)) {
                $cleanLines[] = $line;
                continue;
            }
            
            // Skip other comment lines but keep SQL
            if (!preg_match('/^(--|#|\/\*)/', $trimmed)) {
                $cleanLines[] = $line;
            }
        }
        
        $cleanedSQL = implode("\n", $cleanLines);
        
        // Remove multiple consecutive newlines
        $cleanedSQL = preg_replace('/\n\s*\n\s*\n/', "\n\n", $cleanedSQL);
        
        return trim($cleanedSQL);
    }
}

// Create instances for both databases
$backup_message = '';
$restore_message = '';

// Handle backup process if requested
if (isset($_GET['action']) && $_GET['action'] == 'backup' && $current_tab == 'backup') {
    $loginImport = new backup_restore($db_host, $db_name, $db_user, $db_pass);
    $qrImport = new backup_restore($db_host, $qr_db_name, $db_user, $db_pass);
    
    $loginBackup = $loginImport->backup();
    $qrBackup = $qrImport->backup();
    
    $backup_message = "Databases backed up successfully!";
}

// Database cleaner class for delete functionality
class DatabaseCleaner {
    private $conn;
    
    public function __construct($host, $username, $password, $database) {
        try {
            $this->conn = new mysqli($host, $username, $password, $database);
            
            if ($this->conn->connect_error) {
                throw new Exception('Connection failed: ' . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    public function deleteAllData() {
        try {
            // Disable foreign key checks temporarily
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 0");
            
            // Define tables to delete from in the correct order (respecting foreign key constraints)
            $tablesToClear = [
                'activity_logs',
                'attendance_grades', 
                'attendance_logs',
                'attendance_sessions',
                'courses',
                'offline_data',
                'school_info',
                'tbl_attendance',
                'tbl_face_recognition_logs',
                'tbl_face_verification_logs',
                'tbl_instructors',
                'tbl_instructor_subjects',
                'tbl_student',
                'tbl_subjects',
                'tbl_user_logs'
            ];
            
            $deletedTables = [];
            $skippedTables = [];
            
            foreach ($tablesToClear as $table) {
                // Check if table exists
                $checkTable = $this->conn->query("SHOW TABLES LIKE '$table'");
                if ($checkTable->num_rows > 0) {
                    // Get row count before deletion
                    $countResult = $this->conn->query("SELECT COUNT(*) as count FROM `$table`");
                    $rowCount = $countResult->fetch_assoc()['count'];
                    
                    if ($rowCount > 0) {
                        // Delete all data from table
                        $deleteQuery = "DELETE FROM `$table`";
                        if ($this->conn->query($deleteQuery)) {
                            $deletedTables[] = "$table ($rowCount rows deleted)";
                        } else {
                            throw new Exception("Error deleting from $table: " . $this->conn->error);
                        }
                    } else {
                        $skippedTables[] = "$table (already empty)";
                    }
                } else {
                    $skippedTables[] = "$table (table doesn't exist)";
                }
            }
            
            // Reset auto-increment values for key tables
            $autoIncrementTables = [
                'tbl_attendance',
                'tbl_student', 
                'tbl_instructors',
                'tbl_subjects',
                'activity_logs',
                'attendance_logs',
                'tbl_user_logs'
            ];
            
            foreach ($autoIncrementTables as $table) {
                $checkTable = $this->conn->query("SHOW TABLES LIKE '$table'");
                if ($checkTable->num_rows > 0) {
                    $this->conn->query("ALTER TABLE `$table` AUTO_INCREMENT = 1");
                }
            }
            
            // Re-enable foreign key checks
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            return [
                'success' => true,
                'message' => 'Complete system reset completed successfully! All system data has been permanently deleted from 15 tables.',
                'deleted' => $deletedTables,
                'skipped' => $skippedTables
            ];
            
        } catch (Exception $e) {
            // Re-enable foreign key checks in case of error
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            return [
                'success' => false,
                'message' => 'Error during system reset: ' . $e->getMessage(),
                'deleted' => $deletedTables ?? [],
                'skipped' => $skippedTables ?? []
            ];
        }
    }
    
    public function getTableCounts() {
        $tables = [
            'activity_logs',
            'attendance_grades', 
            'attendance_logs',
            'attendance_sessions',
            'courses',
            'offline_data',
            'school_info',
            'tbl_attendance',
            'tbl_face_recognition_logs',
            'tbl_face_verification_logs',
            'tbl_instructors',
            'tbl_instructor_subjects',
            'tbl_student',
            'tbl_subjects',
            'tbl_user_logs'
        ];
        
        $counts = [];
        foreach ($tables as $table) {
            $checkTable = $this->conn->query("SHOW TABLES LIKE '$table'");
            if ($checkTable->num_rows > 0) {
                $result = $this->conn->query("SELECT COUNT(*) as count FROM `$table`");
                if ($result) {
                    $counts[$table] = $result->fetch_assoc()['count'];
                } else {
                    $counts[$table] = 'Error';
                }
            } else {
                $counts[$table] = 'N/A';
            }
        }
        
        return $counts;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Handle delete request
$delete_message = '';
$delete_result = null;

if (isset($_POST['delete_data']) && $current_tab == 'delete-data') {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'DELETE') {
        $cleaner = new DatabaseCleaner($db_host, $db_user, $db_pass, $qr_db_name);
        $delete_result = $cleaner->deleteAllData();
        $delete_message = $delete_result['message'];
    } else {
        $delete_message = 'Please type "DELETE" to confirm the operation.';
        $delete_result = ['success' => false];
    }
}

// Handle restore process if form submitted
if (isset($_POST['restore_database']) && isset($_FILES['backup_file']) && $current_tab == 'backup') {
    // Check file upload
    if ($_FILES['backup_file']['error'] == 0) {
        $uploadedFile = $backupDir . basename($_FILES['backup_file']['name']);
        
        // Move uploaded file to backup directory
        if (move_uploaded_file($_FILES['backup_file']['tmp_name'], $uploadedFile)) {
            // Process the uploaded file
            $fileInfo = pathinfo($uploadedFile);
            
            if ($fileInfo['extension'] == 'sql') {
                // Determine which database to restore based on file name
                if (strpos($fileInfo['filename'], 'login_register') !== false) {
                    $restoreDB = new backup_restore($db_host, $db_name, $db_user, $db_pass);
                    $restore_message = $restoreDB->restore($uploadedFile);
                } else if (strpos($fileInfo['filename'], 'qr_attendance_db') !== false) {
                    $restoreDB = new backup_restore($db_host, $qr_db_name, $db_user, $db_pass);
                    $restore_message = $restoreDB->restore($uploadedFile);
                } else {
                    $restore_message = "Unknown database in backup file. Please upload a valid backup.";
                }
            } else {
                $restore_message = "Invalid file format. Please upload a .sql file.";
            }
        } else {
            $restore_message = "Error uploading file. Please try again.";
        }
    } else {
        $restore_message = "Error: " . $_FILES['backup_file']['error'];
    }
}

// Handle form submission for school information
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the current tab from POST data
    $current_tab = $_POST['current_tab'] ?? 'academic';
    
    if (isset($_POST['save_school_info'])) {
        // Ensure school_info table exists with school_id
        $create_table_sql = "CREATE TABLE IF NOT EXISTS school_info (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            school_id INT(11) NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            school_address TEXT,
            school_contact VARCHAR(50),
            school_email VARCHAR(100),
            school_website VARCHAR(255),
            school_logo_path VARCHAR(255),
            school_motto TEXT,
            school_vision TEXT,
            school_mission TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY unique_school (school_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn_qr->query($create_table_sql);

        $school_id = isset($_SESSION['school_id']) ? intval($_SESSION['school_id']) : 1;

        // Check if there's any existing data for this school, if not insert default
        $check_data = "SELECT COUNT(*) as count FROM school_info WHERE school_id = $school_id";
        $result = $conn_qr->query($check_data);
        $row = $result->fetch_assoc();

        if ($row['count'] == 0) {
            $insert_default = "INSERT INTO school_info (
                school_id, school_name, school_address, school_contact, school_email, 
                school_website, school_logo_path, school_motto, school_vision, 
                school_mission, created_at, updated_at
            ) VALUES (
                $school_id, 'School Name', 'School Address', 'Contact Number', 'school@email.com',
                'www.schoolwebsite.com', 'admin/image/SPCPC-logo-trans.png', 
                'School Motto', 'School Vision', 'School Mission', NOW(), NOW()
            )";
            $conn_qr->query($insert_default);
        }

        $school_name = $_POST['school_name'];
        $school_address = $_POST['school_address'];
        $school_contact = $_POST['school_contact'];
        $school_email = $_POST['school_email'];
        $school_website = $_POST['school_website'];
        $school_motto = $_POST['school_motto'];
        $school_vision = $_POST['school_vision'];
        $school_mission = $_POST['school_mission'];

        // Get old school info for logging changes
        $old_school_info_query = "SELECT * FROM school_info WHERE school_id = $school_id";
        $old_school_info_result = $conn_qr->query($old_school_info_query);
        $old_school_info = $old_school_info_result->fetch_assoc();

        // Handle logo upload
        $school_logo_path = '';
        $logo_upload_error = '';
        if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] == 0) {
            $target_dir = "admin/image/";
            $file_extension = strtolower(pathinfo($_FILES["school_logo"]["name"], PATHINFO_EXTENSION));
            $new_filename = "school-logo-" . $school_id . "." . $file_extension;
            $target_file = $target_dir . $new_filename;

            // Check file type
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
            if (in_array($file_extension, $allowed_types)) {
                if (move_uploaded_file($_FILES["school_logo"]["tmp_name"], $target_file)) {
                    $school_logo_path = $target_file;
                } else {
                    $logo_upload_error = 'Failed to move uploaded file.';
                }
            } else {
                $logo_upload_error = 'Invalid file type. Only JPG, JPEG, PNG, GIF allowed.';
            }
        }

        // Update school information
        $update_sql = "UPDATE school_info SET 
            school_name = ?, 
            school_address = ?, 
            school_contact = ?, 
            school_email = ?, 
            school_website = ?, 
            school_motto = ?,
            school_vision = ?,
            school_mission = ?,
            updated_at = NOW()";
        if ($school_logo_path != '') {
            $update_sql .= ", school_logo_path = ?";
        }
        $update_sql .= " WHERE school_id = $school_id";

        $stmt = $conn_qr->prepare($update_sql);
        if ($school_logo_path != '') {
            $stmt->bind_param("sssssssss", 
                $school_name, 
                $school_address, 
                $school_contact, 
                $school_email, 
                $school_website, 
                $school_motto,
                $school_vision,
                $school_mission,
                $school_logo_path
            );
        } else {
            $stmt->bind_param("ssssssss", 
                $school_name, 
                $school_address, 
                $school_contact, 
                $school_email, 
                $school_website, 
                $school_motto,
                $school_vision,
                $school_mission
            );
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "School information updated successfully!";
            if ($school_logo_path != '') {
                $_SESSION['success'] .= " School logo updated.";
            }
            if ($logo_upload_error) {
                $_SESSION['error'] = $logo_upload_error;
            }
            // Log the changes using our helper function
            $changes = [];
            if ($old_school_info['school_name'] != $school_name) $changes['school_name'] = ['old' => $old_school_info['school_name'], 'new' => $school_name];
            if ($old_school_info['school_address'] != $school_address) $changes['school_address'] = ['old' => $old_school_info['school_address'], 'new' => $school_address];
            if ($old_school_info['school_contact'] != $school_contact) $changes['school_contact'] = ['old' => $old_school_info['school_contact'], 'new' => $school_contact];
            if ($old_school_info['school_email'] != $school_email) $changes['school_email'] = ['old' => $old_school_info['school_email'], 'new' => $school_email];
            if ($old_school_info['school_website'] != $school_website) $changes['school_website'] = ['old' => $old_school_info['school_website'], 'new' => $school_website];
            if ($old_school_info['school_motto'] != $school_motto) $changes['school_motto'] = ['old' => $old_school_info['school_motto'], 'new' => $school_motto];
            if ($old_school_info['school_vision'] != $school_vision) $changes['school_vision'] = ['old' => $old_school_info['school_vision'], 'new' => $school_vision];
            if ($old_school_info['school_mission'] != $school_mission) $changes['school_mission'] = ['old' => $old_school_info['school_mission'], 'new' => $school_mission];
            if ($school_logo_path != '') $changes['school_logo_path'] = ['old' => $old_school_info['school_logo_path'], 'new' => $school_logo_path];
            logActivity(
                'settings_change',
                "Updated school information",
                'school_info',
                $school_id,
                $changes
            );
        } else {
            $_SESSION['error'] = "Error updating school information: " . $conn_qr->error;
            if ($logo_upload_error) {
                $_SESSION['error'] .= " Logo error: $logo_upload_error";
            }
            // Log the error
            logActivity(
                'settings_error',
                "Failed to update school information",
                'school_info',
                $school_id,
                ['error' => $conn_qr->error]
            );
        }
        $stmt->close();
        header("Location: settings.php?tab=" . urlencode($current_tab));
        exit();
    }
}

// Get current school information with error handling
$school_id = isset($_SESSION['school_id']) ? intval($_SESSION['school_id']) : 1;
$school_info = null;
try {
    $school_info_query = "SELECT * FROM school_info WHERE school_id = $school_id";
    $school_info_result = $conn_qr->query($school_info_query);
    if ($school_info_result && $school_info_result->num_rows > 0) {
        $school_info = $school_info_result->fetch_assoc();
    } else {
        // Set default values if no data found
        $school_info = [
            'school_name' => 'School Name',
            'school_address' => 'School Address',
            'school_contact' => 'Contact Number',
            'school_email' => 'school@email.com',
            'school_website' => 'www.schoolwebsite.com',
            'school_logo_path' => 'admin/image/SPCPC-logo-trans.png',
            'school_motto' => 'School Motto',
            'school_vision' => 'School Vision',
            'school_mission' => 'School Mission'
        ];
    }
} catch (Exception $e) {
    // If table doesn't exist, set default values
    $school_info = [
        'school_name' => 'School Name',
        'school_address' => 'School Address',
        'school_contact' => 'Contact Number',
        'school_email' => 'school@email.com',
        'school_website' => 'www.schoolwebsite.com',
        'school_logo_path' => 'admin/image/SPCPC-logo-trans.png',
        'school_motto' => 'School Motto',
        'school_vision' => 'School Vision',
        'school_mission' => 'School Mission'
    ];
    // You can also show a message to create the table
    error_log("School info table missing: " . $e->getMessage());
}

// Load academic settings from database if not already set in session
if (!isset($_SESSION['school_year']) || !isset($_SESSION['semester'])) {
    try {
        // Check if user_settings table exists
        $check_table = "SHOW TABLES LIKE 'user_settings'";
        $table_exists = $conn_qr->query($check_table);
        
        if ($table_exists && $table_exists->num_rows > 0) {
            $user_email = $_SESSION['email'] ?? '';
            if (!empty($user_email)) {
                $settings_query = "SELECT school_year, semester FROM user_settings WHERE email = ?";
                $settings_stmt = $conn_qr->prepare($settings_query);
                if ($settings_stmt) {
                    $settings_stmt->bind_param("s", $user_email);
                    $settings_stmt->execute();
                    $settings_result = $settings_stmt->get_result();
                    
                    if ($settings_result && $settings_result->num_rows > 0) {
                        $settings = $settings_result->fetch_assoc();
                        $_SESSION['school_year'] = $settings['school_year'];
                        $_SESSION['semester'] = $settings['semester'];
                    }
                    $settings_stmt->close();
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error loading academic settings: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - QR Code Attendance System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background-color: #808080;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        /* Force cursor pointer */
        .close, button.close, .modal .close, .modal .btn-secondary, .modal-header .close {
            cursor: pointer !important;
        }
        
        /* Main content styles */
        .main {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease;
            width: calc(100% - 260px);
            z-index: 1;
        }

        /* When sidebar is closed */
        .sidebar.close ~ .main {
            margin-left: 78px;
            width: calc(100% - 78px);
        }

        /* FORCE CENTERED PAGINATION - DO NOT CHANGE THIS */
        div.dataTables_wrapper div.dataTables_paginate {
            margin: 15px 0 !important;
            white-space: nowrap !important;
            text-align: center !important;
            display: flex !important;
            justify-content: center !important;
            float: none !important;
            width: 100% !important;
        }
        
        /* FORCE CENTERED INFO TEXT - DO NOT CHANGE THIS */
        div.dataTables_wrapper div.dataTables_info {
            padding-top: 10px !important;
            white-space: nowrap !important;
            text-align: center !important;
            float: none !important;
            width: 100% !important;
        }
        
        /* FORCE pagination container to be full width and centered */
        .dataTables_wrapper .row:last-child {
            display: flex !important;
            justify-content: center !important;
            width: 100% !important;
        }
        
        /* Standardized Pagination Styling */
        .pagination-container {
            margin-top: 15px;
            display: flex;
            justify-content: center;
        }
        
        .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            border-radius: 0.25rem;
        }
        
        .page-link {
            position: relative;
            display: block;
            padding: 0.5rem 0.75rem;
            margin-left: -1px;
            line-height: 1.25;
            color: #098744;
            background-color: #fff;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }
        
        .page-link:hover {
            z-index: 2;
            color: #076a34;
            text-decoration: none;
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
        
        .page-link:focus {
            z-index: 3;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(9, 135, 68, 0.25);
        }
        
        .page-item:first-child .page-link {
            margin-left: 0;
            border-top-left-radius: 0.25rem;
            border-bottom-left-radius: 0.25rem;
        }
        
        .page-item:last-child .page-link {
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }
        
        .page-item.active .page-link {
            z-index: 3;
            color: #fff;
            background-color: #098744;
            border-color: #098744;
        }
        
        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            cursor: auto;
            background-color: #fff;
            border-color: #dee2e6;
        }
        
        /* DataTables Custom Styling */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0;
            margin: 0;
            border: none !important;
            background: transparent !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover,
        .dataTables_wrapper .dataTables_paginate .paginate_button:active {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: transparent !important;
            border: none !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current .page-link {
            background-color: #098744;
            color: white;
            border-color: #098744;
        }
        
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }
        
        .dataTables_wrapper .dataTables_info {
            padding-top: 10px;
            font-size: 0.9rem;
            color: #6c757d;
            text-align: center;
            width: 100%;
        }
        
        /* Center pagination */
        .dataTables_wrapper .dataTables_paginate {
            display: flex !important;
            justify-content: center !important;
            width: 100% !important;
            margin: 15px 0 !important;
            float: none !important;
            text-align: center !important;
        }

        /* Settings container styles - Outer container */
        .settings-outer-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            margin: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            width: calc(100% - 40px);
            height: calc(100vh - 60px);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        /* Settings content styles - Inner container */
        .settings-container {
            background-color: white;
            border-radius: 20px;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        .settings-content {
            height: 100%;
            width: 100%;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #098744 transparent;
            max-height: calc(100vh - 110px);
        }
        
        /* Scrollbar styling for webkit browsers */
        .settings-content::-webkit-scrollbar {
            width: 8px;
        }

        .settings-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .settings-content::-webkit-scrollbar-thumb {
            background-color: #098744;
            border-radius: 4px;
        }
        
        /* Content wrapper */
        .content-wrapper {
            padding: 20px;
            background-color: white;
            min-height: calc(100% - 60px);
        }

        /* Button styles */
        .btn-primary, .btn-success {
            background-color: #098744;
            border-color: #098744;
        }

        .btn-primary:hover, .btn-success:hover {
            background-color: #076a34;
            border-color: #076a34;
        }

        /* Simple Navigation */
        .settings-title {
            text-align: center;
            color: #098744;
            margin-bottom: 25px;
            font-weight: bold;
        }

        .settings-nav {
            display: flex;
            flex-wrap: wrap;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .settings-nav a {
            display: block;
            padding: 10px 15px;
            margin-right: 10px;
            margin-bottom: 5px;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }

        .settings-nav a:hover {
            background-color: #e9ecef;
        }

        .settings-nav a.active {
            background-color: #098744;
            color: white;
        }

        .settings-nav a i {
            margin-right: 5px;
        }

        .tab-content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .info-panel {
            background-color: #e9f7fe;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .main {
                margin-left: 0 !important;
                width: 100% !important;
            }

            .settings-outer-container {
                margin: 10px;
                width: calc(100% - 20px);
                padding: 15px;
            }
            
            .settings-nav {
                flex-direction: column;
            }
            
            .settings-nav a {
                margin-right: 0;
            }
        }

        /* Tab styling */
        .nav-pills .nav-link {
            color: #495057;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            margin-right: 5px;
            transition: all 0.3s ease;
        }
        
        .nav-pills .nav-link.active {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .nav-pills .nav-link:hover:not(.active) {
            background-color: #e9ecef;
            border-color: #ced4da;
        }
        
        .tab-content {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        
        /* Form styling for logs */
        .filter-form {
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .filter-form .form-group {
            margin-bottom: 10px;
        }
        
        .export-buttons {
            margin: 10px 0;
        }
        
        .export-buttons .btn {
            margin-right: 5px;
        }
        
        /* Table styling */
        #logsTable {
            width: 100% !important;
        }
        
        #logsTable th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #098744;
            color: #343a40;
            position: relative;
        }
        
        #logsTable th:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 2px;
            background-color: #098744;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        #logsTable th:hover:after {
            transform: scaleX(1);
        }
        
        #logsTable tbody tr {
            transition: all 0.2s ease;
        }
        
        #logsTable tbody tr:hover {
            background-color: rgba(9, 135, 68, 0.05) !important;
            transform: translateX(5px);
        }
        
        #logsTable .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        #logsTable .btn-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Style for both modal types */
        .log-details-modal .modal-content, #globalQrModal .modal-content {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25) !important;
            animation: none !important;
            border: none !important;
            border-radius: 12px !important;
            background: #ffffff;
            position: relative;
            z-index: 2000; /* Ensure modal is on top */
            overflow: hidden;
        }

        /* Add a distinctive border using a pseudo-element */
        .log-details-modal .modal-content:before, #globalQrModal .modal-content:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 4px solid #098744;
            border-radius: 12px;
            pointer-events: none;
        }

        /* Override Bootstrap's modal backdrop behavior */
        .modal-backdrop {
            display: none !important;
            opacity: 0 !important;
        }

        /* Improve the modal header with more distinctive styling */
        .log-details-modal .modal-header {
            background: linear-gradient(135deg, #098744 0%, #054d24 100%);
            color: white;
            border-bottom: none;
            padding: 1.2rem 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .log-details-modal .modal-header .modal-title {
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Add subtle pattern to modal background */
        .log-details-modal .modal-body {
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23098744' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            padding: 2rem;
            border-radius: 0 0 10px 10px;
        }

        /* Enhanced property-value table */
        .log-details-modal table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .log-details-modal table th {
            background-color: #f1f8f5 !important;
            color: #098744;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            padding: 12px 15px !important;
        }

        .log-details-modal table td {
            padding: 15px !important;
            vertical-align: middle;
        }

        /* Highlighted labels */
        .log-details-modal .property-label {
            font-weight: 600;
            color: #343a40;
        }

        /* Special treatment for old/new values */
        .old-value, .new-value {
            position: relative;
            padding: 8px 12px !important;
            margin: 5px 0;
            border-radius: 4px;
            display: inline-block;
            width: auto;
            min-width: 80px;
            font-weight: 500;
        }

        .old-value {
            background-color: #ffe9e9 !important;
            color: #dc3545;
            border-left: 3px solid #dc3545;
        }

        .new-value {
            background-color: #e8f7ef !important;
            color: #28a745;
            border-left: 3px solid #28a745;
        }

        /* Add a hovering highlight effect to the rows */
        .details-row {
            position: relative;
            transition: all 0.3s ease !important;
        }

        .details-row:hover {
            background-color: rgba(9,135,68,0.03) !important;
            transform: translateX(5px) !important;
            z-index: 1;
        }

        .details-row:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 2px;
            width: 0;
            background: linear-gradient(90deg, #098744, transparent);
            transition: width 0.3s ease;
        }

        .details-row:hover:after {
            width: 100%;
        }

        /* Improved close button */
        .log-details-modal .close {
            position: absolute;
            right: 15px;
            top: 15px;
            background-color: rgba(255,255,255,0.2);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: white;
            opacity: 0.8;
            text-shadow: none;
            font-weight: 400;
        }

        .log-details-modal .close:hover {
            background-color: rgba(255,255,255,0.3);
            transform: rotate(90deg);
            opacity: 1;
        }

        /* Update button styles for a more standout effect */
        .view-details-btn {
            background-color: #098744 !important;
            color: white !important;
            font-weight: 600 !important;
            box-shadow: 0 0 15px rgba(9, 135, 68, 0.4);
            border: none !important;
            padding: 0.4rem 0.8rem !important;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-radius: 4px;
            animation: btn-glow 2s infinite alternate;
            font-size: 0.8rem !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .view-details-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: btn-shine 3s infinite;
        }

        .view-details-btn:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 0 20px rgba(9, 135, 68, 0.6) !important;
            background-color: #076a34 !important;
        }

        @keyframes btn-shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        @keyframes btn-glow {
            0% { box-shadow: 0 0 10px rgba(9, 135, 68, 0.4); }
            100% { box-shadow: 0 0 20px rgba(9, 135, 68, 0.7); }
        }

        /* QR Modal header styling */
        #globalQrModal .qr-modal-header {
            background: linear-gradient(135deg, #098744 0%, #054d24 100%);
            color: white;
            padding: 1rem;
            position: relative;
            border-radius: 8px 8px 0 0;
        }

        /* QR Modal button styling */
        #globalQrModal button {
            background-color: #098744 !important;
            color: white !important;
            font-weight: 500;
            border: none;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        #globalQrModal button:hover {
            background-color: #076a34 !important;
        }

        /* Export button styling for consistency */
        .btn-success {
            background-color: #098744 !important;
            border-color: #098744 !important;
            margin-right: 5px;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: #076a34 !important;
            border-color: #076a34 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        form .btn-success {
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
        }

        .view-details-btn {
            border: 1px solid #ddd;
            background-color: transparent;
            padding: 4px 10px;
            transition: all 0.3s ease;
        }
        
        .view-details-btn:hover {
            background-color: #f8f9fa;
        }

        /* Settings search styles */
        .settings-search {
            max-width: 300px;
        }
        
        .settings-search .input-group {
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
            border-radius: 20px;
            overflow: hidden;
        }
        
        .settings-search .form-control {
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
            border-right: none;
            padding-left: 15px;
        }
        
        .settings-search .input-group-append .btn {
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
            padding-right: 15px;
        }
        
        /* Search results modal */
        .search-results-modal .modal-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #098744;
        }
        
        .search-results-modal .search-result-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            transition: all 0.2s ease;
        }
        
        .search-results-modal .search-result-item:hover {
            background-color: #f8f9fa;
        }
        
        .search-results-modal .search-result-item h5 {
            color: #098744;
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .search-results-modal .search-result-item p {
            color: #666;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .search-results-modal .search-result-item .badge {
            font-size: 80%;
        }
        
        .search-results-modal .no-results {
            padding: 30px;
            text-align: center;
            color: #666;
        }
        
        .search-results-modal .no-results i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }
    </style>
    <!-- Ensure modals display without backdrop -->
    <script>
        $(document).ready(function() {
            // Remove backdrop from all detail modals
            $('.log-details-modal').attr('data-backdrop', 'false');
            
            // When any details modal is about to show
            $('.log-details-modal').on('show.bs.modal', function() {
                // Remove any existing backdrop
                setTimeout(function() {
                    $('.modal-backdrop').remove();
                }, 0);
            });
        });
    </script>
</head>
<body>
    <?php include('./components/sidebar-nav.php'); ?>

    <div class="main">
        <div class="settings-outer-container">
            <div class="settings-container">
                <div class="settings-content">
                    <div class="content-wrapper">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="settings-title mb-0"><i class="fas fa-cog"></i> System Settings</h2>
                            
                            <!-- Settings Search Bar -->
                            <div class="settings-search">
                                <form id="settingsSearchForm" class="form-inline">
                                    <div class="input-group">
                                        <input type="text" id="settingsSearchInput" class="form-control" placeholder="Find settings..." aria-label="Search settings">
                                        <div class="input-group-append">
                                            <button class="btn btn-success" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Success/Error Messages -->
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>
                        
                        <!-- Simple Navigation -->
                        <div class="settings-nav">
                            <a href="settings.php?tab=academic" class="<?php echo $current_tab == 'academic' ? 'active' : ''; ?>">
                                <i class="fas fa-calendar-alt"></i> Academic Settings
                            </a>
                            <a href="settings.php?tab=school-info" class="<?php echo $current_tab == 'school-info' ? 'active' : ''; ?>">
                                <i class="fas fa-school"></i> School Information
                            </a>
                            <a href="settings.php?tab=activity-logs" class="<?php echo $current_tab == 'activity-logs' ? 'active' : ''; ?>">
                                <i class="fas fa-history"></i> Activity Logs
                            </a>
                            <a href="settings.php?tab=backup" class="<?php echo $current_tab == 'backup' ? 'active' : ''; ?>">
                                <i class="fas fa-database"></i> Backup & Restore
                            </a>
                            <a href="settings.php?tab=delete-data" class="<?php echo $current_tab == 'delete-data' ? 'active' : ''; ?>">
                                <i class="fas fa-trash-alt"></i> Delete Data
                            </a>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content">
                            <?php if ($current_tab == 'academic'): ?>
                                <!-- Academic Settings Tab -->
                                <h4><i class="fas fa-calendar-alt"></i> Academic Period Settings</h4>
                                
                                <div class="info-panel">
                                <div><strong>Current School Year:</strong> <span id="displayedSchoolYear"><?= isset($_SESSION['school_year']) ? $_SESSION['school_year'] : date('Y').'-'.(date('Y')+1) ?></span></div>
                                <div><strong>Current Semester:</strong> <span id="displayedSemester"><?= isset($_SESSION['semester']) ? $_SESSION['semester'] : '1st Semester' ?></span></div>
                            </div>
                            
                            <form id="academicSettingsForm">
                                <div class="form-group">
                                    <label for="schoolYear">School Year:</label>
                                    <select class="form-control" id="schoolYear" name="schoolYear">
                                        <?php 
                                        $currentYear = date('Y');
                                        for ($i = 0; $i < 5; $i++) {
                                            $startYear = $currentYear - $i;
                                            $endYear = $startYear + 1;
                                            $yearOption = $startYear . '-' . $endYear;
                                            $selected = (isset($_SESSION['school_year']) && $_SESSION['school_year'] == $yearOption) ? 'selected' : '';
                                            echo "<option value=\"$yearOption\" $selected>$yearOption</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="semester">Semester:</label>
                                    <select class="form-control" id="semester" name="semester">
                                        <option value="1st Semester" <?= (isset($_SESSION['semester']) && $_SESSION['semester'] == '1st Semester') ? 'selected' : '' ?>>1st Semester</option>
                                        <option value="2nd Semester" <?= (isset($_SESSION['semester']) && $_SESSION['semester'] == '2nd Semester') ? 'selected' : '' ?>>2nd Semester</option>
                                    </select>
                                </div>
                                    <button type="button" id="setAcademicSettings" class="btn btn-success btn-block">Save Academic Settings</button>
                            </form>
                                <div id="academicSettingsAlert" class="alert mt-3" style="display: none;"></div>
                            
                            <?php elseif ($current_tab == 'school-info'): ?>
                                <!-- School Information Tab -->
                                <h4><i class="fas fa-school"></i> School Information</h4>
                                
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <input type="hidden" name="current_tab" value="school-info">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="school_name">School Name</label>
                                                <input type="text" class="form-control" id="school_name" name="school_name" 
                                                       value="<?php echo ($school_info['school_name'] && $school_info['school_name'] != 'School Name') ? htmlspecialchars($school_info['school_name']) : ''; ?>" 
                                                       placeholder="Enter school name" required>
                        </div>
                                            <div class="form-group">
                                                <label for="school_address">School Address</label>
                                                <textarea class="form-control" id="school_address" name="school_address" 
                                                          rows="3" placeholder="Enter school address"><?php echo ($school_info['school_address'] && $school_info['school_address'] != 'School Address') ? htmlspecialchars($school_info['school_address']) : ''; ?></textarea>
                    </div>
                                            <div class="form-group">
                                                <label for="school_contact">Contact Number</label>
                                                <input type="text" class="form-control" id="school_contact" name="school_contact" 
                                                       value="<?php echo ($school_info['school_contact'] && $school_info['school_contact'] != 'Contact Number') ? htmlspecialchars($school_info['school_contact']) : ''; ?>" 
                                                       placeholder="Enter contact number">
                </div>
                                            <div class="form-group">
                                                <label for="school_email">Email Address</label>
                                                <input type="email" class="form-control" id="school_email" name="school_email" 
                                                       value="<?php echo ($school_info['school_email'] && $school_info['school_email'] != 'school@email.com') ? htmlspecialchars($school_info['school_email']) : ''; ?>" 
                                                       placeholder="Enter email address">
            </div>
                                            <div class="form-group">
                                                <label for="school_website">Website</label>
                                                <input type="url" class="form-control" id="school_website" name="school_website" 
                                                       value="<?php echo ($school_info['school_website'] && $school_info['school_website'] != 'www.schoolwebsite.com') ? htmlspecialchars($school_info['school_website']) : ''; ?>" 
                                                       placeholder="Enter website URL">
        </div>
    </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="school_logo">School Logo</label>
                                                <?php if (!empty($school_info['school_logo_path'])): ?>
                                                    <div class="mb-2">
                                                        <img src="<?php echo htmlspecialchars($school_info['school_logo_path']); ?>" 
                                                             alt="Current School Logo" class="img-thumbnail" style="max-height: 150px; max-width: 200px;">
                                                    </div>
                                                <?php endif; ?>
                                                <input type="file" class="form-control" id="school_logo" name="school_logo" accept="image/*">
                                                <small class="text-muted">Leave empty to keep current logo</small>
                                            </div>
                                            <div class="form-group">
                                                <label for="school_motto">School Motto</label>
                                                <input type="text" class="form-control" id="school_motto" name="school_motto" 
                                                       value="<?php echo ($school_info['school_motto'] && $school_info['school_motto'] != 'School Motto') ? htmlspecialchars($school_info['school_motto']) : ''; ?>" 
                                                       placeholder="Enter school motto">
                                            </div>
                                            <div class="form-group">
                                                <label for="school_vision">School Vision</label>
                                                <textarea class="form-control" id="school_vision" name="school_vision" 
                                                          rows="3" placeholder="Enter school vision"><?php echo ($school_info['school_vision'] && $school_info['school_vision'] != 'School Vision') ? htmlspecialchars($school_info['school_vision']) : ''; ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="school_mission">School Mission</label>
                                                <textarea class="form-control" id="school_mission" name="school_mission" 
                                                          rows="3" placeholder="Enter school mission"><?php echo ($school_info['school_mission'] && $school_info['school_mission'] != 'School Mission') ? htmlspecialchars($school_info['school_mission']) : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="save_school_info" class="btn btn-success btn-block">Save School Information</button>
                                </form>
                            
                            <?php elseif ($current_tab == 'activity-logs'): ?>
                                <!-- Activity Logs Tab -->
                                <h4><i class="fas fa-history"></i> Activity Logs</h4>
                                
                                <?php
                                // Include ActivityLogger class
                                require_once('includes/ActivityLogger.php');
                                
                                // Use email from session to identify the user (instead of user_id)
                                // Get user ID from the email
                                $current_user_id = null;
                                
                                // Ensure we have a valid connection or re-establish it
                                // Don't ping a potentially closed connection - just recreate it to be safe
                                $conn_login = mysqli_connect("localhost", "root", "", "login_register");
                                if (!$conn_login) {
                                    error_log("Failed to connect to login_register database: " . mysqli_connect_error());
                                    $current_user_id = 1; // Default fallback
                                } else {
                                    try {
                                    $user_query = "SELECT id FROM users WHERE email = ?";
                                    $user_stmt = $conn_login->prepare($user_query);
                                    if ($user_stmt) {
                                        $user_stmt->bind_param("s", $_SESSION['email']);
                                        $user_stmt->execute();
                                        $user_result = $user_stmt->get_result();
                                        $user_data = $user_result->fetch_assoc();
                                        $current_user_id = $user_data['id'] ?? null;
                                        $user_stmt->close();
                                        }
                                    } catch (Exception $e) {
                                        error_log("Database error in activity logs: " . $e->getMessage());
                                        $current_user_id = 1; // Default fallback
                                    }
                                }
                                
                                $activity_logger = new ActivityLogger($conn_qr, $current_user_id);
                                
                                // Get filter parameters
                                $start_date = $_GET['start_date'] ?? null;
                                $end_date = $_GET['end_date'] ?? null;
                                $action_type = $_GET['action_type'] ?? null;
                                $user_id = $_GET['user_id'] ?? null;
                                
                                // Build query with modified JOIN to account for users table in login_register database
                                $where_conditions = [];
                                $params = [];
                                $types = "";
                                
                                if ($start_date && $end_date) {
                                    $where_conditions[] = "al.created_at BETWEEN ? AND ?";
                                    $params[] = $start_date;
                                    $params[] = $end_date;
                                    $types .= "ss";
                                }
                                
                                if ($action_type) {
                                    $where_conditions[] = "al.action_type = ?";
                                    $params[] = $action_type;
                                    $types .= "s";
                                }
                                
                                if ($user_id) {
                                    $where_conditions[] = "al.user_id = ?";
                                    $params[] = $user_id;
                                    $types .= "i";
                                }
                                
                                $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
                                
                                // Updated query to explicitly select required fields
                                $sql = "SELECT 
                                    al.id,
                                    al.user_id,
                                    al.action_type,
                                    al.action_description,
                                    al.affected_table,
                                    al.affected_id,
                                    al.user_agent,
                                    al.created_at,
                                    al.additional_data
                                    FROM activity_logs al
                                    $where_clause
                                    ORDER BY al.created_at DESC
                                    LIMIT 1000";
                                
                                $stmt = $conn_qr->prepare($sql);
                                if (!empty($params)) {
                                    $stmt->bind_param($types, ...$params);
                                }
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $logs = $result->fetch_all(MYSQLI_ASSOC);
                                
                                // After fetching logs, get user info for each log
                                $user_emails = [];
                                $user_names = [];
                                
                                // If we have user data in login_register database
                                // Recreate connection to ensure it's fresh and valid
                                $conn_login = mysqli_connect("localhost", "root", "", "login_register");
                                
                                if ($conn_login && !$conn_login->connect_error) {
                                    $user_ids = [];
                                    foreach ($logs as $log) {
                                        if (!empty($log['user_id']) && !in_array($log['user_id'], $user_ids)) {
                                            $user_ids[] = $log['user_id'];
                                        }
                                    }
                                    
                                    if (!empty($user_ids)) {
                                        try {
                                        $ids_string = implode(',', $user_ids);
                                        // Fix query to use only columns that exist
                                        $user_data_query = "SELECT id, username, email, full_name FROM users WHERE id IN ($ids_string)";
                                        $user_data_result = $conn_login->query($user_data_query);
                                        
                                        if ($user_data_result && $user_data_result->num_rows > 0) {
                                            while ($user_row = $user_data_result->fetch_assoc()) {
                                                $user_emails[$user_row['id']] = $user_row['email'];
                                                $user_names[$user_row['id']] = $user_row['username'] ?: $user_row['full_name'] ?: $user_row['email']; // Use username, then full_name, then email as fallback
                                            }
                                            }
                                        } catch (Exception $e) {
                                            error_log("Database error fetching user data for activity logs: " . $e->getMessage());
                                        }
                                    }
                                }
                                
                                // Get unique action types for filter
                                $action_types_sql = "SELECT DISTINCT action_type FROM activity_logs WHERE action_type IS NOT NULL AND action_type != '' ORDER BY action_type";
                                $action_types_result = $conn_qr->query($action_types_sql);
                                $action_types = $action_types_result->fetch_all(MYSQLI_ASSOC);
                                
                                // Get users from QR database for filter dropdown
                                $users = [];
                                $users_sql = "SELECT DISTINCT user_id FROM activity_logs WHERE user_id IS NOT NULL";
                                $users_result = $conn_qr->query($users_sql);
                                
                                if ($users_result && $users_result->num_rows > 0) {
                                    while ($user_row = $users_result->fetch_assoc()) {
                                        $users[] = [
                                            'id' => $user_row['user_id'],
                                            'name' => isset($user_emails[$user_row['user_id']]) ? $user_emails[$user_row['user_id']] : 'User ' . $user_row['user_id']
                                        ];
                                    }
                                }
                                ?>
                                
                                <!-- Filters -->
                                <form method="GET" action="settings.php" class="mb-4">
                                    <input type="hidden" name="tab" value="activity-logs">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="date_range" class="form-label">Date Range</label>
                                                <input type="text" class="form-control" id="date_range" name="date_range" 
                                                       value="<?php echo $start_date && $end_date ? "$start_date - $end_date" : ''; ?>">
                                                <input type="hidden" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                                <input type="hidden" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="action_type" class="form-label">Action Type</label>
                                                <select class="form-control" id="action_type" name="action_type">
                                                    <option value="">All Actions</option>
                                                    <?php 
                                                    // Track action types we've already displayed to avoid duplicates
                                                    $displayed_action_types = [];
                                                    
                                                    foreach ($action_types as $type): 
                                                        $action_type_value = trim($type['action_type']);
                                                        
                                                        // Skip empty, null, or already displayed types
                                                        if (empty($action_type_value) || in_array($action_type_value, $displayed_action_types)) {
                                                            continue;
                                                        }
                                                        
                                                        // Add to displayed list to prevent duplicates
                                                        $displayed_action_types[] = $action_type_value;
                                                                                        
                                                        // Format the display text
                                                        $display_text = ucwords(str_replace('_', ' ', $action_type_value));
                                                    ?>
                                                        <option value="<?php echo htmlspecialchars($action_type_value); ?>"
                                                                <?php echo $action_type === $action_type_value ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($display_text); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="user_id" class="form-label">User</label>
                                                <select class="form-control" id="user_id" name="user_id">
                                                    <option value="">All Users</option>
                                                    <?php foreach ($users as $user): ?>
                                                        <option value="<?php echo $user['id']; ?>"
                                                                <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($user['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="submit" class="btn btn-success d-block">Apply Filters</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <!-- Export Options -->
                                <div class="mb-3">
                                    <form method="POST" class="d-inline" action="export_logs.php">
                                        <input type="hidden" name="start_date" value="<?php echo $start_date ?? ''; ?>">
                                        <input type="hidden" name="end_date" value="<?php echo $end_date ?? ''; ?>">
                                        <input type="hidden" name="action_type" value="<?php echo $action_type ?? ''; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $user_id ?? ''; ?>">
                                        
                                      
                                    </form>
                                </div>

                                <!-- Activity Logs Table -->
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="logsTable">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>User</th>
                                                <th>Action Type</th>
                                                <th>Description</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($logs as $log): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                                    <td>
                                                        <?php if ($log['user_id']): ?>
                                                            <strong><?php echo htmlspecialchars($user_names[$log['user_id']] ?? 'User '.$log['user_id']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($user_emails[$log['user_id']] ?? 'No email available'); ?></small>
                                                        <?php else: ?>
                                                            <strong>System</strong>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            // Make sure we display the action_type properly
                                                            $display_action_type = $log['action_type'];
                                                            echo htmlspecialchars($display_action_type); 
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($log['action_description']); ?></td>
                                                    <td>
                                                        <?php if ($log['additional_data']): ?>
                                                            <button type="button" class="btn btn-sm view-details-btn" 
                                                                    data-toggle="modal" 
                                                                    data-target="#detailsModal<?php echo $log['id']; ?>">
                                                                <i class="fas fa-search-plus mr-1"></i> Details
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Details Modals -->
                                <?php foreach ($logs as $log): ?>
                                    <?php if ($log['additional_data']): ?>
                                        <div class="modal fade log-details-modal" id="detailsModal<?php echo $log['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?php echo $log['id']; ?>" aria-hidden="true" data-backdrop="false">
                                            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="modalLabel<?php echo $log['id']; ?>">
                                                            <i class="fas fa-info-circle text-info mr-2"></i>Activity Details
                                                            <small class="text-muted ml-2"><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></small>
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" data-modal-id="detailsModal<?php echo $log['id']; ?>">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3 p-2 bg-light rounded">
                                                            <div class="d-flex align-items-center mb-2">
                                                                <div class="mr-3">
                                                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-0"><?php echo htmlspecialchars($user_names[$log['user_id']] ?? 'System'); ?></h6>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($log['action_type']); ?></small>
                                                                </div>
                                                            </div>
                                                            <p class="mb-0 pl-5"><?php echo htmlspecialchars($log['action_description']); ?></p>
                                                        </div>
                                                        
                                                        <?php 
                                                        $additional_data = json_decode($log['additional_data'], true);
                                                        if (is_array($additional_data)):
                                                            // Remove the IP address from being displayed
                                                            if (isset($additional_data['ip_address'])) {
                                                                unset($additional_data['ip_address']);
                                                            }
                                                        ?>
                                                            <div class="table-responsive details-table-container">
                                                                <table class="table table-bordered table-hover">
                                                                    <thead class="thead-light">
                                                                        <tr>
                                                                            <th style="width: 30%;">Property</th>
                                                                            <th style="width: 70%;">Value</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($additional_data as $key => $value): ?>
                                                                            <tr class="details-row">
                                                                                <th class="bg-light"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></th>
                                                                                <td>
                                                                                    <?php
                                                                                    if (is_array($value)) {
                                                                                        if (isset($value['old']) && isset($value['new'])) {
                                                                                            echo '<div class="d-flex flex-column">';
                                                                                            echo '<div class="change-value old-value"><span class="badge badge-danger mr-2">Old</span> <span class="text-danger"><del>' . htmlspecialchars($value['old']) . '</del></span></div>';
                                                                                            echo '<div class="change-value new-value"><span class="badge badge-success mr-2">New</span> <span class="text-success">' . htmlspecialchars($value['new']) . '</span></div>';
                                                                                            echo '</div>';
                        } else {
                                                                                            echo '<div class="details-list">';
                                                                                            echo '<ul class="list-group">';
                                                                                            foreach ($value as $subKey => $subValue) {
                                                                                                if (is_array($subValue)) {
                                                                                                    $subValue = json_encode($subValue);
                                                                                                }
                                                                                                echo '<li class="list-group-item p-2 d-flex"><span class="font-weight-bold mr-2">' . htmlspecialchars(ucwords(str_replace('_', ' ', $subKey))) . ':</span> <span class="text-break">' . htmlspecialchars($subValue) . '</span></li>';
                                                                                            }
                                                                                            echo '</ul>';
                                                                                            echo '</div>';
                                                                                        }
                                                                                    } else {
                                                                                        echo '<div class="details-value">' . htmlspecialchars($value) . '</div>';
                                                                                    }
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="alert alert-info details-simple-data">
                                                                <i class="fas fa-info-circle mr-2"></i>
                                                                <?php echo htmlspecialchars($log['additional_data']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <small class="text-muted mr-auto">Attempts: <?php echo isset($log['attempts']) ? htmlspecialchars($log['attempts']) : 0; ?></small>
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-modal-id="detailsModal<?php echo $log['id']; ?>">
                                                            <i class="fas fa-times mr-1"></i>Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            
                            <?php elseif ($current_tab == 'backup'): ?>
                                <!-- Backup & Restore Tab -->
                                <h4><i class="fas fa-database"></i> Backup & Restore Database</h4>
                                
                                <?php if (!empty($backup_message)): ?>
                                <div class="alert alert-success">
                                    <p><?php echo $backup_message; ?></p>
                                    <p>Download the backup files:</p>
                                    <ul>
                                        <li><a href="backup/<?php echo $loginBackup; ?>" class="btn btn-sm btn-primary">Login Database Backup</a></li>
                                        <li><a href="backup/<?php echo $qrBackup; ?>" class="btn btn-sm btn-primary">QR Attendance Database Backup</a></li>
                                    </ul>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($restore_message)): ?>
                                <div class="alert alert-info">
                                    <p><?php echo $restore_message; ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <h5 class="card-title">Backup Database</h5>
                                                <p class="card-text">Create a backup of your current database.</p>
                                                <a href="settings.php?tab=backup&action=backup" class="btn btn-success">
                                                    <i class="fas fa-download"></i> Create Backup
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <h5 class="card-title">Restore Database</h5>
                                                <p class="card-text">Restore from a previous backup file.</p>
                                                <form action="settings.php?tab=backup" method="POST" enctype="multipart/form-data">
                                                    <div class="custom-file mb-3">
                                                        <input type="file" class="custom-file-input" id="backup_file" name="backup_file" accept=".sql" required>
                                                        <label class="custom-file-label" for="backup_file">Choose backup file...</label>
                                                    </div>
                                                    <button type="submit" name="restore_database" class="btn btn-warning" onclick="return confirm('Are you sure you want to restore the database? This will overwrite current data.')">
                                                        <i class="fas fa-upload"></i> Restore Backup
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                
                                <div class="alert alert-warning">
                                    <h5><i class="fas fa-exclamation-triangle"></i> IMPORTANT NOTE:</h5>
                                    <ul class="mb-0">
                                        <li>Make sure to regularly backup your databases to prevent data loss.</li>
                                        <li>No inputs should be made during the backup or restore process.</li>
                                        <li>Make sure to download and save your backup files in a secure location.</li>
                                        <li>Restoring a database will overwrite all current data with the data from the backup file.</li>
                                        <li><strong>DELETE DATA:</strong> Permanently deletes ALL system data (students, instructors, courses, school info, attendance). This is a complete system reset. Always create a backup first!</li>
                                    </ul>
                                </div>
                            
                            <?php elseif ($current_tab == 'delete-data'): ?>
                                <!-- Delete Data Tab -->
                                <h4><i class="fas fa-trash-alt"></i> Complete System Reset - Delete All Data</h4>
                                
                                <?php 
                                // Get current table counts for delete tab
                                if ($current_tab == 'delete-data') {
                                    $cleaner = new DatabaseCleaner($db_host, $db_user, $db_pass, $qr_db_name);
                                    $tableCounts = $cleaner->getTableCounts();
                                }
                                ?>
                                
                                <?php if($delete_message): ?>
                                    <div class="alert alert-<?php echo $delete_result['success'] ? 'success' : 'danger'; ?>">
                                        <strong><?php echo $delete_result['success'] ? 'Success!' : 'Error!'; ?></strong>
                                        <p><?php echo htmlspecialchars($delete_message); ?></p>
                                        
                                        <?php if(isset($delete_result['deleted']) && !empty($delete_result['deleted'])): ?>
                                            <div class="mt-3">
                                                <h6>Tables Cleared:</h6>
                                                <div style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa; border-radius: 5px; padding: 10px;">
                                                    <?php foreach($delete_result['deleted'] as $table): ?>
                                                        <div>✓ <?php echo htmlspecialchars($table); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if(isset($delete_result['skipped']) && !empty($delete_result['skipped'])): ?>
                                            <div class="mt-3">
                                                <h6>Tables Skipped:</h6>
                                                <div style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa; border-radius: 5px; padding: 10px;">
                                                    <?php foreach($delete_result['skipped'] as $table): ?>
                                                        <div>- <?php echo htmlspecialchars($table); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="alert alert-danger">
                                    <h5><i class="fas fa-exclamation-triangle"></i> COMPLETE SYSTEM RESET - DANGER ZONE</h5>
                                    <p><strong>This action will permanently delete ALL data from the attendance system - this is a COMPLETE SYSTEM RESET!</strong></p>
                                    <p>This deletes data from 15 tables including:</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="mb-0">
                                                <li><strong>ALL STUDENT RECORDS</strong> (tbl_student)</li>
                                                <li><strong>ALL INSTRUCTOR DATA</strong> (tbl_instructors, tbl_instructor_subjects)</li>
                                                <li><strong>ALL COURSES & SUBJECTS</strong> (courses, tbl_subjects)</li>
                                                <li><strong>SCHOOL INFORMATION</strong> (school_info)</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="mb-0">
                                                <li><strong>ALL ATTENDANCE DATA</strong> (tbl_attendance, attendance_logs, attendance_grades)</li>
                                                <li><strong>FACE RECOGNITION DATA</strong> (tbl_face_recognition_logs, tbl_face_verification_logs)</li>
                                                <li><strong>ACTIVITY LOGS & USER LOGS</strong> (activity_logs, tbl_user_logs)</li>
                                                <li><strong>OFFLINE DATA & SESSIONS</strong> (offline_data, attendance_sessions)</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p class="mt-2 mb-0"><strong>This is essentially starting fresh with an empty system! This action cannot be undone!</strong></p>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-info text-white">
                                                <h5 class="mb-0"><i class="fas fa-table"></i> Current Database Status</h5>
                                            </div>
                                            <div class="card-body">
                                                <?php if (isset($tableCounts)): ?>
                                                    <?php foreach($tableCounts as $table => $count): ?>
                                                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                            <span><?php echo htmlspecialchars($table); ?>:</span>
                                                            <span class="badge badge-<?php echo $count > 0 ? 'primary' : 'secondary'; ?>">
                                                                <?php echo htmlspecialchars($count); ?> records
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-success mb-3">
                                            <h5><i class="fas fa-shield-alt"></i> WHAT'S PRESERVED</h5>
                                            <p class="mb-0">Your login credentials and admin accounts will NOT be deleted. Only attendance system data will be removed.</p>
                                        </div>
                                        
                                        <div class="alert alert-warning">
                                            <h5><i class="fas fa-info-circle"></i> Before proceeding</h5>
                                            <ul class="mb-0">
                                                <li><strong>Create a backup first!</strong> Use the backup function to save your current data.</li>
                                                <li>Make sure all users are logged out of the system.</li>
                                                <li>Inform all users about the data deletion schedule.</li>
                                                <li>The user_settings table will be preserved to maintain system configuration.</li>
                                                <li>This operation will reset all auto-increment values to 1.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Confirmation Form at Bottom -->
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Final Confirmation Required</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" id="deleteDataForm">
                                            <input type="hidden" name="current_tab" value="delete-data">
                                            <div class="text-center mb-3">
                                                <p class="h6">To proceed with deleting all data, type <strong>DELETE</strong> in the box below:</p>
                                            </div>
                                            
                                            <div class="row justify-content-center">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="text" 
                                                               name="confirm_delete" 
                                                               id="confirmDeleteInput"
                                                               class="form-control text-center" 
                                                               placeholder="Type DELETE to confirm"
                                                               style="text-transform: uppercase; font-weight: bold;"
                                                               required>
                                                    </div>
                                                    
                                                    <button type="button" id="deleteDataBtn" class="btn btn-danger btn-block btn-lg">
                                                        <i class="fas fa-trash-alt"></i> DELETE ALL DATA
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results Modal -->
    <div class="modal fade search-results-modal" id="searchResultsModal" tabindex="-1" role="dialog" aria-labelledby="searchResultsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchResultsModalLabel"><i class="fas fa-search"></i> Search Results</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="searchResultsContainer">
                    <!-- Results will be inserted here by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Delete Warning Modal -->
    <div class="modal fade" id="deleteWarningModalSettings" tabindex="-1" role="dialog" aria-labelledby="deleteWarningModalSettingsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border: 3px solid #dc3545; border-radius: 15px;">
                <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-bottom: none; border-radius: 12px 12px 0 0;">
                    <h4 class="modal-title" id="deleteWarningModalSettingsLabel" style="font-weight: bold;">
                        <i class="fas fa-exclamation-triangle" style="color: #fff; margin-right: 10px; font-size: 1.2em;"></i>
                        Critical Warning
                    </h4>
                </div>
                <div class="modal-body" style="padding: 30px; background-color: #fff8f8;">
                    <div class="text-center mb-4">
                        <i class="fas fa-trash-alt" style="font-size: 4em; color: #dc3545; margin-bottom: 20px;"></i>
                        <h5 style="color: #dc3545; font-weight: bold; margin-bottom: 20px;">
                            Are you sure you want to delete ALL system data?
                        </h5>
                    </div>
                    
                    <div class="alert alert-danger" style="border-left: 4px solid #dc3545;">
                        <h6><strong>This action will permanently delete:</strong></h6>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="mb-0">
                                    <li><strong>ALL Student Records</strong></li>
                                    <li><strong>ALL Instructor Data</strong></li>
                                    <li><strong>ALL Courses & Subjects</strong></li>
                                    <li><strong>School Information</strong></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="mb-0">
                                    <li><strong>ALL Attendance Data</strong></li>
                                    <li><strong>Face Recognition Data</strong></li>
                                    <li><strong>Activity & User Logs</strong></li>
                                    <li><strong>Offline Data & Sessions</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-success" style="border-left: 4px solid #28a745;">
                        <h6><strong><i class="fas fa-shield-alt"></i> Admin Accounts Are SAFE:</strong></h6>
                        <p class="mb-0">Your login credentials and admin accounts will NOT be deleted. Only attendance system data will be removed.</p>
                    </div>
                    
                    <div class="alert alert-warning" style="border-left: 4px solid #ffc107;">
                        <h6><strong>Before proceeding:</strong></h6>
                        <ul class="mb-0">
                            <li>Have you created a backup of your data?</li>
                            <li>Are all users logged out of the system?</li>
                            <li>Do you understand this action cannot be undone?</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6; border-radius: 0 0 12px 12px; padding: 20px;">
                    <button type="button" class="btn btn-success btn-lg" data-dismiss="modal" style="padding: 12px 30px; font-weight: bold;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger btn-lg" id="proceedToDeletePage" style="padding: 12px 30px; font-weight: bold;">
                        <i class="fas fa-trash-alt"></i> Continue to Delete Page
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo asset_url('js/popper.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.min.js'); ?>"></script>
    <!-- Add DateRangePicker and DataTables -->
    <script src="<?php echo asset_url('js/moment.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/daterangepicker.min.js'); ?>"></script>
    <link href="<?php echo asset_url('css/daterangepicker.css'); ?>" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <!-- DataTables Button Libraries -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="<?php echo asset_url('css/buttons.bootstrap4.min.css'); ?>" rel="stylesheet">

    <script>
        // Check if jQuery is loaded
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded!');
            alert('Error: jQuery library is not loaded. Please refresh the page.');
        } else {
            console.log('jQuery is loaded successfully, version:', jQuery.fn.jquery);
        }
        
        $(document).ready(function() {
            // Initialize DataTable with standardized pagination
            const table = $('#logsTable').DataTable({
                paging: true,
                pageLength: 10,
                ordering: true,
                order: [[0, 'desc']], // Set default order to first column (Date & Time) in descending order
                info: true,
                searching: true,
                lengthChange: true,
                // Use Bootstrap pagination styling
                pagingType: 'simple_numbers',
                dom: '<"row"<"col-md-6"B><"col-md-6"f>>' +
                     '<"row"<"col-12"tr>>' +
                     '<"row justify-content-center"<"col-md-auto"i><"col-md-auto"p>>',
                buttons: [      
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn-success',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn-success',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Print',
                        className: 'btn-success',
                        exportOptions: {
                            columns: ':visible'
                        }
                    }
                ],
                drawCallback: function() {
                    // FORCE center the pagination
                    $('.dataTables_paginate').css({
                        'text-align': 'center !important',
                        'float': 'none !important',
                        'margin': '10px auto !important',
                        'display': 'flex !important',
                        'justify-content': 'center !important',
                        'width': '100% !important'
                    });
                    
                    // FORCE center the info
                    $('.dataTables_info').css({
                        'text-align': 'center !important',
                        'width': '100% !important',
                        'float': 'none !important',
                        'padding-top': '10px !important'
                    });
                    
                    styleDataTablePagination();
                }
            });
            
            // Place buttons in the container beside reset button
            $('.dt-buttons').addClass('mt-3 mb-3 text-center');
            
            // Add styling to DataTable buttons
            $('.dt-buttons .btn-success').css({
                'background-color': '#098744',
                'border-color': '#098744',
                'margin-right': '5px'
            });
            
            // Add extra space between buttons
            $('.dt-button').css({
                'margin-right': '5px'
            });
            
            // Ensure DataTables pagination is properly styled
            function styleDataTablePagination() {
                // Apply consistent styling to all DataTables pagination elements
                $('.dataTables_wrapper .dataTables_paginate').addClass('pagination-container');
                $('.dataTables_wrapper .dataTables_paginate').css({
                    'display': 'flex',
                    'justify-content': 'center',
                    'width': '100%',
                    'float': 'none',
                    'text-align': 'center',
                    'margin': '20px auto'
                });
                
                $('.dataTables_wrapper .dataTables_paginate .pagination').addClass('justify-content-center');
                
                $('.dataTables_wrapper .dataTables_paginate .paginate_button').each(function() {
                    // Only add page-link class if it doesn't already have it
                    var link = $(this).find('a');
                    if (link.length === 0) {
                        $(this).addClass('page-item');
                        var text = $(this).text();
                        $(this).html('<a class="page-link" href="#">' + text + '</a>');
                    } else if (!link.hasClass('page-link')) {
                        link.addClass('page-link');
                        $(this).addClass('page-item');
                    }
                    
                    // Add active class to current page
                    if ($(this).hasClass('current')) {
                        $(this).addClass('active');
                    }
                    
                    // Add disabled class to disabled pages
                    if ($(this).hasClass('disabled')) {
                        $(this).addClass('disabled');
                    }
                });
                
                // Also center the info text
                $('.dataTables_wrapper .dataTables_info').css({
                    'text-align': 'center',
                    'width': '100%',
                    'margin-bottom': '10px'
                });
            }
            
            // Settings search functionality
            $("#settingsSearchForm").on("submit", function(e) {
                e.preventDefault();
                const searchQuery = $("#settingsSearchInput").val().trim();
                
                if (searchQuery.length < 2) {
                    alert("Please enter at least 2 characters to search");
                    return;
                }
                
                // Log the search activity via AJAX
                $.ajax({
                    url: "endpoint/log-search.php",
                    type: "POST",
                    data: {
                        search_query: searchQuery,
                        search_type: "settings_search"
                    },
                    dataType: "json"
                });
                
                // Define settings categories for search
                const settingsData = [
                    {
                        category: "Academic Settings",
                        items: [
                            { name: "School Year", description: "Set the current academic year", tab: "academic" },
                            { name: "Semester", description: "Set the current semester", tab: "academic" }
                        ]
                    },
                    {
                        category: "School Information",
                        items: [
                            { name: "School Name", description: "Update school name and branding", tab: "school-info" },
                            { name: "School Logo", description: "Upload or update school logo", tab: "school-info" },
                            { name: "School Address", description: "Update school location and contact details", tab: "school-info" },
                            { name: "School Contact", description: "Update phone numbers and contact information", tab: "school-info" },
                            { name: "School Email", description: "Update official school email address", tab: "school-info" },
                            { name: "School Website", description: "Update school website URL", tab: "school-info" },
                            { name: "School Motto", description: "Update school motto or slogan", tab: "school-info" },
                            { name: "School Vision", description: "Update school vision statement", tab: "school-info" },
                            { name: "School Mission", description: "Update school mission statement", tab: "school-info" }
                        ]
                    },
                    {
                        category: "Activity Logs",
                        items: [
                            { name: "Activity Log Filtering", description: "Filter activity logs by date, user, or action type", tab: "activity-logs" },
                            { name: "Export Logs", description: "Export activity logs to CSV, Excel, or PDF", tab: "activity-logs" }
                        ]
                    },
                    {
                        category: "Backup & Restore",
                        items: [
                            { name: "Database Backup", description: "Create a backup of the system databases", tab: "backup" },
                            { name: "Database Restore", description: "Restore from a previous database backup", tab: "backup" },
                            { name: "Delete All Data", description: "Permanently delete ALL system data including students, instructors, courses, and attendance records (complete system reset)", tab: "backup" }
                        ]
                    }
                ];
                
                // Perform search
                const searchResults = searchSettings(searchQuery, settingsData);
                displaySearchResults(searchQuery, searchResults);
                
                // Show results modal
                $("#searchResultsModal").modal("show");
            });
            
            // Function to search through settings
            function searchSettings(query, settingsData) {
                query = query.toLowerCase();
                let results = [];
                
                settingsData.forEach(category => {
                    const matchingItems = category.items.filter(item => 
                        item.name.toLowerCase().includes(query) || 
                        item.description.toLowerCase().includes(query)
                    );
                    
                    if (matchingItems.length > 0) {
                        results.push({
                            category: category.category,
                            items: matchingItems
                        });
                    }
                });
                
                return results;
            }
            
            // Function to display search results
            function displaySearchResults(query, results) {
                const container = $("#searchResultsContainer");
                container.empty();
                
                if (results.length === 0) {
                    container.html(`
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <p class="mb-2">No settings found matching "${query}"</p>
                            <div class="text-muted">Our developers are working on adding more settings to the system. This feature might not be available yet.</div>
                        </div>
                    `);
                    return;
                }
                
                results.forEach(category => {
                    container.append(`<h6 class="mt-3 mb-2 pl-2 font-weight-bold">${category.category}</h6>`);
                    
                    category.items.forEach(item => {
                        container.append(`
                            <div class="search-result-item" data-tab="${item.tab}">
                                <h5>${item.name} <span class="badge badge-success">${capitalizeFirstLetter(item.tab)}</span></h5>
                                <p>${item.description}</p>
                                <a href="settings.php?tab=${item.tab}" class="btn btn-sm btn-outline-success mt-1">Go to Setting</a>
                            </div>
                        `);
                    });
                });
            }
            
            // Helper function to capitalize first letter
            function capitalizeFirstLetter(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            }
            
            // Set academic settings
            $('#setAcademicSettings').on('click', function() {
                const schoolYear = $('#schoolYear').val();
                const semester = $('#semester').val();
                
                console.log('Academic settings save clicked:', { schoolYear, semester });
                
                if (!schoolYear || !semester) {
                    showAlert('Please select both school year and semester.', 'danger');
                    return;
                }
                
                showAlert('Saving academic settings...', 'info');
                
                $.ajax({
                    url: 'endpoint/save-academic-settings.php',
                    type: 'POST',
                    data: {
                        school_year: schoolYear,
                        semester: semester,
                        current_tab: 'academic'
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('AJAX success response:', response);
                        if (response.success) {
                            $('#displayedSchoolYear').text(schoolYear);
                            $('#displayedSemester').text(semester);
                            showAlert('Academic settings saved successfully!', 'success');
                            
                            // Refresh the page after a short delay to show updated values
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showAlert(response.message || 'Failed to save settings.', 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', { xhr, status, error });
                        console.log('Response text:', xhr.responseText);
                        showAlert('Network error. Settings may not have been saved.', 'danger');
                    }
                });
            });
            
            // Ensure forms preserve the current tab
            $('form').submit(function() {
                // Only add the tab field if it doesn't exist yet
                if (!$(this).find('input[name="current_tab"]').length) {
                    // Extract current tab from URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentTab = urlParams.get('tab') || 'academic';
                    
                    // Add hidden field with current tab
                    $(this).append('<input type="hidden" name="current_tab" value="' + currentTab + '">');
                }
                return true;
            });
            
            // Function to show alerts
            function showAlert(message, type) {
                const alertArea = $('#academicSettingsAlert');
                alertArea.text(message);
                alertArea.removeClass('alert-success alert-info alert-danger')
                       .addClass('alert-' + type);
                alertArea.show();
                
                if (type === 'success') {
                    setTimeout(function() {
                        alertArea.fadeOut();
                    }, 3000);
                }
            }

            // Update custom file input label with filename
            $(".custom-file-input").on("change", function() {
                var fileName = $(this).val().split("\\").pop();
                $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
            });
            
            // Initialize DataTables for logs if on activity logs tab
            if ($('#logsTable').length > 0) {
                // DataTable is already initialized above - no need to re-initialize
                
                // Force pagination styling
                styleDataTablePagination();
            }
            
            // Initialize DateRangePicker if on activity logs tab
            if ($('#date_range').length > 0) {
                $('#date_range').daterangepicker({
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: 'Clear',
                        format: 'YYYY-MM-DD'
                    }
                });
                
                $('#date_range').on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                    $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
                    $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
                });
                
                $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                    $('#start_date').val('');
                    $('#end_date').val('');
                });
            }
            
            // Handle Delete Data button click (from delete-data tab form)
            $('#deleteDataBtn').on('click', function(e) {
                e.preventDefault();
                
                // Check if DELETE was typed correctly
                const confirmInput = $('#confirmDeleteInput').val().trim().toUpperCase();
                if (confirmInput !== 'DELETE') {
                    alert('Please type DELETE in the confirmation box to proceed.');
                    $('#confirmDeleteInput').focus();
                    return;
                }
                
                // Show the beautiful custom modal
                $('#deleteWarningModalSettings').modal('show');
            });
            
            // Handle Delete Data button click from backup tab (old link)
            $(document).on('click', 'a[href="admin/delete_data.php"]', function(e) {
                e.preventDefault();
                $('#deleteWarningModalSettings').modal('show');
            });
            
            // Handle proceed to delete (final confirmation)
            $('#proceedToDeletePage').on('click', function() {
                // Check if DELETE was typed correctly
                const confirmInput = $('#confirmDeleteInput').val().trim().toUpperCase();
                if (confirmInput !== 'DELETE') {
                    $('#deleteWarningModalSettings').modal('hide');
                    setTimeout(function() {
                        alert('Please type "DELETE" in the confirmation box first!');
                    }, 300);
                    return;
                }
                
                // Hide the modal first
                $('#deleteWarningModalSettings').modal('hide');
                
                // Direct redirect to the working delete page
                setTimeout(function() {
                    window.location.href = 'admin/delete_data.php';
                }, 300);
            });
        });
    </script>
    
    <script>
        // Fix for modal closing issues
        $(document).ready(function() {
            // Ensure modals can be properly closed
            $('.log-details-modal').on('shown.bs.modal', function () {
                $('body').addClass('modal-open');
            });
            
            // Handle close button clicks explicitly
            $('.modal .close, .modal .btn-secondary').on('click', function() {
                $(this).closest('.modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            });
            
            // Handle backdrop clicks
            $(document).on('click', '.modal-backdrop', function() {
                $('.modal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            });
            
            // Fix for multiple backdrops
            $('.log-details-modal').on('hidden.bs.modal', function () {
                if ($('.modal-backdrop').length > 0) {
                    $('.modal-backdrop').remove();
                }
                $('body').removeClass('modal-open');
            });
            
            // Ensure ESC key works to close modals
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // ESC key
                    $('.modal').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                }
            });
            
            // Handle modal close buttons with data-modal-id
            $('[data-modal-id]').on('click', function() {
                const modalId = $(this).data('modal-id');
                $('#' + modalId).modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            });
        });
    </script>
</body>
</html>