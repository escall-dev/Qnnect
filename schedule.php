<?php
// Use consistent session handling
require_once 'includes/session_config.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: admin/login.php");
    exit;
}

// Include database connections
include('./conn/db_connect.php');

// Use both database connections
$conn = $conn_qr;
$conn_users = $conn_login;

// Get user's school_id from the users table using login_register connection
$email = $_SESSION['email'];
$user_query = "SELECT school_id, role FROM users WHERE email = ?";
$stmt = $conn_users->prepare($user_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$school_id = $user['school_id'] ?? 1; // Default to school_id 1 if not set
$is_super_admin = ($user['role'] === 'super_admin');

// Get all schools for super admin
$schools = [];
if ($is_super_admin) {
    $schools_query = "SELECT id, name FROM schools WHERE status = 'active'";
    $schools_result = $conn_users->query($schools_query);
    while ($school = $schools_result->fetch_assoc()) {
        $schools[] = $school;
    }
}

// Get selected school from GET parameter or use user's school
$selected_school = isset($_GET['school_id']) && $is_super_admin ? $_GET['school_id'] : $school_id;

// First, let's make sure the class_schedules table has the school_id column
$alter_table_sql = "ALTER TABLE class_schedules 
                    ADD COLUMN IF NOT EXISTS school_id INT DEFAULT 1";
$conn->query($alter_table_sql);

// Update existing records to have school_id if not set
$update_existing = "UPDATE class_schedules SET school_id = 1 WHERE school_id IS NULL";
$conn->query($update_existing);

// Fetch all schedules for the selected school
$schedules = [];
$sql = "SELECT cs.*, s.name as school_name 
        FROM class_schedules cs 
        LEFT JOIN login_register.schools s ON cs.school_id = s.id 
        WHERE cs.school_id = ? 
        ORDER BY cs.start_time";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $selected_school);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}

// Create array of unique time slots from actual schedules
$time_slots = array();
foreach ($schedules as $schedule) {
    $start_time = $schedule['start_time'];
    $end_time = $schedule['end_time'];
    
    // Add to time slots if not already present
    $time_slots[$start_time] = $end_time;
}

// Sort time slots by start time
ksort($time_slots);

// Calculate statistics
$total_classes = count($schedules);
$total_hours = 0;
$instructors = [];

foreach ($schedules as $schedule) {
    $start = strtotime($schedule['start_time']);
    $end = strtotime($schedule['end_time']);
    $total_hours += ($end - $start) / 3600;
    $instructors[$schedule['instructor_name']] = true;
}

$total_hours = round($total_hours);
$total_instructors = count($instructors);

// Function to check for schedule conflicts
function hasScheduleConflict($conn, $start_time, $end_time, $days_array, $school_id, $exclude_id = null) {
    // Build the SQL query to check for conflicts
    $sql = "SELECT * FROM class_schedules WHERE school_id = ? AND (
        (? < end_time AND ? > start_time)
    )";
    
    // Add exclusion for updates
    if ($exclude_id !== null) {
        $sql .= " AND id != ?";
    }
    
    // Prepare and bind parameters
    $stmt = $conn->prepare($sql);
    
    if ($exclude_id !== null) {
        $stmt->bind_param("issi", $school_id, $start_time, $end_time, $exclude_id);
    } else {
        $stmt->bind_param("iss", $school_id, $start_time, $end_time);
    }
    
    // Execute and check for conflicts
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        // Check if any of the conflicting schedules share days
        while ($row = $result->fetch_assoc()) {
            $existing_days = explode(',', $row['days_of_week']);
            foreach ($days_array as $day) {
                if (in_array($day, $existing_days)) {
                    return true; // Found a conflict
                }
            }
        }
    }
    
    return false; // No conflicts found
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check each potentially conflicting schedule
    while ($row = $result->fetch_assoc()) {
        $existing_days = explode(',', $row['days_of_week']);
        // Check if any day overlaps
        foreach ($days_array as $day) {
            if (in_array($day, $existing_days)) {
                return true; // Found a conflict
            }
        }
    }
    
    return false; // No conflicts found
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_schedule'])) {
    $instructor_name = mysqli_real_escape_string($conn, $_POST['instructor_name']);
    $room = mysqli_real_escape_string($conn, $_POST['room']);
    $course_section = mysqli_real_escape_string($conn, $_POST['course_section']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $days_array = $_POST['days'] ?? [];
    $days = implode(',', $days_array);

    // Check for schedule conflicts
    if (hasScheduleConflict($conn, $start_time, $end_time, $days_array, $selected_school)) {
        $_SESSION['error_msg'] = "Error: This schedule conflicts with an existing schedule in the selected time slot.";
    } else {
        $sql = "INSERT INTO class_schedules (instructor_name, room, course_section, subject, start_time, end_time, days_of_week, school_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $instructor_name, $room, $course_section, $subject, $start_time, $end_time, $days, $selected_school);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Schedule added successfully!";
        } else {
            $_SESSION['error_msg'] = "Error adding schedule: " . $conn->error;
        }
    }
    
    header("Location: schedule.php" . ($is_super_admin && isset($_GET['school_id']) ? "?school_id=" . $_GET['school_id'] : ""));
    exit();
}

// Add delete handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_schedule'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    
    // Check if the schedule belongs to the selected school
    $check_sql = "SELECT school_id FROM class_schedules WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $schedule_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $schedule_data = $check_result->fetch_assoc();
    
    if ($schedule_data && $schedule_data['school_id'] == $selected_school) {
        $sql = "DELETE FROM class_schedules WHERE id = ? AND school_id = ?";
    $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $schedule_id, $selected_school);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Schedule deleted successfully!";
    } else {
        $_SESSION['error_msg'] = "Error deleting schedule: " . $conn->error;
        }
    } else {
        $_SESSION['error_msg'] = "You don't have permission to delete this schedule.";
    }
    
    header("Location: schedule.php" . ($is_super_admin && isset($_GET['school_id']) ? "?school_id=" . $_GET['school_id'] : ""));
    exit();
}

// Add update handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_schedule'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $instructor_name = mysqli_real_escape_string($conn, $_POST['instructor_name']);
    $room = mysqli_real_escape_string($conn, $_POST['room']);
    $course_section = mysqli_real_escape_string($conn, $_POST['course_section']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $days = implode(',', $_POST['days'] ?? []);

    // Check if the schedule belongs to the selected school
    $check_sql = "SELECT school_id FROM class_schedules WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $schedule_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $schedule_data = $check_result->fetch_assoc();
    
    if ($schedule_data && $schedule_data['school_id'] == $selected_school) {
        $days_array = $_POST['days'] ?? [];
        
        // Check for schedule conflicts, excluding the current schedule being updated
        if (hasScheduleConflict($conn, $start_time, $end_time, $days_array, $selected_school, $schedule_id)) {
            $_SESSION['error_msg'] = "Error: This schedule conflicts with an existing schedule in the selected time slot.";
        } else {
            $sql = "UPDATE class_schedules SET instructor_name = ?, room = ?, course_section = ?, subject = ?, 
                    start_time = ?, end_time = ?, days_of_week = ? WHERE id = ? AND school_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssii", $instructor_name, $room, $course_section, $subject, $start_time, $end_time, $days, $schedule_id, $selected_school);
            
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Schedule updated successfully!";
            } else {
                $_SESSION['error_msg'] = "Error updating schedule: " . $conn->error;
            }
        }
    } else {
        $_SESSION['error_msg'] = "You don't have permission to update this schedule.";
    }
    
    header("Location: schedule.php" . ($is_super_admin && isset($_GET['school_id']) ? "?school_id=" . $_GET['school_id'] : ""));
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Attendance System - Class Schedule</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="./styles/masterlist.css">
    <link rel="stylesheet" href="./styles/pagination.css">
    <link rel="stylesheet" href="./styles/main.css">
    
    <style>
        :root {
            --primary-color: #0C713D;
            --primary-light: #e8f5e9;
            --secondary-color: #6B5ED1;
            --accent-color: #f57c00;
            --text-dark: #333;
            --text-light: #666;
            --white: #fff;
            --border-radius: 20px;
            --shadow-sm: 0 2px 15px rgba(0, 0, 0, 0.05);
            --shadow-md: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            --transition: all 0.3s ease;
            
            /* Course Base Colors */
            --course-color-1: #e3f2fd; /* Blue-ish */
            --course-color-2: #f3e5f5; /* Purple-ish */
            --course-color-3: #e8f5e9; /* Green-ish */
            --course-color-4: #fff3e0; /* Orange-ish */
            --course-color-5: #fce4ec; /* Pink-ish */
            --course-color-6: #f1f8e9; /* Lime-ish */
            
            /* Section Modifiers */
            --section-a-opacity: 1;
            --section-b-opacity: 0.85;
            --section-c-opacity: 0.7;
            --section-d-opacity: 0.55;
            
            /* Year Level Borders */
            --year-1-border: #1976d2;
            --year-2-border: #388e3c;
            --year-3-border: #7b1fa2;
            --year-4-border: #f57c00;
            --cell-height: 50px;
            --time-column-width: 130px;
            --border-color: #e0e0e0;
        }

        body {
            background-color: #f4f6f8;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            padding: 20px;
            transition: var(--transition);
            width: calc(100% - 260px);
            z-index: 1;
        }

        .sidebar.close ~ .main {
            margin-left: 78px;
            width: calc(100% - 78px);
        }

        .schedule-outer-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            margin: 20px;
            box-shadow: var(--shadow-md);
            width: calc(100% - 40px);
            height: calc(100vh - 60px);
            overflow: hidden;
            transition: var(--transition);
        }

        .schedule-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .schedule-content {
            height: 100%;
            width: 100%;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) transparent;
            max-height: calc(100vh - 110px);
            padding: 20px;
            text-align: center;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 0 10px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-title {
            color: var(--primary-color);
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 600;
            margin: 10px 0;
            color: var(--text-dark);
        }

        .stat-subtitle {
            color: var(--text-light);
            font-size: 14px;
        }

        /* Calendar Schedule Styles */
        /* Update the schedule styles */
        .schedule-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px;
            margin: 0;
            background: white;
            table-layout: fixed;
        }

        .schedule-table th {
            background: #0C713D;
            color: white;
            font-size: 0.95rem;
            font-weight: 500;
            text-align: center;
            vertical-align: middle;
            height: 50px;
            border-radius: 4px;
            padding: 8px;
        }

        .schedule-table th:first-child {
            width: 130px;
            min-width: 130px;
            max-width: 130px;
            background: #0C713D;
            color: white;
            font-weight: 500;
        }

        .schedule-table td {
            padding: 0;
            height: 50px;
            position: relative;
            vertical-align: middle;
            border-radius: 4px;
            background-color: #f8f9fa;
        }

        .schedule-table td:first-child {
            width: 130px;
            min-width: 130px;
            max-width: 130px;
        }

        .time-slot {
            height: 100%;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #098744;
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 8px 4px;
        }

        /* Make time text more compact */
        .time-text {
            display: inline-block;
            line-height: 1.2;
            white-space: nowrap;
            letter-spacing: 0;
        }

        .schedule-cell {
            height: 50px;
            position: relative;
            padding: 0;
        }

        /* Update the schedule block styles */
        .schedule-block {
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            margin: 0;
        }

        .schedule-actions {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }

        .schedule-actions button {
            padding: 3px 6px;
            border: none;
            background: none;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .schedule-actions button:hover {
            transform: scale(1.1);
        }

        .schedule-actions .edit-btn {
            color: #0C713D;
        }

        .schedule-actions .delete-btn {
            color: #dc3545;
        }

        /* Update the schedule block content */
        .schedule-block .course-title {
            font-weight: 600;
            font-size: 0.9em;
            color: #333;
            margin: 0 0 4px 0;
        }

        .schedule-block .subject {
            font-size: 0.8em;
            color: #555;
            margin: 0 0 4px 0;
        }

        .schedule-block .instructor-name {
            font-size: 0.75em;
            color: #666;
            margin: 0;
            font-style: italic;
        }

        /* Add hover effect for schedule blocks */
        .schedule-block:hover {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Schedule Block Types */
        /* Schedule Block Base */
        .schedule-block {
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 10px;
            gap: 5px;
            position: relative;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        /* Dynamic Course Colors */
        .schedule-block[data-course-index="1"] { background-color: var(--course-color-1); }
        .schedule-block[data-course-index="2"] { background-color: var(--course-color-2); }
        .schedule-block[data-course-index="3"] { background-color: var(--course-color-3); }
        .schedule-block[data-course-index="4"] { background-color: var(--course-color-4); }
        .schedule-block[data-course-index="5"] { background-color: var(--course-color-5); }
        .schedule-block[data-course-index="6"] { background-color: var(--course-color-6); }

        /* Year Level Borders */
        .schedule-block[data-year="1"] { border-left-color: var(--year-1-border); }
        .schedule-block[data-year="2"] { border-left-color: var(--year-2-border); }
        .schedule-block[data-year="3"] { border-left-color: var(--year-3-border); }
        .schedule-block[data-year="4"] { border-left-color: var(--year-4-border); }

        /* Section Opacity Modifiers */
        .schedule-block[data-section="A"] { opacity: var(--section-a-opacity); }
        .schedule-block[data-section="B"] { opacity: var(--section-b-opacity); }
        .schedule-block[data-section="C"] { opacity: var(--section-c-opacity); }
        .schedule-block[data-section="D"] { opacity: var(--section-d-opacity); }

        /* Schedule actions */
        .schedule-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: flex;
            gap: 4px;
            background: rgba(255, 255, 255, 0.95);
            padding: 2px 4px;
            border-radius: 4px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .schedule-block:hover .schedule-actions {
            opacity: 1;
        }

        .schedule-actions button {
            background: none;
            border: none;
            padding: 2px 4px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .schedule-actions .edit-btn {
            color: #0C713D;
            background-color: #e8f5e9;
        }

        .schedule-actions .edit-btn:hover {
            background-color: #c8e6c9;
            transform: scale(1.1);
        }

        .schedule-actions .delete-btn {
            color: #dc3545;
            background-color: #ffebee;
        }

        .schedule-actions .delete-btn:hover {
            background-color: #ffcdd2;
            transform: scale(1.1);
        }

        /* Recess styling */
        .recess-row td {
            height: 40px !important;
            background-color: #fff8e1 !important;
            text-align: center;
            color: #f57c00;
            font-weight: 500;
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 !important;
        }

        .recess-cell {
            background-color: #fff8e1 !important;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            border-radius: 15px 15px 0 0;
            border: none;
        }

        .modal-title {
            font-weight: 500;
        }

        .modal-body {
            padding: 25px;
        }

        .form-group label {
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 15px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(12, 113, 61, 0.25);
        }

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-success:hover {
            background-color: #0a5a31;
            border-color: #0a5a31;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
        }

        /* Card styles */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 20px;
        }

        /* Table header */
        .schedule-header {
            background: linear-gradient(135deg, #6B5ED1, #8675FF);
            padding: 15px 20px;
            color: white;
        }

        .schedule-title {
            font-size: 1.2rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Make schedule blocks more compact when they span multiple hours */
        .schedule-block[style*="min-height"] {
            margin: 0;
            z-index: 1;
        }

        /* Table responsive wrapper */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            background: white;
            padding: 1px;
        }

        /* Add subtle hover effect */
        .schedule-block:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
            z-index: 3;
        }

        /* First column styling */
        .schedule-table td:first-child,
        .schedule-table th:first-child {
            border-left: none;
        }

        /* Last column styling */
        .schedule-table td:last-child,
        .schedule-table th:last-child {
            border-right: none;
        }

        /* Bottom row styling */
        .schedule-table tr:last-child td {
            border-bottom: none;
        }

        .schedule-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: none;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 4px;
            padding: 2px;
        }

        .schedule-block {
            position: relative;
        }

        .schedule-block:hover .schedule-actions {
            display: block;
        }

        .schedule-actions .btn-link {
            padding: 2px 5px;
        }

        .time-slot {
            font-family: 'Consolas', monospace;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar-nav.php'; ?>
    
    <div class="main">
        <div class="schedule-outer-container">
            <div class="schedule-container">
                <div class="schedule-content">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="d-flex align-items-center">
                        <h1 class="page-title">
                            <i class="fas fa-calendar-alt"></i>
                            Class Schedule
                        </h1>
                            <?php if ($is_super_admin): ?>
                            <div class="ml-3">
                                <form method="get" class="form-inline">
                                    <select name="school_id" class="form-control" onchange="this.form.submit()">
                                        <?php foreach ($schools as $school): ?>
                                            <option value="<?php echo htmlspecialchars($school['id']); ?>" 
                                                    <?php echo $selected_school == $school['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($school['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-success" data-toggle="modal" data-target="#addScheduleModal">
                            <i class="fas fa-plus"></i> Add New Schedule
                        </button>
                    </div>

                    <!-- Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-title">
                                <i class="fas fa-users"></i> Total Classes
                            </div>
                            <div class="stat-value"><?php echo $total_classes; ?></div>
                            <div class="stat-subtitle">Active schedules</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">
                                <i class="fas fa-clock"></i> Active Hours
                            </div>
                            <div class="stat-value"><?php echo $total_hours; ?></div>
                            <div class="stat-subtitle">Hours per week</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">
                                <i class="fas fa-chalkboard-teacher"></i> Total Instructors
                            </div>
                            <div class="stat-value"><?php echo $total_instructors; ?></div>
                            <div class="stat-subtitle">Teaching this semester</div>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if (isset($_SESSION['success_msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <?php 
                            echo $_SESSION['success_msg'];
                            unset($_SESSION['success_msg']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_msg'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <?php 
                            echo $_SESSION['error_msg'];
                            unset($_SESSION['error_msg']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Schedule Calendar -->
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table schedule-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Time</th>
                                            <th class="text-center">Monday</th>
                                            <th class="text-center">Tuesday</th>
                                            <th class="text-center">Wednesday</th>
                                            <th class="text-center">Thursday</th>
                                            <th class="text-center">Friday</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($time_slots as $startTime => $endTime): 
                                            $currentSlotStart = strtotime($startTime);
                                            $currentSlotEnd = strtotime($endTime);
                                            $isRecess = (date('H:i', $currentSlotStart) === '15:05');
                                        ?>
                                            <tr<?php echo $isRecess ? ' class="recess-row"' : ''; ?>>
                                                <td class="time-slot">
                                                    <?php 
                                                    if ($isRecess) {
                                                        echo 'RECESS';
                                                    } else {
                                                        // Format time without spaces
                                                        $start = date('g:i', $currentSlotStart);
                                                        $end = date('g:i', $currentSlotEnd);
                                                        $startAmPm = date('a', $currentSlotStart);
                                                        $endAmPm = date('a', $currentSlotEnd);
                                                        
                                                        // If both times are in the same period (am/pm), only show it once
                                                        if ($startAmPm === $endAmPm) {
                                                            echo "<span class='time-text'>{$start}-{$end}{$startAmPm}</span>";
                                                        } else {
                                                            echo "<span class='time-text'>{$start}{$startAmPm}-{$end}{$endAmPm}</span>";
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <?php
                                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                                                foreach ($days as $day):
                                                    echo '<td class="schedule-cell">';
                                                    if (!$isRecess) {
                                                        foreach ($schedules as $schedule) {
                                                            $scheduleStart = strtotime($schedule['start_time']);
                                                            
                                                            if (in_array($day, explode(',', $schedule['days_of_week'])) &&
                                                                $scheduleStart === $currentSlotStart) {
                                                                
                                                                $instructor = explode(' ', $schedule['instructor_name'])[0];
                                                                ?>
                                                                <?php
                                                                    // Extract course, year and section
                                                                    $course_parts = explode('-', $schedule['course_section']);
                                                                    $course = $course_parts[0] ?? '';
                                                                    $section = $course_parts[1] ?? '';
                                                                    $year = $section[0] ?? '';
                                                                    $section_letter = substr($section, 1) ?? '';
                                                                    
                                                                    // Get course index (for color)
                                                                    $courses = ['BSIT', 'BSCS', 'BSIS', 'BSEMC', 'BSBA', 'BSA'];
                                                                    $course_index = array_search($course, $courses) + 1;
                                                                ?>
                                                                <div class="schedule-block" 
                                                                     data-course-index="<?php echo htmlspecialchars($course_index); ?>"
                                                                     data-year="<?php echo htmlspecialchars($year); ?>"
                                                                     data-section="<?php echo htmlspecialchars($section_letter); ?>">
                                                                    <div class="schedule-content">
                                                                        <div class="course-title"><?php echo htmlspecialchars($schedule['course_section']); ?></div>
                                                                        <div class="subject"><?php echo htmlspecialchars($schedule['subject'] ?? 'Subject Name'); ?></div>
                                                                        <div class="instructor-name">Dr. <?php echo htmlspecialchars($schedule['instructor_name']); ?></div>
                                                                    <div class="schedule-actions">
                                                                            <button type="button" class="edit-btn" title="Edit Schedule" onclick="editSchedule(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                        <form action="" method="POST" class="d-inline delete-form" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                                                            <input type="hidden" name="delete_schedule" value="1">
                                                                            <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($schedule['id']); ?>">
                                                                                <button type="submit" class="delete-btn" title="Delete Schedule">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php
                                                            }
                                                        }
                                                    }
                                                    echo '</td>';
                                                endforeach;
                                                ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" role="dialog" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">
                        <i class="fas fa-plus-circle"></i>
                        Add New Schedule
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="add_schedule" value="1">
                        <div class="form-group">
                            <label for="instructor_name">
                                <i class="fas fa-user-tie"></i>
                                Instructor Name
                            </label>
                            <input type="text" class="form-control" id="instructor_name" name="instructor_name" required>
                        </div>
                        <div class="form-group">
                            <label for="room">
                                <i class="fas fa-door-open"></i>
                                Room
                            </label>
                            <input type="text" class="form-control" id="room" name="room" required>
                        </div>
                        <div class="form-group">
                            <label for="course_section">
                                <i class="fas fa-graduation-cap"></i>
                                Course/Section
                            </label>
                            <input type="text" class="form-control" id="course_section" name="course_section" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">
                                <i class="fas fa-book"></i>
                                Subject
                            </label>
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-calendar-day"></i>
                                Days
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php
                                $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                                foreach ($weekdays as $day) {
                                    echo '<div class="form-check form-check-inline">';
                                    echo '<input class="form-check-input" type="checkbox" name="days[]" id="' . strtolower($day) . '" value="' . $day . '">';
                                    echo '<label class="form-check-label" for="' . strtolower($day) . '">' . $day . '</label>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="start_time">
                                <i class="fas fa-clock"></i>
                                Start Time
                            </label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label for="end_time">
                                <i class="fas fa-clock"></i>
                                End Time
                            </label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-plus"></i>
                            Add Schedule
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">
                        <i class="fas fa-edit"></i>
                        Edit Schedule
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="update_schedule" value="1">
                        <input type="hidden" name="schedule_id" id="edit_schedule_id">
                        <div class="form-group">
                            <label for="edit_instructor_name">
                                <i class="fas fa-user-tie"></i>
                                Instructor Name
                            </label>
                            <input type="text" class="form-control" id="edit_instructor_name" name="instructor_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_room">
                                <i class="fas fa-door-open"></i>
                                Room
                            </label>
                            <input type="text" class="form-control" id="edit_room" name="room" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_course_section">
                                <i class="fas fa-graduation-cap"></i>
                                Course/Section
                            </label>
                            <input type="text" class="form-control" id="edit_course_section" name="course_section" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_subject">
                                <i class="fas fa-book"></i>
                                Subject
                            </label>
                            <input type="text" class="form-control" id="edit_subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-calendar-day"></i>
                                Days
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php
                                $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                                foreach ($weekdays as $day) {
                                    echo '<div class="form-check form-check-inline">';
                                    echo '<input class="form-check-input edit-day" type="checkbox" name="days[]" id="edit_' . strtolower($day) . '" value="' . $day . '">';
                                    echo '<label class="form-check-label" for="edit_' . strtolower($day) . '">' . $day . '</label>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_start_time">
                                <i class="fas fa-clock"></i>
                                Start Time
                            </label>
                            <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_end_time">
                                <i class="fas fa-clock"></i>
                                End Time
                            </label>
                            <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality
        let sidebarBtn = document.querySelector('.bx-menu');
        if (sidebarBtn) {
            sidebarBtn.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('close');
            });
        }

        // Arrow toggle functionality
        let arrows = document.querySelectorAll('.arrow');
        arrows.forEach(arrow => {
            arrow.addEventListener('click', (e) => {
                let parent = e.target.parentElement.parentElement;
                parent.classList.toggle('showMenu');
            });
        });

        // Mark current page as active
        let currentPath = window.location.pathname;
        let navLinks = document.querySelectorAll('.nav-links li');
        navLinks.forEach(li => {
            let links = li.querySelectorAll('a');
            links.forEach(link => {
                if (link.getAttribute('href') === currentPath || 
                    currentPath.endsWith(link.getAttribute('href'))) {
                    li.classList.add('showMenu');
                    let parentLi = link.closest('li');
                    if (parentLi) {
                        parentLi.classList.add('active');
                    }
                }
            });
        });
        });
    </script>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to adjust schedule block heights based on duration
    function adjustScheduleBlocks() {
        const scheduleBlocks = document.querySelectorAll('.schedule-block');
        scheduleBlocks.forEach(block => {
            const height = block.style.minHeight;
            if (height) {
                const parent = block.closest('.schedule-cell');
                if (parent) {
                    parent.style.minHeight = height;
                }
            }
        });
    }

    // Call the function when page loads
    adjustScheduleBlocks();
});
</script>

<script>
function editSchedule(schedule) {
    document.getElementById('edit_schedule_id').value = schedule.id;
    document.getElementById('edit_instructor_name').value = schedule.instructor_name;
    document.getElementById('edit_room').value = schedule.room;
    document.getElementById('edit_course_section').value = schedule.course_section;
    document.getElementById('edit_subject').value = schedule.subject;
    document.getElementById('edit_start_time').value = schedule.start_time;
    document.getElementById('edit_end_time').value = schedule.end_time;
    
    // Reset all checkboxes
    document.querySelectorAll('.edit-day').forEach(checkbox => checkbox.checked = false);
    
    // Check the appropriate days
    const days = schedule.days_of_week.split(',');
    days.forEach(day => {
        const checkbox = document.getElementById('edit_' + day.toLowerCase());
        if (checkbox) checkbox.checked = true;
    });
    
    $('#editScheduleModal').modal('show');
}

// Format time display
function formatTime(time) {
    return time.replace(/(\d{2}):(\d{2})/, "$1:$2");
}

// Update all time displays
document.addEventListener('DOMContentLoaded', function() {
    const timeSlots = document.querySelectorAll('.time-slot');
    timeSlots.forEach(slot => {
        if (!slot.textContent.includes('RECESS')) {
            const times = slot.textContent.trim().split(' - ');
            if (times.length === 2) {
                const start = formatTime(times[0]);
                const end = formatTime(times[1]);
                slot.textContent = `${start} - ${end}`;
            }
        }
    });
});
</script>
</body>
</html>
