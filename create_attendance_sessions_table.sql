-- Complete SQL Script for attendance_sessions table
-- Run this in phpMyAdmin to create the table with all required columns

-- Drop table if exists (optional - remove if you want to keep existing data)
-- DROP TABLE IF EXISTS `attendance_sessions`;

-- Create attendance_sessions table with all required columns
CREATE TABLE IF NOT EXISTS `attendance_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL DEFAULT 1,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('active','terminated','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `instructor_id` (`instructor_id`),
  KEY `school_id` (`school_id`),
  KEY `status` (`status`),
  KEY `start_time` (`start_time`),
  CONSTRAINT `fk_session_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `tbl_instructors` (`instructor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Note: Indexes are already included in the CREATE TABLE statement above
-- If you need additional indexes, add them manually in phpMyAdmin



