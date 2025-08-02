-- Simple SQL Script to fix attendance_sessions table
-- This script safely adds missing columns and indexes

-- 1. Add school_id column if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.columns 
     WHERE table_schema = DATABASE() 
     AND table_name = 'attendance_sessions' 
     AND column_name = 'school_id') = 0,
    'ALTER TABLE `attendance_sessions` ADD COLUMN `school_id` INT NOT NULL DEFAULT 1 AFTER `instructor_id`',
    'SELECT "Column school_id already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add status column if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.columns 
     WHERE table_schema = DATABASE() 
     AND table_name = 'attendance_sessions' 
     AND column_name = 'status') = 0,
    'ALTER TABLE `attendance_sessions` ADD COLUMN `status` ENUM(\'active\', \'terminated\', \'completed\') DEFAULT \'active\' AFTER `end_time`',
    'SELECT "Column status already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add indexes only if they don't exist
-- Index for school_id
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.statistics 
     WHERE table_schema = DATABASE() 
     AND table_name = 'attendance_sessions' 
     AND index_name = 'idx_school_id') = 0,
    'ALTER TABLE `attendance_sessions` ADD INDEX `idx_school_id` (`school_id`)',
    'SELECT "Index idx_school_id already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index for status
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.statistics 
     WHERE table_schema = DATABASE() 
     AND table_name = 'attendance_sessions' 
     AND index_name = 'idx_status') = 0,
    'ALTER TABLE `attendance_sessions` ADD INDEX `idx_status` (`status`)',
    'SELECT "Index idx_status already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Composite index for school_id and status
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.statistics 
     WHERE table_schema = DATABASE() 
     AND table_name = 'attendance_sessions' 
     AND index_name = 'idx_school_status') = 0,
    'ALTER TABLE `attendance_sessions` ADD INDEX `idx_school_status` (`school_id`, `status`)',
    'SELECT "Index idx_school_status already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index for start_time and end_time
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM information_schema.statistics 
     WHERE table_schema = DATABASE() 
     AND table_name = 'attendance_sessions' 
     AND index_name = 'idx_start_end_time') = 0,
    'ALTER TABLE `attendance_sessions` ADD INDEX `idx_start_end_time` (`start_time`, `end_time`)',
    'SELECT "Index idx_start_end_time already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Update existing records to have school_id = 1 if they don't have it
UPDATE `attendance_sessions` SET `school_id` = 1 WHERE `school_id` = 0 OR `school_id` IS NULL;

-- 5. Show the final table structure
DESCRIBE `attendance_sessions`;

-- 6. Show sample data
SELECT * FROM `attendance_sessions` LIMIT 5;