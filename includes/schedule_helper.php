<?php
// Include database connection if not already included
require_once(__DIR__ . '/../conn/db_connect.php');

/**
 * Get all unique schedule data for dropdowns
 */
function getScheduleDropdownData($school_id = 1) {
    global $conn_qr;
    
    $data = [
        'instructors' => [],
        'sections' => [],
        'subjects' => [],
        'times' => []
    ];
    
    // Get unique instructors
    $sql = "SELECT DISTINCT instructor_name FROM class_schedules WHERE school_id = ? ORDER BY instructor_name";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['instructors'][] = $row['instructor_name'];
    }
    
    // Get unique sections
    $sql = "SELECT DISTINCT course_section FROM class_schedules WHERE school_id = ? ORDER BY course_section";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['sections'][] = $row['course_section'];
    }
    
    // Get unique subjects
    $sql = "SELECT DISTINCT subject FROM class_schedules WHERE school_id = ? ORDER BY subject";
    $stmt = $conn_qr->prepare($sql);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data['subjects'][] = $row['subject'];
    }
    
    // Get unique time slots
    $sql = "SELECT DISTINCT start_time, end_time FROM class_schedules WHERE school_id = ? ORDER BY start_time";
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
 * Get filtered schedule data based on parameters
 */
function getFilteredSchedules($instructor = null, $section = null, $subject = null, $school_id = 1) {
    global $conn_qr;
    
    $where = ["school_id = ?"]; 
    $params = [$school_id];
    $types = "i";
    
    if ($instructor) {
        $where[] = "instructor_name = ?";
        $params[] = $instructor;
        $types .= "s";
    }
    
    if ($section) {
        $where[] = "course_section = ?";
        $params[] = $section;
        $types .= "s";
    }
    
    if ($subject) {
        $where[] = "subject = ?";
        $params[] = $subject;
        $types .= "s";
    }
    
    $whereClause = implode(" AND ", $where);
    $sql = "SELECT * FROM class_schedules WHERE $whereClause ORDER BY start_time";
    
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