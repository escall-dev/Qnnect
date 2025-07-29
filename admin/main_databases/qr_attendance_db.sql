-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 17, 2025 at 03:06 PM
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
-- Table structure for table `tbl_attendance`
--

CREATE TABLE `tbl_attendance` (
  `tbl_attendance_id` int(11) NOT NULL,
  `tbl_student_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_out` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_attendance`
--

INSERT INTO `tbl_attendance` (`tbl_attendance_id`, `tbl_student_id`, `time_in`, `time_out`) VALUES
(3, 2, '2025-03-13 11:23:38', '2025-03-13 11:23:48'),
(4, 2, '2025-03-13 13:12:08', '2025-03-13 13:22:22'),
(5, 1, '2025-03-13 14:02:31', '2025-03-13 14:03:52'),
(6, 4, '2025-03-13 14:02:36', '2025-03-13 14:03:55'),
(7, 5, '2025-03-13 14:03:46', '2025-03-13 14:04:00'),
(8, 1, '2025-03-14 10:54:09', '2025-03-14 11:01:56'),
(9, 4, '2025-03-14 10:54:21', '2025-03-14 11:01:52'),
(10, 2, '2025-03-14 10:54:28', '2025-03-14 11:01:43'),
(11, 5, '2025-03-14 12:09:55', NULL),
(12, 7, '2025-03-14 12:11:25', NULL),
(13, 6, '2025-03-14 12:11:32', NULL),
(15, 4, '2025-03-14 12:13:40', NULL),
(16, 6, '2025-03-16 12:34:04', '2025-03-16 12:34:52'),
(17, 5, '2025-03-16 12:34:23', '2025-03-16 12:35:04'),
(18, 1, '2025-03-16 12:34:29', '2025-03-16 12:35:19'),
(19, 4, '2025-03-16 12:34:38', '2025-03-16 12:36:10'),
(20, 4, '2025-03-16 12:36:27', '2025-03-16 12:36:35'),
(21, 2, '2025-03-16 12:36:40', '2025-03-16 14:28:58');

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
(5, NULL, 'CRYPTO', 'Success', '2025-03-14 19:04:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', 'Face captured during registration');

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
(1, 'Aranaydo', 'BSIT-301', 'KIYkAk6ZRV', NULL, NULL),
(2, 'Alexander Joerenz Escallente', 'BSIT-302', 'F9ZqkMhxdC', NULL, 'face_1741864975_67d2c00fde99f.jpg'),
(4, 'barney', 'BSHRM-202', 'BGDaJwGfqF', NULL, 'face_1741869166_67d2d06e39ee5.jpg'),
(5, 'escall', 'BSBA-301', 'AXNMyFOIO1', NULL, 'face_1741873390_67d2e0eed6208.jpg'),
(6, 'zeno', 'BSTM-101', 't3mXGjOiWL', NULL, 'face_1741950258_67d40d3220a8d.jpg'),
(7, 'CRYPTO', 'BSTM-102', 'ZptdwLoYc8', NULL, 'face_1741950299_67d40d5b2c277.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--



--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  ADD PRIMARY KEY (`tbl_attendance_id`),
  ADD KEY `tbl_student_id` (`tbl_student_id`);

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
-- Indexes for table `tbl_student`
--
ALTER TABLE `tbl_student`
  ADD PRIMARY KEY (`tbl_student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_attendance`
--
ALTER TABLE `tbl_attendance`
  MODIFY `tbl_attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `tbl_face_recognition_logs`
--
ALTER TABLE `tbl_face_recognition_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_face_verification_logs`
--
ALTER TABLE `tbl_face_verification_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_student`
--
ALTER TABLE `tbl_student`
  MODIFY `tbl_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
