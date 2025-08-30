-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 30, 2025 at 03:02 PM
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
(102, 24, '', 'Deleted attendance record #153', 'tbl_attendance', 153, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 22:23:07', NULL, 1),
(103, 30, 'settings_change', 'Updated school information', 'school_info', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 18:00:40', NULL, 1),
(104, 30, 'settings_change', 'Updated school information', 'school_info', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 18:00:45', '{\"school_name\":{\"old\":\"San Pedro City Polytechnic College\",\"new\":\"San Pedro City Polytechnic Colleges\"}}', 1),
(105, 30, 'settings_change', 'Updated school information', 'school_info', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 18:00:49', '{\"school_name\":{\"old\":\"San Pedro City Polytechnic Colleges\",\"new\":\"San Pedro City Polytechnic College\"}}', 1),
(106, 28, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 20:58:51', '{\"school_logo_path\":{\"old\":\"uploads\\/school_logos\\/school_logo_2_1756500343.png\",\"new\":\"admin\\/image\\/school-logo-2.png\"}}', 1),
(107, 28, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 21:01:17', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo-2.png\",\"new\":\"admin\\/image\\/school-logo-2.png\"}}', 1);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `scan_time` datetime NOT NULL DEFAULT current_timestamp(),
  `school_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(35, '', '', '', '00:00:00', 'inactive', '00:00:00', '', 15, 1, '2025-08-23 14:21:53', '2025-08-30 12:23:02'),
(38, '', '', '', '00:00:00', 'inactive', '00:00:00', '', 2, 1, '2025-08-24 04:46:28', '2025-08-30 13:02:25');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `room_name` varchar(191) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `class_name` varchar(191) NOT NULL,
  `instructor` varchar(191) NOT NULL,
  `room` varchar(191) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(2, 'Computer Site Inc.', '', '', '', '', 'admin/image/school-logo-2.png', '', '', '', '2025-07-17 16:14:38', '2025-08-30 21:01:17', 2);

-- --------------------------------------------------------

--
-- Table structure for table `student_qr_tokens`
--

CREATE TABLE `student_qr_tokens` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `token` varchar(191) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_qr_tokens`
--

INSERT INTO `student_qr_tokens` (`id`, `student_id`, `token`, `expires_at`, `used_at`, `revoked_at`, `created_at`, `user_id`, `school_id`) VALUES
(32, 7, 'yWEgdSqoH4bpP_ak4i5cqA-aLICSw', '2025-08-30 03:41:59', NULL, '2025-08-30 03:41:21', '2025-08-30 03:40:59', 28, 2),
(33, 4, 'aUaUukCWqRS8z88o9L8BBg-aLICVA', '2025-08-30 03:42:08', NULL, NULL, '2025-08-30 03:41:08', 28, 2),
(34, 7, 'TOEdKZIVY-zP7k5ODyLFaw-aLICYQ', '2025-08-30 03:46:21', NULL, NULL, '2025-08-30 03:41:21', 28, 2),
(35, 3, 'EyiTSCN3KSxndVw_plW_mw-aLICbw', '2025-08-30 03:56:35', NULL, '2025-08-30 03:48:29', '2025-08-30 03:41:35', 28, 2),
(36, 3, '_uWQc4pdSE9RXSwXFxd6nQ-aLICeg', '2025-08-31 03:41:46', NULL, '2025-08-30 03:48:29', '2025-08-30 03:41:46', 28, 2),
(37, 10, 'VM7-flbyZtuq3c8rHn0Mnw-aLIDnQ', '2025-08-30 03:47:37', '2025-08-30 03:46:52', NULL, '2025-08-30 03:46:37', 28, 2),
(38, 3, 'Gt9VijVD8Qk4l1N0BNptYw-aLIEDQ', '2025-08-30 03:49:29', NULL, NULL, '2025-08-30 03:48:29', 28, 2),
(39, 7, '_ZxVATX8a9f0O8tFKNXx3A-aLIENg', '2025-08-30 03:54:10', '2025-08-30 03:49:35', NULL, '2025-08-30 03:49:10', 28, 2),
(40, 7, '7RXXKVmHpDVQFOsWR_nAOQ-aLIEiw', '2025-08-30 03:55:35', '2025-08-30 03:50:49', NULL, '2025-08-30 03:50:35', 28, 2),
(41, 10, 'PyAXA8jppjtvDXe5fO5fYQ-aLIGRQ', '2025-08-30 04:12:57', NULL, NULL, '2025-08-30 03:57:57', 28, 2),
(42, 11, 'XLIoKd6Xax7r3pZUZmldDw-aLLs5Q', '2025-08-30 20:22:57', NULL, '2025-08-30 20:22:14', '2025-08-30 20:21:57', 28, 2),
(43, 11, 'n7LvYOJoSLRdhvKy1QSfjA-aLLs9g', '2025-08-31 20:22:14', NULL, NULL, '2025-08-30 20:22:14', 28, 2);

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
  `user_id` int(11) DEFAULT NULL,
  `time_in_date` date GENERATED ALWAYS AS (cast(`time_in` as date)) STORED,
  `subject_id_nz` int(11) GENERATED ALWAYS AS (ifnull(`subject_id`,0)) STORED,
  `instructor_id_nz` int(11) GENERATED ALWAYS AS (ifnull(`instructor_id`,0)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_attendance`
--

INSERT INTO `tbl_attendance` (`tbl_attendance_id`, `tbl_student_id`, `time_in`, `status`, `mode`, `time_out`, `instructor_id`, `subject_id`, `school_id`, `user_id`) VALUES
(7, 3, '2025-08-24 04:46:38', 'On Time', 'general', NULL, 25, 0, 2, 28),
(8, 4, '2025-08-24 04:56:58', 'Late', 'general', NULL, 25, 0, 2, 28),
(9, 2, '2025-08-24 05:01:37', 'On Time', 'general', NULL, 27, 0, 15, 30),
(10, 1, '2025-08-24 05:02:08', 'Late', 'general', NULL, 27, 6, 15, 30),
(13, 7, '2025-08-29 19:49:35', 'On Time', 'general', NULL, 25, 8, 2, 28),
(14, 7, '2025-08-29 19:50:49', 'On Time', 'general', NULL, 25, 9, 2, 28),
(15, 3, '2025-08-30 12:40:00', 'On Time', 'general', NULL, NULL, NULL, 2, 26);

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
(37, 'BSIS', 30, 15, '2025-08-23 14:20:39'),
(38, 'HUMMS', 28, 2, '2025-08-24 04:39:38'),
(44, 'BSIT', 30, 15, '2025-08-24 13:03:21'),
(45, 'HUMMS', 30, 15, '2025-08-24 13:03:47'),
(48, 'STEM', 28, 2, '2025-08-29 19:43:52'),
(49, 'ICT', 28, 2, '2025-08-30 02:50:41');

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
(80, 3, 'Comsite', 'Success', '2025-08-24 12:39:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 2, 28),
(81, 4, 'CSI', 'Success', '2025-08-24 12:47:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 2, 28),
(82, 5, 'javascript', 'Success', '2025-08-24 13:47:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 2, 28),
(83, 6, 'Plankton', 'Success', '2025-08-24 20:33:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 2, 28),
(84, 7, 'Plankton', 'Success', '2025-08-24 20:36:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 2, 28),
(85, 8, 'vue', 'Success', '2025-08-24 21:03:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 15, 30),
(86, 9, 'js', 'Success', '2025-08-24 21:03:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 15, 30),
(87, 10, 'krabby', 'Success', '2025-08-30 03:43:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 2, 28);

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
(25, 'COMSITE', '', '2025-08-22 14:04:04', 1, NULL),
(26, 'ADMIN - Bagwis', '', '2025-08-22 15:51:49', 1, NULL),
(27, 'SPCPC', '', '2025-08-23 12:53:15', 1, NULL);

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
(6, 0, 7, '2025-08-24 05:02:18', 1),
(7, 25, 8, '2025-08-29 19:46:15', 1),
(8, 0, 8, '2025-08-29 19:47:29', 1);

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
(204, '302', 30, 15, '2025-08-23 14:20:39', 37),
(205, '12 HUMANIDADES', 28, 2, '2025-08-24 04:39:38', 38),
(206, '11 AGHIMUAN', 28, 2, '2025-08-24 04:47:18', 49),
(207, '11  VERSI', 28, 2, '2025-08-24 12:33:09', 49),
(208, '11  VERSI', 1, 1, '2025-08-24 12:33:34', NULL),
(209, 'ICT SHELBY', 28, 2, '2025-08-24 12:36:17', 41),
(210, 'ICT SHELBY', 1, 1, '2025-08-24 12:36:43', NULL),
(211, 'ICT SHELBYie', 1, 1, '2025-08-24 12:38:12', NULL),
(212, '11 AGHIMUAN', 1, 1, '2025-08-24 12:55:23', NULL),
(213, '302', 1, 1, '2025-08-24 13:00:38', NULL),
(214, '402', 30, 15, '2025-08-24 13:03:21', 44),
(215, '12 EVOLVE', 30, 15, '2025-08-24 13:03:47', 45),
(216, '12 EVOLVE', 1, 1, '2025-08-24 13:04:58', NULL),
(217, '402', 1, 1, '2025-08-24 13:11:14', NULL),
(218, '403', 1, 1, '2025-08-24 13:16:40', NULL),
(219, '12 TEKNO', 28, 2, '2025-08-29 19:43:52', 48);

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
(1, 'SPCPC', 'BSIS - 302', 'STU-30-15-c1fb424c-c9b4f61d9256a239', NULL, 'face_1755958839_68a9ce37f3f4a.jpg', 15, 30),
(2, 'angular', 'BSIS - 302', 'STU-30-15-f41e2082-4e3b81967acf7b46', NULL, 'face_1755959033_68a9cef97258d.jpg', 15, 30),
(3, 'Comsite', 'HUMMS - 12 HUMANIDADES', 'STU-28-2-01b59ddc-b2bfb513115cc649', NULL, 'face_1756010378_68aa978a3d4c0.jpg', 2, 28),
(4, 'CSI', 'ICT - 11 AGHIMUAN', 'STU-28-2-88bca758-f114ee6d4279002b', NULL, 'face_1756010838_68aa995621460.jpg', 2, 28),
(7, 'Plankton', '11 - ICT SHELBY', 'STU-28-2-447222be-1b42b0e46bee51cc', NULL, 'face_1756038977_68ab074137e45.jpg', 2, 28),
(8, 'vue js', 'BSIT - 401', 'STU-30-15-24a561ab-bab013a4c926dca6', NULL, 'face_1756040601_68ab0d998ae71.jpg', 15, 30),
(10, 'krabbypatty', 'STEM - 12 TEKNO', 'STU-28-2-076b1db0-0bcec879b39b3375', NULL, 'face_1756496632_68b202f878f55.jpg', 2, 28),
(11, 'monarba', 'STEM - 12 SIGBIN', '', NULL, NULL, 2, 28);

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
(6, 'Networking', '2025-08-23 12:54:12', 15, 30),
(7, '-- Select Subject --', '2025-08-24 05:02:18', 15, 30),
(8, 'FILIPINO', '2025-08-29 19:46:15', 2, 28),
(9, 'TEKNOLOHIYA', '2025-08-29 19:50:16', 2, 28);

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
(34, 'SPCPC', 'Networkings', 'BSIS - 302', 'Monday', '09:00:00', '23:00:00', 'Computer Laboratory', 15, 30, '2025-08-23 14:21:34', '2025-08-25 09:56:10', 'active'),
(35, 'COMSITE', 'FILIPINO', 'STEM - 12 TEKNO', 'Monday', '12:00:00', '13:00:00', '204', 2, 28, '2025-08-29 19:45:31', '2025-08-29 19:45:31', 'active'),
(36, 'COMSITE', 'TEKNOLOHIYA', 'STEM - 12 TEKNO', 'Saturday', '13:00:00', '14:00:00', '200', 2, 28, '2025-08-29 19:45:57', '2025-08-29 19:45:57', 'active');

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
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"ara\", \"subject\": \"ATOMIC PHYSICS\", \"section\": \"KINDER 202 - MAGALANG\", \"day_of_week\": \"Monday\", \"start_time\": \"12:00:00\", \"end_time\": \"14:00:00\", \"room\": \"ATOMIC SITE\", \"school_id\": 1}', 'root@localhost', '2025-08-16 07:45:53'),
(0, 0, 'INSERT', NULL, '{\"teacher_username\": \"SPCPC\", \"subject\": \"Networking\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Saturday\", \"start_time\": \"21:00:00\", \"end_time\": \"22:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', 'root@localhost', '2025-08-23 12:54:05'),
(0, 0, 'DELETE', '{\"teacher_username\": \"ara\", \"subject\": \"java\", \"section\": \"BSIT - 301\", \"day_of_week\": \"Monday\", \"start_time\": \"07:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 0, 'DELETE', '{\"teacher_username\": \"ara\", \"subject\": \"APP DEV\", \"section\": \"11 ICT - AGHIMUANINANES\", \"day_of_week\": \"Tuesday\", \"start_time\": \"09:00:00\", \"end_time\": \"11:30:00\", \"room\": \"Room 1\", \"school_id\": 1}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 0, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Web Tech\", \"section\": \"ICT 11 - AGHIMUAN\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 0, 'DELETE', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"ICT 11 - AGHIMUAN\", \"day_of_week\": \"Monday\", \"start_time\": \"12:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 0, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"IT - CAPSTONE 1\", \"day_of_week\": \"Tuesday\", \"start_time\": \"12:00:00\", \"end_time\": \"13:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 0, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Networking\", \"section\": \"ICT 11 - AGHIMUANANO\", \"day_of_week\": \"Saturday\", \"start_time\": \"14:00:00\", \"end_time\": \"17:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 0, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Quantum Physics\", \"section\": \"BSIT 402 - PERIPHALS\", \"day_of_week\": \"Wednesday\", \"start_time\": \"15:00:00\", \"end_time\": \"20:00:00\", \"room\": \"CANTEEN\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 0, 'DELETE', '{\"teacher_username\": \"escall\", \"subject\": \"Physical Quantumine\", \"section\": \"BSIT 302 - SIGALOT\", \"day_of_week\": \"Saturday\", \"start_time\": \"18:00:00\", \"end_time\": \"21:00:00\", \"room\": \"2ND FLOOR\", \"school_id\": 1}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 0, 'DELETE', '{\"teacher_username\": \"escall\", \"subject\": \"NUCLEAR PHYSICS\", \"section\": \"BSIT - 302\", \"day_of_week\": \"Saturday\", \"start_time\": \"15:00:00\", \"end_time\": \"17:30:00\", \"room\": \"NUCLEAR LAB\", \"school_id\": 1}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 0, 'DELETE', '{\"teacher_username\": \"ara\", \"subject\": \"ATOMIC PHYSICS\", \"section\": \"KINDER 202 - MAGALANG\", \"day_of_week\": \"Monday\", \"start_time\": \"12:00:00\", \"end_time\": \"14:00:00\", \"room\": \"ATOMIC SITE\", \"school_id\": 1}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 0, 'DELETE', '{\"teacher_username\": \"SPCPC\", \"subject\": \"Networking\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Saturday\", \"start_time\": \"21:00:00\", \"end_time\": \"22:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', NULL, 'root@localhost', '2025-08-23 13:53:01'),
(0, 23, 'DELETE', '{\"teacher_username\": \"escall\", \"subject\": \"Web Tech\", \"section\": \"HUMMS - 12\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 1}', NULL, 'root@localhost', '2025-08-23 13:53:27'),
(0, 24, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Discrete Mathematics\", \"section\": \"BSIT-302\", \"day_of_week\": \"Friday\", \"start_time\": \"17:00:00\", \"end_time\": \"19:00:00\", \"room\": \"Massage Room\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-23 13:53:27'),
(0, 26, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Web Dev\", \"section\": \"BSIT-302\", \"day_of_week\": \"Monday\", \"start_time\": \"07:00:00\", \"end_time\": \"22:00:00\", \"room\": \"Dress Room\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-23 13:53:27'),
(0, 32, 'DELETE', '{\"teacher_username\": \"alex\", \"subject\": \"Capstone 2\", \"section\": \"BSIT-302\", \"day_of_week\": \"Tuesday\", \"start_time\": \"08:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-23 13:53:27'),
(0, 33, 'DELETE', '{\"teacher_username\": \"escall\", \"subject\": \"Networking\", \"section\": \"BSIT-301\", \"day_of_week\": \"Friday\", \"start_time\": \"08:00:00\", \"end_time\": \"09:00:00\", \"room\": \"\", \"school_id\": 1}', NULL, 'root@localhost', '2025-08-23 13:53:27'),
(0, 34, 'INSERT', NULL, '{\"teacher_username\": \"SPCPC\", \"subject\": \"Networking\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', 'root@localhost', '2025-08-23 14:21:34'),
(0, 34, 'UPDATE', '{\"teacher_username\": \"SPCPC\", \"subject\": \"Networking\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', '{\"teacher_username\": \"SPCPC\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', 'root@localhost', '2025-08-25 09:56:10'),
(0, 35, 'INSERT', NULL, '{\"teacher_username\": \"COMSITE\", \"subject\": \"FILIPINO\", \"section\": \"STEM - 12 TEKNO\", \"day_of_week\": \"Monday\", \"start_time\": \"12:00:00\", \"end_time\": \"13:00:00\", \"room\": \"204\", \"school_id\": 2}', 'root@localhost', '2025-08-29 19:45:31'),
(0, 36, 'INSERT', NULL, '{\"teacher_username\": \"COMSITE\", \"subject\": \"TEKNOLOHIYA\", \"section\": \"STEM - 12 TEKNO\", \"day_of_week\": \"Saturday\", \"start_time\": \"13:00:00\", \"end_time\": \"14:00:00\", \"room\": \"200\", \"school_id\": 2}', 'root@localhost', '2025-08-29 19:45:57'),
(0, 37, 'INSERT', NULL, '{\"teacher_username\": \"aranaydo\", \"subject\": \"abc\", \"section\": \"MLBB - 12 DAWN\", \"day_of_week\": \"Wednesday\", \"start_time\": \"09:00:00\", \"end_time\": \"10:00:00\", \"room\": \"1\", \"school_id\": 2}', 'root@localhost', '2025-08-29 20:53:15'),
(0, 37, 'UPDATE', '{\"teacher_username\": \"aranaydo\", \"subject\": \"abc\", \"section\": \"MLBB - 12 DAWN\", \"day_of_week\": \"Wednesday\", \"start_time\": \"09:00:00\", \"end_time\": \"10:00:00\", \"room\": \"1\", \"school_id\": 2}', '{\"teacher_username\": \"COMSITE\", \"subject\": \"abc\", \"section\": \"MLBB - 12 DAWN\", \"day_of_week\": \"Wednesday\", \"start_time\": \"09:00:00\", \"end_time\": \"10:00:00\", \"room\": \"1\", \"school_id\": 2}', 'root@localhost', '2025-08-29 20:53:58'),
(0, 37, 'UPDATE', '{\"teacher_username\": \"COMSITE\", \"subject\": \"abc\", \"section\": \"MLBB - 12 DAWN\", \"day_of_week\": \"Wednesday\", \"start_time\": \"09:00:00\", \"end_time\": \"10:00:00\", \"room\": \"1\", \"school_id\": 2}', '{\"teacher_username\": \"COMSITE\", \"subject\": \"abc\", \"section\": \"MLBB - 12 DAWN\", \"day_of_week\": \"Wednesday\", \"start_time\": \"09:00:00\", \"end_time\": \"11:00:00\", \"room\": \"12\", \"school_id\": 2}', 'root@localhost', '2025-08-29 20:59:33'),
(0, 37, 'UPDATE', '{\"teacher_username\": \"COMSITE\", \"subject\": \"abc\", \"section\": \"MLBB - 12 DAWN\", \"day_of_week\": \"Wednesday\", \"start_time\": \"09:00:00\", \"end_time\": \"11:00:00\", \"room\": \"12\", \"school_id\": 2}', '{\"teacher_username\": \"COMSITE\", \"subject\": \"abcde\", \"section\": \"MLBB - 12 DAWNe\", \"day_of_week\": \"Tuesday\", \"start_time\": \"08:00:00\", \"end_time\": \"10:00:00\", \"room\": \"123\", \"school_id\": 2}', 'root@localhost', '2025-08-29 21:03:50'),
(0, 38, 'INSERT', NULL, '{\"teacher_username\": \"COMSITE\", \"subject\": \"aa\", \"section\": \"STEM -11 SIGBIN\", \"day_of_week\": \"Thursday\", \"start_time\": \"06:07:00\", \"end_time\": \"07:00:00\", \"room\": \"1\", \"school_id\": 2}', 'root@localhost', '2025-08-30 02:34:32'),
(0, 38, 'DELETE', '{\"teacher_username\": \"COMSITE\", \"subject\": \"aa\", \"section\": \"STEM -11 SIGBIN\", \"day_of_week\": \"Thursday\", \"start_time\": \"06:07:00\", \"end_time\": \"07:00:00\", \"room\": \"1\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-30 02:34:55'),
(0, 37, 'DELETE', '{\"teacher_username\": \"COMSITE\", \"subject\": \"abcde\", \"section\": \"MLBB - 12 DAWNe\", \"day_of_week\": \"Tuesday\", \"start_time\": \"08:00:00\", \"end_time\": \"10:00:00\", \"room\": \"123\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-30 11:57:57');

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
(1, 'escall.byte@gmail.com', '2024-2025', '1st Semester', '2025-04-05 14:54:58', '2025-08-24 13:03:56', 1, 1),
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
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_logs_session_student` (`session_id`,`student_id`),
  ADD KEY `idx_logs_session` (`session_id`),
  ADD KEY `idx_logs_student` (`student_id`);

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
  ADD KEY `idx_attendance_sessions_end` (`end_time`),
  ADD KEY `idx_sessions_instr_course_time` (`instructor_id`,`course_id`,`start_time`,`end_time`),
  ADD KEY `idx_sessions_school` (`school_id`);

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
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_school_room` (`school_id`,`room_name`),
  ADD KEY `idx_school` (`school_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_day` (`school_id`,`day_of_week`),
  ADD KEY `idx_room_day_time` (`school_id`,`room`,`day_of_week`,`start_time`,`end_time`);

--
-- Indexes for table `school_info`
--
ALTER TABLE `school_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_qr_tokens`
--
ALTER TABLE `student_qr_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_student_current` (`student_id`,`used_at`,`expires_at`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_user_school` (`user_id`,`school_id`);

--
-- Indexes for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  ADD PRIMARY KEY (`tbl_attendance_id`),
  ADD UNIQUE KEY `uq_attendance_unique_day` (`tbl_student_id`,`user_id`,`school_id`,`subject_id_nz`,`instructor_id_nz`,`time_in_date`);

--
-- Indexes for table `tbl_courses`
--
ALTER TABLE `tbl_courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `tbl_face_recognition_logs`
--
ALTER TABLE `tbl_face_recognition_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `tbl_face_verification_logs`
--
ALTER TABLE `tbl_face_verification_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `tbl_instructors`
--
ALTER TABLE `tbl_instructors`
  ADD PRIMARY KEY (`instructor_id`);

--
-- Indexes for table `tbl_instructor_subjects`
--
ALTER TABLE `tbl_instructor_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_sections`
--
ALTER TABLE `tbl_sections`
  ADD PRIMARY KEY (`section_id`);

--
-- Indexes for table `tbl_student`
--
ALTER TABLE `tbl_student`
  ADD PRIMARY KEY (`tbl_student_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_ts_per_user` (`school_id`,`user_id`,`subject`,`section`,`day_of_week`,`start_time`,`end_time`),
  ADD KEY `idx_ts_user_scoped` (`school_id`,`user_id`,`status`,`day_of_week`,`start_time`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_time_settings`
--
ALTER TABLE `class_time_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_info`
--
ALTER TABLE `school_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_qr_tokens`
--
ALTER TABLE `student_qr_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  MODIFY `tbl_attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_courses`
--
ALTER TABLE `tbl_courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `tbl_face_verification_logs`
--
ALTER TABLE `tbl_face_verification_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `tbl_instructors`
--
ALTER TABLE `tbl_instructors`
  MODIFY `instructor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `tbl_instructor_subjects`
--
ALTER TABLE `tbl_instructor_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_sections`
--
ALTER TABLE `tbl_sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT for table `tbl_student`
--
ALTER TABLE `tbl_student`
  MODIFY `tbl_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_subjects`
--
ALTER TABLE `tbl_subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `teacher_schedules`
--
ALTER TABLE `teacher_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_logs_session` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
