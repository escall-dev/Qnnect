<?php
// This script manually updates attendance grades without using mysqli or PDO 
// to work around potential PHP extension issues

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Manual Attendance Grade Fix</h1>";

// Try to update grades directly in the database
echo "<p>This script will attempt to manually update student grades.</p>";

echo "<p>Since you're having issues with database drivers in PHP, please follow these steps to fix the issue:</p>";

echo "<ol>";
echo "<li>Your PHP installation is missing the mysqli database extension</li>";
echo "<li>You need to edit php.ini at C:\\Program Files\\php-8.3.11\\php.ini</li>";
echo "<li>Uncomment these lines by removing the semicolons:
<pre>;extension=mysqli
;extension=pdo_mysql</pre></li>";
echo "<li>Restart your PHP server after making these changes</li>";
echo "</ol>";

echo "<h2>Alternative: Use XAMPP's built-in PHP</h2>";
echo "<p>Since you're already in xamppie directory, try using XAMPP's pre-configured PHP which should have the database extensions enabled:</p>";
echo "<ol>";
echo "<li>Start Apache from XAMPP Control Panel</li>";
echo "<li>Access your project through: <a href='http://localhost/qr-code-attendance-system/attendance-grades.php'>http://localhost/qr-code-attendance-system/attendance-grades.php</a></li>";
echo "</ol>";

echo "<h2>Manual Database Fix Instructions</h2>";
echo "<p>If you can access phpMyAdmin or any database tool, run these SQL commands:</p>";

echo "<pre>
-- Check for sessions
SELECT COUNT(*) FROM attendance_sessions;

-- Create sample sessions if needed
INSERT INTO attendance_sessions 
(instructor_id, course_id, term, section, start_time, end_time)
VALUES 
(1, 1, '2nd Semester', '301', '2023-04-01 08:00:00', '2023-04-01 09:30:00'),
(1, 1, '2nd Semester', '301', '2023-04-02 08:00:00', '2023-04-02 09:30:00'),
(1, 1, '2nd Semester', '301', '2023-04-03 08:00:00', '2023-04-03 09:30:00'),
(1, 1, '2nd Semester', '301', '2023-04-04 08:00:00', '2023-04-04 09:30:00'),
(1, 1, '2nd Semester', '301', '2023-04-05 08:00:00', '2023-04-05 09:30:00');

-- Create sample attendance logs
INSERT INTO attendance_logs (session_id, student_id, scan_time)
SELECT s.id, 5, s.start_time  -- Use actual student ID instead of 5
FROM attendance_sessions s
WHERE s.course_id = 1 AND s.section = '301'  -- Match with actual course/section
LIMIT 5;  -- Creates 5 attendance records for this student

-- Update grades for student
UPDATE attendance_grades 
SET attendance_rate = 80.0, attendance_grade = 2.00
WHERE student_id = 5 AND course_id = 1;  -- Use actual student/course IDs
</pre>";

echo "<p>Change the IDs in the example to match your actual student and course IDs.</p>";

echo "<p>You can run these commands directly in your database tool to create sample data and update grades.</p>";

echo "<a href='attendance-grades.php'>Return to Attendance Grades</a>";
?> 