-- Simple Fix for attendance_sessions table
-- Just add the missing columns - run this in phpMyAdmin

-- Add school_id column (ignore error if it already exists)
ALTER TABLE `attendance_sessions` 
ADD COLUMN `school_id` INT NOT NULL DEFAULT 1 AFTER `instructor_id`;

-- Add status column (ignore error if it already exists)  
ALTER TABLE `attendance_sessions` 
ADD COLUMN `status` ENUM('active', 'terminated', 'completed') DEFAULT 'active' AFTER `end_time`;

-- Update any existing records to have school_id = 1
UPDATE `attendance_sessions` SET `school_id` = 1 WHERE `school_id` = 0 OR `school_id` IS NULL;

-- Show the table structure
DESCRIBE `attendance_sessions`;

-- Show sample data
SELECT * FROM `attendance_sessions` LIMIT 5; 