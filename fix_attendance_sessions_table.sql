-- SQL Script to fix attendance_sessions table
-- Run this in phpMyAdmin to add missing school_id column

-- First, check if the table exists
SELECT COUNT(*) as table_exists 
FROM information_schema.tables 
WHERE table_schema = 'qr_attendance_db' 
AND table_name = 'attendance_sessions';

-- Add school_id column if it doesn't exist
ALTER TABLE `attendance_sessions` 
ADD COLUMN `school_id` INT NOT NULL DEFAULT 1 AFTER `instructor_id`;

-- Add status column if it doesn't exist (needed for terminate session API)
ALTER TABLE `attendance_sessions` 
ADD COLUMN `status` ENUM('active', 'terminated', 'completed') DEFAULT 'active' AFTER `end_time`;

-- Add indexes for better performance
ALTER TABLE `attendance_sessions` 
ADD INDEX `idx_school_id` (`school_id`),
ADD INDEX `idx_status` (`status`),
ADD INDEX `idx_start_time` (`start_time`);

-- Update existing records to have school_id = 1 (default)
UPDATE `attendance_sessions` SET `school_id` = 1 WHERE `school_id` = 0 OR `school_id` IS NULL;

-- Show the final table structure
DESCRIBE `attendance_sessions`;

-- Show sample data
SELECT * FROM `attendance_sessions` LIMIT 5; 