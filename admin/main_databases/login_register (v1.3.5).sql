-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 30, 2025 at 03:03 PM
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
-- Database: `login_register`
--

-- --------------------------------------------------------

--
-- Table structure for table `recent_logins`
--

CREATE TABLE `recent_logins` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `school_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recent_logins`
--

INSERT INTO `recent_logins` (`id`, `username`, `profile_image`, `last_login`, `created_at`, `school_id`) VALUES
(1, 'escall', 'uploads/profile_images/profile_1752690336.jpg', '2025-08-22 16:29:50', '2025-07-13 16:43:05', NULL),
(13, 'ara', 'uploads/profile_images/profile_1752744870_40011e02deb1edad.jpg', '2025-08-22 17:17:41', '2025-07-13 16:53:40', NULL),
(114, 'alex', 'uploads/profile_images/profile_1755870815.png', '2025-08-22 14:05:02', '2025-07-16 19:34:13', 2),
(771, 'JOERENZ', 'uploads/profile_images/profile_1755870815.png', '2025-08-22 14:01:52', '2025-08-22 14:01:52', 2),
(773, 'COMSITE', 'uploads/profile_images/profile_1756558903.png', '2025-08-30 13:02:25', '2025-08-22 14:03:31', 2),
(788, 'ADMIN - Bagwis', 'uploads/profile_images/profile_1755871431.png', '2025-08-24 08:48:56', '2025-08-22 15:52:24', NULL),
(805, 'SPCPC', 'uploads/profile_images/profile_1756500463.png', '2025-08-30 12:23:02', '2025-08-23 11:31:43', 15);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `capacity` int(11) DEFAULT 30,
  `room_type` enum('classroom','laboratory','auditorium','library','other') DEFAULT 'classroom',
  `status` enum('available','maintenance','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `school_id`, `room_name`, `capacity`, `room_type`, `status`, `created_at`) VALUES
(6, 2, 'Training Room A', 20, 'classroom', 'available', '2025-07-16 16:51:47'),
(7, 2, 'Training Room B', 20, 'classroom', 'available', '2025-07-16 16:51:47'),
(8, 2, 'Tech Lab', 15, 'laboratory', 'available', '2025-07-16 16:51:47'),
(9, 2, 'Conference Hall', 50, 'auditorium', 'available', '2025-07-16 16:51:47');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `theme_color` varchar(7) DEFAULT '#098744',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `code`, `theme_color`, `created_at`, `updated_at`, `status`) VALUES
(2, 'Computer Site Inc.', 'CSI', '#4CAF00', '2025-07-16 16:51:47', '2025-08-13 07:37:31', 'active'),
(15, 'San Pedro City Polytechnic College', 'SPCPC', '#098744', '2025-08-23 11:29:23', '2025-08-29 20:22:54', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `school_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 16:52:42'),
(2, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 16:54:00'),
(3, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 16:56:32'),
(4, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 17:06:29'),
(5, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 18:01:46'),
(6, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 18:20:39'),
(7, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 18:23:05'),
(8, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 18:25:52'),
(9, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:20:03'),
(10, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:20:40'),
(11, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:22:17'),
(12, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:34:13'),
(13, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:38:11'),
(14, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:39:17'),
(15, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 04:25:02'),
(16, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 06:14:23'),
(17, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 06:14:38'),
(18, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 06:15:26'),
(19, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:05:41'),
(20, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:06:09'),
(21, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:08:13'),
(22, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:15:09'),
(23, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:15:27'),
(24, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:15:46'),
(25, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:23:28'),
(26, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:35:21'),
(27, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 10:11:55'),
(28, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 10:13:02'),
(29, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 10:13:55'),
(30, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 10:37:48'),
(31, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 10:38:34'),
(32, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 11:31:37'),
(33, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 12:27:22'),
(34, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:34:41'),
(35, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-18 04:52:46'),
(36, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-18 04:54:17'),
(37, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-20 10:25:17'),
(38, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-20 10:36:00'),
(39, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-20 11:34:56'),
(40, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-20 13:51:32'),
(41, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 02:06:39'),
(42, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 02:06:56'),
(43, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 02:08:06'),
(44, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 08:13:06'),
(45, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 08:28:50'),
(46, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:29:00'),
(47, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:30:36'),
(48, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:32:54'),
(49, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:33:03'),
(50, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:35:04'),
(51, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:41:44'),
(52, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:42:25'),
(53, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:48:38'),
(54, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:51:57'),
(55, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 10:03:33'),
(56, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 11:18:05'),
(57, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 11:21:47'),
(58, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:29:07'),
(59, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:35:13'),
(60, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:37:18'),
(61, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:40:58'),
(62, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 14:37:29'),
(63, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 16:47:40'),
(64, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 18:03:26'),
(65, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 18:17:26'),
(66, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 18:17:40'),
(67, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:02:55'),
(68, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:20:26'),
(69, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:21:32'),
(70, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:22:09'),
(71, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:28:58'),
(72, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:43:19'),
(73, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:47:17'),
(74, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 08:30:39'),
(75, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:28:33'),
(76, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:28:47'),
(77, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:30:28'),
(78, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:31:37'),
(79, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:31:55'),
(80, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 10:11:18'),
(81, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 14:02:22'),
(82, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 14:02:35'),
(83, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 14:06:59'),
(84, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 14:24:12'),
(85, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 12:29:19'),
(86, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 12:55:02'),
(87, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:07:26'),
(88, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:15:04'),
(89, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:15:25'),
(90, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:15:51'),
(91, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:18:17'),
(92, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:18:30'),
(93, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:22:45'),
(94, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 14:08:11'),
(95, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 14:08:24'),
(96, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 14:08:57'),
(97, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:01:33'),
(98, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:13:34'),
(99, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:17:55'),
(100, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:18:20'),
(101, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:20:29'),
(102, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:23:10'),
(103, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:25:35'),
(104, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:27:35'),
(105, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:36:40'),
(106, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:47:52'),
(107, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:48:14'),
(108, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:49:00'),
(109, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:49:29'),
(110, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:57:36'),
(111, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:06:37'),
(112, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:07:51'),
(113, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:15:45'),
(114, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:17:38'),
(115, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:18:18'),
(116, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:20:02'),
(117, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:20:13'),
(118, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:21:59'),
(119, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:32:22'),
(120, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:33:06'),
(121, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:52:52'),
(122, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:53:28'),
(123, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:04:52'),
(124, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:08:13'),
(125, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:21:27'),
(126, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:23:58'),
(127, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:38:28'),
(128, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:38:46'),
(129, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:39:09'),
(130, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:42:39'),
(131, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:48:05'),
(132, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:48:41'),
(133, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:48:54'),
(134, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:49:18'),
(135, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:51:18'),
(136, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:53:24'),
(137, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:54:39'),
(138, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:54:59'),
(139, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 18:02:03'),
(140, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 18:02:19'),
(141, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 18:03:08'),
(142, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 18:10:37'),
(143, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 18:14:12'),
(144, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:02:50'),
(145, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:07:02'),
(146, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:10:24'),
(147, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:31:28'),
(148, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:31:41'),
(149, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:35:51'),
(150, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:41:24'),
(151, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:43:04'),
(152, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 13:14:51'),
(153, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 13:50:48'),
(154, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 15:31:39'),
(155, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 15:51:21'),
(156, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 16:22:55'),
(157, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 16:23:29'),
(158, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 02:25:00'),
(159, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 02:32:56'),
(160, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 02:34:12'),
(161, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 03:57:21'),
(162, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 04:39:19'),
(163, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 05:41:51'),
(164, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 05:45:22'),
(165, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 06:25:23'),
(166, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 13:41:40'),
(167, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 14:04:37'),
(168, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:07:44'),
(169, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:11:41'),
(170, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:19:48'),
(171, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:21:48'),
(172, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:22:04'),
(173, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:22:49'),
(174, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:23:04'),
(175, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:34:13'),
(176, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:34:47'),
(177, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:37:35'),
(178, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:52:24'),
(179, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:54:18'),
(180, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:57:08'),
(181, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 03:45:44'),
(182, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 03:46:03'),
(183, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 03:49:05'),
(184, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:05:26'),
(185, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:05:54'),
(186, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:06:14'),
(187, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:06:30'),
(188, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:06:56'),
(189, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:14:48'),
(190, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 06:15:14'),
(191, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 07:23:44'),
(192, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 09:10:56'),
(193, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 10:01:36'),
(194, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 10:25:44'),
(195, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 10:26:54'),
(196, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 10:40:56'),
(197, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:11:00'),
(198, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:14:44'),
(199, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:15:05'),
(200, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:16:48'),
(201, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:24:28'),
(202, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:31:18'),
(203, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:42:50'),
(204, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:45:17'),
(205, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:50:21'),
(206, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:58:26'),
(207, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 12:06:41'),
(208, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.102.3 Chrome/134.0.6998.205 Electron/35.6.0 Safari/537.36', '2025-08-02 12:11:00'),
(209, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.102.3 Chrome/134.0.6998.205 Electron/35.6.0 Safari/537.36', '2025-08-02 12:11:09'),
(210, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 12:26:19'),
(211, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 12:27:56'),
(212, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 12:28:13'),
(213, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:18:13'),
(214, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:27:43'),
(215, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:30:04'),
(216, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:30:10'),
(217, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:31:42'),
(218, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:32:09'),
(219, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:36:18'),
(220, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:39:10'),
(221, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:45:02'),
(222, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:54:40'),
(223, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:55:18'),
(224, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:56:41'),
(225, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 14:16:44'),
(226, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 14:38:42'),
(227, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 14:54:06'),
(228, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 15:16:10'),
(229, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 15:31:17'),
(230, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 15:56:11'),
(231, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:05:58'),
(232, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:06:54'),
(233, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:10:35'),
(234, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:10:54'),
(235, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:18:52'),
(236, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:22:55'),
(237, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:27:36'),
(238, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:27:57'),
(239, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:28:12'),
(240, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:28:27'),
(241, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:39:09'),
(242, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:00:47'),
(243, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:26:19'),
(244, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:27:31'),
(245, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:28:00'),
(246, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:30:05'),
(247, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:34:06'),
(248, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:48:40'),
(249, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:49:24'),
(250, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:50:10'),
(251, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:51:18'),
(252, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:51:49'),
(253, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:52:27'),
(254, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:12:42'),
(255, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:20:02'),
(256, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:20:13'),
(257, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:20:56'),
(258, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:27:26'),
(259, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:34:03'),
(260, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:35:54'),
(261, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:39:54'),
(262, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:45:35'),
(263, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:50:02'),
(264, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:54:35'),
(265, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-09 09:48:57'),
(266, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-09 09:56:51');
INSERT INTO `system_logs` (`id`, `user_id`, `school_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(267, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-09 10:32:20'),
(268, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-09 10:35:08'),
(269, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-09 10:35:40'),
(270, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-10 11:38:35'),
(271, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-10 11:52:35'),
(272, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-10 15:18:42'),
(273, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-10 16:07:33'),
(274, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-10 16:08:10'),
(275, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:03:13'),
(276, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:04:54'),
(277, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:06:50'),
(278, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:07:36'),
(279, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:08:28'),
(280, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:09:24'),
(281, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:25:38'),
(282, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 06:02:20'),
(283, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 06:03:07'),
(284, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 06:12:01'),
(285, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 06:12:11'),
(286, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 10:11:54'),
(287, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 10:13:21'),
(288, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 10:30:15'),
(289, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 11:36:31'),
(290, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 11:39:03'),
(291, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 11:40:16'),
(292, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 13:03:44'),
(293, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-13 07:42:54'),
(294, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-13 07:43:46'),
(295, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-13 07:44:10'),
(296, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:10:08'),
(297, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:12:06'),
(298, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:12:30'),
(299, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:12:46'),
(300, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:13:33'),
(301, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:15:29'),
(302, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:15:40'),
(303, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:15:59'),
(304, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:17:04'),
(305, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:17:27'),
(306, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:33:19'),
(307, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:40:52'),
(308, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 07:58:47'),
(309, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 08:27:31'),
(310, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 08:44:45'),
(311, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 12:09:17'),
(312, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 12:14:39'),
(313, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 12:26:29'),
(314, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 12:55:07'),
(315, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 13:22:38'),
(316, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 13:23:48'),
(317, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 13:36:25'),
(318, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 13:57:44'),
(319, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 13:59:14'),
(320, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 13:59:47'),
(321, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 14:01:18'),
(322, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 14:01:56'),
(323, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 14:14:02'),
(324, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 14:18:19'),
(325, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 14:28:22'),
(326, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 14:36:37'),
(327, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 14:59:26'),
(328, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 15:00:38'),
(329, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 15:06:09'),
(330, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 15:17:07'),
(331, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 15:36:09'),
(332, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 15:37:01'),
(333, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 15:37:17'),
(334, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 15:53:35'),
(335, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 15:55:44'),
(336, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 15:59:52'),
(337, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 16:00:58'),
(338, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 16:01:35'),
(339, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 16:01:53'),
(340, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 16:02:54'),
(341, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 16:03:15'),
(342, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 16:09:57'),
(343, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 17:01:26'),
(344, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 17:12:41'),
(345, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 17:22:29'),
(346, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 18:36:26'),
(347, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 18:41:39'),
(348, NULL, 2, 'USER_LOGIN', 'School: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 18:47:10'),
(349, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 18:47:24'),
(350, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 18:50:57'),
(351, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 03:52:46'),
(352, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 05:05:52'),
(353, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 05:14:38'),
(354, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 05:23:34'),
(355, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 05:34:03'),
(356, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 05:34:21'),
(357, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 05:34:35'),
(358, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 06:20:53'),
(359, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 06:21:57'),
(360, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 06:22:08'),
(361, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 06:28:45'),
(362, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 06:57:17'),
(363, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 07:43:29'),
(364, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 07:44:04'),
(365, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 07:44:48'),
(366, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 09:28:04'),
(367, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 11:05:25'),
(368, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 11:12:48'),
(369, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 12:04:34'),
(370, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 12:38:11'),
(371, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 12:38:54'),
(372, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 13:29:21'),
(373, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 18:55:16'),
(374, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 1, Expires: 2025-08-21 23:34:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 21:34:24'),
(375, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 2, Expires: 2025-08-21 23:35:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 21:35:45'),
(376, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 2, Expires: 2025-08-21 23:35:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 21:35:47'),
(377, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 2, Expires: 2025-08-21 23:35:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 21:35:48'),
(378, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 12:21:33'),
(379, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 12:43:23'),
(380, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 12:46:13'),
(381, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 12:55:44'),
(382, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 12:57:29'),
(383, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 12:57:59'),
(384, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:21:42'),
(385, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:24:37'),
(386, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:32:57'),
(387, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:41:24'),
(388, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:41:57'),
(389, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:42:21'),
(390, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:46:31'),
(391, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:47:11'),
(392, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:48:15'),
(393, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:48:35'),
(394, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:49:05'),
(395, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:52:38'),
(396, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:53:17'),
(397, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 13:54:01'),
(398, 26, NULL, 'USER_UPDATED', 'User ID: 26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:00:46'),
(399, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:01:14'),
(400, NULL, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:01:52'),
(401, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:02:12'),
(402, 26, NULL, 'USER_CREATED', 'User ID: 28, Role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:03:21'),
(403, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:03:31'),
(404, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:04:01'),
(405, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:04:42'),
(406, 26, NULL, 'USER_DELETED', 'User ID: 24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:04:53'),
(407, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:05:10'),
(408, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:05:54'),
(409, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:06:10'),
(410, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:12:24'),
(411, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 14:13:18'),
(412, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 15:21:58'),
(413, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 15:22:29'),
(414, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 15:35:59'),
(415, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 15:39:05'),
(416, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 15:43:32'),
(417, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 15:52:33'),
(418, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 15:53:13'),
(419, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 15:54:13'),
(420, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 15:54:22'),
(421, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:03:14'),
(422, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:04:06'),
(423, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:20:32'),
(424, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 2, Expires: 2025-08-23 18:22:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:22:51'),
(425, 26, NULL, 'THEME_PASSKEY_USED', 'Passkey ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:22:59'),
(426, 26, NULL, 'THEME_UPDATED', 'School ID: 1, Color: #098744', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:22:59'),
(427, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:25:38'),
(428, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:25:43'),
(429, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:25:59'),
(430, 26, NULL, 'USER_ROLE_CHANGED', 'User ID: 23, New Role: super_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:28:32'),
(431, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:28:45'),
(432, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:28:58'),
(433, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:29:19'),
(434, 26, NULL, 'USER_UPDATED', 'User ID: 23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:29:26'),
(435, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:29:34'),
(436, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:29:46'),
(437, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:29:57'),
(438, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:37:51'),
(439, 26, NULL, 'SCHOOL_ADDED', 'Name: Saint Louise Anne Colleges, Code: SLAC', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 16:44:53'),
(440, 26, NULL, 'SCHOOL_UPDATED', 'School ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:13:33'),
(441, NULL, NULL, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:17:38'),
(442, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:17:49'),
(443, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:17:58'),
(444, 26, NULL, 'SCHOOL_UPDATED', 'School ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:35:25'),
(445, 26, NULL, 'SCHOOL_DELETED', 'School ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:35:29'),
(446, 26, NULL, 'SCHOOL_ADDED', 'Name: Saint Louise Anne Colleges, Code: SLAC', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:35:45'),
(447, 26, NULL, 'SCHOOL_DELETED', 'School ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:35:57'),
(448, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:45:05'),
(449, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:45:45'),
(450, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:45:56'),
(451, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:46:14'),
(452, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:47:02'),
(453, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:47:33'),
(454, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-22 17:48:07'),
(455, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:37:24'),
(456, 26, NULL, 'SCHOOL_ADDED', 'Name: Saint Louise Anne Colleges, Code: SLAC', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:46:29'),
(457, 26, NULL, 'SCHOOL_DELETED', 'School ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:46:55'),
(458, 26, NULL, 'USER_CREATED', 'User ID: 29, Role: super_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:47:25'),
(459, 26, NULL, 'USER_DELETED', 'User ID: 29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:47:33'),
(460, 26, NULL, 'SCHOOL_ADDED', 'Name: Saint Louise Anne Colleges, Code: SLAC', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:48:14'),
(461, 26, NULL, 'SCHOOL_DELETED', 'School ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:48:23'),
(462, 26, NULL, 'SCHOOL_ADDED', 'Name: Saint Louise Anne Colleges, Code: SLAC', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:48:31'),
(463, 26, NULL, 'SCHOOL_DELETED', 'School ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:48:37'),
(464, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:06:07'),
(465, 26, NULL, 'SCHOOL_ADDED', 'Name: San Pedro City Polytechnic College, Code: SPCPC', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:29:23'),
(466, 26, NULL, 'SCHOOL_ADDED', 'Name: Saint Louise Anne Colleges, Code: SLAC', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:30:04'),
(467, 26, NULL, 'SCHOOL_DELETED', 'School ID: 16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:30:11'),
(468, 26, NULL, 'USER_CREATED', 'User ID: 30, Role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:31:30'),
(469, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:31:43'),
(470, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:42:56'),
(471, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 11:43:08'),
(472, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 12:52:11'),
(473, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:20:34'),
(474, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:21:22'),
(475, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:21:29'),
(476, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:21:55'),
(477, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:22:04'),
(478, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:28:17'),
(479, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:28:29'),
(480, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:28:48'),
(481, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:29:02'),
(482, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:31:40'),
(483, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:31:51'),
(484, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:31:58'),
(485, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:32:11'),
(486, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:32:37'),
(487, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:47:58'),
(488, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:49:13'),
(489, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:51:28'),
(490, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:51:57'),
(491, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 13:53:41'),
(492, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-24 16:11:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:11:08'),
(493, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 2, Expires: 2025-08-24 16:11:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:11:28'),
(494, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-24 16:12:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:12:09'),
(495, 26, NULL, 'THEME_PASSKEY_USED', 'Passkey ID: 8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:12:19'),
(496, 26, NULL, 'THEME_UPDATED', 'School ID: 15, Color: #38e589', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:12:19'),
(497, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-24 16:12:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:12:59'),
(498, 26, NULL, 'THEME_PASSKEY_USED', 'Passkey ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:13:04'),
(499, 26, NULL, 'THEME_UPDATED', 'School ID: 15, Color: #098744', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:13:04'),
(500, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 0, Expires: 2025-08-24 16:13:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:13:25'),
(501, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 2, Expires: 2025-08-24 16:13:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:13:28'),
(502, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 2, Expires: 2025-08-24 16:13:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:13:29'),
(503, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 2, Expires: 2025-08-24 16:15:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:15:50'),
(504, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-24 16:16:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:16:15'),
(505, 26, NULL, 'THEME_PASSKEY_USED', 'Passkey ID: 14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:16:23'),
(506, 26, NULL, 'THEME_UPDATED', 'School ID: 15, Color: #5cd695', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:16:23'),
(507, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 0, Expires: 2025-08-24 16:16:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:16:45'),
(508, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-24 16:16:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:16:59'),
(509, 26, NULL, 'THEME_PASSKEY_USED', 'Passkey ID: 16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:17:05'),
(510, 26, NULL, 'THEME_UPDATED', 'School ID: 15, Color: #098744', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:17:05'),
(511, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 14:20:12'),
(512, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 04:34:46'),
(513, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 05:01:14'),
(514, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 05:02:52'),
(515, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 05:07:45'),
(516, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 05:49:31'),
(517, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 06:43:25'),
(518, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:31:09'),
(519, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:47:11');
INSERT INTO `system_logs` (`id`, `user_id`, `school_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(520, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:47:23'),
(521, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:47:31'),
(522, 26, NULL, 'USER_UPDATED', 'User ID: 28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:48:08'),
(523, 26, NULL, 'USER_UPDATED', 'User ID: 28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:48:49'),
(524, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:49:12'),
(525, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:49:24'),
(526, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 2, Expires: 2025-08-25 10:50:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:50:16'),
(527, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-25 10:50:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:50:31'),
(528, 26, NULL, 'THEME_PASSKEY_USED', 'Passkey ID: 18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:50:43'),
(529, 26, NULL, 'THEME_UPDATED', 'School ID: 15, Color: #389f68', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:50:43'),
(530, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-25 10:51:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:51:04'),
(531, 26, NULL, 'THEME_PASSKEY_USED', 'Passkey ID: 19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:51:11'),
(532, 26, NULL, 'THEME_UPDATED', 'School ID: 15, Color: #098744', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:51:11'),
(533, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:52:04'),
(534, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:52:56'),
(535, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:53:05'),
(536, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 08:56:25'),
(537, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 09:16:59'),
(538, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 09:27:17'),
(539, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 2, Expires: 2025-08-25 11:29:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 09:29:36'),
(540, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 10:41:43'),
(541, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 11:33:15'),
(542, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 11:43:48'),
(543, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 12:15:55'),
(544, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 12:16:39'),
(545, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 12:21:06'),
(546, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 12:22:14'),
(547, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 12:22:30'),
(548, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 12:32:11'),
(549, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 13:00:31'),
(550, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-24 13:18:23'),
(551, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 07:10:19'),
(552, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 07:49:49'),
(553, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 07:50:50'),
(554, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 07:52:17'),
(555, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 07:54:20'),
(556, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 07:58:11'),
(557, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 08:07:42'),
(558, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 09:22:58'),
(559, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 09:23:17'),
(560, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 09:28:03'),
(561, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 09:31:02'),
(562, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 09:50:00'),
(563, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:14:41'),
(564, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:24:03'),
(565, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:24:15'),
(566, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:28:56'),
(567, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:33:08'),
(568, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:33:18'),
(569, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:33:34'),
(570, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:40:16'),
(571, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:40:26'),
(572, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 10:55:52'),
(573, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 11:05:36'),
(574, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 11:05:52'),
(575, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 11:07:34'),
(576, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 11:11:01'),
(577, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 11:11:43'),
(578, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 11:12:55'),
(579, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 11:14:13'),
(580, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 16:42:17'),
(581, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 16:54:35'),
(582, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 16:54:41'),
(583, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 14:52:35'),
(584, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 17:38:47'),
(585, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 17:43:48'),
(586, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 17:45:54'),
(587, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 17:46:13'),
(588, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 17:46:21'),
(589, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 17:53:58'),
(590, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 17:54:22'),
(591, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 17:54:31'),
(592, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 17:54:55'),
(593, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 17:55:14'),
(594, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 18:34:46'),
(595, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 19:39:09'),
(596, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:12:25'),
(597, 26, NULL, 'SCHOOL_UPDATED', 'School ID: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:17:08'),
(598, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-30 22:17:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:17:27'),
(599, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-29 23:17:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:17:52'),
(600, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-30 22:20:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:20:24'),
(601, 26, NULL, 'THEME_PASSKEY_USED', 'Passkey ID: 23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:20:34'),
(602, 26, NULL, 'THEME_UPDATED', 'School ID: 15, Color: #098744', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:20:34'),
(603, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-30 22:22:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:22:16'),
(604, 26, NULL, 'THEME_PASSKEY_USED', 'Passkey ID: 24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:22:27'),
(605, 26, NULL, 'THEME_UPDATED', 'School ID: 15, Color: #5fbf8c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:22:27'),
(606, 26, NULL, 'THEME_PASSKEY_GENERATED', 'School ID: 15, Expires: 2025-08-30 22:22:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:22:45'),
(607, 26, NULL, 'THEME_PASSKEY_USED', 'Passkey ID: 25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:22:54'),
(608, 26, NULL, 'THEME_UPDATED', 'School ID: 15, Color: #098744', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:22:54'),
(609, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:34:18'),
(610, 26, NULL, 'SCHOOL_UPDATED', 'School ID: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:34:58'),
(611, 26, NULL, 'SCHOOL_LOGO_UPDATED', 'School ID: 15, Logo: uploads/school_logos/school_logo_15_1756499698.jpeg', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:34:58'),
(612, 26, NULL, 'SCHOOL_UPDATED', 'School ID: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:38:06'),
(613, 26, NULL, 'SCHOOL_LOGO_UPDATED', 'School ID: 15, Logo: uploads/school_logos/school_logo_15_1756499886.png', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:38:06'),
(614, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:38:15'),
(615, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:41:53'),
(616, 26, NULL, 'SCHOOL_UPDATED', 'School ID: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:44:25'),
(617, 26, NULL, 'SCHOOL_LOGO_UPDATED', 'School ID: 15, Logo: uploads/school_logos/school_logo_15_1756500265.jpeg', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:44:25'),
(618, 26, NULL, 'SCHOOL_UPDATED', 'School ID: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:44:57'),
(619, 26, NULL, 'SCHOOL_LOGO_UPDATED', 'School ID: 15, Logo: uploads/school_logos/school_logo_15_1756500297.jpg', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:44:57'),
(620, 26, NULL, 'SCHOOL_UPDATED', 'School ID: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:45:35'),
(621, 26, NULL, 'SCHOOL_LOGO_UPDATED', 'School ID: 15, Logo: uploads/school_logos/school_logo_15_1756500335.png', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:45:36'),
(622, 26, NULL, 'SCHOOL_UPDATED', 'School ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:45:43'),
(623, 26, NULL, 'SCHOOL_LOGO_UPDATED', 'School ID: 2, Logo: uploads/school_logos/school_logo_2_1756500343.png', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:45:43'),
(624, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:47:30'),
(625, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:47:51'),
(626, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:48:11'),
(627, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:48:53'),
(628, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:52:42'),
(629, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 20:59:59'),
(630, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 21:00:12'),
(631, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 21:03:17'),
(632, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 21:08:44'),
(633, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 21:09:13'),
(634, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 21:09:24'),
(635, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 21:09:54'),
(636, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 21:15:27'),
(637, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 02:25:33'),
(638, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 02:25:55'),
(639, 26, NULL, 'USER_UPDATED', 'User ID: 28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 02:29:56'),
(640, 26, NULL, 'USER_UPDATED', 'User ID: 28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 02:30:15'),
(641, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 02:46:53'),
(642, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 02:49:56'),
(643, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 03:04:33'),
(644, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 11:21:41'),
(645, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 11:28:37'),
(646, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 11:31:24'),
(647, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 11:32:07'),
(648, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 11:42:46'),
(649, 30, 15, 'USER_LOGIN', 'School: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 12:22:55'),
(650, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 12:25:33'),
(651, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 12:34:32'),
(652, 26, NULL, 'SUPER_ADMIN_LOGIN', 'Super admin logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 12:42:09'),
(653, 28, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-30 12:58:32');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(191) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'super_admin_pin_hash', '$2y$10$pM8D9Ztp5YXl/asf0/JgVen5xnxxd7Zn8Vr4Tx2/q/FiC/0yWpqzW', '2025-08-22 15:21:17', '2025-08-22 15:21:17');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user_logs`
--

CREATE TABLE `tbl_user_logs` (
  `log_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `user_type` varchar(20) DEFAULT 'User',
  `log_in_time` datetime DEFAULT current_timestamp(),
  `log_out_time` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_id` int(11) NOT NULL DEFAULT 1,
  `school_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_user_logs`
--

INSERT INTO `tbl_user_logs` (`log_id`, `username`, `user_type`, `log_in_time`, `log_out_time`, `ip_address`, `user_id`, `school_id`) VALUES
(604, 'escall', 'Super Admin', '2025-08-22 20:01:41', NULL, '::1', 26, 0),
(605, 'escall', 'Super Admin', '2025-08-22 20:02:12', NULL, '::1', 26, 0),
(606, 'escall', 'Super Admin', '2025-08-22 20:06:37', NULL, '::1', 26, 0),
(607, 'escall', 'Super Admin', '2025-08-22 20:21:33', NULL, '::1', 26, 0),
(608, 'escall', 'Super Admin', '2025-08-22 20:43:23', NULL, '::1', 26, 0),
(609, 'escall', 'Super Admin', '2025-08-22 20:46:13', NULL, '::1', 26, 0),
(610, 'escall', 'Super Admin', '2025-08-22 20:55:44', NULL, '::1', 26, 0),
(611, 'escall', 'Super Admin', '2025-08-22 20:57:29', NULL, '::1', 26, 0),
(612, 'escall', 'Super Admin', '2025-08-22 20:57:59', NULL, '::1', 26, 0),
(613, 'escall', 'Super Admin', '2025-08-22 21:21:42', NULL, '::1', 26, 0),
(614, 'escall', 'Super Admin', '2025-08-22 21:24:37', NULL, '::1', 26, 0),
(615, 'escall', 'Super Admin', '2025-08-22 21:32:57', NULL, '::1', 26, 0),
(616, 'escall', 'Super Admin', '2025-08-22 21:41:24', NULL, '::1', 26, 0),
(617, 'escall', 'admin', '2025-08-22 21:41:57', NULL, '::1', 23, 1),
(618, 'escall', 'Super Admin', '2025-08-22 21:42:21', NULL, '::1', 26, 0),
(619, 'escall', 'Super Admin', '2025-08-22 21:46:31', NULL, '::1', 26, 0),
(620, 'alex', 'Super Admin', '2025-08-22 21:47:11', NULL, '::1', 26, 0),
(621, 'alex', 'Super Admin', '2025-08-22 21:48:15', NULL, '::1', 26, 0),
(622, 'alex', 'Super Admin', '2025-08-22 21:48:35', NULL, '::1', 26, 0),
(623, 'alex', 'Super Admin', '2025-08-22 21:49:05', NULL, '::1', 26, 0),
(624, 'alex', 'Super Admin', '2025-08-22 21:52:38', NULL, '::1', 26, 0),
(625, 'alex', 'admin', '2025-08-22 21:53:17', NULL, '::1', 24, 2),
(626, 'alex', 'Super Admin', '2025-08-22 21:54:01', NULL, '::1', 26, 0),
(627, 'JOERENZ', 'Super Admin', '2025-08-22 22:01:14', NULL, '::1', 26, 0),
(628, 'JOERENZ', 'admin', '2025-08-22 22:01:52', NULL, '::1', 24, 2),
(629, 'alex', 'Super Admin', '2025-08-22 22:02:12', NULL, '::1', 26, 0),
(630, 'COMSITE', 'admin', '2025-08-22 22:03:31', '2025-08-22 22:04:17', '::1', 28, 2),
(631, 'COMSITE', 'admin', '2025-08-22 22:04:01', '2025-08-23 21:58:37', '::1', 28, 2),
(632, 'alex', 'Super Admin', '2025-08-22 22:04:42', NULL, '::1', 26, 0),
(633, 'COMSITE', 'admin', '2025-08-22 22:05:10', '2025-08-23 21:58:37', '::1', 28, 2),
(634, 'escall', 'admin', '2025-08-22 22:05:54', NULL, '::1', 23, 1),
(635, 'ara', 'admin', '2025-08-22 22:06:10', NULL, '::1', 25, 1),
(636, 'alex', 'Super Admin', '2025-08-22 22:12:24', NULL, '::1', 26, 0),
(637, 'alex', 'Super Admin', '2025-08-22 22:13:18', NULL, '::1', 26, 0),
(638, 'ADMIN - Bagwis', 'Super Admin', '2025-08-22 23:21:58', NULL, '::1', 26, 0),
(639, 'COMSITE', 'admin', '2025-08-22 23:22:29', '2025-08-23 21:58:37', '::1', 28, 2),
(640, 'ADMIN - Bagwis', 'Super Admin', '2025-08-22 23:35:59', NULL, '::1', 26, 0),
(641, 'COMSITE', 'admin', '2025-08-22 23:39:05', '2025-08-23 21:58:37', '::1', 28, 2),
(642, 'ADMIN - Bagwis', 'Super Admin', '2025-08-22 23:43:32', NULL, '::1', 26, 0),
(643, 'ara', 'admin', '2025-08-22 23:52:33', NULL, '::1', 25, 1),
(644, 'ADMIN - Bagwis', 'Super Admin', '2025-08-22 23:53:13', NULL, '::1', 26, 0),
(645, 'ADMIN - Bagwis', 'Super Admin', '2025-08-22 23:54:13', NULL, '::1', 26, 0),
(646, 'ADMIN - Bagwis', 'Super Admin', '2025-08-22 23:54:22', NULL, '::1', 26, 0),
(647, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 00:03:14', NULL, '::1', 26, 0),
(648, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 00:04:06', NULL, '::1', 26, 0),
(649, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 00:20:32', NULL, '::1', 26, 0),
(650, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 00:25:38', NULL, '::1', 26, 0),
(651, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 00:25:43', NULL, '::1', 26, 0),
(652, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 00:25:59', NULL, '::1', 26, 0),
(653, 'escall', 'super_admin', '2025-08-23 00:28:45', NULL, '::1', 23, 1),
(654, 'escall', 'super_admin', '2025-08-23 00:28:58', NULL, '::1', 23, 1),
(655, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 00:29:19', NULL, '::1', 26, 0),
(656, 'COMSITE', 'admin', '2025-08-23 00:29:34', '2025-08-23 21:58:37', '::1', 28, 2),
(657, 'escall', 'admin', '2025-08-23 00:29:46', NULL, '::1', 23, 1),
(658, 'ara', 'admin', '2025-08-23 00:29:57', NULL, '::1', 25, 1),
(659, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 00:37:51', NULL, '::1', 26, 0),
(660, 'ara', 'admin', '2025-08-23 01:17:38', NULL, '::1', 25, 1),
(661, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 01:17:49', NULL, '::1', 26, 0),
(662, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 01:17:58', NULL, '::1', 26, 0),
(663, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 01:45:05', NULL, '::1', 26, 0),
(664, 'COMSITE', 'admin', '2025-08-23 01:45:45', '2025-08-23 21:58:37', '::1', 28, 2),
(665, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 01:45:56', NULL, '::1', 26, 0),
(666, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 01:46:14', NULL, '::1', 26, 0),
(667, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 01:47:02', NULL, '::1', 26, 0),
(668, 'Qnnect ', 'Super Admin', '2025-08-23 01:47:33', NULL, '::1', 26, 0),
(669, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 01:48:07', NULL, '::1', 26, 0),
(670, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 12:37:23', NULL, '::1', 26, 0),
(671, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 19:06:07', NULL, '::1', 26, 0),
(672, 'SPCPC', 'admin', '2025-08-23 19:31:43', '2025-08-24 16:53:18', '::1', 30, 15),
(673, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 19:42:56', NULL, '::1', 26, 0),
(674, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 19:43:08', NULL, '::1', 26, 0),
(675, 'SPCPC', 'admin', '2025-08-23 20:52:11', '2025-08-24 16:53:18', '::1', 30, 15),
(676, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 21:20:34', NULL, '::1', 26, 0),
(677, 'SPCPC', 'admin', '2025-08-23 21:21:22', '2025-08-24 16:53:18', '::1', 30, 15),
(678, 'COMSITE', 'admin', '2025-08-23 21:21:29', '2025-08-23 21:58:37', '::1', 28, 2),
(679, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 21:21:55', NULL, '::1', 26, 0),
(680, 'COMSITE', 'admin', '2025-08-23 21:22:04', '2025-08-23 21:58:37', '::1', 28, 2),
(681, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 21:28:17', NULL, '::1', 26, 0),
(682, 'COMSITE', 'admin', '2025-08-23 21:28:29', '2025-08-23 21:58:37', '::1', 28, 2),
(683, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 21:28:48', NULL, '::1', 26, 0),
(684, 'SPCPC', 'admin', '2025-08-23 21:29:02', '2025-08-24 16:53:18', '::1', 30, 15),
(685, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 21:31:40', NULL, '::1', 26, 0),
(686, 'COMSITE', 'admin', '2025-08-23 21:31:51', '2025-08-23 21:58:37', '::1', 28, 2),
(687, 'COMSITE', 'admin', '2025-08-23 21:31:58', '2025-08-23 21:58:37', '::1', 28, 2),
(688, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 21:32:11', NULL, '::1', 26, 0),
(689, 'COMSITE', 'admin', '2025-08-23 21:32:37', '2025-08-23 21:58:37', '::1', 28, 2),
(690, 'COMSITE', 'admin', '2025-08-23 21:47:58', '2025-08-23 21:58:37', '::1', 28, 2),
(691, 'SPCPC', 'admin', '2025-08-23 21:49:13', '2025-08-24 16:53:18', '::1', 30, 15),
(692, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 21:51:28', NULL, '::1', 26, 0),
(693, 'ADMIN - Bagwis', 'Super Admin', '2025-08-23 21:51:57', NULL, '::1', 26, 0),
(694, 'COMSITE', 'admin', '2025-08-23 21:53:41', '2025-08-24 20:21:38', '::1', 28, 2),
(695, 'SPCPC', 'admin', '2025-08-23 22:20:12', '2025-08-24 16:53:18', '::1', 30, 15),
(696, 'COMSITE', 'admin', '2025-08-24 12:34:46', '2025-08-24 20:21:38', '::1', 28, 2),
(697, 'SPCPC', 'admin', '2025-08-24 13:01:14', '2025-08-24 16:53:18', '::1', 30, 15),
(698, 'COMSITE', 'admin', '2025-08-24 13:02:52', '2025-08-24 20:21:38', '::1', 28, 2),
(699, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 13:07:45', NULL, '::1', 26, 0),
(700, 'SPCPC', 'admin', '2025-08-24 13:49:31', '2025-08-24 16:53:18', '::1', 30, 15),
(701, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 14:43:25', NULL, '::1', 26, 0),
(702, 'COMSITE', 'admin', '2025-08-24 16:31:09', '2025-08-24 20:21:38', '::1', 28, 2),
(703, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 16:47:11', NULL, '::1', 26, 0),
(704, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 16:47:23', NULL, '::1', 26, 0),
(705, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 16:47:31', NULL, '::1', 26, 0),
(706, 'COMSITE', 'admin', '2025-08-24 16:49:12', '2025-08-24 20:21:38', '::1', 28, 2),
(707, 'COMSITE', 'admin', '2025-08-24 16:49:24', '2025-08-24 20:21:38', '::1', 28, 2),
(708, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 16:52:04', NULL, '::1', 26, 0),
(709, 'COMSITE', 'admin', '2025-08-24 16:52:56', '2025-08-24 20:21:38', '::1', 28, 2),
(710, 'SPCPC', 'admin', '2025-08-24 16:53:05', '2025-08-25 17:56:20', '::1', 30, 15),
(711, 'COMSITE', 'admin', '2025-08-24 16:56:25', '2025-08-24 20:21:38', '::1', 28, 2),
(712, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 17:16:59', NULL, '::1', 26, 0),
(713, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 17:27:17', NULL, '::1', 26, 0),
(714, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 18:41:43', NULL, '::1', 26, 0),
(715, 'COMSITE', 'admin', '2025-08-24 19:33:15', '2025-08-24 20:21:38', '::1', 28, 2),
(716, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 19:43:48', NULL, '::1', 26, 0),
(717, 'SPCPC', 'admin', '2025-08-24 20:15:55', '2025-08-25 17:56:20', '::1', 30, 15),
(718, 'COMSITE', 'admin', '2025-08-24 20:16:39', '2025-08-24 20:21:38', '::1', 28, 2),
(719, 'COMSITE', 'admin', '2025-08-24 20:21:06', '2025-08-27 00:42:28', '::1', 28, 2),
(720, 'SPCPC', 'admin', '2025-08-24 20:22:14', '2025-08-25 17:56:20', '::1', 30, 15),
(721, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 20:22:30', NULL, '::1', 26, 0),
(722, 'COMSITE', 'admin', '2025-08-24 20:32:11', '2025-08-27 00:42:28', '::1', 28, 2),
(723, 'SPCPC', 'admin', '2025-08-24 21:00:31', '2025-08-25 17:56:20', '::1', 30, 15),
(724, 'ADMIN - Bagwis', 'Super Admin', '2025-08-24 21:18:23', NULL, '::1', 26, 0),
(725, 'COMSITE', 'admin', '2025-08-25 15:10:19', '2025-08-27 00:42:28', '::1', 28, 2),
(726, 'SPCPC', 'admin', '2025-08-25 15:49:49', '2025-08-25 17:56:20', '::1', 30, 15),
(727, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 15:50:50', NULL, '::1', 26, 0),
(728, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 15:52:17', NULL, '::1', 26, 0),
(729, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 15:54:20', NULL, '::1', 26, 0),
(730, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 15:58:11', NULL, '::1', 26, 0),
(731, 'COMSITE', 'admin', '2025-08-25 16:07:42', '2025-08-27 00:42:28', '::1', 28, 2),
(732, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 17:22:58', NULL, '::1', 26, 0),
(733, 'COMSITE', 'admin', '2025-08-25 17:23:17', '2025-08-27 00:42:28', '::1', 28, 2),
(734, 'COMSITE', 'admin', '2025-08-25 17:28:03', '2025-08-27 00:42:28', '::1', 28, 2),
(735, 'COMSITE', 'admin', '2025-08-25 17:31:02', '2025-08-27 00:42:28', '::1', 28, 2),
(736, 'SPCPC', 'admin', '2025-08-25 17:50:00', NULL, '::1', 30, 15),
(737, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 18:14:41', NULL, '::1', 26, 0),
(738, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 18:24:03', NULL, '::1', 26, 0),
(739, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 18:24:15', NULL, '::1', 26, 0),
(740, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 18:28:56', NULL, '::1', 26, 0),
(741, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 18:33:08', NULL, '::1', 26, 0),
(742, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 18:33:18', NULL, '::1', 26, 0),
(743, 'COMSITE', 'admin', '2025-08-25 18:33:34', '2025-08-27 00:42:28', '::1', 28, 2),
(744, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 18:40:16', NULL, '::1', 26, 0),
(745, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 18:40:26', NULL, '::1', 26, 0),
(746, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 18:55:52', NULL, '::1', 26, 0),
(747, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 19:05:36', NULL, '::1', 26, 0),
(748, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 19:05:52', NULL, '::1', 26, 0),
(749, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 19:07:34', NULL, '::1', 26, 0),
(750, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 19:11:01', NULL, '::1', 26, 0),
(751, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 19:11:43', NULL, '::1', 26, 0),
(752, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 19:12:55', NULL, '::1', 26, 0),
(753, 'ADMIN - Bagwis', 'Super Admin', '2025-08-25 19:14:13', NULL, '::1', 26, 0),
(754, 'COMSITE', 'admin', '2025-08-27 00:42:17', '2025-08-30 10:55:37', '::1', 28, 2),
(755, 'ADMIN - Bagwis', 'Super Admin', '2025-08-27 00:54:35', NULL, '::1', 26, 0),
(756, 'ADMIN - Bagwis', 'Super Admin', '2025-08-27 00:54:41', NULL, '::1', 26, 0),
(757, 'ADMIN - Bagwis', 'Super Admin', '2025-08-29 22:52:35', NULL, '::1', 26, 0),
(758, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 01:38:47', NULL, '::1', 26, 0),
(759, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 01:43:48', NULL, '::1', 26, 0),
(760, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 01:45:54', NULL, '::1', 26, 0),
(761, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 01:46:13', NULL, '::1', 26, 0),
(762, 'COMSITE', 'admin', '2025-08-30 01:46:21', '2025-08-30 10:55:37', '::1', 28, 2),
(763, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 01:53:58', NULL, '::1', 26, 0),
(764, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 01:54:22', NULL, '::1', 26, 0),
(765, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 01:54:31', NULL, '::1', 26, 0),
(766, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 01:54:55', NULL, '::1', 26, 0),
(767, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 01:55:14', NULL, '::1', 26, 0),
(768, 'COMSITE', 'admin', '2025-08-30 02:34:46', '2025-08-30 10:55:37', '::1', 28, 2),
(769, 'COMSITE', 'admin', '2025-08-30 03:39:09', '2025-08-30 10:55:37', '::1', 28, 2),
(770, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 04:12:24', NULL, '::1', 26, 0),
(771, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 04:34:18', NULL, '::1', 26, 0),
(772, 'SPCPC', 'admin', '2025-08-30 04:38:15', NULL, '::1', 30, 15),
(773, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 04:41:53', NULL, '::1', 26, 0),
(774, 'SPCPC', 'admin', '2025-08-30 04:47:30', NULL, '::1', 30, 15),
(775, 'COMSITE', 'admin', '2025-08-30 04:47:51', '2025-08-30 10:55:37', '::1', 28, 2),
(776, 'COMSITE', 'admin', '2025-08-30 04:48:11', '2025-08-30 10:55:37', '::1', 28, 2),
(777, 'COMSITE', 'admin', '2025-08-30 04:48:53', '2025-08-30 10:55:37', '::1', 28, 2),
(778, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 04:52:42', NULL, '::1', 26, 0),
(779, 'SPCPC', 'admin', '2025-08-30 04:59:59', NULL, '::1', 30, 15),
(780, 'COMSITE', 'admin', '2025-08-30 05:00:12', '2025-08-30 10:55:37', '::1', 28, 2),
(781, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 05:03:17', NULL, '::1', 26, 0),
(782, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 05:08:44', NULL, '::1', 26, 0),
(783, 'SPCPC', 'admin', '2025-08-30 05:09:13', NULL, '::1', 30, 15),
(784, 'COMSITE', 'admin', '2025-08-30 05:09:24', '2025-08-30 10:55:37', '::1', 28, 2),
(785, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 05:09:54', NULL, '::1', 26, 0),
(786, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 05:15:27', NULL, '::1', 26, 0),
(787, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 10:25:33', NULL, '::1', 26, 0),
(788, 'COMSITE', 'admin', '2025-08-30 10:25:55', '2025-08-30 10:55:37', '::1', 28, 2),
(789, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 10:46:53', NULL, '::1', 26, 0),
(790, 'COMSITE', 'admin', '2025-08-30 10:49:56', NULL, '::1', 28, 2),
(791, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 11:04:33', NULL, '::1', 26, 0),
(792, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 19:21:41', NULL, '::1', 26, 0),
(793, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 19:28:37', NULL, '::1', 26, 0),
(794, 'COMSITE', 'admin', '2025-08-30 19:31:24', NULL, '::1', 28, 2),
(795, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 19:32:07', NULL, '::1', 26, 0),
(796, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 19:42:46', NULL, '::1', 26, 0),
(797, 'SPCPC', 'admin', '2025-08-30 20:22:55', NULL, '::1', 30, 15),
(798, 'COMSITE', 'admin', '2025-08-30 20:25:33', NULL, '::1', 28, 2),
(799, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 20:34:32', NULL, '::1', 26, 0),
(800, 'ADMIN - Bagwis', 'Super Admin', '2025-08-30 20:42:09', NULL, '::1', 26, 0),
(801, 'COMSITE', 'admin', '2025-08-30 20:58:32', NULL, '::1', 28, 2);

-- --------------------------------------------------------

--
-- Table structure for table `theme_passkeys`
--

CREATE TABLE `theme_passkeys` (
  `id` int(11) NOT NULL,
  `key_hash` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `used_by` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `theme_passkeys`
--

INSERT INTO `theme_passkeys` (`id`, `key_hash`, `created_by`, `school_id`, `used`, `used_at`, `used_by`, `expires_at`, `created_at`) VALUES
(1, '$2y$10$NUb9z0yFtGG2dAOIvavrme7gRgFRAeFiHeVzO4MrCH46n8iYrR3ne', 26, 1, 0, NULL, NULL, '2025-08-21 23:34:24', '2025-08-20 21:34:24'),
(2, '$2y$10$G6yXqBJBZZeziz.1V7cquOl0eUoZzHeE02MffqoZWReHZl82fcLcG', 26, 2, 0, NULL, NULL, '2025-08-21 23:35:45', '2025-08-20 21:35:45'),
(3, '$2y$10$ZB9n5KTUjkbaz0xhLm05buc/oWm0qJRkzlYEWIOyze3Z4CdpLVsym', 26, 2, 0, NULL, NULL, '2025-08-21 23:35:47', '2025-08-20 21:35:47'),
(4, '$2y$10$86MbnYBNlwmmL1ayEDPB/uOrLVLSVcEv6Y8/sSkTHLiXsARIF0Eta', 26, 2, 0, NULL, NULL, '2025-08-21 23:35:48', '2025-08-20 21:35:48'),
(5, '$2y$10$JYD2mX9gVYFSiYEG5GBtmu8Sc5wItk52ARErmtuyWw9QoTNRLu..2', 26, 2, 1, '2025-08-23 00:22:59', 26, '2025-08-23 18:22:51', '2025-08-22 16:22:51'),
(6, '$2y$10$PlDW7WJqnhvgtOrACQ.ij.aoVHOAdAQKCVvntDDD51DBm8aQXIY0a', 26, 15, 0, NULL, NULL, '2025-08-24 16:11:08', '2025-08-23 14:11:08'),
(7, '$2y$10$BsxPzy/X/daBPBeLYCyMreRTUNyLlU5oW9V7F/yeBZnKv79T6fJ9G', 26, 2, 0, NULL, NULL, '2025-08-24 16:11:28', '2025-08-23 14:11:28'),
(8, '$2y$10$JMy/bQdV/90Y/lhKCbOGHeiELFx1Hmgn1ojZ2vCJMBlb1cELnB7f6', 26, 15, 1, '2025-08-23 22:12:19', 26, '2025-08-24 16:12:09', '2025-08-23 14:12:09'),
(9, '$2y$10$kQbNbxvaTI8v5DOZM4ls5ukcftOzLjvguo6ikPWm6oQ2yNElOEo2G', 26, 15, 1, '2025-08-23 22:13:04', 26, '2025-08-24 16:12:59', '2025-08-23 14:12:59'),
(10, '$2y$10$74HPU2VKiNZThvvhgS3N3OcKwlU1KoeckeK67w9ymPttnF8k9GJf2', 26, 0, 0, NULL, NULL, '2025-08-24 16:13:25', '2025-08-23 14:13:25'),
(11, '$2y$10$zZP8BCi7AbhmjOzkhMIxh.5I2eqjDx1LZ8gvuwt6NPTfwPEe6PGf2', 26, 2, 0, NULL, NULL, '2025-08-24 16:13:28', '2025-08-23 14:13:28'),
(12, '$2y$10$xnm4e7xHtczAoxxIhNCbA.GC.M/VWp3F0FyBzD/KSpnlq/2Be9d2e', 26, 2, 0, NULL, NULL, '2025-08-24 16:13:29', '2025-08-23 14:13:29'),
(13, '$2y$10$xFMQIkqlic/NkGRuOj6cAemqx1IC4C/waCkOkJvv6V4yBgmq.arqy', 26, 2, 0, NULL, NULL, '2025-08-24 16:15:50', '2025-08-23 14:15:50'),
(14, '$2y$10$fXBfD1aBq/q9eFyIlNJVy.mTtwefbIwCzDlrw3ElQMVBCdziJ.yxu', 26, 15, 1, '2025-08-23 22:16:23', 26, '2025-08-24 16:16:15', '2025-08-23 14:16:15'),
(15, '$2y$10$fAohkIcwY6JFSLonWQyKH.YXkShBPIg0UywMIY0ePGF7AS9vbxZfS', 26, 0, 0, NULL, NULL, '2025-08-24 16:16:45', '2025-08-23 14:16:45'),
(16, '$2y$10$D6yk8wNdxGHaQiPi21me4OtPJ5GN3BxGyBRU5y34phI.ix6DprU9e', 26, 15, 1, '2025-08-23 22:17:05', 26, '2025-08-24 16:16:59', '2025-08-23 14:16:59'),
(17, '$2y$10$V3XlZ5QlbRTVzDVh.X7LQuU3ym/BVVkU8hQ7C6XbbAbY/271KFDrG', 26, 2, 0, NULL, NULL, '2025-08-25 10:50:16', '2025-08-24 08:50:16'),
(18, '$2y$10$OMl.Pc8y1.LKKmtXAG30aedwk.Xcu4kxgAM7OJNKzf.fp21.DPEzO', 26, 15, 1, '2025-08-24 16:50:43', 26, '2025-08-25 10:50:31', '2025-08-24 08:50:31'),
(19, '$2y$10$l86QIlhO8MOX3JREsFczFeyBcYX.Hh4.sCn1foCfLYBbF2OBI1p1K', 26, 15, 1, '2025-08-24 16:51:11', 26, '2025-08-25 10:51:04', '2025-08-24 08:51:04'),
(20, '$2y$10$yh2TGtGX2HzgTFrKnLTeX.H8LSq7R3cNR5LwYuf6CKhDeGhlGzOhi', 26, 2, 0, NULL, NULL, '2025-08-25 11:29:36', '2025-08-24 09:29:36'),
(21, '$2y$10$cSnZFIxiBYT8HJ2ocvFXq.X9ggtNZAM1XdVQVd0uj.EsTJamR/7.K', 26, 15, 0, NULL, NULL, '2025-08-30 22:17:27', '2025-08-29 20:17:27'),
(22, '$2y$10$CdeSFIfXe2B.A0bA28RX4u9QLsSgap9ZsV4v4QgtsuJLabA2PgBc6', 26, 15, 0, NULL, NULL, '2025-08-29 23:17:52', '2025-08-29 20:17:52'),
(23, '$2y$10$jrECcumxAK6SLSeikFYxL.QnCXSCzYmcTmgXHAgZJmv.oXx/r1TLu', 26, 15, 1, '2025-08-30 04:20:34', 26, '2025-08-30 22:20:24', '2025-08-29 20:20:24'),
(24, '$2y$10$2jjYQYpTIyH227gre3AS/.ku/334eEwxpFDZUcGG88EWYhZrmhhgC', 26, 15, 1, '2025-08-30 04:22:27', 26, '2025-08-30 22:22:16', '2025-08-29 20:22:16'),
(25, '$2y$10$7qr7wGFjYCbJGYIpjHU0neuzX0kvH3MHmnmWG.FVN8VhWeExUKfCG', 26, 15, 1, '2025-08-30 04:22:54', 26, '2025-08-30 22:22:45', '2025-08-29 20:22:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(128) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `reset_token_hash` varchar(255) DEFAULT NULL,
  `reset_token_hash_expires_at` datetime DEFAULT NULL,
  `user_type` varchar(20) DEFAULT 'User',
  `role` enum('admin','super_admin') DEFAULT 'admin',
  `school_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `username`, `password`, `profile_image`, `reset_token_hash`, `reset_token_hash_expires_at`, `user_type`, `role`, `school_id`) VALUES
(26, 'Alexander Joerenz Escallente', 'joerenzescallente027@gmail.com', 'ADMIN - Bagwis', '$2y$10$hs4DHo.1FASEj/B.4Vcyhu/QsYZV8KwlUz0n8LOqBlhCADunDMiEW', 'uploads/profile_images/profile_1755870815.png', NULL, NULL, 'User', 'super_admin', NULL),
(28, '', 'escall.byte@gmail.com', 'COMSITE', '$2y$10$.pTdjTgWQE.AzPRiPlQeqe75/Dd/JQ60WujMNWqcRUqeefW8To3Wm', 'uploads/profile_images/profile_1756558903.png', '0fd6ad7c1f4981301c8af1cfdd101d5d4291c56fa5c9aedb06b5ada40819ab86', '2025-08-30 04:37:13', 'User', 'admin', 2),
(30, '', 'spcpc2017ph@gmail.com', 'SPCPC', '$2y$10$Lwtk7g05AdFv8XL9yv05veIWSXDFExUIMX32OnVG56Xs/6xZznE2C', 'uploads/profile_images/profile_1756500463.png', NULL, NULL, 'User', 'admin', 15);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `recent_logins`
--
ALTER TABLE `recent_logins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_last_login` (`last_login`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `fk_recent_logins_school` (`school_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_room` (`school_id`,`room_name`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_action` (`user_id`,`action`),
  ADD KEY `idx_school_action` (`school_id`,`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `tbl_user_logs`
--
ALTER TABLE `tbl_user_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `theme_passkeys`
--
ALTER TABLE `theme_passkeys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school` (`school_id`),
  ADD KEY `idx_used` (`used`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`),
  ADD KEY `fk_users_school` (`school_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `recent_logins`
--
ALTER TABLE `recent_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=914;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=654;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_user_logs`
--
ALTER TABLE `tbl_user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=802;

--
-- AUTO_INCREMENT for table `theme_passkeys`
--
ALTER TABLE `theme_passkeys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `recent_logins`
--
ALTER TABLE `recent_logins`
  ADD CONSTRAINT `fk_recent_logins_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `system_logs_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
