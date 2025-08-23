-- Qnnect: Reset core data and reset AUTO_INCREMENT to 1
-- This clears records from the specified tables and ensures new inserts start at ID 1
-- Tested for MySQL/MariaDB (XAMPP).

-- Safety: disable checks during bulk truncates
SET @OLD_FOREIGN_KEY_CHECKS := @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;
SET @OLD_UNIQUE_CHECKS := @@UNIQUE_CHECKS;
SET UNIQUE_CHECKS = 0;
SET @OLD_SQL_MODE := @@SQL_MODE;
-- Keep NO_AUTO_VALUE_ON_ZERO off unless you know you need it

START TRANSACTION;

-- 1) Child tables first (respect typical FK relationships)
-- Attendance and M2M links
TRUNCATE TABLE `tbl_attendance`;
TRUNCATE TABLE `tbl_instructor_subjects`;

-- Teacher schedule logs then schedules
TRUNCATE TABLE `teacher_schedule_logs`;
TRUNCATE TABLE `teacher_schedules`;

-- Face verification / recognition tables
TRUNCATE TABLE `tbl_face_verification`;
TRUNCATE TABLE `tbl_face_recognition`;

-- Sections before courses (sections usually reference courses)
TRUNCATE TABLE `tbl_sections`;

-- Courses
TRUNCATE TABLE `tbl_courses`;

-- 2) Parent tables
-- Students
TRUNCATE TABLE `tbl_student`;

-- Instructors and Subjects
TRUNCATE TABLE `tbl_instructors`;
TRUNCATE TABLE `tbl_subjects`;

-- 3) Explicitly reset AUTO_INCREMENT (redundant after TRUNCATE, but kept for clarity)
ALTER TABLE `tbl_attendance` AUTO_INCREMENT = 1;
ALTER TABLE `tbl_instructor_subjects` AUTO_INCREMENT = 1;
ALTER TABLE `teacher_schedule_logs` AUTO_INCREMENT = 1;
ALTER TABLE `teacher_schedules` AUTO_INCREMENT = 1;
ALTER TABLE `tbl_face_verification` AUTO_INCREMENT = 1;
ALTER TABLE `tbl_face_recognition` AUTO_INCREMENT = 1;
ALTER TABLE `tbl_sections` AUTO_INCREMENT = 1;
ALTER TABLE `tbl_courses` AUTO_INCREMENT = 1;
ALTER TABLE `tbl_student` AUTO_INCREMENT = 1;
ALTER TABLE `tbl_instructors` AUTO_INCREMENT = 1;
ALTER TABLE `tbl_subjects` AUTO_INCREMENT = 1;

COMMIT;

-- Restore safety settings
SET SQL_MODE = @OLD_SQL_MODE;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- Notes:
-- - TRUNCATE resets AUTO_INCREMENT to 1 by default (MySQL/MariaDB).
-- - With AUTO_INCREMENT reset, the first new row inserted into each table will have ID = 1,
--   so dependent foreign keys can reference 1 as needed.
-- - If some tables do not exist in your database, comment out their lines above to avoid errors.
