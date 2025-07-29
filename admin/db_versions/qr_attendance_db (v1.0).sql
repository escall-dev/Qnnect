-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2025 at 09:51 AM
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
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action_type`, `action_description`, `affected_table`, `affected_id`, `ip_address`, `user_agent`, `created_at`, `additional_data`) VALUES
(1, 19, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 1st Semester', 'user_settings', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) qr-electron/1.0.0 Chrome/122.0.6261.156 Electron/29.4.6 Safari/537.36', '2025-06-21 13:41:00', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"1st Semester\"}}'),
(2, 21, '', 'Deleted attendance record #1', 'tbl_attendance', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 17:06:15', '{\"tbl_attendance_id\":1,\"tbl_student_id\":1,\"time_in\":\"2025-06-21 15:27:15\",\"status\":\"On Time\",\"time_out\":null,\"instructor_id\":1,\"subject_id\":1}'),
(3, 21, 'data_export', 'Exported attendance records in pdf format', 'tbl_attendance', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-25 19:41:09', '{\"format\":\"pdf\",\"start_date\":\"2025-06-18\",\"end_date\":\"2025-06-25\",\"course_section\":\"\",\"day\":\"\",\"record_count\":2}'),
(4, 21, 'data_export', 'Exported attendance records in pdf format', 'tbl_attendance', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-25 19:42:20', '{\"format\":\"pdf\",\"start_date\":\"2025-06-18\",\"end_date\":\"2025-06-25\",\"course_section\":\"\",\"day\":\"\",\"record_count\":2}'),
(5, 21, 'data_export', 'Exported attendance records in csv format', 'tbl_attendance', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-25 19:43:04', '{\"format\":\"csv\",\"start_date\":\"2025-06-18\",\"end_date\":\"2025-06-25\",\"course_section\":\"\",\"day\":\"\",\"record_count\":2}'),
(6, 24, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:36:06', '{\"school_name\":{\"old\":null,\"new\":\"Comsite\"},\"school_address\":{\"old\":null,\"new\":\"san pedro\"},\"school_contact\":{\"old\":null,\"new\":\"12345678901\"},\"school_email\":{\"old\":null,\"new\":\"school@email.com\"},\"school_website\":{\"old\":null,\"new\":\"http:\\/\\/www.school.com\"},\"school_motto\":{\"old\":null,\"new\":\"motto\"},\"school_vision\":{\"old\":null,\"new\":\"vision\"},\"school_mission\":{\"old\":null,\"new\":\"mission\"},\"school_logo_path\":{\"old\":null,\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(7, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:21', '{\"school_year\":{\"old\":\"\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"\",\"new\":\"2nd Semester\"}}'),
(8, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:36', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"1st Semester\"}}'),
(9, NULL, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:39', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"1st Semester\",\"new\":\"1st Semester\"}}'),
(10, NULL, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:40', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"1st Semester\",\"new\":\"1st Semester\"}}'),
(11, NULL, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:41', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"1st Semester\",\"new\":\"1st Semester\"}}'),
(12, NULL, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:41', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"1st Semester\",\"new\":\"1st Semester\"}}'),
(13, NULL, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 03:37:43', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"1st Semester\",\"new\":\"2nd Semester\"}}'),
(14, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 12:47:14', '{\"school_year\":{\"old\":\"\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"\",\"new\":\"2nd Semester\"}}'),
(15, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 12:47:24', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}'),
(16, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 12:47:35', '{\"school_name\":{\"old\":null,\"new\":\"San Pedro City Polytechnic College\"}}'),
(17, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:01:15', '{\"school_name\":{\"old\":\"School Name\",\"new\":\"San Pedro City Polytechnic College\"},\"school_address\":{\"old\":\"School Address\",\"new\":\"\"},\"school_contact\":{\"old\":\"Contact Number\",\"new\":\"\"},\"school_email\":{\"old\":\"school@email.com\",\"new\":\"\"},\"school_website\":{\"old\":\"www.schoolwebsite.com\",\"new\":\"\"},\"school_motto\":{\"old\":\"School Motto\",\"new\":\"\"},\"school_vision\":{\"old\":\"School Vision\",\"new\":\"\"},\"school_mission\":{\"old\":\"School Mission\",\"new\":\"\"}}'),
(18, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:01:21', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}'),
(19, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:18:58', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}'),
(20, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:19:56', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/SPCPC-logo-trans.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(21, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:20:20', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(22, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:31:47', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}'),
(23, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', '2025-07-17 13:32:01', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}'),
(24, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:36:30', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}'),
(25, NULL, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:36:35', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}'),
(26, 23, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:01:24', '{\"school_year\":{\"old\":\"\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"\",\"new\":\"2nd Semester\"}}'),
(27, 23, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 2nd Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:01:30', '{\"school_year\":{\"old\":\"2025-2026\",\"new\":\"2024-2025\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"2nd Semester\"}}'),
(28, 23, 'settings_change', 'Updated academic settings: School Year: 2025-2026, Semester: 1st Semester', 'user_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:01:40', '{\"school_year\":{\"old\":\"2024-2025\",\"new\":\"2025-2026\"},\"semester\":{\"old\":\"2nd Semester\",\"new\":\"1st Semester\"}}'),
(29, 24, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:15:01', '{\"school_name\":{\"old\":\"San Pedro City Polytechnic College\",\"new\":\"Computer Site Inc.\"},\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(30, 24, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:15:09', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(31, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:15:36', '{\"school_name\":{\"old\":\"Computer Site Inc.\",\"new\":\"San Pedro City Polytechnic College\"}}'),
(32, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:37:33', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(33, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:37:48', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(34, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:37:58', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(35, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:41:17', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(36, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:48:47', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(37, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:51:20', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(38, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:51:42', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(39, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:51:47', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}'),
(40, 23, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 14:52:03', '{\"school_logo_path\":{\"old\":\"admin\\/image\\/school-logo.png\",\"new\":\"admin\\/image\\/school-logo.png\"}}');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_grades`
--

CREATE TABLE `attendance_grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `attendance_rate` decimal(5,2) NOT NULL,
  `attendance_grade` decimal(3,2) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `scan_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `expected_meetings` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_schedule`
--

CREATE TABLE `master_schedule` (
  `id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `instructor` varchar(100) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` varchar(10) NOT NULL,
  `end_time` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `master_schedule`
--

INSERT INTO `master_schedule` (`id`, `section`, `subject`, `instructor`, `day_of_week`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(141, 'McGregor', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Monday', '12:20 PM', '1:15 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(142, 'McGregor', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Tuesday', '12:20 PM', '1:15 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(143, 'McGregor', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Wednesday', '12:20 PM', '1:15 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(144, 'McGregor', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Thursday', '12:20 PM', '1:15 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(145, 'McGregor', 'Organization and Management', 'Dr. Arnold Aranaydo', 'Friday', '12:20 PM', '1:15 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(146, 'Deming', 'Organization and Management', 'Dr. Arnold Aranaydo', 'Monday', '1:15 PM', '2:10 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(147, 'Deming', 'Organization and Management', 'Dr. Arnold Aranaydo', 'Tuesday', '1:15 PM', '2:10 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(148, 'McGregor', 'Organization and Management', 'Dr. Arnold Aranaydo', 'Monday', '2:10 PM', '3:05 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(149, 'McGregor', 'Organization and Management', 'Dr. Arnold Aranaydo', 'Thursday', '2:10 PM', '3:05 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(150, 'Mausner', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Monday', '3:25 PM', '4:20 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(151, 'McGregor', 'Organization and Management', 'Dr. Arnold Aranaydo', 'Tuesday', '3:25 PM', '4:20 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(152, 'Mausner', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Wednesday', '3:25 PM', '4:20 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(153, 'Mausner', 'Organization and Management', 'Dr. Arnold Aranaydo', 'Thursday', '3:25 PM', '4:20 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(154, 'Mausner', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Friday', '3:25 PM', '4:20 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(155, 'Carnegie', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Wednesday', '4:20 PM', '5:15 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(156, 'Carnegie', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Thursday', '4:20 PM', '5:15 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(157, 'Deming', 'Organization and Management', 'Dr. Arnold Aranaydo', 'Friday', '4:20 PM', '5:15 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(158, 'Carnegie', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Monday', '5:15 PM', '6:10 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(159, 'Carnegie', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Tuesday', '5:15 PM', '6:10 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(160, 'Deming', 'Organization and Management', 'Dr. Arnold Aranaydo', 'Thursday', '5:15 PM', '6:10 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(161, 'Deming', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Friday', '5:15 PM', '6:10 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(162, 'Deming', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Monday', '6:10 PM', '7:05 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(163, 'Deming', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Tuesday', '6:10 PM', '7:05 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49'),
(164, 'Deming', 'Business Mathematics', 'Dr. Arnold Aranaydo', 'Wednesday', '6:10 PM', '7:05 PM', '2025-06-21 16:41:49', '2025-06-21 16:41:49');

-- --------------------------------------------------------

--
-- Table structure for table `offline_data`
--

CREATE TABLE `offline_data` (
  `id` int(11) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `action_type` enum('insert','update','delete') NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `status` enum('pending','synced','failed') DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `synced_at` datetime DEFAULT NULL,
  `sync_attempts` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_info`
--

INSERT INTO `school_info` (`id`, `school_name`, `school_address`, `school_contact`, `school_email`, `school_website`, `school_logo_path`, `school_motto`, `school_vision`, `school_mission`, `created_at`, `updated_at`) VALUES
(1, 'San Pedro City Polytechnic College', '', '', '', '', 'admin/image/school-logo.png', '', '', '', '2025-07-17 13:01:15', '2025-07-17 14:52:03');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_attendance`
--

CREATE TABLE `tbl_attendance` (
  `tbl_attendance_id` int(11) NOT NULL,
  `tbl_student_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  `time_out` timestamp NULL DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_attendance`
--

INSERT INTO `tbl_attendance` (`tbl_attendance_id`, `tbl_student_id`, `time_in`, `status`, `time_out`, `instructor_id`, `subject_id`) VALUES
(1, 1, '2025-06-23 11:47:02', 'On Time', NULL, 1, 1),
(2, 1, '2025-06-23 11:49:26', 'Late', NULL, 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_face_recognition_logs`
--

CREATE TABLE `tbl_face_recognition_logs` (
  `log_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `recognition_status` enum('success','failed') NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
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
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_face_verification_logs`
--

INSERT INTO `tbl_face_verification_logs` (`log_id`, `student_id`, `student_name`, `status`, `verification_time`, `ip_address`, `user_agent`, `notes`) VALUES
(1, NULL, 'Alexander Joerenz Escallente', 'Success', '2025-06-23 19:46:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'Face captured during registration');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_instructors`
--

CREATE TABLE `tbl_instructors` (
  `instructor_id` int(11) NOT NULL,
  `instructor_name` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_instructors`
--

INSERT INTO `tbl_instructors` (`instructor_id`, `instructor_name`, `subject`, `created_at`) VALUES
(1, 'Dr. Arnold Aranaydo', '', '2025-06-21 07:25:54');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_instructor_subjects`
--

CREATE TABLE `tbl_instructor_subjects` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_instructor_subjects`
--

INSERT INTO `tbl_instructor_subjects` (`id`, `instructor_id`, `subject_id`, `created_at`) VALUES
(9, 1, 1, '2025-06-21 07:25:54'),
(11, 1, 3, '2025-06-21 09:48:17');

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
  `face_image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_student`
--

INSERT INTO `tbl_student` (`tbl_student_id`, `student_name`, `course_section`, `generated_code`, `face_image`, `face_image_path`) VALUES
(1, 'Alexander Joerenz Escallente', '11 - Mc Gregor', 'aMWhzC2hXG', NULL, 'face_1750679172_68593e847dbc2.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_subjects`
--

CREATE TABLE `tbl_subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_subjects`
--

INSERT INTO `tbl_subjects` (`subject_id`, `subject_name`, `created_at`) VALUES
(1, 'Business Math', '2025-06-21 07:25:54'),
(3, 'Organizational Management', '2025-06-21 09:48:17');

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
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `email`, `school_year`, `semester`, `created_at`, `updated_at`) VALUES
(1, 'escall.byte@gmail.com', '2024-2025', '2nd Semester', '2025-04-05 14:54:58', '2025-04-09 21:20:55'),
(2, '', '2025-2026', '2nd Semester', '2025-07-17 13:01:21', '2025-07-17 13:36:35'),
(3, 'test@example.com', '2024-2025', '2nd Semester', '2025-07-17 13:28:45', '2025-07-17 13:28:59'),
(6, 'joerenz.dev@gmail.com', '2025-2026', '1st Semester', '2025-07-17 14:01:24', '2025-07-17 14:01:40');

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
-- Indexes for table `attendance_grades`
--
ALTER TABLE `attendance_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_course_term_section` (`student_id`,`course_id`,`term`,`section`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_schedule`
--
ALTER TABLE `master_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`section`,`subject`,`day_of_week`,`start_time`);

--
-- Indexes for table `offline_data`
--
ALTER TABLE `offline_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `school_info`
--
ALTER TABLE `school_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  ADD PRIMARY KEY (`tbl_attendance_id`),
  ADD KEY `tbl_student_id` (`tbl_student_id`),
  ADD KEY `fk_instructor_id` (`instructor_id`),
  ADD KEY `fk_subject_id` (`subject_id`);

--
-- Indexes for table `tbl_face_recognition_logs`
--
ALTER TABLE `tbl_face_recognition_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `tbl_face_verification_logs`
--
ALTER TABLE `tbl_face_verification_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `tbl_instructors`
--
ALTER TABLE `tbl_instructors`
  ADD PRIMARY KEY (`instructor_id`);

--
-- Indexes for table `tbl_instructor_subjects`
--
ALTER TABLE `tbl_instructor_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_instructor_subject` (`instructor_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

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
  ADD UNIQUE KEY `subject_name` (`subject_name`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `attendance_grades`
--
ALTER TABLE `attendance_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_schedule`
--
ALTER TABLE `master_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `offline_data`
--
ALTER TABLE `offline_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_info`
--
ALTER TABLE `school_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  MODIFY `tbl_attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_face_recognition_logs`
--
ALTER TABLE `tbl_face_recognition_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_face_verification_logs`
--
ALTER TABLE `tbl_face_verification_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_instructors`
--
ALTER TABLE `tbl_instructors`
  MODIFY `instructor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_instructor_subjects`
--
ALTER TABLE `tbl_instructor_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_student`
--
ALTER TABLE `tbl_student`
  MODIFY `tbl_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_subjects`
--
ALTER TABLE `tbl_subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_grades`
--
ALTER TABLE `attendance_grades`
  ADD CONSTRAINT `fk_grade_student` FOREIGN KEY (`student_id`) REFERENCES `tbl_student` (`tbl_student_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_log_session` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_student` FOREIGN KEY (`student_id`) REFERENCES `tbl_student` (`tbl_student_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD CONSTRAINT `fk_session_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `tbl_instructors` (`instructor_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  ADD CONSTRAINT `fk_instructor_id` FOREIGN KEY (`instructor_id`) REFERENCES `tbl_instructors` (`instructor_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_subject_id` FOREIGN KEY (`subject_id`) REFERENCES `tbl_subjects` (`subject_id`) ON DELETE SET NULL;

--
-- Constraints for table `tbl_face_recognition_logs`
--
ALTER TABLE `tbl_face_recognition_logs`
  ADD CONSTRAINT `tbl_face_recognition_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `tbl_student` (`tbl_student_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_face_verification_logs`
--
ALTER TABLE `tbl_face_verification_logs`
  ADD CONSTRAINT `fk_verification_student` FOREIGN KEY (`student_id`) REFERENCES `tbl_student` (`tbl_student_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tbl_instructor_subjects`
--
ALTER TABLE `tbl_instructor_subjects`
  ADD CONSTRAINT `tbl_instructor_subjects_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `tbl_instructors` (`instructor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_instructor_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `tbl_subjects` (`subject_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
