<?php
// Create the missing class_schedules table for compatibility
require_once 'conn/db_connect.php';

echo "<h2>Creating class_schedules table for compatibility...</h2>";

try {
    // Create class_schedules table (for compatibility with existing system)
    $sql = "CREATE TABLE IF NOT EXISTS `class_schedules` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `subject` varchar(255) NOT NULL,
        `course_section` varchar(100) NOT NULL,
        `instructor_name` varchar(255) NOT NULL,
        `room` varchar(100) DEFAULT NULL,
        `start_time` time NOT NULL,
        `end_time` time NOT NULL,
        `days_of_week` varchar(50) NOT NULL,
        `school_id` int(11) DEFAULT 1,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_school_id` (`school_id`),
        KEY `idx_instructor` (`instructor_name`),
        KEY `idx_section` (`course_section`),
        KEY `idx_subject` (`subject`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn_qr->query($sql);
    echo "✅ class_schedules table created successfully<br>";
    
    // Insert some sample data for compatibility
    $sql = "INSERT IGNORE INTO `class_schedules` 
            (`subject`, `course_section`, `instructor_name`, `room`, `start_time`, `end_time`, `days_of_week`, `school_id`) VALUES
            ('Mathematics', 'BSIT-1A', 'teacher1', 'Room 101', '08:00:00', '09:30:00', 'Monday', 1),
            ('Physics', 'BSIT-1B', 'teacher1', 'Room 102', '10:00:00', '11:30:00', 'Monday', 1),
            ('Computer Science', 'BSIT-2A', 'teacher1', 'Computer Lab', '08:00:00', '09:30:00', 'Tuesday', 1),
            ('Programming', 'BSIT-2B', 'teacher1', 'Computer Lab', '10:00:00', '11:30:00', 'Tuesday', 1),
            ('Database Management', 'BSIT-3A', 'teacher1', 'Room 103', '08:00:00', '09:30:00', 'Wednesday', 1),
            ('Web Development', 'BSIT-3B', 'teacher1', 'Computer Lab', '10:00:00', '11:30:00', 'Wednesday', 1)";
    
    $conn_qr->query($sql);
    echo "✅ Sample data inserted into class_schedules table<br>";
    
    echo "<br><h3>🎉 Compatibility table created successfully!</h3>";
    echo "<p>The class_schedules table has been created to maintain compatibility with the existing system.</p>";
    echo "<p><a href='index.php'>Go to Index Page</a> | <a href='teacher-schedule.php'>Go to Teacher Schedule</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error creating table:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 