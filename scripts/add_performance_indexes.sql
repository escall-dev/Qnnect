-- Recommended indexes to speed up logout/session termination queries
-- Run this once on your database

-- attendance_sessions
CREATE INDEX IF NOT EXISTS idx_attendance_sessions_school_start ON attendance_sessions (school_id, start_time);
CREATE INDEX IF NOT EXISTS idx_attendance_sessions_start ON attendance_sessions (start_time);
CREATE INDEX IF NOT EXISTS idx_attendance_sessions_end ON attendance_sessions (end_time);

-- class_time_settings
CREATE INDEX IF NOT EXISTS idx_class_time_settings_school ON class_time_settings (school_id);

