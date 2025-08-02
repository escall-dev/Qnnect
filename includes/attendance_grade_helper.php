<?php
/**
 * Attendance Grade Helper Functions
 * 
 * This file contains functions for calculating and managing attendance grades.
 */

/**
 * Get the total number of meetings for a course, term, and section
 *
 * @param PDO $pdo Database connection
 * @param int $courseId Course ID
 * @param string $term Academic term
 * @param string $section Class section
 * @return int Total number of meetings
 */
function getTotalMeetings($pdo, $courseId, $term, $section) {
    $query = "SELECT COUNT(*) as total FROM attendance_sessions 
              WHERE course_id = ? AND term = ? AND section = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$courseId, $term, $section]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return (int) $result['total'];
}

/**
 * Get the number of meetings attended by a student
 *
 * @param PDO $pdo Database connection
 * @param int $studentId Student ID
 * @param int $courseId Course ID
 * @param string $term Academic term
 * @param string $section Class section
 * @return int Number of attended meetings
 */
function getAttendedCount($pdo, $studentId, $courseId, $term, $section) {
    $query = "SELECT COUNT(DISTINCT al.session_id) as attended 
              FROM attendance_logs al
              JOIN attendance_sessions s ON al.session_id = s.id
              WHERE al.student_id = ? 
              AND s.course_id = ? 
              AND s.term = ? 
              AND s.section = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$studentId, $courseId, $term, $section]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return (int) $result['attended'];
}

/**
 * Calculate attendance rate
 *
 * @param int $attendedCount Number of meetings attended
 * @param int $totalMeetings Total number of meetings
 * @return float Attendance rate (percentage)
 */
function calculateAttendanceRate($attendedCount, $totalMeetings) {
    if ($totalMeetings == 0) {
        return 100.0; // Prevent division by zero, treat as 100%
    }
    return ($attendedCount / $totalMeetings) * 100;
}

/**
 * Map attendance rate to a grade (1.00-5.00)
 *
 * @param float $rate Attendance rate percentage
 * @return float Grade between 1.00 and 5.00
 */
function attendanceGrade($rate) {
    if ($rate >= 100) return 1.00;
    if ($rate >= 95) return 1.25;
    if ($rate >= 90) return 1.50;
    if ($rate >= 85) return 1.75;
    if ($rate >= 80) return 2.00;
    if ($rate >= 75) return 2.50;
    if ($rate >= 70) return 2.75;
    if ($rate >= 65) return 3.00;
    if ($rate >= 60) return 4.00;
    return 5.00; // Less than 60%
}

/**
 * Update or insert attendance grade for a student
 *
 * @param PDO $pdo Database connection
 * @param int $studentId Student ID
 * @param int $courseId Course ID
 * @param string $term Academic term
 * @param string $section Class section
 * @param float $attendanceRate Calculated attendance rate
 * @param float $attendanceGrade Calculated attendance grade
 * @return bool Success status
 */
function updateAttendanceGrade($pdo, $studentId, $courseId, $term, $section, $attendanceRate, $attendanceGrade) {
    // Check if record exists
    $checkQuery = "SELECT id FROM attendance_grades 
                  WHERE student_id = ? AND course_id = ? AND term = ? AND section = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$studentId, $courseId, $term, $section]);
    
    if ($checkStmt->rowCount() > 0) {
        // Update existing record
        $gradeId = $checkStmt->fetch(PDO::FETCH_ASSOC)['id'];
        $query = "UPDATE attendance_grades 
                 SET attendance_rate = ?, attendance_grade = ?
                 WHERE id = ?";
        $stmt = $pdo->prepare($query);
        return $stmt->execute([$attendanceRate, $attendanceGrade, $gradeId]);
    } else {
        // Insert new record
        $query = "INSERT INTO attendance_grades 
                 (student_id, course_id, term, section, attendance_rate, attendance_grade)
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        return $stmt->execute([$studentId, $courseId, $term, $section, $attendanceRate, $attendanceGrade]);
    }
}

/**
 * Calculate and update attendance grade for a student
 *
 * @param PDO $pdo Database connection
 * @param int $studentId Student ID
 * @param int $courseId Course ID
 * @param string $term Academic term
 * @param string $section Class section
 * @return array Result with status and data
 */
function calculateAndUpdateAttendanceGrade($pdo, $studentId, $courseId, $term, $section) {
    try {
        // Get attendance data
        $totalMeetings = getTotalMeetings($pdo, $courseId, $term, $section);
        $attendedCount = getAttendedCount($pdo, $studentId, $courseId, $term, $section);
        
        // Calculate attendance rate and grade
        $attendanceRate = calculateAttendanceRate($attendedCount, $totalMeetings);
        $grade = attendanceGrade($attendanceRate);
        
        // Update database
        $success = updateAttendanceGrade($pdo, $studentId, $courseId, $term, $section, $attendanceRate, $grade);
        
        return [
            'success' => $success,
            'data' => [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'term' => $term,
                'section' => $section,
                'total_meetings' => $totalMeetings,
                'attended_count' => $attendedCount,
                'attendance_rate' => $attendanceRate,
                'attendance_grade' => $grade
            ]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get attendance grade data for a student
 *
 * @param PDO $pdo Database connection
 * @param int $studentId Student ID
 * @param int|null $courseId Optional course ID filter
 * @return array Attendance grade data
 */
function getStudentAttendanceGrades($pdo, $studentId, $courseId = null) {
    $query = "SELECT * FROM attendance_grades WHERE student_id = ?";
    $params = [$studentId];
    
    if ($courseId !== null) {
        $query .= " AND course_id = ?";
        $params[] = $courseId;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create a new attendance session
 *
 * @param PDO $pdo Database connection
 * @param int $instructorId Instructor ID
 * @param int $courseId Course ID
 * @param string $term Academic term
 * @param string $section Class section
 * @param string $startTime Session start time (Y-m-d H:i:s format)
 * @param string $endTime Session end time (Y-m-d H:i:s format)
 * @return int|bool New session ID on success, false on failure
 */
function createAttendanceSession($pdo, $instructorId, $courseId, $term, $section, $startTime, $endTime) {
    $query = "INSERT INTO attendance_sessions 
             (instructor_id, course_id, term, section, start_time, end_time)
             VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($query);
    $success = $stmt->execute([$instructorId, $courseId, $term, $section, $startTime, $endTime]);
    
    if ($success) {
        return $pdo->lastInsertId();
    }
    
    return false;
}

/**
 * Record student attendance for a session
 *
 * @param PDO $pdo Database connection
 * @param int $sessionId Session ID
 * @param int $studentId Student ID
 * @param string|null $scanTime Scan time (default: current time)
 * @return bool Success status
 */
function recordAttendance($pdo, $sessionId, $studentId, $scanTime = null, $schoolId = 1) {
    if ($scanTime === null) {
        $scanTime = date('Y-m-d H:i:s');
    }
    
    $query = "INSERT INTO attendance_logs (session_id, student_id, scan_time, school_id)
             VALUES (?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($query);
    return $stmt->execute([$sessionId, $studentId, $scanTime, $schoolId]);
} 