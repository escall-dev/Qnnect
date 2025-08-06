<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/asset_helper.php';
require_once 'includes/session_config.php'; // Handle session configuration
require_once 'includes/schedule_helper.php'; // Has getScheduleDropdownData
require_once 'includes/schedule_data_helper.php'; // Has getFilteredScheduleData

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

// Get user_id from session
$user_id = $_SESSION['user_id'] ?? 1; // Default to user_id 1 if not set

// Auto-fix attendance status for records that might be incorrect
function autoFixAttendanceStatus($conn, $user_id, $school_id) {
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
    
    // Only fix records from today with missing status for current user
    $today = date('Y-m-d');
    $query = "SELECT tbl_attendance_id, time_in, status FROM tbl_attendance WHERE DATE(time_in) = ? AND (status IS NULL OR status = '') AND user_id = ? AND school_id = ?";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $today, $user_id, $school_id);
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
    autoFixAttendanceStatus($conn_qr, $user_id, $school_id);
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
        
        // Auto-assign instructor from logged-in user
        if ($userData) {
            $loggedInUserName = $userData['username'] ?? $userData['email'] ?? 'Unknown User';
            
            // Check if instructor exists in tbl_instructors, if not create one
            $instructor_check_query = "SELECT instructor_id FROM tbl_instructors WHERE instructor_name = ?";
            $instructor_stmt = $conn_qr->prepare($instructor_check_query);
            $instructor_stmt->bind_param("s", $loggedInUserName);
            $instructor_stmt->execute();
            $instructor_result = $instructor_stmt->get_result();
            
            if ($instructor_result && $instructor_result->num_rows > 0) {
                $instructor_data = $instructor_result->fetch_assoc();
                $current_instructor_id = $instructor_data['instructor_id'];
            } else {
                // Create new instructor record for logged-in user
                $create_instructor_query = "INSERT INTO tbl_instructors (instructor_name) VALUES (?)";
                $create_stmt = $conn_qr->prepare($create_instructor_query);
                $create_stmt->bind_param("s", $loggedInUserName);
                if ($create_stmt->execute()) {
                    $current_instructor_id = $conn_qr->insert_id;
                } else {
                    $current_instructor_id = 1; // Fallback
                }
            }
            
            // Set current instructor in session
            $_SESSION['current_instructor_id'] = $current_instructor_id;
            $_SESSION['current_instructor_name'] = $loggedInUserName;
            $_SESSION['auto_assigned_instructor'] = true;
            
            error_log("Auto-assigned instructor: $loggedInUserName (ID: $current_instructor_id)");
        }
        
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
        // Get attendance details before deletion for logging
        $get_attendance_query = "SELECT * FROM tbl_attendance WHERE tbl_attendance_id = ?";
        $get_stmt = $conn_qr->prepare($get_attendance_query);
        $get_stmt->bind_param("i", $attendance);
        $get_stmt->execute();
        $attendance_details = $get_stmt->get_result()->fetch_assoc();

        $query = "DELETE FROM tbl_attendance WHERE tbl_attendance_id = ?";
        $stmt = $conn_qr->prepare($query);
        $stmt->bind_param("i", $attendance);
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
    $school_id,
    $selectedInstructor,
    $selectedSection,
    $selectedSubject
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
        
        /* Attendance wrapper container - similar to student-container in masterlist.php */
        .attendance-wrapper-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            transition: all 0.3s ease;
            width: 95%;
        }
        
        /* Custom scrollbar styling for the attendance list - cross-browser */
        .table-container {
            /* Firefox */
            scrollbar-width: thin;
            scrollbar-color: #098744 #e9f9f0;
        }
        
        /* Chrome, Edge, Safari */
        .table-container::-webkit-scrollbar {
            width: 10px; /* Slightly wider for better visibility */
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #e9f9f0;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background-color: #098744;
            border-radius: 4px;
            border: 2px solid #e9f9f0;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background-color: #076630;
        }
        
        /* Table row hover effect for better UX when scrolling */
        #attendanceTable tbody tr {
            transition: all 0.2s ease;
        }
        
        #attendanceTable tbody tr:hover {
            filter: brightness(110%);
            box-shadow: 0 3px 5px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        
        /* Card scrollbar styling */
        .card.p-3.mb-3::-webkit-scrollbar {
            width: 10px;
        }
        
        .card.p-3.mb-3::-webkit-scrollbar-track {
            background: #e9f9f0;
            border-radius: 4px;
        }
        
        .card.p-3.mb-3::-webkit-scrollbar-thumb {
            background-color: #098744;
            border-radius: 4px;
            border: 2px solid #e9f9f0;
        }
        
        .card.p-3.mb-3::-webkit-scrollbar-thumb:hover {
            background-color: #076630;
        }
        
        /* Table row hover effects */
        #attendanceTable tbody tr {
            transition: all 0.2s ease;
        }
        
        #attendanceTable tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 0 auto 20px auto;
        }
        
        .attendance-section-title {
            color: #098744;
            margin-bottom: 20px;
            font-weight: 600;
            border-bottom: 2px solid #098744;
            padding-bottom: 10px;
        }
        
        .attendance-section-title i {
            margin-right: 10px;
        }

        /* When sidebar is closed */
        .sidebar.close ~ .main {
            margin-left: 78px;
            width: calc(100% - 78px);
        }
        
        /* Adjust wrapper container when sidebar is closed */
        .sidebar.close ~ .main .attendance-wrapper-container {
            width: 95%;
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
            margin: 0 auto;
        }

        /* Scrollable left sidebar */
        .col-md-3 {
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: #098744 #f1f1f1;
        }

        .col-md-3::-webkit-scrollbar {
            width: 8px;
        }

        .col-md-3::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .col-md-3::-webkit-scrollbar-thumb {
            background: #098744;
            border-radius: 4px;
        }

        .col-md-3::-webkit-scrollbar-thumb:hover {
            background: #076a32;
        }

        /* Make class time section more prominent */
        .class-time-setting {
            animation: glow 2s ease-in-out infinite alternate;
            margin-left: 0;
            margin-right: 0;
            width: 100%;
        }

        /* Improved styles for class time settings */
        .class-time-setting .card {
            border-width: 2px;
            margin: 0 0 0 0;
        }
        
        .class-time-setting .card-body {
            padding: 15px;
        }
        
        .class-time-setting .form-control {
            border: 1px solid #ced4da;
            height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
        }
        
        .class-time-setting .btn {
            padding: .375rem .75rem;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        
        .class-time-setting #classStartTime {
            min-width: 150px;
        }

        /* Enhanced class time display */
        .class-time-setting #currentTimeSettings {
            border-left: 4px solid #28a745;
            animation: pulse-border 2s ease-in-out;
        }

        /* Class time info above attendance table */
        .class-time-info {
            border-left: 4px solid #28a745;
            font-weight: 500;
            animation: fade-in 0.5s ease-in-out;
        }
        
        /* Global notification area */
        #globalNotificationArea {
            position: relative;
            z-index: 1000;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            font-weight: 500;
        }
        
        /* Class Time Confirmation Banner */
        #classTimeConfirmationBanner {
            background-color: #28a745;
            color: white;
            border: 2px solid #218838;
            border-radius: 5px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transform: translateY(0);
            transition: all 0.5s ease;
            opacity: 1;
            position: relative;
            overflow: hidden;
        }
        
        #classTimeConfirmationBanner::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 50%, rgba(255,255,255,0.2) 100%);
            pointer-events: none;
        }
        
        #classTimeConfirmationBanner.hide {
            transform: translateY(-20px);
            opacity: 0;
        }
        
        .pulse-icon {
            animation: pulse-icon 2s infinite;
            height: 30px;
            width: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        @keyframes pulse-icon {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }
        
        /* Enhanced alert area */
        #classTimeAlertArea {
            padding: 12px 15px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            font-weight: 500;
        }
        
        /* Animations */
        @keyframes pulse-border {
            0% { border-left-color: #28a745; }
            50% { border-left-color: #155724; }
            100% { border-left-color: #28a745; }
        }
        
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fade-out {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
        
        .time-set-animation {
            animation: highlight-time 1s ease-in-out;
        }
        
        @keyframes highlight-time {
            0% { background-color: #d4edda; }
            50% { background-color: #b1dfbb; }
            100% { background-color: #d4edda; }
        }
        
        /* Button animation */
        #setClassTime:active:not(:disabled) {
            transform: scale(0.95);
        }
        
        #setClassTime {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        #setClassTime::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }
        
        #setClassTime:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            20% {
                transform: scale(25, 25);
                opacity: 0.3;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }
        
        .class-time-setting #currentTimeSettings {
            margin: 0 0 15px 0;
            padding: 10px;
            border-radius: 5px;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        
        .time-set-animation {
            animation: timeSetFlash 1s ease-out;
        }
        
        @keyframes timeSetFlash {
            0% { background-color: #28a745; border-color: #28a745; color: white; }
            100% { background-color: #d1ecf1; border-color: #bee5eb; color: inherit; }
        }
        
        @keyframes glow {
            from {
                box-shadow: 0 0 5px #28a745;
            }
            to {
                box-shadow: 0 0 10px #28a745, 0 0 15px #28a745;
            }
        }
        
        /* Terminate button styling */
        #terminateClassSession {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        #terminateClassSession:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        
        #terminateClassSession:active:not(:disabled) {
            transform: scale(0.95);
        }
        
        #terminateClassSession:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Compact card styling */
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }

        /* Force table visibility */
        .attendance-list {
            background-color: #f8f9fa;
            min-height: 600px;
        }

        .table-container {
            background-color: white;
            min-height: 400px;
            border: 2px solid #098744;
        }

        #attendanceTable {
            width: 100%;
            background-color: white;
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
        
        /* Instascan camera styling to reduce zoom */
        #interactive {
            width: 100%;
            height: 180px; /* Further reduced for better scrolling */
            border: 3px solid #098744;
            border-radius: 10px;
            overflow: hidden;
        }
        
        #interactive video {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Use contain instead of cover to reduce zoom */
            transform: scaleX(-1); /* Mirror the video */
            -webkit-transform: scaleX(-1); /* Safari support */
            -moz-transform: scaleX(-1); /* Firefox support */
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
        
        /* Instructor management buttons styling */
        #advancedInstructorManagement .btn {
            padding: 0.375rem 0.5rem;
            font-size: 0.9rem;
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
        }
        
        #advancedInstructorManagement .card-body {
            padding: 1rem;
            overflow: hidden;
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

        #currentDate, #currentTime {
            display: block;
            text-align: center;
            width: 100%;
            margin: 0 auto;
        }
        .live-time-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        /* Compact Attendance Mode Styling */
        .attendance-mode-section .card-header h6 {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .attendance-mode-section .form-control-sm {
            font-size: 0.875rem;
        }
        
        .attendance-mode-section .alert {
            line-height: 1.3;
        }
        
        /* Responsive adjustments for compact mode */
        @media (max-width: 768px) {
            .attendance-mode-section .card-body {
                padding: 0.75rem !important;
            }
            
            .attendance-mode-section .alert {
                font-size: 0.8rem !important;
                padding: 0.5rem !important;
            }
            
            /* Make instructor management buttons more compact on mobile */
            #advancedInstructorManagement .btn {
                font-size: 0.8rem;
                padding: 0.25rem 0.4rem;
            }
            
            #advancedInstructorManagement .card-body {
                padding: 0.75rem;
            }
        }

        /* Unified Container Layout Styles */
        .unified-attendance-container {
            display: flex;
            width: 100%;
            max-width: 100%;
            margin: 0;
            box-shadow: rgba(0, 0, 0, 0.15) 0px 2px 6px;
            border-radius: 15px;
            overflow: hidden;
            background: #f8f9fa;
            min-height: 600px;
            border: 1px solid rgba(9, 135, 68, 0.2);
        }

        .qr-section {
            flex: 0 0 400px;
            min-width: 400px;
            max-width: 400px;
            max-height: 90vh;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #098744 #f1f1f1;
            display: flex;
            flex-direction: column;
        }

        .qr-section::-webkit-scrollbar {
            width: 8px;
        }

        .qr-section::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .qr-section::-webkit-scrollbar-thumb {
            background: #098744;
            border-radius: 4px;
        }

        .qr-section::-webkit-scrollbar-thumb:hover {
            background: #076a32;
        }

        .attendance-section {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Update QR container styling */
        .qr-container {
            border-right: 3px solid #098744 !important;
            border-radius: 15px 0 0 15px !important;
            margin: 0 !important;
            height: 100% !important;
            box-shadow: none !important;
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
        }

        /* Update attendance list styling */
        .attendance-list {
            border-radius: 0 15px 15px 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }

        /* Responsive behavior */
        @media (max-width: 1200px) {
            .attendance-wrapper-container {
                width: 98%;
                padding: 20px;
            }
            
            .qr-section {
                flex: 0 0 350px;
                min-width: 350px;
                max-width: 350px;
            }
        }

        @media (max-width: 992px) {
            .qr-section {
                flex: 0 0 320px;
                min-width: 320px;
                max-width: 320px;
            }
        }

        @media (max-width: 768px) {
            .attendance-wrapper-container {
                width: 100%;
                padding: 15px;
                border-radius: 15px;
            }
            
            .attendance-section-title {
                font-size: 1.25rem;
                margin-bottom: 15px;
            }
            
            .unified-attendance-container {
                flex-direction: column;
                min-height: auto;
            }
            
            .qr-section {
                flex: none;
                max-height: none;
                min-width: 100%;
                max-width: 100%;
            }
            
            .qr-container {
                border-right: none !important;
                border-bottom: 3px solid #098744 !important;
                border-radius: 15px 15px 0 0 !important;
            }
            
            .attendance-list {
                border-radius: 0 0 15px 15px !important;
            }
        }

        @media (max-width: 992px) {
            .qr-section {
                flex: 0 0 300px;
            }
        }

        /* Ensure proper layout in main container */
        .main .container-fluid {
            max-width: none;
            padding: 20px 15px;
        }

        /* Fix any content overflow in QR container */
        .qr-container > * {
            max-width: 100%;
            box-sizing: border-box;
        }

        /* Ensure table container doesn't overflow */
        .table-container {
            overflow-x: auto;
            max-width: 100%;
        }

        /* Additional fixes for proper spacing */
        .main {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <!-- Custom Alert Box -->
    <div id="customAlert" class="custom-alert"></div>

    <?php include('./components/sidebar-nav.php'); ?>

    

    <div class="main" id="main">
        <div class="container-fluid">
            <!-- Outer wrapper container for QR and Attendance -->
            <div class="attendance-wrapper-container">
               
                <!-- Unified Container for QR and Attendance -->
                <div class="unified-attendance-container">
                    <div class="qr-section">
                        <div class="qr-container" style="background-color: rgba(255, 255, 255, 0.95); border-radius: 15px 0 0 15px; padding: 15px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px; height: 100%; border-right: 3px solid #098744;">
                <!-- Attendance Mode Selection - Compact Version -->
                <div class="attendance-mode-section mb-3">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white py-2">
                            <h6 class="mb-0"><i class="fas fa-toggle-on"></i> Attendance Mode</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="form-group mb-2">
                                <select class="form-control form-control-sm" id="attendanceMode" name="attendanceMode">
                                    <option value="general">📋 General Attendance</option>
                                    <option value="room_subject">📘 Room and Subject-based</option>
                                </select>
                            </div>
                            <div class="alert alert-info mb-0 py-2" id="modeDescription" style="font-size: 0.85rem;">
                                <strong>General Mode:</strong> Manual attendance tracking without schedule integration.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="scanner-con mb-3">
                    <h6 class="text-center mb-2">Scan QR Code for Attendance</h6>
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
                
                <!-- Class Time Setting Form -->
                <div class="class-time-setting mt-3">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white py-2">
                            <h6 class="mb-0"><i class="fas fa-clock"></i> Class Time Settings</h6>
                        </div>
                        <div class="card-body p-3">
                            <div id="currentTimeSettings" class="alert alert-info py-2 mb-3" style="<?= isset($_SESSION['class_start_time']) ? '' : 'display:none;' ?>">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                    <div>
                                        <strong class="d-block">Current Class Time:</strong>
                                        <span id="displayedStartTime" class="font-weight-bold">
                                        <?php 
                                        if(isset($_SESSION['class_start_time'])) {
                                            $time = DateTime::createFromFormat('H:i', $_SESSION['class_start_time']);
                                            echo $time ? $time->format('h:i A') : $_SESSION['class_start_time'];
                                        } else {
                                            echo '08:00 AM';
                                        }
                                        ?>
                                        </span>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-info-circle"></i> Attendance after this time will be marked as "Late"
                                        </small>
                                        <small class="text-success d-block mt-1 font-weight-bold">
                                            <i class="fas fa-check"></i> Time is active and being used for attendance
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <form id="classTimeForm">
                                <div class="form-group mb-3">
                                    <label for="classStartTime" class="form-label">Start Time:</label>
                                    <div class="input-group">
                                        <input type="time" class="form-control" id="classStartTime" name="classStartTime" value="<?= $active_class_time ?: '08:00' ?>" required>
                                        <div class="input-group-append">
                                            <button type="button" id="setClassTime" class="btn btn-success">
                                                <i class="fas fa-save mr-1"></i> Set
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Works in all attendance modes
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label for="classDuration" class="form-label">Duration (minutes):</label>
                                    <input type="number" class="form-control" id="classDuration" name="classDuration" value="60" min="15" max="240" step="15">
                                </div>
                            </form>
                            
                            <?php
                            // Check for active class time from database or session
                            $active_class_time = null;
                            $class_time_source = '';
                            
                            // First try to get from database
                            if (isset($_SESSION['school_id'])) {
                                try {
                                    require_once('conn/db_connect.php');
                                    // Add cache busting for debugging
                                    error_log("Loading class time for school_id: " . $_SESSION['school_id']);
                                    if (isset($conn_qr)) {
                                        $query = "SELECT start_time, updated_at FROM class_time_settings WHERE school_id = ? ORDER BY updated_at DESC LIMIT 1";
                                        $stmt = $conn_qr->prepare($query);
                                        if ($stmt) {
                                            $stmt->bind_param("i", $_SESSION['school_id']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            if ($row = $result->fetch_assoc()) {
                                                $active_class_time = $row['start_time'];
                                                $class_time_source = 'database (saved at ' . date('h:i A', strtotime($row['updated_at'])) . ')';
                                                
                                                // FIX: Set session variables when loading from database
                                                $_SESSION['class_start_time'] = $row['start_time'];
                                                $_SESSION['class_start_time_formatted'] = date('h:i A', strtotime($row['start_time']));
                                                
                                                error_log("Loaded class time from database: " . $active_class_time);
                                                error_log("Set session class_start_time: " . $_SESSION['class_start_time']);
                                                error_log("Set session class_start_time_formatted: " . $_SESSION['class_start_time_formatted']);
                                                
                                                // Also load teacher data if available
                                                $teacher_query = "SELECT teacher_username, subject, section FROM teacher_schedules WHERE school_id = ? AND status = 'active' ORDER BY updated_at DESC LIMIT 1";
                                                $teacher_stmt = $conn_qr->prepare($teacher_query);
                                                if ($teacher_stmt) {
                                                    $teacher_stmt->bind_param("i", $_SESSION['school_id']);
                                                    $teacher_stmt->execute();
                                                    $teacher_result = $teacher_stmt->get_result();
                                                    if ($teacher_row = $teacher_result->fetch_assoc()) {
                                                        $_SESSION['current_instructor_id'] = $teacher_row['teacher_username'];
                                                        $_SESSION['current_subject'] = $teacher_row['subject'];
                                                        $_SESSION['current_section'] = $teacher_row['section'];
                                                    }
                                                    $teacher_stmt->close();
                                                }
                                            }
                                            $stmt->close();
                                        }
                                    }
                                } catch (Exception $e) {
                                    // If database fails, fallback to session
                                    error_log("Error reading class time from database: " . $e->getMessage());
                                }
                            }
                            
                            // Fallback to session if no database value
                            if (!$active_class_time && isset($_SESSION['class_start_time'])) {
                                $active_class_time = $_SESSION['class_start_time'];
                                $class_time_source = 'session';
                            }
                            ?>
                            
                            <!-- Class Time Status Display -->
                            <div id="classTimeStatus" class="mt-3">
                                <?php if ($active_class_time): ?>
                                    <div class="alert alert-success">
                                        <h6><i class="fas fa-clock"></i> Active Class Session</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Start Time:</strong> <?= date('h:i A', strtotime($active_class_time)) ?>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Status:</strong> <span class="badge badge-success">Active</span>
                                            </div>
                                        </div>
                                        <?php if (isset($_SESSION['current_instructor_name'])): ?>
                                            <div class="row mt-2">
                                                <div class="col-md-6">
                                                    <strong>Instructor:</strong> <?= htmlspecialchars($_SESSION['current_instructor_name']) ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Subject:</strong> <?= htmlspecialchars($_SESSION['current_subject_name'] ?? 'Not set') ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <div class="bg-light p-2 rounded">
                                                    <small>
                                                        <strong>Current Time:</strong> 
                                                        <span id="currentSessionTime" class="font-weight-bold text-primary"></span>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-1">
                                            <div class="col-12">
                                                <small class="text-muted">
                                                    <i class="fas fa-database"></i> Source: <?= $class_time_source ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <button type="button" id="terminateClassSession" class="btn btn-danger btn-sm" onclick="if(typeof terminateClassSession === 'function') { terminateClassSession(); } else { console.error('terminateClassSession function not found'); alert('Function not available'); }">
                                                    <i class="fas fa-stop-circle"></i> Terminate Session
                                                </button>
                                                
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                 
                                <?php endif; ?>
                            </div>
                            
                            <!-- Class Time Alert Message Area -->
                            <div id="classTimeAlertArea" style="display: none;" class="mt-3 alert"></div>
                            <!-- Attendance Session Info Area -->
                            <div id="sessionInfoArea" style="display: none;" class="mt-3 alert alert-success">
                                <strong>Session Created</strong>
                                <div id="sessionDetails"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Schedule Selection Section - Only visible in Room & Subject mode -->
                <div class="schedule-section mt-4" id="scheduleSectionContainer" style="display: none;">
                    <h5 class="text-center">📘 Schedule-Based Attendance Settings</h5>
                    <div class="card">
                        <div class="card-body">
                            <!-- Auto-assigned Instructor Display -->
                            <div class="alert alert-success mb-3">
                                <strong><i class="fas fa-user-check"></i> Auto-Assigned Instructor:</strong> 
                                <span id="autoInstructorName"><?= $_SESSION['current_instructor_name'] ?? 'Loading...' ?></span>
                            </div>
                            
                            <form id="scheduleFilterForm">
                                <div class="form-group">
                                    <label for="scheduleSubjectSelect">Subject:</label>
                                    <select class="form-control" id="scheduleSubjectSelect" name="subject">
                                        <option value="">Loading subjects...</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="scheduleSectionSelect">Section:</label>
                                    <select class="form-control" id="scheduleSectionSelect" name="section">
                                        <option value="">Loading sections...</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary btn-block" id="loadScheduleBtn" disabled>
                                    <i class="fas fa-calendar-alt"></i> Load Schedule
                                </button>
                            </form>
                            
                            <!-- Schedule Info Display -->
                            <div id="scheduleInfo" class="mt-3 p-3 bg-success text-white rounded" style="display: none;">
                                <h6 class="mb-2"><i class="fas fa-calendar-check"></i> Active Schedule:</h6>
                                <p class="mb-1"><strong>Instructor:</strong> <span id="scheduleInstructor"></span></p>
                                <p class="mb-1"><strong>Subject:</strong> <span id="scheduleSubject"></span></p>
                                <p class="mb-1"><strong>Section:</strong> <span id="scheduleSection"></span></p>
                                <p class="mb-1"><strong>Room:</strong> <span id="scheduleRoom"></span></p>
                                <p class="mb-0"><strong>Time:</strong> <span id="scheduleTime"></span></p>
                                <div class="mt-2">
                                    <small><i class="fas fa-info-circle"></i> Class time automatically set for punctuality tracking</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instructor Management Section - Only visible in General mode -->
                <div class="instructor-section mt-4" id="instructorSection">
                    <h5 class="text-center">📋 Instructor Information</h5>
                    
                    <!-- Auto-assigned Instructor Display -->
                    <div class="alert alert-success">
                        <h6><i class="fas fa-user-check"></i> Auto-Assigned Instructor</h6>
                        <div><strong>Current Instructor:</strong> <span id="displayedInstructorName"><?= $_SESSION['current_instructor_name'] ?? 'Loading...' ?></span></div>
                        <div><strong>Current Subject:</strong> <span id="displayedSubject"><?= $_SESSION['current_subject_name'] ?? 'Not set' ?></span></div>
                        <small class="text-muted"><i class="fas fa-info-circle"></i> Instructor automatically assigned from your login account</small>
                    </div>
                    
                    <!-- Manual Subject Selection for General Mode -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-book"></i> Subject Selection</h6>
                        </div>
                        <div class="card-body">
                            <form id="setSubjectForm" class="mb-3">
                                <div class="form-group">
                                    <label for="generalSubjectSelect">Select Subject for General Attendance:</label>
                                    <select class="form-control" id="generalSubjectSelect">
                                        <option value="">-- Select Subject --</option>
                                        <?php
                                        // Get current teacher's username and user_id
                                        $current_teacher_username = $_SESSION['userData']['username'] ?? $_SESSION['email'] ?? '';
                                        $current_user_id = $_SESSION['user_id'] ?? null;
                                        
                                        // Fetch subjects from teacher_schedules for this teacher
                                        $teacher_subjects = getTeacherSubjects($current_teacher_username, $school_id, $current_user_id);
                                        
                                        if (!empty($teacher_subjects)) {
                                            foreach ($teacher_subjects as $subject) {
                                                $selected = (isset($_SESSION['current_subject']) && $_SESSION['current_subject'] == $subject) ? 'selected' : '';
                                                echo "<option value=\"" . htmlspecialchars($subject) . "\" $selected>" . htmlspecialchars($subject) . "</option>";
                                            }
                                        } else {
                                            // Fallback: get all subjects if no teacher-specific subjects found
                                            $all_subjects = getAllSubjects($school_id);
                                            foreach ($all_subjects as $subject) {
                                                $selected = (isset($_SESSION['current_subject']) && $_SESSION['current_subject'] == $subject) ? 'selected' : '';
                                                echo "<option value=\"" . htmlspecialchars($subject) . "\" $selected>" . htmlspecialchars($subject) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="button" id="setCurrentSubject" class="btn btn-primary btn-block">Set Current Subject</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Advanced Instructor Management (Collapsed by default) -->
                    <div class="card mt-3">
                        <div class="card-header" data-toggle="collapse" data-target="#advancedInstructorManagement" aria-expanded="false">
                            <h6 class="mb-0">
                                <i class="fas fa-cog"></i> Advanced Instructor Management
                                <small class="text-muted">(Click to expand)</small>
                            </h6>
                        </div>
                        <div class="collapse" id="advancedInstructorManagement">
                            <div class="card-body">
                                <!-- Toggle Buttons for Add/Edit/Delete - 2x2 grid layout -->
                                <div class="row mb-3">
                                    <div class="col-6 mb-2">
                                        <button id="showAddInstructor" class="btn btn-success btn-block">Add Instructor</button>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <button id="showEditInstructor" class="btn btn-warning btn-block">Edit</button>
                                    </div>
                                    <div class="col-6">
                                        <button id="showManageSubjects" class="btn btn-info btn-block">Manage Subjects</button>
                                    </div>
                                    <div class="col-6">
                                        <button id="showDeleteInstructor" class="btn btn-danger btn-block">Delete Instructor</button>
                                    </div>
                                </div>
                                
                                <!-- Management Forms Container -->
                                <div id="instructorManagementForms">
                                    <!-- Forms will be dynamically loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Instructor Message Alert Area -->
                    <div id="instructorAlertArea" style="display: none;" class="mt-2 alert"></div>
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

                <div class="attendance-section">
                    <div class="attendance-list" style="min-height: 600px; background-color: rgba(255, 255, 255, 0.95); border-radius: 0 15px 15px 0; padding: 20px; box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
                    <div class="card p-3 mb-3" style="border: none; background: transparent; max-height: 80vh; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #098744 #e9f9f0;">
                    <!-- Live Clock Display -->
                    <div class="live-time-container text-center mb-4" style="background-color: #f8f9fa; padding: 12px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 5px;">
                        <div class="d-flex justify-content-center align-items-center">
                            <div>
                                <span id="currentDate" style="font-weight: bold; margin-right: 15px; font-size: 1.1rem; display: block; text-align: center; width: 100%; margin: 0 auto;"></span>
                                <span id="currentTime" style="font-size: 1.6rem; font-weight: bold; color: #098744; display: block; text-align: center; width: 100%; margin: 0 auto;"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Global notification area -->
                    <div id="globalNotificationArea" style="display: none;" class="alert alert-success mb-2"></div>
                    
                    <!-- Class Time Confirmation Banner -->
                    <div id="classTimeConfirmationBanner" style="display: none;" class="alert alert-success mb-2 text-center py-3">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="pulse-icon mr-2">
                                <i class="fas fa-check-circle fa-lg"></i>
                            </div>
                            <div>
                                <strong style="font-size: 1.1rem;">Class Time Set Successfully!</strong>
                                <div id="bannerClassTime" style="font-size: 1.2rem; font-weight: 500;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance header with info section -->
                    <div class="attendance-header-info mb-2">
                        <!-- This is where the class time info will be displayed -->
                        <?php if(isset($_SESSION['class_start_time'])): ?>
                        <div class="class-time-info alert alert-success py-2 mb-2">
                            <i class="fas fa-check-circle"></i> <strong>Class Time Set:</strong> 
                            <?php 
                                $time = DateTime::createFromFormat('H:i', $_SESSION['class_start_time']);
                                echo $time ? $time->format('h:i A') : $_SESSION['class_start_time'];
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="text-center mb-3">List of Present Students</h4>
                    
                    <!-- Table container with height optimized for 8 records -->
                    <div class="table-container table-responsive" style="flex: 1; overflow-y: auto; height: auto; max-height: 440px; border: 2px solid #098744; background-color: white; display: block; overflow-x: hidden;">
                        <table class="table text-center table-sm table-bordered" id="attendanceTable" style="border-collapse: separate; border-spacing: 0; width: 100%; margin: 0;">
                            <thead style="background-color: #098744; color: white; position: sticky; top: 0; z-index: 10; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                <tr>
                                    <th scope="col" style="width: 5%; padding: 12px 8px; position: sticky; top: 0; background-color: #098744;">#</th>
                                    <th scope="col" style="width: 20%; padding: 12px 8px; position: sticky; top: 0; background-color: #098744;">Name</th>
                                    <th scope="col" style="width: 15%; padding: 12px 8px; position: sticky; top: 0; background-color: #098744;">Course & Section</th>
                                    <th scope="col" style="width: 15%; padding: 12px 8px; min-width: 120px; position: sticky; top: 0; background-color: #098744;">Date</th>
                                    <th scope="col" style="width: 15%; padding: 12px 8px; min-width: 100px; position: sticky; top: 0; background-color: #098744;">Time In</th>
                                    <th scope="col" style="width: 15%; padding: 12px 8px; min-width: 100px; position: sticky; top: 0; background-color: #098744;">Status</th>
                                    <th scope="col" style="width: 10%; padding: 12px 8px; position: sticky; top: 0; background-color: #098744;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                date_default_timezone_set('Asia/Manila');

                                try {
                                    // Pagination logic
                                    $limit = 8; // Changed from 9 to 8 records per page
                                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                    $offset = ($page - 1) * $limit;

                                    $query = "
                                        SELECT a.*, s.student_name, s.course_section 
                                        FROM tbl_attendance a
                                        LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
                                        WHERE a.time_in IS NOT NULL AND a.user_id = ? AND a.school_id = ?
                                        ORDER BY a.time_in DESC 
                                        LIMIT ?, ?
                                    ";
                                    
                                    $stmt = $conn_qr->prepare($query);
                                    $stmt->bind_param("iiii", $user_id, $school_id, $offset, $limit);
                                    $stmt->execute();
                                    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                                    // Get total records for pagination
                                    $totalQuery = "SELECT COUNT(*) as total FROM tbl_attendance WHERE user_id = ? AND school_id = ?";
                                    $totalStmt = $conn_qr->prepare($totalQuery);
                                    $totalStmt->bind_param("ii", $user_id, $school_id);
                                    $totalStmt->execute();
                                    $totalResult = $totalStmt->get_result();
                                    $totalRow = $totalResult->fetch_assoc();
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
                                        // Enhanced status display with colored badges
                                        $statusBadge = ($status == 'On Time') 
                                            ? "<span class='badge badge-success'><i class='fas fa-check-circle'></i> On Time</span>" 
                                            : "<span class='badge badge-warning'><i class='fas fa-clock'></i> Late</span>";
                                        
                                        // Display the most recent attendance record
                                        echo "<tr style='background-color: #098744; color: white;'>
                                                <th scope='row'>{$recentAttendance['tbl_attendance_id']}</th>
                                                <td>{$recentAttendance['student_name']}</td>
                                                <td>{$recentAttendance['course_section']}</td>
                                                <td style='white-space: nowrap;'>" . date('M d, Y', strtotime($recentAttendance["time_in"])) . "</td>
                                                <td style='white-space: nowrap;'>" . date('h:i:s A', strtotime($recentAttendance["time_in"])) . "</td>
                                                <td style='white-space: nowrap;'>{$statusBadge}</td>
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
                                            
                                            // Use the status stored in the database with enhanced badge display
                                            $row_status = isset($row["status"]) && !empty($row["status"]) ? 
                                                      $row["status"] : 'Unknown';
                                            $row_statusBadge = ($row_status == 'On Time') 
                                                ? "<span class='badge badge-success'><i class='fas fa-check-circle'></i> On Time</span>" 
                                                : ($row_status == 'Late' 
                                                    ? "<span class='badge badge-warning'><i class='fas fa-clock'></i> Late</span>"
                                                    : "<span class='badge badge-secondary'><i class='fas fa-question-circle'></i> Unknown</span>");
                                    ?>
                                <tr style="background-color: #098744; color: white;">
                                    <th scope="row"><?= $attendanceID ?></th>
                                    <td><?= $studentName ?></td>
                                    <td><?= $studentCourse ?></td>
                                    <td style="white-space: nowrap;"><?= $attendanceDate ?></td>
                                    <td style="white-space: nowrap;"><?= $timeIn ?></td>
                                    <td style="white-space: nowrap;"><?= $row_statusBadge ?></td>
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
                    
                    <!-- Export Buttons Section -->
                    <div class="text-center my-3" style="position: relative; z-index: 100;">
                        <!-- Print Button -->
                        <button onclick="printAttendance()" class="btn btn-success">
                            <i class="fas fa-print"></i> Print Attendance
                        </button>
                    </div>
                    
                    <!-- Export Buttons Row -->
                    <div class="d-flex justify-content-center mt-2">
                        <form method="GET" action="export_attendance.php" style="margin: 0 3px;">
                            <input type="hidden" name="format" value="csv">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                        </form>
                        <form method="GET" action="export_attendance.php" style="margin: 0 3px;">
                            <input type="hidden" name="format" value="excel">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                        </form>
                        <form method="GET" action="export_attendance.php" style="margin: 0 3px;">
                            <input type="hidden" name="format" value="pdf">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </form>
                    </div>
                    
                    <!-- Pagination Container -->
                    <div class="attendance-pagination">
                        <div id="paginationContainer" class="pagination-container">
                            <!-- Manual pagination as fallback -->
                            <?php if ($totalPages > 1): ?>
                            <div class="pagination-container">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=1">First</a>
                                        </li>
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                        </li>
                                        
                                        <?php
                                        $maxVisiblePages = 3;
                                        $startPage = max(1, min($page - floor($maxVisiblePages / 2), $totalPages - $maxVisiblePages + 1));
                                        $endPage = min($startPage + $maxVisiblePages - 1, $totalPages);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                        </li>
                                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $totalPages; ?>">Last</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="page-records-info">
                            Showing 8 records per page (Total: <?php echo $totalRecords; ?>, Pages: <?php echo $totalPages; ?>, Current: <?php echo $page; ?>)
                        </div>
                    </div>
                </div>
                </div>
                </div>
                </div>
            </div>
            </div>
        </div>
    </div>   <!-- HTML for overlay and popups -->
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo asset_url('js/popper.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.min.js'); ?>"></script>

    <!-- instascan Js -->
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <script src="./functions/pagination.js"></script>
    <script src="./js/modal-helpers.js"></script>

    <!--sidebar-toggle-->
    <script>
        $(document).ready(function() {
            // Debug pagination values
            console.log('Pagination values:', {
                currentPage: <?php echo $page; ?>,
                totalPages: <?php echo $totalPages; ?>
            });
            
            // We're now using server-side PHP pagination instead of JavaScript
            // so no need to call createStandardPagination here
            
            // Auto-load instructor schedules when page loads
            loadInstructorSchedules();
            
            // Enable automatic time setting regardless of mode
            enableAutomaticTimeSetup();
        });
        
        // Setup AJAX pagination handlers
        function setupPaginationHandlers() {
            $(document).on('click', '#paginationContainer .page-link', function(e) {
                e.preventDefault();
                const href = $(this).attr('href');
                const urlParams = new URLSearchParams(href.substring(href.indexOf('?')));
                const page = urlParams.get('page') || 1;
                
                // Load attendance data for the selected page
                loadAttendanceData(page);
                
                // Update URL without refreshing page
                history.pushState(null, null, '?page=' + page);
                
                return false;
            });
        }
        
        // Function to load attendance data via AJAX
        function loadAttendanceData(page = 1) {
            fetch('./api/get-attendance-list.php?page=' + page)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error loading attendance data:', data.error);
                        return;
                    }
                    
                    // Update attendance table
                    updateAttendanceTable(data.records);
                    
                    // Update pagination
                    createStandardPagination(
                        data.pagination.currentPage,
                        data.pagination.totalPages,
                        '?',
                        'paginationContainer'
                    );
                })
                .catch(error => {
                    console.error('Error fetching attendance data:', error);
                });
        }
        
        // Function to update the attendance table with new data
        function updateAttendanceTable(records) {
            const tableBody = document.querySelector('#attendanceTable tbody');
            
            // Clear current table rows
            tableBody.innerHTML = '';
            
            if (records.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No records found.</td></tr>';
                return;
            }
            
                // Add each record to the table
            records.forEach((record, index) => {
                const row = document.createElement('tr');
                
                // Add standard class and highlight the most recent attendance
                row.classList.add('attendance-table-row');
                if (index === 0) {
                    row.classList.add('recent-attendance');
                }                // Format status class
                let statusClass = '';
                let statusText = record.status || 'Unknown';
                
                if (statusText.toLowerCase() === 'on time') {
                    statusClass = 'bg-success text-white';
                } else if (statusText.toLowerCase() === 'late') {
                    statusClass = 'bg-warning text-dark';
                } else if (statusText.toLowerCase() === 'absent') {
                    statusClass = 'bg-danger text-white';
                }
                
                // Format date and time
                const timeIn = record.time_in ? new Date(record.time_in) : null;
                const formattedDate = timeIn ? timeIn.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                }) : 'N/A';
                
                const formattedTime = timeIn ? timeIn.toLocaleTimeString('en-US', {
                    hour: '2-digit', 
                    minute: '2-digit'
                }) : 'N/A';
                
                // Create row content
                row.innerHTML = `
                    <td>${record.student_name || 'Unknown'}</td>
                    <td>${record.course_section || 'N/A'}</td>
                    <td>${formattedDate}</td>
                    <td>${formattedTime}</td>
                    <td><span class="badge ${statusClass}">${statusText}</span></td>
                    <td>${record.subject || 'N/A'}</td>
                    <td><a href="#" class="attendance-details" data-id="${record.tbl_attendance_id}">Details</a></td>
                `;
                
                tableBody.appendChild(row);
            });
        }
        
        // Function to load instructor schedules automatically
        // Function to load instructor schedules automatically
        async function loadInstructorSchedules() {
            try {
                const response = await fetch('./api/get-schedule-data.php?action=get_dropdown_data');
                const data = await response.json();
                
                if (data.success) {
                    // Update instructor name display (use current logged-in user)
                    const currentUser = '<?= $_SESSION['userData']['username'] ?? $_SESSION['email'] ?? 'Current User' ?>';
                    document.getElementById('autoInstructorName').textContent = currentUser;
                    
                    // Populate subject dropdown with subjects from teacher_schedules
                    const subjectSelect = document.getElementById('scheduleSubjectSelect');
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                    if (data.data.subjects && data.data.subjects.length > 0) {
                        data.data.subjects.forEach(subject => {
                            subjectSelect.innerHTML += `<option value="${subject}">${subject}</option>`;
                        });
                    } else {
                        subjectSelect.innerHTML += '<option value="" disabled>No subjects available</option>';
                    }
                    
                    // Start with empty section dropdown - will be populated when subject is selected
                    const sectionSelect = document.getElementById('scheduleSectionSelect');
                    sectionSelect.innerHTML = '<option value="">Select Subject First</option>';
                    
                    // Enable load schedule button when both subject and section are selected
                    const loadBtn = document.getElementById('loadScheduleBtn');
                    function checkEnableLoadBtn() {
                        loadBtn.disabled = !(subjectSelect.value && sectionSelect.value);
                    }
                    
                    // Add event listeners for interdependent dropdowns
                    subjectSelect.addEventListener('change', function() {
                        const selectedSubject = this.value;
                        
                        if (selectedSubject) {
                            // Always update sections when subject is selected
                            updateSectionsBySubject(selectedSubject);
                        } else {
                            // Clear section dropdown if no subject selected
                            sectionSelect.innerHTML = '<option value="">Select Subject First</option>';
                        }
                        checkEnableLoadBtn();
                    });
                    
                    sectionSelect.addEventListener('change', function() {
                        const selectedSection = this.value;
                        
                        if (selectedSection) {
                            // When section is selected, update sections based on current subject
                            // But don't filter the subject dropdown - keep all subjects available
                            const currentSubject = subjectSelect.value;
                            if (currentSubject) {
                                updateSectionsBySubject(currentSubject);
                            }
                        } else {
                            // Clear section dropdown if no section selected
                            sectionSelect.innerHTML = '<option value="">Select Subject First</option>';
                        }
                        checkEnableLoadBtn();
                    });
                    
                    // Add click event to subject dropdown to ensure it shows all subjects
                    subjectSelect.addEventListener('click', function() {
                        // Reload all subjects when dropdown is clicked
                        loadAllSubjects();
                    });
                    
                    console.log('Schedule data loaded successfully', data);
                } else {
                    console.error('Failed to load schedule data:', data.message);
                    showInstructorAlert('Failed to load schedules: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error in loadInstructorSchedules:', error);
                showInstructorAlert('Error loading schedule data', 'error');
            }
        }
        
        // Function to update sections based on selected subject
        async function updateSectionsBySubject(subject) {
            try {
                const response = await fetch(`api/get-sections-by-subject.php?subject=${encodeURIComponent(subject)}`);
                const data = await response.json();
                
                if (data.success) {
                    const sectionSelect = document.getElementById('scheduleSectionSelect');
                    const currentSection = sectionSelect.value; // Preserve current selection
                    
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    
                    if (data.data && data.data.length > 0) {
                        data.data.forEach(section => {
                            const selected = (section === currentSection) ? 'selected' : '';
                            sectionSelect.innerHTML += `<option value="${section}" ${selected}>${section}</option>`;
                        });
                    } else {
                        sectionSelect.innerHTML += '<option value="" disabled>No sections available for this subject</option>';
                    }
                } else {
                    console.error('Failed to load sections for subject:', data.message);
                }
            } catch (error) {
                console.error('Error updating sections:', error);
            }
        }
        
        // Function to load all available subjects
        async function loadAllSubjects() {
            try {
                const response = await fetch('./api/get-schedule-data.php?action=get_dropdown_data');
                const data = await response.json();
                
                if (data.success) {
                    const subjectSelect = document.getElementById('scheduleSubjectSelect');
                    const currentSubject = subjectSelect.value; // Preserve current selection
                    
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                    if (data.data.subjects && data.data.subjects.length > 0) {
                        data.data.subjects.forEach(subject => {
                            const selected = (subject === currentSubject) ? 'selected' : '';
                            subjectSelect.innerHTML += `<option value="${subject}" ${selected}>${subject}</option>`;
                        });
                    } else {
                        subjectSelect.innerHTML += '<option value="" disabled>No subjects available</option>';
                    }
                } else {
                    console.error('Failed to load all subjects:', data.message);
                }
            } catch (error) {
                console.error('Error loading all subjects:', error);
            }
        }
        
        // Function to update subjects based on selected section (DEPRECATED - not used)
        // We keep all subjects available, so this function is no longer needed
        async function updateSubjectsBySection(section) {
            // This function is deprecated - subjects should always show all available options
            console.log('updateSubjectsBySection called but deprecated - subjects should show all options');
        }
        
        // Function to smoothly scroll to class time settings
        function scrollToClassTime() {
            const classTimeSection = document.querySelector('.class-time-setting');
            if (classTimeSection) {
                classTimeSection.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                // Add a temporary highlight effect
                classTimeSection.style.animation = 'glow 1s ease-in-out 3';
                setTimeout(() => {
                    classTimeSection.style.animation = '';
                }, 3000);
            }
        }
        
        // Function to enable automatic time setup
        function enableAutomaticTimeSetup() {
            // Allow time setting regardless of instructor selection or mode
            const setTimeBtn = document.getElementById('setClassTime');
            const timeInput = document.getElementById('classStartTime');
            const durationInput = document.getElementById('classDuration');
            
            if (setTimeBtn) {
                setTimeBtn.disabled = false;
                
                // Remove any existing event listeners to avoid duplicates
                setTimeBtn.replaceWith(setTimeBtn.cloneNode(true));
                
                // Get the fresh reference after cloning
                const freshSetTimeBtn = document.getElementById('setClassTime');
                
                // Add event listener to the fresh button
                freshSetTimeBtn.addEventListener('click', function() {
                    const timeInput = document.getElementById('classStartTime');
                    const durationInput = document.getElementById('classDuration');
                    
                    if (timeInput.value) {
                        // Show loading state
                        const originalButtonText = freshSetTimeBtn.innerHTML;
                        freshSetTimeBtn.disabled = true;
                        freshSetTimeBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Setting...';
                        
                        // Save the time to session via AJAX
                        fetch('api/set-class-time.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'classStartTime=' + encodeURIComponent(timeInput.value)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update UI with time information
                                setClassTime(timeInput.value, durationInput.value || 60);
                                
                                // Show success message
                                showClassTimeAlert('Class time set successfully to ' + convertTo12Hour(timeInput.value), 'success');
                            } else {
                                showClassTimeAlert(data.message || 'Failed to set class time', 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error setting class time:', error);
                            showClassTimeAlert('Network error. Please try again.', 'danger');
                        })
                        .finally(() => {
                            // Reset button state
                            freshSetTimeBtn.disabled = false;
                            freshSetTimeBtn.innerHTML = originalButtonText;
                        });
                    } else {
                        // Validate input
                        timeInput.classList.add('is-invalid');
                        showClassTimeAlert('Please select a valid start time', 'danger');
                    }
                });
            }
        }
        
        // Enhanced load schedule functionality
        document.addEventListener('DOMContentLoaded', function() {
            const loadScheduleBtn = document.getElementById('loadScheduleBtn');
            if (loadScheduleBtn) {
                loadScheduleBtn.addEventListener('click', async function() {
                    const subject = document.getElementById('scheduleSubjectSelect').value;
                    const section = document.getElementById('scheduleSectionSelect').value;
                    
                    if (!subject || !section) {
                        showInstructorAlert('Please select both subject and section', 'warning');
                        return;
                    }
                    
                    // Show loading state
                    loadScheduleBtn.disabled = true;
                    loadScheduleBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    
                    try {
                        // Get current instructor name
                        const currentInstructor = '<?= $_SESSION['userData']['username'] ?? $_SESSION['email'] ?? '' ?>';
                        
                        const response = await fetch(`./api/get-schedule-data.php?action=get_filtered_schedules&instructor=${encodeURIComponent(currentInstructor)}&subject=${encodeURIComponent(subject)}&section=${encodeURIComponent(section)}`);
                        
                        const data = await response.json();
                        
                        if (data.success && data.schedules && data.schedules.length > 0) {
                            const schedule = data.schedules[0]; // Take the first matching schedule
                            
                            // Display schedule info
                            const scheduleInfo = document.getElementById('scheduleInfo');
                            document.getElementById('scheduleInstructor').textContent = schedule.teacher_username;
                            document.getElementById('scheduleSubject').textContent = schedule.subject;
                            document.getElementById('scheduleSection').textContent = schedule.section;
                            document.getElementById('scheduleRoom').textContent = schedule.room || 'N/A';
                            document.getElementById('scheduleTime').textContent = `${schedule.start_time} - ${schedule.end_time}`;
                            
                            scheduleInfo.style.display = 'block';
                            
                            // Auto-set class time
                            const timeInput = document.getElementById('classStartTime');
                            if (timeInput) {
                                timeInput.value = schedule.start_time;
                                // Trigger time setting automatically
                                setClassTime(schedule.start_time, 60);
                            }
                            
                            // Update session info display
                            updateSessionInfo(schedule);
                            
                            showInstructorAlert('Schedule loaded successfully! Class time set automatically.', 'success');
                        } else {
                            showInstructorAlert('No schedule found for the selected subject and section', 'warning');
                        }
                    } catch (error) {
                        console.error('Error loading schedule:', error);
                        showInstructorAlert('Error loading schedule. Please try again.', 'error');
                    } finally {
                        // Reset button
                        loadScheduleBtn.disabled = false;
                        loadScheduleBtn.innerHTML = '<i class="fas fa-calendar-alt"></i> Load Schedule';
                    }
                });
            }
        });
        
        // Function to set class time automatically
        function setClassTime(startTime, duration) {
            // Calculate end time
            const start = new Date(`2024-01-01 ${startTime}`);
            const end = new Date(start.getTime() + (duration * 60000));
            const endTime = end.toTimeString().substr(0, 5);
            
            // Format end time to 12-hour format
            const formattedEndTime = end.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            // Format time for display
            const time = new Date(`2024-01-01 ${startTime}`);
            const formattedTime = time.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            // Update display in class time settings section
            const displayedStartTime = document.getElementById('displayedStartTime');
            if (displayedStartTime) {
                displayedStartTime.textContent = formattedTime;
            }
            
            // Show current time settings
            const currentTimeSettings = document.getElementById('currentTimeSettings');
            if (currentTimeSettings) {
                currentTimeSettings.style.display = 'block';
            }
            
            // Update the hidden input field for attendance checks
            const classStartTimeInput = document.getElementById('class-start-time');
            if (classStartTimeInput) {
                classStartTimeInput.value = startTime;
            }
            
            // SHOW PROMINENT CONFIRMATION BANNER
            const confirmationBanner = document.getElementById('classTimeConfirmationBanner');
            if (confirmationBanner) {
                // Set the time in the banner
                const bannerClassTime = document.getElementById('bannerClassTime');
                if (bannerClassTime) {
                    bannerClassTime.textContent = formattedTime;
                }
                
                // Show banner with animation
                confirmationBanner.classList.remove('hide');
                confirmationBanner.style.display = 'block';
                
                // Scroll to make sure banner is visible
                confirmationBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Auto-hide after 8 seconds
                setTimeout(function() {
                    confirmationBanner.classList.add('hide');
                    setTimeout(function() {
                        confirmationBanner.style.display = 'none';
                    }, 500);
                }, 8000);
            }
            
            // Show session info
            const sessionInfo = document.getElementById('sessionInfoArea');
            const sessionDetails = document.getElementById('sessionDetails');
            if (sessionInfo && sessionDetails) {
                sessionDetails.innerHTML = `
                    <small>
                        <strong>Start:</strong> ${formattedTime}<br>
                        <strong>Duration:</strong> ${duration} minutes<br>
                        <strong>End:</strong> ${formattedEndTime}
                    </small>
                `;
                sessionInfo.style.display = 'block';
                
                // Preserve session state
                preserveSessionState();
            }
            
            // Update confirmation text above attendance table
            const attendanceTableHeader = document.querySelector('.attendance-header-info');
            if (attendanceTableHeader) {
                let classTimeInfo = attendanceTableHeader.querySelector('.class-time-info');
                if (!classTimeInfo) {
                    classTimeInfo = document.createElement('div');
                    classTimeInfo.className = 'class-time-info alert alert-success py-2 mb-2';
                    attendanceTableHeader.prepend(classTimeInfo);
                }
                classTimeInfo.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Class Time Set:</strong> ' + formattedTime;
                classTimeInfo.style.display = 'block';
            }
        }
        
        // Function to terminate active class session
        function terminateClassSession() {
            console.log('terminateClassSession function called');
            
            if (!confirm('Are you sure you want to terminate the current class session? This will end attendance tracking for the current session.')) {
                console.log('User cancelled termination');
                return;
            }
            
            // Show loading state
            const terminateBtn = document.getElementById('terminateClassSession');
            const originalText = terminateBtn.innerHTML;
            terminateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Terminating...';
            terminateBtn.disabled = true;
            
            // Make API call to terminate session
            fetch('api/terminate-class-session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    if (typeof showInstructorAlert === 'function') {
                        showInstructorAlert('Class session terminated successfully', 'success');
                    } else {
                        alert('Class session terminated successfully');
                    }
                    
                    // Update the UI to show no active session
                    const classTimeStatus = document.getElementById('classTimeStatus');
                    if (classTimeStatus) {
                        classTimeStatus.innerHTML = `
                            <div id="noActiveSessionAlert" class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> No Active Session</h6>
                                <p class="mb-0">Please set a class start time to begin attendance tracking.</p>
                            </div>
                        `;
                    }
                    
                    // Hide session info area
                    const sessionInfoArea = document.getElementById('sessionInfoArea');
                    if (sessionInfoArea) {
                        sessionInfoArea.style.display = 'none';
                    }
                    
                    // Remove class time info from attendance table header
                    const attendanceTableHeader = document.querySelector('.attendance-header-info');
                    if (attendanceTableHeader) {
                        const classTimeInfo = attendanceTableHeader.querySelector('.class-time-info');
                        if (classTimeInfo) {
                            classTimeInfo.remove();
                        }
                    }
                    
                    // Hide confirmation banner if visible
                    const confirmationBanner = document.getElementById('classTimeConfirmationBanner');
                    if (confirmationBanner) {
                        confirmationBanner.style.display = 'none';
                    }
                    
                    // Clear any stored session state
                    if (typeof preserveSessionState === 'function') {
                        // Clear session state
                        localStorage.removeItem('classSessionState');
                    }
                    
                    console.log('Session terminated:', data.data);
                } else {
                    if (typeof showInstructorAlert === 'function') {
                        showInstructorAlert('Error terminating session: ' + data.message, 'danger');
                    } else {
                        alert('Error terminating session: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error terminating session:', error);
                showInstructorAlert('Error terminating session. Please try again.', 'danger');
            })
            .finally(() => {
                // Restore button state
                terminateBtn.innerHTML = originalText;
                terminateBtn.disabled = false;
            });
        }
        
        // Function to update session info with schedule data
        function updateSessionInfo(schedule) {
            const sessionInfo = document.getElementById('sessionInfoArea');
            const sessionDetails = document.getElementById('sessionDetails');
            
            if (sessionInfo && sessionDetails) {
                sessionDetails.innerHTML = `
                    <small>
                        <strong>Subject:</strong> ${schedule.subject}<br>
                        <strong>Section:</strong> ${schedule.section}<br>
                        <strong>Room:</strong> ${schedule.room || 'N/A'}<br>
                        <strong>Time:</strong> ${schedule.start_time} - ${schedule.end_time}
                    </small>
                `;
                sessionInfo.style.display = 'block';
                sessionInfo.className = 'mt-2 alert alert-success';
            }
        }
        
        // Enhanced subject setting for general mode
        const setSubjectBtn = document.getElementById('setCurrentSubject');
        if (setSubjectBtn) {
            setSubjectBtn.addEventListener('click', function() {
                const subjectSelect = document.getElementById('generalSubjectSelect');
                const selectedOption = subjectSelect.options[subjectSelect.selectedIndex];
                
                if (selectedOption.value) {
                    // Update displayed subject
                    const displayedSubject = document.getElementById('displayedSubject');
                    if (displayedSubject) {
                        displayedSubject.textContent = selectedOption.text;
                    }
                    
                    showInstructorAlert('Subject set successfully: ' + selectedOption.text, 'success');
                } else {
                    showInstructorAlert('Please select a subject', 'warning');
                }
            });
        }
        
        // Schedule filter handling (updated)
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
            
            // Terminate Class Session
            try {
                const terminateBtn = document.getElementById('terminateClassSession');
                console.log('Terminate button found:', terminateBtn);
                if (terminateBtn) {
                    terminateBtn.addEventListener('click', function() {
                        console.log('Terminate button clicked!');
                        if (typeof terminateClassSession === 'function') {
                            terminateClassSession();
                        } else {
                            console.error('terminateClassSession function not found!');
                            alert('Error: terminateClassSession function not available');
                        }
                    });
                    console.log('Terminate button event listener attached');
                } else {
                    console.log('Terminate button not found in DOM');
                }
            } catch (error) {
                console.error('Error setting up terminate button:', error);
            }
            
            // Set Class Time
            // Make sure button exists
            const setTimeBtn = document.getElementById('setClassTime');
            if (!setTimeBtn) {
                console.error("setClassTime button not found!");
                // Don't return here - let other code continue
            } else {
                setTimeBtn.addEventListener('click', function() {
                    console.log("Set Class Time button clicked");
                    const startTimeInput = document.getElementById('classStartTime');
                    if (!startTimeInput) {
                        console.error("classStartTime input not found!");
                        alert("Error: Time input not found!");
                        return;
                    }
                    
                    const startTime = startTimeInput.value;
                    const durationInput = document.getElementById('classDuration');
                    const duration = durationInput ? durationInput.value : '60';
                    
                    // Add validation and visual feedback
                    if (!startTime) {
                        startTimeInput.classList.add('is-invalid');
                        alert('Please select a valid start time');
                        return;
                    } else {
                        startTimeInput.classList.remove('is-invalid');
                        startTimeInput.classList.add('is-valid');
                    }
                    
                    // Show loading state
                    const setButton = document.getElementById('setClassTime');
                    const originalButtonText = setButton.innerHTML;
                    setButton.disabled = true;
                    setButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Setting...';
                    
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
                        console.log('Server response:', data);
                        if (data.success) {
                            alert('Class time set successfully to ' + (data.data.formatted_time || startTime) + '!');
                            // Force reload with cache busting to ensure fresh data
                            setTimeout(function() {
                                window.location.href = window.location.pathname + '?t=' + new Date().getTime();
                            }, 1000);
                        } else {
                            alert('Failed to set class time: ' + (data.message || 'Unknown error'));
                        }
                        
                        // Reset button state
                        setButton.disabled = false;
                        setButton.innerHTML = originalButtonText;
                    })
                    .catch(error => {
                        console.error('Error setting class time:', error);
                        alert('An error occurred while setting class time. Please try again.');
                        
                        // Reset button state
                        setButton.disabled = false;
                        setButton.innerHTML = originalButtonText;
                    });
                });
            }
            
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
                console.log("Showing class time alert:", message, type);
                const alertArea = document.getElementById('classTimeAlertArea');
                
                // Check if alert area exists
                if (!alertArea) {
                    console.error("Alert area not found! Creating one...");
                    // Create alert area if it doesn't exist
                    const classTimeForm = document.getElementById('classTimeForm');
                    if (classTimeForm) {
                        const newAlertArea = document.createElement('div');
                        newAlertArea.id = 'classTimeAlertArea';
                        newAlertArea.className = 'mt-3 alert';
                        classTimeForm.insertAdjacentElement('afterend', newAlertArea);
                        return showClassTimeAlert(message, type); // Try again with new element
                    } else {
                        console.error("Could not find classTimeForm to append alert area");
                        return;
                    }
                }
                
                // Set alert content with icon
                let icon = '';
                if (type === 'success') {
                    icon = '<i class="fas fa-check-circle mr-2"></i>';
                } else if (type === 'danger') {
                    icon = '<i class="fas fa-exclamation-circle mr-2"></i>';
                } else if (type === 'warning') {
                    icon = '<i class="fas fa-exclamation-triangle mr-2"></i>';
                }
                
                // Enhanced styling for better visibility
                alertArea.innerHTML = icon + message;
                alertArea.className = 'mt-3 alert';
                alertArea.classList.add(type === 'success' ? 'alert-success' : type === 'warning' ? 'alert-warning' : 'alert-danger');
                
                if (type === 'success') {
                    alertArea.style.fontWeight = '500';
                    alertArea.style.border = '1px solid #28a745';
                    alertArea.style.boxShadow = '0 0 5px rgba(40, 167, 69, 0.3)';
                }
                
                alertArea.style.display = 'block';
                
                // Animate the appearance of the alert
                alertArea.style.opacity = '0';
                alertArea.style.transform = 'translateY(-10px)';
                alertArea.style.transition = 'all 0.3s ease-in-out';
                
                setTimeout(function() {
                    alertArea.style.opacity = '1';
                    alertArea.style.transform = 'translateY(0)';
                }, 10);
                
                // Auto hide after 5 seconds if it's a success message
                if (type === 'success') {
                    setTimeout(function() {
                        alertArea.style.opacity = '0';
                        alertArea.style.transform = 'translateY(-10px)';
                        
                        setTimeout(function() {
                            alertArea.style.display = 'none';
                        }, 300);
                    }, 5000);
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
        // Simple Set Class Time functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Set Class Time script loaded");
            
            // Start session clock if there's already an active session
            const sessionTimeElement = document.getElementById('currentSessionTime');
            if (sessionTimeElement) {
                console.log("Active session detected, starting clock");
                startSessionClock();
            }
            
            const setTimeBtn = document.getElementById('setClassTime');
            if (setTimeBtn) {
                console.log("Set button found, adding click listener");
                setTimeBtn.addEventListener('click', function() {
                    console.log("Set button clicked!");
                    
                    const startTimeInput = document.getElementById('classStartTime');
                    if (!startTimeInput) {
                        alert("Time input not found!");
                        return;
                    }
                    
                    const startTime = startTimeInput.value;
                    if (!startTime) {
                        alert('Please select a valid start time');
                        return;
                    }
                    
                    console.log("Setting time:", startTime);
                    
                    // Disable button and show loading
                    setTimeBtn.disabled = true;
                    setTimeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Setting...';
                    
                    // Send AJAX request
                    fetch('api/set-class-time.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'classStartTime=' + encodeURIComponent(startTime)
                    })
                    .then(response => {
                        console.log('Raw response status:', response.status);
                        console.log('Response headers:', response.headers);
                        return response.text(); // Get as text first to see if it's valid JSON
                    })
                    .then(text => {
                        console.log('Raw response text:', text);
                        try {
                            const data = JSON.parse(text);
                            console.log('Parsed JSON data:', data);
                            if (data.success) {
                                console.log('Class time set successfully, showing modal...');
                                // Update the session status display immediately
                                updateClassTimeStatus(data.data);
                                // Show success modal with delay to ensure DOM is ready
                                setTimeout(() => {
                                    showClassTimeSuccessModal(data.data);
                                }, 100);
                            } else {
                                console.error('Server returned error:', data.message);
                                alert('Failed: ' + data.message);
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Response was not valid JSON:', text);
                            alert('Error: Invalid response from server');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error occurred. Please try again.');
                    })
                    .finally(() => {
                        setTimeBtn.disabled = false;
                        setTimeBtn.innerHTML = '<i class="fas fa-save mr-1"></i> Set';
                    });
                });
            } else {
                console.error("Set button not found!");
            }
        });
        
        // Function to update class time status display
        function updateClassTimeStatus(data) {
            const statusDiv = document.getElementById('classTimeStatus');
            if (statusDiv) {
                const currentTime = new Date().toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                
                statusDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-clock"></i> Active Class Session</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Start Time:</strong> ${data.formatted_time || data.class_start_time}
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong> <span class="badge badge-success">Active</span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <strong>Instructor:</strong> ${data.instructor || 'Not set'}
                            </div>
                            <div class="col-md-6">
                                <strong>Subject:</strong> ${data.subject || 'Not set'}
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> Session started at ${currentTime}
                                </small>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="bg-light p-2 rounded">
                                    <small>
                                        <strong>Current Time:</strong> 
                                        <span id="currentSessionTime" class="font-weight-bold text-primary"></span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Start updating the current time every second
                startSessionClock();
                
                // Preserve session state
                preserveSessionState();
            }
        }
        
        // Function to start the session clock
        function startSessionClock() {
            const timeElement = document.getElementById('currentSessionTime');
            if (timeElement) {
                function updateTime() {
                    const now = new Date();
                    const timeString = now.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    timeElement.textContent = timeString;
                }
                
                // Update immediately
                updateTime();
                
                // Update every second
                setInterval(updateTime, 1000);
            }
        }
        
        // Function to show success modal with session details
        function showClassTimeSuccessModal(data) {
            console.log('showClassTimeSuccessModal called with data:', data);
            
            // Show immediate alert to test if function is called
            alert('SUCCESS! Class Time Set to: ' + (data.formatted_time || data.class_start_time));
            
            // Remove existing modals first
            const existingModal = document.getElementById('classTimeSuccessModal');
            if (existingModal) {
                try {
                    $(existingModal).modal('hide');
                } catch(e) {
                    console.log('Error hiding existing modal:', e);
                }
                existingModal.remove();
            }
            
            const modalHtml = `
                <div class="modal fade" id="classTimeSuccessModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-check-circle"></i> Class Time Set Successfully
                                </h5>
                                <button type="button" class="close text-white" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <i class="fas fa-clock text-success" style="font-size: 48px;"></i>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Start Time:</strong>
                                    </div>
                                    <div class="col-6">
                                        ${data.formatted_time || data.class_start_time}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Instructor:</strong>
                                    </div>
                                    <div class="col-6">
                                        ${data.instructor || 'Not set'}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Subject:</strong>
                                    </div>
                                    <div class="col-6">
                                        ${data.subject || 'Not set'}
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            The class session is now active and ready for attendance tracking!
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-success" data-dismiss="modal">
                                    <i class="fas fa-check"></i> Continue with Current Session
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show modal with a small delay to ensure DOM is ready
            setTimeout(() => {
                try {
                    if (typeof $ !== 'undefined' && $.fn.modal) {
                        console.log('Showing Bootstrap modal');
                        $('#classTimeSuccessModal').modal({
                            backdrop: 'static',
                            keyboard: false
                        }).modal('show');
                        
                        // Auto-hide after 5 seconds
                        setTimeout(() => {
                            $('#classTimeSuccessModal').modal('hide');
                        }, 5000);
                    } else {
                        // Fallback if Bootstrap modal is not available
                        console.log('Bootstrap modal not available, using fallback');
                        const modal = document.getElementById('classTimeSuccessModal');
                        if (modal) {
                            modal.style.display = 'block';
                            modal.classList.add('show');
                            modal.style.paddingRight = '17px';
                            document.body.classList.add('modal-open');
                            
                            // Create backdrop
                            const backdrop = document.createElement('div');
                            backdrop.className = 'modal-backdrop fade show';
                            document.body.appendChild(backdrop);
                            
                            // Auto-hide after 5 seconds
                            setTimeout(() => {
                                modal.style.display = 'none';
                                modal.classList.remove('show');
                                document.body.classList.remove('modal-open');
                                if (backdrop.parentNode) {
                                    backdrop.parentNode.removeChild(backdrop);
                                }
                            }, 5000);
                        }
                    }
                } catch (error) {
                    console.error('Error showing modal:', error);
                    // Simple alert fallback
                    alert(`Class Time Set Successfully!\nStart Time: ${data.formatted_time || data.class_start_time}\nThe class session is now active!`);
                }
            }, 100);
        }
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
                
                // The QR code contains the student information directly
                // Pass the QR code as-is to the API
                var qr_code = qrCodeMessage;
                
                // Log the scanning activity (using the helper function via AJAX)
                $.ajax({
                    url: 'api/log-activity.php',
                    type: 'POST',
                    data: {
                        action_type: 'qr_scan',
                        description: 'Scanned QR code: ' + qr_code,
                        table: 'tbl_attendance',
                        additional_data: JSON.stringify({
                            qr_code: qr_code
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
                checkAttendance(qr_code);
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
                
                // For manual entry, we need to find a student by course and section
                // This is a different approach since we don't have a QR code
                $.ajax({
                    url: 'api/check-attendance.php',
                    type: 'POST',
                    data: {
                        course_code: course_code,
                        section: section,
                        instructor_id: instructor_id,
                        manual_entry: true
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
                            
                            // Instead of reloading, update the attendance table and preserve session
                            updateAttendanceTableAfterScan(response);
                        } else {
                            showAlert(response.message || 'Failed to record attendance. Please try again.', 'danger');
                        }
                    },
                    error: function() {
                        $("#scanning-indicator").hide();
                        showAlert('Network error. Please try again.', 'danger');
                    }
                });
            });
            
            // Function to check attendance
            function checkAttendance(qr_code) {
                console.log('Processing QR code:', qr_code);
                
                // Show diagnostic info to help debug
                $("#scanning-indicator").show();
                $("#scanning-indicator").html('<div class="spinner-border text-primary" role="status"></div> Processing QR: ' + qr_code);
                
                // AJAX call to check attendance with better error handling
                $.ajax({
                    url: 'api/check-attendance.php',
                    type: 'POST',
                    data: {
                        qr_code: qr_code,
                        instructor_id: <?php echo $user_id; ?> // Explicitly pass user_id as instructor_id
                    },
                    dataType: 'json',
                    timeout: 10000, // 10 second timeout
                    success: function(response) {
                        $("#scanning-indicator").hide();
                        console.log('API response:', response);
                        
                        if (response.success) {
                            showAlert('Attendance recorded successfully!', 'success');
                            
                            // Immediately preserve session state
                            preserveSessionState();
                            
                            // If student_id is available, also record in the new attendance_logs table
                            if (response.data && response.data.student_id) {
                                recordAttendanceLog(response.data.student_id);
                            }
                            
                            // Instead of reloading, update the attendance table and preserve session
                            updateAttendanceTableAfterScan(response);
                            
                            // Restart scanner after a delay to allow for more scans
                            setTimeout(function() {
                                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                            }, 3000);
                        } else {
                            // Enhanced error message with debug info if available
                            let errorMsg = response.message || 'Failed to record attendance. Please try again.';
                            if (response.debug) {
                                console.error('Debug info:', response.debug);
                                errorMsg += ' (Error details in console)';
                            }
                            showAlert(errorMsg, 'danger');
                            
                            // Restart scanner
                            setTimeout(function() {
                                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                            }, 3000);
                        }
                    },
                    error: function(xhr, status, error) {
                        $("#scanning-indicator").hide();
                        console.error('AJAX error:', status, error);
                        console.error('Response:', xhr.responseText);
                        
                        // Try to parse response if it's JSON
                        let errorMessage = 'Network error. Please try again.';
                        try {
                            const jsonResponse = JSON.parse(xhr.responseText);
                            if (jsonResponse && jsonResponse.message) {
                                errorMessage = jsonResponse.message;
                            }
                        } catch (e) {
                            // Use default message if parsing fails
                        }
                        
                        showAlert(errorMessage, 'danger');
                        
                        // Restart scanner
                        setTimeout(function() {
                            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                        }, 3000);
                    }
                });
            }
            
            // Function to update attendance table after successful scan without page reload
            function updateAttendanceTableAfterScan(response) {
                console.log('updateAttendanceTableAfterScan called with:', response);
                
                // Force session preservation
                preserveSessionState();
                
                // Get the attendance table body
                const attendanceTableBody = document.querySelector('#attendanceTable tbody');
                if (attendanceTableBody && response.data) {
                    const data = response.data;
                    
                    // Format the time to match existing table format
                    const now = new Date();
                    const timeFormatted = now.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true
                    });
                    
                    const dateFormatted = now.toLocaleDateString('en-US', {
                        month: 'short',
                        day: '2-digit',
                        year: 'numeric'
                    });
                    
                    // Create new row
                    const newRow = document.createElement('tr');
                    
                    // Get the current row count for the # column
                    const currentRowCount = attendanceTableBody.children.length + 1;
                    
                    // Determine status badge with icons to match existing format
                    const statusBadge = data.status === 'On Time' 
                        ? `<span class="badge badge-success"><i class="fas fa-check-circle"></i> On Time</span>`
                        : `<span class="badge badge-warning"><i class="fas fa-clock"></i> Late</span>`;
                    
                    newRow.innerHTML = `
                        <td class="text-center">${currentRowCount}</td>
                        <td>${data.student_name || 'Unknown'}</td>
                        <td class="text-center">${data.course_section || 'N/A'}</td>
                        <td class="text-center" style="white-space: nowrap;">${dateFormatted}</td>
                        <td class="text-center" style="white-space: nowrap;">${timeFormatted}</td>
                        <td class="text-center" style="white-space: nowrap;">${statusBadge}</td>
                        <td class="text-center">
                            <div class="action-button">
                                <button class="btn btn-danger delete-button" onclick="deleteAttendance(${data.attendance_id || 0})">X</button>
                            </div>
                        </td>
                    `;
                    
                    // Add the new row at the top of the table
                    attendanceTableBody.insertBefore(newRow, attendanceTableBody.firstChild);
                    
                    // Update row numbers for all existing rows
                    const allRows = attendanceTableBody.querySelectorAll('tr');
                    allRows.forEach((row, index) => {
                        const numberCell = row.querySelector('td:first-child');
                        if (numberCell) {
                            numberCell.textContent = index + 1;
                        }
                    });
                    
                    // Add highlight animation to the new row
                    newRow.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        newRow.style.backgroundColor = '';
                    }, 3000);
                    
                    console.log('New attendance row added successfully');
                }
                
                // Update any status displays or counters if they exist
                updateAttendanceCounters();
                
                // Force another session preservation after DOM updates
                setTimeout(() => {
                    preserveSessionState();
                }, 100);
            }
            
            // Function to preserve session state
            function preserveSessionState() {
                console.log('Preserving session state...');
                
                // Hide "No Active Session" warning
                const noActiveSessionAlert = document.getElementById('noActiveSessionAlert');
                if (noActiveSessionAlert) {
                    noActiveSessionAlert.style.display = 'none';
                    console.log('Hidden noActiveSessionAlert');
                }
                
                // Show session info area
                const sessionInfo = document.getElementById('sessionInfoArea');
                if (sessionInfo) {
                    sessionInfo.style.display = 'block';
                    console.log('Shown sessionInfoArea');
                }
                
                // Show current time settings
                const currentTimeSettings = document.getElementById('currentTimeSettings');
                if (currentTimeSettings) {
                    currentTimeSettings.style.display = 'block';
                    console.log('Shown currentTimeSettings');
                }
                
                console.log('Session state preservation completed');
            }
            
            // Function to update attendance counters/statistics
            function updateAttendanceCounters() {
                const attendanceTableBody = document.querySelector('#attendanceTable tbody');
                if (attendanceTableBody) {
                    const totalCount = attendanceTableBody.children.length;
                    const onTimeCount = attendanceTableBody.querySelectorAll('.badge-success').length;
                    const lateCount = attendanceTableBody.querySelectorAll('.badge-warning').length;
                    
                    // Update any counter displays if they exist
                    const totalCountElement = document.getElementById('totalAttendanceCount');
                    if (totalCountElement) {
                        totalCountElement.textContent = totalCount;
                    }
                    
                    const onTimeCountElement = document.getElementById('onTimeCount');
                    if (onTimeCountElement) {
                        onTimeCountElement.textContent = onTimeCount;
                    }
                    
                    const lateCountElement = document.getElementById('lateCount');
                    if (lateCountElement) {
                        lateCountElement.textContent = lateCount;
                    }
                }
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

    <!-- Attendance Mode and Real-time Updates Script -->
    <script>
        $(document).ready(function() {
            // Attendance Mode Management
            $('#attendanceMode').on('change', function() {
                const mode = $(this).val();
                toggleAttendanceMode(mode);
                updateModeDescription(mode);
                
                // Store preference in localStorage
                localStorage.setItem('attendanceMode', mode);
            });
            
            // Load saved preference or default to general
            const savedMode = localStorage.getItem('attendanceMode') || 'general';
            $('#attendanceMode').val(savedMode);
            toggleAttendanceMode(savedMode);
            updateModeDescription(savedMode);
            
            function toggleAttendanceMode(mode) {
                if (mode === 'room_subject') {
                    // Show schedule section, hide instructor section
                    $('#scheduleSectionContainer').show();
                    $('#instructorSection').hide();
                    
                    // Update QR form to use schedule data
                    $('#class-start-time').attr('name', 'schedule_start_time');
                } else {
                    // Show instructor section, hide schedule section
                    $('#instructorSection').show();
                    $('#scheduleSectionContainer').hide();
                    
                    // Reset QR form to use manual class time
                    $('#class-start-time').attr('name', 'class_start_time');
                }
            }
            
            function updateModeDescription(mode) {
                const descriptions = {
                    'general': '<strong>General Mode:</strong> Manual attendance tracking without schedule integration.',
                    'room_subject': '<strong>Room & Subject Mode:</strong> Schedule-integrated attendance with automatic class time detection.'
                };
                $('#modeDescription').html(descriptions[mode]);
            }
            
            // Schedule Loading for Room & Subject Mode
            $('#loadScheduleBtn').on('click', function() {
                const section = $('#scheduleSectionSelect').val();
                const subject = $('#scheduleSubjectSelect').val();
                
                if (!section || !subject) {
                    showCustomAlert('Please select both Section and Subject fields', 'warning');
                    return;
                }
                
                // Load schedule data via AJAX
                $.ajax({
                    url: 'api/get-schedule.php',
                    type: 'POST',
                    data: {
                        section: section,
                        subject: subject,
                        school_id: <?php echo $school_id; ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.schedule) {
                            const schedule = response.schedule;
                            
                            // Update schedule info display
                            $('#scheduleInstructor').text(schedule.instructor_name);
                            $('#scheduleSection').text(schedule.course_section);
                            $('#scheduleSubject').text(schedule.subject);
                            $('#scheduleTime').text(schedule.start_time + ' - ' + schedule.end_time);
                            $('#scheduleInfo').show();
                            
                            // Auto-set class time
                            $('#classStartTime').val(schedule.start_time);
                            $('#setClassTime').click(); // Trigger class time setting
                            
                            // Store schedule data in session
                            $.post('api/set-schedule-session.php', {
                                schedule_id: schedule.id,
                                subject: schedule.subject,
                                section: schedule.course_section,
                                start_time: schedule.start_time
                            });
                            
                            showCustomAlert('Schedule loaded successfully!', 'success');
                        } else {
                            showCustomAlert('No schedule found for the selected criteria', 'warning');
                        }
                    },
                    error: function() {
                        showCustomAlert('Error loading schedule. Please try again.', 'error');
                    }
                });
            });
            
            // Real-time attendance table updates
            let lastAttendanceId = 0;
            
            function initializeLastAttendanceId() {
                const firstRow = $('#attendanceTable tbody tr:first-child th:first-child');
                if (firstRow.length) {
                    lastAttendanceId = parseInt(firstRow.text()) || 0;
                }
            }
            
            function checkForNewAttendance() {
                $.ajax({
                    url: 'api/get-latest-attendance.php',
                    type: 'GET',
                    data: {
                        last_id: lastAttendanceId,
                        school_id: <?php echo $school_id; ?>,
                        user_id: <?php echo $user_id; ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.records && response.records.length > 0) {
                            response.records.forEach(function(record) {
                                addAttendanceRow(record);
                                lastAttendanceId = Math.max(lastAttendanceId, record.tbl_attendance_id);
                            });
                            
                            // Show notification
                            showAttendanceNotification(response.records.length);
                        }
                    },
                    error: function() {
                        console.log('Error checking for new attendance');
                    }
                });
            }
            
            function addAttendanceRow(record) {
                // Enhanced status display with colored badges
                const statusBadge = (record.status === 'On Time') 
                    ? `<span class='badge badge-success'><i class='fas fa-check-circle'></i> On Time</span>` 
                    : (record.status === 'Late'
                        ? `<span class='badge badge-warning'><i class='fas fa-clock'></i> Late</span>`
                        : `<span class='badge badge-secondary'><i class='fas fa-question-circle'></i> ${record.status || 'Unknown'}</span>`);
                
                const newRow = `
                    <tr style="background-color: #098744; color: white;" data-new="true">
                        <th scope="row">${record.tbl_attendance_id}</th>
                        <td>${record.student_name}</td>
                        <td>${record.course_section}</td>
                        <td style="white-space: nowrap;">${record.formatted_date}</td>
                        <td style="white-space: nowrap;">${record.formatted_time}</td>
                        <td style="white-space: nowrap;">${statusBadge}</td>
                        <td>
                            <div class="action-button">
                                <button class="btn btn-danger delete-button" onclick="deleteAttendance(${record.tbl_attendance_id})">X</button>
                            </div>
                        </td>
                    </tr>
                `;
                
                $('#attendanceTable tbody').prepend(newRow);
                
                // Add a subtle animation for new rows
                $('tr[data-new="true"]').hide().fadeIn(1000);
                
                // Remove the data-new attribute after animation
                setTimeout(function() {
                    $('tr[data-new="true"]').removeAttr('data-new');
                }, 1000);
            }
            
            function showAttendanceNotification(count) {
                const message = count === 1 ? 'New attendance record added!' : `${count} new attendance records added!`;
                const notification = `
                    <div id="attendanceNotification" class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i> ${message}
                    </div>
                `;
                
                $('.attendance-container').prepend(notification);
                
                // Auto-remove notification after 5 seconds
                setTimeout(function() {
                    $('#attendanceNotification').fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Initialize real-time updates
            initializeLastAttendanceId();
            
            // Check for new attendance every 5 seconds
            setInterval(checkForNewAttendance, 5000);
            
            // Enhanced session validation for QR scanning
            function validateSession() {
                $.ajax({
                    url: 'api/validate-session.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (!response.valid) {
                            showCustomAlert('Session expired. Please refresh the page.', 'warning');
                            // Optionally redirect to login
                            setTimeout(function() {
                                window.location.href = 'admin/login.php';
                            }, 3000);
                        }
                    },
                    error: function() {
                        console.log('Session validation failed');
                    }
                });
            }
            
            // Validate session every 2 minutes
            setInterval(validateSession, 120000);
            
            // Function to show success attendance modal
            function showSuccessAttendanceModal(studentName, status) {
                const modal = $('#successAttendanceModal');
                const studentNameSpan = $('#successStudentName');
                const statusSpan = $('#successStatus');
                
                // Update modal content
                if (studentName) {
                    studentNameSpan.text(studentName);
                }
                if (status) {
                    statusSpan.text(status);
                }
                
                // Show modal with animation
                modal.show();
                modal.removeClass('fade-out');
                
                // Play success sound using Web Audio API
                playSuccessSound();
                
                // Auto-hide after 1.5 seconds
                setTimeout(function() {
                    modal.addClass('fade-out');
                    setTimeout(function() {
                        modal.hide();
                        modal.removeClass('fade-out');
                    }, 300); // Wait for fade-out animation
                }, 1500);
            }
            
            // Function to play success sound using Web Audio API
            function playSuccessSound() {
                try {
                    // Create audio context
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    
                    // Create success tone sequence
                    const playTone = (frequency, startTime, duration) => {
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();
                        
                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);
                        
                        oscillator.frequency.setValueAtTime(frequency, startTime);
                        oscillator.type = 'sine';
                        
                        // Envelope for smooth sound
                        gainNode.gain.setValueAtTime(0, startTime);
                        gainNode.gain.linearRampToValueAtTime(0.1, startTime + 0.01);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, startTime + duration);
                        
                        oscillator.start(startTime);
                        oscillator.stop(startTime + duration);
                    };
                    
                    // Play success melody: C5 -> E5 -> G5
                    const now = audioContext.currentTime;
                    playTone(523.25, now, 0.15);        // C5
                    playTone(659.25, now + 0.1, 0.15);  // E5
                    playTone(783.99, now + 0.2, 0.3);   // G5
                    
                } catch (e) {
                    console.log('Web Audio API not supported or error:', e);
                    // Fallback to HTML5 audio
                    try {
                        const audio = document.getElementById('successSound');
                        if (audio) {
                            audio.currentTime = 0;
                            audio.play().catch(e => console.log('Audio play failed:', e));
                        }
                    } catch (fallbackError) {
                        console.log('Fallback audio also failed:', fallbackError);
                    }
                }
            }
            
            // Check for success parameters in URL
            function checkForSuccessMessage() {
                const urlParams = new URLSearchParams(window.location.search);
                const success = urlParams.get('success');
                const student = urlParams.get('student');
                const status = urlParams.get('status');
                
                if (success === 'attendance_added') {
                    showSuccessAttendanceModal(student, status);
                    
                    // Clean URL without reloading
                    const cleanUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, cleanUrl);
                }
            }
            
            // Function to show error attendance modal
            // Implementation moved to modal-helpers.js
                
                // Play error sound
                playErrorSound();
                
                // Auto-hide after 4 seconds
                setTimeout(function() {
                    modal.addClass('fade-out');
                    setTimeout(function() {
                        modal.hide();
                        modal.removeClass('fade-out');
                    }, 300); // Wait for fade-out animation
                }, 4000);
            }
            
            // Function to play error sound using Web Audio API
            function playErrorSound() {
                try {
                    // Create audio context
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    
                    // Create error tone sequence
                    const playTone = (frequency, startTime, duration) => {
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();
                        
                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);
                        
                        oscillator.frequency.setValueAtTime(frequency, startTime);
                        oscillator.type = 'sine';
                        
                        // Envelope for smooth sound
                        gainNode.gain.setValueAtTime(0, startTime);
                        gainNode.gain.linearRampToValueAtTime(0.2, startTime + 0.01);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, startTime + duration);
                        
                        oscillator.start(startTime);
                        oscillator.stop(startTime + duration);
                    };
                    
                    // Play error melody: Two descending low tones
                    const now = audioContext.currentTime;
                    playTone(220, now, 0.3);        // A3
                    playTone(175, now + 0.35, 0.3); // F3
                    
                } catch (e) {
                    console.log('Web Audio API not supported or error:', e);
                }
            }
            
            // Check for error parameters in URL
            function checkForErrorMessage() {
                const urlParams = new URLSearchParams(window.location.search);
                const error = urlParams.get('error');
                const errorMsg = urlParams.get('message');
                const errorDetails = urlParams.get('details');
                
                console.log('Checking for errors:', { error, errorMsg, errorDetails });
                
                if (error) {
                    console.log('Error found, showing modal');
                    let title = 'QR Code Error';
                    let message = 'Invalid QR Code';
                    
                    if (error === 'invalid_qr') {
                        title = 'Invalid QR Code';
                        message = 'QR code not registered to this school';
                    } else if (error === 'unauthorized') {
                        title = 'Access Denied';
                        message = 'QR code not authorized for this user';
                    } else if (error === 'expired') {
                        title = 'QR Code Expired';
                        message = 'This QR code has expired';
                    } else if (error === 'duplicate_scan') {
                        title = 'Duplicate Attendance';
                        message = 'Attendance already recorded';
                    } else if (error === 'empty_qr') {
                        title = 'No QR Code';
                        message = 'No QR code detected';
                    } else if (error === 'missing_qr') {
                        title = 'QR Data Missing';
                        message = 'QR code data missing';
                    } else if (error === 'db_error') {
                        title = 'Database Error';
                        message = 'Database connection error';
                    } else if (error === 'db_insert_failed') {
                        title = 'Save Failed';
                        message = 'Failed to save attendance';
                    }
                    
                    showErrorAttendanceModal(title, errorMsg || message, errorDetails);
                    
                    // Clean URL without reloading
                    const cleanUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, cleanUrl);
                } else {
                    console.log('No error parameters found');
                }
            }
            
            // Check for success message on page load
            checkForSuccessMessage();
            
            // Check for error message on page load
            checkForErrorMessage();
            
            // Enhanced QR code processing with better error handling
            window.originalOnScanSuccess = window.onScanSuccess;
            window.onScanSuccess = function(decodedText, decodedResult) {
                // Validate session before processing QR
                validateSession();
                
                // Add school and user context to QR processing
                $('#detected-qr-code').val(decodedText);
                $('#detected-qr-code').data('school-id', <?php echo $school_id; ?>);
                $('#detected-qr-code').data('user-id', <?php echo $user_id; ?>);
                
                // Call original function
                if (window.originalOnScanSuccess) {
                    window.originalOnScanSuccess(decodedText, decodedResult);
                }
            };
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

<!-- Success Attendance Modal -->
<div id="successAttendanceModal" class="success-attendance-modal" style="display: none;">
    <div class="success-modal-content">
        <div class="success-icon-container">
            <div class="success-checkmark">
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark-check" fill="none" d="m14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
        </div>
        <h3 class="success-title">✅ Attendance Recorded!</h3>
        <p class="success-message">Your attendance has been successfully recorded.</p>
        <div class="success-details">
            <span id="successStudentName"></span> - <span id="successStatus"></span>
        </div>
    </div>
</div>

<!-- Error Attendance Modal -->
<div id="errorAttendanceModal" class="error-attendance-modal" style="display: none;">
    <div class="error-modal-content">
        <div class="error-icon-container">
            <div class="error-cross">
                <svg class="crossmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="crossmark-circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="crossmark-cross" fill="none" d="m16,16 l20,20 m0,-20 l-20,20"/>
                </svg>
            </div>
        </div>
        <h3 class="error-title">❌ Invalid QR Code!</h3>
        <p class="error-message">This QR code is not registered for this school or user.</p>
        <div class="error-details">
            <span id="errorMessage">QR code not found in your records.</span>
        </div>
        <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="closeErrorAttendanceModal()">Close</button>
    </div>
</div>

<!-- Fallback Audio element for older browsers -->
<audio id="successSound" preload="auto" style="display: none;">
    <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IAAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTmJ0fLPejEIJILM8tyIQAkUXbPp7alVFApFnt/zvmwhBTiJ0PLPejAIJYPM8tuJQAkUXrLo7KlXFAhFnt7zvmwiBjiJ0PLQeSsFJYLM8tyJPwkVXrLo7KlXEwhGnt7zvmwiBjiJ0PLQeSsFJYPM8tyJPwkVXrPo7KlXEwhGnt7zvmwiBjiK0PLQeisFJYPM8tyJQAkVXrPo7KlXEwdGnt7yv2whBjiK0PLQeisFJYPM8tyJQAkVXrPo7KlXEwdGnt7yv2wiBjiK0PLQeisFJYPM8tyJPwkVXrPo7KpXEwdGnt7yv2wiBjiK0PLQeisFJYPM8tyJPwkVXrPo7KpXEwdGnt7yv2wiBjiK0PLQeisFJYPM8tyJPwkVXrPo7KpXEwdGnt7yv2wiBjiK0PLQeisFJYPM8tyJPwkVXrPo7KpXEwdGnt7yv2wiBjiK0PLQeisFJYPM8tyJPwkVXrPo7KpXEwdGnt7yv2wi" type="audio/wav">
</audio>

<style>
/* Success Attendance Modal Styles */
.success-attendance-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease-in-out;
}

.success-modal-content {
    background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 3px solid #48bb78;
    max-width: 400px;
    animation: slideIn 0.4s ease-out;
    position: relative;
    overflow: hidden;
}

.success-modal-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shimmer 1.5s ease-in-out;
}

.success-icon-container {
    margin-bottom: 1rem;
}

.success-checkmark {
    width: 80px;
    height: 80px;
    margin: 0 auto;
}

.checkmark {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: block;
    stroke-width: 3;
    stroke: #48bb78;
    stroke-miterlimit: 10;
    animation: checkmark-scale 0.6s ease-in-out;
}

.checkmark-circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 3;
    stroke-miterlimit: 10;
    stroke: #48bb78;
    fill: none;
    animation: stroke-circle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark-check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: stroke-check 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;
}

.success-title {
    color: #2d7738;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0.5rem 0;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.success-message {
    color: #2d7738;
    font-size: 1rem;
    margin: 0.5rem 0;
    font-weight: 500;
}

.success-details {
    background: rgba(72, 187, 120, 0.1);
    border-radius: 10px;
    padding: 0.75rem;
    margin-top: 1rem;
    color: #1a5827;
    font-weight: 600;
    border: 1px solid rgba(72, 187, 120, 0.3);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        transform: translateY(-50px) scale(0.8);
        opacity: 0;
    }
    to { 
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

@keyframes checkmark-scale {
    0% { transform: scale(0.8); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

@keyframes stroke-circle {
    100% {
        stroke-dashoffset: 0;
    }
}

@keyframes stroke-check {
    100% {
        stroke-dashoffset: 0;
    }
}

/* Fade out animation */
.success-attendance-modal.fade-out {
    animation: fadeOut 0.3s ease-in-out forwards;
}

.success-attendance-modal.fade-out .success-modal-content {
    animation: slideOut 0.3s ease-in forwards;
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}

@keyframes slideOut {
    from { 
        transform: translateY(0) scale(1);
        opacity: 1;
    }
    to { 
        transform: translateY(-30px) scale(0.9);
        opacity: 0;
    }
}

/* Error Attendance Modal Styles */
.error-attendance-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease-in-out;
}

.error-modal-content {
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 3px solid #e53e3e;
    max-width: 400px;
    animation: slideIn 0.4s ease-out;
    position: relative;
    overflow: hidden;
}

.error-modal-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shimmer 1.5s ease-in-out;
}

.error-icon-container {
    margin-bottom: 1rem;
}

.error-cross {
    width: 80px;
    height: 80px;
    margin: 0 auto;
}

.crossmark {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: block;
    stroke-width: 3;
    stroke: #e53e3e;
    stroke-miterlimit: 10;
    animation: checkmark-scale 0.6s ease-in-out;
}

.crossmark-circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 3;
    stroke-miterlimit: 10;
    stroke: #e53e3e;
    fill: none;
    animation: stroke-circle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.crossmark-cross {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: stroke-check 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;
}

.error-title {
    color: #c53030;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0.5rem 0;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.error-message {
    color: #c53030;
    font-size: 1rem;
    margin: 0.5rem 0;
    font-weight: 500;
}

.error-details {
    background: rgba(229, 62, 62, 0.1);
    border-radius: 10px;
    padding: 0.75rem;
    margin-top: 1rem;
    color: #742a2a;
    font-weight: 600;
    border: 1px solid rgba(229, 62, 62, 0.3);
}

/* Error modal fade out animation */
.error-attendance-modal.fade-out {
    animation: fadeOut 0.3s ease-in-out forwards;
}

.error-attendance-modal.fade-out .error-modal-content {
    animation: slideOut 0.3s ease-in forwards;
}

/* Responsive design */
@media (max-width: 768px) {
    .success-modal-content {
        padding: 1.5rem;
        max-width: 320px;
        margin: 1rem;
    }
    
    .success-title {
        font-size: 1.3rem;
    }
    
    .success-checkmark {
        width: 60px;
        height: 60px;
    }
    
    .checkmark {
        width: 60px;
        height: 60px;
    }
}
</style>

<!-- Class Time Set Confirmation Modal -->
<div class="modal fade" id="classTimeModal" tabindex="-1" role="dialog" aria-labelledby="classTimeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-success">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="classTimeModalTitle">
                    <i class="fas fa-check-circle mr-2"></i> Class Time Set Successfully
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-clock text-success" style="font-size: 48px;"></i>
                </div>
                <h4 class="mb-3">Class Time Has Been Set To:</h4>
                <div class="display-4 font-weight-bold text-success mb-3" id="modalClassTime"></div>
                <p class="mb-1">This time will be used for determining attendance status.</p>
                <p class="text-muted"><small>Students arriving after this time will be marked as late.</small></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success px-4" data-dismiss="modal">Got it!</button>
            </div>
        </div>
    </div>
</div>

<!-- Session Preservation Script -->
<script>
    // Utility function to convert time to 12-hour format
    function convertTo12HourFormat(timeString) {
        if (!timeString) return '';
        
        // Handle different time formats
        const time = new Date(`2024-01-01 ${timeString}`);
        if (isNaN(time.getTime())) {
            // If parsing fails, try to handle as 12-hour format already
            return timeString;
        }
        
        return time.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }
    
    // Function to load class time from database
    function loadClassTimeFromDatabase() {
        fetch('api/get-class-time.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    console.log('Class time loaded from database:', data.data);
                    
                    // Update the time input field
                    const timeInput = document.getElementById('classStartTime');
                    if (timeInput) {
                        timeInput.value = data.data.class_start_time;
                    }
                    
                    // Update the displayed time with proper 12-hour format
                    const displayedStartTime = document.getElementById('displayedStartTime');
                    if (displayedStartTime) {
                        // Use the formatted time from API if available, otherwise convert
                        const displayTime = data.data.formatted_time || convertTo12HourFormat(data.data.class_start_time);
                        displayedStartTime.textContent = displayTime;
                    }
                    
                    // Update the class time info in attendance table header
                    const attendanceTableHeader = document.querySelector('.attendance-header-info');
                    if (attendanceTableHeader) {
                        let classTimeInfo = attendanceTableHeader.querySelector('.class-time-info');
                        if (!classTimeInfo) {
                            classTimeInfo = document.createElement('div');
                            classTimeInfo.className = 'class-time-info alert alert-success py-2 mb-2';
                            attendanceTableHeader.prepend(classTimeInfo);
                        }
                        const displayTime = data.data.formatted_time || convertTo12HourFormat(data.data.class_start_time);
                        classTimeInfo.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Class Time Set:</strong> ' + displayTime;
                        classTimeInfo.style.display = 'block';
                    }
                    
                    // Show current time settings
                    const currentTimeSettings = document.getElementById('currentTimeSettings');
                    if (currentTimeSettings) {
                        currentTimeSettings.style.display = 'block';
                    }
                    
                    // Update hidden input for attendance
                    const classStartTimeInput = document.getElementById('class-start-time');
                    if (classStartTimeInput) {
                        classStartTimeInput.value = data.data.class_start_time;
                    }
                    
                    // Preserve session state
                    if (typeof preserveSessionState === 'function') {
                        preserveSessionState();
                    }
                    
                    console.log('Class time restored from database successfully');
                } else {
                    console.log('No class time found in database or error occurred');
                }
            })
            .catch(error => {
                console.error('Error loading class time from database:', error);
            });
    }
    
    // Ensure session information is preserved on page load
    document.addEventListener('DOMContentLoaded', function() {
        // First, try to load class time from database
        loadClassTimeFromDatabase();
        
        // Check if we have an active session by looking for session info in PHP
        <?php if (isset($_SESSION['class_start_time']) && !empty($_SESSION['class_start_time'])): ?>
            // Use the preserveSessionState function if it exists, otherwise fallback
            if (typeof preserveSessionState === 'function') {
                preserveSessionState();
            } else {
                // Fallback preservation logic
                const noActiveSessionAlert = document.getElementById('noActiveSessionAlert');
                if (noActiveSessionAlert) {
                    noActiveSessionAlert.style.display = 'none';
                }
                
                const sessionInfo = document.getElementById('sessionInfoArea');
                if (sessionInfo) {
                    sessionInfo.style.display = 'block';
                }
                
                const currentTimeSettings = document.getElementById('currentTimeSettings');
                if (currentTimeSettings) {
                    currentTimeSettings.style.display = 'block';
                }
            }
            
            console.log('Active session detected and preserved on page load');
        <?php else: ?>
            console.log('No active session found');
        <?php endif; ?>
        
        // Set up periodic session state preservation to combat any interference
        setInterval(function() {
            <?php if (isset($_SESSION['class_start_time']) && !empty($_SESSION['class_start_time'])): ?>
                if (typeof preserveSessionState === 'function') {
                    preserveSessionState();
                }
            <?php endif; ?>
        }, 5000); // Every 5 seconds
    });
</script>

</body>
</html>