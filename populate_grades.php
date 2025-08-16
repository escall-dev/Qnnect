<?php
// Include the database connection
require_once 'conn/db_connect.php';
include('./includes/attendance_grade_helper.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Convert MySQL connection to PDO for helper functions
$dsn = 'mysql:host=127.0.0.1;dbname=qr_attendance_db;charset=utf8mb4';
$username = 'root';
$password = '';
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Check if we have students and courses
$studentQuery = "SELECT COUNT(*) as count FROM tbl_student";
$studentResult = mysqli_query($conn_qr, $studentQuery);
$studentCount = mysqli_fetch_assoc($studentResult)['count'];

$courseQuery = "SELECT COUNT(*) as count FROM tbl_subjects";
$courseResult = mysqli_query($conn_qr, $courseQuery);
$courseCount = mysqli_fetch_assoc($courseResult)['count'];

echo "<h2>Checking Database</h2>";
echo "Students: $studentCount<br>";
echo "Courses: $courseCount<br>";

// If we have students and courses, proceed
if ($studentCount > 0 && $courseCount > 0) {
    // Get students
    $studentQuery = "SELECT tbl_student_id, student_name, course_section FROM tbl_student LIMIT 20";
    $studentResult = mysqli_query($conn_qr, $studentQuery);
    $students = [];
    while ($row = mysqli_fetch_assoc($studentResult)) {
        $students[] = $row;
    }
    
    // Get courses
    $courseQuery = "SELECT subject_id, subject_name FROM tbl_subjects LIMIT 5";
    $courseResult = mysqli_query($conn_qr, $courseQuery);
    $courses = [];
    while ($row = mysqli_fetch_assoc($courseResult)) {
        $courses[] = $row;
    }
    
    // Define terms and sections
    $terms = ['1st Semester', '2nd Semester', 'Summer'];
    $sections = ['A', 'B', 'C'];
    
    echo "<h2>Generating Sample Data</h2>";
    
    // First create some sample attendance sessions if none exist
    $sessionQuery = "SELECT COUNT(*) as count FROM attendance_sessions";
    $sessionResult = mysqli_query($conn_qr, $sessionQuery);
    $sessionCount = mysqli_fetch_assoc($sessionResult)['count'];
    
    if ($sessionCount == 0) {
        echo "Creating sample attendance sessions...<br>";
        // Create sample sessions
        foreach ($courses as $course) {
            foreach ($terms as $term) {
                foreach ($sections as $section) {
                    // Create 10 sessions per course/term/section
                    for ($i = 0; $i < 10; $i++) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $startTime = "$date 08:00:00";
                        $endTime = "$date 09:30:00";
                        
                        $sessionQuery = "INSERT INTO attendance_sessions 
                                      (instructor_id, course_id, term, section, start_time, end_time) 
                                      VALUES (1, ?, ?, ?, ?, ?)";
                        $sessionStmt = $conn_qr->prepare($sessionQuery);
                        $sessionStmt->bind_param("issss", $course['subject_id'], $term, $section, $startTime, $endTime);
                        $sessionStmt->execute();
                    }
                }
            }
        }
        
        echo "Created sample sessions.<br>";
    } else {
        echo "Found $sessionCount existing attendance sessions.<br>";
    }
    
    // Create sample attendance logs if none exist
    $logQuery = "SELECT COUNT(*) as count FROM attendance_logs";
    $logResult = mysqli_query($conn_qr, $logQuery);
    $logCount = mysqli_fetch_assoc($logResult)['count'];
    
    if ($logCount == 0) {
        echo "Creating sample attendance logs...<br>";
        
        // Get sessions
        $sessionQuery = "SELECT id, course_id, term, section FROM attendance_sessions";
        $sessionResult = mysqli_query($conn_qr, $sessionQuery);
        $sessions = [];
        while ($row = mysqli_fetch_assoc($sessionResult)) {
            $sessions[] = $row;
        }
        
        // For each student, record attendance with different rates
        foreach ($students as $student) {
            foreach ($sessions as $session) {
                // Randomly determine if student attended (80% chance)
                if (rand(1, 100) <= 80) {
                    $scanTime = date('Y-m-d H:i:s', strtotime($session['start_time'] . ' + ' . rand(5, 20) . ' minutes'));
                    $logQuery = "INSERT INTO attendance_logs (session_id, student_id, scan_time) VALUES (?, ?, ?)";
                    $logStmt = $conn_qr->prepare($logQuery);
                    $logStmt->bind_param("iis", $session['id'], $student['tbl_student_id'], $scanTime);
                    $logStmt->execute();
                }
            }
        }
        
        echo "Created sample attendance logs.<br>";
    } else {
        echo "Found $logCount existing attendance logs.<br>";
    }
    
    // Calculate and update grades for each student
    echo "<h2>Calculating and Updating Attendance Grades</h2>";
    
    $updatedCount = 0;
    foreach ($students as $student) {
        foreach ($courses as $course) {
            foreach ($terms as $term) {
                foreach ($sections as $section) {
                    // Check if student belongs to this section based on course_section field
                    $courseParts = explode('-', $student['course_section']);
                    $studentSection = isset($courseParts[1]) ? $courseParts[1] : '';
                    
                    if ($studentSection == $section) {
                        // Calculate grade
                        $result = calculateAndUpdateAttendanceGrade(
                            $pdo,
                            $student['tbl_student_id'],
                            $course['subject_id'],
                            $term,
                            $section
                        );
                        
                        if ($result['success']) {
                            $updatedCount++;
                            echo "Updated grade for student {$student['student_name']} in {$course['subject_name']}, $term, Section $section. ";
                            echo "Rate: {$result['data']['attendance_rate']}%, Grade: {$result['data']['attendance_grade']}<br>";
                        }
                    }
                }
            }
        }
    }
    
    echo "<h2>Summary</h2>";
    echo "Updated $updatedCount attendance grades.<br>";
    echo "<a href='attendance-grades.php'>View Attendance Grades</a>";
} else {
    echo "<h2>Error</h2>";
    echo "No students or courses found in the database. Please add some first.";
}
?> 