<?php
// Debug script to check data for different school IDs
session_start();
require_once('conn/db_connect.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>School Data Debug</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>🔍 School Data Debug</h2>
        
        <div class='row'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Test Different School IDs</h5>
                    </div>
                    <div class='card-body'>
                        <form method='post'>
                            <div class='form-group mb-3'>
                                <label>Select School ID to Debug:</label>
                                <select name='debug_school_id' class='form-control'>
                                    <option value='1'>School ID 1</option>
                                    <option value='2'>School ID 2</option>
                                    <option value='3'>School ID 3</option>
                                    <option value='4'>School ID 4</option>
                                    <option value='5'>School ID 5</option>
                                </select>
                            </div>
                            <button type='submit' class='btn btn-primary'>Debug School Data</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5>Current Session</h5>
                    </div>
                    <div class='card-body'>
                        <p><strong>Current School ID:</strong> " . ($_SESSION['school_id'] ?? 'Not set') . "</p>
                        <p><strong>Current User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>
                        <p><strong>Email:</strong> " . ($_SESSION['email'] ?? 'Not set') . "</p>
                    </div>
                </div>
            </div>
        </div>";

if ($_POST['debug_school_id']) {
    $school_id = intval($_POST['debug_school_id']);
    $_SESSION['school_id'] = $school_id;
    
    echo "<div class='card mt-4'>
        <div class='card-header'>
            <h5>Data Analysis for School ID: $school_id</h5>
        </div>
        <div class='card-body'>";
    
    // Check class_time_settings
    $class_time_query = "SELECT * FROM class_time_settings WHERE school_id = ?";
    $stmt = $conn_qr->prepare($class_time_query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $class_time_result = $stmt->get_result();
    $class_time_count = $class_time_result->num_rows;
    
    echo "<h6>📅 Class Time Settings:</h6>";
    echo "<p>Records found: <strong>$class_time_count</strong></p>";
    if ($class_time_count > 0) {
        while ($row = $class_time_result->fetch_assoc()) {
            echo "<p>• Start Time: " . $row['start_time'] . " | Updated: " . $row['updated_at'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No class time settings found for School ID $school_id</p>";
    }
    
    // Check teacher_schedules
    $teacher_schedules_query = "SELECT COUNT(*) as count FROM teacher_schedules WHERE school_id = ? AND status = 'active'";
    $stmt = $conn_qr->prepare($teacher_schedules_query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $teacher_result = $stmt->get_result();
    $teacher_count = $teacher_result->fetch_assoc()['count'];
    
    echo "<h6>👨‍🏫 Teacher Schedules:</h6>";
    echo "<p>Active schedules: <strong>$teacher_count</strong></p>";
    if ($teacher_count == 0) {
        echo "<p style='color: orange;'>⚠️ No active teacher schedules found for School ID $school_id</p>";
    }
    
    // Check tbl_student
    $student_query = "SELECT COUNT(*) as count FROM tbl_student WHERE school_id = ?";
    $stmt = $conn_qr->prepare($student_query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    $student_count = $student_result->fetch_assoc()['count'];
    
    echo "<h6>👥 Students:</h6>";
    echo "<p>Students found: <strong>$student_count</strong></p>";
    if ($student_count == 0) {
        echo "<p style='color: orange;'>⚠️ No students found for School ID $school_id</p>";
    }
    
    // Check attendance_sessions
    $attendance_query = "SELECT COUNT(*) as count FROM attendance_sessions WHERE school_id = ?";
    $stmt = $conn_qr->prepare($attendance_query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $attendance_result = $stmt->get_result();
    $attendance_count = $attendance_result->fetch_assoc()['count'];
    
    echo "<h6>📊 Attendance Sessions:</h6>";
    echo "<p>Attendance sessions: <strong>$attendance_count</strong></p>";
    if ($attendance_count == 0) {
        echo "<p style='color: orange;'>⚠️ No attendance sessions found for School ID $school_id</p>";
    }
    
    // Check tbl_instructors
    $instructor_query = "SELECT COUNT(*) as count FROM tbl_instructors WHERE school_id = ?";
    $stmt = $conn_qr->prepare($instructor_query);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $instructor_result = $stmt->get_result();
    $instructor_count = $instructor_result->fetch_assoc()['count'];
    
    echo "<h6>👨‍💼 Instructors:</h6>";
    echo "<p>Instructors found: <strong>$instructor_count</strong></p>";
    if ($instructor_count == 0) {
        echo "<p style='color: orange;'>⚠️ No instructors found for School ID $school_id</p>";
    }
    
    echo "<h6>🔍 Summary:</h6>";
    if ($class_time_count > 0 && $teacher_count > 0) {
        echo "<p style='color: green;'>✅ School ID $school_id has data and should show active sessions</p>";
    } else {
        echo "<p style='color: red;'>❌ School ID $school_id is missing data:</p>";
        echo "<ul>";
        if ($class_time_count == 0) echo "<li>No class time settings</li>";
        if ($teacher_count == 0) echo "<li>No teacher schedules</li>";
        if ($student_count == 0) echo "<li>No students</li>";
        if ($instructor_count == 0) echo "<li>No instructors</li>";
        echo "</ul>";
    }
    
    echo "</div></div>";
}

echo "<div class='card mt-4'>
    <div class='card-header'>
        <h5>Quick Actions</h5>
    </div>
    <div class='card-body'>
        <a href='index.php' class='btn btn-success me-2'>
            <i class='fas fa-home'></i> Go to Main Page
        </a>
        <a href='test_multi_tenant_features.php' class='btn btn-info me-2'>
            <i class='fas fa-cogs'></i> Test Multi-Tenant Features
        </a>
        <a href='debug_terminate_button.php' class='btn btn-warning'>
            <i class='fas fa-bug'></i> Debug Terminate Button
        </a>
    </div>
</div>

</div>
</body>
</html>";
?> 