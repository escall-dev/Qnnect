<?php
// Direct SQL script to add attendance data

// Display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Direct SQL Attendance Data Generator</h1>";

// Database connection (without using mysqli or PDO)
$command = "mysql -u root -h localhost qr_attendance_db";
$output = [];
$return_var = 0;

echo "<p>This script will execute SQL commands directly to update your attendance database.</p>";

// Create attendance sessions if they don't exist
$create_sessions_sql = "
INSERT INTO attendance_sessions 
(instructor_id, course_id, term, section, start_time, end_time)
SELECT 1, subject_id, '2nd Semester', RIGHT(s.course_section, LENGTH(s.course_section) - LOCATE('-', s.course_section)), 
    DATE_FORMAT(NOW() - INTERVAL (id % 10) DAY, '%Y-%m-%d 08:00:00'),
    DATE_FORMAT(NOW() - INTERVAL (id % 10) DAY, '%Y-%m-%d 09:30:00')
FROM tbl_subjects 
CROSS JOIN (SELECT DISTINCT RIGHT(course_section, LENGTH(course_section) - LOCATE('-', course_section)) as section FROM tbl_student) AS sections
CROSS JOIN (SELECT id FROM tbl_student LIMIT 10) AS counter
WHERE NOT EXISTS (
    SELECT 1 FROM attendance_sessions 
    WHERE course_id = subject_id 
    AND term = '2nd Semester'
    AND section = RIGHT(s.course_section, LENGTH(s.course_section) - LOCATE('-', s.course_section))
)
LIMIT 100;
";

// Create attendance logs
$create_logs_sql = "
INSERT INTO attendance_logs (session_id, student_id, scan_time)
SELECT s.id, st.tbl_student_id, s.start_time + INTERVAL FLOOR(RAND() * 30) MINUTE
FROM attendance_sessions s
JOIN tbl_student st ON RIGHT(st.course_section, LENGTH(st.course_section) - LOCATE('-', st.course_section)) = s.section
WHERE NOT EXISTS (
    SELECT 1 FROM attendance_logs WHERE session_id = s.id AND student_id = st.tbl_student_id
)
LIMIT 1000;
";

// Update attendance grades
$update_grades_sql = "
INSERT INTO attendance_grades (student_id, course_id, term, section, attendance_rate, attendance_grade)
SELECT 
    st.tbl_student_id,
    s.course_id,
    s.term,
    s.section,
    COUNT(DISTINCT al.session_id) * 100.0 / COUNT(DISTINCT s.id) as rate,
    CASE
        WHEN COUNT(DISTINCT al.session_id) * 100.0 / COUNT(DISTINCT s.id) >= 100 THEN 1.00
        WHEN COUNT(DISTINCT al.session_id) * 100.0 / COUNT(DISTINCT s.id) >= 95 THEN 1.25
        WHEN COUNT(DISTINCT al.session_id) * 100.0 / COUNT(DISTINCT s.id) >= 90 THEN 1.50
        WHEN COUNT(DISTINCT al.session_id) * 100.0 / COUNT(DISTINCT s.id) >= 85 THEN 1.75
        WHEN COUNT(DISTINCT al.session_id) * 100.0 / COUNT(DISTINCT s.id) >= 80 THEN 2.00
        WHEN COUNT(DISTINCT al.session_id) * 100.0 / COUNT(DISTINCT s.id) >= 75 THEN 2.50
        WHEN COUNT(DISTINCT al.session_id) * 100.0 / COUNT(DISTINCT s.id) >= 70 THEN 2.75
        WHEN COUNT(DISTINCT al.session_id) * 100.0 / COUNT(DISTINCT s.id) >= 65 THEN 3.00
        WHEN COUNT(DISTINCT al.session_id) * 100.0 / COUNT(DISTINCT s.id) >= 60 THEN 4.00
        ELSE 5.00
    END as grade
FROM 
    attendance_sessions s
JOIN 
    tbl_student st ON RIGHT(st.course_section, LENGTH(st.course_section) - LOCATE('-', st.course_section)) = s.section
LEFT JOIN 
    attendance_logs al ON al.session_id = s.id AND al.student_id = st.tbl_student_id
WHERE 
    s.term = '2nd Semester'
GROUP BY 
    st.tbl_student_id, s.course_id, s.term, s.section
ON DUPLICATE KEY UPDATE
    attendance_rate = VALUES(attendance_rate),
    attendance_grade = VALUES(attendance_grade);
";

// Display the SQL queries for manual execution in phpMyAdmin
echo "<h2>Instructions:</h2>";
echo "<p>Since we're having issues with PHP's database extensions, please run these SQL commands directly in phpMyAdmin:</p>";

echo "<h3>1. Create attendance sessions</h3>";
echo "<pre>" . htmlspecialchars($create_sessions_sql) . "</pre>";

echo "<h3>2. Create attendance logs</h3>";
echo "<pre>" . htmlspecialchars($create_logs_sql) . "</pre>";

echo "<h3>3. Update attendance grades</h3>";
echo "<pre>" . htmlspecialchars($update_grades_sql) . "</pre>";

echo "<h2>Alternative: Use XAMPP</h2>";
echo "<p>As an easier solution, try using XAMPP's pre-configured PHP which has all database extensions enabled:</p>";
echo "<ol>";
echo "<li>Open XAMPP Control Panel</li>";
echo "<li>Start Apache</li>";
echo "<li>Access your project using: <a href='http://localhost/qr-code-attendance-system/'>http://localhost/qr-code-attendance-system/</a></li>";
echo "</ol>";

echo "<p><a href='attendance-grades.php'>Return to Attendance Grades</a></p>";
?> 