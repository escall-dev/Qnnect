-- Create schedule table
CREATE TABLE IF NOT EXISTS class_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_name VARCHAR(100) NOT NULL,
    room VARCHAR(50) NOT NULL,
    course_section VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    days_of_week VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add school_id column to class_schedules if it doesn't exist
ALTER TABLE class_schedules 
ADD COLUMN IF NOT EXISTS school_id INT DEFAULT 1,
ADD CONSTRAINT fk_schedule_school 
FOREIGN KEY (school_id) REFERENCES login_register.schools(id) ON DELETE CASCADE;
