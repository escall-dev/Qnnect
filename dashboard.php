<?php
require_once 'includes/asset_helper.php';
require_once 'includes/session_config.php'; // Handle session configuration
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Session is already started by session_config.php

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: admin/login.php");
    exit();
}

// Include database connections
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

// Add session check for user isolation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo "<script>alert('User session expired or not logged in. Please log in again.'); window.location.href = 'login.php';</script>";
    exit();
}
$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];

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

// Total Students
$total_students_query = "SELECT COUNT(DISTINCT tbl_student_id) as total_students FROM tbl_student WHERE school_id = ? AND user_id = ?";
$total_students_stmt = $conn_qr->prepare($total_students_query);
$total_students_stmt->bind_param("ii", $school_id, $user_id);
$total_students_stmt->execute();
$total_students_result = $total_students_stmt->get_result();
$total_students = ($total_students_result && $total_students_result->num_rows > 0) ? $total_students_result->fetch_assoc()['total_students'] : 0;

// Total Attendance Records
$total_attendance_query = "SELECT COUNT(*) as total_attendance FROM tbl_attendance WHERE school_id = ? AND user_id = ?";
$total_attendance_stmt = $conn_qr->prepare($total_attendance_query);
$total_attendance_stmt->bind_param("ii", $school_id, $user_id);
$total_attendance_stmt->execute();
$total_attendance_result = $total_attendance_stmt->get_result();
$total_attendance = ($total_attendance_result && $total_attendance_result->num_rows > 0) ? $total_attendance_result->fetch_assoc()['total_attendance'] : 0;

// Total Courses/Subjects (NO user_id filter)
$total_subjects_query = "SELECT COUNT(*) as total_subjects FROM tbl_subjects WHERE school_id = ?";
$total_subjects_stmt = $conn_qr->prepare($total_subjects_query);
$total_subjects_stmt->bind_param("i", $school_id);
$total_subjects_stmt->execute();
$total_subjects_result = $total_subjects_stmt->get_result();
$total_subjects = ($total_subjects_result && $total_subjects_result->num_rows > 0) ? $total_subjects_result->fetch_assoc()['total_subjects'] : 0;

// Total Instructors
$total_instructors_query = "SELECT COUNT(*) as total_instructors FROM tbl_instructors WHERE school_id = ? AND user_id = ?";
$total_instructors_stmt = $conn_qr->prepare($total_instructors_query);
$total_instructors_stmt->bind_param("ii", $school_id, $user_id);
$total_instructors_stmt->execute();
$total_instructors_result = $total_instructors_stmt->get_result();
$total_instructors = ($total_instructors_result && $total_instructors_result->num_rows > 0) ? $total_instructors_result->fetch_assoc()['total_instructors'] : 0;

// Recent Attendance (last 5) - filtered by user
$recent_attendance_query = "
    SELECT a.*, s.student_name, i.instructor_name, sub.subject_name 
    FROM tbl_attendance a
    LEFT JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id
    LEFT JOIN tbl_instructors i ON a.instructor_id = i.instructor_id
    LEFT JOIN tbl_subjects sub ON a.subject_id = sub.subject_id
    WHERE a.school_id = ? AND a.user_id = ?
    ORDER BY a.time_in DESC
    LIMIT 5
";
$recent_attendance_stmt = $conn_qr->prepare($recent_attendance_query);
$recent_attendance_stmt->bind_param("ii", $school_id, $user_id);
$recent_attendance_stmt->execute();
$recent_attendance_result = $recent_attendance_stmt->get_result();
$recent_attendance = [];
if ($recent_attendance_result && $recent_attendance_result->num_rows > 0) {
    while ($row = $recent_attendance_result->fetch_assoc()) {
        $recent_attendance[] = $row;
    }
}

// Today's Attendance Count - filtered by user
$today = date('Y-m-d');
$today_attendance_query = "SELECT COUNT(*) as today_count FROM tbl_attendance WHERE school_id = ? AND user_id = ? AND DATE(time_in) = ?";
$today_attendance_stmt = $conn_qr->prepare($today_attendance_query);
$today_attendance_stmt->bind_param("iis", $school_id, $user_id, $today);
$today_attendance_stmt->execute();
$today_attendance_result = $today_attendance_stmt->get_result();
$today_attendance = ($today_attendance_result && $today_attendance_result->num_rows > 0) ? $today_attendance_result->fetch_assoc()['today_count'] : 0;

// Get attendance data per course for charts - filtered by user
$attendance_by_course_query = "
    SELECT sub.subject_name, COUNT(*) as attendance_count
    FROM tbl_attendance a
    LEFT JOIN tbl_subjects sub ON a.subject_id = sub.subject_id
    WHERE a.school_id = ? AND a.user_id = ?
    GROUP BY a.subject_id
    ORDER BY attendance_count DESC
    LIMIT 5
";
$attendance_by_course_stmt = $conn_qr->prepare($attendance_by_course_query);
$attendance_by_course_stmt->bind_param("ii", $school_id, $user_id);
$attendance_by_course_stmt->execute();
$attendance_by_course_result = $attendance_by_course_stmt->get_result();
$course_labels = [];
$course_data = [];
$attendanceData = [];

if ($attendance_by_course_result && $attendance_by_course_result->num_rows > 0) {
    while ($row = $attendance_by_course_result->fetch_assoc()) {
        $course_labels[] = $row['subject_name'] ?? 'Unknown';
        $course_data[] = (int)$row['attendance_count'];
        $attendanceData[] = [
            'course_section' => $row['subject_name'] ?? 'Unknown',
            'attendance_count' => (int)$row['attendance_count']
        ];
    }
}

// Get top 5 students with most attendance - filtered by user
$top_students_query = "
    SELECT s.student_name, s.tbl_student_id, COUNT(*) as attendance_count
    FROM tbl_attendance a
    JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id
    WHERE a.school_id = ? AND a.user_id = ? AND s.school_id = ? AND s.user_id = ?
    GROUP BY a.tbl_student_id
    ORDER BY attendance_count DESC
    LIMIT 5
";
$top_students_stmt = $conn_qr->prepare($top_students_query);
$top_students_stmt->bind_param("iiii", $school_id, $user_id, $school_id, $user_id);
$top_students_stmt->execute();
$top_students_result = $top_students_stmt->get_result();
$top_students = [];
if ($top_students_result && $top_students_result->num_rows > 0) {
    while ($row = $top_students_result->fetch_assoc()) {
        $top_students[] = $row;
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - QR Code Attendance System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="./functions/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Main content styles */
        .main {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease;
            width: calc(100% - 260px);
            background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5)), url('./admin/image/bg.jpg');
            background-size: cover;
            background-attachment: fixed;
        }

        /* When sidebar is closed */
        .sidebar.close ~ .main {
            margin-left: 78px;
            width: calc(100% - 78px);
        }

        /* Dashboard outer container */
        .dashboard-outer-container {
            min-height: 90vh;
            width: 95%;
            margin: 0 auto;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            padding: 25px;
            overflow-x: hidden;
        }

        /* Dashboard inner container */
        .dashboard-container {
            width: 100%;
            background-color: white;
            border-radius: 20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            padding: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            max-height: calc(100% - 20px);
            scrollbar-width: thin;
            scrollbar-color: #098744 transparent;
        }

        /* Dashboard title */
        .title {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
            border-radius: 20px 20px 0 0;
        }

        .title h4 {
            margin: 0;
            color: #098744;
            font-weight: 600;
        }

        /* Stats card styles */
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            flex: 1;
            min-width: 180px;
            background: white;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background-color: #098744;
        }

        .stat-card h5 {
            color: #098744;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
        }

        .stat-card i {
            font-size: 20px;
            color: #098744;
            margin-bottom: 5px;
        }

        /* Quick access cards */
        .quick-access {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }

        .access-card {
            flex: 1;
            min-width: 150px;
            background: white;
            border-radius: 15px;
            padding: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none !important;
            color: #333;
        }

        .access-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
            color: #333;
        }

        .access-card i {
            font-size: 22px;
            color: #098744;
            margin-bottom: 5px;
            display: block;
        }

        .access-card h6 {
            margin-bottom: 3px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .access-card p {
            margin-bottom: 0;
            font-size: 0.8rem;
            color: #666;
        }

        /* Chart card and table card */
        .chart-card, .table-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            height: 250px;
            position: relative;
        }

        /* Welcome message */
        .welcome-message {
            background: linear-gradient(to right, #098744, #076633);
            color: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .welcome-message h4 {
            margin-bottom: 5px;
            font-size: 1.3rem;
        }

        .welcome-message p {
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        /* Calendar styles */
        .mini-calendar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 25px;
            height: 100%;
            display: flex;
            flex-direction: column;
            max-height: 380px; /* Reduce maximum height */
            font-family: 'Poppins', sans-serif;
            margin-left: 10px; /* Add left margin to shift right */
            width: 500px; /* Increased width from 250px */
        }

        .calendar-header {
            background: #098744;
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .calendar-body {
            padding: 10px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0;
            flex-grow: 1;
            margin-top: 5px;
            width: 100%;
        }
        
        .calendar-day {
            text-align: center;
            padding: 8px 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            font-size: 1rem;
            transition: all 0.2s ease;
            margin: 3px auto;
        }
        
        .calendar-day.current {
            background-color: #098744;
            color: white;
            font-weight: bold;
        }
        
        .calendar-day.inactive {
            color: #ccc;
        }
        
        .calendar-day:not(.inactive):not(.current):hover {
            background-color: rgba(9, 135, 68, 0.1);
            cursor: pointer;
        }

        /* Table styling */
        .table {
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .table thead th {
            background-color: #098744;
            color: white;
            border: none;
            padding: 10px;
            font-size: 0.85rem;
        }

        .table tbody tr:hover {
            background-color: rgba(9, 135, 68, 0.05);
        }

        .table tbody td {
            padding: 8px 10px;
            vertical-align: middle;
        }

        .section-title {
            color: #098744;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
        }

        .section-title i {
            margin-right: 10px;
        }

        /* Scrollbar styling for webkit browsers */
        .dashboard-container::-webkit-scrollbar {
            width: 8px;
        }

        .dashboard-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .dashboard-container::-webkit-scrollbar-thumb {
            background-color: #098744;
            border-radius: 4px;
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 15px;
            }
            
            .sidebar.close ~ .main {
                margin-left: 0;
                width: 100%;
            }
            
            .dashboard-outer-container {
                width: 100%;
                min-height: calc(100vh - 30px);
                padding: 15px;
            }
            
            .dashboard-container {
                padding: 10px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Custom Alert Box -->
    <div id="customAlert" class="custom-alert"></div>

    <?php include('./components/sidebar-nav.php'); ?>

    <div class="main" id="main">
        <div class="dashboard-outer-container">
            <div class="dashboard-container">
                <div class="title">
                    <h4><i class="fas fa-tachometer-alt"></i> Dashboard</h4>
                </div>
                
                <!-- Welcome Message -->
                <div class="welcome-message">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4><i class="fas fa-hand-wave"></i> Welcome, <?php echo htmlspecialchars($userData['username'] ?? $_SESSION['username'] ?? 'User'); ?>!</h4>
                            <p>Here's an overview of the QR Code Attendance System.</p>
                        </div>
                       
                    </div>
                </div>

                <!-- Clock Section -->
                <div class="text-center mb-4">
                    <div id="live-clock" style="font-size: 2rem; font-weight: bold; color: #098744;"></div>
                </div>

                <div class="row">
                    <!-- Stats Cards and Quick Access Section -->
                    <div class="col-md-9">
                        <h5 class="section-title"><i class="fas fa-chart-line"></i> Key Statistics</h5>
                        <div class="stats-container">
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <h5>Total Students</h5>
                                <div class="value"><?php echo $total_students; ?></div>
                                <small>Registered</small>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-clipboard-check"></i>
                                <h5>Total Attendance</h5>
                                <div class="value"><?php echo $total_attendance; ?></div>
                                <small>All time</small>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-calendar-check"></i>
                                <h5>Today's Attendance</h5>
                                <div class="value"><?php echo $today_attendance; ?></div>
                                <small><?php echo date('F d'); ?></small>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <h5>Course Instructors</h5>
                                <div class="value"><?php echo $total_instructors; ?></div>
                                <small>Teaching staff</small>
                            </div>
                        </div>
                        
                        <!-- Quick Access -->
                        <h5 class="section-title mt-4"><i class="fas fa-bolt"></i> Quick Access</h5>
                        <div class="quick-access">
                            <a href="index.php" class="access-card">
                                <i class="fas fa-qrcode"></i>
                                <h6>QR Scanner</h6>
                                <p>Scan QR Codes</p>
                            </a>
                            <a href="settings.php" class="access-card">
                                <i class="fas fa-cog"></i>
                                <h6>Settings</h6>
                                <p>System settings</p>
                            </a>
                            <a href="analytics.php" class="access-card">
                                <i class="fas fa-chart-pie"></i>
                                <h6>Analytics</h6>
                                <p>View analytics</p>
                            </a>
                            <a href="leaderboard.php" class="access-card">
                                <i class="fas fa-trophy"></i>
                                <h6>Leaderboard</h6>
                                <p>Ranking</p>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Mini Calendar -->
                    <div class="col-md-3 d-flex align-items-start justify-content-end">
                        <div class="mini-calendar">
                            <div class="calendar-header">
                                <!-- Month and year will be inserted by JavaScript -->
                            </div>
                            <div class="calendar-body" id="mini-calendar">
                                <!-- Calendar will be generated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Tables Section -->
                <div class="row mt-4">
                    <!-- Line Chart Section -->
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <h5 class="text-center mb-3" style="color: #098744;">
                                <i class="fas fa-chart-line"></i>
                                Attendance by Course
                            </h5>
                            <div class="chart-container">
                                <canvas id="lineChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Students Section -->
                    <div class="col-lg-6">
                        <div class="table-card">
                            <h5 class="text-center mb-3" style="color: #098744;">
                                <i class="fas fa-star"></i>
                                Top Students by Attendance
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Student Name</th>
                                            <th>ID</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($top_students)): ?>
                                            <?php foreach ($top_students as $key => $student): ?>
                                                <tr>
                                                    <td><?php echo $key + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['tbl_student_id']); ?></td>
                                                    <td><?php echo $student['attendance_count']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No student data available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Attendance -->
                <div class="table-card">
                    <h5 class="text-center mb-3" style="color: #098744;">
                        <i class="fas fa-history"></i>
                        Recent Attendance Records
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Instructor</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_attendance)): ?>
                                    <?php foreach ($recent_attendance as $attendance): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($attendance['student_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($attendance['subject_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($attendance['instructor_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo date('M d, h:i A', strtotime($attendance['time_in'])); ?></td>
                                            <td>
                                                <?php 
                                                    $status = $attendance['status'] ?? 'Present';
                                                    $statusClass = 'success';
                                                    $statusIcon = 'check-circle';
                                                    
                                                    if (strtolower($status) === 'late') {
                                                        $statusClass = 'warning';
                                                        $statusIcon = 'clock';
                                                    } elseif (strtolower($status) === 'absent') {
                                                        $statusClass = 'danger';
                                                        $statusIcon = 'times-circle';
                                                    }
                                                ?>
                                                <span class="badge badge-<?php echo $statusClass; ?>">
                                                    <i class="fas fa-<?php echo $statusIcon; ?>"></i> <?php echo $status; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No recent attendance records</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="index.php" class="btn btn-sm btn-success">View All Records</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="<?php echo asset_url('js/popper.min.js'); ?>"></script>
    <script src="<?php echo asset_url('js/bootstrap.min.js'); ?>"></script>

    <script>
        // Live Clock Function
        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            
            hours = hours % 12;
            hours = hours ? hours : 12; // Convert 0 to 12
            const timeString = `<i class="fas fa-clock mr-2"></i>${hours}:${minutes}:${seconds} <span style="font-size: 1.5rem;">${ampm}</span>`;
            
            document.getElementById('live-clock').innerHTML = timeString;
            setTimeout(updateClock, 1000);
        }
        
        // Generate mini calendar
        function generateCalendar() {
            // Get current date information
            const now = new Date();
            const currentMonth = now.getMonth();
            const currentYear = now.getFullYear();
            const currentDay = now.getDate();
            
            // Month names array
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
            
            // Update header with current month and year
            document.querySelector('.calendar-header').textContent = `${monthNames[currentMonth]} ${currentYear}`;
            
            // Get first day of the month (0 = Sunday, 1 = Monday, etc.)
            const firstDayOfWeek = new Date(currentYear, currentMonth, 1).getDay();
            
            // Get number of days in the month
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            
            // Create weekday headers
            const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
            let weekdaysHTML = '';
            weekdays.forEach(day => {
                weekdaysHTML += `<div>${day}</div>`;
            });
            
            // Create days of the month
            let daysHTML = '';
            
            // Add empty cells for days before the first day of month
            for (let i = 0; i < firstDayOfWeek; i++) {
                daysHTML += `<div class="calendar-day inactive"></div>`;
            }
            
            // Add days of the month
            for (let i = 1; i <= daysInMonth; i++) {
                const isCurrentDay = i === currentDay;
                daysHTML += `<div class="calendar-day${isCurrentDay ? ' current' : ''}">${i}</div>`;
            }
            
            // Calculate how many cells we need to complete the grid
            const totalCells = firstDayOfWeek + daysInMonth;
            const rowsNeeded = Math.ceil(totalCells / 7);
            const cellsNeeded = rowsNeeded * 7;
            const remainingCells = cellsNeeded - totalCells;
            
            // Add empty cells at the end to complete the grid
            if (remainingCells > 0 && remainingCells < 7) {
                for (let i = 0; i < remainingCells; i++) {
                    daysHTML += `<div class="calendar-day inactive"></div>`;
                }
            }
            
            // Create the complete calendar HTML
            const calendarHTML = `
                <div class="calendar-weekdays">
                    ${weekdaysHTML}
                </div>
                <div class="calendar-days">
                    ${daysHTML}
                </div>
            `;
            
            // Insert the calendar HTML into the container
            document.getElementById('mini-calendar').innerHTML = calendarHTML;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize clock
            updateClock();
            
            // Generate calendar
            generateCalendar();
            
            // Toggle sidebar functionality
            const toggleButton = document.querySelector('.sidebar-toggle');
            if (toggleButton) {
                toggleButton.onclick = toggleSidebar;
            }
        });

        // Define chart colors
        const chartColors = [
            '#098744',  // Primary green
            '#FFCE56',  // Yellow
            '#36A2EB',  // Blue
            '#FF6384',  // Red
            '#9966FF',  // Purple
        ];

        // Line Chart Implementation
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($course_labels); ?>,
                datasets: [{
                    label: 'Number of Attendances',
                    data: <?php echo json_encode($course_data); ?>,
                    backgroundColor: 'rgba(9, 135, 68, 0.2)',
                    borderColor: '#098744',
                    borderWidth: 3,
                    pointBackgroundColor: '#098744',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Toggle sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main');
            const toggleButton = document.querySelector('.sidebar-toggle');

            sidebar.classList.toggle('active');
            main.classList.toggle('active');
            toggleButton.classList.toggle('rotate');
        }
    </script>

    <!-- Session Tracking Script -->
    <script src="js/session-tracker.js"></script>
    <script>
        // Set current user for session tracking
        <?php if (isset($_SESSION['username'])): ?>
            setCurrentUser('<?php echo htmlspecialchars($_SESSION['username']); ?>');
        <?php endif; ?>
    </script>
</body>
</html> 