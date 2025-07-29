<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Attendance System Diagnostic (PDO Version)</h1>";

// Database connection
try {
    // Connect using PDO
    $pdo = new PDO(
        'mysql:host=localhost;dbname=qr_attendance_db;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p>Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['attendance_logs', 'attendance_sessions', 'attendance_grades', 'tbl_student'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table $table exists</p>";
            
            // Count records
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<p>&nbsp;&nbsp;&nbsp;Records: $count</p>";
        } else {
            echo "<p>❌ Table $table does not exist!</p>";
        }
    }
    
    // Get first student and check their attendance
    $student = $pdo->query("SELECT tbl_student_id, student_name, course_section FROM tbl_student LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        $studentId = $student['tbl_student_id'];
        echo "<h2>Checking sample student: {$student['student_name']} (ID: $studentId)</h2>";
        
        // Get course parts
        $courseParts = explode('-', $student['course_section']);
        $course = $courseParts[0] ?? '';
        $section = $courseParts[1] ?? '';
        
        echo "<p>Course: $course, Section: $section</p>";
        
        // Find matching course
        $stmt = $pdo->prepare("SELECT subject_id, subject_name FROM tbl_subjects WHERE subject_name LIKE ? LIMIT 1");
        $stmt->execute(["%$course%"]);
        $courseData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($courseData) {
            $courseId = $courseData['subject_id'];
            echo "<p>Found matching course: {$courseData['subject_name']} (ID: $courseId)</p>";
            
            // Create some attendance sessions if none exist
            $sessionCount = $pdo->query("SELECT COUNT(*) FROM attendance_sessions WHERE course_id = $courseId AND section = '$section'")->fetchColumn();
            
            if ($sessionCount == 0) {
                echo "<p>Creating sample attendance sessions...</p>";
                
                $term = "2nd Semester";
                $sampleCount = 10;
                $createdCount = 0;
                
                $stmt = $pdo->prepare("INSERT INTO attendance_sessions (instructor_id, course_id, term, section, start_time, end_time) VALUES (1, ?, ?, ?, ?, ?)");
                
                for ($i = 0; $i < $sampleCount; $i++) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $startTime = "$date 08:00:00";
                    $endTime = "$date 09:30:00";
                    
                    if ($stmt->execute([$courseId, $term, $section, $startTime, $endTime])) {
                        $createdCount++;
                    }
                }
                
                echo "<p>Created $createdCount sample sessions</p>";
                $sessionCount = $createdCount;
            } else {
                echo "<p>Found $sessionCount existing attendance sessions</p>";
            }
            
            // Create some attendance logs for this student
            $logCount = $pdo->query("SELECT COUNT(*) FROM attendance_logs al 
                                    JOIN attendance_sessions s ON al.session_id = s.id
                                    WHERE al.student_id = $studentId 
                                    AND s.course_id = $courseId 
                                    AND s.section = '$section'")->fetchColumn();
            
            if ($logCount == 0) {
                echo "<p>Creating sample attendance logs...</p>";
                
                // Get the sessions for this course/section
                $sessions = $pdo->query("SELECT id FROM attendance_sessions 
                                        WHERE course_id = $courseId 
                                        AND section = '$section' 
                                        LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
                
                $insertCount = 0;
                $stmt = $pdo->prepare("INSERT INTO attendance_logs (session_id, student_id, scan_time) VALUES (?, ?, ?)");
                
                foreach ($sessions as $sessionId) {
                    $scanTime = date('Y-m-d H:i:s');
                    if ($stmt->execute([$sessionId, $studentId, $scanTime])) {
                        $insertCount++;
                    }
                }
                
                echo "<p>Created $insertCount attendance logs for student</p>";
                $logCount = $insertCount;
            } else {
                echo "<p>Found $logCount existing attendance logs</p>";
            }
            
            // Calculate and update attendance rate and grade
            $term = "2nd Semester";
            
            // Check if grade record exists
            $stmt = $pdo->prepare("SELECT id FROM attendance_grades 
                                  WHERE student_id = ? AND course_id = ? AND section = ? AND term = ?");
            $stmt->execute([$studentId, $courseId, $section, $term]);
            
            if ($stmt->rowCount() == 0) {
                echo "<p>Creating attendance grade record...</p>";
                
                $stmt = $pdo->prepare("INSERT INTO attendance_grades (student_id, course_id, term, section) 
                                     VALUES (?, ?, ?, ?)");
                $stmt->execute([$studentId, $courseId, $term, $section]);
            }
            
            // Now calculate attendance rate
            $attendedCount = $pdo->query("SELECT COUNT(DISTINCT al.session_id) FROM attendance_logs al
                                         JOIN attendance_sessions s ON al.session_id = s.id
                                         WHERE al.student_id = $studentId
                                         AND s.course_id = $courseId
                                         AND s.section = '$section'
                                         AND s.term = '$term'")->fetchColumn();
            
            $totalSessions = $pdo->query("SELECT COUNT(*) FROM attendance_sessions
                                         WHERE course_id = $courseId
                                         AND section = '$section'
                                         AND term = '$term'")->fetchColumn();
            
            if ($totalSessions > 0) {
                $attendanceRate = ($attendedCount / $totalSessions) * 100;
                
                // Determine grade based on rate
                if ($attendanceRate >= 100) $grade = 1.00;
                elseif ($attendanceRate >= 95) $grade = 1.25;
                elseif ($attendanceRate >= 90) $grade = 1.50;
                elseif ($attendanceRate >= 85) $grade = 1.75;
                elseif ($attendanceRate >= 80) $grade = 2.00;
                elseif ($attendanceRate >= 75) $grade = 2.50;
                elseif ($attendanceRate >= 70) $grade = 2.75;
                elseif ($attendanceRate >= 65) $grade = 3.00;
                elseif ($attendanceRate >= 60) $grade = 4.00;
                else $grade = 5.00;
                
                // Update the grade
                $stmt = $pdo->prepare("UPDATE attendance_grades SET 
                                     attendance_rate = ?, 
                                     attendance_grade = ?
                                     WHERE student_id = ? AND course_id = ? AND section = ? AND term = ?");
                
                if ($stmt->execute([$attendanceRate, $grade, $studentId, $courseId, $section, $term])) {
                    echo "<p>Updated attendance grade: Rate = $attendanceRate%, Grade = $grade</p>";
                } else {
                    echo "<p>Error updating grade</p>";
                }
            } else {
                echo "<p>No sessions found for calculating attendance rate</p>";
            }
        } else {
            echo "<p>No matching course found for '$course'</p>";
        }
    } else {
        echo "<p>No students found in database</p>";
    }
    
    echo "<h2>Summary</h2>";
    echo "<p>This script has checked and potentially created sample attendance data.</p>";
    echo "<p><a href='attendance-grades.php' target='_blank'>Check Attendance Grades</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>Database Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), 'could not find driver') !== false) {
        echo "<p>The PDO MySQL driver is not enabled. Please check your PHP configuration:</p>";
        echo "<ol>";
        echo "<li>Open php.ini file (located at " . php_ini_loaded_file() . ")</li>";
        echo "<li>Find and uncomment the line ;extension=pdo_mysql by removing the semicolon</li>";
        echo "<li>Save the file and restart your web server</li>";
        echo "</ol>";
    }
}
?> 