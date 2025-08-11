<?php
// Include database connection if not already included
require_once(__DIR__ . '/../conn/db_connect.php');

/**
 * Get all unique schedule data for dropdowns scoped to the current user
 *
 * @param int $school_id         The school ID to filter data for (required)
 * @param string $teacher_username Current user's teacher username (required)
 * @param int $user_id           Current user's ID (required)
 */
function getScheduleDropdownData($school_id, $teacher_username, $user_id) {
    global $conn_qr;

    $data = [
        'instructors' => [],
        'sections' => [],
        'subjects' => [],
        'times' => []
    ];

    // Instructors: only the current teacher
    $sql = "SELECT DISTINCT teacher_username 
            FROM teacher_schedules 
            WHERE school_id = ? AND status = 'active' AND teacher_username = ? AND user_id = ?
            ORDER BY teacher_username";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("isi", $school_id, $teacher_username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['instructors'][] = $row['teacher_username'];
    }

    // Sections taught by this user
    $sql = "SELECT DISTINCT section 
            FROM teacher_schedules 
            WHERE school_id = ? AND status = 'active' AND teacher_username = ? AND user_id = ?
            ORDER BY section";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("isi", $school_id, $teacher_username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['section'])) {
            $data['sections'][] = $row['section'];
        }
    }

    // Subjects taught by this user
    $sql = "SELECT DISTINCT subject 
            FROM teacher_schedules 
            WHERE school_id = ? AND status = 'active' AND teacher_username = ? AND user_id = ?
            ORDER BY subject";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("isi", $school_id, $teacher_username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['subjects'][] = $row['subject'];
    }

    // Time slots for this user
    $sql = "SELECT DISTINCT start_time, end_time 
            FROM teacher_schedules 
            WHERE school_id = ? AND status = 'active' AND teacher_username = ? AND user_id = ?
            ORDER BY start_time";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("isi", $school_id, $teacher_username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['times'][] = [
            'start_time' => $row['start_time'],
            'end_time'   => $row['end_time']
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
 * Get all unique subjects from teacher_schedules for the current user
 *
 * @param int $school_id
 * @param string $teacher_username
 * @param int $user_id
 */
function getAllSubjectsForUser($school_id, $teacher_username, $user_id) {
    global $conn_qr;
    
    $sql = "SELECT DISTINCT subject 
            FROM teacher_schedules 
            WHERE school_id = ? AND status = 'active' AND teacher_username = ? AND user_id = ?
            ORDER BY subject";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("isi", $school_id, $teacher_username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject'];
    }
    
    return $subjects;
} 