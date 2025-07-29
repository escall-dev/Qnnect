-- Multi-School Data Isolation Schema
-- This script adds school_id to all relevant tables for complete data isolation

-- 1. Add school_id to attendance-related tables
ALTER TABLE tbl_student 
ADD COLUMN school_id INT NULL,
ADD CONSTRAINT fk_student_school 
FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL;

ALTER TABLE tbl_instructors 
ADD COLUMN school_id INT NULL,
ADD CONSTRAINT fk_instructor_school 
FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL;

ALTER TABLE tbl_subjects 
ADD COLUMN school_id INT NULL,
ADD CONSTRAINT fk_subject_school 
FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL;

ALTER TABLE tbl_attendance 
ADD COLUMN school_id INT NULL,
ADD CONSTRAINT fk_attendance_school 
FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL;

-- 2. Add indexes for better performance
CREATE INDEX idx_student_school ON tbl_student(school_id);
CREATE INDEX idx_instructor_school ON tbl_instructors(school_id);
CREATE INDEX idx_subject_school ON tbl_subjects(school_id);
CREATE INDEX idx_attendance_school ON tbl_attendance(school_id);

-- 3. Create school-specific settings table
CREATE TABLE IF NOT EXISTS school_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_school_setting (school_id, setting_key)
);

-- 4. Insert default settings for each school
INSERT INTO school_settings (school_id, setting_key, setting_value) VALUES
(1, 'attendance_grace_period', '15'),
(1, 'default_class_duration', '90'),
(1, 'academic_year', '2024-2025'),
(1, 'semester', 'First Semester'),
(2, 'attendance_grace_period', '10'),
(2, 'default_class_duration', '120'),
(2, 'academic_year', '2024-2025'),
(2, 'semester', 'First Semester')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- 5. Create school-specific user preferences
CREATE TABLE IF NOT EXISTS user_school_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    school_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_school_pref (user_id, school_id, preference_key)
);

-- 6. Update existing data to assign to default school (SPCPC)
-- This is a one-time migration for existing data
UPDATE tbl_student SET school_id = 1 WHERE school_id IS NULL;
UPDATE tbl_instructors SET school_id = 1 WHERE school_id IS NULL;
UPDATE tbl_subjects SET school_id = 1 WHERE school_id IS NULL;
UPDATE tbl_attendance SET school_id = 1 WHERE school_id IS NULL;

-- 7. Create views for school-specific data access
CREATE OR REPLACE VIEW v_school_students AS
SELECT s.*, sch.name as school_name, sch.code as school_code
FROM tbl_student s
JOIN schools sch ON s.school_id = sch.id
WHERE sch.status = 'active';

CREATE OR REPLACE VIEW v_school_instructors AS
SELECT i.*, sch.name as school_name, sch.code as school_code
FROM tbl_instructors i
JOIN schools sch ON i.school_id = sch.id
WHERE sch.status = 'active';

CREATE OR REPLACE VIEW v_school_subjects AS
SELECT sub.*, sch.name as school_name, sch.code as school_code
FROM tbl_subjects sub
JOIN schools sch ON sub.school_id = sch.id
WHERE sch.status = 'active';

CREATE OR REPLACE VIEW v_school_attendance AS
SELECT a.*, s.student_name, i.instructor_name, sub.subject_name, sch.name as school_name
FROM tbl_attendance a
LEFT JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id
LEFT JOIN tbl_instructors i ON a.instructor_id = i.instructor_id
LEFT JOIN tbl_subjects sub ON a.subject_id = sub.subject_id
JOIN schools sch ON a.school_id = sch.id
WHERE sch.status = 'active';