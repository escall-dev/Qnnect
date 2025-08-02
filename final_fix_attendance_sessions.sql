-- Final Fix for attendance_sessions table
-- This script ONLY adds missing columns - no indexes to avoid duplicate key errors

-- Step 1: Add school_id column if it doesn't exist
ALTER TABLE `attendance_sessions` 
ADD COLUMN `school_id` INT NOT NULL DEFAULT 1 AFTER `instructor_id`;

-- Step 2: Add status column if it doesn't exist  
ALTER TABLE `attendance_sessions` 
ADD COLUMN `status` ENUM('active', 'terminated', 'completed') DEFAULT 'active' AFTER `end_time`;

-- Step 3: Update any existing records to have school_id = 1
UPDATE `attendance_sessions` SET `school_id` = 1 WHERE `school_id` = 0 OR `school_id` IS NULL;

-- Step 4: Show the final table structure
DESCRIBE `attendance_sessions`;

-- Step 5: Show sample data
SELECT * FROM `attendance_sessions` LIMIT 5;

-- Step 6: Verify the fix worked
SELECT 
    'Table fixed successfully!' as status,
    COUNT(*) as total_records,
    COUNT(CASE WHEN school_id = 1 THEN 1 END) as records_with_school_id_1
FROM `attendance_sessions`; 