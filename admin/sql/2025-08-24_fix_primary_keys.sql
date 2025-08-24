-- Migration: Fix missing PRIMARY KEY on log tables and enforce dedupe
-- Safe to run multiple times (uses conditional checks where possible)

-- Ensure tbl_attendance has PK (skip manually if already present)
-- Note: Older MySQL/MariaDB do not support IF NOT EXISTS for PRIMARY KEY
-- Use admin/auto_fix_schema.php for idempotent setup if this fails.
-- ALTER TABLE `tbl_attendance` ADD PRIMARY KEY (`tbl_attendance_id`);

-- attendance_logs id primary key
-- ALTER TABLE `attendance_logs` ADD PRIMARY KEY (`id`);

-- attendance_sessions id primary key
-- ALTER TABLE `attendance_sessions` ADD PRIMARY KEY (`id`);

-- Face recognition/verification logs (if they exist without PK)
-- ALTER TABLE `tbl_face_recognition_logs` ADD PRIMARY KEY (`log_id`);

-- ALTER TABLE `tbl_face_verification_logs` ADD PRIMARY KEY (`log_id`);

-- Create unique index for daily dedupe if missing
-- Create unique index for daily dedupe if missing
-- If you see #1061 Duplicate key name, it already exists — you can ignore.
ALTER TABLE `tbl_attendance`
  ADD UNIQUE KEY IF NOT EXISTS `uq_attendance_unique_day` (`tbl_student_id`,`user_id`,`school_id`,`subject_id_nz`,`instructor_id_nz`,`time_in_date`);

-- Note: MariaDB/MySQL variants vary on IF NOT EXISTS support. Prefer using admin/auto_fix_schema.php
-- or admin/fix_logid_primary_keys.php for safe, repeatable changes.
