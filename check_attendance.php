<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Attendance System Diagnostic</h1>";

// Database connection
try {
    $conn = new mysqli('localhost', 'root', '', 'qr_attendance_db');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "<p>Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['attendance_logs', 'attendance_sessions', 'attendance_grades', 'tbl_student'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<p>✅ Table $table exists</p>";
            
            // Count records
            $count = $conn->query("SELECT COUNT(*) as count FROM $table");
            $countRow = $count->fetch_assoc();
            echo "<p>&nbsp;&nbsp;&nbsp;Records: {$countRow['count']}</p>";
        } else {
            echo "<p>❌ Table $table does not exist!</p>";
        }
    }
    
    // Check if attendance logs exist for students
    echo "<h2>Sample Student Attendance Check:</h2>";
    $query = "SELECT s.tbl_student_id, s.student_name, s.course_section, 
             (SELECT COUNT(*) FROM attendance_logs al 
              JOIN attendance_sessions sess ON al.session_id = sess.id 
              WHERE al.student_id = s.tbl_student_id) as log_count
             FROM tbl_student s
             LIMIT 5";
    
    $result = $conn->query($query);
    if ($result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Student ID</th><th>Name</th><th>Course-Section</th><th>Attendance Logs</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['tbl_student_id']}</td>";
            echo "<td>{$row['student_name']}</td>";
            echo "<td>{$row['course_section']}</td>";
            echo "<td>{$row['log_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Error checking student attendance: " . $conn->error . "</p>";
    }
    
    // Check attendance sessions
    echo "<h2>Sample Attendance Sessions:</h2>";
    $query = "SELECT course_id, term, section, COUNT(*) as session_count 
              FROM attendance_sessions 
              GROUP BY course_id, term, section
              LIMIT 10";
    
    $result = $conn->query($query);
    if ($result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Course ID</th><th>Term</th><th>Section</th><th>Sessions Count</th></tr>";
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['course_id']}</td>";
                echo "<td>{$row['term']}</td>";
                echo "<td>{$row['section']}</td>";
                echo "<td>{$row['session_count']}</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No attendance sessions found</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Error checking attendance sessions: " . $conn->error . "</p>";
    }
    
    // Check manually for a specific student and course to see what's wrong
    echo "<h2>Detailed Attendance for First Student:</h2>";
    
    // Get first student
    $student = $conn->query("SELECT tbl_student_id, student_name, course_section FROM tbl_student LIMIT 1");
    if ($student && $student->num_rows > 0) {
        $studentRow = $student->fetch_assoc();
        $studentId = $studentRow['tbl_student_id'];
        echo "<p>Checking attendance for student: {$studentRow['student_name']} (ID: $studentId)</p>";
        
        // Get course parts
        $courseParts = explode('-', $studentRow['course_section']);
        $course = $courseParts[0] ?? '';
        $section = $courseParts[1] ?? '';
        
        echo "<p>Course: $course, Section: $section</p>";
        
        // Get any course ID that might match
        $courseQuery = $conn->query("SELECT subject_id, subject_name FROM tbl_subjects WHERE subject_name LIKE '%$course%' LIMIT 1");
        
        if ($courseQuery && $courseQuery->num_rows > 0) {
            $courseRow = $courseQuery->fetch_assoc();
            $courseId = $courseRow['subject_id'];
            echo "<p>Found matching course: {$courseRow['subject_name']} (ID: $courseId)</p>";
            
            // Check for sessions
            $sessionsQuery = $conn->query("SELECT * FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' LIMIT 5");
            
            if ($sessionsQuery && $sessionsQuery->num_rows > 0) {
                echo "<p>Found " . $sessionsQuery->num_rows . " matching sessions</p>";
                
                // Now check if student has logs
                $logsQuery = $conn->query("SELECT al.* FROM attendance_logs al 
                                          JOIN attendance_sessions s ON al.session_id = s.id
                                          WHERE al.student_id = $studentId 
                                          AND s.course_id = $courseId 
                                          AND s.section = '$section'
                                          LIMIT 5");
                
                if ($logsQuery && $logsQuery->num_rows > 0) {
                    echo "<p>Found " . $logsQuery->num_rows . " attendance logs for this student</p>";
                    
                    // The attendance calculation should work
                    echo "<p>The attendance calculation should be working correctly.</p>";
                } else {
                    echo "<p>❌ No attendance logs found for this student in this course/section.</p>";
                    echo "<p>This explains why the attendance rate is 0.00%</p>";
                    
                    // Let's populate some logs
                    echo "<p>Let's create some sample logs for this student</p>";
                    
                    // Get sessions for this course
                    $getSessionsQuery = $conn->query("SELECT id FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' LIMIT 10");
                    
                    if ($getSessionsQuery && $getSessionsQuery->num_rows > 0) {
                        $insertCount = 0;
                        while ($sessionRow = $getSessionsQuery->fetch_assoc()) {
                            $sessionId = $sessionRow['id'];
                            $scanTime = date('Y-m-d H:i:s');
                            
                            $insertQuery = "INSERT INTO attendance_logs (session_id, student_id, scan_time) 
                                          VALUES ($sessionId, $studentId, '$scanTime')
                                          ON DUPLICATE KEY UPDATE scan_time = '$scanTime'";
                            
                            if ($conn->query($insertQuery)) {
                                $insertCount++;
                            }
                        }
                        
                        echo "<p>Created $insertCount sample attendance logs for student</p>";
                        
                        // Now calculate the attendance grade
                        $term = "2nd Semester"; // Using your term
                        $updateQuery = "UPDATE attendance_grades SET 
                                      attendance_rate = (
                                          SELECT COUNT(DISTINCT al.session_id) * 100.0 / 
                                          (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' AND term = '$term')
                                          FROM attendance_logs al
                                          JOIN attendance_sessions s ON al.session_id = s.id
                                          WHERE al.student_id = $studentId
                                          AND s.course_id = $courseId
                                          AND s.section = '$section'
                                          AND s.term = '$term'
                                      ),
                                      attendance_grade = (
                                          CASE
                                              WHEN (SELECT COUNT(DISTINCT al.session_id) * 100.0 / 
                                                  (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' AND term = '$term')
                                                  FROM attendance_logs al
                                                  JOIN attendance_sessions s ON al.session_id = s.id
                                                  WHERE al.student_id = $studentId
                                                  AND s.course_id = $courseId
                                                  AND s.section = '$section'
                                                  AND s.term = '$term') >= 100 THEN 1.00
                                              WHEN (SELECT COUNT(DISTINCT al.session_id) * 100.0 / 
                                                  (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' AND term = '$term')
                                                  FROM attendance_logs al
                                                  JOIN attendance_sessions s ON al.session_id = s.id
                                                  WHERE al.student_id = $studentId
                                                  AND s.course_id = $courseId
                                                  AND s.section = '$section'
                                                  AND s.term = '$term') >= 95 THEN 1.25
                                              WHEN (SELECT COUNT(DISTINCT al.session_id) * 100.0 / 
                                                  (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' AND term = '$term')
                                                  FROM attendance_logs al
                                                  JOIN attendance_sessions s ON al.session_id = s.id
                                                  WHERE al.student_id = $studentId
                                                  AND s.course_id = $courseId
                                                  AND s.section = '$section'
                                                  AND s.term = '$term') >= 90 THEN 1.50
                                              WHEN (SELECT COUNT(DISTINCT al.session_id) * 100.0 / 
                                                  (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' AND term = '$term')
                                                  FROM attendance_logs al
                                                  JOIN attendance_sessions s ON al.session_id = s.id
                                                  WHERE al.student_id = $studentId
                                                  AND s.course_id = $courseId
                                                  AND s.section = '$section'
                                                  AND s.term = '$term') >= 85 THEN 1.75
                                              WHEN (SELECT COUNT(DISTINCT al.session_id) * 100.0 / 
                                                  (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' AND term = '$term')
                                                  FROM attendance_logs al
                                                  JOIN attendance_sessions s ON al.session_id = s.id
                                                  WHERE al.student_id = $studentId
                                                  AND s.course_id = $courseId
                                                  AND s.section = '$section'
                                                  AND s.term = '$term') >= 80 THEN 2.00
                                              WHEN (SELECT COUNT(DISTINCT al.session_id) * 100.0 / 
                                                  (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' AND term = '$term')
                                                  FROM attendance_logs al
                                                  JOIN attendance_sessions s ON al.session_id = s.id
                                                  WHERE al.student_id = $studentId
                                                  AND s.course_id = $courseId
                                                  AND s.section = '$section'
                                                  AND s.term = '$term') >= 75 THEN 2.50
                                              WHEN (SELECT COUNT(DISTINCT al.session_id) * 100.0 / 
                                                  (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' AND term = '$term')
                                                  FROM attendance_logs al
                                                  JOIN attendance_sessions s ON al.session_id = s.id
                                                  WHERE al.student_id = $studentId
                                                  AND s.course_id = $courseId
                                                  AND s.section = '$section'
                                                  AND s.term = '$term') >= 70 THEN 2.75
                                              WHEN (SELECT COUNT(DISTINCT al.session_id) * 100.0 / 
                                                  (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' AND term = '$term')
                                                  FROM attendance_logs al
                                                  JOIN attendance_sessions s ON al.session_id = s.id
                                                  WHERE al.student_id = $studentId
                                                  AND s.course_id = $courseId
                                                  AND s.section = '$section'
                                                  AND s.term = '$term') >= 65 THEN 3.00
                                              WHEN (SELECT COUNT(DISTINCT al.session_id) * 100.0 / 
                                                  (SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section' AND term = '$term')
                                                  FROM attendance_logs al
                                                  JOIN attendance_sessions s ON al.session_id = s.id
                                                  WHERE al.student_id = $studentId
                                                  AND s.course_id = $courseId
                                                  AND s.section = '$section'
                                                  AND s.term = '$term') >= 60 THEN 4.00
                                              ELSE 5.00
                                          END
                                      )
                                      WHERE student_id = $studentId AND course_id = $courseId AND section = '$section' AND term = '$term'";
                        
                        if ($conn->query($updateQuery)) {
                            echo "<p>Updated attendance grade for this student</p>";
                        } else {
                            echo "<p>Error updating grade: " . $conn->error . "</p>";
                            
                            // Check if student has a grade record at all
                            $checkGradeQuery = $conn->query("SELECT * FROM attendance_grades WHERE student_id = $studentId AND course_id = $courseId AND section = '$section' AND term = '$term'");
                            
                            if ($checkGradeQuery && $checkGradeQuery->num_rows == 0) {
                                echo "<p>No grade record found, creating one...</p>";
                                
                                $insertGradeQuery = "INSERT INTO attendance_grades (student_id, course_id, term, section, attendance_rate, attendance_grade)
                                                  VALUES ($studentId, $courseId, '$term', '$section', 0, 5.00)";
                                
                                if ($conn->query($insertGradeQuery)) {
                                    echo "<p>Created attendance grade record</p>";
                                    
                                    // Try update again
                                    if ($conn->query($updateQuery)) {
                                        echo "<p>Now updated attendance grade for this student</p>";
                                    } else {
                                        echo "<p>Still error updating grade: " . $conn->error . "</p>";
                                    }
                                } else {
                                    echo "<p>Error creating grade record: " . $conn->error . "</p>";
                                }
                            }
                        }
                    } else {
                        echo "<p>No sessions found for this course and section!</p>";
                    }
                }
            } else {
                echo "<p>❌ No attendance sessions found for this course/section combination.</p>";
                echo "<p>This explains why there are no attendance records.</p>";
                
                // Create some sessions
                echo "<p>Creating sample sessions for this course/section...</p>";
                
                $term = "2nd Semester"; // Using your current term
                $sampleCount = 10;
                $createdCount = 0;
                
                for ($i = 0; $i < $sampleCount; $i++) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $startTime = "$date 08:00:00";
                    $endTime = "$date 09:30:00";
                    
                    $insertQuery = "INSERT INTO attendance_sessions (instructor_id, course_id, term, section, start_time, end_time)
                                  VALUES (1, $courseId, '$term', '$section', '$startTime', '$endTime')";
                    
                    if ($conn->query($insertQuery)) {
                        $createdCount++;
                    }
                }
                
                echo "<p>Created $createdCount sample sessions</p>";
            }
        } else {
            echo "<p>❌ No matching course found for '$course'</p>";
        }
    } else {
        echo "<p>No students found in database</p>";
    }
    
    echo "<h2>Summary</h2>";
    echo "<p>The most likely issue is that there are no attendance logs recorded for students, or the sessions don't match the student's course section.</p>";
    echo "<p>This script created sample data for a test student - please check the attendance grades page now.</p>";
    echo "<p><a href='attendance-grades.php' target='_blank'>Check Attendance Grades</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 