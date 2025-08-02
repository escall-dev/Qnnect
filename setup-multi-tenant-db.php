<?php
require_once 'conn/db_connect.php';

// Enhanced Multi-Tenant Database Setup Script
echo "<h2>Setting up Multi-Tenant Database Structure...</h2>";

try {
    // 1. Update tbl_student table for multi-tenant support
    echo "<p>Updating tbl_student table...</p>";
    $alterStudentTable = "ALTER TABLE tbl_student 
                         ADD COLUMN IF NOT EXISTS school_id INT NOT NULL DEFAULT 1,
                         ADD COLUMN IF NOT EXISTS user_id INT NOT NULL DEFAULT 1,
                         ADD INDEX IF NOT EXISTS idx_student_school_user (school_id, user_id),
                         ADD INDEX IF NOT EXISTS idx_student_qr_school (generated_code, school_id, user_id)";
    
    if ($conn_qr->query($alterStudentTable)) {
        echo "<span style='color: green;'>✓ tbl_student updated successfully</span><br>";
    } else {
        echo "<span style='color: orange;'>⚠ tbl_student may already be updated: " . $conn_qr->error . "</span><br>";
    }

    // 2. Update tbl_attendance table for multi-tenant support
    // 2. Update tbl_attendance table for multi-tenant support
echo "<p>Updating tbl_attendance table...</p>";
$alterAttendanceTable = "
    ALTER TABLE tbl_attendance 
    ADD COLUMN IF NOT EXISTS school_id INT NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS user_id INT NOT NULL DEFAULT 1,
    ADD INDEX IF NOT EXISTS idx_attendance_school_user (school_id, user_id),
    ADD INDEX idx_attendance_compound (school_id, user_id, tbl_student_id, time_in);
";

if ($conn_qr->multi_query($alterAttendanceTable)) {
    // Loop through multiple results if needed
    do {
        // store first result
        if ($result = $conn_qr->store_result()) {
            $result->free();
        }
    } while ($conn_qr->more_results() && $conn_qr->next_result());

    echo "<span style='color: green;'>✓ tbl_attendance updated successfully</span><br>";
} else {
    echo "<span style='color: orange;'>⚠ tbl_attendance may already be updated or error occurred: " . $conn_qr->error . "</span><br>";
}


    // 3. Create or update class_schedules table
    echo "<p>Creating/updating class_schedules table...</p>";
    $createScheduleTable = "CREATE TABLE IF NOT EXISTS class_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        instructor_name VARCHAR(100) NOT NULL,
        course_section VARCHAR(50) NOT NULL,
        subject VARCHAR(100) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        day_of_week VARCHAR(20) NOT NULL,
        school_id INT NOT NULL DEFAULT 1,
        user_id INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_school_user (school_id, user_id),
        INDEX idx_schedule_lookup (instructor_name, course_section, subject, school_id),
        UNIQUE KEY unique_schedule (instructor_name, course_section, subject, day_of_week, start_time, school_id, user_id)
    )";
    
    if ($conn_qr->query($createScheduleTable)) {
        echo "<span style='color: green;'>✓ class_schedules table created/updated successfully</span><br>";
    } else {
        echo "<span style='color: red;'>✗ Failed to create class_schedules table: " . $conn_qr->error . "</span><br>";
    }

    // 4. Create attendance_sessions table for session management
    echo "<p>Creating attendance_sessions table...</p>";
    $createSessionsTable = "CREATE TABLE IF NOT EXISTS attendance_sessions (
        session_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        school_id INT NOT NULL,
        instructor_id INT NULL,
        subject_id INT NULL,
        class_start_time TIME NOT NULL,
        session_date DATE NOT NULL,
        attendance_mode ENUM('general', 'room_subject') DEFAULT 'general',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ended_at TIMESTAMP NULL,
        INDEX idx_session_user_school (user_id, school_id),
        INDEX idx_session_active (is_active, session_date),
        UNIQUE KEY unique_active_session (user_id, school_id, session_date, is_active)
    )";
    
    if ($conn_qr->query($createSessionsTable)) {
        echo "<span style='color: green;'>✓ attendance_sessions table created successfully</span><br>";
    } else {
        echo "<span style='color: red;'>✗ Failed to create attendance_sessions table: " . $conn_qr->error . "</span><br>";
    }

    // 5. Update existing attendance records with school_id and user_id if they don't have them
    echo "<p>Updating existing attendance records...</p>";
    $updateExistingAttendance = "UPDATE tbl_attendance 
                                SET school_id = 1, user_id = 1 
                                WHERE school_id = 0 OR user_id = 0 OR school_id IS NULL OR user_id IS NULL";
    
    if ($conn_qr->query($updateExistingAttendance)) {
        $affected = $conn_qr->affected_rows;
        echo "<span style='color: green;'>✓ Updated $affected existing attendance records</span><br>";
    } else {
        echo "<span style='color: orange;'>⚠ Could not update existing attendance records: " . $conn_qr->error . "</span><br>";
    }

    // 6. Update existing student records with school_id and user_id if they don't have them
    echo "<p>Updating existing student records...</p>";
    $updateExistingStudents = "UPDATE tbl_student 
                              SET school_id = 1, user_id = 1 
                              WHERE school_id = 0 OR user_id = 0 OR school_id IS NULL OR user_id IS NULL";
    
    if ($conn_qr->query($updateExistingStudents)) {
        $affected = $conn_qr->affected_rows;
        echo "<span style='color: green;'>✓ Updated $affected existing student records</span><br>";
    } else {
        echo "<span style='color: orange;'>⚠ Could not update existing student records: " . $conn_qr->error . "</span><br>";
    }

    // 7. Create audit_logs table for tracking multi-tenant access
    echo "<p>Creating audit_logs table...</p>";
    $createAuditTable = "CREATE TABLE IF NOT EXISTS audit_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        school_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        table_name VARCHAR(50) NOT NULL,
        record_id INT NULL,
        old_values JSON NULL,
        new_values JSON NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit_user_school (user_id, school_id),
        INDEX idx_audit_action (action, created_at),
        INDEX idx_audit_table (table_name, record_id)
    )";
    
    if ($conn_qr->query($createAuditTable)) {
        echo "<span style='color: green;'>✓ audit_logs table created successfully</span><br>";
    } else {
        echo "<span style='color: red;'>✗ Failed to create audit_logs table: " . $conn_qr->error . "</span><br>";
    }

    // 8. Insert sample class schedules if none exist
    echo "<p>Checking for sample schedules...</p>";
    $checkSchedules = "SELECT COUNT(*) as count FROM class_schedules";
    $result = $conn_qr->query($checkSchedules);
    $scheduleCount = $result->fetch_assoc()['count'];
    
    if ($scheduleCount == 0) {
        echo "<p>Inserting sample schedules...</p>";
        $sampleSchedules = [
            ['Dr. Smith', 'BSIT-3A', 'Database Systems', '08:00:00', '09:30:00', 'Monday'],
            ['Prof. Johnson', 'BSIT-3A', 'Web Development', '09:30:00', '11:00:00', 'Monday'],
            ['Dr. Brown', 'BSIT-3B', 'Software Engineering', '11:00:00', '12:30:00', 'Monday'],
            ['Prof. Davis', 'BSIT-2A', 'Data Structures', '13:30:00', '15:00:00', 'Monday'],
            ['Dr. Wilson', 'BSIT-2B', 'Programming Logic', '15:00:00', '16:30:00', 'Monday']
        ];
        
        $insertSchedule = $conn_qr->prepare("INSERT INTO class_schedules 
            (instructor_name, course_section, subject, start_time, end_time, day_of_week, school_id, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, 1, 1)");
        
        foreach ($sampleSchedules as $schedule) {
            $insertSchedule->bind_param("ssssss", ...$schedule);
            $insertSchedule->execute();
        }
        
        echo "<span style='color: green;'>✓ Sample schedules inserted successfully</span><br>";
    } else {
        echo "<span style='color: blue;'>ℹ $scheduleCount schedules already exist</span><br>";
    }

    echo "<h3 style='color: green;'>✅ Multi-Tenant Database Setup Complete!</h3>";
    echo "<p><a href='index.php'>← Return to Main Page</a></p>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Setup Failed!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='index.php'>← Return to Main Page</a></p>";
}
?>
