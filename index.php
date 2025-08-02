<?php
require_once 'includes/asset_helper.php';
require_once 'includes/session_config.php'; // Handle session configuration
require_once 'includes/schedule_helper.php'; // Has getScheduleDropdownData
require_once 'includes/schedule_data_helper.php'; // Has getFilteredScheduleData
require_once 'includes/data_isolation_helper.php'; // Data isolation functions

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Check if user is logged in, if not redirect to login page
// Session is already started by session_config.php

// Include database connections first
include('./conn/db_connect.php');
// Include activity logging helper
include('./includes/activity_log_helper.php');

// Use login database connection for user data
$conn_login = mysqli_connect("localhost", "root", "", "login_register");
if (!$conn_login) {
    die("Connection failed: " . mysqli_connect_error());
}

// Use QR database connection for attendance
$conn = $conn_qr;

// Get user's school_id from the users table
$email = $_SESSION['email'];
$user_query = "SELECT school_id, role FROM users WHERE email = ?";
$stmt = $conn_login->prepare($user_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$school_id = $user['school_id'] ?? 1; // Default to school_id 1 if not set
$_SESSION['school_id'] = $school_id; // Store in session for other pages

// Auto-fix attendance status for records that might be incorrect
function autoFixAttendanceStatus($conn) {
    // Skip if no class time set
    if (!isset($_SESSION['class_start_time'])) {
        return;
    }
    
    // Get class start time
    $class_start_time = isset($_SESSION['class_start_time_formatted']) 
        ? $_SESSION['class_start_time_formatted'] 
        : $_SESSION['class_start_time'];
    
    // Ensure it has seconds
    if (strlen($class_start_time) == 5) {
        $class_start_time .= ':00';
    }
    
    // Only fix records from today with missing status
    $today = date('Y-m-d');
    $query = "SELECT tbl_attendance_id, time_in, status FROM tbl_attendance WHERE DATE(time_in) = ? AND (status IS NULL OR status = '')";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $attendanceId = $row['tbl_attendance_id'];
            $timeIn = $row['time_in'];
            
            // Get timestamps for comparison
            $timeInDate = date('Y-m-d', strtotime($timeIn));
            $timeInDateTime = new DateTime($timeIn);
            $classStartDateTime = new DateTime($timeInDate . ' ' . $class_start_time);
            
            // Determine status only for records without a status
            $isOnTime = ($timeInDateTime->getTimestamp() <= $classStartDateTime->getTimestamp());
            $correctStatus = $isOnTime ? 'On Time' : 'Late';
            
            // Only set status if it's missing (NULL or empty string)
            error_log("Setting missing status for attendance #$attendanceId to $correctStatus");
            $updateQuery = "UPDATE tbl_attendance SET status = ? WHERE tbl_attendance_id = ? AND (status IS NULL OR status = '')";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("si", $correctStatus, $attendanceId);
            $updateStmt->execute();
        }
    } catch (Exception $e) {
        error_log("Error setting missing attendance status: " . $e->getMessage());
    }
}

// Run auto-fix when page loads
if (isset($conn_qr) && $conn_qr !== null) {
    autoFixAttendanceStatus($conn_qr);
}

// Fetch user data from login_register database
if (isset($_SESSION['email'])) {
    try {
        $email = $_SESSION['email'];
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn_login->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        
        // Store user data in session
        $_SESSION['userData'] = $userData;
        
        // Load user's academic settings from database if not already set in session
        if (!isset($_SESSION['school_year']) || !isset($_SESSION['semester'])) {
            $settings_query = "SELECT school_year, semester FROM user_settings WHERE email = ?";
            $settings_stmt = $conn_qr->prepare($settings_query);
            $settings_stmt->bind_param("s", $email);
            $settings_stmt->execute();
            $settings_result = $settings_stmt->get_result();
            
            if ($settings_result && $settings_result->num_rows > 0) {
                $settings = $settings_result->fetch_assoc();
                $_SESSION['school_year'] = $settings['school_year'];
                $_SESSION['semester'] = $settings['semester'];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching user data: " . $e->getMessage());
    }
}

if (isset($_GET['attendance'])) {
    $attendance = $_GET['attendance'];

    try {
        // Get user context for data isolation
        $context = getCurrentUserContext();
        
        // Get attendance details before deletion for logging with isolation
        $get_attendance_query = "SELECT * FROM tbl_attendance 
                                WHERE tbl_attendance_id = ? 
                                AND school_id = ? 
                                " . ($context['user_id'] ? "AND (user_id = ? OR user_id IS NULL)" : "");
        
        $get_stmt = $conn_qr->prepare($get_attendance_query);
        
        if ($context['user_id']) {
            $get_stmt->bind_param("iii", $attendance, $context['school_id'], $context['user_id']);
        } else {
            $get_stmt->bind_param("ii", $attendance, $context['school_id']);
        }
        
        $get_stmt->execute();
        $attendance_details = $get_stmt->get_result()->fetch_assoc();

        // Only proceed if the record exists and belongs to the user's context
        if ($attendance_details) {
            $query = "DELETE FROM tbl_attendance 
                     WHERE tbl_attendance_id = ? 
                     AND school_id = ? 
                     " . ($context['user_id'] ? "AND (user_id = ? OR user_id IS NULL)" : "");
            
            $stmt = $conn_qr->prepare($query);
            
            if ($context['user_id']) {
                $stmt->bind_param("iii", $attendance, $context['school_id'], $context['user_id']);
            } else {
                $stmt->bind_param("ii", $attendance, $context['school_id']);
            }
            
            $query_execute = $stmt->execute();

            if ($query_execute) {
                // Log the successful deletion
                logActivity(
                    'delete',
                    "Deleted attendance record #$attendance",
                    'tbl_attendance',
                    $attendance,
                    $attendance_details
                );

                echo "<script>
                    showCustomAlert('Attendance deleted successfully!', 'success');
                    setTimeout(function() {
                        window.location.href = 'http://localhost/personal-proj/Qnnect/index.php';
                    }, 3000);
                </script>";
            } else {
                // Log the failed deletion attempt
                logActivity(
                    'delete_failed',
                    "Failed to delete attendance record #$attendance",
                    'tbl_attendance',
                    $attendance,
                    ['error' => $conn_qr->error]
                );

                echo "<script>
                    showCustomAlert('Failed to delete attendance!', 'error');
                    setTimeout(function() {
                        window.location.href = 'http://localhost/personal-proj/Qnnect/index.php';
                    }, 3000);
                </script>";
            }
        } else {
            // Record doesn't exist or doesn't belong to user
            echo "<script>
                showCustomAlert('Attendance record not found or access denied!', 'error');
                setTimeout(function() {
                    window.location.href = 'http://localhost/personal-proj/Qnnect/index.php';
                }, 3000);
            </script>";
        }
    } catch (Exception $e) {
        // Log the exception
        logActivity(
            'error',
            "Error deleting attendance: " . $e->getMessage(),
            'tbl_attendance',
            $attendance
        );
        
        echo "Error: " . $e->getMessage();
    }
}

// Get user's school_id (assuming it's stored in session)
$school_id = $_SESSION['school_id'] ?? 1;

// Get dropdown data
$scheduleData = getScheduleDropdownData($school_id);

// Get selected filters from GET parameters
$selectedInstructor = $_GET['instructor'] ?? '';
$selectedSection = $_GET['section'] ?? '';
$selectedSubject = $_GET['subject'] ?? '';

// Get filtered schedules
$filteredSchedules = getFilteredSchedules(
    $selectedInstructor,
    $selectedSection,
    $selectedSubject,
    $school_id
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Attendance System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="./styles/pagination.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="./functions/script.js"></script>
    <style>
        /* Main content styles - matching face-verification.php */
        .main {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease;
            width: calc(100% - 260px);
            display: flex;
            justify-content: center; /* Center content horizontally */
            align-items: center; /* Center content vertically */
        }

        /* When sidebar is closed */
        .sidebar.close ~ .main {
            margin-left: 78px;
            width: calc(100% - 78px);
        }

        /* Hamburger menu rotation */
        .sidebar-toggle {
            transition: transform 0.3s ease;
        }

        .sidebar-toggle.rotate {
            transform: rotate(180deg);
        }

        /* Content container styles */
        .attendance-container {
            padding: 20px;
            margin: 0 auto; /* Center the container */
            width: 100%;
            max-width: 100%; /* Maximize the width */
        }

        .qr-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            width: 100%; /* Maximize the width */
        }

        .camera-container {
            position: relative;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            border: 3px solid #098744;
            border-radius: 10px;
            overflow: hidden;
        }

        .verification-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .verification-buttons button {
            width: 100%;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 100%; /* Maximize the width */
        }

        /* Responsive styles */
        @media (max-width: 1200px) {
            .main.collapsed {
                margin-left: 200px;
                width: calc(100% - 200px);
            }
            .main.active {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
        }

        @media (max-width: 992px) {
            .main.collapsed {
                margin-left: 180px;
                width: calc(100% - 180px);
            }
            .main.active {
                margin-left: 50px;
                width: calc(100% - 50px);
            }
        }

        @media (max-width: 768px) {
            .main, .main.active {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .qr-container {
                margin: 10px;
                width: calc(100% - 20px);
            }
            
            .card {
                padding: 15px;
                margin: 10px;
            }
        }

        @media (max-width: 576px) {
            .main {
                padding: 10px;
            }
            
            .qr-container {
                margin: 10px;
            }
        }
        
        /* Popup styles */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
        }
        
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff8f8; /* Light red background */
            width: 350px;
            padding: 25px;
            border-radius: 10px;
            border: 2px solid #dc3545; /* Red border */
            box-shadow: 0 5px 25px rgba(220, 53, 69, 0.3); /* Red-tinted shadow */
            z-index: 10000;
            text-align: center;
        }
        
        .popup h5 {
            color: #dc3545; /* Red text for heading */
            font-size: 20px;
            font-weight: bold;
        }
        
        .popup h5 i {
            margin-right: 8px;
        }
        
        .popup p {
            color: #333333;
            font-size: 16px;
            margin: 20px 0;
            line-height: 1.5;
        }
        
        .popup button {
            padding: 10px 25px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .confirm-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .confirm-delete:hover {
            background-color: #c82333;
        }
        
        .cancel-delete {
            background-color: #6c757d;
            color: white;
        }
        
        .cancel-delete:hover {
            background-color: #5a6268;
        }
        
        .success-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #28a745;
            color: white;
            width: 300px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            text-align: center;
        }
        
        /* Subject management styles */
        .subject-removable {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        
        .subject-removable:hover {
            background-color: #fff8f8;
            border-left: 3px solid #dc3545;
        }
        
        .subject-removable .btn-danger {
            opacity: 0.8;
            transition: all 0.2s ease;
        }
        
        .subject-removable:hover .btn-danger {
            opacity: 1;
            transform: scale(1.05);
        }
        
        /* Modal animations */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
            transform: scale(0.8);
        }
        
        .modal.show .modal-dialog {
            transform: scale(1);
        }
        
        .modal-content {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }
        
        /* Custom styling for the subject removal modal */
        #subjectRemovalModal .modal-header {
            background: linear-gradient(to right, #dc3545, #c82333);
        }
        
        #subjectRemovalModal .modal-body strong {
            color: #dc3545;
            font-weight: bold;
        }
        
        #subjectRemovalModal .btn-danger {
            background: linear-gradient(to right, #dc3545, #c82333);
            border: none;
            transition: all 0.3s;
        }
        
        #subjectRemovalModal .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Loading spinner */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-loading:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin-top: -10px;
            margin-left: -10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Success modal styles */
        #subjectRemovalSuccessModal .modal-header {
            background: linear-gradient(to right, #28a745, #218838);
        }
        
        #subjectRemovalSuccessModal .fas.fa-check-circle {
            animation: success-bounce 1s ease-in-out;
        }
        
        #subjectRemovalSuccessModal .btn-success {
            background: linear-gradient(to right, #28a745, #218838);
            border: none;
            transition: all 0.3s;
        }
        
        #subjectRemovalSuccessModal .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        @keyframes success-bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(9, 135, 68, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(9, 135, 68, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(9, 135, 68, 0);
            }
        }
        
        #attendanceNotification {
            border-left: 4px solid #098744;
            font-weight: 500;
            animation: pulse 2s infinite;
            font-size: 1.1rem;
            background-color: rgba(40, 167, 69, 0.9) !important;
            color: white !important;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }
        
        #attendanceNotification i {
            margin-right: 5px;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Custom Alert Box -->
    <div id="customAlert" class="custom-alert"></div>

    <?php include('./components/sidebar-nav.php'); ?>

    

    <div class="main" id="main">
        <div class="attendance-container row" style="margin: 0 15px; width: calc(100% - 30px); overflow: hidden;">
            <div class="qr-container col-md-3 col-sm-12 pr-md-3 mb-4 mb-md-0" style="height: fit-content;">
                <div class="scanner-con">
                    <h5 class="text-center">Scan your QR Code here for your attendance</h5>
                    <video id="interactive" class="viewport" width="100%"></video>
                </div>

                <div class="qr-detected-container" style="display: none;">
                    <form action="./endpoint/add-attendance.php" method="POST">
                        <h4 class="text-center">Student QR Detected!</h4>
                        <input type="hidden" id="detected-qr-code" name="qr_code">
                        <input type="hidden" id="class-start-time" name="class_start_time" value="<?= isset($_SESSION['class_start_time']) ? $_SESSION['class_start_time'] : '08:00' ?>">
                        <button type="submit" class="btn btn-dark form-control">Submit Attendance</button>
                    </form>
                </div>
                
                <!-- Class Time Setting Form moved below camera -->
                <div class="class-time-setting mt-4">
                    <h5 class="text-center">Set Class Time</h5>
                    <div id="currentTimeSettings" class="alert alert-info" style="<?= isset($_SESSION['class_start_time']) ? '' : 'display:none;' ?>">
                        Current class start time: <strong id="displayedStartTime">
                            <?php 
                            if(isset($_SESSION['class_start_time'])) {
                                // Convert 24-hour time to 12-hour format with AM/PM
                                $time = DateTime::createFromFormat('H:i', $_SESSION['class_start_time']);
                                echo $time ? $time->format('h:i A') : $_SESSION['class_start_time'];
                            } else {
                                echo '08:00 AM';
                            }
                            ?>
                        </strong>
                    </div>
                    <form id="classTimeForm">
                        <div class="input-group mb-3">
                            <input type="time" class="form-control" id="classStartTime" name="classStartTime" value="<?= isset($_SESSION['class_start_time']) ? $_SESSION['class_start_time'] : '08:00' ?>" required>
                            <div class="input-group-append">
                                <button type="button" id="setClassTime" class="btn btn-success">Set Time</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="classDuration">Class Duration (minutes)</label>
                            <input type="number" class="form-control" id="classDuration" name="classDuration" value="60" min="15" max="240" step="15">
                        </div>
                    </form>
                    <!-- Class Time Alert Message Area -->
                    <div id="classTimeAlertArea" style="display: none;" class="mt-2 alert"></div>
                    <!-- Attendance Session Info Area -->
                    <div id="sessionInfoArea" style="display: none;" class="mt-2 alert alert-success">
                        <strong>Attendance Session Created</strong>
                        <div id="sessionDetails"></div>
                    </div>
                </div>
                
                <!-- Schedule Selection Section -->
                <div class="schedule-section mt-4">
                    <h5 class="text-center">Schedule Settings</h5>
                    <div class="card">
                        <div class="card-body">
                            <form id="scheduleFilterForm">
                                <div class="form-group">
                                    <label for="instructorSelect">Instructor:</label>
                                    <select class="form-control" id="instructorSelect" name="instructor">
                                        <option value="">Select Instructor</option>
                                        <?php
                                        $scheduleData = getScheduleDropdownData($school_id);
                                        foreach ($scheduleData['instructors'] as $instructor) {
                                            echo "<option value='" . htmlspecialchars($instructor) . "'>" . htmlspecialchars($instructor) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="sectionSelect">Section:</label>
                                    <select class="form-control" id="sectionSelect" name="section">
                                        <option value="">Select Section</option>
                                        <?php
                                        foreach ($scheduleData['sections'] as $section) {
                                            echo "<option value='" . htmlspecialchars($section) . "'>" . htmlspecialchars($section) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="subjectSelect">Subject:</label>
                                    <select class="form-control" id="subjectSelect" name="subject">
                                        <option value="">Select Subject</option>
                                        <?php
                                        foreach ($scheduleData['subjects'] as $subject) {
                                            echo "<option value='" . htmlspecialchars($subject) . "'>" . htmlspecialchars($subject) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </form>
                            <!-- Schedule Info -->
                            <div id="scheduleInfo" class="mt-3 p-3 bg-light rounded" style="display: none;">
                                <h6 class="mb-2">Selected Schedule:</h6>
                                <p class="mb-1"><strong>Instructor:</strong> <span id="scheduleInstructor"></span></p>
                                <p class="mb-1"><strong>Section:</strong> <span id="scheduleSection"></span></p>
                                <p class="mb-1"><strong>Subject:</strong> <span id="scheduleSubject"></span></p>
                                <p class="mb-0"><strong>Time:</strong> <span id="scheduleTime"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instructor Management Section -->
                <div class="instructor-section mt-4">
                    <h5 class="text-center">Select Instructor</h5>
                    
                    <!-- Current Instructor Display -->
                    <div id="currentInstructorInfo" class="alert alert-info" style="<?= isset($_SESSION['current_instructor_id']) ? '' : 'display:none;' ?>">
                        <div><strong>Current Instructor:</strong> <span id="displayedInstructorName"><?= isset($_SESSION['current_instructor_name']) ? $_SESSION['current_instructor_name'] : '' ?></span></div>
                        <div><strong>Current Subject:</strong> <span id="displayedSubject"><?= isset($_SESSION['current_subject_name']) ? $_SESSION['current_subject_name'] : '' ?></span></div>
                    </div>
                    
                    <!-- Set Current Instructor Form -->
                    <form id="setInstructorForm" class="mb-3">
                        <div class="form-group">
                                
                            <select class="form-control" id="instructorSelect">
                                <option value="">-- Select Instructor --</option>
                                <?php
                                // Ensure tables exist
                                $create_instructors_table = "CREATE TABLE IF NOT EXISTS tbl_instructors (
                                    instructor_id INT AUTO_INCREMENT PRIMARY KEY,
                                    instructor_name VARCHAR(100) NOT NULL UNIQUE,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                )";
                                $conn_qr->query($create_instructors_table);
                                
                                $create_subjects_table = "CREATE TABLE IF NOT EXISTS tbl_subjects (
                                    subject_id INT AUTO_INCREMENT PRIMARY KEY,
                                    subject_name VARCHAR(100) NOT NULL UNIQUE,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                )";
                                $conn_qr->query($create_subjects_table);
                                
                                $create_instructor_subjects_table = "CREATE TABLE IF NOT EXISTS tbl_instructor_subjects (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    instructor_id INT NOT NULL,
                                    subject_id INT NOT NULL,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    UNIQUE KEY unique_instructor_subject (instructor_id, subject_id),
                                    FOREIGN KEY (instructor_id) REFERENCES tbl_instructors(instructor_id) ON DELETE CASCADE,
                                    FOREIGN KEY (subject_id) REFERENCES tbl_subjects(subject_id) ON DELETE CASCADE
                                )";
                                $conn_qr->query($create_instructor_subjects_table);
                                
                                // Fetch all instructors
                                $instructors_query = "
                                    SELECT i.instructor_id, i.instructor_name, 
                                           GROUP_CONCAT(s.subject_name SEPARATOR ', ') as subjects
                                    FROM tbl_instructors i
                                    LEFT JOIN tbl_instructor_subjects is_rel ON i.instructor_id = is_rel.instructor_id
                                    LEFT JOIN tbl_subjects s ON is_rel.subject_id = s.subject_id
                                    GROUP BY i.instructor_id
                                    ORDER BY i.instructor_name ASC
                                ";
                                $instructors_result = $conn_qr->query($instructors_query);
                                
                                if ($instructors_result && $instructors_result->num_rows > 0) {
                                    while ($instructor = $instructors_result->fetch_assoc()) {
                                        $selected = (isset($_SESSION['current_instructor_id']) && $_SESSION['current_instructor_id'] == $instructor['instructor_id']) ? 'selected' : '';
                                        echo "<option value=\"{$instructor['instructor_id']}\" data-subjects=\"{$instructor['subjects']}\" $selected>{$instructor['instructor_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group subject-select-container" style="display: none;">
                            <label for="subjectSelect">Select Subject:</label>
                            <select class="form-control" id="subjectSelect"></select>
                        </div>
                        <button type="button" id="setCurrentInstructor" class="btn btn-primary btn-block">Set Current Instructor</button>
                    </form>
                    
                    <!-- Toggle Buttons for Add/Edit/Delete -->
                    <div class="d-flex justify-content-between mb-3">
                        <button id="showAddInstructor" class="btn btn-success flex-grow-1 mr-1">Add Instructor</button>
                        <button id="showEditInstructor" class="btn btn-warning flex-grow-1 mr-1">Edit</button>
                        <button id="showManageSubjects" class="btn btn-info flex-grow-1 mr-1">Delete Subject</button>
                        <button id="showDeleteInstructor" class="btn btn-danger flex-grow-1">Delete Instructor</button>
                    </div>
                    
                    <!-- Add Instructor Form -->
                    <form id="addInstructorForm" style="display: none;">
                        <h6 class="text-center mb-3">Add New Instructor</h6>
                        <div class="form-group">
                            <label for="newInstructorName"><b>Instructor Name:</b></label>
                            <input type="text" class="form-control" id="newInstructorName" required>
                        </div>
                        <div class="form-group">
                            <label for="newInstructorSubjects"><b>Subjects (by using commas, it will <mark>separate</mark> multiple subjects):</b></label>
                            <textarea class="form-control" id="newInstructorSubjects" rows="2" placeholder="Math, Science, English" required></textarea>
                            <small class="form-text text-muted">Enter multiple subjects separated by commas</small>
                        </div>
                        <button type="button" id="submitAddInstructor" class="btn btn-success btn-block">Save Instructor</button>
                        <button type="button" class="btn btn-secondary btn-block cancelInstructorAction">Cancel</button>
                    </form>
                    
                    <!-- Edit Instructor Form -->
                    <form id="editInstructorForm" style="display: none;">
                        <h6 class="text-center mb-3">Edit Instructor</h6>
                        <div class="form-group">
                            <label for="editInstructorSelect"><b>Select Instructor to Edit:</b></label>
                            <select class="form-control" id="editInstructorSelect">
                                <option value="">-- Select Instructor --</option>
                                <?php
                                // Reset the result pointer
                                if ($instructors_result) {
                                    $instructors_result->data_seek(0);
                                    while ($instructor = $instructors_result->fetch_assoc()) {
                                        echo "<option value=\"{$instructor['instructor_id']}\" data-subjects=\"{$instructor['subjects']}\">{$instructor['instructor_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editInstructorName"><b>Instructor Name:</b></label>
                            <input type="text" class="form-control" id="editInstructorName" required>
                        </div>
                        <div class="form-group">
                            <label for="editInstructorSubjects"><b>Subjects (comma-separated):</b></label>
                            <textarea class="form-control" id="editInstructorSubjects" rows="2" placeholder="Math, Science, English" required></textarea>
                            <small class="form-text text-muted">Enter multiple subjects separated by commas</small>
                        </div>
                        <button type="button" id="submitEditInstructor" class="btn btn-warning btn-block">Update Instructor</button>
                        <button type="button" class="btn btn-secondary btn-block cancelInstructorAction">Cancel</button>
                    </form>
                    
                    <!-- View/Manage Subjects Form -->
                    <form id="manageSubjectsForm" style="display: none;">
                        <h6 class="text-center mb-3"><b>Manage Instructor Subjects</b></h6>
                        <div class="form-group">
                            <label for="subjectInstructorSelect"><b>Select First the Instructor:</b></label>
                            <select class="form-control" id="subjectInstructorSelect">
                                <option value="">-- Select Instructor --</option>
                                <?php
                                // Reset the result pointer
                                if ($instructors_result) {
                                    $instructors_result->data_seek(0);
                                    while ($instructor = $instructors_result->fetch_assoc()) {
                                        echo "<option value=\"{$instructor['instructor_id']}\">{$instructor['instructor_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div id="instructorSubjectsList" class="mb-3" style="display: none;">
                            <label>Select from the list of Current Subjects:</label>
                            <div class="list-group" id="subjectListContainer">
                                <!-- Subjects will be populated here dynamically -->
                            </div>
                            
                        </div>
                        <div class="form-group" id="addSubjectContainer" style="display: none;">
                            <label for="newSubjectName">Add Subject:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="newSubjectName" placeholder="Enter new subject">
                                <div class="input-group-append">
                                    <button type="button" id="addSubjectBtn" class="btn btn-success">Add</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-block cancelInstructorAction">Done</button>
                    </form>
                    
                    <!-- Delete Instructor Form -->
                    <form id="deleteInstructorForm" style="display: none;">
                        <h6 class="text-center mb-3"><b>Delete Instructor</b></h6>
                        <div class="form-group">
                            <label for="deleteInstructorSelect"><b>Select Instructor to Delete:</b></label>
                            <select class="form-control" id="deleteInstructorSelect">
                                <option value="">-- Select Instructor --</option>
                                <?php
                                // Reset the result pointer
                                if ($instructors_result) {
                                    $instructors_result->data_seek(0);
                                    while ($instructor = $instructors_result->fetch_assoc()) {
                                        echo "<option value=\"{$instructor['instructor_id']}\">{$instructor['instructor_name']} - {$instructor['subjects']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="alert alert-danger">
                            <strong>Warning!</strong> This will delete the instructor and all their associated subjects.
                        </div>
                        <button type="button" id="submitDeleteInstructor" class="btn btn-danger btn-block">Delete Instructor</button>
                        <button type="button" class="btn btn-secondary btn-block cancelInstructorAction">Cancel</button>
                    </form>
                    
                    <!-- Instructor Message Alert Area -->
                    <div id="instructorAlertArea" style="display: none;" class="mt-2 alert"></div>
                </div>
            </div>

            <div class="attendance-list col-md-9 col-sm-12 px-md-3" style="display: flex; flex-direction: column;">
                <div class="card p-3 mb-3">
                    <!-- Live Clock Display -->
                    <div class="live-time-container text-center mb-4" style="background-color: #f8f9fa; padding: 12px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 5px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span id="currentDate" style="font-weight: bold; margin-right: 15px; font-size: 1.1rem;"></span>
                                <span id="currentTime" style="font-size: 1.6rem; font-weight: bold; color: #098744;"></span>
                            </div>
                            <div id="attendanceNotification" class="alert alert-success py-2 px-3 mb-0" style="display: <?= isset($_GET['success']) && $_GET['success'] == 'attendance_added' ? 'block' : 'none' ?>; max-width: 300px;">
                                <i class="fas fa-check-circle"></i> <span id="notificationMessage">Attendance recorded successfully!</span>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="text-center mb-3">List of Present Students</h4>
  
                    <div class="table-container table-responsive" style="flex: 1; overflow: auto;">
                        <table class="table text-center table-sm table-bordered" id="attendanceTable" style="border-collapse: separate; border-spacing: 0; overflow: hidden; width: 100%;">
                            <thead style="background-color: #098744; color: white; position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th scope="col" style="width: 5%; padding: 12px 8px;">#</th>
                                    <th scope="col" style="width: 20%; padding: 12px 8px;">Name</th>
                                    <th scope="col" style="width: 15%; padding: 12px 8px;">Course & Section</th>
                                    <th scope="col" style="width: 15%; padding: 12px 8px; min-width: 120px;">Date</th>
                                    <th scope="col" style="width: 15%; padding: 12px 8px; min-width: 100px;">Time In</th>
                                    <th scope="col" style="width: 15%; padding: 12px 8px; min-width: 100px;">Status</th>
                                    <th scope="col" style="width: 10%; padding: 12px 8px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                date_default_timezone_set('Asia/Manila');

                                try {
                                    // Pagination logic
                                    $limit = 9;
                                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                    $offset = ($page - 1) * $limit;

                                    // Get user context for data isolation
                                    $context = getCurrentUserContext();
                                    
                                    $query = "
                                        SELECT a.*, s.student_name, s.course_section 
                                        FROM tbl_attendance a
                                        LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
                                        WHERE a.time_in IS NOT NULL
                                        AND a.school_id = {$context['school_id']}
                                        " . ($context['user_id'] ? "AND (a.user_id = {$context['user_id']} OR a.user_id IS NULL)" : "") . "
                                        ORDER BY a.time_in DESC 
                                        LIMIT ?, ?
                                    ";
                                    
                                    $stmt = $conn_qr->prepare($query);
                                    $stmt->bind_param("ii", $offset, $limit);
                                    $stmt->execute();
                                    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                                    // Get total records for pagination with isolation
                                    $totalQuery = "SELECT COUNT(*) as total FROM tbl_attendance 
                                                  WHERE school_id = {$context['school_id']}
                                                  " . ($context['user_id'] ? "AND (user_id = {$context['user_id']} OR user_id IS NULL)" : "");
                                    $totalStmt = $conn_qr->query($totalQuery);
                                    $totalRow = $totalStmt->fetch_assoc();
                                    $totalRecords = $totalRow['total'];
                                    $totalPages = ceil($totalRecords / $limit);

                                    if (empty($result)) {
                                        echo "<tr><td colspan='7' class='text-center'>No records found.</td></tr>";
                                    } else {
                                        // Move the most recent attendance to the top
                                        $recentAttendance = array_shift($result);
                                        
                                        // Use the status stored in the database - do not recalculate based on class time
                                        $status = isset($recentAttendance["status"]) && !empty($recentAttendance["status"]) ? 
                                               $recentAttendance["status"] : 'Unknown';
                                        $statusClass = ($status == 'On Time') ? '' : 'text-danger';
                                        
                                        // Display the most recent attendance record
                                        echo "<tr style='background-color: #098744; color: white;'>
                                                <th scope='row'>{$recentAttendance['tbl_attendance_id']}</th>
                                                <td>{$recentAttendance['student_name']}</td>
                                                <td>{$recentAttendance['course_section']}</td>
                                                <td style='white-space: nowrap;'>" . date('M d, Y', strtotime($recentAttendance["time_in"])) . "</td>
                                                <td style='white-space: nowrap;'>" . date('h:i:s A', strtotime($recentAttendance["time_in"])) . "</td>
                                                <td style='white-space: nowrap;' class='{$statusClass}'><strong>{$status}</strong></td>
                                                <td>
                                                    <div class='action-button'>
                                                        <button class='btn btn-danger delete-button' onclick='deleteAttendance({$recentAttendance['tbl_attendance_id']})'>X</button>
                                                    </div>
                                                </td>
                                            </tr>";

                                        // Display remaining attendance records
                                        foreach ($result as $row) {
                                            $attendanceID = $row["tbl_attendance_id"];
                                            $studentName = $row["student_name"];
                                            $studentCourse = $row["course_section"];
                                            $attendanceDate = date('M d, Y', strtotime($row["time_in"]));
                                            $timeIn = date('h:i:s A', strtotime($row["time_in"]));
                                            
                                            // Use the status stored in the database - do not recalculate
                                            $row_status = isset($row["status"]) && !empty($row["status"]) ? 
                                                      $row["status"] : 'Unknown';
                                            $row_statusClass = ($row_status == 'On Time') ? '' : 'text-danger';
                                    ?>
                                <tr style="background-color: #098744; color: white;">
                                    <th scope="row"><?= $attendanceID ?></th>
                                    <td><?= $studentName ?></td>
                                    <td><?= $studentCourse ?></td>
                                    <td style="white-space: nowrap;"><?= $attendanceDate ?></td>
                                    <td style="white-space: nowrap;"><?= $timeIn ?></td>
                                    <td style="white-space: nowrap;" class="<?= $row_statusClass ?>"><strong><?= $row_status ?></strong></td>
                                    <td>
                                        <div class="action-button">
                                            <button class="btn btn-danger delete-button" onclick="deleteAttendance(<?= $attendanceID ?>)">X</button>
                                        </div>
                                    </td>
                                </tr>

                                    <?php
                                        }
                                    }
                                } catch (Exception $e) {
                                    echo "<tr><td colspan='7' class='text-center'>Error: " . $e->getMessage() . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Container -->
                    <div id="paginationContainer" class="pagination-container">
                        <!-- Pagination will be inserted here by JavaScript -->
                    </div>
                        
                    <!-- Print Button -->
                    <div class="text-center mt-3">
                        <button onclick="printAttendance()" class="btn" style="background-color: #098744; color: white; border-color: #098744;">
                            <i class="fas fa-print"></i> Print Attendance List
                        </button>
                        
                        <!-- Export Buttons -->
                        <div class="mt-2">
                            <form method="GET" action="export_attendance.php" class="d-inline">
                                <input type="hidden" name="format" value="csv">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                            </form>
                            <form method="GET" action="export_attendance.php" class="d-inline">
                                <input type="hidden" name="format" value="excel">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                            </form>
                            <form method="GET" action="export_attendance.php" class="d-inline">
                                <input type="hidden" name="format" value="pdf">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

   <!-- HTML for overlay and popups -->
<div class="overlay"></div>

<!-- Confirmation popup -->
<div class="popup">
    <h5 style="font-weight: bold; margin-bottom: 15px; color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Warning</h5>
    <p style="color: #dc3545; font-size: 16px; font-weight: bold;">Are you sure you want to delete this attendance record?</p>
    <div>
        <button class="confirm-delete">Yes</button>
        <button class="cancel-delete">Cancel</button>
    </div>
</div>

<!-- Success message popup -->
<div class="success-popup">
    <p>Successfully deleted!</p>
</div>

<!-- Subject Removal Confirmation Modal -->
<div class="modal fade" id="subjectRemovalModal" tabindex="-1" role="dialog" aria-labelledby="subjectRemovalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="subjectRemovalModalLabel"><i class="fas fa-exclamation-triangle"></i> Confirm Subject Removal</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove the subject <strong id="modalSubjectName"></strong> from instructor <strong id="modalInstructorName"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmSubjectRemoval">Remove Subject</button>
            </div>
        </div>
    </div>
</div>

<!-- Subject Removal Success Modal -->
<div class="modal fade" id="subjectRemovalSuccessModal" tabindex="-1" role="dialog" aria-labelledby="subjectRemovalSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="subjectRemovalSuccessModalLabel"><i class="fas fa-check-circle"></i> Success</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h4>Subject Removed Successfully!</h4>
                <p>The subject <strong id="successModalSubjectName"></strong> has been removed from <strong id="successModalInstructorName"></strong>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Duplicate Attendance Modal -->
<div class="modal fade" id="duplicateAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="duplicateAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="duplicateAttendanceModalLabel"><i class="fas fa-exclamation-circle"></i> Duplicate Attendance</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <p><strong>Student has already been marked present for this subject and instructor today!</strong></p>
                    <hr>
                    <div class="student-details">
                        <p><strong>Student Name:</strong> <span id="modal-student-name"></span></p>
                        <p><strong>Instructor:</strong> <span id="modal-instructor-name"></span></p>
                        <p><strong>Subject:</strong> <span id="modal-subject-name"></span></p>
                        <p><strong>Attendance Date:</strong> <span id="modal-attendance-date"></span></p>
                        <p><strong>Attendance Time:</strong> <span id="modal-attendance-time"></span></p>
                        <p><strong>Status:</strong> <span id="modal-attendance-status"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="<?php echo asset_url('js/popper.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.min.js'); ?>"></script>

    <!-- instascan Js -->
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <script src="./functions/pagination.js"></script>

    <!--sidebar-toggle-->
    <script>
        $(document).ready(function() {
            // Initialize pagination with standardized function
            createStandardPagination(
                <?php echo $page; ?>, // Current page
                <?php echo $totalPages; ?>, // Total pages
                '?', // Base URL
                'paginationContainer' // Container ID
            );
        });
        
        // Schedule filter handling
        const instructorSelect = document.getElementById('instructorSelect');
        const sectionSelect = document.getElementById('sectionSelect');
        const subjectSelect = document.getElementById('subjectSelect');
        const scheduleInfo = document.getElementById('scheduleInfo');

        // Function to update schedule info display
        function updateScheduleInfo(scheduleData) {
            if (scheduleData && scheduleData.length > 0) {
                const schedule = scheduleData[0]; // Get first matching schedule
                document.getElementById('scheduleInstructor').textContent = schedule.instructor_name || 'N/A';
                document.getElementById('scheduleSection').textContent = schedule.course_section || 'N/A';
                document.getElementById('scheduleSubject').textContent = schedule.subject || 'N/A';
                document.getElementById('scheduleTime').textContent = `${schedule.start_time} - ${schedule.end_time}`;
                scheduleInfo.style.display = 'block';
            } else {
                scheduleInfo.style.display = 'none';
            }
        }

        // Function to fetch filtered schedule data
        async function fetchFilteredSchedules() {
            const instructor = instructorSelect.value;
            const section = sectionSelect.value;
            const subject = subjectSelect.value;

            try {
                const response = await fetch('api/get-filtered-schedules.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        instructor: instructor,
                        section: section,
                        subject: subject
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    updateScheduleInfo(data);

                    // Update class time if schedule is found
                    if (data && data.length > 0) {
                        const schedule = data[0];
                        document.getElementById('classStartTime').value = schedule.start_time;
                        // Trigger class time update
                        document.getElementById('setClassTime').click();
                    }
                }
            } catch (error) {
                console.error('Error fetching schedule data:', error);
            }
        }

        // Add event listeners to select elements
        instructorSelect.addEventListener('change', fetchFilteredSchedules);
        sectionSelect.addEventListener('change', fetchFilteredSchedules);
        subjectSelect.addEventListener('change', fetchFilteredSchedules);

        function toggleYearLevel(event) {
            event.preventDefault();

            const clickedYear = event.currentTarget;
            const submenu = clickedYear.nextElementSibling;
            const arrow = clickedYear.querySelector('.arrow');

            // Close all other year-level submenus
            document.querySelectorAll('.submenu').forEach(menu => {
                if (menu !== submenu) {
                    menu.style.display = 'none';
                    const otherArrow = menu.previousElementSibling.querySelector('.arrow');
                    if (otherArrow) otherArrow.style.transform = 'rotate(0deg)';
                }
            });

            // Toggle the clicked year-level submenu
            const isHidden = submenu.style.display === 'none' || submenu.style.display === '';
            submenu.style.display = isHidden ? 'block' : 'none';
            arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
        }

        function showCourses(year) {
            const courseSubmenu = document.getElementById(year + '-courses');
            const yearToggle = courseSubmenu.previousElementSibling;
            const arrow = yearToggle.querySelector('.year-arrow');

            // Close all other course submenus
            document.querySelectorAll('.course-submenu').forEach(menu => {
                if (menu !== courseSubmenu) {
                    menu.style.display = 'none';
                    const otherArrow = menu.previousElementSibling.querySelector('.year-arrow');
                    if (otherArrow) otherArrow.style.transform = 'rotate(0deg)';
                }
            });

            // Toggle the selected course submenu
            const isHidden = courseSubmenu.style.display === 'none' || courseSubmenu.style.display === '';
            courseSubmenu.style.display = isHidden ? 'block' : 'none';
            arrow.style.transform = isHidden ? 'rotate(90deg)' : 'rotate(0deg)';
        }

        // Function to handle attendance deletion
        function deleteAttendance(id) {
            // Show overlay and popup
            const overlay = document.querySelector('.overlay');
            const popup = document.querySelector('.popup');
            const successPopup = document.querySelector('.success-popup');
            
            overlay.style.display = 'block';
            popup.style.display = 'block';
            
            // Store the ID for deletion
            let attendanceIdToDelete = id;
            
            // Set up confirm and cancel buttons
            document.querySelector('.confirm-delete').onclick = function() {
                // Redirect to delete the attendance
                window.location.href = 'index.php?attendance=' + attendanceIdToDelete;
            };
            
            document.querySelector('.cancel-delete').onclick = function() {
                // Hide overlay and popup
                overlay.style.display = 'none';
                popup.style.display = 'none';
            };
        }

        // Function to print attendance
        function printAttendance() {
            window.location.href = 'print-attendance.php';
        }

        // Class Time Setting Functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const errorParam = urlParams.get('error');
            const successParam = urlParams.get('success');
            
            // Handle error messages
            if (errorParam) {
                switch(errorParam) {
                    case 'duplicate_scan':
                        showDuplicateAttendanceModal();
                        break;
                    case 'invalid_qr':
                        showCustomAlert('Invalid QR Code!', 'error');
                        break;
                    case 'empty_qr':
                        showCustomAlert('QR Code cannot be empty!', 'error');
                        break;
                    case 'missing_qr':
                        showCustomAlert('QR Code is required!', 'error');
                        break;
                    case 'db_error':
                        showCustomAlert('Database error occurred!', 'error');
                        break;
                }
            }
            
            // Handle success messages
            if (successParam) {
                switch(successParam) {
                    case 'attendance_added':
                        // Show in the notification area next to the clock
                        const notificationArea = document.getElementById('attendanceNotification');
                        const notificationMessage = document.getElementById('notificationMessage');
                        
                        if (notificationArea && notificationMessage) {
                            notificationMessage.textContent = 'Attendance recorded successfully!';
                            notificationArea.className = 'alert py-2 px-3 mb-0 alert-success';
                            notificationArea.style.display = 'block';
                            
                            // Keep visible for only 1.5 seconds as requested
                            setTimeout(function() {
                                notificationArea.style.display = 'none';
                            }, 1500);
                        }
                        break;
                }
            }
            
            // Function to display duplicate attendance modal
            function showDuplicateAttendanceModal() {
                // Get data from localStorage
                const duplicateData = JSON.parse(localStorage.getItem('duplicateAttendance') || '{}');
                
                // Fill modal with data
                document.getElementById('modal-student-name').textContent = duplicateData.studentName || 'N/A';
                document.getElementById('modal-instructor-name').textContent = duplicateData.instructorName || 'N/A';
                document.getElementById('modal-subject-name').textContent = duplicateData.subjectName || 'N/A';
                document.getElementById('modal-attendance-date').textContent = duplicateData.attendanceDate || 'N/A';
                document.getElementById('modal-attendance-time').textContent = duplicateData.attendanceTime || 'N/A';
                
                // Set status with appropriate color
                const statusElement = document.getElementById('modal-attendance-status');
                statusElement.textContent = duplicateData.attendanceStatus || 'N/A';
                if (duplicateData.attendanceStatus === 'Late') {
                    statusElement.classList.add('text-danger');
                }
                
                // Show the modal
                $('#duplicateAttendanceModal').modal('show');
                
                // Clear the localStorage data
                localStorage.removeItem('duplicateAttendance');
            }
            
            // Set Class Time
            document.getElementById('setClassTime').addEventListener('click', function() {
                const startTime = document.getElementById('classStartTime').value;
                const duration = document.getElementById('classDuration').value;
                
                if (!startTime) {
                    showClassTimeAlert('Please select a valid start time', 'danger');
                    return;
                }
                
                // Hide any previous alerts or session info
                document.getElementById('classTimeAlertArea').style.display = 'none';
                document.getElementById('sessionInfoArea').style.display = 'none';
                
                // Save the time to session via AJAX
                fetch('api/set-class-time.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'classStartTime=' + encodeURIComponent(startTime)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Convert 24-hour time to 12-hour time for display
                        const timeForDisplay = convertTo12Hour(startTime);
                        document.getElementById('displayedStartTime').textContent = timeForDisplay;
                        document.getElementById('currentTimeSettings').style.display = 'block';
                        
                        // Show success message
                        showClassTimeAlert('Class time set successfully!', 'success');
                        
                        // Create attendance session
                        createAttendanceSession(startTime, duration);
                    } else {
                        showClassTimeAlert(data.message || 'Failed to set class time', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showClassTimeAlert('An error occurred while setting class time', 'danger');
                });
            });
            
            // Function to create an attendance session
            function createAttendanceSession(startTime, duration) {
                // Check if an instructor is selected
                if (!document.getElementById('currentInstructorInfo').style.display || 
                    document.getElementById('currentInstructorInfo').style.display === 'none') {
                    showClassTimeAlert('Please select an instructor before creating an attendance session', 'warning');
                    return;
                }
                
                // Create attendance session via AJAX
                fetch('api/create-attendance-session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'start_time=' + encodeURIComponent(startTime) + '&duration=' + encodeURIComponent(duration)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show session info
                        const sessionDetails = document.getElementById('sessionDetails');
                        sessionDetails.innerHTML = `
                            <p><strong>Subject:</strong> ${document.getElementById('displayedSubject').textContent}</p>
                            <p><strong>Instructor:</strong> ${document.getElementById('displayedInstructorName').textContent}</p>
                            <p><strong>Start Time:</strong> ${convertTo12Hour(startTime)}</p>
                            <p><strong>Duration:</strong> ${duration} minutes</p>
                        `;
                        document.getElementById('sessionInfoArea').style.display = 'block';
                    } else {
                        showClassTimeAlert(data.message || 'Failed to create attendance session', 'warning');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showClassTimeAlert('An error occurred while creating attendance session', 'danger');
                });
            }
            
            // Convert 24-hour time to 12-hour time with AM/PM
            function convertTo12Hour(time24) {
                const [hours, minutes] = time24.split(':');
                const period = hours >= 12 ? 'PM' : 'AM';
                const hours12 = hours % 12 || 12;
                return `${hours12}:${minutes} ${period}`;
            }
            
            // Function to show alerts in the class time section
            function showClassTimeAlert(message, type) {
                const alertArea = document.getElementById('classTimeAlertArea');
                alertArea.textContent = message;
                alertArea.className = 'mt-2 alert';
                alertArea.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
                alertArea.style.display = 'block';
                
                // Auto hide after 3 seconds if it's a success message
                if (type === 'success') {
                    setTimeout(function() {
                        alertArea.style.display = 'none';
                    }, 3000);
                }
            }
            
            // Live time function 
            function updateClock() {
                const now = new Date();
                
                // Format time: 12-hour with AM/PM
                let hours = now.getHours();
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const seconds = now.getSeconds().toString().padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12; // Convert 0 to 12
                const timeString = `${hours}:${minutes}:${seconds} ${ampm}`;
                
                // Format date: Month Day, Year
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const dateString = now.toLocaleDateString('en-US', options);
                
                // Update the display if elements exist
                const timeElement = document.getElementById('currentTime');
                const dateElement = document.getElementById('currentDate');
                
                if (timeElement) timeElement.textContent = timeString;
                if (dateElement) dateElement.textContent = dateString;
            }
            
            // Make sure the clock starts immediately when page loads
            window.addEventListener('load', function() {
                // Initial call
                updateClock();
                // Update every second
                setInterval(updateClock, 1000);
            });
        });
    </script>

    <script>
        // Ensure this script runs immediately
        (function() {
            // Live clock function that updates every second
            function updateLiveClock() {
                const now = new Date();
                
                // Format time with leading zeros for minutes and seconds
                let hours = now.getHours();
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const seconds = now.getSeconds().toString().padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12; // Convert 0 to 12
                const timeString = `${hours}:${minutes}:${seconds} ${ampm}`;
                
                // Format full date: Weekday, Month Day, Year
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const dateString = now.toLocaleDateString('en-US', options);
                
                // Update the display
                document.getElementById('currentTime').textContent = timeString;
                document.getElementById('currentDate').textContent = dateString;
            }
            
            // Run once immediately
            updateLiveClock();
            
            // Then update every second
            setInterval(updateLiveClock, 1000);
        })();
    </script>

    <!-- Custom alert function -->
    <script>
        function showCustomAlert(message, type) {
            const alertBox = document.getElementById('customAlert');
            alertBox.textContent = message;
            alertBox.className = 'custom-alert';
            alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
            alertBox.style.display = 'block';
            
            setTimeout(function() {
                alertBox.style.display = 'none';
            }, 1500);
        }
        
        function showAttendanceNotification(message, type) {
            // Show in both places - the notification area and the custom alert
            const notificationArea = document.getElementById('attendanceNotification');
            const notificationMessage = document.getElementById('notificationMessage');
            
            if (notificationArea && notificationMessage) {
                notificationMessage.textContent = message;
                notificationArea.className = 'alert py-2 px-3 mb-0';
                notificationArea.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
                notificationArea.style.display = 'block';
                
                // Highlight with animation
                notificationArea.style.animation = 'pulse 2s infinite';
                
                // Keep visible for only 1.5 seconds
                setTimeout(function() {
                    notificationArea.style.animation = '';
                    notificationArea.style.display = 'none';
                }, 1500);
            }
            
            // Also show in custom alert for consistency
            showCustomAlert(message, type);
        }
    </script>

    <!-- Instructor Management JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle forms visibility
            document.getElementById('showAddInstructor').addEventListener('click', function() {
                hideAllInstructorForms();
                document.getElementById('addInstructorForm').style.display = 'block';
            });
            
            document.getElementById('showEditInstructor').addEventListener('click', function() {
                hideAllInstructorForms();
                document.getElementById('editInstructorForm').style.display = 'block';
            });
            
            document.getElementById('showManageSubjects').addEventListener('click', function() {
                hideAllInstructorForms();
                document.getElementById('manageSubjectsForm').style.display = 'block';
            });
            
            document.getElementById('showDeleteInstructor').addEventListener('click', function() {
                hideAllInstructorForms();
                document.getElementById('deleteInstructorForm').style.display = 'block';
            });
            
            // Cancel buttons
            document.querySelectorAll('.cancelInstructorAction').forEach(function(button) {
                button.addEventListener('click', function() {
                    hideAllInstructorForms();
                });
            });
            
            // Load subjects when instructor is selected
            document.getElementById('instructorSelect').addEventListener('change', function() {
                const instructorId = this.value;
                const subjectContainer = document.querySelector('.subject-select-container');
                
                if (!instructorId) {
                    subjectContainer.style.display = 'none';
                    return;
                }
                
                // Fetch subjects for this instructor
                const formData = new FormData();
                formData.append('action', 'get_subjects');
                formData.append('instructor_id', instructorId);
                
                fetch('endpoint/save-instructor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.subjects.length > 0) {
                        // Populate subject dropdown
                        const subjectSelect = document.getElementById('subjectSelect');
                        subjectSelect.innerHTML = ''; // Clear existing options
                        
                        data.data.subjects.forEach(subject => {
                            const option = document.createElement('option');
                            option.value = subject.subject_id;
                            option.textContent = subject.subject_name;
                            subjectSelect.appendChild(option);
                        });
                        
                        // Show subject selection
                        subjectContainer.style.display = 'block';
                    } else {
                        subjectContainer.style.display = 'none';
                        showInstructorAlert('No subjects found for this instructor', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading subjects:', error);
                    subjectContainer.style.display = 'none';
                    showInstructorAlert('Error loading subjects', 'error');
                });
            });
            
            
            // Set current instructor
            document.getElementById('setCurrentInstructor').addEventListener('click', function() {
                const instructorSelect = document.getElementById('instructorSelect');
                const instructorId = instructorSelect.value;
                const subjectSelect = document.getElementById('subjectSelect');
                const subjectId = subjectSelect.style.display !== 'none' ? subjectSelect.value : '';
                
                if (!instructorId) {
                    showInstructorAlert('Please select an instructor', 'error');
                    return;
                }
                
                // Send AJAX request to set current instructor
                const formData = new FormData();
                formData.append('action', 'set');
                formData.append('instructor_id', instructorId);
                if (subjectId) {
                    formData.append('subject_id', subjectId);
                }
                
                fetch('endpoint/save-instructor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update displayed instructor info
                        document.getElementById('displayedInstructorName').textContent = data.data.instructor_name;
                        document.getElementById('displayedSubject').textContent = data.data.subject_name || 'None';
                        document.getElementById('currentInstructorInfo').style.display = 'block';
                        
                        showInstructorAlert('Current instructor set successfully', 'success');
                    } else {
                        showInstructorAlert(data.message || 'Failed to set instructor', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error setting instructor:', error);
                    showInstructorAlert('An error occurred while setting the instructor', 'error');
                });
            });
            
            // Add new instructor
            document.getElementById('submitAddInstructor').addEventListener('click', function() {
                const name = document.getElementById('newInstructorName').value.trim();
                const subjectsText = document.getElementById('newInstructorSubjects').value.trim();
                
                if (!name || !subjectsText) {
                    showInstructorAlert('Please fill in all fields', 'error');
                    return;
                }
                
                // Split subjects by comma and trim whitespace
                const subjects = subjectsText.split(',').map(s => s.trim()).filter(s => s);
                
                if (subjects.length === 0) {
                    showInstructorAlert('Please enter at least one subject', 'error');
                    return;
                }
                
                // Send AJAX request to add instructor
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('name', name);
                formData.append('subjects', subjects.join(','));
                
                fetch('endpoint/save-instructor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear form
                        document.getElementById('newInstructorName').value = '';
                        document.getElementById('newInstructorSubjects').value = '';
                        
                        // Hide form
                        document.getElementById('addInstructorForm').style.display = 'none';
                        
                        // Show success message
                        showInstructorAlert('Instructor added successfully', 'success');
                        
                        // Refresh instructor lists
                        refreshInstructorLists();
                    } else {
                        showInstructorAlert(data.message || 'Failed to add instructor', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error adding instructor:', error);
                    showInstructorAlert('An error occurred while adding the instructor', 'error');
                });
            });
            
            // Edit instructor
            document.getElementById('editInstructorSelect').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const subjects = selectedOption.getAttribute('data-subjects') || '';
                
                document.getElementById('editInstructorName').value = selectedOption.text;
                document.getElementById('editInstructorSubjects').value = subjects;
            });
            
            document.getElementById('submitEditInstructor').addEventListener('click', function() {
                const instructorId = document.getElementById('editInstructorSelect').value;
                const name = document.getElementById('editInstructorName').value.trim();
                const subjectsText = document.getElementById('editInstructorSubjects').value.trim();
                
                if (!instructorId || !name || !subjectsText) {
                    showInstructorAlert('Please fill in all fields', 'error');
                    return;
                }
                
                // Split subjects by comma and trim whitespace
                const subjects = subjectsText.split(',').map(s => s.trim()).filter(s => s);
                
                if (subjects.length === 0) {
                    showInstructorAlert('Please enter at least one subject', 'error');
                    return;
                }
                
                // Send AJAX request to edit instructor
                const formData = new FormData();
                formData.append('action', 'edit');
                formData.append('instructor_id', instructorId);
                formData.append('name', name);
                formData.append('subjects', subjects.join(','));
                
                fetch('endpoint/save-instructor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear form
                        document.getElementById('editInstructorName').value = '';
                        document.getElementById('editInstructorSubjects').value = '';
                        document.getElementById('editInstructorSelect').value = '';
                        
                        // Hide form
                        document.getElementById('editInstructorForm').style.display = 'none';
                        
                        // Show success message
                        showInstructorAlert('Instructor updated successfully', 'success');
                        
                        // Refresh instructor lists
                        refreshInstructorLists();
                        
                        // Update current instructor display if it was the one edited
                        if (document.getElementById('currentInstructorInfo').style.display !== 'none') {
                            const currentInstructorId = document.getElementById('setInstructorForm').querySelector('#instructorSelect').value;
                            if (parseInt(currentInstructorId) === parseInt(instructorId)) {
                                // Refresh the current instructor display by getting latest data
                                const refreshFormData = new FormData();
                                refreshFormData.append('action', 'set');
                                refreshFormData.append('instructor_id', instructorId);
                                
                                fetch('endpoint/save-instructor.php', {
                                    method: 'POST',
                                    body: refreshFormData
                                })
                                .then(response => response.json())
                                .then(refreshData => {
                                    if (refreshData.success) {
                                        document.getElementById('displayedInstructorName').textContent = refreshData.data.instructor_name;
                                        document.getElementById('displayedSubject').textContent = refreshData.data.subject_name || 'None';
                                    }
                                })
                                .catch(console.error);
                            }
                        }
                    } else {
                        showInstructorAlert(data.message || 'Failed to update instructor', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error updating instructor:', error);
                    showInstructorAlert('An error occurred while updating the instructor', 'error');
                });
            });
            
            // Delete instructor
            document.getElementById('submitDeleteInstructor').addEventListener('click', function() {
                const instructorId = document.getElementById('deleteInstructorSelect').value;
                
                if (!instructorId) {
                    showInstructorAlert('Please select an instructor to delete', 'error');
                    return;
                }
                
                // Confirm deletion
                if (!confirm('Are you sure you want to delete this instructor? This action cannot be undone.')) {
                    return;
                }
                
                // Send AJAX request to delete instructor
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('instructor_id', instructorId);
                
                fetch('endpoint/save-instructor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear form
                        document.getElementById('deleteInstructorSelect').value = '';
                        
                        // Hide form
                        document.getElementById('deleteInstructorForm').style.display = 'none';
                        
                        // Show success message
                        showInstructorAlert('Instructor deleted successfully', 'success');
                        
                        // Refresh instructor lists
                        refreshInstructorLists();
                        
                        // Check if the deleted instructor was the current one
                        if (document.getElementById('currentInstructorInfo').style.display !== 'none') {
                            // If current instructor info is showing, refresh it or hide it
                            fetch('endpoint/save-instructor.php', {
                                method: 'POST',
                                body: new URLSearchParams({ 'action': 'list' })
                            })
                            .then(response => response.json())
                            .then(listData => {
                                if (!listData.data.current_instructor.id) {
                                    // Current instructor was deleted, hide the info
                                    document.getElementById('currentInstructorInfo').style.display = 'none';
                                }
                            });
                        }
                    } else {
                        showInstructorAlert(data.message || 'Failed to delete instructor', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting instructor:', error);
                    showInstructorAlert('An error occurred while deleting the instructor', 'error');
                });
            });
            
            // Helper function to hide all instructor forms
            function hideAllInstructorForms() {
                document.getElementById('addInstructorForm').style.display = 'none';
                document.getElementById('editInstructorForm').style.display = 'none';
                document.getElementById('manageSubjectsForm').style.display = 'none';
                document.getElementById('deleteInstructorForm').style.display = 'none';
            }
            
            // Helper function to show instructor alerts
            function showInstructorAlert(message, type, autoHide = true) {
                const alertArea = document.getElementById('instructorAlertArea');
                alertArea.textContent = message;
                alertArea.className = 'mt-2 alert';
                
                // Set the appropriate alert class based on type
                if (type === 'success') {
                    alertArea.classList.add('alert-success');
                } else if (type === 'error') {
                    alertArea.classList.add('alert-danger');
                } else if (type === 'info') {
                    alertArea.classList.add('alert-info');
                } else {
                    alertArea.classList.add('alert-warning');
                }
                
                alertArea.style.display = 'block';
                
                // Clear any existing timers
                if (window.alertTimer) {
                    clearTimeout(window.alertTimer);
                }
                
                // Auto hide after 3 seconds if autoHide is true
                if (autoHide) {
                    window.alertTimer = setTimeout(function() {
                        alertArea.style.display = 'none';
                    }, 3000);
                }
            }
            
            // Helper function to refresh instructor lists
            function refreshInstructorLists() {
                fetch('endpoint/save-instructor.php', {
                    method: 'POST',
                    body: new URLSearchParams({ 'action': 'list' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const instructors = data.data.instructors;
                        const currentInstructor = data.data.current_instructor;
                        
                        // Update main instructor dropdown
                        updateInstructorDropdown('instructorSelect', instructors, currentInstructor.id);
                        
                        // Update edit instructor dropdown
                        updateEditInstructorDropdown(instructors);
                        
                        // Update delete instructor dropdown
                        updateDeleteInstructorDropdown(instructors);
                    }
                })
                .catch(error => {
                    console.error('Error refreshing instructor lists:', error);
                });
            }
            
            // Helper function to update main instructor dropdown
            function updateInstructorDropdown(selectId, instructors, selectedId = null) {
                const select = document.getElementById(selectId);
                select.innerHTML = '<option value="">-- Select Instructor --</option>';
                
                instructors.forEach(function(instructor) {
                    const option = document.createElement('option');
                    option.value = instructor.instructor_id;
                    option.textContent = instructor.instructor_name;
                    option.setAttribute('data-subjects', instructor.subjects || '');
                    
                    if (selectedId && instructor.instructor_id == selectedId) {
                        option.selected = true;
                    }
                    
                    select.appendChild(option);
                });
                
                // Reset subject selection visibility
                document.querySelector('.subject-select-container').style.display = 'none';
            }
            
            // Helper function to update edit instructor dropdown
            function updateEditInstructorDropdown(instructors) {
                const select = document.getElementById('editInstructorSelect');
                select.innerHTML = '<option value="">-- Select Instructor --</option>';
                
                instructors.forEach(function(instructor) {
                    const option = document.createElement('option');
                    option.value = instructor.instructor_id;
                    option.textContent = instructor.instructor_name;
                    option.setAttribute('data-subjects', instructor.subjects || '');
                    select.appendChild(option);
                });
            }
            
            // Helper function to update delete instructor dropdown
            function updateDeleteInstructorDropdown(instructors) {
                const select = document.getElementById('deleteInstructorSelect');
                select.innerHTML = '<option value="">-- Select Instructor --</option>';
                
                instructors.forEach(function(instructor) {
                    const option = document.createElement('option');
                    option.value = instructor.instructor_id;
                    option.textContent = instructor.instructor_name + (instructor.subjects ? ' - ' + instructor.subjects : '');
                    select.appendChild(option);
                });
            }

            // Load instructor subjects for management
            document.getElementById('subjectInstructorSelect').addEventListener('change', function() {
                const instructorId = this.value;
                const subjectsList = document.getElementById('instructorSubjectsList');
                const addSubjectContainer = document.getElementById('addSubjectContainer');
                
                if (!instructorId) {
                    subjectsList.style.display = 'none';
                    addSubjectContainer.style.display = 'none';
                    return;
                }
                
                // Fetch subjects for this instructor
                const formData = new FormData();
                formData.append('action', 'get_subjects');
                formData.append('instructor_id', instructorId);
                
                fetch('endpoint/save-instructor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const subjectListContainer = document.getElementById('subjectListContainer');
                    subjectListContainer.innerHTML = ''; // Clear existing items
                    
                    if (data.success && data.data.subjects.length > 0) {
                        // Populate subjects list
                        data.data.subjects.forEach(subject => {
                            const subjectItem = document.createElement('div');
                            subjectItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                            
                            const subjectName = document.createElement('span');
                            subjectName.textContent = subject.subject_name;
                            subjectItem.appendChild(subjectName);
                            
                            // Only add remove button if there's more than one subject
                            if (data.data.subjects.length > 1) {
                                const removeBtn = document.createElement('button');
                                removeBtn.className = 'btn btn-sm btn-danger';
                                removeBtn.innerHTML = '<i class="fas fa-times"></i> Remove';
                                removeBtn.title = 'Click to remove this subject';
                                removeBtn.onclick = function(e) {
                                    e.preventDefault();
                                    removeSubjectFromInstructor(instructorId, subject.subject_id, subject.subject_name);
                                };
                                subjectItem.appendChild(removeBtn);
                                
                                // Add a hover effect to indicate item can be deleted
                                subjectItem.classList.add('subject-removable');
                            } else {
                                const badgeSpan = document.createElement('span');
                                badgeSpan.className = 'badge badge-secondary';
                                badgeSpan.textContent = 'Cannot remove (only subject)';
                                badgeSpan.title = 'An instructor must have at least one subject';
                                subjectItem.appendChild(badgeSpan);
                            }
                            
                            subjectListContainer.appendChild(subjectItem);
                        });
                        
                        // Show the subjects list
                        subjectsList.style.display = 'block';
                        addSubjectContainer.style.display = 'block';
                    } else {
                        showInstructorAlert('No subjects found for this instructor', 'error');
                        subjectsList.style.display = 'none';
                        addSubjectContainer.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading subjects:', error);
                    showInstructorAlert('Error loading subjects', 'error');
                    subjectsList.style.display = 'none';
                    addSubjectContainer.style.display = 'none';
                });
            });
            
            // Add new subject to instructor
            document.getElementById('addSubjectBtn').addEventListener('click', function() {
                const instructorId = document.getElementById('subjectInstructorSelect').value;
                const newSubject = document.getElementById('newSubjectName').value.trim();
                
                if (!instructorId || !newSubject) {
                    showInstructorAlert('Please select an instructor and enter a subject name', 'error');
                    return;
                }
                
                // Add subject to instructor using the edit functionality
                const formData = new FormData();
                formData.append('action', 'edit');
                formData.append('instructor_id', instructorId);
                
                // Get current instructor name
                const instructorName = document.getElementById('subjectInstructorSelect').options[
                    document.getElementById('subjectInstructorSelect').selectedIndex
                ].text;
                
                // Get current subjects and add the new one
                const currentSubjects = [];
                document.querySelectorAll('#subjectListContainer .list-group-item span:first-child').forEach(span => {
                    currentSubjects.push(span.textContent);
                });
                
                // Add new subject if it doesn't already exist
                if (!currentSubjects.includes(newSubject)) {
                    currentSubjects.push(newSubject);
                }
                
                formData.append('name', instructorName);
                formData.append('subjects', currentSubjects.join(','));
                
                fetch('endpoint/save-instructor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear the input field
                        document.getElementById('newSubjectName').value = '';
                        
                        // Show success message
                        showInstructorAlert('Subject added successfully', 'success');
                        
                        // Refresh the subjects list
                        document.getElementById('subjectInstructorSelect').dispatchEvent(new Event('change'));
                        
                        // Refresh instructor lists
                        refreshInstructorLists();
                    } else {
                        showInstructorAlert(data.message || 'Failed to add subject', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error adding subject:', error);
                    showInstructorAlert('An error occurred while adding the subject', 'error');
                });
            });
            
            // Function to remove a subject from an instructor
            function removeSubjectFromInstructor(instructorId, subjectId, subjectName) {
                const instructorSelect = document.getElementById('subjectInstructorSelect');
                const instructorName = instructorSelect.options[instructorSelect.selectedIndex].text;
                
                // Set the modal content
                document.getElementById('modalSubjectName').textContent = subjectName;
                document.getElementById('modalInstructorName').textContent = instructorName;
                
                // Show the modal
                $('#subjectRemovalModal').modal('show');
                
                // Handle confirm button click
                document.getElementById('confirmSubjectRemoval').onclick = function() {
                    // Show loading spinner on button
                    const confirmButton = document.getElementById('confirmSubjectRemoval');
                    confirmButton.classList.add('btn-loading');
                    confirmButton.disabled = true;
                    
                    const formData = new FormData();
                    formData.append('action', 'remove_subject');
                    formData.append('instructor_id', instructorId);
                    formData.append('subject_id', subjectId);
                    
                    // Hide the modal after a brief delay to show the loading state
                    setTimeout(() => {
                        $('#subjectRemovalModal').modal('hide');
                        
                        // Reset button state (will be hidden when modal closes)
                        setTimeout(() => {
                            confirmButton.classList.remove('btn-loading');
                            confirmButton.disabled = false;
                        }, 300);
                        
                        // Show loading indicator in alert area
                        showInstructorAlert('Removing subject...', 'info', false);
                    }, 500);
                    
                    fetch('endpoint/save-instructor.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success alert
                            showInstructorAlert('Subject removed successfully', 'success');
                            
                            // Set success modal content
                            document.getElementById('successModalSubjectName').textContent = subjectName;
                            document.getElementById('successModalInstructorName').textContent = instructorName;
                            
                            // Show success modal
                            setTimeout(() => {
                                $('#subjectRemovalSuccessModal').modal('show');
                            }, 300);
                            
                            // Refresh the subjects list
                            document.getElementById('subjectInstructorSelect').dispatchEvent(new Event('change'));
                            
                            // Refresh instructor lists
                            refreshInstructorLists();
                            
                            // Check if this affects the current instructor/subject selection
                            if (document.getElementById('currentInstructorInfo').style.display !== 'none') {
                                const currentInstructorId = document.getElementById('instructorSelect').value;
                                if (currentInstructorId === instructorId) {
                                    // Refresh the current instructor display
                                    fetch('endpoint/save-instructor.php', {
                                        method: 'POST',
                                        body: new URLSearchParams({ 
                                            'action': 'set',
                                            'instructor_id': instructorId
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(refreshData => {
                                        if (refreshData.success) {
                                            // Update the displayed subject if it changed
                                            document.getElementById('displayedSubject').textContent = 
                                                refreshData.data.subject_name || 'None';
                                        }
                                    })
                                    .catch(console.error);
                                }
                            }
                        } else {
                            showInstructorAlert(data.message || 'Failed to remove subject', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error removing subject:', error);
                        showInstructorAlert('An error occurred while removing the subject', 'error');
                    });
                };
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            // QR Scanner initialization
            var html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader", { fps: 10, qrbox: 250 });
            
            function onScanSuccess(qrCodeMessage) {
                // Stop the scanner
                html5QrcodeScanner.clear();
                
                // Parse the QR code data
                var qrData = qrCodeMessage.split('|');
                if (qrData.length < 3) {
                    showAlert('Invalid QR Code format. Please try again.', 'danger');
                    // Restart scanner after delay
                    setTimeout(function() {
                        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                    }, 2000);
                    return;
                }
                
                // Extract data from QR code
                var course_code = qrData[0];
                var section = qrData[1];
                var instructor_id = qrData[2];
                
                // Log the scanning activity (using the helper function via AJAX)
                $.ajax({
                    url: 'api/log-activity.php',
                    type: 'POST',
                    data: {
                        action_type: 'qr_scan',
                        description: 'Scanned QR code for ' + course_code + ' ' + section,
                        table: 'tbl_attendance',
                        additional_data: JSON.stringify({
                            course_code: course_code,
                            section: section,
                            instructor_id: instructor_id
                        })
                    },
                    success: function(response) {
                        console.log('Activity logged successfully');
                    },
                    error: function() {
                        console.error('Failed to log activity');
                    }
                });
                
                // Show scanning indicator
                $("#scanning-indicator").show();
                
                // Proceed with attendance checking
                checkAttendance(course_code, section, instructor_id);
            }
            
            function onScanFailure(error) {
                // Handle scan failure silently (don't need to show errors to user)
                console.warn(`QR scan error: ${error}`);
            }
            
            // Render the scanner
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            
            // Handle adding subject manually
            $("#addSubjectForm").on("submit", function(e) {
                e.preventDefault();
                
                var course_code = $("#course_code").val();
                var section = $("#section").val();
                var instructor_id = $("#instructor_id").val();
                
                // Log the manual subject addition
                $.ajax({
                    url: 'api/log-activity.php',
                    type: 'POST',
                    data: {
                        action_type: 'manual_entry',
                        description: 'Manually added attendance for ' + course_code + ' ' + section,
                        table: 'tbl_attendance',
                        additional_data: JSON.stringify({
                            course_code: course_code,
                            section: section,
                            instructor_id: instructor_id,
                            method: 'manual'
                        })
                    }
                });
                
                // Show scanning indicator
                $("#scanning-indicator").show();
                
                // Process the attendance
                checkAttendance(course_code, section, instructor_id);
            });
            
            // Function to check attendance
            function checkAttendance(course_code, section, instructor_id) {
                // AJAX call to check attendance
                $.ajax({
                    url: 'api/check-attendance.php',
                    type: 'POST',
                    data: {
                        course_code: course_code,
                        section: section,
                        instructor_id: instructor_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        $("#scanning-indicator").hide();
                        
                        if (response.success) {
                            showAlert('Attendance recorded successfully!', 'success');
                            
                            // If student_id is available, also record in the new attendance_logs table
                            if (response.data && response.data.student_id) {
                                recordAttendanceLog(response.data.student_id);
                            }
                            
                            // Reload the page to show updated attendance
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showAlert(response.message || 'Failed to record attendance. Please try again.', 'danger');
                            // Restart scanner
                            setTimeout(function() {
                                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                            }, 3000);
                        }
                    },
                    error: function() {
                        $("#scanning-indicator").hide();
                        showAlert('Network error. Please try again.', 'danger');
                        // Restart scanner
                        setTimeout(function() {
                            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                        }, 3000);
                    }
                });
            }
            
            // Function to record attendance in the new attendance_logs table
            function recordAttendanceLog(studentId) {
                // Only proceed if we have a student ID
                if (!studentId) return;
                
                // Call the API endpoint to record attendance in the new system
                $.ajax({
                    url: 'api/record-attendance-log.php',
                    type: 'POST',
                    data: {
                        student_id: studentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Attendance log recorded:', response);
                        
                        // If we have grade data, show it in a toast or other UI element
                        if (response.success && response.data && response.data.grade_data) {
                            const grade = response.data.grade_data;
                            console.log('Attendance grade updated:', grade);
                            
                            // You could show a toast notification here if desired
                            // showAttendanceGradeToast(grade);
                        }
                    },
                    error: function(error) {
                        console.error('Error recording attendance log:', error);
                        // No need to show error to user as the primary attendance was recorded
                    }
                });
            }
            
            // Function to show alerts
            function showAlert(message, type) {
                var alertBox = $('<div class="alert alert-' + type + ' alert-dismissible fade show mt-3" role="alert">' +
                    message +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span>' +
                    '</button>' +
                    '</div>');
                
                $("#alertContainer").html(alertBox);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    alertBox.alert('close');
                }, 5000);
            }
        });
    </script>

<?php
// Safely close the connection
if (isset($conn_login) && $conn_login instanceof mysqli) {
    try {
        if ($conn_login->ping()) {
            $conn_login->close();
        }
    } catch (Throwable $e) {
        // Connection is already closed or invalid, do nothing
    }
}
?>
</body>
</html>
