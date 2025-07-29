<?php
// Setup script for teacher schedule database
require_once 'conn/db_connect.php';

echo "<h2>Setting up Teacher Schedule Database...</h2>";

try {
    // Create teacher_schedules table
    $sql = "CREATE TABLE IF NOT EXISTS `teacher_schedules` (
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
        KEY `idx_day_time` (`day_of_week`, `start_time`, `end_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn_qr->query($sql);
    echo "✅ teacher_schedules table created successfully<br>";
    
    // Create teacher_holidays table
    $sql = "CREATE TABLE IF NOT EXISTS `teacher_holidays` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn_qr->query($sql);
    echo "✅ teacher_holidays table created successfully<br>";
    
    // Create teacher_schedule_logs table
    $sql = "CREATE TABLE IF NOT EXISTS `teacher_schedule_logs` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn_qr->query($sql);
    echo "✅ teacher_schedule_logs table created successfully<br>";
    
    // Insert sample data
    $sql = "INSERT IGNORE INTO `teacher_schedules` (`teacher_username`, `subject`, `section`, `day_of_week`, `start_time`, `end_time`, `room`, `school_id`) VALUES
    ('teacher1', 'Mathematics', 'BSIT-1A', 'Monday', '08:00:00', '09:30:00', 'Room 101', 1),
    ('teacher1', 'Physics', 'BSIT-1B', 'Monday', '10:00:00', '11:30:00', 'Room 102', 1),
    ('teacher1', 'Computer Science', 'BSIT-2A', 'Tuesday', '08:00:00', '09:30:00', 'Computer Lab', 1),
    ('teacher1', 'Programming', 'BSIT-2B', 'Tuesday', '10:00:00', '11:30:00', 'Computer Lab', 1),
    ('teacher1', 'Database Management', 'BSIT-3A', 'Wednesday', '08:00:00', '09:30:00', 'Room 103', 1),
    ('teacher1', 'Web Development', 'BSIT-3B', 'Wednesday', '10:00:00', '11:30:00', 'Computer Lab', 1)";
    
    $conn_qr->query($sql);
    echo "✅ Sample schedule data inserted successfully<br>";
    
    // Insert sample holidays
    $sql = "INSERT IGNORE INTO `teacher_holidays` (`holiday_date`, `holiday_name`, `holiday_type`, `school_id`) VALUES
    ('2024-01-01', 'New Year''s Day', 'national', 1),
    ('2024-04-09', 'Day of Valor (Araw ng Kagitingan)', 'national', 1),
    ('2024-05-01', 'Labor Day', 'national', 1),
    ('2024-06-12', 'Independence Day', 'national', 1),
    ('2024-08-21', 'Ninoy Aquino Day', 'national', 1),
    ('2024-08-30', 'National Heroes Day', 'national', 1),
    ('2024-11-01', 'All Saints'' Day', 'national', 1),
    ('2024-11-02', 'All Souls'' Day', 'national', 1),
    ('2024-11-30', 'Bonifacio Day', 'national', 1),
    ('2024-12-24', 'Christmas Eve', 'national', 1),
    ('2024-12-25', 'Christmas Day', 'national', 1),
    ('2024-12-30', 'Rizal Day', 'national', 1),
    ('2024-12-31', 'New Year''s Eve', 'national', 1)";
    
    $conn_qr->query($sql);
    echo "✅ Sample holiday data inserted successfully<br>";
    
    echo "<br><h3>🎉 Database setup completed successfully!</h3>";
    echo "<p>You can now use the teacher schedule system. <a href='teacher-schedule.php'>Go to Teacher Schedule</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error setting up database:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 