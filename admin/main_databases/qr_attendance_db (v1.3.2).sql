-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 16, 2025 at 11:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `qr_attendance_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckScheduleConflict` (IN `p_teacher_username` VARCHAR(255), IN `p_day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), IN `p_start_time` TIME, IN `p_end_time` TIME, IN `p_school_id` INT, IN `p_exclude_id` INT, OUT `p_has_conflict` BOOLEAN)   BEGIN
    DECLARE conflict_count INT DEFAULT 0;

    SELECT COUNT(*) INTO conflict_count
    FROM teacher_schedules
    WHERE teacher_username = p_teacher_username
    AND day_of_week = p_day_of_week
    AND school_id = p_school_id
    AND status = 'active'
    AND (
        (start_time < p_end_time AND end_time > p_start_time) OR
        (p_start_time < end_time AND p_end_time > start_time)
    )
    AND (p_exclude_id IS NULL OR id != p_exclude_id);

    SET p_has_conflict = (conflict_count > 0);
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `GetTeacherWeeklySchedule` (`p_teacher_username` VARCHAR(255), `p_school_id` INT) RETURNS TEXT CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC READS SQL DATA BEGIN
    DECLARE result TEXT DEFAULT '';

    SELECT GROUP_CONCAT(
        CONCAT(
            day_of_week, ': ',
            subject, ' - ',
            section, ' (',
            TIME_FORMAT(start_time, '%h:%i %p'), ' - ',
            TIME_FORMAT(end_time, '%h:%i %p'), ')',
            IF(room IS NOT NULL, CONCAT(' in ', room), '')
        ) ORDER BY 
            FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
            start_time
        SEPARATOR '\n'
    ) INTO result
    FROM teacher_schedules
    WHERE teacher_username = p_teacher_username
    AND school_id = p_school_id
    AND status = 'active';

    RETURN IFNULL(result, 'No schedules found');
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` enum('attendance_scan','settings_change','file_action','user_action','system_change','data_export','offline_sync') NOT NULL,
  `action_description` text NOT NULL,
  `affected_table` varchar(50) DEFAULT NULL,
  `affected_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `school_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action_type`, `action_description`, `affected_table`, `affected_id`, `ip_address`, `user_agent`, `created_at`, `additional_data`, `school_id`) VALUES
(1, 19, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 1st Semester', 'user_settings', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) qr-electron/1.0.0 Chrome/122.0.6261.156 Electron/29.4.6 Safari/537.36', '2025-06-21 13:41:00', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"1st Semester\"}}', 1),
(2, 21, '', 'Deleted attendance record #1', 'tbl_attendance', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 17:06:15', '{\"tbl_attendance_id\":1,\"tbl_student_id\":1,\"time_in\":\"2025-06-21 15:27:15\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":1,\"subject_id\":1}', 1),
(3, 21, 'data_export', 'Exported attendance records in pdf format', 'tbl_attendance', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-25 19:41:09', '{\"format\":\"pdf\",\"start_date\":\"2025-06-18\",\"end_date\":\"2025-06-25\",\"course_section\":\"\",\"day\":\"\",\"record_count\":2}', 1),
(4, 21, 'data_export', 'Exported attendance records in pdf format', 'tbl_attendance', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-25 19:42:20', '{\"format\":\"pdf\",\"start_date\":\"2025-06-18\",\"end_date\":\"2025-06-25\",\"course_section\":\"\",\"day\":\"\",\"record_count\":2}', 1),
(5, 21, 'data_export', 'Exported attendance records in csv format', 'tbl_attendance', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-25 19:43:04', '{\"format\":\"csv\",\"start_date\":\"2025-06-18\",\"end_date\":\"2025-06-25\",\"course_section\":\"\",\"day\":\"\",\"record_count\":2}', 1),
(6, 24, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:36:06', '{\"school_name\":{\"old\":null,\"new\":\"Comsite\"},\"school_address\":{\"old\":null,\"new\":\"san pedro\"},\"school_contact\":{\"old\":null,\"new\":\"12345678901\"},\"school_email\":{\"old\":null,\"new\":\"school@email.com\"},\"school_website\":{\"old\":null,\"new\":\"http:\\/\\/www.school.com\"},\"school_motto\":{\"old\":null,\"new\":\"motto\"},\"school_vision\":{\"old\":null,\"new\":\"vision\"},\"school_mission\":{\"old\":null,\"new\":\"mission\"},\"school_logo_path\":{\"old\":null,\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(7, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:21', '{\"school_year\":{\"old\":\"\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"\",\"new\":\"2nd Semester\"}}', 1),
(8, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:36', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"1st Semester\"}}', 1),
(9, NULL, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:39', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"1st Semester\",\"new\":\"1st Semester\"}}', 1),
(10, NULL, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:40', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"1st Semester\",\"new\":\"1st Semester\"}}', 1),
(11, NULL, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:41', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"1st Semester\",\"new\":\"1st Semester\"}}', 1),
(12, NULL, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:41', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"1st Semester\",\"new\":\"1st Semester\"}}', 1),
(13, NULL, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:43', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"1st Semester\",\"new\":\"2nd Semester\"}}', 1),
(14, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 12:47:14', '{\"school_year\":{\"old\":\"\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"\",\"new\":\"2nd Semester\"}}', 1),
(15, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 12:47:24', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}', 1),
(16, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 12:47:35', '{\"school_name\":{\"old\":null,\"new\":\"San Pedro City Polytechnic College\"}}', 1),
(17, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:01:15', '{\"school_name\":{\"old\":\"School Name\",\"new\":\"San Pedro City Polytechnic College\"},\"school_address\":{\"old\":\"School Address\",\"new\":\"\"},\"school_contact\":{\"old\":\"Contact Number\",\"new\":\"\"},\"school_email\":{\"old\":\"school@email.com\",\"new\":\"\"},\"school_website\":{\"old\":\"www.schoolwebsite.com\",\"new\":\"\"},\"school_motto\":{\"old\":\"School Motto\",\"new\":\"\"},\"school_vision\":{\"old\":\"School Vision\",\"new\":\"\"},\"school_mission\":{\"old\":\"School Mission\",\"new\":\"\"}}', 1),
(18, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:01:21', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}', 1),
(19, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:18:58', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}', 1),
(20, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:19:56', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/SPCPC-logo-trans.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(21, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:20:20', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(22, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:31:47', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}', 1),
(23, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', '2025-07-17 13:32:01', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}', 1),
(24, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:36:30', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}', 1),
(25, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:36:35', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}', 1),
(26, 23, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:01:24', '{\"school_year\":{\"old\":\"\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"\",\"new\":\"2nd Semester\"}}', 1),
(27, 23, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:01:30', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}', 1),
(28, 23, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:01:40', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"1st Semester\"}}', 1),
(29, 24, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:15:01', '{\"school_name\":{\"old\":\"San Pedro City Polytechnic College\",\"new\":\"Computer Site Inc.\"},\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(30, 24, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:15:09', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(31, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:15:36', '{\"school_name\":{\"old\":\"Computer Site Inc.\",\"new\":\"San Pedro City Polytechnic College\"}}', 1),
(32, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:37:33', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(33, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:37:48', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(34, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:37:58', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(35, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:41:17', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(36, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:48:47', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(37, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:51:20', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(38, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:51:42', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(39, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:51:47', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(40, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:52:03', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(41, 24, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:05:54', '{\"school_name\":{\"old\":\"San Pedro City Polytechnic College\",\"new\":\"Computer Site Inc.\"},\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(42, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:06:19', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}', 1),
(43, 24, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:14:38', '{\"school_name\":{\"old\":\"School Name\",\"new\":\"San Pedro City Polytechnic College\"},\"school_address\":{\"old\":\"School Address\",\"new\":\"\"},\"school_contact\":{\"old\":\"Contact Number\",\"new\":\"\"},\"school_email\":{\"old\":\"school@email.com\",\"new\":\"\"},\"school_website\":{\"old\":\"www.schoolwebsite.com\",\"new\":\"\"},\"school_motto\":{\"old\":\"School Motto\",\"new\":\"\"},\"school_vision\":{\"old\":\"School Vision\",\"new\":\"\"},\"school_mission\":{\"old\":\"School Mission\",\"new\":\"\"}}', 1),
(44, 24, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:14:45', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/SPCPC-logo-trans.png\",\"new\":\"admin\\/image\\/school-logo-2.png\"}}', 1),
(45, 24, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:15:17', '{\"school_name\":{\"old\":\"San Pedro City Polytechnic College\",\"new\":\"Computer Site Inc.\"}}', 1),
(46, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:15:36', '{\"school_name\":{\"old\":\"Computer Site Inc.\",\"new\":\"San Pedro City Polytechnic College\"}}', 1),
(47, 24, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:16:51', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo-2.png\",\"new\":\"admin\\/image\\/school-logo-2.png\"}}', 1),
(48, 24, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:22:59', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo-2.png\",\"new\":\"admin\\/image\\/school-logo-2.png\"}}', 1),
(49, 24, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:24:23', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo-2.png\",\"new\":\"admin\\/image\\/school-logo-2.png\"}}', 1),
(50, 24, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:24:31', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo-2.png\",\"new\":\"admin\\/image\\/school-logo-2.png\"}}', 1),
(51, 24, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 16:24:37', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo-2.png\",\"new\":\"admin\\/image\\/school-logo-2.png\"}}', 1),
(52, 24, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 15:21:59', '{\"school_year\":{\"old\":\"\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"\",\"new\":\"2nd Semester\"}}', 1),
(53, 23, '', 'Deleted attendance record #1', 'tbl_attendance', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 21:26:41', '{\"tbl_attendance_id\":1,\"tbl_student_id\":1,\"time_in\":\"2025-06-23 19:47:02\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":1,\"school_id\":1}', 1),
(54, 23, '', 'Deleted attendance record #2', 'tbl_attendance', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 21:26:44', '{\"tbl_attendance_id\":2,\"tbl_student_id\":1,\"time_in\":\"2025-06-23 19:49:26\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":3,\"school_id\":1}', 1),
(55, 23, '', 'Deleted attendance record #3', 'tbl_attendance', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 21:26:46', '{\"tbl_attendance_id\":3,\"tbl_student_id\":5,\"time_in\":\"2025-07-30 21:02:00\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1}', 1),
(56, 23, '', 'Deleted attendance record #4', 'tbl_attendance', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 21:26:49', '{\"tbl_attendance_id\":4,\"tbl_student_id\":1,\"time_in\":\"2025-07-30 21:03:07\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1}', 1),
(57, 23, '', 'Deleted attendance record #3', 'tbl_attendance', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 21:28:07', NULL, 1),
(58, 24, '', 'Deleted attendance record #5', 'tbl_attendance', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:00:01', '{\"tbl_attendance_id\":5,\"tbl_student_id\":10,\"time_in\":\"2025-07-31 00:58:16\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":null}', 1),
(59, 24, '', 'Deleted attendance record #6', 'tbl_attendance', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:00:03', '{\"tbl_attendance_id\":6,\"tbl_student_id\":10,\"time_in\":\"2025-07-31 00:58:19\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":null}', 1),
(60, 24, '', 'Deleted attendance record #7', 'tbl_attendance', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:00:06', '{\"tbl_attendance_id\":7,\"tbl_student_id\":10,\"time_in\":\"2025-07-31 00:58:28\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":null}', 1),
(61, 24, '', 'Deleted attendance record #9', 'tbl_attendance', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:02:15', '{\"tbl_attendance_id\":9,\"tbl_student_id\":10,\"time_in\":\"2025-07-31 01:00:59\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":null}', 1),
(62, 24, '', 'Deleted attendance record #11', 'tbl_attendance', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:09:06', '{\"tbl_attendance_id\":11,\"tbl_student_id\":12,\"time_in\":\"2025-07-31 01:02:21\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":null}', 1),
(63, 24, '', 'Deleted attendance record #12', 'tbl_attendance', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:09:10', '{\"tbl_attendance_id\":12,\"tbl_student_id\":12,\"time_in\":\"2025-07-31 01:02:43\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":null}', 1),
(64, 23, '', 'Deleted attendance record #8', 'tbl_attendance', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:22:10', '{\"tbl_attendance_id\":8,\"tbl_student_id\":7,\"time_in\":\"2025-07-31 00:59:17\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(65, 23, '', 'Deleted attendance record #10', 'tbl_attendance', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:22:15', '{\"tbl_attendance_id\":10,\"tbl_student_id\":16,\"time_in\":\"2025-07-31 01:02:10\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(66, 23, '', 'Deleted attendance record #13', 'tbl_attendance', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:22:19', '{\"tbl_attendance_id\":13,\"tbl_student_id\":17,\"time_in\":\"2025-07-31 01:09:19\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(67, 24, '', 'Deleted attendance record #15', 'tbl_attendance', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:32:37', '{\"tbl_attendance_id\":15,\"tbl_student_id\":14,\"time_in\":\"2025-07-31 01:23:03\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":23}', 1),
(68, 24, '', 'Deleted attendance record #14', 'tbl_attendance', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:32:39', '{\"tbl_attendance_id\":14,\"tbl_student_id\":8,\"time_in\":\"2025-07-31 01:22:55\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":23}', 1),
(69, 24, '', 'Deleted attendance record #14', 'tbl_attendance', 14, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 01:32:46', NULL, 1),
(70, 24, '', 'Deleted attendance record #21', 'tbl_attendance', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 21:46:27', '{\"tbl_attendance_id\":21,\"tbl_student_id\":16,\"time_in\":\"2025-07-31 20:09:01\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(71, 24, '', 'Deleted attendance record #21', 'tbl_attendance', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 21:48:32', NULL, 1),
(72, 24, '', 'Deleted attendance record #54', 'tbl_attendance', 54, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:05', '{\"tbl_attendance_id\":54,\"tbl_student_id\":16,\"time_in\":\"2025-08-01 21:51:16\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(73, 24, '', 'Deleted attendance record #88', 'tbl_attendance', 88, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:09', '{\"tbl_attendance_id\":88,\"tbl_student_id\":16,\"time_in\":\"2025-08-02 01:52:57\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(74, 24, '', 'Deleted attendance record #87', 'tbl_attendance', 87, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:12', '{\"tbl_attendance_id\":87,\"tbl_student_id\":16,\"time_in\":\"2025-08-02 01:52:39\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(75, 24, '', 'Deleted attendance record #78', 'tbl_attendance', 78, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:14', '{\"tbl_attendance_id\":78,\"tbl_student_id\":16,\"time_in\":\"2025-08-02 01:37:22\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(76, 24, '', 'Deleted attendance record #77', 'tbl_attendance', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:16', '{\"tbl_attendance_id\":77,\"tbl_student_id\":16,\"time_in\":\"2025-08-02 01:37:07\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(77, 24, '', 'Deleted attendance record #76', 'tbl_attendance', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:18', '{\"tbl_attendance_id\":76,\"tbl_student_id\":16,\"time_in\":\"2025-08-02 01:37:01\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(78, 24, '', 'Deleted attendance record #75', 'tbl_attendance', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:22', '{\"tbl_attendance_id\":75,\"tbl_student_id\":16,\"time_in\":\"2025-08-02 01:36:26\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(79, 24, '', 'Deleted attendance record #56', 'tbl_attendance', 56, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:25', '{\"tbl_attendance_id\":56,\"tbl_student_id\":16,\"time_in\":\"2025-08-01 22:02:36\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(80, 24, '', 'Deleted attendance record #55', 'tbl_attendance', 55, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:28', '{\"tbl_attendance_id\":55,\"tbl_student_id\":16,\"time_in\":\"2025-08-01 21:57:30\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(81, 24, '', 'Deleted attendance record #53', 'tbl_attendance', 53, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:35', '{\"tbl_attendance_id\":53,\"tbl_student_id\":16,\"time_in\":\"2025-08-01 15:51:15\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(82, 24, '', 'Deleted attendance record #52', 'tbl_attendance', 52, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:37', '{\"tbl_attendance_id\":52,\"tbl_student_id\":16,\"time_in\":\"2025-08-01 15:50:43\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(83, 24, '', 'Deleted attendance record #51', 'tbl_attendance', 51, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:39', '{\"tbl_attendance_id\":51,\"tbl_student_id\":16,\"time_in\":\"2025-08-01 15:46:33\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(84, 24, '', 'Deleted attendance record #50', 'tbl_attendance', 50, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:41', '{\"tbl_attendance_id\":50,\"tbl_student_id\":16,\"time_in\":\"2025-08-01 15:36:49\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(85, 24, '', 'Deleted attendance record #49', 'tbl_attendance', 49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:52:44', '{\"tbl_attendance_id\":49,\"tbl_student_id\":16,\"time_in\":\"2025-08-01 15:07:32\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(86, 23, '', 'Deleted attendance record #92', 'tbl_attendance', 92, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:55:00', '{\"tbl_attendance_id\":92,\"tbl_student_id\":16,\"time_in\":\"2025-08-02 11:51:06\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":null}', 1),
(87, 23, '', 'Deleted attendance record #91', 'tbl_attendance', 91, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:55:03', '{\"tbl_attendance_id\":91,\"tbl_student_id\":16,\"time_in\":\"2025-08-02 11:50:45\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":null}', 1),
(88, 25, '', 'Deleted attendance record #33', 'tbl_attendance', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:55:35', '{\"tbl_attendance_id\":33,\"tbl_student_id\":17,\"time_in\":\"2025-08-01 00:50:36\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":null}', 1),
(89, 25, '', 'Deleted attendance record #23', 'tbl_attendance', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 21:55:38', '{\"tbl_attendance_id\":23,\"tbl_student_id\":13,\"time_in\":\"2025-07-31 20:31:03\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":25}', 1),
(90, 24, '', 'Deleted attendance record #47', 'tbl_attendance', 47, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-03 00:28:49', '{\"tbl_attendance_id\":47,\"tbl_student_id\":7,\"time_in\":\"2025-08-01 14:15:52\",\"status\":\"Late\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(91, 25, '', 'Deleted attendance record #20', 'tbl_attendance', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-03 01:47:29', '{\"tbl_attendance_id\":20,\"tbl_student_id\":13,\"time_in\":\"2025-07-31 02:02:50\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":null,\"subject_id\":null,\"school_id\":1,\"user_id\":25}', 1),
(92, 24, '', 'Deleted attendance record #30', 'tbl_attendance', 30, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 21:49:59', '{\"tbl_attendance_id\":30,\"tbl_student_id\":17,\"time_in\":\"2025-08-01 00:10:35\",\"status\":\"On Time\",\"mode\":\"general\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(93, 24, '', 'Deleted attendance record #30', 'tbl_attendance', 30, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 21:56:08', NULL, 1),
(94, 24, '', 'Deleted attendance record #152', 'tbl_attendance', 152, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 21:56:13', '{\"tbl_attendance_id\":152,\"tbl_student_id\":27,\"time_in\":\"2025-08-06 21:42:03\",\"status\":\"Late\",\"mode\":\"general\",\"time_out\":null,\"instructor_id\":1,\"subject_id\":1,\"school_id\":2,\"user_id\":24}', 1),
(95, 24, '', 'Deleted attendance record #152', 'tbl_attendance', 152, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 21:58:51', NULL, 1),
(96, 24, '', 'Deleted attendance record #145', 'tbl_attendance', 145, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 22:15:04', '{\"tbl_attendance_id\":145,\"tbl_student_id\":41,\"time_in\":\"2025-08-06 20:05:19\",\"status\":\"Late\",\"mode\":\"general\",\"time_out\":null,\"instructor_id\":0,\"subject_id\":1,\"school_id\":2,\"user_id\":24}', 1),
(97, 24, '', 'Deleted attendance record #48', 'tbl_attendance', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 22:22:51', '{\"tbl_attendance_id\":48,\"tbl_student_id\":7,\"time_in\":\"2025-08-01 14:26:03\",\"status\":\"Late\",\"mode\":\"general\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(98, 24, '', 'Deleted attendance record #48', 'tbl_attendance', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 22:22:56', NULL, 1),
(99, 24, '', 'Deleted attendance record #48', 'tbl_attendance', 48, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 22:22:58', NULL, 1),
(100, 24, '', 'Deleted attendance record #46', 'tbl_attendance', 46, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 22:23:01', '{\"tbl_attendance_id\":46,\"tbl_student_id\":7,\"time_in\":\"2025-08-01 14:09:34\",\"status\":\"Late\",\"mode\":\"general\",\"time_out\":null,\"instructor_id\":6,\"subject_id\":null,\"school_id\":2,\"user_id\":24}', 1),
(101, 24, '', 'Deleted attendance record #153', 'tbl_attendance', 153, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 22:23:04', '{\"tbl_attendance_id\":153,\"tbl_student_id\":27,\"time_in\":\"2025-08-06 21:59:02\",\"status\":\"Late\",\"mode\":\"general\",\"time_out\":null,\"instructor_id\":1,\"subject_id\":1,\"school_id\":2,\"user_id\":24}', 1),
(102, 24, '', 'Deleted attendance record #153', 'tbl_attendance', 153, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 22:23:07', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL DEFAULT 1,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('active','terminated','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_schedules`
--

CREATE TABLE `class_schedules` (
  `id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `course_section` varchar(100) NOT NULL,
  `instructor_name` varchar(255) NOT NULL,
  `room` varchar(100) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `days_of_week` varchar(50) NOT NULL,
  `school_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_time_settings`
--

CREATE TABLE `class_time_settings` (
  `id` int(11) NOT NULL,
  `instructor_name` varchar(100) NOT NULL DEFAULT '',
  `course_section` varchar(100) NOT NULL DEFAULT '',
  `subject` varchar(100) NOT NULL DEFAULT '',
  `start_time` time NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `end_time` time NOT NULL DEFAULT '00:00:00',
  `days_of_week` varchar(100) NOT NULL DEFAULT '',
  `school_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_time_settings`
--

INSERT INTO `class_time_settings` (`id`, `instructor_name`, `course_section`, `subject`, `start_time`, `status`, `end_time`, `days_of_week`, `school_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, '', '', '', '00:00:00', 'active', '00:00:00', '', 1, 1, '2025-08-15 14:55:12', '2025-08-16 07:46:14'),
(2, '', '', '', '18:00:00', 'active', '18:28:58', '', 2, 1, '2025-08-15 15:28:58', '2025-08-16 09:29:22');

-- --------------------------------------------------------

--
-- Table structure for table `school_info`
--

CREATE TABLE `school_info` (
  `id` int(11) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `school_address` text DEFAULT NULL,
  `school_contact` varchar(50) DEFAULT NULL,
  `school_email` varchar(100) DEFAULT NULL,
  `school_website` varchar(255) DEFAULT NULL,
  `school_logo_path` varchar(255) DEFAULT NULL,
  `school_motto` text DEFAULT NULL,
  `school_vision` text DEFAULT NULL,
  `school_mission` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `school_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_info`
--

INSERT INTO `school_info` (`id`, `school_name`, `school_address`, `school_contact`, `school_email`, `school_website`, `school_logo_path`, `school_motto`, `school_vision`, `school_mission`, `created_at`, `updated_at`, `school_id`) VALUES
(1, 'San Pedro City Polytechnic College', '', '', '', '', 'admin/image/school-logo.png', '', '', '', '2025-07-17 13:01:15', '2025-07-17 16:15:36', 1),
(2, 'Computer Site Inc.', '', '', '', '', 'admin/image/school-logo-2.png', '', '', '', '2025-07-17 16:14:38', '2025-07-17 16:24:37', 2);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_attendance`
--

CREATE TABLE `tbl_attendance` (
  `tbl_attendance_id` int(11) NOT NULL,
  `tbl_student_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  `mode` varchar(20) DEFAULT 'general',
  `time_out` timestamp NULL DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `school_id` int(11) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_attendance`
--

INSERT INTO `tbl_attendance` (`tbl_attendance_id`, `tbl_student_id`, `time_in`, `status`, `mode`, `time_out`, `instructor_id`, `subject_id`, `school_id`, `user_id`) VALUES
(16, 14, '2025-07-30 17:32:59', 'On Time', 'general', NULL, NULL, NULL, 1, 23),
(17, 8, '2025-07-30 17:33:14', 'On Time', 'general', NULL, NULL, NULL, 1, 23),
(18, 17, '2025-07-30 17:50:03', 'On Time', 'general', NULL, NULL, NULL, 2, 24),
(19, 16, '2025-07-30 17:55:21', 'On Time', 'general', NULL, NULL, NULL, 2, 24),
(22, 17, '2025-07-31 12:09:05', 'Late', 'general', NULL, NULL, NULL, 2, 24),
(24, 14, '2025-07-31 12:33:40', 'Late', 'general', NULL, NULL, NULL, 1, 23),
(25, 17, '2025-07-31 15:33:04', 'Late', 'general', NULL, 6, NULL, 2, 24),
(26, 17, '2025-07-31 15:33:07', 'Late', 'general', NULL, 6, NULL, 2, 24),
(27, 17, '2025-07-31 15:33:08', 'Late', 'general', NULL, 6, NULL, 2, 24),
(28, 17, '2025-07-31 15:33:09', 'Late', 'general', NULL, 6, NULL, 2, 24),
(29, 17, '2025-07-31 16:10:32', 'On Time', 'general', NULL, 6, NULL, 2, 24),
(31, 17, '2025-07-31 16:15:36', 'On Time', 'general', NULL, 6, NULL, 2, 24),
(32, 17, '2025-07-31 16:39:23', 'On Time', 'general', NULL, 6, NULL, 2, 24),
(34, 17, '2025-07-31 18:01:43', 'On Time', 'general', NULL, 6, NULL, 2, 24),
(35, 7, '2025-08-01 03:41:33', 'Late', 'general', NULL, 6, NULL, 2, 24),
(36, 7, '2025-08-01 04:00:17', 'Late', 'general', NULL, 6, NULL, 2, 24),
(37, 7, '2025-08-01 04:10:23', 'Late', 'general', NULL, 6, NULL, 2, 24),
(38, 7, '2025-08-01 04:17:53', 'Late', 'general', NULL, 6, NULL, 2, 24),
(39, 7, '2025-08-01 04:26:54', 'Late', 'general', NULL, 6, NULL, 2, 24),
(40, 7, '2025-08-01 05:18:59', 'Late', 'general', NULL, 6, NULL, 2, 24),
(41, 7, '2025-08-01 05:27:45', 'Late', 'general', NULL, 6, NULL, 2, 24),
(42, 7, '2025-08-01 05:45:54', 'Late', 'general', NULL, 6, NULL, 2, 24),
(44, 7, '2025-08-01 05:53:51', 'Late', 'general', NULL, 6, NULL, 2, 24),
(57, 14, '2025-08-01 14:32:25', 'Late', 'general', NULL, 7, NULL, 1, 23),
(58, 14, '2025-08-01 14:33:24', 'Late', 'general', NULL, 7, NULL, 1, 23),
(59, 14, '2025-08-01 14:34:27', 'Late', 'general', NULL, 7, NULL, 1, 23),
(60, 14, '2025-08-01 14:37:09', 'Late', 'general', NULL, 7, NULL, 1, 23),
(61, 14, '2025-08-01 14:37:32', 'Late', 'general', NULL, 7, NULL, 1, 23),
(62, 14, '2025-08-01 14:40:39', 'On Time', 'general', NULL, 7, NULL, 1, 23),
(63, 14, '2025-08-01 14:42:08', 'On Time', 'general', NULL, 7, NULL, 1, 23),
(64, 14, '2025-08-01 14:42:20', 'On Time', 'general', NULL, 7, NULL, 1, 23),
(65, 14, '2025-08-01 14:46:47', 'On Time', 'general', NULL, 7, NULL, 1, 23),
(66, 14, '2025-08-01 14:48:45', 'Late', 'general', NULL, 7, NULL, 1, 23),
(67, 14, '2025-08-01 14:48:56', 'On Time', 'general', NULL, 7, NULL, 1, 23),
(68, 14, '2025-08-01 14:50:44', 'Late', 'general', NULL, 7, NULL, 1, 23),
(69, 14, '2025-08-01 14:51:00', 'On Time', 'general', NULL, 7, NULL, 1, 23),
(70, 8, '2025-08-01 14:51:56', 'On Time', 'general', NULL, 7, NULL, 1, 23),
(71, 8, '2025-08-01 14:52:43', 'Late', 'general', NULL, 7, NULL, 1, 23),
(72, 14, '2025-08-01 16:24:51', 'On Time', 'general', NULL, 7, NULL, 1, 23),
(101, 28, '2025-08-02 18:36:55', 'On Time', 'general', NULL, NULL, NULL, 1, 25),
(102, 29, '2025-08-02 18:38:29', 'On Time', 'general', NULL, NULL, NULL, 1, 25),
(103, 29, '2025-08-02 18:38:53', 'On Time', 'general', NULL, NULL, NULL, 1, 25),
(129, 38, '2025-08-06 10:01:46', 'Late', 'general', NULL, NULL, NULL, 1, NULL),
(147, 42, '2025-08-06 12:09:12', 'On Time', 'general', NULL, 0, 1, 1, 23),
(148, 43, '2025-08-06 12:11:05', 'Late', 'general', NULL, 0, 1, 1, 23),
(149, 43, '2025-08-06 12:50:39', 'On Time', 'general', NULL, 1, 1, 1, 23),
(0, 29, '2025-08-16 05:06:30', 'Late', 'general', NULL, 1, 1, 1, 25),
(0, 28, '2025-08-16 05:06:45', 'On Time', 'general', NULL, 0, 1, 1, 25),
(0, 0, '2025-08-16 05:24:01', 'On Time', 'general', NULL, 1, 1, 1, 25),
(0, 51, '2025-08-16 05:33:22', 'On Time', 'general', NULL, 0, 1, 1, 25),
(0, 29, '2025-08-16 05:33:39', 'On Time', 'general', NULL, 0, 1, 1, 25),
(0, 0, '2025-08-16 05:35:26', 'On Time', 'general', NULL, 7, 1, 1, 23),
(0, 43, '2025-08-16 05:35:36', 'On Time', 'general', NULL, 0, 1, 1, 23),
(0, 0, '2025-08-16 06:31:51', 'On Time', 'general', NULL, 0, 1, 1, 23),
(0, 0, '2025-08-16 07:12:06', 'On Time', 'general', NULL, 7, 0, 1, 23),
(0, 0, '2025-08-16 09:29:08', 'Late', 'general', NULL, 6, 4, 2, 24),
(0, 0, '2025-08-16 09:29:46', 'On Time', 'general', NULL, 6, 3, 2, 24);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_courses`
--

CREATE TABLE `tbl_courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_courses`
--

INSERT INTO `tbl_courses` (`course_id`, `course_name`, `user_id`, `school_id`, `created_at`) VALUES
(27, 'ICT', 24, 2, '2025-08-06 10:25:14'),
(28, 'HUMMS', 24, 2, '2025-08-06 14:49:49'),
(31, 'STEM 12', 1, 1, '2025-08-06 15:38:03'),
(32, '12 HUMMS -', 25, 1, '2025-08-06 16:39:54'),
(33, '12 HUMMS', 1, 1, '2025-08-06 16:40:20'),
(34, '12', 25, 1, '2025-08-06 17:04:41'),
(35, '12 PRADA', 1, 1, '2025-08-06 17:05:19'),
(36, '12 GUCCI', 25, 1, '2025-08-06 17:07:08'),
(0, 'HUMMS', 25, 1, '2025-08-15 07:14:12'),
(0, 'BSIT 402', 24, 2, '2025-08-15 18:39:26'),
(0, 'BSIT 302', 23, 1, '2025-08-15 18:42:37'),
(0, 'BSIS', 23, 1, '2025-08-16 04:05:07'),
(0, 'ICT', 23, 1, '2025-08-16 04:06:41'),
(0, 'BSIT 302', 25, 1, '2025-08-16 05:08:09');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_face_recognition_logs`
--

CREATE TABLE `tbl_face_recognition_logs` (
  `log_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `recognition_status` enum('success','failed') NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `school_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_face_verification_logs`
--

CREATE TABLE `tbl_face_verification_logs` (
  `log_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `student_name` varchar(255) NOT NULL,
  `status` enum('Success','Failed') NOT NULL,
  `verification_time` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `school_id` int(11) DEFAULT 1,
  `user_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_face_verification_logs`
--

INSERT INTO `tbl_face_verification_logs` (`log_id`, `student_id`, `student_name`, `status`, `verification_time`, `ip_address`, `user_agent`, `notes`, `school_id`, `user_id`) VALUES
(1, NULL, 'Alexander Joerenz Escallente', 'Success', '2025-06-23 19:46:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(2, NULL, 'BARNEY', 'Success', '2025-07-25 02:01:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(3, NULL, 'BARNEY', 'Success', '2025-07-25 02:03:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(4, NULL, 'BARNEY', 'Success', '2025-07-25 02:06:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(5, NULL, 'BARNEY', 'Success', '2025-07-25 02:06:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(6, NULL, 'BARNEY', 'Success', '2025-07-25 02:09:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(7, NULL, 'CONG', 'Success', '2025-07-25 02:10:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(8, NULL, 'CONG', 'Success', '2025-07-25 02:13:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(9, NULL, 'CONG', 'Success', '2025-07-25 02:14:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(10, NULL, 'CONG', 'Success', '2025-07-25 02:15:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(11, NULL, 'PATRICK STAR', 'Success', '2025-07-27 18:18:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(12, NULL, 'PATRICK STAR', 'Failed', '2025-07-27 18:18:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Failed to capture face during registration', 1, 1),
(13, NULL, 'PATRICK STAR', 'Success', '2025-07-27 18:19:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(14, NULL, 'PATRICK STAR', 'Failed', '2025-07-27 18:19:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Failed to capture face during registration', 1, 1),
(15, NULL, 'PATRICK STAR', 'Success', '2025-07-27 18:19:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(16, NULL, 'PATRICK STAR', 'Success', '2025-07-27 18:19:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(17, NULL, 'Spongebob', 'Success', '2025-07-27 22:02:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(18, NULL, 'Shane Harvey', 'Success', '2025-07-30 20:30:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(19, NULL, 'Harvey', 'Success', '2025-07-30 21:08:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(20, NULL, 'Harvey', 'Success', '2025-07-30 21:13:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(21, NULL, 'Spongebob', 'Success', '2025-07-30 21:16:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(22, NULL, 'BARNEY', 'Success', '2025-07-30 22:07:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(23, NULL, 'BARNEY', 'Success', '2025-07-30 23:12:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(24, NULL, 'BARNEY', 'Success', '2025-07-30 23:14:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(25, NULL, 'BARNEY', 'Success', '2025-07-30 23:17:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(26, NULL, 'BARNEY', 'Success', '2025-07-30 23:18:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(27, NULL, 'BARNEY', 'Success', '2025-07-30 23:20:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(28, NULL, 'BARNEY', 'Success', '2025-07-30 23:22:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(29, NULL, 'BARNEY', 'Success', '2025-07-30 23:27:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(30, NULL, 'BARNEY', 'Success', '2025-07-30 23:31:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(31, NULL, 'BARNEY', 'Success', '2025-07-30 23:36:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(32, NULL, 'PATRICK STAR', 'Success', '2025-07-30 23:57:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(33, NULL, 'Arnold Aranaydo', 'Success', '2025-07-31 00:18:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(34, NULL, 'SQUIDWARD', 'Success', '2025-07-31 00:31:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(35, NULL, 'Sandy', 'Success', '2025-07-31 00:32:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(36, NULL, 'MR. KRABS', 'Success', '2025-07-31 01:00:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(37, NULL, 'mr. beast', 'Success', '2025-07-31 01:01:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(38, NULL, 'Harvey', 'Success', '2025-07-31 01:08:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(39, NULL, 'Mrs. Puffs', 'Success', '2025-08-01 23:15:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(40, NULL, 'PLANKTON', 'Success', '2025-08-02 21:39:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(41, NULL, 'PERY', 'Success', '2025-08-02 21:44:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(42, NULL, 'PERY', 'Success', '2025-08-02 21:45:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(43, NULL, 'GARY', 'Success', '2025-08-02 21:53:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(44, NULL, 'PHINEAS', 'Success', '2025-08-02 21:56:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(45, NULL, 'GARY', 'Success', '2025-08-02 22:17:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(46, NULL, 'GARYY', 'Success', '2025-08-02 22:26:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(47, NULL, 'GARYy', 'Success', '2025-08-02 22:44:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(48, NULL, 'GARYy', 'Success', '2025-08-02 23:05:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(49, NULL, 'krabs', 'Success', '2025-08-02 23:16:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(50, NULL, 'simpson', 'Success', '2025-08-03 00:08:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(51, NULL, 'PHINEAS', 'Success', '2025-08-03 01:50:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(52, NULL, 'FERB', 'Success', '2025-08-03 02:37:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(53, NULL, 'plattypus', 'Success', '2025-08-05 09:18:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(54, NULL, 'PEPITO', 'Success', '2025-08-05 11:57:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(55, NULL, 'PEPITO', 'Success', '2025-08-05 12:02:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(56, NULL, 'PEPITO', 'Success', '2025-08-05 12:06:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(57, NULL, 'PEPITO', 'Success', '2025-08-05 12:10:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(58, NULL, 'ELSA', 'Success', '2025-08-05 12:33:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(59, NULL, 'elsa', 'Success', '2025-08-05 12:41:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(60, NULL, 'PEPITO', 'Success', '2025-08-05 14:57:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(61, NULL, 'PEPITO', 'Success', '2025-08-06 09:26:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(62, NULL, 'CHOU', 'Success', '2025-08-06 17:20:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(63, NULL, 'chou', 'Success', '2025-08-06 17:21:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(64, NULL, 'CHOU', 'Success', '2025-08-06 17:22:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(65, NULL, 'open', 'Success', '2025-08-06 18:25:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(66, NULL, 'close', 'Success', '2025-08-06 19:14:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(67, NULL, 'close', 'Success', '2025-08-06 20:04:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(68, NULL, 'BARNEY', 'Success', '2025-08-06 20:08:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(69, NULL, 'DINOSAUR', 'Success', '2025-08-06 20:10:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(70, NULL, 'stuart', 'Success', '2025-08-06 22:49:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(71, NULL, 'PERRY', 'Success', '2025-08-06 23:00:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(72, NULL, 'Harvey Flores', 'Success', '2025-08-06 23:04:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(73, NULL, 'OCTO', 'Success', '2025-08-06 23:07:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(74, NULL, 'octo', 'Success', '2025-08-06 23:29:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(75, NULL, 'barista', 'Success', '2025-08-07 00:28:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(76, NULL, 'BARNEYUY', 'Success', '2025-08-07 00:39:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(77, NULL, 'ESTRA', 'Success', '2025-08-07 00:43:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(78, NULL, 'MEOW', 'Success', '2025-08-07 01:04:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(79, NULL, 'MEWOE', 'Success', '2025-08-07 01:06:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(0, NULL, 'deploy', 'Success', '2025-08-15 15:14:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(0, NULL, 'APPLIED', 'Success', '2025-08-15 15:14:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 1, 1),
(0, NULL, 'alice', 'Success', '2025-08-15 15:50:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 1, 25),
(0, NULL, 'oppo', 'Success', '2025-08-16 02:39:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 2, 24),
(0, NULL, 'rabbit', 'Success', '2025-08-16 02:42:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 1, 23),
(0, NULL, 'thomas', 'Success', '2025-08-16 12:05:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 1, 23),
(0, NULL, 'BARNEYYYY', 'Success', '2025-08-16 12:06:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 1, 23),
(0, NULL, 'candice', 'Success', '2025-08-16 13:08:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 1, 25),
(0, NULL, 'NANA', 'Success', '2025-08-16 14:32:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 1, 23),
(0, NULL, 'shojo', 'Success', '2025-08-16 17:28:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 2, 24);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_instructors`
--

CREATE TABLE `tbl_instructors` (
  `instructor_id` int(11) NOT NULL,
  `instructor_name` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `school_id` int(11) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_instructors`
--

INSERT INTO `tbl_instructors` (`instructor_id`, `instructor_name`, `subject`, `created_at`, `school_id`, `user_id`) VALUES
(6, 'alex', '', '2025-07-31 13:22:35', 2, NULL),
(7, 'escall', '', '2025-07-31 13:50:50', 1, NULL),
(8, 'ara', '', '2025-08-01 17:21:50', 1, NULL),
(24, 'alex', '', '2025-08-06 11:54:02', 1, NULL),
(23, 'escall', '', '2025-08-16 04:00:35', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_instructor_subjects`
--

CREATE TABLE `tbl_instructor_subjects` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `school_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_instructor_subjects`
--

INSERT INTO `tbl_instructor_subjects` (`id`, `instructor_id`, `subject_id`, `created_at`, `school_id`) VALUES
(0, 0, 0, '2025-08-16 07:10:13', 1),
(0, 0, 2, '2025-08-16 07:40:52', 1),
(0, 0, 3, '2025-08-16 07:44:15', 1),
(0, 0, 4, '2025-08-16 07:44:27', 1),
(0, 0, 5, '2025-08-16 07:45:59', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sections`
--

CREATE TABLE `tbl_sections` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `course_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_sections`
--

INSERT INTO `tbl_sections` (`section_id`, `section_name`, `user_id`, `school_id`, `created_at`, `course_id`) VALUES
(195, '12', 24, 2, '2025-08-06 10:25:14', 27),
(198, 'VERSI', 1, 1, '2025-08-06 15:38:03', 31),
(199, 'HUMANOIDS', 25, 1, '2025-08-06 16:39:54', 32),
(200, 'HUMANOIDS', 1, 1, '2025-08-06 16:40:20', NULL),
(201, 'GUCCI PRADA', 25, 1, '2025-08-06 17:04:41', 34),
(202, 'GUCCI', 1, 1, '2025-08-06 17:05:19', NULL),
(203, 'VERSACE', 25, 1, '2025-08-06 17:07:08', 36),
(0, '12 HUMANIDADES', 24, 2, '2025-08-16 09:28:32', 28);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_student`
--

CREATE TABLE `tbl_student` (
  `tbl_student_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `course_section` varchar(255) NOT NULL,
  `generated_code` varchar(255) NOT NULL,
  `face_image` varchar(255) DEFAULT NULL,
  `face_image_path` varchar(255) DEFAULT NULL,
  `school_id` int(11) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_student`
--

INSERT INTO `tbl_student` (`tbl_student_id`, `student_name`, `course_section`, `generated_code`, `face_image`, `face_image_path`, `school_id`, `user_id`) VALUES
(12, 'Arnold Aranaydo', 'BSIT-402', 'STU-12-e28ba74e-691a3864', NULL, 'face_1753892319_688a45dfca11b.jpg', 2, 23),
(28, 'PHINEAS', 'HUMMS - 12', 'STU-25-1-f204bae5-e0cf4fa6849479d6', NULL, 'face_1754157041_688e4ff1c4643.jpg', 1, 25),
(29, 'FERB', 'ICT - 11', 'STU-25-1-ce1b8fa0-ecd57c5a246d375f', NULL, 'face_1754159855_688e5aefbae9f.jpg', 1, 25),
(30, 'plattypus', 'BIKINI BOTTOM', 'BIKINI|BOTTOM|1', NULL, 'face_1754356692_68915bd487dd3.jpg', 2, 24),
(39, 'open', 'ICT - 12', 'STU-24-2-840ffe22-4366d63f70194784', NULL, 'face_1754475914_68932d8a89768.jpg', 2, 24),
(41, 'close', 'BSIT-402', 'STU-24-2-5b31f9b3-6241b5939f374e36', NULL, 'face_1754481876_689344d4c8b68.jpg', 2, 24),
(43, 'DINOSAUR', 'ICT - 11 AGHIMUAN', 'STU-23-1-51be2143-a3c4f622bc8b30f6', NULL, 'face_1754482252_6893464cef668.jpg', 1, 23),
(45, 'PERRY', 'HUMMS - 12 HUMANIDADES', 'STU-24-2-62c1c135-6419d1d559b504fe', NULL, 'face_1754492441_68936e19530d3.jpg', 2, 24),
(46, 'Harvey Flores', 'ICT - 11 AGHIMUAN', 'STU-24-2-41f23ec1-e556e0733a0f8edf', NULL, 'face_1754492673_68936f01571fb.jpg', 2, 24),
(48, 'octo', 'STEM 12 - VERSI', 'STU-24-2-121e3d79-6458d7d6ebe40a8c', NULL, 'face_1754494172_689374dc40ed2.jpg', 2, 24),
(51, 'MEWOE', '12 GUCCI - VERSACE', 'STU-25-1-5bbda5e8-a69bde578ac5ad2c', NULL, 'face_1754500028_68938bbcee71a.jpg', 1, 25),
(0, 'BARNEYYYY', 'ICT - 11 AGHIMUAN', 'STU-23-1-5c78fcc6-6c310c2aac20bceb', NULL, 'face_1755317201_68a003d11b85b.jpg', 1, 23),
(0, 'candice', 'BSIT 302 - SIGALOT', 'STU-25-1-40593fe0-aef171722d487895', NULL, 'face_1755320889_68a01239bb179.jpg', 1, 25),
(0, 'NANA', 'BSIT 302 - SIGALOT', 'STU-23-1-47eb359c-4e183ee5484254fd', NULL, 'face_1755325978_68a0261a52e6a.jpg', 1, 23),
(0, 'shojo', 'HUMMS - 12 HUMANIDADES', 'STU-24-2-0b878762-f7bce6dd70fee87d', NULL, 'face_1755336512_68a04f400fb3c.jpg', 2, 24);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_subjects`
--

CREATE TABLE `tbl_subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `school_id` int(11) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_subjects`
--

INSERT INTO `tbl_subjects` (`subject_id`, `subject_name`, `created_at`, `school_id`, `user_id`) VALUES
(1, 'NUCLEAR PHYSICS', '2025-08-16 07:24:30', 1, 23),
(2, 'Physical Quantumine', '2025-08-16 07:40:52', 1, 23),
(3, 'Networking', '2025-08-16 07:44:15', 2, 24),
(4, 'Quantum Physics', '2025-08-16 07:44:27', 2, 24),
(5, 'ATOMIC PHYSICS', '2025-08-16 07:45:59', 1, 25);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_holidays`
--

CREATE TABLE `teacher_holidays` (
  `id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(255) NOT NULL,
  `holiday_type` enum('national','school','personal') DEFAULT 'school',
  `school_id` int(11) DEFAULT 1,
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_holidays`
--

INSERT INTO `teacher_holidays` (`id`, `holiday_date`, `holiday_name`, `holiday_type`, `school_id`, `created_by`, `created_at`) VALUES
(1, '2024-01-01', 'New Year\'s Day', 'national', 1, NULL, '2025-07-27 08:46:39'),
(2, '2024-04-09', 'Day of Valor (Araw ng Kagitingan)', 'national', 1, NULL, '2025-07-27 08:46:39'),
(3, '2024-05-01', 'Labor Day', 'national', 1, NULL, '2025-07-27 08:46:39'),
(4, '2024-06-12', 'Independence Day', 'national', 1, NULL, '2025-07-27 08:46:39'),
(5, '2024-08-21', 'Ninoy Aquino Day', 'national', 1, NULL, '2025-07-27 08:46:39'),
(6, '2024-08-30', 'National Heroes Day', 'national', 1, NULL, '2025-07-27 08:46:39'),
(7, '2024-11-01', 'All Saints\' Day', 'national', 1, NULL, '2025-07-27 08:46:39'),
(8, '2024-11-02', 'All Souls\' Day', 'national', 1, NULL, '2025-07-27 08:46:39'),
(9, '2024-11-30', 'Bonifacio Day', 'national', 1, NULL, '2025-07-27 08:46:39'),
(10, '2024-12-24', 'Christmas Eve', 'national', 1, NULL, '2025-07-27 08:46:39'),
(11, '2024-12-25', 'Christmas Day', 'national', 1, NULL, '2025-07-27 08:46:39'),
(12, '2024-12-30', 'Rizal Day', 'national', 1, NULL, '2025-07-27 08:46:39'),
(13, '2024-12-31', 'New Year\'s Eve', 'national', 1, NULL, '2025-07-27 08:46:39');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_schedules`
--

CREATE TABLE `teacher_schedules` (
  `id` int(11) NOT NULL,
  `teacher_username` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `section` varchar(100) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(100) DEFAULT NULL,
  `school_id` int(11) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_schedules`
--

INSERT INTO `teacher_schedules` (`id`, `teacher_username`, `subject`, `section`, `day_of_week`, `start_time`, `end_time`, `room`, `school_id`, `user_id`, `created_at`, `updated_at`, `status`) VALUES
(23, 'escall', 'Web Tech', 'HUMMS - 12', 'Monday', '08:00:00', '12:00:00', 'Computer Laboratory', 1, 23, '2025-07-27 10:02:40', '2025-08-09 09:56:35', 'inactive'),
(24, 'alex', 'Discrete Mathematics', 'BSIT-302', 'Friday', '17:00:00', '19:00:00', 'Massage Room', 2, 24, '2025-07-27 10:11:48', '2025-08-09 09:59:38', 'inactive'),
(26, 'alex', 'Web Dev', 'BSIT-302', 'Monday', '07:00:00', '22:00:00', 'Dress Room', 2, 24, '2025-07-30 16:51:22', '2025-08-09 09:59:38', 'inactive'),
(32, 'alex', 'Capstone 2', 'BSIT-302', 'Tuesday', '08:00:00', '12:00:00', 'Computer Laboratory', 2, 24, '2025-08-01 06:27:57', '2025-08-09 09:59:38', 'inactive'),
(33, 'escall', 'Networking', 'BSIT-301', 'Friday', '08:00:00', '09:00:00', '', 1, 23, '2025-08-01 16:34:58', '2025-08-09 09:56:35', 'inactive'),
(0, 'ara', 'java', 'BSIT - 301', 'Monday', '07:00:00', '12:00:00', 'Computer Laboratory', 1, 25, '2025-08-09 10:33:15', '2025-08-09 10:34:51', 'inactive'),
(0, 'ara', 'APP DEV', '11 ICT - AGHIMUANINANES', 'Tuesday', '09:00:00', '11:30:00', 'Room 1', 1, 25, '2025-08-09 10:34:08', '2025-08-09 10:34:51', 'inactive'),
(0, 'alex', 'Web Tech', 'ICT 11 - AGHIMUAN', 'Monday', '09:00:00', '12:00:00', 'Computer Laboratory', 2, 24, '2025-08-11 03:03:45', '2025-08-11 03:04:29', 'inactive'),
(0, 'escall', 'Web Tech', 'ICT 11 - AGHIMUAN', 'Monday', '12:00:00', '13:00:00', 'Computer Laboratory', 1, 23, '2025-08-11 03:07:10', '2025-08-11 03:07:22', 'inactive'),
(0, 'alex', 'Networking', 'IT - CAPSTONE 1', 'Tuesday', '12:00:00', '13:00:00', 'Computer Laboratory', 2, 24, '2025-08-11 03:09:00', '2025-08-11 03:09:14', 'inactive'),
(0, 'alex', 'Networking', 'ICT 11 - AGHIMUANANO', 'Saturday', '14:00:00', '17:00:00', 'Computer Laboratory', 2, 24, '2025-08-11 06:02:47', '2025-08-11 06:02:47', 'active'),
(0, 'alex', 'Quantum Physics', 'BSIT 402 - PERIPHALS', 'Wednesday', '15:00:00', '20:00:00', 'CANTEEN', 2, 24, '2025-08-11 11:38:21', '2025-08-11 11:38:21', 'active'),
(0, 'escall', 'Physical Quantumine', 'BSIT 302 - SIGALOT', 'Saturday', '18:00:00', '21:00:00', '2ND FLOOR', 1, 23, '2025-08-11 11:39:46', '2025-08-11 11:39:46', 'active'),
(0, 'escall', 'NUCLEAR PHYSICS', 'BSIT - 302', 'Saturday', '15:00:00', '17:30:00', 'NUCLEAR LAB', 1, 23, '2025-08-16 06:31:00', '2025-08-16 06:31:00', 'active'),
(0, 'ara', 'ATOMIC PHYSICS', 'KINDER 202 - MAGALANG', 'Monday', '12:00:00', '14:00:00', 'ATOMIC SITE', 1, 25, '2025-08-16 07:45:53', '2025-08-16 07:45:53', 'active');

--
-- Triggers `teacher_schedules`
--
DELIMITER $$
CREATE TRIGGER `teacher_schedule_after_delete` AFTER DELETE ON `teacher_schedules` FOR EACH ROW BEGIN
    INSERT INTO teacher_schedule_logs (schedule_id, action, old_values, changed_by)
    VALUES (OLD.id, 'DELETE', JSON_OBJECT(
        'teacher_username', OLD.teacher_username,
        'subject', OLD.subject,
        'section', OLD.section,
        'day_of_week', OLD.day_of_week,
        'start_time', OLD.start_time,
        'end_time', OLD.end_time,
        'room', OLD.room,
        'school_id', OLD.school_id
    ), USER());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `teacher_schedule_after_insert` AFTER INSERT ON `teacher_schedules` FOR EACH ROW BEGIN
    INSERT INTO teacher_schedule_logs (schedule_id, action, new_values, changed_by)
    VALUES (NEW.id, 'INSERT', JSON_OBJECT(
        'teacher_username', NEW.teacher_username,
        'subject', NEW.subject,
        'section', NEW.section,
        'day_of_week', NEW.day_of_week,
        'start_time', NEW.start_time,
        'end_time', NEW.end_time,
        'room', NEW.room,
        'school_id', NEW.school_id
    ), USER());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `teacher_schedule_after_update` AFTER UPDATE ON `teacher_schedules` FOR EACH ROW BEGIN
    INSERT INTO teacher_schedule_logs (schedule_id, action, old_values, new_values, changed_by)
    VALUES (NEW.id, 'UPDATE', JSON_OBJECT(
        'teacher_username', OLD.teacher_username,
        'subject', OLD.subject,
        'section', OLD.section,
        'day_of_week', OLD.day_of_week,
        'start_time', OLD.start_time,
        'end_time', OLD.end_time,
        'room', OLD.room,
        'school_id', OLD.school_id
    ), JSON_OBJECT(
        'teacher_username', NEW.teacher_username,
        'subject', NEW.subject,
        'section', NEW.section,
        'day_of_week', NEW.day_of_week,
        'start_time', NEW.start_time,
        'end_time', NEW.end_time,
        'room', NEW.room,
        'school_id', NEW.school_id
    ), USER());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `teacher_schedule_conflicts`
-- (See below for the actual view)
--
CREATE TABLE `teacher_schedule_conflicts` (
`schedule1_id` int(11)
,`teacher1` varchar(255)
,`subject1` varchar(255)
,`section1` varchar(100)
,`day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')
,`start1` time
,`end1` time
,`room1` varchar(100)
,`schedule2_id` int(11)
,`teacher2` varchar(255)
,`subject2` varchar(255)
,`section2` varchar(100)
,`start2` time
,`end2` time
,`room2` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_schedule_logs`
--

CREATE TABLE `teacher_schedule_logs` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `changed_by` varchar(255) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_schedule_logs`
--

INSERT INTO `teacher_schedule_logs` (`id`, `schedule_id`, `action`, `old_values`, `new_values`, `changed_by`, `changed_at`) VALUES
(1, 19, 'INSERT', NULL, '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"bsit 302\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', 'root@localhost', '2025-07-27 09:28:17'),
(2, 19, 'UPDATE', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"bsit 302\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', '{\"teacher_username\": \"escall\", \"subject\": \"Web Technology\", \"section\": \"bsit 302\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', 'root@localhost', '2025-07-27 09:29:25'),
(3, 20, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Quantitaive Methods\", \"section\": \"bsit 301\", \"day_of_week\": \"Tuesday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Room 1\", \"school_id\": 2}', 'root@localhost', '2025-07-27 09:30:52'),
(4, 21, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"bsit 301\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"22:00:00\", \"room\": \"Upper Stage\", \"school_id\": 2}', 'root@localhost', '2025-07-27 09:31:24'),
(5, 22, 'INSERT', NULL, '{\"teacher_username\": \"escall\", \"subject\": \"Quantitaive Methods\", \"section\": \"bsit 301\", \"day_of_week\": \"Monday\", \"start_time\": \"13:00:00\", \"end_time\": \"14:00:00\", \"room\": \"Upper Stage\", \"school_id\": 1}', 'root@localhost', '2025-07-27 09:55:28'),
(6, 1, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Mathematics\", \"section\": \"BSIT-1A\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:30:00\", \"room\": \"Room 101\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(7, 2, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Physics\", \"section\": \"BSIT-1B\", \"day_of_week\": \"Monday\", \"start_time\": \"10:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Room 102\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(8, 3, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Computer Science\", \"section\": \"BSIT-2A\", \"day_of_week\": \"Tuesday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:30:00\", \"room\": \"Computer Lab\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(9, 4, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Programming\", \"section\": \"BSIT-2B\", \"day_of_week\": \"Tuesday\", \"start_time\": \"10:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Computer Lab\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(10, 5, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Database Management\", \"section\": \"BSIT-3A\", \"day_of_week\": \"Wednesday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:30:00\", \"room\": \"Room 103\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(11, 6, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Web Development\", \"section\": \"BSIT-3B\", \"day_of_week\": \"Wednesday\", \"start_time\": \"10:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Computer Lab\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(12, 7, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Mathematics\", \"section\": \"BSIT-1A\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:30:00\", \"room\": \"Room 101\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(13, 8, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Physics\", \"section\": \"BSIT-1B\", \"day_of_week\": \"Monday\", \"start_time\": \"10:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Room 102\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(14, 9, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Computer Science\", \"section\": \"BSIT-2A\", \"day_of_week\": \"Tuesday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:30:00\", \"room\": \"Computer Lab\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(15, 10, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Programming\", \"section\": \"BSIT-2B\", \"day_of_week\": \"Tuesday\", \"start_time\": \"10:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Computer Lab\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(16, 11, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Database Management\", \"section\": \"BSIT-3A\", \"day_of_week\": \"Wednesday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:30:00\", \"room\": \"Room 103\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(17, 12, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Web Development\", \"section\": \"BSIT-3B\", \"day_of_week\": \"Wednesday\", \"start_time\": \"10:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Computer Lab\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(18, 13, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Mathematics\", \"section\": \"BSIT-1A\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:30:00\", \"room\": \"Room 101\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(19, 14, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Physics\", \"section\": \"BSIT-1B\", \"day_of_week\": \"Monday\", \"start_time\": \"10:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Room 102\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(20, 15, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Computer Science\", \"section\": \"BSIT-2A\", \"day_of_week\": \"Tuesday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:30:00\", \"room\": \"Computer Lab\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(21, 16, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Programming\", \"section\": \"BSIT-2B\", \"day_of_week\": \"Tuesday\", \"start_time\": \"10:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Computer Lab\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(22, 17, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Database Management\", \"section\": \"BSIT-3A\", \"day_of_week\": \"Wednesday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:30:00\", \"room\": \"Room 103\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(23, 18, 'DELETE', '{\"teacher_username\": \"teacher1\", \"subject\": \"Web Development\", \"section\": \"BSIT-3B\", \"day_of_week\": \"Wednesday\", \"start_time\": \"10:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Computer Lab\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(24, 19, 'DELETE', '{\"teacher_username\": \"escall\", \"subject\": \"Web Technology\", \"section\": \"bsit 302\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(25, 20, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Quantitaive Methods\", \"section\": \"bsit 301\", \"day_of_week\": \"Tuesday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Room 1\", \"school_id\": 2}', NULL, 'root@localhost', '2025-07-27 10:02:16'),
(26, 21, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"bsit 301\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"22:00:00\", \"room\": \"Upper Stage\", \"school_id\": 2}', NULL, 'root@localhost', '2025-07-27 10:02:17'),
(27, 22, 'DELETE', '{\"teacher_username\": \"escall\", \"subject\": \"Quantitaive Methods\", \"section\": \"bsit 301\", \"day_of_week\": \"Monday\", \"start_time\": \"13:00:00\", \"end_time\": \"14:00:00\", \"room\": \"Upper Stage\", \"school_id\": 1}', NULL, 'root@localhost', '2025-07-27 10:02:17'),
(28, 23, 'INSERT', NULL, '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"bsit 302\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', 'root@localhost', '2025-07-27 10:02:40'),
(29, 24, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Quantitaive Methods\", \"section\": \"BSIT-302\", \"day_of_week\": \"Friday\", \"start_time\": \"17:00:00\", \"end_time\": \"19:00:00\", \"room\": \"Massage Room\", \"school_id\": 2}', 'root@localhost', '2025-07-27 10:11:48'),
(30, 23, 'UPDATE', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"bsit 302\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"bsit 302\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', 'root@localhost', '2025-07-30 15:57:19'),
(31, 24, 'UPDATE', '{\"teacher_username\": \"alex\", \"subject\": \"Quantitaive Methods\", \"section\": \"BSIT-302\", \"day_of_week\": \"Friday\", \"start_time\": \"17:00:00\", \"end_time\": \"19:00:00\", \"room\": \"Massage Room\", \"school_id\": 2}', '{\"teacher_username\": \"alex\", \"subject\": \"Quantitaive Methods\", \"section\": \"BSIT-302\", \"day_of_week\": \"Friday\", \"start_time\": \"17:00:00\", \"end_time\": \"19:00:00\", \"room\": \"Massage Room\", \"school_id\": 2}', 'root@localhost', '2025-07-30 15:57:19'),
(32, 25, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Oral Communications\", \"section\": \"BSIT-302\", \"day_of_week\": \"Monday\", \"start_time\": \"07:00:00\", \"end_time\": \"10:00:00\", \"room\": \"Room 1\", \"school_id\": 2}', 'root@localhost', '2025-07-30 16:40:39'),
(33, 26, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Web Dev\", \"section\": \"BSIT-302\", \"day_of_week\": \"Monday\", \"start_time\": \"07:00:00\", \"end_time\": \"22:00:00\", \"room\": \"Dress Room\", \"school_id\": 2}', 'root@localhost', '2025-07-30 16:51:22'),
(34, 25, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Oral Communications\", \"section\": \"BSIT-302\", \"day_of_week\": \"Monday\", \"start_time\": \"07:00:00\", \"end_time\": \"10:00:00\", \"room\": \"Room 1\", \"school_id\": 2}', NULL, 'root@localhost', '2025-07-30 16:52:27'),
(35, 27, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Web Tech\", \"section\": \"bsit 302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-01 02:39:53'),
(36, 27, 'UPDATE', '{\"teacher_username\": \"alex\", \"subject\": \"Web Tech\", \"section\": \"bsit 302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', '{\"teacher_username\": \"alex\", \"subject\": \"Web Tech\", \"section\": \"bsit 302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-01 02:41:00'),
(37, 27, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Web Tech\", \"section\": \"bsit 302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-01 02:50:38'),
(38, 28, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-01 02:51:44'),
(39, 28, 'UPDATE', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-01 02:52:02'),
(40, 28, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-01 03:05:05'),
(41, 29, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-01 03:05:34'),
(42, 29, 'UPDATE', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-01 03:05:52'),
(43, 29, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-01 03:13:02'),
(44, 30, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-01 03:13:33'),
(45, 30, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-01 03:13:45'),
(46, 31, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Upper Stage\", \"school_id\": 2}', 'root@localhost', '2025-08-01 03:37:20'),
(47, 31, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"07:00:00\", \"end_time\": \"11:00:00\", \"room\": \"Upper Stage\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-01 03:39:09'),
(48, 32, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Capstone 2\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-01 06:27:57'),
(49, 23, 'UPDATE', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"bsit 302\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"HUMMS - 12\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', 'root@localhost', '2025-08-01 15:42:14'),
(50, 33, 'INSERT', NULL, '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"BSIT-301\", \"day_of_week\": \"Friday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:00:00\", \"room\": null, \"school_id\": 1}', 'root@localhost', '2025-08-01 16:34:58'),
(51, 33, 'UPDATE', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"BSIT-301\", \"day_of_week\": \"Friday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:00:00\", \"room\": null, \"school_id\": 1}', '{\"teacher_username\": \"escall\", \"subject\": \"Networking\", \"section\": \"BSIT-301\", \"day_of_week\": \"Friday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:00:00\", \"room\": \"\", \"school_id\": 1}', 'root@localhost', '2025-08-01 16:45:07'),
(52, 24, 'UPDATE', '{\"teacher_username\": \"alex\", \"subject\": \"Quantitaive Methods\", \"section\": \"BSIT-302\", \"day_of_week\": \"Friday\", \"start_time\": \"17:00:00\", \"end_time\": \"19:00:00\", \"room\": \"Massage Room\", \"school_id\": 2}', '{\"teacher_username\": \"alex\", \"subject\": \"Discrete Mathematics\", \"section\": \"BSIT-302\", \"day_of_week\": \"Friday\", \"start_time\": \"17:00:00\", \"end_time\": \"19:00:00\", \"room\": \"Massage Room\", \"school_id\": 2}', 'root@localhost', '2025-08-02 16:24:57'),
(0, 23, 'UPDATE', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"HUMMS - 12\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"HUMMS - 12\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', 'root@localhost', '2025-08-09 09:56:35'),
(0, 33, 'UPDATE', '{\"teacher_username\": \"escall\", \"subject\": \"Networking\", \"section\": \"BSIT-301\", \"day_of_week\": \"Friday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:00:00\", \"room\": \"\", \"school_id\": 1}', '{\"teacher_username\": \"escall\", \"subject\": \"Networking\", \"section\": \"BSIT-301\", \"day_of_week\": \"Friday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:00:00\", \"room\": \"\", \"school_id\": 1}', 'root@localhost', '2025-08-09 09:56:35'),
(0, 24, 'UPDATE', '{\"teacher_username\": \"alex\", \"subject\": \"Discrete Mathematics\", \"section\": \"BSIT-302\", \"day_of_week\": \"Friday\", \"start_time\": \"17:00:00\", \"end_time\": \"19:00:00\", \"room\": \"Massage Room\", \"school_id\": 2}', '{\"teacher_username\": \"alex\", \"subject\": \"Discrete Mathematics\", \"section\": \"BSIT-302\", \"day_of_week\": \"Friday\", \"start_time\": \"17:00:00\", \"end_time\": \"19:00:00\", \"room\": \"Massage Room\", \"school_id\": 2}', 'root@localhost', '2025-08-09 09:59:38'),
(0, 26, 'UPDATE', '{\"teacher_username\": \"alex\", \"subject\": \"Web Dev\", \"section\": \"BSIT-302\", \"day_of_week\": \"Monday\", \"start_time\": \"07:00:00\", \"end_time\": \"22:00:00\", \"room\": \"Dress Room\", \"school_id\": 2}', '{\"teacher_username\": \"alex\", \"subject\": \"Web Dev\", \"section\": \"BSIT-302\", \"day_of_week\": \"Monday\", \"start_time\": \"07:00:00\", \"end_time\": \"22:00:00\", \"room\": \"Dress Room\", \"school_id\": 2}', 'root@localhost', '2025-08-09 09:59:38'),
(0, 32, 'UPDATE', '{\"teacher_username\": \"alex\", \"subject\": \"Capstone 2\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', '{\"teacher_username\": \"alex\", \"subject\": \"Capstone 2\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-09 09:59:38'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"ara\", \"subject\": \"java\", \"section\": \"BSIT - 301\", \"day_of_week\": \"Monday\", \"start_time\": \"07:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', 'root@localhost', '2025-08-09 10:33:15'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"ara\", \"subject\": \"APP DEV\", \"section\": \"11 ICT - AGHIMUANINANES\", \"day_of_week\": \"Tuesday\", \"start_time\": \"09:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Room 1\", \"school_id\": 1}', 'root@localhost', '2025-08-09 10:34:08'),
(0, 0, 'UPDATE', '{\"teacher_username\": \"ara\", \"subject\": \"java\", \"section\": \"BSIT - 301\", \"day_of_week\": \"Monday\", \"start_time\": \"07:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', '{\"teacher_username\": \"ara\", \"subject\": \"java\", \"section\": \"BSIT - 301\", \"day_of_week\": \"Monday\", \"start_time\": \"07:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', 'root@localhost', '2025-08-09 10:34:51'),
(0, 0, 'UPDATE', '{\"teacher_username\": \"ara\", \"subject\": \"APP DEV\", \"section\": \"11 ICT - AGHIMUANINANES\", \"day_of_week\": \"Tuesday\", \"start_time\": \"09:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Room 1\", \"school_id\": 1}', '{\"teacher_username\": \"ara\", \"subject\": \"APP DEV\", \"section\": \"11 ICT - AGHIMUANINANES\", \"day_of_week\": \"Tuesday\", \"start_time\": \"09:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Room 1\", \"school_id\": 1}', 'root@localhost', '2025-08-09 10:34:51'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Web Tech\", \"section\": \"ICT 11 - AGHIMUAN\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-11 03:03:45'),
(0, 0, 'UPDATE', '{\"teacher_username\": \"alex\", \"subject\": \"Web Tech\", \"section\": \"ICT 11 - AGHIMUAN\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', '{\"teacher_username\": \"alex\", \"subject\": \"Web Tech\", \"section\": \"ICT 11 - AGHIMUAN\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-11 03:04:29'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"ICT 11 - AGHIMUAN\", \"day_of_week\": \"Monday\", \"start_time\": \"12:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', 'root@localhost', '2025-08-11 03:07:10'),
(0, 0, 'UPDATE', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"ICT 11 - AGHIMUAN\", \"day_of_week\": \"Monday\", \"start_time\": \"12:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"ICT 11 - AGHIMUAN\", \"day_of_week\": \"Monday\", \"start_time\": \"12:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', 'root@localhost', '2025-08-11 03:07:22'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"IT - CAPSTONE 1\", \"day_of_week\": \"Tuesday\", \"start_time\": \"12:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-11 03:09:00'),
(0, 0, 'UPDATE', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"IT - CAPSTONE 1\", \"day_of_week\": \"Tuesday\", \"start_time\": \"12:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"IT - CAPSTONE 1\", \"day_of_week\": \"Tuesday\", \"start_time\": \"12:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-11 03:09:14'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"ICT 11 - AGHIMUANANO\", \"day_of_week\": \"Saturday\", \"start_time\": \"14:00:00\", \"end_time\": \"17:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', 'root@localhost', '2025-08-11 06:02:47'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"alex\", \"subject\": \"Quantum Physics\", \"section\": \"BSIT 402 - PERIPHALS\", \"day_of_week\": \"Wednesday\", \"start_time\": \"15:00:00\", \"end_time\": \"20:00:00\", \"room\": \"CANTEEN\", \"school_id\": 2}', 'root@localhost', '2025-08-11 11:38:21'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"escall\", \"subject\": \"Physical Quantumine\", \"section\": \"BSIT 302 - SIGALOT\", \"day_of_week\": \"Saturday\", \"start_time\": \"18:00:00\", \"end_time\": \"21:00:00\", \"room\": \"2ND FLOOR\", \"school_id\": 1}', 'root@localhost', '2025-08-11 11:39:46'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"escall\", \"subject\": \"NUCLEAR PHYSICS\", \"section\": \"BSIT - 302\", \"day_of_week\": \"Saturday\", \"start_time\": \"15:00:00\", \"end_time\": \"17:30:00\", \"room\": \"NUCLEAR LAB\", \"school_id\": 1}', 'root@localhost', '2025-08-16 06:31:00'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"ara\", \"subject\": \"ATOMIC PHYSICS\", \"section\": \"KINDER 202 - MAGALANG\", \"day_of_week\": \"Monday\", \"start_time\": \"12:00:00\", \"end_time\": \"14:00:00\", \"room\": \"ATOMIC SITE\", \"school_id\": 1}', 'root@localhost', '2025-08-16 07:45:53');

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `school_year` varchar(10) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `school_id` int(11) DEFAULT 1,
  `user_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `email`, `school_year`, `semester`, `created_at`, `updated_at`, `school_id`, `user_id`) VALUES
(1, 'escall.byte@gmail.com', '2024-2025', '2nd Semester', '2025-04-05 14:54:58', '2025-04-09 21:20:55', 1, 1),
(2, '', '2025-2026', '2nd Semester', '2025-07-17 13:01:21', '2025-07-17 13:36:35', 1, 1),
(3, 'test@example.com', '2024-2025', '2nd Semester', '2025-07-17 13:28:45', '2025-07-17 13:28:59', 1, 1),
(6, 'joerenz.dev@gmail.com', '2025-2026', '1st Semester', '2025-07-17 14:01:24', '2025-07-17 14:01:40', 1, 1),
(7, 'joerenzescallente027@gmail.com', '2025-2026', '2nd Semester', '2025-07-27 15:21:59', '2025-07-27 15:21:59', 1, 1);

-- --------------------------------------------------------

--
-- Structure for view `teacher_schedule_conflicts`
--
DROP TABLE IF EXISTS `teacher_schedule_conflicts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `teacher_schedule_conflicts`  AS SELECT `t1`.`id` AS `schedule1_id`, `t1`.`teacher_username` AS `teacher1`, `t1`.`subject` AS `subject1`, `t1`.`section` AS `section1`, `t1`.`day_of_week` AS `day_of_week`, `t1`.`start_time` AS `start1`, `t1`.`end_time` AS `end1`, `t1`.`room` AS `room1`, `t2`.`id` AS `schedule2_id`, `t2`.`teacher_username` AS `teacher2`, `t2`.`subject` AS `subject2`, `t2`.`section` AS `section2`, `t2`.`start_time` AS `start2`, `t2`.`end_time` AS `end2`, `t2`.`room` AS `room2` FROM (`teacher_schedules` `t1` join `teacher_schedules` `t2` on(`t1`.`id` <> `t2`.`id` and `t1`.`day_of_week` = `t2`.`day_of_week` and `t1`.`school_id` = `t2`.`school_id` and (`t1`.`start_time` < `t2`.`end_time` and `t1`.`end_time` > `t2`.`start_time` or `t2`.`start_time` < `t1`.`end_time` and `t2`.`end_time` > `t1`.`start_time`))) WHERE `t1`.`status` = 'active' AND `t2`.`status` = 'active' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `status` (`status`),
  ADD KEY `start_time` (`start_time`),
  ADD KEY `idx_school_status` (`school_id`,`status`),
  ADD KEY `idx_start_end_time` (`start_time`,`end_time`),
  ADD KEY `idx_attendance_sessions_school_start` (`school_id`,`start_time`),
  ADD KEY `idx_attendance_sessions_start` (`start_time`),
  ADD KEY `idx_attendance_sessions_end` (`end_time`);

--
-- Indexes for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_instructor` (`instructor_name`),
  ADD KEY `idx_section` (`course_section`),
  ADD KEY `idx_subject` (`subject`);

--
-- Indexes for table `class_time_settings`
--
ALTER TABLE `class_time_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_time` (`school_id`),
  ADD KEY `idx_class_time_settings_school` (`school_id`);

--
-- Indexes for table `tbl_subjects`
--
ALTER TABLE `tbl_subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `uniq_subject_per_school` (`subject_name`,`school_id`),
  ADD KEY `idx_tbl_subjects_user_id` (`user_id`);

--
-- Indexes for table `teacher_schedules`
--
ALTER TABLE `teacher_schedules`
  ADD UNIQUE KEY `uniq_ts_per_user` (`school_id`,`user_id`,`subject`,`section`,`day_of_week`,`start_time`,`end_time`),
  ADD KEY `idx_ts_user_scoped` (`school_id`,`user_id`,`status`,`day_of_week`,`start_time`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `class_time_settings`
--
ALTER TABLE `class_time_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `tbl_subjects`
--
ALTER TABLE `tbl_subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
