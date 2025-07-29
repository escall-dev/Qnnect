-- Role-Based System Database Schema Updates
-- Run this script to set up the complete role-based system

-- 1. Update users table with role and school_id columns
ALTER TABLE users 
ADD COLUMN role ENUM('admin', 'super_admin') DEFAULT 'admin',
ADD COLUMN school_id INT NULL;

-- 2. Create schools table with enhanced branding
CREATE TABLE IF NOT EXISTS schools (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    code VARCHAR(50) NOT NULL UNIQUE,
    logo_path VARCHAR(500) DEFAULT 'image/SPCPC-logo-trans.png',
    theme_color VARCHAR(7) DEFAULT '#098744',
    secondary_color VARCHAR(7) DEFAULT '#0a5c2e',
    accent_color VARCHAR(7) DEFAULT '#42b883',
    tagline VARCHAR(255) DEFAULT 'Track Attendance Seamlessly',
    description TEXT DEFAULT 'Modern attendance tracking system',
    address TEXT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    principal_name VARCHAR(255) NULL,
    school_type ENUM('elementary', 'high_school', 'college', 'university', 'training_center') DEFAULT 'college',
    timezone VARCHAR(50) DEFAULT 'Asia/Manila',
    academic_year_start MONTH DEFAULT 6,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- 3. Insert initial schools
INSERT INTO schools (name, code, theme_color) VALUES 
('SPCPC', 'SPCPC', '#098744'),
('Computer Site Inc.', 'CSI', '#FFD700')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 4. Create theme_passkeys table
CREATE TABLE IF NOT EXISTS theme_passkeys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_hash VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    school_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    used BOOLEAN DEFAULT FALSE,
    used_at DATETIME NULL,
    used_by INT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL,
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_expires_at (expires_at),
    INDEX idx_used (used)
);

-- 5. Create schedules table for auto-generated schedules
CREATE TABLE IF NOT EXISTS schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    class_name VARCHAR(255) NOT NULL,
    instructor VARCHAR(255) NOT NULL,
    room VARCHAR(100) NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_school_day_time (school_id, day_of_week, start_time),
    INDEX idx_room_time (room, day_of_week, start_time)
);

-- 6. Create rooms table for room availability tracking
CREATE TABLE IF NOT EXISTS rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    room_name VARCHAR(100) NOT NULL,
    capacity INT DEFAULT 30,
    room_type ENUM('classroom', 'laboratory', 'auditorium', 'library', 'other') DEFAULT 'classroom',
    status ENUM('available', 'maintenance', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_school_room (school_id, room_name)
);

-- 7. Insert sample rooms for each school
INSERT INTO rooms (school_id, room_name, capacity, room_type) VALUES 
(1, 'Room 101', 30, 'classroom'),
(1, 'Room 102', 30, 'classroom'),
(1, 'Computer Lab 1', 25, 'laboratory'),
(1, 'Computer Lab 2', 25, 'laboratory'),
(1, 'Auditorium', 100, 'auditorium'),
(2, 'Training Room A', 20, 'classroom'),
(2, 'Training Room B', 20, 'classroom'),
(2, 'Tech Lab', 15, 'laboratory'),
(2, 'Conference Hall', 50, 'auditorium')
ON DUPLICATE KEY UPDATE room_name = VALUES(room_name);

-- 8. Create system_logs table for comprehensive logging
CREATE TABLE IF NOT EXISTS system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    school_id INT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_school_action (school_id, action),
    INDEX idx_created_at (created_at)
);

-- 9. Add foreign key constraint to users table
ALTER TABLE users 
ADD CONSTRAINT fk_users_school 
FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL;

-- 10. Create a super admin user (password: admin123)
INSERT INTO users (username, email, password, role, school_id, profile_image) VALUES 
('superadmin', 'admin@system.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', NULL, 'image/SPCPC-logo-trans.png')
ON DUPLICATE KEY UPDATE role = 'super_admin';

-- 11. Update recent_logins table to include school_id
ALTER TABLE recent_logins 
ADD COLUMN school_id INT NULL,
ADD CONSTRAINT fk_recent_logins_school 
FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL;