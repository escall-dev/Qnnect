<?php
// Use consistent session handling
require_once '../includes/session_config.php';
require_once "database.php";

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$qr_db_name = "qr_attendance_db"; // QR attendance database

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
        // Safely close the connection
        if (isset($this->conn) && $this->conn instanceof mysqli && @$this->conn->ping()) {
            $this->conn->close();
        }
    }
}

// Handle delete request
$message = '';
$result = null;

if (isset($_POST['delete_data'])) {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'DELETE') {
        $cleaner = new DatabaseCleaner($db_host, $db_user, $db_pass, $qr_db_name);
        $result = $cleaner->deleteAllData();
        $message = $result['message'];
    } else {
        $message = 'Please type "DELETE" to confirm the operation.';
        $result = ['success' => false];
    }
}

// Get current table counts
$cleaner = new DatabaseCleaner($db_host, $db_user, $db_pass, $qr_db_name);
$tableCounts = $cleaner->getTableCounts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete System Reset - QR Attendance System</title>
    
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

        /* Delete container styles */
        .delete-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            margin: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            min-height: calc(100vh - 40px);
        }

        .delete-content {
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
            color: #dc3545;
        }

        /* Table count styles */
        .table-counts {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .count-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .count-item:last-child {
            border-bottom: none;
        }

        /* Warning styles */
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .danger-box {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        /* Delete form styles */
        .delete-form {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
            border: 2px solid #dc3545;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: white;
        }

        .btn-delete:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        /* Message styles */
        .message-box {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .message-box.success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .message-box.error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        /* Result details */
        .result-details {
            margin-top: 15px;
        }

        .result-list {
            max-height: 200px;
            overflow-y: auto;
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
        }

        /* Navigation styles */
        .nav-buttons {
            text-align: center;
            margin: 20px 0;
        }

        .nav-buttons .btn {
            margin: 0 10px;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .main, .main.active {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .delete-container {
                margin: 15px;
            }
        }
    </style>
</head>

<body>
    <?php include('../components/sidebar-nav.php'); ?>
    
    <div class="main" id="main">
        <div class="delete-container">
            <div class="delete-content">
                <div class="title">
                    <h4><i class="fas fa-trash-alt"></i> Complete System Reset - Delete All Data</h4>
                </div>

                <div class="nav-buttons">
                    <a href="backup-restore.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Backup/Restore
                    </a>
                </div>
                
                <?php if($message): ?>
                    <div class="message-box <?php echo $result['success'] ? 'success' : 'error'; ?>">
                        <strong><?php echo $result['success'] ? 'Success!' : 'Error!'; ?></strong>
                        <p><?php echo htmlspecialchars($message); ?></p>
                        
                        <?php if(isset($result['deleted']) && !empty($result['deleted'])): ?>
                            <div class="result-details">
                                <h6>Tables Cleared:</h6>
                                <div class="result-list">
                                    <?php foreach($result['deleted'] as $table): ?>
                                        <div>✓ <?php echo htmlspecialchars($table); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($result['skipped']) && !empty($result['skipped'])): ?>
                            <div class="result-details">
                                <h6>Tables Skipped:</h6>
                                <div class="result-list">
                                    <?php foreach($result['skipped'] as $table): ?>
                                        <div>- <?php echo htmlspecialchars($table); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="danger-box">
                    <h5><i class="fas fa-exclamation-triangle"></i> COMPLETE SYSTEM RESET - DANGER ZONE</h5>
                    <p><strong>This action will permanently delete ALL data from the entire system - this is a COMPLETE SYSTEM RESET!</strong></p>
                    <p>This deletes data from 15 tables including:</p>
                    <ul>
                        <li><strong>ALL STUDENT RECORDS</strong> (tbl_student)</li>
                        <li><strong>ALL INSTRUCTOR DATA</strong> (tbl_instructors, tbl_instructor_subjects)</li>
                        <li><strong>ALL COURSES & SUBJECTS</strong> (courses, tbl_subjects)</li>
                        <li><strong>SCHOOL INFORMATION</strong> (school_info)</li>
                        <li><strong>ALL ATTENDANCE DATA</strong> (tbl_attendance, attendance_logs, attendance_grades)</li>
                        <li><strong>FACE RECOGNITION DATA</strong> (tbl_face_recognition_logs, tbl_face_verification_logs)</li>
                        <li><strong>ACTIVITY LOGS & USER LOGS</strong> (activity_logs, tbl_user_logs)</li>
                        <li><strong>OFFLINE DATA & SESSIONS</strong> (offline_data, attendance_sessions)</li>
                    </ul>
                    <p class="mb-0"><strong>This is essentially starting fresh with an empty system! This action cannot be undone!</strong></p>
                </div>
                
                <div class="table-counts">
                    <h5><i class="fas fa-table"></i> Current Database Status</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <?php 
                            $halfCount = ceil(count($tableCounts) / 2);
                            $counter = 0;
                            foreach($tableCounts as $table => $count): 
                                if($counter >= $halfCount) break;
                            ?>
                                <div class="count-item">
                                    <span><?php echo htmlspecialchars($table); ?>:</span>
                                    <span class="badge badge-<?php echo $count > 0 ? 'primary' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($count); ?> records
                                    </span>
                                </div>
                            <?php 
                                $counter++;
                            endforeach; 
                            ?>
                        </div>
                        <div class="col-md-6">
                            <?php 
                            $counter = 0;
                            foreach($tableCounts as $table => $count): 
                                if($counter < $halfCount) {
                                    $counter++;
                                    continue;
                                }
                            ?>
                                <div class="count-item">
                                    <span><?php echo htmlspecialchars($table); ?>:</span>
                                    <span class="badge badge-<?php echo $count > 0 ? 'primary' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($count); ?> records
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="warning-box">
                    <h5><i class="fas fa-info-circle"></i> Before You Proceed</h5>
                    <ul class="mb-0">
                        <li><strong>Create a backup first!</strong> Use the backup function to save your current data.</li>
                        <li>Make sure all users are logged out of the system.</li>
                        <li>Inform all users about the data deletion schedule.</li>
                        <li>The user_settings table will be preserved to maintain system configuration.</li>
                        <li>This operation will reset all auto-increment values to 1.</li>
                    </ul>
                </div>
                
                <form method="post" class="delete-form" id="deleteForm">
                    <h5><i class="fas fa-shield-alt"></i> Confirmation Required</h5>
                    <p>To proceed with deleting all data, type <strong>DELETE</strong> in the box below:</p>
                    
                    <div class="form-group">
                        <input type="text" 
                               name="confirm_delete" 
                               class="form-control" 
                               placeholder="Type DELETE to confirm"
                               style="text-transform: uppercase;"
                               required>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" name="delete_data" class="btn-delete" onclick="confirmDelete()">
                            <i class="fas fa-trash-alt"></i> DELETE ALL DATA
                        </button>
                    </div>
                </form>
                
                <!-- Add section for preserved data information -->
                <div class="alert alert-success mt-4">
                    <h5><i class="fas fa-shield-alt"></i> WHAT'S PRESERVED</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Your admin accounts are SAFE!</strong></p>
                            <ul class="mb-0">
                                <li>✅ <strong>Admin/User Accounts</strong></li>
                                <li>✅ <strong>Login Credentials</strong></li>
                                <li>✅ <strong>User Permissions</strong></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <p><strong>System configuration preserved:</strong></p>
                            <ul class="mb-0">
                                <li>✅ <strong>User Settings</strong></li>
                                <li>✅ <strong>System Configuration</strong></li>
                                <li>✅ <strong>Database Structure</strong></li>
                            </ul>
                        </div>
                    </div>
                    <p class="mt-2 mb-0"><strong>Only attendance system data (qr_attendance_db) will be deleted!</strong></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Warning Modal -->
    <div class="modal fade" id="deleteWarningModal" tabindex="-1" role="dialog" aria-labelledby="deleteWarningModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border: 3px solid #dc3545; border-radius: 15px;">
                <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-bottom: none; border-radius: 12px 12px 0 0;">
                    <h4 class="modal-title" id="deleteWarningModalLabel" style="font-weight: bold;">
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
                    <button type="button" class="btn btn-danger btn-lg" id="confirmSystemReset" style="padding: 12px 30px; font-weight: bold;">
                        <i class="fas fa-trash-alt"></i> Yes, Delete All Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Confirmation Modal -->
    <div class="modal fade" id="finalConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="finalConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border: 3px solid #dc3545; border-radius: 15px;">
                <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #b02a37); color: white; border-bottom: none; border-radius: 12px 12px 0 0;">
                    <h4 class="modal-title" id="finalConfirmationModalLabel" style="font-weight: bold;">
                        <i class="fas fa-exclamation-circle" style="color: #fff; margin-right: 10px; font-size: 1.2em;"></i>
                        Final Confirmation
                    </h4>
                </div>
                <div class="modal-body text-center" style="padding: 40px; background-color: #fff8f8;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 5em; color: #dc3545; margin-bottom: 25px;"></i>
                    <h4 style="color: #dc3545; font-weight: bold; margin-bottom: 20px;">Last Chance!</h4>
                    <p style="font-size: 18px; color: #333; margin-bottom: 25px;">
                        This is your final opportunity to cancel.<br>
                        <strong>This action will permanently delete ALL system data from 15 tables.</strong>
                    </p>
                    <div class="alert alert-danger" style="font-size: 16px; font-weight: bold;">
                        This is a COMPLETE SYSTEM RESET!<br>
                        Have you created a backup?
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6; border-radius: 0 0 12px 12px; padding: 20px; justify-content: center;">
                    <button type="button" class="btn btn-success btn-lg" data-dismiss="modal" style="padding: 12px 30px; font-weight: bold; margin-right: 20px;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger btn-lg" id="proceedWithDeletion" style="padding: 12px 30px; font-weight: bold;">
                        <i class="fas fa-trash-alt"></i> Proceed with Deletion
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    
    <script>
        function confirmDelete() {
            // Check if DELETE was typed first
            const confirmInput = $('input[name="confirm_delete"]').val();
            if (confirmInput !== 'DELETE') {
                showCustomAlert('Please type "DELETE" in the confirmation box first!', 'warning');
                return false;
            }
            
            // Show custom final confirmation modal
            $('#finalConfirmationModal').modal('show');
            return false;
        }
        
        // Enhanced custom alert function
        function showCustomAlert(message, type) {
            const iconType = type === 'warning' ? '⚠️' : type === 'success' ? '✅' : '❌';
            const colorType = type === 'warning' ? '#ffc107' : type === 'success' ? '#28a745' : '#dc3545';
            const titleType = type === 'warning' ? 'Input Required' : type === 'success' ? 'Success' : 'Error';
            
            const alertHtml = `
                <div class="custom-alert-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s ease;">
                    <div class="custom-alert-box" style="background: white; padding: 30px; border-radius: 15px; max-width: 400px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-left: 5px solid ${colorType}; animation: slideIn 0.3s ease;">
                        <div style="font-size: 3em; margin-bottom: 15px; color: ${colorType};">
                            ${iconType}
                        </div>
                        <h4 style="color: #333; margin-bottom: 15px; font-weight: bold;">
                            ${titleType}
                        </h4>
                        <p style="color: #666; font-size: 16px; line-height: 1.5;">${message}</p>
                        <button onclick="$(this).closest('.custom-alert-overlay').fadeOut(300, function(){ $(this).remove(); })" style="background: ${colorType}; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 15px; transition: all 0.2s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            OK
                        </button>
                    </div>
                </div>
                <style>
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideIn {
                        from { transform: translateY(-50px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                </style>
            `;
            $('body').append(alertHtml);
        }
        
        // Handle first confirmation
        $('#confirmSystemReset').on('click', function() {
            $('#deleteWarningModal').modal('hide');
            // Small delay before showing second modal
            setTimeout(function() {
                $('#finalConfirmationModal').modal('show');
            }, 300);
        });
        
        // Handle final confirmation
        $('#proceedWithDeletion').on('click', function() {
            // Check if DELETE was typed in the input
            const confirmInput = $('input[name="confirm_delete"]').val();
            if (confirmInput !== 'DELETE') {
                $('#finalConfirmationModal').modal('hide');
                setTimeout(function() {
                    showCustomAlert('Please type "DELETE" in the confirmation box first!', 'warning');
                }, 300);
                return;
            }
            
            // Show beautiful loading state
            const btn = $(this);
            btn.html('<i class="fas fa-spinner fa-spin"></i> Deleting All Data...');
            btn.prop('disabled', true);
            btn.css('background-color', '#6c757d');
            
            // Add a nice progress effect
            let progress = 0;
            const progressInterval = setInterval(function() {
                progress += 10;
                if (progress <= 100) {
                    btn.html(`<i class="fas fa-spinner fa-spin"></i> Processing... ${progress}%`);
                } else {
                    clearInterval(progressInterval);
                }
            }, 200);
            
            // Add the required hidden field and submit the form
            setTimeout(function() {
                const form = $('form.delete-form');
                // Add the delete_data field if it doesn't exist
                if (!form.find('input[name="delete_data"]').length) {
                    form.append('<input type="hidden" name="delete_data" value="1">');
                }
                form[0].submit();
            }, 1000);
        });
        
        // Auto-uppercase the confirmation input
        document.querySelector('input[name="confirm_delete"]').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
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