-- SQL Script to populate sample data for school_id = 1
-- This ensures QR code scanning works for school_id = 1

-- Insert sample instructors for school_id = 1
INSERT INTO `tbl_instructors` (`instructor_name`, `email`, `phone`, `school_id`, `created_at`) VALUES
('John Smith', 'john.smith@school1.edu', '09123456789', 1, NOW()),
('Jane Doe', 'jane.doe@school1.edu', '09123456790', 1, NOW()),
('Mike Johnson', 'mike.johnson@school1.edu', '09123456791', 1, NOW())
ON DUPLICATE KEY UPDATE `school_id` = VALUES(`school_id`);

-- Insert sample students for school_id = 1
INSERT INTO `tbl_student` (`student_name`, `student_id`, `course`, `section`, `school_id`, `created_at`) VALUES
('Alice Johnson', '2021-001', 'Computer Science', 'A', 1, NOW()),
('Bob Smith', '2021-002', 'Computer Science', 'A', 1, NOW()),
('Charlie Brown', '2021-003', 'Computer Science', 'B', 1, NOW()),
('Diana Prince', '2021-004', 'Information Technology', 'A', 1, NOW()),
('Edward Wilson', '2021-005', 'Information Technology', 'B', 1, NOW())
ON DUPLICATE KEY UPDATE `school_id` = VALUES(`school_id`);

-- Insert sample class schedules for school_id = 1
INSERT INTO `class_schedules` (`instructor_name`, `subject`, `course_section`, `day_of_week`, `start_time`, `end_time`, `room`, `school_id`, `created_at`) VALUES
('John Smith', 'Programming Fundamentals', 'Computer Science A', 'Monday', '08:00:00', '09:30:00', 'Room 101', 1, NOW()),
('John Smith', 'Programming Fundamentals', 'Computer Science A', 'Wednesday', '08:00:00', '09:30:00', 'Room 101', 1, NOW()),
('Jane Doe', 'Database Management', 'Computer Science A', 'Tuesday', '10:00:00', '11:30:00', 'Room 102', 1, NOW()),
('Jane Doe', 'Database Management', 'Computer Science A', 'Thursday', '10:00:00', '11:30:00', 'Room 102', 1, NOW()),
('Mike Johnson', 'Web Development', 'Information Technology A', 'Monday', '13:00:00', '14:30:00', 'Room 103', 1, NOW()),
('Mike Johnson', 'Web Development', 'Information Technology A', 'Wednesday', '13:00:00', '14:30:00', 'Room 103', 1, NOW())
ON DUPLICATE KEY UPDATE `school_id` = VALUES(`school_id`);

-- Insert sample attendance sessions for school_id = 1
INSERT INTO `attendance_sessions` (`course_id`, `term`, `section`, `instructor_id`, `school_id`, `start_time`, `end_time`, `status`) VALUES
(1, '2nd Semester', 'Computer Science A', 1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR), 'active'),
(2, '2nd Semester', 'Computer Science A', 2, 1, DATE_ADD(NOW(), INTERVAL 2 HOUR), DATE_ADD(NOW(), INTERVAL 3 HOUR), 'active'),
(3, '2nd Semester', 'Information Technology A', 3, 1, DATE_ADD(NOW(), INTERVAL 4 HOUR), DATE_ADD(NOW(), INTERVAL 5 HOUR), 'active')
ON DUPLICATE KEY UPDATE `school_id` = VALUES(`school_id`);

-- Insert sample class time settings for school_id = 1
INSERT INTO `class_time_settings` (`school_id`, `user_id`, `class_start_time`, `updated_at`) VALUES
(1, 1, '08:00:00', NOW())
ON DUPLICATE KEY UPDATE `class_start_time` = VALUES(`class_start_time`);

-- Show the data that was created
SELECT 'Instructors for school_id = 1' as table_name, COUNT(*) as count FROM tbl_instructors WHERE school_id = 1
UNION ALL
SELECT 'Students for school_id = 1' as table_name, COUNT(*) as count FROM tbl_student WHERE school_id = 1
UNION ALL
SELECT 'Schedules for school_id = 1' as table_name, COUNT(*) as count FROM class_schedules WHERE school_id = 1
UNION ALL
SELECT 'Attendance sessions for school_id = 1' as table_name, COUNT(*) as count FROM attendance_sessions WHERE school_id = 1
UNION ALL
SELECT 'Class time settings for school_id = 1' as table_name, COUNT(*) as count FROM class_time_settings WHERE school_id = 1; 