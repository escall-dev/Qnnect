-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2025 at 06:49 PM
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
(1, 19, 'settings_change', 'Updated academic settings: School Year: 2024-2025, Semester: 2nd Semester', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-09 22:19:38', '{\"school_year\":\"2024-2025\",\"semester\":\"2nd Semester\"}'),
(2, 19, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-09 23:12:48', '{\"school_motto\":{\"old\":\"omsimize\",\"new\":\"\"}}'),
(3, 19, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-09 23:12:51', NULL),
(4, 19, 'settings_change', 'Updated school information', 'school_info', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-09 23:15:41', '{\"school_address\":{\"old\":\"\",\"new\":\"Brgy. Santo Ni\\u00f1o\"},\"school_contact\":{\"old\":\"\",\"new\":\"09100668203\"}}'),
(5, 19, 'data_export', 'Exported attendance records in excel format', 'tbl_attendance', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-10 00:41:00', '{\"format\":\"excel\",\"start_date\":\"2025-04-03\",\"end_date\":\"2025-04-10\",\"course_section\":\"\",\"day\":\"\",\"record_count\":24}'),
(6, 19, 'data_export', 'Exported attendance records in excel format', 'tbl_attendance', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-10 00:46:29', '{\"format\":\"excel\",\"start_date\":\"2025-03-02\",\"end_date\":\"2025-04-10\",\"course_section\":\"\",\"day\":\"\",\"record_count\":36}');

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
(1, 'San Pedro City Polytechnic College', 'Brgy. Santo Niño', '09100668203', '', '', 'admin/image/school-logo.png', '', '', '', '2025-04-09 21:20:46', '2025-04-09 23:15:41');

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
(3, 2, '2025-03-13 11:23:38', 'Late', '2025-03-13 11:23:48', NULL, NULL),
(4, 2, '2025-03-13 13:12:08', 'Late', '2025-03-13 13:22:22', NULL, NULL),
(6, 4, '2025-03-13 14:02:36', 'Late', '2025-03-13 14:03:55', NULL, NULL),
(7, 5, '2025-03-13 14:03:46', 'Late', '2025-03-13 14:04:00', NULL, NULL),
(9, 4, '2025-03-14 10:54:21', 'Late', '2025-03-14 11:01:52', NULL, NULL),
(10, 2, '2025-03-14 10:54:28', 'Late', '2025-03-14 11:01:43', NULL, NULL),
(17, 5, '2025-03-16 12:34:23', 'Late', '2025-03-16 12:35:04', NULL, NULL),
(19, 4, '2025-03-16 12:34:38', 'Late', '2025-03-16 12:36:10', NULL, NULL),
(20, 4, '2025-03-16 12:36:27', 'Late', '2025-03-16 12:36:35', NULL, NULL),
(21, 2, '2025-03-16 12:36:40', 'Late', '2025-03-16 14:28:58', NULL, NULL),
(22, 8, '2025-03-18 00:44:33', 'Late', '2025-03-18 00:44:50', NULL, NULL),
(24, 7, '2025-04-02 09:49:56', 'Late', '2025-04-02 12:09:22', NULL, NULL),
(26, 9, '2025-04-03 03:30:36', 'Late', '2025-04-03 03:30:52', NULL, NULL),
(33, 11, '2025-04-05 01:28:36', 'Late', '2025-04-05 01:35:47', NULL, NULL),
(34, 10, '2025-04-05 01:35:56', 'Late', '2025-04-05 01:40:28', NULL, NULL),
(35, 2, '2025-04-05 01:43:13', 'Late', NULL, NULL, NULL),
(36, 4, '2025-04-05 05:12:47', 'On Time', NULL, NULL, NULL),
(37, 7, '2025-04-05 05:13:16', 'On Time', NULL, NULL, NULL),
(38, 6, '2025-04-05 05:18:58', 'Late', NULL, NULL, NULL),
(39, 13, '2025-04-05 07:12:49', 'On Time', NULL, NULL, NULL),
(40, 13, '2025-04-05 09:41:49', 'Late', NULL, 1, 2),
(41, 11, '2025-04-05 09:42:31', 'Late', NULL, 1, 2),
(42, 10, '2025-04-05 09:42:48', 'On Time', NULL, 1, 2),
(43, 10, '2025-04-05 09:44:29', 'On Time', NULL, 1, 1),
(44, 10, '2025-04-05 09:55:57', 'Late', NULL, 4, 6),
(45, 7, '2025-04-05 10:01:50', 'Late', NULL, 4, 6),
(46, 6, '2025-04-05 10:03:08', 'Late', NULL, 4, 6),
(47, 2, '2025-04-05 10:03:50', 'Late', NULL, 4, 6),
(48, 7, '2025-04-05 10:08:04', 'On Time', NULL, 3, 4),
(49, 7, '2025-04-05 10:09:29', 'On Time', NULL, 4, 7),
(50, 6, '2025-04-05 10:09:54', 'Late', NULL, 4, 7),
(51, 13, '2025-04-09 11:09:47', 'Late', NULL, NULL, NULL),
(52, 13, '2025-04-09 12:40:35', 'Late', NULL, NULL, NULL),
(53, 13, '2025-04-09 12:41:17', 'On Time', NULL, 1, 1),
(54, 11, '2025-04-09 12:41:23', 'On Time', NULL, 1, 1),
(55, 10, '2025-04-09 12:41:29', 'On Time', NULL, 1, 1);

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

--
-- Dumping data for table `tbl_face_recognition_logs`
--

INSERT INTO `tbl_face_recognition_logs` (`log_id`, `student_id`, `recognition_status`, `timestamp`, `ip_address`) VALUES
(1, 2, 'failed', '2025-03-13 19:53:07', '::1'),
(2, 2, 'failed', '2025-03-13 19:53:10', '::1'),
(3, 2, 'failed', '2025-03-13 19:53:11', '::1'),
(4, 2, 'failed', '2025-03-13 19:53:12', '::1'),
(5, 2, 'failed', '2025-03-13 19:53:13', '::1'),
(6, 2, 'failed', '2025-03-13 19:53:43', '::1'),
(7, 2, 'failed', '2025-03-13 19:53:45', '::1'),
(8, 2, 'failed', '2025-03-13 19:53:46', '::1'),
(9, 2, 'failed', '2025-03-13 19:53:47', '::1'),
(10, 2, 'failed', '2025-03-13 19:53:47', '::1');

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
(1, NULL, 'Alexander Joerenz Escallente', 'Success', '2025-03-13 19:22:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(2, NULL, 'barney', 'Success', '2025-03-13 20:32:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(3, NULL, 'escall', 'Success', '2025-03-13 21:43:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(4, NULL, 'zeno', 'Success', '2025-03-14 19:04:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(5, NULL, 'CRYPTO', 'Success', '2025-03-14 19:04:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(6, NULL, 'joshua bayot', 'Success', '2025-03-18 08:43:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(7, NULL, 'melissa', 'Success', '2025-04-03 11:28:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(8, NULL, 'capstone 1', 'Success', '2025-04-04 10:01:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(9, NULL, 'atom nucleus', 'Success', '2025-04-04 21:18:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(10, NULL, 'melissa lucenecio', 'Success', '2025-04-05 10:40:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(11, NULL, 'CAP1', 'Success', '2025-04-05 15:12:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(12, NULL, 'alucard', 'Success', '2025-04-05 16:36:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration'),
(13, NULL, 'chou', 'Success', '2025-04-05 16:37:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration');

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
(1, 'Arnold Aranaydo', 'Capstone 1', '2025-04-05 07:21:22'),
(3, 'Wency Trapago', '', '2025-04-05 07:55:44'),
(4, 'Mr. Valdez', '', '2025-04-05 08:14:20');

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
(1, 1, 1, '2025-04-05 07:30:20'),
(2, 1, 2, '2025-04-05 07:30:49'),
(5, 3, 4, '2025-04-05 07:55:44'),
(7, 4, 6, '2025-04-05 08:14:20'),
(8, 4, 7, '2025-04-05 08:14:20');

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
(2, 'Alexander Joerenz Escallente', 'BSIT-302', 'F9ZqkMhxdC', NULL, 'face_1741864975_67d2c00fde99f.jpg'),
(4, 'barney', 'BSHRM-202', 'BGDaJwGfqF', NULL, 'face_1741869166_67d2d06e39ee5.jpg'),
(5, 'escall', 'BSBA-301', 'AXNMyFOIO1', NULL, 'face_1741873390_67d2e0eed6208.jpg'),
(6, 'zeno', 'BSTM-101', 't3mXGjOiWL', NULL, 'face_1741950258_67d40d3220a8d.jpg'),
(7, 'CRYPTO', 'BSTM-102', 'ZptdwLoYc8', NULL, 'face_1741950299_67d40d5b2c277.jpg'),
(8, 'joshua bayot', 'BSIT-302', 't59iFIrHee', NULL, 'face_1742258642_67d8c1d2ae831.jpg'),
(9, 'melissa', 'BSIT-302', 'JifZShPEZF', NULL, 'face_1743650938_67ee007a02a50.jpg'),
(10, 'capstone 1', 'BSIT-301', 'hcJIaoCQjb', NULL, 'face_1743732089_67ef3d79a723e.jpg'),
(11, 'atom nucleus', 'BSIT-301', 'tU7xeQLoqy', NULL, 'face_1743772719_67efdc2fe402b.jpg'),
(12, 'melissa lucenecio', 'BSIT-302', 'fWyLB4j8yM', NULL, 'face_1743820862_67f0983e14c71.jpg'),
(13, 'CAP1', 'BSIT-302', 'ySscHVEbVs', NULL, 'face_1743837145_67f0d7d966a46.jpg'),
(14, 'alucard', 'BSIT-302', 'XWJCtfrFi4', NULL, 'face_1743842202_67f0eb9a4841c.jpg'),
(15, 'chou', 'BSIS-301', 'PPtdm28Iwi', NULL, 'face_1743842237_67f0ebbdee328.jpg');

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
(1, 'System Admin & Maintenance', '2025-04-05 07:30:20'),
(2, 'Capstone 1', '2025-04-05 07:30:49'),
(3, 'System Information Assurance 1', '2025-04-05 07:47:54'),
(4, 'Information Assurance Security 1', '2025-04-05 07:49:01'),
(5, 'Casptone 2', '2025-04-05 07:56:01'),
(6, 'Social Professional Issues', '2025-04-05 08:14:20'),
(7, 'Application Development', '2025-04-05 08:14:20');

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
(1, 'escall.byte@gmail.com', '2024-2025', '2nd Semester', '2025-04-05 14:54:58', '2025-04-09 21:20:55');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `tbl_attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `tbl_face_recognition_logs`
--
ALTER TABLE `tbl_face_recognition_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_face_verification_logs`
--
ALTER TABLE `tbl_face_verification_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tbl_instructors`
--
ALTER TABLE `tbl_instructors`
  MODIFY `instructor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_instructor_subjects`
--
ALTER TABLE `tbl_instructor_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_student`
--
ALTER TABLE `tbl_student`
  MODIFY `tbl_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_subjects`
--
ALTER TABLE `tbl_subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

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
