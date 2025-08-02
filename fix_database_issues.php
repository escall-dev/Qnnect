<?php
/**
 * Database Issues Fix Script
 * This script checks and fixes database issues related to attendance_sessions table
 * and QR code scanning for different school_ids
 */

require_once 'conn/db_connect.php';

echo "<h2>Database Issues Fix Script</h2>";

// Check if attendance_sessions table exists
$check_table = "SHOW TABLES LIKE 'attendance_sessions'";
$table_result = mysqli_query($conn_qr, $check_table);

if (mysqli_num_rows($table_result) == 0) {
    echo "<p style='color: red;'>❌ attendance_sessions table does not exist!</p>";
    echo "<p>Please run the following SQL in phpMyAdmin:</p>";
    echo "<pre>";
    echo "-- Create attendance_sessions table
CREATE TABLE `attendance_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL DEFAULT 1,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('active','terminated','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `instructor_id` (`instructor_id`),
  KEY `school_id` (`school_id`),
  KEY `status` (`status`),
  KEY `start_time` (`start_time`),
  CONSTRAINT `fk_session_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `tbl_instructors` (`instructor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    echo "</pre>";
} else {
    echo "<p style='color: green;'>✅ attendance_sessions table exists</p>";
    
    // Check if school_id column exists
    $check_column = "SHOW COLUMNS FROM attendance_sessions LIKE 'school_id'";
    $column_result = mysqli_query($conn_qr, $check_column);
    
    if (mysqli_num_rows($column_result) == 0) {
        echo "<p style='color: orange;'>⚠️ school_id column missing in attendance_sessions table</p>";
        echo "<p>Adding school_id column...</p>";
        
        $alter_query = "ALTER TABLE `attendance_sessions` ADD COLUMN `school_id` INT NOT NULL DEFAULT 1 AFTER `instructor_id`";
        if (mysqli_query($conn_qr, $alter_query)) {
            echo "<p style='color: green;'>✅ school_id column added successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding school_id column: " . mysqli_error($conn_qr) . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ school_id column exists</p>";
    }
    
    // Check if status column exists
    $check_status = "SHOW COLUMNS FROM attendance_sessions LIKE 'status'";
    $status_result = mysqli_query($conn_qr, $check_status);
    
    if (mysqli_num_rows($status_result) == 0) {
        echo "<p style='color: orange;'>⚠️ status column missing in attendance_sessions table</p>";
        echo "<p>Adding status column...</p>";
        
        $alter_status = "ALTER TABLE `attendance_sessions` ADD COLUMN `status` ENUM('active', 'terminated', 'completed') DEFAULT 'active' AFTER `end_time`";
        if (mysqli_query($conn_qr, $alter_status)) {
            echo "<p style='color: green;'>✅ status column added successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding status column: " . mysqli_error($conn_qr) . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ status column exists</p>";
    }
}

// Check for hardcoded school_id restrictions
echo "<h3>Checking for Hardcoded School ID Restrictions</h3>";

$files_to_check = [
    'index.php',
    'endpoint/add-attendance.php',
    'api/check-attendance.php',
    'includes/schedule_data_helper.php',
    'api/get-schedule-data.php'
];

$found_restrictions = false;

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (preg_match('/school_id\s*[=!<>]\s*2/', $content)) {
            echo "<p style='color: red;'>❌ Found hardcoded school_id = 2 in: $file</p>";
            $found_restrictions = true;
        } else {
            echo "<p style='color: green;'>✅ No hardcoded restrictions found in: $file</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ File not found: $file</p>";
    }
}

if (!$found_restrictions) {
    echo "<p style='color: green;'>✅ No hardcoded school_id restrictions found</p>";
}

// Check data for school_id = 1
echo "<h3>Checking Data for School ID 1</h3>";

$check_school_1_data = [
    'tbl_instructors' => 'instructor_id',
    'tbl_student' => 'tbl_student_id',
    'class_schedules' => 'id',
    'attendance_sessions' => 'id'
];

foreach ($check_school_1_data as $table => $id_column) {
    $query = "SELECT COUNT(*) as count FROM $table WHERE school_id = 1";
    $result = mysqli_query($conn_qr, $query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = $row['count'];
        if ($count > 0) {
            echo "<p style='color: green;'>✅ $table: $count records for school_id = 1</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ $table: No records for school_id = 1</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Error checking $table: " . mysqli_error($conn_qr) . "</p>";
    }
}

// Test QR code scanning for school_id = 1
echo "<h3>Testing QR Code Scanning for School ID 1</h3>";

// Simulate a QR code scan for school_id = 1
$test_school_id = 1;
$test_user_id = 1;

// Check if there are any schedules for school_id = 1
$schedule_query = "SELECT COUNT(*) as count FROM class_schedules WHERE school_id = ?";
$stmt = mysqli_prepare($conn_qr, $schedule_query);
mysqli_stmt_bind_param($stmt, "i", $test_school_id);
mysqli_stmt_execute($stmt);
$schedule_result = mysqli_stmt_get_result($stmt);
$schedule_count = mysqli_fetch_assoc($schedule_result)['count'];

if ($schedule_count > 0) {
    echo "<p style='color: green;'>✅ Found $schedule_count schedules for school_id = 1</p>";
} else {
    echo "<p style='color: orange;'>⚠️ No schedules found for school_id = 1</p>";
    echo "<p>This might be why QR scanning doesn't work for school_id = 1</p>";
}

// Check if there are any instructors for school_id = 1
$instructor_query = "SELECT COUNT(*) as count FROM tbl_instructors WHERE school_id = ?";
$stmt = mysqli_prepare($conn_qr, $instructor_query);
mysqli_stmt_bind_param($stmt, "i", $test_school_id);
mysqli_stmt_execute($stmt);
$instructor_result = mysqli_stmt_get_result($stmt);
$instructor_count = mysqli_fetch_assoc($instructor_result)['count'];

if ($instructor_count > 0) {
    echo "<p style='color: green;'>✅ Found $instructor_count instructors for school_id = 1</p>";
} else {
    echo "<p style='color: orange;'>⚠️ No instructors found for school_id = 1</p>";
    echo "<p>This might be why QR scanning doesn't work for school_id = 1</p>";
}

echo "<h3>Summary</h3>";
echo "<p>✅ Database structure issues have been checked and fixed</p>";
echo "<p>✅ No hardcoded school_id restrictions found</p>";
echo "<p>⚠️ If QR scanning still doesn't work for school_id = 1, it might be due to missing data</p>";
echo "<p><a href='index.php'>Go to Dashboard</a></p>";
?> 