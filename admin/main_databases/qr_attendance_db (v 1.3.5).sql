-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 07, 2025 at 02:40 PM
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
(107, 28, 'settings_change', 'Updated school information', 'school_info', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 21:01:17', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo-2.png\",\"new\":\"admin\\/image\\/school-logo-2.png\"}}', 1),
(108, 43, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-06 18:28:37', '{\"school_year\":{\"old\":\"\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"\",\"new\":\"2nd Semester\"}}', 1),
(109, 43, 'settings_change', 'Updated school information', 'school_info', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-06 18:28:54', '{\"school_name\":{\"old\":\"School Name\",\"new\":\"Computer Site Inc.\"},\"school_address\":{\"old\":\"School Address\",\"new\":\"\"},\"school_contact\":{\"old\":\"Contact Number\",\"new\":\"\"},\"school_email\":{\"old\":\"school@email.com\",\"new\":\"\"},\"school_website\":{\"old\":\"www.schoolwebsite.com\",\"new\":\"\"},\"school_motto\":{\"old\":\"School Motto\",\"new\":\"\"},\"school_vision\":{\"old\":\"School Vision\",\"new\":\"\"},\"school_mission\":{\"old\":\"School Mission\",\"new\":\"\"}}', 1),
(110, 43, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-06 18:31:15', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"1st Semester\"}}', 21),
(111, 43, 'settings_change', 'Updated school information', 'school_info', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-06 18:31:30', NULL, 21),
(112, 43, 'settings_change', 'Updated school information', 'school_info', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-06 18:32:08', '{\"school_name\":{\"old\":\"Computer Site Inc.\",\"new\":\"Computer Site Institute Incorporated\"},\"school_logo_path\":{\"old\":\"admin\\/image\\/SPCPC-logo-trans.png\",\"new\":\"admin\\/image\\/school-logo-21.png\"}}', 21),
(113, 34, 'settings_change', 'Updated school information', 'school_info', 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-06 18:32:51', '{\"school_name\":{\"old\":\"School Name\",\"new\":\"San Pedro City Polytechnic College\"},\"school_address\":{\"old\":\"School Address\",\"new\":\"\"},\"school_contact\":{\"old\":\"Contact Number\",\"new\":\"\"},\"school_email\":{\"old\":\"school@email.com\",\"new\":\"\"},\"school_website\":{\"old\":\"www.schoolwebsite.com\",\"new\":\"\"},\"school_motto\":{\"old\":\"School Motto\",\"new\":\"\"},\"school_vision\":{\"old\":\"School Vision\",\"new\":\"\"},\"school_mission\":{\"old\":\"School Mission\",\"new\":\"\"}}', 20),
(114, 34, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-06 18:33:14', '{\"school_year\":{\"old\":\"\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"\",\"new\":\"2nd Semester\"}}', 20),
(115, 34, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-06 18:33:19', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"1st Semester\"}}', 20);

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
(35, '', '', '', '00:00:00', 'inactive', '00:00:00', '', 15, 1, '2025-08-23 14:21:53', '2025-09-04 05:46:50'),
(38, '', '', '', '00:00:00', 'inactive', '00:00:00', '', 2, 1, '2025-08-24 04:46:28', '2025-08-30 13:02:25'),
(45, '', '', '', '00:00:00', 'active', '00:00:00', '', 17, 1, '2025-09-03 12:42:06', '2025-09-03 12:43:11'),
(46, '', '', '', '00:00:00', 'inactive', '00:00:00', '', 20, 1, '2025-09-04 01:07:34', '2025-09-06 15:24:57'),
(50, '', '', '', '00:00:00', 'active', '00:00:00', '', 21, 1, '2025-09-06 09:12:18', '2025-09-06 15:07:36');

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
(2, 'Computer Site Inc.', '', '', '', '', 'admin/image/school-logo-2.png', '', '', '', '2025-07-17 16:14:38', '2025-08-30 21:01:17', 2),
(3, 'Computer Site Institute Incorporated', '', '', '', '', 'uploads/school_logos/school_logo_21_1757171063.png', '', '', '', '2025-09-06 18:28:54', '2025-09-06 23:04:23', 21),
(4, 'San Pedro City Polytechnic College', '', '', '', '', 'uploads/school_logos/school_logo_20_1757171071.png', '', '', '', '2025-09-06 18:32:51', '2025-09-06 23:04:31', 20);

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
(43, 11, 'n7LvYOJoSLRdhvKy1QSfjA-aLLs9g', '2025-08-31 20:22:14', NULL, NULL, '2025-08-30 20:22:14', 28, 2),
(44, 12, 'ghayZj-6KywPOyc8LKqlVQ-aLg3wQ', '2025-09-03 20:43:41', NULL, '2025-09-03 20:42:41', '2025-09-03 20:42:41', 31, 17),
(45, 12, 'Y38ChoaJL-4bdJCt9kzotw-aLg3wQ', '2025-09-03 20:43:41', '2025-09-03 20:42:57', NULL, '2025-09-03 20:42:41', 31, 17),
(46, 14, 'd5njpDM1ha8m3uNQa10zJA-aLjndg', '2025-09-04 09:27:22', NULL, '2025-09-04 09:12:22', '2025-09-04 09:12:22', 34, 20),
(47, 14, 'mEmel74wSIGarNR4Zuc-JA-aLjndg', '2025-09-04 09:27:22', '2025-09-04 09:12:44', NULL, '2025-09-04 09:12:22', 34, 20),
(48, 15, 'W8BitoX3-K17ypAt7SLKiQ-aLjoCA', '2025-09-04 09:29:48', NULL, '2025-09-04 09:14:49', '2025-09-04 09:14:48', 34, 20),
(49, 15, 'KzqN2liLbAJoIbkVznsmIg-aLjoCQ', '2025-09-04 09:29:49', '2025-09-04 09:15:11', NULL, '2025-09-04 09:14:49', 34, 20),
(50, 16, '6hcaZo27FpPUAwQYLkaBww-aLjobQ', '2025-09-04 09:31:29', NULL, '2025-09-04 09:16:29', '2025-09-04 09:16:29', 34, 20),
(51, 16, 'SatDdVUoO9DI7ORMdcp9xA-aLjobQ', '2025-09-04 09:31:29', '2025-09-04 09:16:52', NULL, '2025-09-04 09:16:29', 34, 20),
(52, 17, 'g2fbMknpIsBhQoECKNlApg-aLjoyw', '2025-09-04 09:33:03', NULL, '2025-09-04 09:18:03', '2025-09-04 09:18:03', 34, 20),
(53, 17, '50rL-m_J8MMbdyx3dUPw-Q-aLjoyw', '2025-09-04 09:33:03', '2025-09-04 09:18:30', NULL, '2025-09-04 09:18:03', 34, 20),
(54, 18, 'lgq0Hz1kPj_MZ5rJ1_J5Sg-aLjpLA', '2025-09-04 09:34:40', NULL, '2025-09-04 09:19:40', '2025-09-04 09:19:40', 34, 20),
(55, 18, 'VWzh5sZxNGj7CfSMt0IqKg-aLjpLA', '2025-09-04 09:34:40', '2025-09-04 09:19:55', NULL, '2025-09-04 09:19:40', 34, 20),
(56, 19, '426dNCqO7wx0YND58yxJJg-aLjpbw', '2025-09-04 09:35:47', NULL, '2025-09-04 09:20:47', '2025-09-04 09:20:47', 34, 20),
(57, 19, 'PAJhqTLAE61Glmk0y3F_eQ-aLjpbw', '2025-09-04 09:35:47', '2025-09-04 09:21:10', NULL, '2025-09-04 09:20:47', 34, 20),
(58, 20, 'Wz5qocrb4fj2FfeXeZCyRw-aLjptQ', '2025-09-04 09:36:57', NULL, '2025-09-04 09:21:59', '2025-09-04 09:21:57', 34, 20),
(59, 20, 'Qbf3PzDcdR9mpZXJhYgVag-aLjptQ', '2025-09-04 09:36:57', NULL, '2025-09-04 09:21:59', '2025-09-04 09:21:57', 34, 20),
(60, 20, 'ojs19DJo34rEGMNVnRHRuA-aLjptw', '2025-09-04 09:36:59', NULL, '2025-09-04 09:21:59', '2025-09-04 09:21:59', 34, 20),
(61, 20, 'DCFBX0pUlWVXfDpWZqwdFQ-aLjptw', '2025-09-04 09:36:59', '2025-09-04 09:22:17', NULL, '2025-09-04 09:21:59', 34, 20),
(62, 21, 'WIBY-0DAgboT97qWJ0C55w-aLjp9Q', '2025-09-04 09:38:01', NULL, '2025-09-04 09:23:01', '2025-09-04 09:23:01', 34, 20),
(63, 21, 'xG9xZCKodoahGebU4-DMpA-aLjp9Q', '2025-09-04 09:38:01', '2025-09-04 09:23:53', NULL, '2025-09-04 09:23:01', 34, 20),
(64, 22, 'h4rrjJvD1d-ilMP1KYLnNQ-aLjqTw', '2025-09-04 09:39:31', NULL, '2025-09-04 09:24:31', '2025-09-04 09:24:31', 34, 20),
(65, 22, 'xXuInH-fYrubKJ-iqzcDpA-aLjqTw', '2025-09-04 09:39:31', '2025-09-04 09:24:53', NULL, '2025-09-04 09:24:31', 34, 20),
(66, 23, 'ldE1yXFo4DXpZcAZF-sGcw-aLjqmw', '2025-09-04 09:40:47', NULL, '2025-09-04 09:25:47', '2025-09-04 09:25:47', 34, 20),
(67, 23, 'lnPIXYnNfQwkv0CESxRkLA-aLjqmw', '2025-09-04 09:40:47', '2025-09-04 09:26:15', NULL, '2025-09-04 09:25:47', 34, 20),
(68, 24, 'jPhXl2oSBJ8rce-gtkxt0w-aLjq6g', '2025-09-04 09:42:06', NULL, '2025-09-04 09:27:06', '2025-09-04 09:27:06', 34, 20),
(69, 24, 'DzPpMXg0KeRBL6EJY559Rg-aLjq6g', '2025-09-04 09:42:06', '2025-09-04 09:27:31', NULL, '2025-09-04 09:27:06', 34, 20),
(70, 25, 'cdFgJxTjBlAf1Eg2qv3Fng-aLjrMg', '2025-09-04 09:43:18', NULL, '2025-09-04 09:28:18', '2025-09-04 09:28:18', 34, 20),
(71, 25, 'SjCukR_lvqGvzeryYEYuRQ-aLjrMg', '2025-09-04 09:43:18', '2025-09-04 09:28:34', NULL, '2025-09-04 09:28:18', 34, 20),
(72, 26, '7C95Dsw24tcwro0fvLrdsQ-aLjrcA', '2025-09-04 09:44:20', NULL, '2025-09-04 09:29:20', '2025-09-04 09:29:20', 34, 20),
(73, 26, 'cVYHIQpcAlxzWnJ8iphjxw-aLjrcA', '2025-09-04 09:44:20', '2025-09-04 09:29:34', NULL, '2025-09-04 09:29:20', 34, 20),
(74, 27, 'IZrFQby3fgPrkEm211Tf4Q-aLjrwA', '2025-09-04 09:45:40', NULL, '2025-09-04 09:30:40', '2025-09-04 09:30:40', 34, 20),
(75, 27, 'rr5cUZlKvG6_ypDXIohd5A-aLjrwA', '2025-09-04 09:45:40', '2025-09-04 09:30:59', NULL, '2025-09-04 09:30:40', 34, 20),
(76, 28, 'f-XTxPb6ODsHoN5E3gBFkw-aLjsDQ', '2025-09-04 09:46:57', NULL, '2025-09-04 09:31:57', '2025-09-04 09:31:57', 34, 20),
(77, 28, '7_jlvNAlB796HIz2_eyoGA-aLjsDQ', '2025-09-04 09:46:57', '2025-09-04 09:32:13', NULL, '2025-09-04 09:31:57', 34, 20),
(78, 29, 'Pakpw_vzuyvGkkS5fCyT3g-aLjsVQ', '2025-09-04 09:48:09', NULL, '2025-09-04 09:33:28', '2025-09-04 09:33:09', 34, 20),
(79, 29, 'UHHNpM0IrVg0nCSUX8X0Mg-aLjsaA', '2025-09-04 09:48:28', NULL, '2025-09-04 09:33:28', '2025-09-04 09:33:28', 34, 20),
(80, 29, 'z4cBCdtRlIMVcVWpVcjHAA-aLjsaA', '2025-09-04 09:48:28', '2025-09-04 09:33:45', NULL, '2025-09-04 09:33:28', 34, 20),
(81, 30, 'dC-fEXf0O_rT8XPp_fY90A-aLjssg', '2025-09-04 09:49:42', NULL, '2025-09-04 09:34:42', '2025-09-04 09:34:42', 34, 20),
(82, 30, 'LpN-n6p_w-ls_wjkVKnLbA-aLjssg', '2025-09-04 09:49:42', '2025-09-04 09:35:02', NULL, '2025-09-04 09:34:42', 34, 20),
(83, 31, '9wRmtTfLTcEvc2avnYQ-LA-aLjs8g', '2025-09-04 09:50:46', NULL, '2025-09-04 09:35:46', '2025-09-04 09:35:46', 34, 20),
(84, 31, '_7lLYBuItFab9FuCLNP3tQ-aLjs8g', '2025-09-04 09:50:46', '2025-09-04 09:36:02', NULL, '2025-09-04 09:35:46', 34, 20),
(85, 32, 'wO74i1KktELYv21kIFmglw-aLjtPw', '2025-09-04 09:52:03', NULL, '2025-09-04 09:37:03', '2025-09-04 09:37:03', 34, 20),
(86, 32, 'EB53XOz4kUsxI7znpBM5nQ-aLjtPw', '2025-09-04 09:52:03', '2025-09-04 09:37:22', NULL, '2025-09-04 09:37:03', 34, 20),
(87, 33, 'eb1d3iDhy_IDrS5USQAUdA-aLjtiA', '2025-09-04 09:53:16', NULL, '2025-09-04 09:39:32', '2025-09-04 09:38:16', 34, 20),
(88, 33, 'FXVsS13e7OheEo9C0JhOgg-aLjtiA', '2025-09-04 09:53:16', NULL, '2025-09-04 09:39:32', '2025-09-04 09:38:16', 34, 20),
(89, 33, 'uZdG65QL7Akof_DGnvCxzw-aLjtuQ', '2025-09-04 09:54:05', NULL, '2025-09-04 09:39:32', '2025-09-04 09:39:05', 34, 20),
(90, 33, 'qvjipxi2a4_06OVWfmUhBQ-aLjtuQ', '2025-09-04 09:54:05', NULL, '2025-09-04 09:39:32', '2025-09-04 09:39:05', 34, 20),
(91, 33, 'CLdz-ppcnxWChm6xYYF-Bw-aLjt0w', '2025-09-04 09:54:31', NULL, '2025-09-04 09:39:32', '2025-09-04 09:39:31', 34, 20),
(92, 33, '3BbRNkK1uotJhDkLf_UGRA-aLjt1A', '2025-09-04 09:54:32', '2025-09-04 09:39:44', NULL, '2025-09-04 09:39:32', 34, 20),
(93, 34, 'ojmSk2knFlZTMTSGVUPJDQ-aLjuCg', '2025-09-04 09:55:26', NULL, '2025-09-04 09:40:26', '2025-09-04 09:40:26', 34, 20),
(94, 34, 'OABmr9aQ4NS0dKnQNA6LiA-aLjuCg', '2025-09-04 09:55:26', '2025-09-04 09:40:44', NULL, '2025-09-04 09:40:26', 34, 20),
(95, 35, 'u-KS3PpTjyLblphWltuL9g-aLkoXg', '2025-09-04 14:04:18', NULL, '2025-09-04 13:49:53', '2025-09-04 13:49:18', 38, 20),
(96, 35, 'X72G9aE78qs1wrHDZdSDAA-aLkoXg', '2025-09-04 14:04:18', NULL, '2025-09-04 13:49:53', '2025-09-04 13:49:18', 38, 20),
(97, 35, 'ZZlOSbpFJ1ncrged5JV48A-aLkogQ', '2025-09-04 14:04:53', NULL, '2025-09-04 13:49:53', '2025-09-04 13:49:53', 38, 20),
(98, 35, 'p8vM7SJs-Ev9EgBd4bItKg-aLkogQ', '2025-09-04 14:04:53', '2025-09-04 13:50:47', NULL, '2025-09-04 13:49:53', 38, 20),
(99, 36, 'ZGmRkqsRGwBsJ0Bs8A8gzg-aLkohQ', '2025-09-04 14:04:57', NULL, '2025-09-04 13:49:57', '2025-09-04 13:49:57', 38, 20),
(100, 36, 'U9lzHxecODarAZ082dhfvA-aLkohQ', '2025-09-04 14:04:57', '2025-09-04 13:50:16', NULL, '2025-09-04 13:49:57', 38, 20),
(101, 37, '7Mpx-PYV7DnHfg0RqM-e4Q-aLko-A', '2025-09-04 14:06:52', NULL, '2025-09-04 13:52:23', '2025-09-04 13:51:52', 38, 20),
(102, 37, 'l88HM0m4uZYKZO-Ahxr91A-aLko-A', '2025-09-04 14:06:52', NULL, '2025-09-04 13:52:23', '2025-09-04 13:51:52', 38, 20),
(103, 37, 'GFUB6mi-h71oLJ3qNcGOOQ-aLkpFw', '2025-09-04 14:07:23', NULL, '2025-09-04 13:52:23', '2025-09-04 13:52:23', 38, 20),
(104, 37, 'qoBI5rCmvwUbNMw1UnOx1g-aLkpFw', '2025-09-04 14:07:23', '2025-09-04 13:52:43', NULL, '2025-09-04 13:52:23', 38, 20),
(105, 38, 'u7xYyureclzYqV0mc22qBQ-aLkpGw', '2025-09-04 14:07:27', NULL, '2025-09-04 13:52:27', '2025-09-04 13:52:27', 38, 20),
(106, 38, 'punlT2IaQ5XlJMMwItu7IA-aLkpGw', '2025-09-04 14:07:27', '2025-09-04 13:52:49', NULL, '2025-09-04 13:52:27', 38, 20),
(107, 39, 'Hu9ecSt1TdYFR78YFVG8wQ-aLkpHg', '2025-09-04 14:07:30', NULL, '2025-09-04 13:52:30', '2025-09-04 13:52:30', 38, 20),
(108, 39, 'kFbI2ajdWxBuDTxhNXEr2g-aLkpHg', '2025-09-04 14:07:30', '2025-09-04 13:52:55', NULL, '2025-09-04 13:52:30', 38, 20),
(109, 40, 'W9zp6ZxOLZm3Y9kYRxn8Yg-aLk7mg', '2025-09-05 15:11:22', NULL, '2025-09-04 15:37:15', '2025-09-04 15:11:22', 37, 20),
(110, 40, 'qjonsSJ1Luq-lHEljIgDtA-aLk7mg', '2025-09-05 15:11:22', NULL, '2025-09-04 15:37:15', '2025-09-04 15:11:22', 37, 20),
(111, 40, 'DClhdR2RXbsA6QuQhZrUdg-aLk7qA', '2025-09-05 15:11:36', NULL, '2025-09-04 15:37:15', '2025-09-04 15:11:36', 37, 20),
(112, 40, 'v5JD-M7BjOnLSqmT4EnHLg-aLk7qA', '2025-09-05 15:11:36', NULL, '2025-09-04 15:37:15', '2025-09-04 15:11:36', 37, 20),
(113, 40, 'u9Rhe-2aSWV8yoatIainSw-aLk7tg', '2025-09-05 15:11:50', NULL, '2025-09-04 15:37:15', '2025-09-04 15:11:50', 37, 20),
(114, 40, 'ob2M4Eetlx4KHQX2IsyopQ-aLk7tg', '2025-09-05 15:11:50', NULL, '2025-09-04 15:37:15', '2025-09-04 15:11:50', 37, 20),
(115, 40, 'y9nk7BUKz_qGMQvo0CqRqA-aLk7uQ', '2025-09-05 15:11:53', NULL, '2025-09-04 15:37:15', '2025-09-04 15:11:53', 37, 20),
(116, 40, '5o8tQS23kvufoQ0-4mBX0A-aLk7uQ', '2025-09-05 15:11:53', NULL, '2025-09-04 15:37:15', '2025-09-04 15:11:53', 37, 20),
(117, 55, 'kqzhd6wWC11txm7fF--P_w-aLk_WA', '2025-09-05 15:27:20', NULL, '2025-09-04 15:31:48', '2025-09-04 15:27:20', 37, 20),
(118, 55, 'wbJHaExEeZCqt4O3duYWzw-aLk_WA', '2025-09-05 15:27:20', '2025-09-04 15:29:25', NULL, '2025-09-04 15:27:20', 37, 20),
(119, 63, 'TvR5z9r5UPMrx90VRDhOvA-aLk_3Q', '2025-09-05 15:29:33', NULL, '2025-09-04 15:31:38', '2025-09-04 15:29:33', 37, 20),
(120, 63, '4Kx2p-p_hb3PV7e_nRQeYA-aLk_3Q', '2025-09-05 15:29:33', '2025-09-04 15:29:52', NULL, '2025-09-04 15:29:33', 37, 20),
(121, 63, 'PifYsC8xKyUF2ZFDKolChA-aLlAWg', '2025-09-05 15:31:38', NULL, '2025-09-04 15:31:38', '2025-09-04 15:31:38', 37, 20),
(122, 63, 'WxoZpuwCjjhRhVNWfGApFg-aLlAWg', '2025-09-05 15:31:38', '2025-09-04 15:32:07', NULL, '2025-09-04 15:31:38', 37, 20),
(123, 55, '9TX1ap3MZIom8fSJZtVuyg-aLlAZA', '2025-09-05 15:31:48', NULL, '2025-09-04 15:31:48', '2025-09-04 15:31:48', 37, 20),
(124, 55, '4blqydrL9W6k1CS4q5dLYQ-aLlAZA', '2025-09-05 15:31:48', '2025-09-04 15:32:01', NULL, '2025-09-04 15:31:48', 37, 20),
(125, 52, 'h0gn8pTsxGmvUmcKg6D9lQ-aLlAqg', '2025-09-05 15:32:58', '2025-09-04 15:33:23', NULL, '2025-09-04 15:32:58', 37, 20),
(126, 49, 'cVHkppAfGTd9nS0lWJAUrg-aLlA3w', '2025-09-05 15:33:51', '2025-09-04 15:34:12', NULL, '2025-09-04 15:33:51', 37, 20),
(127, 57, 'd08c2Yk9_tnhai7ZaWc_xg-aLlA_g', '2025-09-05 15:34:22', NULL, '2025-09-04 15:34:22', '2025-09-04 15:34:22', 37, 20),
(128, 57, 'TN1APXcFRr9HLh0Xh_9eUw-aLlA_g', '2025-09-05 15:34:22', '2025-09-04 15:34:44', NULL, '2025-09-04 15:34:22', 37, 20),
(129, 40, 'vknEA69WPEqIKwguZRZa8A-aLlBqw', '2025-09-05 15:37:15', '2025-09-04 15:37:43', NULL, '2025-09-04 15:37:15', 37, 20),
(130, 60, 'qcpIP2XDtvjMsxUyXJQWvw-aLlB1A', '2025-09-05 15:37:56', '2025-09-04 15:38:25', NULL, '2025-09-04 15:37:56', 37, 20),
(131, 59, 'pcxrv3M6-vg-2mqvy7YuAw-aLlCDw', '2025-09-05 15:38:55', NULL, '2025-09-04 15:38:56', '2025-09-04 15:38:55', 37, 20),
(132, 59, 'IDEn6T1JCI5qytKWRZ2Oig-aLlCEA', '2025-09-05 15:38:56', '2025-09-04 15:39:25', NULL, '2025-09-04 15:38:56', 37, 20),
(133, 61, 'ALNex29CwjyTcRutuVDwLw-aLlCOg', '2025-09-05 15:39:38', '2025-09-04 15:39:54', NULL, '2025-09-04 15:39:38', 37, 20),
(134, 56, 'ybu2udBSk_Ju9p3PmaTwgg-aLlCWg', '2025-09-05 15:40:10', NULL, '2025-09-04 15:40:10', '2025-09-04 15:40:10', 37, 20),
(135, 56, 'BEsQoJ2MIA_W7CqvsxVqzg-aLlCWg', '2025-09-05 15:40:10', '2025-09-04 15:40:30', NULL, '2025-09-04 15:40:10', 37, 20),
(136, 46, 'NIgdC3swHxWM6bDwJ4KpDw-aLlC4A', '2025-09-05 15:42:24', '2025-09-04 15:42:48', NULL, '2025-09-04 15:42:24', 37, 20),
(137, 50, 'eGpB5-si1JFJSCQgUAOizA-aLlDBw', '2025-09-05 15:43:03', '2025-09-04 15:43:34', NULL, '2025-09-04 15:43:03', 37, 20),
(138, 62, 'lP8NRfVkTf1EsRxgKfUZiQ-aLlDZA', '2025-09-05 15:44:36', '2025-09-04 15:45:08', NULL, '2025-09-04 15:44:36', 37, 20),
(139, 47, 'RwFg3RDMiAirszThYruEhg-aLlDmg', '2025-09-05 15:45:30', '2025-09-04 15:45:53', NULL, '2025-09-04 15:45:30', 37, 20),
(140, 64, '70anf8ucqBh-89zU1LHbng-aLlDvg', '2025-09-05 15:46:06', '2025-09-04 15:46:19', NULL, '2025-09-04 15:46:06', 37, 20),
(141, 43, 'sMu2eaEnYVQmDNKZ6ic90Q-aLlD3w', '2025-09-05 15:46:39', '2025-09-04 15:46:56', NULL, '2025-09-04 15:46:39', 37, 20),
(142, 44, 'sWV5TZkeQ79XkKQO-YCE1w-aLlEiQ', '2025-09-05 15:49:29', '2025-09-04 15:50:12', NULL, '2025-09-04 15:49:29', 37, 20),
(143, 48, '_MDtVKi1emu_TOVyVSOPag-aLlEvg', '2025-09-05 15:50:22', '2025-09-04 15:50:49', NULL, '2025-09-04 15:50:22', 37, 20),
(144, 54, 'H-Awx3KLgQbfBczaKLRYfg-aLlE6w', '2025-09-05 15:51:07', '2025-09-04 15:51:45', NULL, '2025-09-04 15:51:07', 37, 20),
(145, 41, 'gFx0sYjbsyL74hl_T5RSWQ-aLlFGQ', '2025-09-05 15:51:53', NULL, '2025-09-04 15:52:09', '2025-09-04 15:51:53', 37, 20),
(146, 41, 'GiYEQmu-dNlF5-dxDcOzIw-aLlFKQ', '2025-09-05 15:52:09', '2025-09-04 15:52:19', NULL, '2025-09-04 15:52:09', 37, 20),
(147, 53, 'kHleCDKKDQr25RHWnY-Bxg-aLlFOg', '2025-09-05 15:52:26', '2025-09-04 15:52:48', NULL, '2025-09-04 15:52:26', 37, 20),
(148, 65, 'aD_kwwfZfgblEEXBYu-RxQ-aLv90Q', '2025-09-06 17:25:33', NULL, '2025-09-06 17:24:33', '2025-09-06 17:24:33', 43, 21),
(149, 65, 'pMENKAYvSDRVvmVdN-CnOA-aLv90Q', '2025-09-06 17:25:33', NULL, NULL, '2025-09-06 17:24:33', 43, 21),
(150, 65, 'jZQuJS4yw2nEpTu_1BrS5w-aLwCPA', '2025-09-06 17:44:24', NULL, '2025-09-06 17:43:24', '2025-09-06 17:43:24', 43, 21),
(151, 65, 'r3qchoSvLA3cY1mZArxj6w-aLwCPA', '2025-09-06 17:44:24', NULL, NULL, '2025-09-06 17:43:24', 43, 21),
(152, 65, 'ZAnA66h9K_S-WWUkElG0gQ-aLwClg', '2025-09-06 17:45:54', NULL, '2025-09-06 17:44:54', '2025-09-06 17:44:54', 43, 21),
(153, 65, 'n273Hg8LkhFBq3pshqD8og-aLwClg', '2025-09-06 17:45:54', NULL, NULL, '2025-09-06 17:44:54', 43, 21),
(154, 65, 't40c4yOfVwfjokvLQ1i2bA-aLwDjw', '2025-09-06 17:50:03', NULL, '2025-09-06 17:49:03', '2025-09-06 17:49:03', 43, 21),
(155, 65, 'kULaa6Gsu1yEgLDwuJQJjQ-aLwDjw', '2025-09-06 17:50:03', NULL, NULL, '2025-09-06 17:49:03', 43, 21),
(156, 66, 'UqozJnOO43Rj1UaUdt6Jwg-aLwIMg', '2025-09-06 18:09:50', NULL, NULL, '2025-09-06 18:08:50', 43, 21),
(157, 65, 'v5tvAkr6p_VRFFr4u8aU9g-aLwIfA', '2025-09-06 18:15:04', NULL, '2025-09-06 18:10:04', '2025-09-06 18:10:04', 43, 21),
(158, 65, 's9-5MkGND3H2NcSl5Ueqmg-aLwIfA', '2025-09-06 18:15:04', '2025-09-06 18:10:17', NULL, '2025-09-06 18:10:04', 43, 21),
(159, 67, 'Hkdnn5Qvlr4z7UrmCB20Ew-aLwI-Q', '2025-09-06 18:13:09', NULL, '2025-09-06 18:12:09', '2025-09-06 18:12:09', 43, 21),
(160, 67, 'GO4lZxO18tpLAQPy2QLLqg-aLwI-Q', '2025-09-06 18:13:09', '2025-09-06 18:12:30', NULL, '2025-09-06 18:12:09', 43, 21),
(161, 68, 'jrwEJ0rfKmkO_KhTWCSLEg-aLwJAQ', '2025-09-06 18:13:17', NULL, '2025-09-06 18:12:17', '2025-09-06 18:12:17', 43, 21),
(162, 68, 'VJ_FQw-wq3OzXvBg2-yJHA-aLwJAQ', '2025-09-06 18:13:17', '2025-09-06 18:12:37', NULL, '2025-09-06 18:12:17', 43, 21),
(163, 69, '6I-BhRH99NIGrQ3yvDi3jQ-aLwfuw', '2025-09-06 19:50:15', NULL, '2025-09-06 19:49:15', '2025-09-06 19:49:15', 43, 21),
(164, 69, 'be0c_SFRn_XkSDVyag0uPg-aLwfuw', '2025-09-06 19:50:15', '2025-09-06 19:49:39', NULL, '2025-09-06 19:49:15', 43, 21);

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
(15, 3, '2025-08-30 12:40:00', 'On Time', 'general', NULL, NULL, NULL, 2, 26),
(16, 12, '2025-09-03 12:42:57', 'On Time', 'general', NULL, 28, 10, 17, 31),
(17, 14, '2025-09-04 01:12:44', 'On Time', 'general', NULL, 30, 11, 20, 34),
(18, 15, '2025-09-04 01:15:11', 'On Time', 'general', NULL, 30, 11, 20, 34),
(19, 16, '2025-09-04 01:16:52', 'On Time', 'general', NULL, 30, 11, 20, 34),
(20, 17, '2025-09-04 01:18:30', 'On Time', 'general', NULL, 30, 11, 20, 34),
(21, 18, '2025-09-04 01:19:55', 'On Time', 'general', NULL, 30, 11, 20, 34),
(22, 19, '2025-09-04 01:21:10', 'On Time', 'general', NULL, 30, 11, 20, 34),
(23, 20, '2025-09-04 01:22:17', 'On Time', 'general', NULL, 30, 11, 20, 34),
(24, 21, '2025-09-04 01:23:53', 'On Time', 'general', NULL, 30, 11, 20, 34),
(25, 22, '2025-09-04 01:24:53', 'On Time', 'general', NULL, 30, 11, 20, 34),
(26, 23, '2025-09-04 01:26:15', 'On Time', 'general', NULL, 30, 11, 20, 34),
(27, 24, '2025-09-04 01:27:31', 'On Time', 'general', NULL, 30, 11, 20, 34),
(28, 25, '2025-09-04 01:28:34', 'On Time', 'general', NULL, 30, 11, 20, 34),
(29, 26, '2025-09-04 01:29:34', 'On Time', 'general', NULL, 30, 11, 20, 34),
(30, 27, '2025-09-04 01:30:59', 'On Time', 'general', NULL, 30, 11, 20, 34),
(31, 28, '2025-09-04 01:32:13', 'On Time', 'general', NULL, 30, 11, 20, 34),
(32, 29, '2025-09-04 01:33:45', 'On Time', 'general', NULL, 30, 11, 20, 34),
(33, 30, '2025-09-04 01:35:02', 'On Time', 'general', NULL, 30, 11, 20, 34),
(34, 31, '2025-09-04 01:36:02', 'On Time', 'general', NULL, 30, 11, 20, 34),
(35, 32, '2025-09-04 01:37:22', 'On Time', 'general', NULL, 30, 11, 20, 34),
(36, 33, '2025-09-04 01:39:44', 'On Time', 'general', NULL, 30, 11, 20, 34),
(37, 34, '2025-09-04 01:40:44', 'On Time', 'general', NULL, 30, 11, 20, 34),
(38, 36, '2025-09-04 05:50:16', 'On Time', 'general', NULL, 32, 12, 20, 38),
(39, 35, '2025-09-04 05:50:47', 'On Time', 'general', NULL, 32, 12, 20, 38),
(40, 37, '2025-09-04 05:52:43', 'On Time', 'general', NULL, 32, 12, 20, 38),
(41, 38, '2025-09-04 05:52:49', 'On Time', 'general', NULL, 32, 12, 20, 38),
(42, 39, '2025-09-04 05:52:55', 'On Time', 'general', NULL, 32, 12, 20, 38),
(45, 55, '2025-09-04 07:32:01', 'On Time', 'general', NULL, 31, 13, 20, 37),
(46, 63, '2025-09-04 07:32:07', 'On Time', 'general', NULL, 31, 13, 20, 37),
(47, 52, '2025-09-04 07:33:23', 'On Time', 'general', NULL, 31, 13, 20, 37),
(48, 49, '2025-09-04 07:34:12', 'On Time', 'general', NULL, 31, 13, 20, 37),
(49, 57, '2025-09-04 07:34:44', 'On Time', 'general', NULL, 31, 13, 20, 37),
(50, 40, '2025-09-04 07:37:43', 'On Time', 'general', NULL, 31, 13, 20, 37),
(51, 60, '2025-09-04 07:38:25', 'On Time', 'general', NULL, 31, 13, 20, 37),
(52, 59, '2025-09-04 07:39:25', 'On Time', 'general', NULL, 31, 13, 20, 37),
(53, 61, '2025-09-04 07:39:54', 'On Time', 'general', NULL, 31, 13, 20, 37),
(54, 56, '2025-09-04 07:40:30', 'On Time', 'general', NULL, 31, 13, 20, 37),
(55, 46, '2025-09-04 07:42:48', 'On Time', 'general', NULL, 31, 13, 20, 37),
(56, 50, '2025-09-04 07:43:34', 'On Time', 'general', NULL, 31, 13, 20, 37),
(57, 62, '2025-09-04 07:45:08', 'On Time', 'general', NULL, 31, 13, 20, 37),
(58, 47, '2025-09-04 07:45:53', 'On Time', 'general', NULL, 31, 13, 20, 37),
(59, 64, '2025-09-04 07:46:19', 'On Time', 'general', NULL, 31, 13, 20, 37),
(60, 43, '2025-09-04 07:46:56', 'On Time', 'general', NULL, 31, 13, 20, 37),
(61, 44, '2025-09-04 07:50:12', 'On Time', 'general', NULL, 31, 13, 20, 37),
(62, 48, '2025-09-04 07:50:49', 'On Time', 'general', NULL, 31, 13, 20, 37),
(63, 54, '2025-09-04 07:51:45', 'On Time', 'general', NULL, 31, 13, 20, 37),
(64, 41, '2025-09-04 07:52:19', 'On Time', 'general', NULL, 31, 13, 20, 37),
(65, 53, '2025-09-04 07:52:48', 'On Time', 'general', NULL, 31, 13, 20, 37),
(66, 65, '2025-09-06 09:49:25', 'Late', 'general', NULL, 34, 0, 21, 43),
(67, 65, '2025-09-06 09:50:04', 'On Time', 'general', NULL, 0, 0, 21, 43),
(68, 65, '2025-09-06 09:57:31', 'On Time', 'general', NULL, 0, 14, 21, 43),
(69, 65, '2025-09-06 09:58:14', 'Late', 'general', NULL, 34, 15, 21, 43),
(70, 66, '2025-09-06 09:59:03', 'Late', 'general', NULL, 34, 15, 21, 43),
(71, 65, '2025-09-06 09:59:46', 'On Time', 'general', NULL, 0, 16, 21, 43),
(72, 66, '2025-09-06 09:59:51', 'On Time', 'general', NULL, 34, 16, 21, 43),
(73, 66, '2025-09-06 10:09:29', 'On Time', 'general', NULL, 0, 17, 21, 43),
(74, 66, '2025-09-06 10:09:35', 'On Time', 'general', NULL, 34, 17, 21, 43),
(75, 65, '2025-09-06 10:10:17', 'On Time', 'general', NULL, 34, 17, 21, 43),
(76, 67, '2025-09-06 10:12:30', 'On Time', 'general', NULL, 34, 17, 21, 43),
(77, 68, '2025-09-06 10:12:37', 'On Time', 'general', NULL, 34, 17, 21, 43),
(78, 66, '2025-09-06 10:14:13', 'On Time', 'general', NULL, 0, 18, 21, 43),
(79, 65, '2025-09-06 10:14:26', 'On Time', 'general', NULL, 34, 18, 21, 43),
(80, 65, '2025-09-06 11:21:10', 'On Time', 'general', NULL, 34, 19, 21, 43),
(81, 66, '2025-09-06 11:26:50', 'On Time', 'general', NULL, 34, 19, 21, 43),
(82, 66, '2025-09-06 11:47:57', 'On Time', 'general', NULL, 0, 20, 21, 43),
(83, 65, '2025-09-06 11:48:06', 'On Time', 'general', NULL, 34, 20, 21, 43),
(84, 69, '2025-09-06 11:49:39', 'On Time', 'general', NULL, 34, 20, 21, 43),
(85, 69, '2025-09-06 11:50:36', 'Late', 'general', NULL, 34, 19, 21, 43),
(86, 69, '2025-09-06 11:50:47', 'Late', 'general', NULL, 34, 15, 21, 43);

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
(49, 'ICT', 28, 2, '2025-08-30 02:50:41'),
(50, '1ST YEAR', 31, 17, '2025-09-03 12:41:10'),
(51, 'BSIS', 34, 20, '2025-09-04 01:06:02'),
(52, 'BSIT', 38, 20, '2025-09-04 05:48:53'),
(53, 'BSCS', 38, 20, '2025-09-04 05:51:49'),
(54, 'BSIS', 37, 20, '2025-09-04 06:28:48'),
(55, 'TEST 1', 43, 21, '2025-09-06 09:11:08'),
(56, 'TEST 2', 43, 21, '2025-09-06 11:49:12');

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
(87, 10, 'krabby', 'Success', '2025-08-30 03:43:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 2, 28),
(88, 12, 'david joshua j. bayot', 'Success', '2025-09-03 20:41:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 17, 31),
(89, 13, 'Jerson A. AGATON', 'Success', '2025-09-04 09:05:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(90, 14, 'heinz rey ngsang', 'Success', '2025-09-04 09:12:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(91, 15, 'Anthony J.Flores', 'Success', '2025-09-04 09:14:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(92, 16, 'jewel christan R Monte', 'Success', '2025-09-04 09:16:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(93, 17, 'Kim Andrew Raras', 'Success', '2025-09-04 09:17:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(94, 18, 'Marc Angel M. Paner', 'Success', '2025-09-04 09:19:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(95, 19, 'johndave fedelino', 'Success', '2025-09-04 09:20:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(96, 20, 'james ryan letun patalud', 'Success', '2025-09-04 09:21:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(97, 21, 'jerson agaton', 'Success', '2025-09-04 09:22:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(98, 22, 'janrey b. yap', 'Success', '2025-09-04 09:24:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(99, 23, 'jade rhemil B. esller', 'Success', '2025-09-04 09:25:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(100, 24, 'Kenneth P. Dimatulac', 'Success', '2025-09-04 09:26:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(101, 25, 'Vonn Jomar V. Verzosa', 'Success', '2025-09-04 09:28:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(102, 26, 'daniela julia v avengoza', 'Success', '2025-09-04 09:29:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(103, 27, 'jedeail J. Evasco', 'Success', '2025-09-04 09:30:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(104, 28, 'rhondelle c. Jocosol', 'Success', '2025-09-04 09:31:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(105, 29, 'Jervie C. pepito', 'Success', '2025-09-04 09:32:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(106, 30, 'euro kein i. torre', 'Success', '2025-09-04 09:34:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(107, 31, 'Sylen B. Olvinar', 'Success', '2025-09-04 09:35:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(108, 32, 'Paul Josiah T. Tizon', 'Success', '2025-09-04 09:36:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(109, 33, 'lyndon fredrick alvarado', 'Success', '2025-09-04 09:38:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(110, 34, 'Joshua Anthony G. Magcamit', 'Success', '2025-09-04 09:40:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 34),
(111, 35, 'btsbayot', 'Success', '2025-09-04 13:48:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 38),
(112, 36, 'tensionado', 'Success', '2025-09-04 13:49:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 38),
(113, 37, 'alksdja', 'Success', '2025-09-04 13:51:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 38),
(114, 38, 'jom', 'Success', '2025-09-04 13:51:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 38),
(115, 39, 'ash', 'Success', '2025-09-04 13:52:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 38),
(116, NULL, 'AGATON, Jerson A.', 'Success', '2025-09-04 14:27:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(117, 40, 'AGATON, Jerson A.', 'Success', '2025-09-04 14:28:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(118, 41, 'ALVARADO, Lyndon Fredrick', 'Success', '2025-09-04 14:31:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(119, 42, 'ALVARADO, Lyndon Fredrick', 'Success', '2025-09-04 14:32:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(120, 43, 'CASAIS, Kenneth B.', 'Success', '2025-09-04 14:33:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(121, 44, 'AVENGOZA, Daniela Julia V.', 'Success', '2025-09-04 14:35:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(122, 45, 'CULAWAY, Wisdom R.', 'Success', '2025-09-04 14:36:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(123, 46, 'DIMATULAC, Kenneth P.', 'Success', '2025-09-04 14:37:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(124, 47, 'ESLLER, Jade Rhemil B.', 'Success', '2025-09-04 14:37:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(125, 48, 'EVASCO, Jedeail J.', 'Success', '2025-09-04 14:38:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(126, 49, 'FEDELINO, John Dave O.', 'Success', '2025-09-04 14:39:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(127, 50, 'FLORES, Anthony J.', 'Success', '2025-09-04 14:39:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(128, 51, 'JARA, Dominic C.', 'Success', '2025-09-04 14:40:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(129, 52, 'JOCOSOL, Rhondelle C.', 'Success', '2025-09-04 14:40:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(130, 53, 'MAGCAMIT, Joshua Anthony G.', 'Success', '2025-09-04 14:40:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(131, 54, 'MONTE, Jewel Christan R.', 'Success', '2025-09-04 14:41:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(132, 55, 'NGSANG, Heinz Rey L.', 'Success', '2025-09-04 14:41:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(133, 56, 'OLVINAR, Sylen B.', 'Success', '2025-09-04 14:42:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(134, 57, 'PANER, Marc Angel M.', 'Success', '2025-09-04 14:42:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(135, 58, 'PATALUD, James Ryan L.', 'Success', '2025-09-04 14:44:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(136, 59, 'PEPITO, Jervie C.', 'Success', '2025-09-04 14:45:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(137, 60, 'RARAS, Kim Andrew G.', 'Success', '2025-09-04 14:45:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(138, 61, 'TIZON, Paul Josiah T.', 'Success', '2025-09-04 14:46:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(139, 62, 'TORRE, Euro Kein T.', 'Success', '2025-09-04 14:46:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(140, 63, 'VERZOSA, Vonn Jomar V.', 'Success', '2025-09-04 14:47:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(141, 64, 'YAP, Janrey B.', 'Success', '2025-09-04 14:47:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Face captured during registration', 20, 37),
(142, 65, 'test', 'Success', '2025-09-06 17:11:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Face captured during registration', 21, 43),
(143, 66, 'test 2', 'Success', '2025-09-06 17:58:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Face captured during registration', 21, 43),
(144, 67, 'test 3', 'Success', '2025-09-06 18:11:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Face captured during registration', 21, 43),
(145, 68, 'test 4', 'Success', '2025-09-06 18:11:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Face captured during registration', 21, 43),
(146, 69, 'test 5', 'Success', '2025-09-06 19:49:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Face captured during registration', 21, 43);

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
(27, 'SPCPC', '', '2025-08-23 12:53:15', 1, NULL),
(28, 'admin', '', '2025-09-03 12:39:58', 1, NULL),
(29, 'Wency_Trapago', '', '2025-09-03 13:23:06', 1, NULL),
(30, 'Arnold_Aranaydo', '', '2025-09-03 13:24:30', 1, NULL),
(31, 'Wency Trapago', '', '2025-09-04 01:58:09', 1, NULL),
(32, 'BaklangNaglalaptop', '', '2025-09-04 05:48:18', 1, NULL),
(33, 'escall test', '', '2025-09-04 11:07:50', 1, NULL),
(34, 'comsi', '', '2025-09-06 09:12:04', 1, NULL),
(35, 'Arnold Aranaydo', '', '2025-09-06 11:11:32', 1, NULL);

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
(8, 0, 8, '2025-08-29 19:47:29', 1),
(9, 28, 10, '2025-09-03 12:42:10', 1),
(10, 30, 11, '2025-09-04 01:07:25', 1),
(11, 32, 12, '2025-09-04 05:50:06', 1),
(12, 31, 13, '2025-09-04 07:29:14', 1),
(13, 34, 19, '2025-09-06 11:20:56', 1),
(14, 0, 20, '2025-09-06 11:47:41', 1),
(15, 0, 19, '2025-09-06 11:50:13', 1),
(16, 0, 15, '2025-09-06 11:50:43', 1);

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
(213, '302', 1, 1, '2025-08-24 13:00:38', 51),
(214, '402', 30, 15, '2025-08-24 13:03:21', 44),
(215, '12 EVOLVE', 30, 15, '2025-08-24 13:03:47', 45),
(216, '12 EVOLVE', 1, 1, '2025-08-24 13:04:58', NULL),
(217, '402', 1, 1, '2025-08-24 13:11:14', NULL),
(218, '403', 1, 1, '2025-08-24 13:16:40', NULL),
(219, '12 TEKNO', 28, 2, '2025-08-29 19:43:52', 48),
(220, 'LABRADOG', 31, 17, '2025-09-03 12:41:10', 50),
(221, '403', 38, 20, '2025-09-04 05:48:53', 52),
(222, '301', 38, 20, '2025-09-04 05:51:49', 53),
(223, '302', 37, 20, '2025-09-04 06:28:48', 54),
(224, 'TEST', 43, 21, '2025-09-06 09:11:08', 56);

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
(13, 'Arnold Aranaydo', 'BSIS - 302', 'STU-34-20-5a66354b-4579ef6c55f3e166', NULL, 'face_1756947962_68b8e5fa1063d.jpg', 20, 34),
(14, 'heinz rey ngsang', 'BSIS - 302', 'STU-34-20-b8032e81-656adcf335ce1f03', NULL, 'face_1756948338_68b8e7720cde1.jpg', 20, 34),
(15, 'Anthony J.Flores', 'BSIS - 302', 'STU-34-20-9ae1ff1a-fb9626436aa429aa', NULL, 'face_1756948485_68b8e805a1fb4.jpg', 20, 34),
(16, 'jewel christan R Monte', 'BSIS - 302', 'STU-34-20-7ee948c6-f79f76ae5bdbe7ca', NULL, 'face_1756948585_68b8e869a9df9.jpg', 20, 34),
(17, 'Kim Andrew Raras', 'BSIS - 302', 'STU-34-20-f522e9a0-c4fff2bce27c8276', NULL, 'face_1756948677_68b8e8c553f33.jpg', 20, 34),
(18, 'Marc Angel M. Paner', 'BSIS - 302', 'STU-34-20-98509563-69d201c36f3dbf3e', NULL, 'face_1756948778_68b8e92a0cd5f.jpg', 20, 34),
(19, 'johndave fedelino', 'BSIS - 302', 'STU-34-20-83803228-94fd46c47a752b1d', NULL, 'face_1756948844_68b8e96cf0928.jpg', 20, 34),
(20, 'james ryan letun patalud', 'BSIS - 302', 'STU-34-20-f5a3d939-8b0d6aad0648497c', NULL, 'face_1756948914_68b8e9b2a132f.jpg', 20, 34),
(21, 'jerson agaton', 'BSIS - 302', 'STU-34-20-7637bbe1-d09f902a1e5d1ac0', NULL, 'face_1756948978_68b8e9f27d21c.jpg', 20, 34),
(22, 'janrey b. yap ', 'BSIS - 302', 'STU-34-20-42fad93e-d245173cdc79df5e', NULL, 'face_1756949068_68b8ea4c8416f.jpg', 20, 34),
(23, 'jade rhemil B. esller', 'BSIS - 302', 'STU-34-20-cd776c56-c6b253a33c1a2a4e', NULL, 'face_1756949143_68b8ea9709c5c.jpg', 20, 34),
(24, 'Kenneth P. Dimatulac', 'BSIS - 302', 'STU-34-20-2381b4ce-e1061c171f568deb', NULL, 'face_1756949221_68b8eae524910.jpg', 20, 34),
(25, 'Vonn Jomar V. Verzosa', 'BSIS - 302', 'STU-34-20-78eeef7e-25b6287b852ac2c6', NULL, 'face_1756949293_68b8eb2d8723e.jpg', 20, 34),
(26, 'daniela julia v avengoza', 'BSIS - 302', 'STU-34-20-a305f520-b09148f7ec378287', NULL, 'face_1756949357_68b8eb6d4254a.jpg', 20, 34),
(27, 'jedeail J. Evasco', 'BSIS - 302', 'STU-34-20-06c59716-a275e5d6f76c2761', NULL, 'face_1756949437_68b8ebbd873b7.jpg', 20, 34),
(28, 'rhondelle c. Jocosol', 'BSIS - 302', 'STU-34-20-4ff103df-1d80b6e1cf06fff5', NULL, 'face_1756949514_68b8ec0ad1f93.jpg', 20, 34),
(29, 'Jervie C. pepito ', 'BSIS - 302', 'STU-34-20-dc888ab7-884ccfa3eddde755', NULL, 'face_1756949585_68b8ec51ac431.jpg', 20, 34),
(30, 'euro kein i. torre', 'BSIS - 302', 'STU-34-20-53fa4acb-6fdac3955ee2cb6a', NULL, 'face_1756949679_68b8ecaf9e4dc.jpg', 20, 34),
(31, 'Sylen B. Olvinar', 'BSIS - 302', 'STU-34-20-a939e975-74184ae62b612464', NULL, 'face_1756949741_68b8ecede7552.jpg', 20, 34),
(32, 'Paul Josiah T. Tizon', 'BSIS - 302', 'STU-34-20-9eb6f962-c186f2a2092f8ce5', NULL, 'face_1756949821_68b8ed3d67c9b.jpg', 20, 34),
(33, 'lyndon fredrick alvarado', 'BSIS - 302', 'STU-34-20-ef6d36a7-9af890f3c1d9edf4', NULL, 'face_1756949893_68b8ed856f334.jpg', 20, 34),
(34, 'Joshua Anthony G. Magcamit', 'BSIS - 302', 'STU-34-20-0edb3d74-7ac792747c3e9096', NULL, 'face_1756950023_68b8ee0798fd1.jpg', 20, 34),
(35, 'btsbayot', 'BSIT - 403', 'STU-38-20-9b6e456d-82b982fe8330033c', NULL, 'face_1756964933_68b92845a5042.jpg', 20, 38),
(36, 'tensionado', 'BSIT - 403', 'STU-38-20-993ce487-47adeb5394f00041', NULL, 'face_1756964955_68b9285bb232c.jpg', 20, 38),
(37, 'alksdja', 'BSIT - 403', 'STU-38-20-27724b36-496ed1a20906d52b', NULL, 'face_1756965079_68b928d7cdeb4.jpg', 20, 38),
(38, 'jom', 'BSCS - 301', 'STU-38-20-ad791e96-4f025ee4da5ca39e', NULL, 'face_1756965109_68b928f5ce817.jpg', 20, 38),
(39, 'ash', 'BSCS - 301', 'STU-38-20-40484c4a-42a9fbd53ed6726f', NULL, 'face_1756965140_68b929144c531.jpg', 20, 38),
(40, 'AGATON, Jerson A.', 'BSIS - 302', 'STU-37-20-c2cc9b42-89feb25eb1596737', NULL, 'face_1756967328_68b931a0c6375.jpg', 20, 37),
(41, 'ALVARADO, Lyndon Fredrick', 'BSIS - 302', 'STU-37-20-46372dfa-ed1fb501ae8b1e80', NULL, 'face_1756967521_68b93261ac1d2.jpg', 20, 37),
(43, 'CASAIS, Kenneth B.', 'BSIS - 302', 'STU-37-20-bb1822b3-6badcba73830d9bc', NULL, 'face_1756967625_68b932c9bf957.jpg', 20, 37),
(44, 'AVENGOZA, Daniela Julia V.', 'BSIS - 302', 'STU-37-20-44198afe-9e61a8d3ccab357e', NULL, 'face_1756967711_68b9331f7ccd1.jpg', 20, 37),
(45, 'CULAWAY, Wisdom R.', 'BSIS - 302', 'STU-37-20-6f423336-997fac645367a530', NULL, 'face_1756967810_68b933828e746.jpg', 20, 37),
(46, 'DIMATULAC, Kenneth P.', 'BSIS - 302', 'STU-37-20-22a19a45-844785a197350b0b', NULL, 'face_1756967845_68b933a5a4d27.jpg', 20, 37),
(47, 'ESLLER, Jade Rhemil B.', 'BSIS - 302', 'STU-37-20-e70d468f-ea70a7bb38750d13', NULL, 'face_1756967875_68b933c3dd583.jpg', 20, 37),
(48, 'EVASCO, Jedeail J.', 'BSIS - 302', 'STU-37-20-343636c6-0ae2e4001787164f', NULL, 'face_1756967922_68b933f2615ab.jpg', 20, 37),
(49, 'FEDELINO, John Dave O.', 'BSIS - 302', 'STU-37-20-e21a2899-a7d5dd8da143fc4a', NULL, 'face_1756967950_68b9340e54e01.jpg', 20, 37),
(50, 'FLORES, Anthony J.', 'BSIS - 302', 'STU-37-20-c1421db5-0eb65adc5391b5d2', NULL, 'face_1756967982_68b9342e3f521.jpg', 20, 37),
(51, 'JARA, Dominic C.', 'BSIS - 302', 'STU-37-20-30277255-608c72a7e4c9f786', NULL, 'face_1756968009_68b93449781dc.jpg', 20, 37),
(52, 'JOCOSOL, Rhondelle C.', 'BSIS - 302', 'STU-37-20-6259a981-72eb5cb9c80fe516', NULL, 'face_1756968036_68b934644518d.jpg', 20, 37),
(53, 'MAGCAMIT, Joshua Anthony G.', 'BSIS - 302', 'STU-37-20-98578bd7-2066fa38dd78840a', NULL, 'face_1756968064_68b9348089cf5.jpg', 20, 37),
(54, 'MONTE, Jewel Christan R.', 'BSIS - 302', 'STU-37-20-8a2154cd-40c688fa86ca534e', NULL, 'face_1756968090_68b9349a8ec76.jpg', 20, 37),
(55, 'NGSANG, Heinz Rey L.', 'BSIS - 302', 'STU-37-20-92f48e21-0cbaf81064b573c1', NULL, 'face_1756968121_68b934b9d5288.jpg', 20, 37),
(56, 'OLVINAR, Sylen B.', 'BSIS - 302', 'STU-37-20-70580a8d-bd79aaa1df0f36d4', NULL, 'face_1756968147_68b934d30dbdb.jpg', 20, 37),
(57, 'PANER, Marc Angel M.', 'BSIS - 302', 'STU-37-20-e1587bd3-4301c71441876513', NULL, 'face_1756968173_68b934ed9e134.jpg', 20, 37),
(58, 'PATALUD, James Ryan L.', 'BSIS - 302', 'STU-37-20-fa90275e-76e38903eaa08799', NULL, 'face_1756968247_68b93537be188.jpg', 20, 37),
(59, 'PEPITO, Jervie C.', 'BSIS - 302', 'STU-37-20-c62b2b09-6adf6c55858258f8', NULL, 'face_1756968324_68b93584df4a3.jpg', 20, 37),
(60, 'RARAS, Kim Andrew G.', 'BSIS - 302', 'STU-37-20-c2704fea-69bdea95564c7007', NULL, 'face_1756968356_68b935a49f54e.jpg', 20, 37),
(61, 'TIZON, Paul Josiah T.', 'BSIS - 302', 'STU-37-20-50839082-d71c7629b87f139d', NULL, 'face_1756968387_68b935c3f1e85.jpg', 20, 37),
(62, 'TORRE, Euro Kein T.', 'BSIS - 302', 'STU-37-20-313c3be4-e7de7fc28eb7c86b', NULL, 'face_1756968416_68b935e0e1d84.jpg', 20, 37),
(63, 'VERZOSA, Vonn Jomar V.', 'BSIS - 302', 'STU-37-20-e3addad4-bb904e1609002788', NULL, 'face_1756968454_68b936064b440.jpg', 20, 37),
(64, 'YAP, Janrey B.', 'BSIS - 302', 'STU-37-20-9268dd87-3c69012edd790779', NULL, 'face_1756968483_68b93623b11b2.jpg', 20, 37),
(65, 'test', 'TEST 1 - TEST', 'STU-43-21-3c38e9e1-7066efec762b86d6', NULL, 'face_1757149868_68bbfaac7fd7b.jpg', 21, 43),
(66, 'test 2', 'TEST 1 - TEST', 'STU-43-21-3c02b462-f24f90e7a44375ef', NULL, 'face_1757152720_68bc05d07e352.jpg', 21, 43),
(67, 'test 3', 'TEST 1 - TEST', 'STU-43-21-2a4e5e2f-b0531640e8c27f68', NULL, 'face_1757153496_68bc08d8e685e.jpg', 21, 43),
(68, 'test 4', 'TEST 1 - TEST', 'STU-43-21-5766eae2-0ad00c202eec0bf7', NULL, 'face_1757153519_68bc08efe067f.jpg', 21, 43),
(69, 'test 5', 'TEST 2 -TEST', 'STU-43-21-4b3ac2f4-d73e30e6d9a318ba', NULL, 'face_1757159352_68bc1fb872c6b.jpg', 21, 43);

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
(9, 'TEKNOLOHIYA', '2025-08-29 19:50:16', 2, 28),
(10, 'IAS', '2025-09-03 12:42:10', 17, 31),
(11, 'ENTRERPRENEUR ARCHITECTURE', '2025-09-04 01:07:25', 20, 34),
(12, 'DiwataPares', '2025-09-04 05:50:06', 20, 38),
(13, 'Technopreneurship', '2025-09-04 07:29:14', 20, 37),
(14, 'set 1', '2025-09-06 09:57:21', 21, 43),
(15, 'set 2', '2025-09-06 09:58:05', 21, 43),
(16, 'set 3', '2025-09-06 09:59:35', 21, 43),
(17, 'set 5', '2025-09-06 10:09:29', 21, 43),
(18, 'telekenesis', '2025-09-06 10:13:31', 21, 43),
(19, 'metal bending', '2025-09-06 11:20:56', 21, 43),
(20, 'blood bending', '2025-09-06 11:47:41', 21, 43);

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
(34, 'Arnold Aranaydo', 'Networkings', 'BSIS - 302', 'Monday', '09:00:00', '23:00:00', 'Computer Laboratory', 15, 30, '2025-08-23 14:21:34', '2025-09-06 11:11:30', 'active'),
(35, 'COMSITE', 'FILIPINO', 'STEM - 12 TEKNO', 'Monday', '12:00:00', '13:00:00', '204', 2, 28, '2025-08-29 19:45:31', '2025-08-29 19:45:31', 'active'),
(36, 'COMSITE', 'TEKNOLOHIYA', 'STEM - 12 TEKNO', 'Saturday', '13:00:00', '14:00:00', '200', 2, 28, '2025-08-29 19:45:57', '2025-08-29 19:45:57', 'active'),
(39, 'admin', 'IAS', '1ST YEAR-LABRADOG', 'Monday', '08:00:00', '10:00:00', 'room 101', 17, 31, '2025-09-03 12:41:48', '2025-09-03 12:41:48', 'active'),
(40, 'Arnold Aranaydo', 'ENTRERPRISE ARCHITECTURE', 'BSIS - 302', 'Thursday', '08:30:00', '10:00:00', 'Old Poso Office', 20, 34, '2025-09-04 01:07:17', '2025-09-06 11:12:56', 'active'),
(42, 'BaklangNaglalaptop', 'DiwataPares', 'BSIT - 403', 'Wednesday', '10:00:00', '12:00:00', 'Baguio', 20, 38, '2025-09-04 05:49:48', '2025-09-04 05:49:48', 'active'),
(43, 'Wency Trapago', 'Technopreneurship', 'BSIS - 302', 'Thursday', '18:00:00', '21:00:00', 'spti library', 20, 37, '2025-09-04 06:31:06', '2025-09-04 06:31:06', 'active'),
(44, 'comsi', 'blood bending', 'TEST 1 - TEST', 'Monday', '09:00:00', '10:00:00', '204', 21, 43, '2025-09-06 09:55:15', '2025-09-06 11:19:50', 'active'),
(45, 'comsi', 'set 2', 'TEST 1 - TEST', 'Tuesday', '10:00:00', '12:00:00', '200', 21, 43, '2025-09-06 09:55:35', '2025-09-06 09:55:35', 'active'),
(46, 'comsi', 'set 3', 'TEST 1 - TEST', 'Monday', '11:30:00', '15:00:00', '200', 21, 43, '2025-09-06 09:55:58', '2025-09-06 09:56:14', 'active'),
(47, 'comsi', 'set 4', 'TEST 1 - TEST', 'Wednesday', '12:00:00', '13:00:00', 'ROOM 101', 21, 43, '2025-09-06 09:56:43', '2025-09-06 09:56:43', 'active'),
(48, 'comsi', 'set 5', 'TEST 1 - TEST', 'Thursday', '09:00:00', '10:00:00', '1', 21, 43, '2025-09-06 09:57:10', '2025-09-06 09:57:10', 'active'),
(49, 'comsi', 'telekenesis', 'TEST 1 - TEST', 'Friday', '07:00:00', '12:00:00', '200', 21, 43, '2025-09-06 10:11:08', '2025-09-06 10:11:08', 'active'),
(50, 'comsi', 'metal bending', 'TEST 1 - TEST', 'Tuesday', '15:00:00', '18:00:00', 'Computer Laboratory', 21, 43, '2025-09-06 11:20:48', '2025-09-06 11:20:48', 'active');

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
(0, 37, 'DELETE', '{\"teacher_username\": \"COMSITE\", \"subject\": \"abcde\", \"section\": \"MLBB - 12 DAWNe\", \"day_of_week\": \"Tuesday\", \"start_time\": \"08:00:00\", \"end_time\": \"10:00:00\", \"room\": \"123\", \"school_id\": 2}', NULL, 'root@localhost', '2025-08-30 11:57:57'),
(0, 39, 'INSERT', NULL, '{\"teacher_username\": \"admin\", \"subject\": \"IAS\", \"section\": \"1ST YEAR-LABRADOG\", \"day_of_week\": \"Monday\", \"start_time\": \"08:00:00\", \"end_time\": \"10:00:00\", \"room\": \"room 101\", \"school_id\": 17}', 'root@localhost', '2025-09-03 12:41:48'),
(0, 40, 'INSERT', NULL, '{\"teacher_username\": \"Arnold_Aranaydo\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', 'root@localhost', '2025-09-04 01:07:17'),
(0, 41, 'INSERT', NULL, '{\"teacher_username\": \"Arnold_Aranaydo\", \"subject\": \"Technopreneurship\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"18:00:00\", \"end_time\": \"21:00:00\", \"room\": \"SPTI\", \"school_id\": 20}', 'root@localhost', '2025-09-04 01:52:02'),
(0, 41, 'DELETE', '{\"teacher_username\": \"Arnold_Aranaydo\", \"subject\": \"Technopreneurship\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"18:00:00\", \"end_time\": \"21:00:00\", \"room\": \"SPTI\", \"school_id\": 20}', NULL, 'root@localhost', '2025-09-04 01:55:15'),
(0, 42, 'INSERT', NULL, '{\"teacher_username\": \"BaklangNaglalaptop\", \"subject\": \"DiwataPares\", \"section\": \"BSIT - 403\", \"day_of_week\": \"Wednesday\", \"start_time\": \"10:00:00\", \"end_time\": \"12:00:00\", \"room\": \"Baguio\", \"school_id\": 20}', 'root@localhost', '2025-09-04 05:49:48'),
(0, 43, 'INSERT', NULL, '{\"teacher_username\": \"Wency Trapago\", \"subject\": \"Technopreneurship\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"18:00:00\", \"end_time\": \"21:00:00\", \"room\": \"spti library\", \"school_id\": 20}', 'root@localhost', '2025-09-04 06:31:06'),
(0, 34, 'UPDATE', '{\"teacher_username\": \"SPCPC\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', '{\"teacher_username\": \"arnold_aranaydo\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', 'root@localhost', '2025-09-04 11:40:05'),
(0, 34, 'UPDATE', '{\"teacher_username\": \"arnold_aranaydo\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', '{\"teacher_username\": \"SPCPC\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', 'root@localhost', '2025-09-04 11:40:39'),
(0, 40, 'UPDATE', '{\"teacher_username\": \"Arnold_Aranaydo\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', '{\"teacher_username\": \"SPCPC\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', 'root@localhost', '2025-09-04 11:40:39'),
(0, 34, 'UPDATE', '{\"teacher_username\": \"SPCPC\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', '{\"teacher_username\": \"Qnnect\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', 'root@localhost', '2025-09-04 11:52:01'),
(0, 40, 'UPDATE', '{\"teacher_username\": \"SPCPC\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', '{\"teacher_username\": \"Qnnect\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', 'root@localhost', '2025-09-04 11:52:01'),
(0, 34, 'UPDATE', '{\"teacher_username\": \"Qnnect\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', '{\"teacher_username\": \"arnold_aranaydo\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', 'root@localhost', '2025-09-04 11:53:32'),
(0, 40, 'UPDATE', '{\"teacher_username\": \"Qnnect\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', '{\"teacher_username\": \"arnold_aranaydo\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', 'root@localhost', '2025-09-04 11:53:32'),
(0, 34, 'UPDATE', '{\"teacher_username\": \"arnold_aranaydo\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', '{\"teacher_username\": \"SPCPC\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', 'root@localhost', '2025-09-04 11:54:53'),
(0, 40, 'UPDATE', '{\"teacher_username\": \"arnold_aranaydo\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', '{\"teacher_username\": \"SPCPC\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', 'root@localhost', '2025-09-04 11:54:53'),
(0, 34, 'UPDATE', '{\"teacher_username\": \"SPCPC\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', '{\"teacher_username\": \"arnold_aranaydo\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', 'root@localhost', '2025-09-04 11:55:20'),
(0, 40, 'UPDATE', '{\"teacher_username\": \"SPCPC\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', '{\"teacher_username\": \"arnold_aranaydo\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', 'root@localhost', '2025-09-04 11:55:20'),
(0, 44, 'INSERT', NULL, '{\"teacher_username\": \"comsi\", \"subject\": \"set 1\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"10:00:00\", \"room\": \"204\", \"school_id\": 21}', 'root@localhost', '2025-09-06 09:55:15'),
(0, 45, 'INSERT', NULL, '{\"teacher_username\": \"comsi\", \"subject\": \"set 2\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Tuesday\", \"start_time\": \"10:00:00\", \"end_time\": \"12:00:00\", \"room\": \"200\", \"school_id\": 21}', 'root@localhost', '2025-09-06 09:55:35'),
(0, 46, 'INSERT', NULL, '{\"teacher_username\": \"comsi\", \"subject\": \"set 3\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Monday\", \"start_time\": \"12:00:00\", \"end_time\": \"15:00:00\", \"room\": \"200\", \"school_id\": 21}', 'root@localhost', '2025-09-06 09:55:58'),
(0, 46, 'UPDATE', '{\"teacher_username\": \"comsi\", \"subject\": \"set 3\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Monday\", \"start_time\": \"12:00:00\", \"end_time\": \"15:00:00\", \"room\": \"200\", \"school_id\": 21}', '{\"teacher_username\": \"comsi\", \"subject\": \"set 3\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Monday\", \"start_time\": \"11:30:00\", \"end_time\": \"15:00:00\", \"room\": \"200\", \"school_id\": 21}', 'root@localhost', '2025-09-06 09:56:14'),
(0, 47, 'INSERT', NULL, '{\"teacher_username\": \"comsi\", \"subject\": \"set 4\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Wednesday\", \"start_time\": \"12:00:00\", \"end_time\": \"13:00:00\", \"room\": \"ROOM 101\", \"school_id\": 21}', 'root@localhost', '2025-09-06 09:56:43'),
(0, 48, 'INSERT', NULL, '{\"teacher_username\": \"comsi\", \"subject\": \"set 5\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Thursday\", \"start_time\": \"09:00:00\", \"end_time\": \"10:00:00\", \"room\": \"1\", \"school_id\": 21}', 'root@localhost', '2025-09-06 09:57:10'),
(0, 49, 'INSERT', NULL, '{\"teacher_username\": \"comsi\", \"subject\": \"telekenesis\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Friday\", \"start_time\": \"07:00:00\", \"end_time\": \"12:00:00\", \"room\": \"200\", \"school_id\": 21}', 'root@localhost', '2025-09-06 10:11:08'),
(0, 34, 'UPDATE', '{\"teacher_username\": \"arnold_aranaydo\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', '{\"teacher_username\": \"Arnold Aranaydo\", \"subject\": \"Networkings\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"23:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 15}', 'root@localhost', '2025-09-06 11:11:30'),
(0, 40, 'UPDATE', '{\"teacher_username\": \"arnold_aranaydo\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', '{\"teacher_username\": \"Arnold Aranaydo\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', 'root@localhost', '2025-09-06 11:11:30'),
(0, 40, 'UPDATE', '{\"teacher_username\": \"Arnold Aranaydo\", \"subject\": \"ENTRERPRENEUR ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', '{\"teacher_username\": \"Arnold Aranaydo\", \"subject\": \"ENTRERPRISE ARCHITECTURE\", \"section\": \"BSIS - 302\", \"day_of_week\": \"Thursday\", \"start_time\": \"08:30:00\", \"end_time\": \"10:00:00\", \"room\": \"Old Poso Office\", \"school_id\": 20}', 'root@localhost', '2025-09-06 11:12:56'),
(0, 44, 'UPDATE', '{\"teacher_username\": \"comsi\", \"subject\": \"set 1\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"10:00:00\", \"room\": \"204\", \"school_id\": 21}', '{\"teacher_username\": \"comsi\", \"subject\": \"blood bending\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Monday\", \"start_time\": \"09:00:00\", \"end_time\": \"10:00:00\", \"room\": \"204\", \"school_id\": 21}', 'root@localhost', '2025-09-06 11:19:50'),
(0, 50, 'INSERT', NULL, '{\"teacher_username\": \"comsi\", \"subject\": \"metal bending\", \"section\": \"TEST 1 - TEST\", \"day_of_week\": \"Tuesday\", \"start_time\": \"15:00:00\", \"end_time\": \"18:00:00\", \"room\": \"Computer Laboratory\", \"school_id\": 21}', 'root@localhost', '2025-09-06 11:20:48');

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
(7, 'joerenzescallente027@gmail.com', '2025-2026', '2nd Semester', '2025-07-27 15:21:59', '2025-07-27 15:21:59', 1, 1),
(8, 'comsi@gmail.com', '2025-2026', '1st Semester', '2025-09-06 18:28:37', '2025-09-06 18:31:15', 1, 1),
(9, 'davidbayot8@gmail.com', '2025-2026', '1st Semester', '2025-09-06 18:33:14', '2025-09-06 18:33:19', 1, 1);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_time_settings`
--
ALTER TABLE `class_time_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_qr_tokens`
--
ALTER TABLE `student_qr_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  MODIFY `tbl_attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `tbl_courses`
--
ALTER TABLE `tbl_courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `tbl_face_verification_logs`
--
ALTER TABLE `tbl_face_verification_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT for table `tbl_instructors`
--
ALTER TABLE `tbl_instructors`
  MODIFY `instructor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `tbl_instructor_subjects`
--
ALTER TABLE `tbl_instructor_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tbl_sections`
--
ALTER TABLE `tbl_sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

--
-- AUTO_INCREMENT for table `tbl_student`
--
ALTER TABLE `tbl_student`
  MODIFY `tbl_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `tbl_subjects`
--
ALTER TABLE `tbl_subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `teacher_schedules`
--
ALTER TABLE `teacher_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
