-- Create the teacher_schedules table
CREATE TABLE IF NOT EXISTS `teacher_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_username` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `section` varchar(100) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(100) DEFAULT NULL,
  `school_id` int(11) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_teacher_username` (`teacher_username`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_day_time` (`day_of_week`, `start_time`, `end_time`),
  KEY `idx_teacher_schedules_composite` (`teacher_username`, `school_id`, `day_of_week`),
  KEY `idx_teacher_schedules_time_range` (`start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create the teacher_holidays table
CREATE TABLE IF NOT EXISTS `teacher_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(255) NOT NULL,
  `holiday_type` enum('national','school','personal') DEFAULT 'school',
  `school_id` int(11) DEFAULT 1,
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_holiday_date_school` (`holiday_date`, `school_id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_holiday_date` (`holiday_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create the teacher_schedule_logs table
CREATE TABLE IF NOT EXISTS `teacher_schedule_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `changed_by` varchar(255) DEFAULT NULL,
  `changed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_schedule_id` (`schedule_id`),
  KEY `idx_action` (`action`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create the teacher_schedule_conflicts view
CREATE OR REPLACE VIEW `teacher_schedule_conflicts` AS
SELECT 
    t1.id as schedule1_id,
    t1.teacher_username as teacher1,
    t1.subject as subject1,
    t1.section as section1,
    t1.day_of_week,
    t1.start_time as start1,
    t1.end_time as end1,
    t1.room as room1,
    t2.id as schedule2_id,
    t2.teacher_username as teacher2,
    t2.subject as subject2,
    t2.section as section2,
    t2.start_time as start2,
    t2.end_time as end2,
    t2.room as room2
FROM teacher_schedules t1
JOIN teacher_schedules t2 ON 
    t1.id != t2.id AND
    t1.day_of_week = t2.day_of_week AND
    t1.school_id = t2.school_id AND
    (
        (t1.start_time < t2.end_time AND t1.end_time > t2.start_time) OR
        (t2.start_time < t1.end_time AND t2.end_time > t1.start_time)
    )
WHERE t1.status = 'active' AND t2.status = 'active';

-- Create stored procedure to check schedule conflicts
DELIMITER //
CREATE PROCEDURE CheckScheduleConflict(
    IN p_teacher_username VARCHAR(255),
    IN p_day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
    IN p_start_time TIME,
    IN p_end_time TIME,
    IN p_school_id INT,
    IN p_exclude_id INT,
    OUT p_has_conflict BOOLEAN
)
BEGIN
    DECLARE conflict_count INT DEFAULT 0;

    SELECT COUNT(*) INTO conflict_count
    FROM teacher_schedules
    WHERE teacher_username = p_teacher_username
    AND day_of_week = p_day_of_week
    AND school_id = p_school_id
    AND status = 'active'
    AND (
        (start_time < p_end_time AND end_time > p_start_time) OR
        (p_start_time < end_time AND p_end_time > start_time)
    )
    AND (p_exclude_id IS NULL OR id != p_exclude_id);

    SET p_has_conflict = (conflict_count > 0);
END //
DELIMITER ;

-- Create function to get teacher weekly schedule
DELIMITER //
CREATE FUNCTION GetTeacherWeeklySchedule(
    p_teacher_username VARCHAR(255),
    p_school_id INT
) 
RETURNS TEXT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE result TEXT DEFAULT '';

    SELECT GROUP_CONCAT(
        CONCAT(
            day_of_week, ': ',
            subject, ' - ',
            section, ' (',
            TIME_FORMAT(start_time, '%h:%i %p'), ' - ',
            TIME_FORMAT(end_time, '%h:%i %p'), ')',
            IF(room IS NOT NULL, CONCAT(' in ', room), '')
        ) ORDER BY 
            FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
            start_time
        SEPARATOR '\n'
    ) INTO result
    FROM teacher_schedules
    WHERE teacher_username = p_teacher_username
    AND school_id = p_school_id
    AND status = 'active';

    RETURN IFNULL(result, 'No schedules found');
END //
DELIMITER ;

-- Trigger for logging inserts
DELIMITER //
CREATE TRIGGER teacher_schedule_after_insert
AFTER INSERT ON teacher_schedules
FOR EACH ROW
BEGIN
    INSERT INTO teacher_schedule_logs (schedule_id, action, new_values, changed_by)
    VALUES (NEW.id, 'INSERT', JSON_OBJECT(
        'teacher_username', NEW.teacher_username,
        'subject', NEW.subject,
        'section', NEW.section,
        'day_of_week', NEW.day_of_week,
        'start_time', NEW.start_time,
        'end_time', NEW.end_time,
        'room', NEW.room,
        'school_id', NEW.school_id
    ), USER());
END //
DELIMITER ;

-- Trigger for logging updates
DELIMITER //
CREATE TRIGGER teacher_schedule_after_update
AFTER UPDATE ON teacher_schedules
FOR EACH ROW
BEGIN
    INSERT INTO teacher_schedule_logs (schedule_id, action, old_values, new_values, changed_by)
    VALUES (NEW.id, 'UPDATE', JSON_OBJECT(
        'teacher_username', OLD.teacher_username,
        'subject', OLD.subject,
        'section', OLD.section,
        'day_of_week', OLD.day_of_week,
        'start_time', OLD.start_time,
        'end_time', OLD.end_time,
        'room', OLD.room,
        'school_id', OLD.school_id
    ), JSON_OBJECT(
        'teacher_username', NEW.teacher_username,
        'subject', NEW.subject,
        'section', NEW.section,
        'day_of_week', NEW.day_of_week,
        'start_time', NEW.start_time,
        'end_time', NEW.end_time,
        'room', NEW.room,
        'school_id', NEW.school_id
    ), USER());
END //
DELIMITER ;

-- Trigger for logging deletions
DELIMITER //
CREATE TRIGGER teacher_schedule_after_delete
AFTER DELETE ON teacher_schedules
FOR EACH ROW
BEGIN
    INSERT INTO teacher_schedule_logs (schedule_id, action, old_values, changed_by)
    VALUES (OLD.id, 'DELETE', JSON_OBJECT(
        'teacher_username', OLD.teacher_username,
        'subject', OLD.subject,
        'section', OLD.section,
        'day_of_week', OLD.day_of_week,
        'start_time', OLD.start_time,
        'end_time', OLD.end_time,
        'room', OLD.room,
        'school_id', OLD.school_id
    ), USER());
END //
DELIMITER ;
