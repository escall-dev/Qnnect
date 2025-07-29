<?php
require_once(__DIR__ . '/schedule_helper.php');

function getFilteredScheduleData($conn, $instructor = null, $section = null, $subject = null, $school_id = 1) {
    try {
        $conditions = ["school_id = ?"];
        $params = [$school_id];
        $types = "i";

        if ($instructor) {
            $conditions[] = "instructor_name = ?";
            $params[] = $instructor;
            $types .= "s";
        }
        if ($section) {
            $conditions[] = "course_section = ?";
            $params[] = $section;
            $types .= "s";
        }
        if ($subject) {
            $conditions[] = "subject = ?";
            $params[] = $subject;
            $types .= "s";
        }

        $sql = "SELECT * FROM class_schedules WHERE " . implode(" AND ", $conditions) . " ORDER BY start_time";
        $stmt = $conn->prepare($sql);
        
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $schedules = [];
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        
        return $schedules;
    } catch (Exception $e) {
        error_log("Error in getFilteredScheduleData: " . $e->getMessage());
        return [];
    }
}
