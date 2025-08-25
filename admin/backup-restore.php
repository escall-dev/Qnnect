<?php
// Handle AJAX API requests first (before any output)
if (isset($_POST['action']) || isset($_GET['action'])) {
    // Use super admin session handling (same as admin panel)
    require_once '../includes/session_config_superadmin.php';
    require_once '../includes/auth_functions.php';
    require_once "database.php";

    // Check if user is logged in for API requests
    if (!isset($_SESSION['email']) || !hasRole('super_admin')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated or insufficient permissions']);
        exit;
    }

    header('Content-Type: application/json');
    
    // Database configuration
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "login_register";
    $qr_db_name = "qr_attendance_db";
    
    $action = $_POST['action'] ?? $_GET['action'];
    
    try {
        switch ($action) {
            case 'backup':
                $database = $_POST['database'] ?? 'login_register';
                
                // Include the backup class here
                class backup_restore_api {
                    private $host;
                    private $username;
                    private $passwd;
                    private $dbName;
                    private $conn;
                    private $backupDir;
                    private $backupFile;
                    
                    public function __construct($host, $dbName, $username, $passwd) {
                        $this->host = $host;
                        $this->dbName = $dbName;
                        $this->username = $username;
                        $this->passwd = $passwd;
                        $this->conn = $this->connectDB();
                        $this->backupDir = __DIR__ . '/backup/';
                        $this->backupFile = 'database_'.$this->dbName.'_'.date('Y-m-d_H-i-s').'.sql';
                        
                        if (!file_exists($this->backupDir)) {
                            mkdir($this->backupDir, 0777, true);
                        }
                    }
                    
                    protected function connectDB() {
                        try {
                            $conn = new mysqli($this->host, $this->username, $this->passwd, $this->dbName);
                            $conn->set_charset('utf8');
                            
                            if ($conn->connect_error) {
                                throw new Exception('Error connecting to database: ' . $conn->connect_error);
                            }
                            
                            return $conn;
                        } catch (Exception $e) {
                            throw new Exception($e->getMessage());
                        }
                    }
                    
                    public function backup() {
                        try {
                            $this->conn->query("SET NAMES 'utf8'");
                            
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
                                $result = $this->conn->query("SHOW CREATE TABLE $table");
                                $row = $result->fetch_row();
                                
                                $sqlScript .= "\n\n-- Table structure for table `$table`\n\n";
                                $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
                                $sqlScript .= $row[1].";\n\n";
                                
                                $result = $this->conn->query("SELECT * FROM $table");
                                if ($result->num_rows > 0) {
                                    $sqlScript .= "-- Dumping data for table `$table`\n\n";
                                    
                                    while ($row = $result->fetch_row()) {
                                        $sqlScript .= "INSERT INTO `$table` VALUES(";
                                        for ($j = 0; $j < count($row); $j++) {
                                            $row[$j] = $row[$j] ? addslashes($row[$j]) : '';
                                            if (isset($row[$j])) {
                                                $sqlScript .= '"'.$row[$j].'"';
                                            } else {
                                                $sqlScript .= '""';
                                            }
                                            if ($j < (count($row) - 1)) {
                                                $sqlScript .= ',';
                                            }
                                        }
                                        $sqlScript .= ");\n";
                                    }
                                }
                                $sqlScript .= "\n";
                            }
                            
                            $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";
                            
                            $backupFilePath = $this->backupDir . $this->backupFile;
                            file_put_contents($backupFilePath, $sqlScript);
                            
                            return $this->backupFile;
                        } catch (Exception $e) {
                            throw new Exception("Backup failed: " . $e->getMessage());
                        }
                    }
                }
                
                $backup = new backup_restore_api($db_host, $database, $db_user, $db_pass);
                $result = $backup->backup();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'filename' => $result
                ]);
                exit;
                
            case 'list':
                $backupDir = __DIR__ . '/backup/';
                $files = [];
                
                if (is_dir($backupDir)) {
                    $fileList = scandir($backupDir);
                    foreach ($fileList as $file) {
                        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                            $filePath = $backupDir . $file;
                            $files[] = [
                                'name' => $file,
                                'database' => (strpos($file, 'qr_attendance') !== false) ? 'qr_attendance_db' : 'login_register',
                                'date' => date('Y-m-d H:i:s', filemtime($filePath)),
                                'size' => number_format(filesize($filePath) / 1024, 2) . ' KB'
                            ];
                        }
                    }
                }
                
                echo json_encode(['success' => true, 'files' => $files]);
                exit;
                
            case 'delete':
                $filename = $_POST['filename'] ?? '';
                if (empty($filename)) {
                    echo json_encode(['success' => false, 'message' => 'No filename provided']);
                    exit;
                }
                
                $backupDir = __DIR__ . '/backup/';
                $filePath = $backupDir . basename($filename); // basename for security
                
                if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'sql') {
                    if (unlink($filePath)) {
                        echo json_encode(['success' => true, 'message' => 'Backup file deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to delete backup file']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Backup file not found']);
                }
                exit;
                
            case 'download':
                $filename = $_GET['file'] ?? '';
                if (empty($filename)) {
                    echo json_encode(['success' => false, 'message' => 'No filename provided']);
                    exit;
                }
                
                $backupDir = __DIR__ . '/backup/';
                $filePath = $backupDir . basename($filename);
                
                if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'sql') {
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                    header('Content-Length: ' . filesize($filePath));
                    readfile($filePath);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Backup file not found']);
                    exit;
                }
                
            case 'restore':
                if (!isset($_FILES['backup_file'])) {
                    echo json_encode(['success' => false, 'message' => 'No backup file uploaded']);
                    exit;
                }
                
                $database = $_POST['database'] ?? 'login_register';
                
                // Simple restore functionality
                try {
                    $uploadedFile = $_FILES['backup_file'];
                    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception('File upload error');
                    }
                    
                    $sqlContent = file_get_contents($uploadedFile['tmp_name']);
                    if ($sqlContent === false) {
                        throw new Exception('Could not read uploaded file');
                    }
                    
                    // Connect to database
                    $conn = new mysqli($db_host, $db_user, $db_pass, $database);
                    if ($conn->connect_error) {
                        throw new Exception('Database connection failed: ' . $conn->connect_error);
                    }
                    
                    // Execute SQL
                    if ($conn->multi_query($sqlContent)) {
                        // Process all results
                        do {
                            if ($result = $conn->store_result()) {
                                $result->free();
                            }
                        } while ($conn->next_result());
                        
                        echo json_encode(['success' => true, 'message' => 'Backup restored successfully']);
                    } else {
                        throw new Exception('Error executing SQL: ' . $conn->error);
                    }
                    
                    $conn->close();
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()]);
                }
                exit;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Use super admin session handling (same as admin panel)
require_once '../includes/session_config_superadmin.php';
require_once '../includes/auth_functions.php';
require_once "database.php";

// Check if user is logged in and has super admin role
if (!isset($_SESSION['email']) || !hasRole('super_admin')) {
    header("Location: super_admin_login.php");
    exit;
}

// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "login_register"; // Primary database
$qr_db_name = "qr_attendance_db"; // QR attendance database

// Add this near the beginning of your script
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
        // Check if today is 15th or 30th of the month
        $today = date('j');
        if ($today != 15 && $today != 30) {
            // For testing purposes, allow backup anytime
            // In production, uncomment the line below
            // return "Backup is only available on the 15th and 30th of the month.";
        }
        
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
    
    public function restore() {
        try {
            $backupFilePath = $this->backupDir . 'database_' . $this->dbName . '.sql';
            
            if (!file_exists($backupFilePath)) {
                return "Backup file does not exist.";
            }
            
            $sql = file_get_contents($backupFilePath);
            
            if ($this->conn->multi_query($sql)) {
                do {
                    // Wait for each query to finish
                    if ($result = $this->conn->store_result()) {
                        $result->free();
                    }
                } while ($this->conn->more_results() && $this->conn->next_result());
            }
            
            if ($this->conn->error) {
                throw new Exception("Error executing SQL: " . $this->conn->error);
            }
            
            return "Database restored successfully!";
        } catch (Exception $e) {
            return "Error restoring database: " . $e->getMessage();
        }
    }
    
    public function uploadBackup($file) {
        try {
            $targetDir = $this->backupDir;
            $targetFile = $targetDir . "database_" . $this->dbName . ".sql";
            
            // Check if file already exists
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            
            if (move_uploaded_file($file["tmp_name"], $targetFile)) {
                return "The backup file has been uploaded.";
            } else {
                throw new Exception("Sorry, there was an error uploading your file.");
            }
        } catch (Exception $e) {
            return "Error uploading backup: " . $e->getMessage();
        }
    }
}

// Create instances for both databases
$loginImport = new backup_restore($db_host, $db_name, $db_user, $db_pass);
$qrImport = new backup_restore($db_host, $qr_db_name, $db_user, $db_pass);
$message = '';

// Handle file upload
if (isset($_POST['upload_backup']) && isset($_FILES['sql_file'])) {
    $message = $loginImport->uploadBackup($_FILES['sql_file']);
    $process = 'upload';
}
// Handle processes
elseif (isset($_GET['process'])) {
    $process = $_GET['process'];
    if ($process == 'backup') {
        $loginBackup = $loginImport->backup();
        $qrBackup = $qrImport->backup();
        $message = "Both databases backed up successfully: \n" . $loginBackup . "\n" . $qrBackup;
    } elseif ($process == 'restore') {
        $loginRestore = $loginImport->restore();
        $qrRestore = $qrImport->restore();
        $message = "Restore results: \n" . $loginRestore . "\n" . $qrRestore;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup and Restore - QR Attendance System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="./styles/masterlist.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
body {
    background: linear-gradient(to bottom, rgba(255,255,255,0.15) 0%, rgba(0,0,0,0.15) 100%), radial-gradient(at top center, rgba(255,255,255,0.40) 0%, rgba(0,0,0,0.40) 120%) #989898;
    background-blend-mode: multiply,multiply;
    background-attachment: fixed;
    background-repeat: no-repeat;
    background-size: cover;
}
        /* Main content styles */
        .main {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease;
            width: calc(100% - 260px);
        }

        /* When sidebar is closed (collapsed) */
        .sidebar.close ~ .main,
        .main.active {
            margin-left: 78px;
            width: calc(100% - 78px);
        }

        /* Backup container styles */
        .backup-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            margin: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            min-height: calc(100vh - 40px);
        }

        .backup-content {
            background-color: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
        }

        /* Title styles */
        .title {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            margin-bottom: 20px;
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 20px 20px 0 0;
        }

        .title h4 {
            margin: 0;
            color: #098744;
        }

        /* Button styles */
        .backup-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .btn-backup {
            background-color: #098744;
            color: white;
            border: none;
            padding: 15px 25px;
            font-size: 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
            flex: 1;
            max-width: 300px;
            text-align: center;
        }

        .btn-restore {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 15px 25px;
            font-size: 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
            flex: 1;
            max-width: 300px;
            text-align: center;
        }

        .btn-backup:hover {
            background-color: #076633;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: white;
            text-decoration: none;
        }

        .btn-restore:hover {
            background-color: #138496;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: white;
            text-decoration: none;
        }

        .btn-delete-data {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 15px 25px;
            font-size: 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
            flex: 1;
            max-width: 300px;
            text-align: center;
        }

        .btn-delete-data:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: white;
            text-decoration: none;
        }

        /* Message styles */
        .message-box {
            background-color: #f8f9fa;
            border-left: 4px solid #098744;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        /* Note styles */
        .note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 30px 0;
            border-radius: 5px;
        }

        /* Upload form styles */
        .upload-form {
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .main, .main.active {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .backup-container {
                margin: 15px;
            }
            
            .backup-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-backup, .btn-restore, .btn-delete-data {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include('../components/sidebar-nav.php'); ?>
    
    <div class="main" id="main">
        <div class="backup-container">
            <div class="backup-content">
                <div class="title">
                    <h4><i class="fas fa-database"></i> Database Management</h4>
                </div>
                
                <?php if(isset($process) && isset($message)): ?>
                    <div class="message-box">
                        <?php 
                            switch($process) {
                                case 'backup':
                                    echo '<p class="text-success">Backup created successfully!</p>';
                                    echo '<p>Download the backup files:</p>';
                                    echo '<ul>';
                                    echo '<li><a href="backup/'.$loginBackup.'" target="_blank" class="btn btn-sm btn-primary">Login Database Backup</a></li>';
                                    echo '<li><a href="backup/'.$qrBackup.'" target="_blank" class="btn btn-sm btn-primary">QR Attendance Database Backup</a></li>';
                                    echo '</ul>';
                                    break;
                                case 'restore':
                                    echo '<p class="text-info">'.$message.'</p>';
                                    break;
                                case 'upload':
                                    echo '<p class="text-info">'.$message.'</p>';
                                    break;
                            }
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="backup-buttons">
                    <a href="backup-restore.php?process=backup" class="btn-backup">
                        <i class="fas fa-download"></i> BACKUP DATABASE
                    </a>
                    
                    <a href="backup-restore.php?process=restore" class="btn-restore">
                        <i class="fas fa-upload"></i> RESTORE DATABASE
                    </a>
                    
                    <a href="delete_data.php" class="btn-delete-data">
                        <i class="fas fa-trash-alt"></i> DELETE DATA
                    </a>
                </div>
                
                <div class="upload-form">
                    <h5 class="mb-3">Upload Backup File</h5>
                    <form method="post" action="backup-restore.php" enctype="multipart/form-data">
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" id="sql_file" name="sql_file" accept=".sql">
                            <label class="custom-file-label" for="sql_file">Choose backup file...</label>
                        </div>
                        <button type="submit" name="upload_backup" class="btn btn-primary">
                            <i class="fas fa-cloud-upload-alt"></i> Upload & Prepare Restore
                        </button>
                    </form>
                </div>
                
                <div class="note">
                    <h5><i class="fas fa-exclamation-triangle"></i> IMPORTANT NOTE:</h5>
                    <ul class="mb-0">
                        <li>The backup process is only available on the 15th and 30th of each month (Kinsenas at Katapusan).</li>
                        <li>No inputs should be made during the backup or restore process.</li>
                        <li>Make sure to download and save your backup files in a secure location.</li>
                        <li>Restoring a database will overwrite all current data with the data from the backup file.</li>
                        <li><strong>DELETE DATA:</strong> Permanently removes all attendance records. Always backup first!</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    
    <script>
        // Update custom file input label with filename
        $(".custom-file-input").on("change", function() {
            var fileName = $(this).val().split("\\").pop();
            $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        });
        
        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main');
            const toggleButton = document.querySelector('.sidebar-toggle');

            sidebar.classList.toggle("active");
            main.classList.toggle("active");
            toggleButton.classList.toggle("rotate");
        }

        // Add event listener for sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.querySelector('.sidebar-toggle');
            if (toggleButton) {
                toggleButton.onclick = toggleSidebar;
            }
        });
    </script>
</body>
</html> 