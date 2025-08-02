<?php
// Include database connection if not already included
require_once(__DIR__ . '/../conn/db_connect.php');

/**
 * Get all unique schedule data for dropdowns from teacher_schedules table
 * @param int $school_id - The school ID to filter data for (required)
 */
function getScheduleDropdownData($school_id) {
    global $conn_qr;
    
    $data = [
        'instructors' => [],
        'sections' => [],
        'subjects' => [],
        'times' => []
    ];
    
    // Get unique instructors (teacher_username)
    $sql = "SELECT DISTINCT teacher_username FROM teacher_schedules WHERE school_id = ? AND status = 'active' ORDER BY teacher_username";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['instructors'][] = $row['teacher_username'];
    }
    
    // Get unique sections from course_section field in tbl_student
    $sql = "SELECT DISTINCT course_section FROM tbl_student WHERE school_id = ? AND course_section IS NOT NULL AND course_section != '' ORDER BY course_section";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['sections'][] = $row['course_section'];
    }
    
    // Get unique subjects
    $sql = "SELECT DISTINCT subject FROM teacher_schedules WHERE school_id = ? AND status = 'active' ORDER BY subject";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['subjects'][] = $row['subject'];
    }
    
    // Get unique time slots
    $sql = "SELECT DISTINCT start_time, end_time FROM teacher_schedules WHERE school_id = ? AND status = 'active' ORDER BY start_time";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['times'][] = [
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time']
        ];
    }
    
    return $data;
}

/**
 * Get filtered schedule data based on parameters from teacher_schedules table
 * @param int $school_id - The school ID to filter data for (required)
 * @param string|null $instructor - Instructor name to filter by
 * @param string|null $section - Section to filter by
 * @param string|null $subject - Subject to filter by
 */
function getFilteredSchedules($school_id, $instructor = null, $section = null, $subject = null) {
    global $conn_qr;
    
    $where = ["school_id = ? AND status = 'active'"]; 
    $params = [$school_id];
    $types = "i";
    
    if ($instructor) {
        $where[] = "teacher_username = ?";
        $params[] = $instructor;
        $types .= "s";
    }
    
    if ($section) {
        $where[] = "section = ?";
        $params[] = $section;
        $types .= "s";
    }
    
    if ($subject) {
        $where[] = "subject = ?";
        $params[] = $subject;
        $types .= "s";
    }
    
    $whereClause = implode(" AND ", $where);
    $sql = "SELECT * FROM teacher_schedules WHERE $whereClause ORDER BY day_of_week, start_time";
    
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    
    return $schedules;
}

/**
 * Format time for display
 */
function formatScheduleTime($time) {
    return date('g:ia', strtotime($time));
}

/**
 * Get subjects for a specific teacher from teacher_schedules table
 * @param string $teacher_username - The teacher's username
 * @param int $school_id - The school ID to filter data for (required)
 * @param int|null $user_id - Optional user ID for additional filtering
 */
function getTeacherSubjects($teacher_username, $school_id, $user_id = null) {
    global $conn_qr;
    
    $where = ["school_id = ? AND status = 'active' AND teacher_username = ?"];
    $params = [$school_id, $teacher_username];
    $types = "is";
    
    // Add user_id condition if provided for better isolation
    if ($user_id !== null) {
        $where[] = "user_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
    
    $whereClause = implode(" AND ", $where);
    $sql = "SELECT DISTINCT subject FROM teacher_schedules WHERE $whereClause ORDER BY subject";
    
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject'];
    }
    
    return $subjects;
}

/**
 * Get all unique subjects from teacher_schedules table for a school
 * @param int $school_id - The school ID to filter data for (required)
 */
function getAllSubjects($school_id) {
    global $conn_qr;
    
    $sql = "SELECT DISTINCT subject FROM teacher_schedules WHERE school_id = ? AND status = 'active' ORDER BY subject";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject'];
    }
    
    return $subjects;
} 