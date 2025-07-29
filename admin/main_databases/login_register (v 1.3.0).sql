-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 29, 2025 at 05:27 PM
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
(1, 'escall', 'uploads/profile_images/profile_1752690336.jpg', '2025-07-27 14:24:17', '2025-07-13 16:43:05', 1),
(13, 'ara', 'uploads/profile_images/profile_1752744870_40011e02deb1edad.jpg', '2025-07-27 09:31:43', '2025-07-13 16:53:40', 1),
(114, 'alex', 'uploads/profile_images/profile_1752694438_4af1d3abb596df2b.jpg', '2025-07-27 10:20:11', '2025-07-16 19:34:13', 2);

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
(1, 1, 'Room 101', 30, 'classroom', 'available', '2025-07-16 16:51:47'),
(2, 1, 'Room 102', 30, 'classroom', 'available', '2025-07-16 16:51:47'),
(3, 1, 'Computer Lab 1', 25, 'laboratory', 'available', '2025-07-16 16:51:47'),
(4, 1, 'Computer Lab 2', 25, 'laboratory', 'available', '2025-07-16 16:51:47'),
(5, 1, 'Auditorium', 100, 'auditorium', 'available', '2025-07-16 16:51:47'),
(6, 2, 'Training Room A', 20, 'classroom', 'available', '2025-07-16 16:51:47'),
(7, 2, 'Training Room B', 20, 'classroom', 'available', '2025-07-16 16:51:47'),
(8, 2, 'Tech Lab', 15, 'laboratory', 'available', '2025-07-16 16:51:47'),
(9, 2, 'Conference Hall', 50, 'auditorium', 'available', '2025-07-16 16:51:47');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `class_name` varchar(255) NOT NULL,
  `instructor` varchar(255) NOT NULL,
  `room` varchar(100) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'SPCPC', 'SPCPC', '#098744', '2025-07-16 16:51:47', '2025-07-16 16:51:47', 'active'),
(2, 'Computer Site Inc.', 'CSI', '#4CAF00', '2025-07-16 16:51:47', '2025-07-21 02:31:47', 'active');

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
(1, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 16:52:42'),
(2, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 16:54:00'),
(3, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 16:56:32'),
(4, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 17:06:29'),
(5, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 18:01:46'),
(6, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 18:20:39'),
(7, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 18:23:05'),
(8, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 18:25:52'),
(9, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:20:03'),
(10, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:20:40'),
(11, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:22:17'),
(12, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:34:13'),
(13, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:38:11'),
(14, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-16 19:39:17'),
(15, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 04:25:02'),
(16, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 06:14:23'),
(17, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 06:14:38'),
(18, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 06:15:26'),
(19, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:05:41'),
(20, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:06:09'),
(21, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:08:13'),
(22, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:15:09'),
(23, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:15:27'),
(24, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:15:46'),
(25, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:23:28'),
(26, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 08:35:21'),
(27, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 10:11:55'),
(28, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 10:13:02'),
(29, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 10:13:55'),
(30, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 10:37:48'),
(31, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 10:38:34'),
(32, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 11:31:37'),
(33, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 12:27:22'),
(34, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-17 13:34:41'),
(35, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-18 04:52:46'),
(36, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-18 04:54:17'),
(37, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-20 10:25:17'),
(38, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-20 10:36:00'),
(39, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-20 11:34:56'),
(40, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-20 13:51:32'),
(41, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 02:06:39'),
(42, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 02:06:56'),
(43, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 02:08:06'),
(44, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 08:13:06'),
(45, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 08:28:50'),
(46, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:29:00'),
(47, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:30:36'),
(48, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:32:54'),
(49, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:33:03'),
(50, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:35:04'),
(51, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:41:44'),
(52, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:42:25'),
(53, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:48:38'),
(54, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:51:57'),
(55, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 10:03:33'),
(56, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 11:18:05'),
(57, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 11:21:47'),
(58, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:29:07'),
(59, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:35:13'),
(60, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:37:18'),
(61, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 13:40:58'),
(62, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 14:37:29'),
(63, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 16:47:40'),
(64, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 18:03:26'),
(65, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 18:17:26'),
(66, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 18:17:40'),
(67, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:02:55'),
(68, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:20:26'),
(69, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:21:32'),
(70, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:22:09'),
(71, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:28:58'),
(72, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:43:19'),
(73, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 07:47:17'),
(74, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 08:30:39'),
(75, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:28:33'),
(76, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:28:47'),
(77, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:30:28'),
(78, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:31:37'),
(79, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:31:55'),
(80, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 10:11:18'),
(81, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 14:02:22'),
(82, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 14:02:35'),
(83, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 14:06:59'),
(84, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 14:24:12');

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
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_user_logs`
--

INSERT INTO `tbl_user_logs` (`log_id`, `username`, `user_type`, `log_in_time`, `log_out_time`, `ip_address`) VALUES
(1, 'escalliente', 'User', '2025-03-17 20:57:53', '2025-03-17 20:59:20', '::1'),
(2, 'escalliente', 'User', '2025-03-17 20:59:33', '2025-03-17 21:00:20', '::1'),
(3, 'escalliente', 'User', '2025-03-17 21:01:27', '2025-06-16 18:55:05', '::1'),
(4, 'escalliente', 'User', '2025-03-17 21:06:32', '2025-06-16 18:55:05', '::1'),
(5, 'escalliente', 'User', '2025-03-17 21:07:00', '2025-06-16 18:55:05', '::1'),
(6, 'escalliente', 'User', '2025-03-17 21:07:58', '2025-03-17 21:19:23', '::1'),
(7, 'escalliente', 'User', '2025-03-17 21:19:39', '2025-03-17 21:21:15', '::1'),
(8, 'escalliente', 'User', '2025-03-17 21:21:29', '2025-03-17 21:23:32', '::1'),
(9, 'escalliente', 'User', '2025-03-17 21:24:22', '2025-03-17 21:24:31', '::1'),
(10, 'spcpc', 'User', '2025-03-17 21:25:17', '2025-03-17 21:44:59', '::1'),
(11, 'escalliente', 'User', '2025-03-17 21:45:12', '2025-03-17 22:09:03', '::1'),
(12, 'escalliente', 'User', '2025-03-17 22:09:18', '2025-06-16 18:55:05', '::1'),
(13, 'escalliente', 'User', '2025-03-18 08:36:58', '2025-03-18 08:37:52', '::1'),
(14, 'escalliente', 'User', '2025-03-18 08:41:51', '2025-06-16 18:55:05', '::1'),
(15, 'escalliente', 'User', '2025-03-18 08:56:39', '2025-03-18 08:59:38', '::1'),
(16, 'escalliente', 'User', '2025-03-18 08:59:49', '2025-03-18 08:59:57', '::1'),
(17, 'escalliente', 'User', '2025-03-20 12:36:53', '2025-06-16 18:55:05', '::1'),
(18, 'escalliente', 'User', '2025-03-20 12:51:32', '2025-06-16 18:55:05', '::1'),
(19, 'escalliente', 'User', '2025-03-20 19:22:46', '2025-03-20 19:53:09', '::1'),
(20, 'escalliente', 'User', '2025-03-20 19:53:22', '2025-06-16 18:55:05', '::1'),
(21, 'escalliente', 'User', '2025-03-24 20:16:32', '2025-06-16 18:55:05', '::1'),
(22, 'escall', 'User', '2025-03-24 20:25:18', '2025-03-24 20:33:04', '::1'),
(23, 'escall', 'User', '2025-03-24 20:33:09', '2025-03-24 20:36:06', '::1'),
(24, 'escall', 'User', '2025-03-24 20:37:24', '2025-06-16 18:54:47', '::1'),
(25, 'escall', 'User', '2025-03-27 11:52:34', '2025-03-27 11:52:42', '::1'),
(26, 'escall', 'User', '2025-03-27 13:11:48', '2025-06-16 18:54:47', '::1'),
(27, 'escall', 'User', '2025-03-27 13:15:06', '2025-03-27 13:26:26', '::1'),
(28, 'escall', 'User', '2025-03-27 13:26:32', '2025-06-16 18:54:47', '::1'),
(29, 'escall', 'User', '2025-03-28 20:12:23', '2025-03-28 20:14:38', '::1'),
(30, 'escall', 'User', '2025-03-28 20:14:43', '2025-03-28 20:15:54', '::1'),
(31, 'escall', 'User', '2025-04-02 17:49:09', '2025-04-02 18:45:26', '::1'),
(32, 'escall', 'User', '2025-04-02 18:45:32', '2025-06-16 18:54:47', '::1'),
(33, 'escall', 'User', '2025-04-02 20:12:20', '2025-06-16 18:54:47', '::1'),
(34, 'escall', 'User', '2025-04-02 20:55:03', '2025-04-02 20:55:14', '::1'),
(35, 'escall', 'User', '2025-04-03 11:04:17', '2025-04-03 11:05:01', '::1'),
(36, 'escall', 'User', '2025-04-03 11:24:00', '2025-06-16 18:54:47', '::1'),
(37, 'escall', 'User', '2025-04-03 21:14:47', '2025-06-16 18:54:47', '::1'),
(38, 'escall', 'User', '2025-04-04 09:34:30', '2025-06-16 18:54:47', '::1'),
(39, 'escall', 'User', '2025-04-04 20:50:38', '2025-04-04 20:54:51', '::1'),
(40, 'escall', 'User', '2025-04-04 20:55:00', '2025-06-16 18:54:47', '::1'),
(41, 'escall', 'User', '2025-04-05 06:50:18', '2025-04-05 06:51:43', '::1'),
(42, 'escall', 'User', '2025-04-05 07:01:54', '2025-06-16 18:54:47', '::1'),
(43, 'escall', 'User', '2025-04-05 07:58:48', '2025-04-05 08:33:42', '::1'),
(44, 'escall', 'User', '2025-04-05 09:28:12', '2025-04-05 10:07:50', '::1'),
(45, 'escall', 'User', '2025-04-05 10:38:08', '2025-04-05 11:27:21', '::1'),
(46, 'escall', 'User', '2025-04-05 12:56:28', '2025-04-05 12:57:14', '::1'),
(47, 'escall', 'User', '2025-04-05 12:57:20', '2025-04-05 13:00:07', '::1'),
(48, 'escall', 'User', '2025-04-05 13:00:17', '2025-04-05 14:41:15', '::1'),
(49, 'escall', 'User', '2025-04-05 14:41:50', '2025-04-05 14:47:35', '::1'),
(50, 'escall', 'User', '2025-04-05 14:47:53', '2025-04-05 14:55:03', '::1'),
(51, 'escall', 'User', '2025-04-05 14:55:17', '2025-04-05 15:00:43', '::1'),
(52, 'escall', 'User', '2025-04-05 15:00:53', '2025-04-05 15:22:15', '::1'),
(53, 'escall', 'User', '2025-04-05 15:22:25', '2025-04-05 16:07:49', '::1'),
(54, 'escall', 'User', '2025-04-05 16:08:14', '2025-04-05 16:42:34', '::1'),
(55, 'escall', 'User', '2025-04-05 16:42:54', '2025-04-05 17:34:05', '::1'),
(56, 'escall', 'User', '2025-04-05 17:34:11', '2025-06-16 18:54:47', '::1'),
(57, 'escall', 'User', '2025-04-05 17:45:45', '2025-04-05 17:45:53', '::1'),
(58, 'escall', 'User', '2025-04-05 17:45:59', '2025-04-05 18:10:51', '::1'),
(59, 'escall', 'User', '2025-04-05 18:11:01', '2025-04-05 18:11:14', '::1'),
(60, 'escall', 'User', '2025-04-08 08:21:17', '2025-04-08 08:25:15', '::1'),
(61, 'escall', 'User', '2025-04-08 09:21:44', '2025-04-08 10:24:00', '::1'),
(62, 'escall', 'User', '2025-04-09 19:08:27', '2025-04-09 20:42:49', '::1'),
(63, 'escall', 'User', '2025-04-09 20:43:04', '2025-04-09 21:40:41', '::1'),
(64, 'escall', 'User', '2025-04-09 21:40:50', '2025-04-09 21:44:54', '::1'),
(65, 'escall', 'User', '2025-04-09 21:45:11', '2025-06-16 18:54:47', '::1'),
(66, 'escall', 'User', '2025-04-13 21:55:39', '2025-04-13 21:58:41', '::1'),
(67, 'escall', 'User', '2025-04-20 00:20:12', '2025-04-20 00:35:05', '::1'),
(68, 'escall', 'User', '2025-04-20 00:35:13', '2025-04-20 00:42:41', '::1'),
(69, 'escall', 'User', '2025-04-20 00:42:56', '2025-06-16 18:54:47', '::1'),
(70, 'escall', 'User', '2025-04-22 13:57:42', '2025-04-22 14:00:37', '::1'),
(71, 'escall', 'User', '2025-04-22 14:01:00', '2025-04-22 14:04:19', '::1'),
(72, 'escall', 'User', '2025-04-24 20:16:01', '2025-04-24 21:11:53', '::1'),
(73, 'escall', 'User', '2025-04-24 21:11:58', '2025-04-24 21:35:24', '::1'),
(74, 'escall', 'User', '2025-04-24 21:40:53', '2025-04-24 23:05:48', '::1'),
(75, 'escall', 'User', '2025-04-24 23:05:59', '2025-04-25 02:18:47', '::1'),
(76, 'escall', 'User', '2025-04-25 02:19:31', '2025-04-25 03:07:26', '::1'),
(77, 'escall', 'User', '2025-04-25 03:08:03', '2025-06-16 18:54:47', '::1'),
(78, 'escall', 'User', '2025-04-25 20:28:03', '2025-06-16 18:54:47', '::1'),
(79, 'escall', 'User', '2025-05-03 15:25:40', '2025-05-03 15:30:11', '::1'),
(80, 'escall', 'User', '2025-05-03 15:45:39', '2025-05-03 15:50:25', '::1'),
(81, 'escall', 'User', '2025-05-03 15:50:31', '2025-05-03 15:57:30', '::1'),
(82, 'escall', 'User', '2025-05-03 15:57:45', '2025-05-03 16:09:03', '::1'),
(83, 'escall', 'User', '2025-05-03 16:09:57', '2025-05-03 16:19:33', '::1'),
(84, 'escall', 'User', '2025-05-03 16:20:20', '2025-06-16 18:54:47', '::1'),
(85, 'escall', 'User', '2025-05-03 16:37:32', '2025-05-03 22:07:47', '::1'),
(86, 'escall', 'User', '2025-05-03 22:08:04', '2025-05-03 22:26:06', '::1'),
(87, 'escall', 'User', '2025-05-03 22:26:45', '2025-06-16 18:54:47', '::1'),
(88, 'escall', 'User', '2025-05-14 17:28:46', '2025-05-14 17:29:25', '::1'),
(89, 'escall', 'User', '2025-05-15 00:17:05', '2025-05-15 00:17:12', '::1'),
(90, 'escall', 'User', '2025-05-15 01:38:58', '2025-05-15 01:39:05', '::1'),
(91, 'escall', 'User', '2025-05-15 02:22:40', '2025-06-16 18:54:47', '::1'),
(92, 'escall', 'User', '2025-05-15 02:23:30', '2025-05-15 02:23:34', '::1'),
(93, 'escall', 'User', '2025-05-15 13:26:01', '2025-05-15 13:26:11', '::1'),
(94, 'escall', 'User', '2025-05-19 00:33:07', '2025-05-19 00:33:19', '::1'),
(95, 'escall', 'User', '2025-06-04 19:58:20', '2025-06-04 20:04:23', '::1'),
(96, 'escall', 'User', '2025-06-05 17:01:35', '2025-06-05 17:17:11', '::1'),
(97, 'escall', 'User', '2025-06-16 18:14:49', '2025-06-16 18:16:08', '::1'),
(98, 'escall', 'User', '2025-06-16 18:16:15', '2025-06-16 18:17:01', '::1'),
(99, 'escall', 'User', '2025-06-16 18:17:12', '2025-06-16 18:30:51', '::1'),
(100, 'escall', 'User', '2025-06-16 18:30:57', '2025-06-16 18:54:47', '::1'),
(101, 'escall', 'User', '2025-06-16 18:47:54', '2025-06-16 18:48:15', '::1'),
(102, 'escall', 'User', '2025-06-16 18:48:38', '2025-06-16 18:54:47', '::1'),
(103, 'escall', 'User', '2025-06-16 18:48:41', '2025-06-16 18:54:47', '::1'),
(104, 'escall', 'User', '2025-06-16 18:50:41', '2025-06-16 18:50:41', '::1'),
(105, 'escall', 'User', '2025-06-16 18:50:41', '2025-06-16 18:55:05', '::1'),
(106, 'escall', 'User', '2025-06-16 18:55:05', '2025-06-16 19:00:38', '::1'),
(107, 'escall', 'User', '2025-06-16 19:01:04', '2025-06-16 19:15:02', '::1'),
(108, 'escall', 'User', '2025-06-16 19:01:07', '2025-06-16 19:15:02', '::1'),
(109, 'escall', 'User', '2025-06-16 19:06:17', '2025-06-16 19:06:17', '::1'),
(110, 'escall', 'User', '2025-06-16 19:06:17', '2025-06-16 19:06:29', '::1'),
(111, 'escall', 'User', '2025-06-16 19:06:35', '2025-06-16 19:15:02', '::1'),
(112, 'escall', 'User', '2025-06-16 19:06:37', '2025-06-16 19:06:54', '::1'),
(113, 'escall', 'User', '2025-06-16 19:07:00', '2025-06-16 19:15:02', '::1'),
(114, 'escall', 'User', '2025-06-16 19:07:03', '2025-06-16 19:07:33', '::1'),
(115, 'escall', 'User', '2025-06-16 19:07:40', '2025-06-16 19:15:02', '::1'),
(116, 'escall', 'User', '2025-06-16 19:07:42', '2025-06-16 19:11:26', '::1'),
(117, 'escall', 'User', '2025-06-16 19:11:31', '2025-06-16 19:15:02', '::1'),
(118, 'escall', 'User', '2025-06-16 19:11:36', '2025-06-16 19:15:02', '::1'),
(119, 'escall', 'User', '2025-06-16 19:15:02', '2025-06-16 19:15:39', '::1'),
(120, 'escall', 'User', '2025-06-16 19:15:45', '2025-06-16 19:15:57', '::1'),
(121, 'escall', 'User', '2025-06-16 19:15:48', '2025-06-16 19:15:57', '::1'),
(122, 'escall', 'User', '2025-06-16 19:15:57', '2025-06-16 19:29:45', '::1'),
(123, 'escall', 'User', '2025-06-16 19:29:51', '2025-06-16 22:02:33', '::1'),
(124, 'escall', 'User', '2025-06-17 12:30:23', '2025-06-21 13:40:52', '::1'),
(125, 'escall', 'User', '2025-06-21 13:40:10', '2025-06-21 13:41:26', '127.0.0.1'),
(126, 'ara', 'User', '2025-06-21 13:42:44', '2025-06-21 13:44:51', '127.0.0.1'),
(127, 'ara', 'User', '2025-06-21 13:47:02', '2025-06-21 13:47:08', '127.0.0.1'),
(128, 'ara', 'User', '2025-06-21 13:47:52', '2025-06-21 13:48:12', '127.0.0.1'),
(129, 'ara', 'User', '2025-06-21 15:08:45', '2025-06-21 15:13:38', '127.0.0.1'),
(130, 'ara', 'User', '2025-06-21 15:13:43', '2025-06-21 17:48:48', '127.0.0.1'),
(131, 'ara', 'User', '2025-06-21 15:14:49', '2025-06-21 15:20:43', '127.0.0.1'),
(132, 'escall', 'User', '2025-06-21 15:16:21', '2025-06-21 15:45:28', '::1'),
(133, 'ara', 'User', '2025-06-21 15:20:49', '2025-06-21 17:48:48', '127.0.0.1'),
(134, 'ara', 'User', '2025-06-21 15:22:44', '2025-06-21 17:48:48', '127.0.0.1'),
(135, 'ara', 'User', '2025-06-21 15:24:21', '2025-06-21 17:48:48', '127.0.0.1'),
(136, 'ara', 'User', '2025-06-21 15:37:52', '2025-06-21 17:48:48', '127.0.0.1'),
(137, 'ara', 'User', '2025-06-21 15:42:37', '2025-06-21 17:48:48', '127.0.0.1'),
(138, 'ara', 'User', '2025-06-21 15:46:19', '2025-06-21 17:48:48', '::1'),
(139, 'ara', 'User', '2025-06-21 15:46:41', '2025-06-21 17:48:48', '::1'),
(140, 'ara', 'User', '2025-06-21 15:47:53', '2025-06-21 17:48:48', '::1'),
(141, 'ara', 'User', '2025-06-21 16:37:30', '2025-06-21 17:48:48', '::1'),
(142, 'ara', 'User', '2025-06-21 16:50:48', '2025-06-21 16:51:07', '::1'),
(143, 'ara', 'User', '2025-06-21 17:04:42', '2025-06-21 17:48:48', '::1'),
(144, 'ara', 'User', '2025-06-21 17:05:58', '2025-06-21 17:48:48', '::1'),
(145, 'ara', 'User', '2025-06-21 17:10:07', '2025-06-21 17:11:58', '::1'),
(146, 'ara', 'User', '2025-06-21 17:12:03', '2025-06-21 18:28:28', '::1'),
(147, 'ara', 'User', '2025-06-21 17:13:29', '2025-06-21 17:13:37', '127.0.0.1'),
(148, 'ara', 'User', '2025-06-21 18:28:32', '2025-06-21 21:32:57', '::1'),
(149, 'ara', 'User', '2025-06-21 20:42:51', '2025-06-21 20:43:47', '127.0.0.1'),
(150, 'ara', 'User', '2025-06-21 21:58:44', '2025-06-21 22:38:42', '::1'),
(151, 'ara', 'User', '2025-06-21 22:38:46', '2025-06-22 00:10:21', '::1'),
(152, 'ara', 'User', '2025-06-22 00:10:36', '2025-06-22 01:38:09', '::1'),
(153, 'ara', 'User', '2025-06-22 01:38:13', '2025-06-22 01:38:19', '::1'),
(154, 'ara', 'User', '2025-06-22 13:25:21', '2025-06-22 13:49:33', '::1'),
(155, 'ara', 'User', '2025-06-22 13:50:00', '2025-06-22 14:30:13', '::1'),
(156, 'ara', 'User', '2025-06-23 19:45:31', '2025-06-23 19:52:30', '::1'),
(157, 'ara', 'User', '2025-06-23 20:14:36', '2025-06-23 20:31:17', '::1'),
(158, 'ara', 'User', '2025-06-23 20:14:42', '2025-06-23 20:14:57', '::1'),
(159, 'ara', 'User', '2025-06-23 20:15:02', '2025-06-23 20:15:30', '::1'),
(160, 'ara', 'User', '2025-06-23 20:31:13', '2025-06-23 20:31:28', '::1'),
(161, 'spcpc', 'User', '2025-06-23 20:33:24', '2025-06-23 20:33:47', '::1'),
(162, 'escall', 'User', '2025-06-24 22:27:55', '2025-07-13 22:16:40', '::1'),
(163, 'ara', 'User', '2025-06-25 19:39:57', '2025-06-25 19:53:46', '::1'),
(164, 'ara', 'User', '2025-06-25 19:53:58', '2025-06-25 19:56:36', '::1'),
(165, 'ara', 'User', '2025-07-07 20:57:38', '2025-07-13 22:13:27', '::1'),
(166, 'ara', 'User', '2025-07-13 22:13:12', '2025-07-13 22:13:36', '::1'),
(167, 'escall', 'User', '2025-07-13 22:16:16', '2025-07-13 22:16:40', '::1'),
(168, 'escall', 'User', '2025-07-13 22:16:38', '2025-07-17 02:03:34', '::1'),
(169, 'escall', 'User', '2025-07-13 22:18:25', '2025-07-13 22:18:42', '::1'),
(170, 'escall', 'User', '2025-07-13 22:19:11', '2025-07-13 22:19:14', '::1'),
(171, 'escall', 'User', '2025-07-13 22:32:12', '2025-07-13 22:32:17', '::1'),
(172, 'ara', 'User', '2025-07-13 22:32:20', '2025-07-13 22:32:23', '::1'),
(173, 'escall', 'User', '2025-07-13 22:32:29', '2025-07-13 22:32:45', '::1'),
(174, 'escall', 'User', '2025-07-13 22:32:50', '2025-07-13 22:32:54', '::1'),
(175, 'escall', 'User', '2025-07-13 22:33:16', '2025-07-13 22:33:25', '::1'),
(176, 'escall', 'User', '2025-07-13 23:02:37', '2025-07-13 23:02:40', '::1'),
(177, 'escall', 'User', '2025-07-14 00:26:30', '2025-07-14 00:28:51', '::1'),
(178, 'escall', 'User', '2025-07-14 00:43:05', '2025-07-17 02:03:34', '::1'),
(179, 'escall', 'User', '2025-07-14 00:43:15', '2025-07-17 02:03:34', '::1'),
(180, 'escall', 'User', '2025-07-14 00:50:04', '2025-07-17 02:03:34', '::1'),
(181, 'ara', 'User', '2025-07-14 00:53:40', NULL, '::1'),
(182, 'escall', 'User', '2025-07-14 01:02:08', '2025-07-17 02:03:34', '::1'),
(183, 'ara', 'User', '2025-07-14 01:02:46', NULL, '::1'),
(184, 'escall', 'User', '2025-07-14 01:04:39', '2025-07-17 02:03:34', '::1'),
(185, 'escall', 'User', '2025-07-14 01:05:04', '2025-07-17 02:03:34', '::1'),
(186, 'escall', 'User', '2025-07-14 01:08:22', '2025-07-17 02:03:34', '::1'),
(187, 'ara', 'User', '2025-07-14 01:09:10', NULL, '::1'),
(188, 'escall', 'User', '2025-07-14 01:09:32', '2025-07-17 02:03:34', '::1'),
(189, 'ara', 'User', '2025-07-14 01:09:41', NULL, '::1'),
(190, 'escall', 'User', '2025-07-14 01:24:14', '2025-07-17 02:03:34', '::1'),
(191, 'ara', 'User', '2025-07-14 01:39:00', NULL, '::1'),
(192, 'ara', 'User', '2025-07-14 01:46:14', NULL, '::1'),
(193, 'ara', 'User', '2025-07-14 01:46:26', NULL, '::1'),
(194, 'ara', 'User', '2025-07-14 01:50:34', NULL, '::1'),
(195, 'escall', 'User', '2025-07-14 01:51:51', '2025-07-17 02:03:34', '::1'),
(196, 'ara', 'User', '2025-07-14 01:53:49', NULL, '::1'),
(197, 'ara', 'User', '2025-07-14 02:01:34', NULL, '::1'),
(198, 'ara', 'User', '2025-07-14 02:01:48', NULL, '::1'),
(199, 'ara', 'User', '2025-07-14 02:40:18', NULL, '::1'),
(200, 'ara', 'User', '2025-07-14 02:40:37', NULL, '::1'),
(201, 'escall', 'User', '2025-07-14 10:55:08', '2025-07-17 02:03:34', '::1'),
(202, 'ara', 'User', '2025-07-14 10:55:18', '2025-07-14 11:00:12', '::1'),
(203, 'ara', 'User', '2025-07-14 11:22:04', NULL, '::1'),
(204, 'escall', 'User', '2025-07-14 11:24:52', '2025-07-14 11:29:14', '::1'),
(205, 'ara', 'User', '2025-07-14 11:32:14', NULL, '::1'),
(206, 'ara', 'User', '2025-07-14 11:54:25', '2025-07-14 11:56:40', '::1'),
(207, 'ara', 'User', '2025-07-14 14:05:10', NULL, '::1'),
(208, 'ara', 'User', '2025-07-14 14:33:51', NULL, '::1'),
(209, 'ara', 'User', '2025-07-17 00:44:51', NULL, '::1'),
(210, 'escall', 'admin', '2025-07-17 00:52:42', '2025-07-17 02:03:34', '::1'),
(211, 'escall', 'admin', '2025-07-17 00:54:00', '2025-07-17 02:03:34', '::1'),
(212, 'escall', 'admin', '2025-07-17 00:56:32', '2025-07-17 02:03:34', '::1'),
(213, 'escall', 'admin', '2025-07-17 01:06:29', '2025-07-17 02:03:34', '::1'),
(214, 'escall', 'User', '2025-07-17 01:06:45', '2025-07-17 02:03:34', '::1'),
(215, 'escall', 'admin', '2025-07-17 02:01:46', '2025-07-17 02:03:34', '::1'),
(216, 'escall', 'User', '2025-07-17 02:02:46', '2025-07-17 03:25:51', '::1'),
(217, 'escall', 'User', '2025-07-17 02:04:22', '2025-07-17 03:25:51', '::1'),
(218, 'escall', 'admin', '2025-07-17 02:20:39', '2025-07-17 03:25:51', '::1'),
(219, 'escall', 'admin', '2025-07-17 02:23:05', '2025-07-17 03:25:51', '::1'),
(220, 'escall', 'admin', '2025-07-17 02:25:52', '2025-07-17 03:25:51', '::1'),
(221, 'escall', 'admin', '2025-07-17 03:20:03', '2025-07-17 03:25:51', '::1'),
(222, 'escall', 'admin', '2025-07-17 03:20:40', '2025-07-17 03:25:51', '::1'),
(223, 'escall', 'admin', '2025-07-17 03:22:17', '2025-07-17 12:26:22', '::1'),
(224, 'alex', 'admin', '2025-07-17 03:34:13', '2025-07-17 16:36:26', '::1'),
(225, 'escall', 'admin', '2025-07-17 03:38:11', '2025-07-17 12:26:22', '::1'),
(226, 'alex', 'admin', '2025-07-17 03:39:17', '2025-07-17 16:36:26', '::1'),
(227, 'escall', 'admin', '2025-07-17 12:25:02', '2025-07-17 14:14:25', '::1'),
(228, 'escall', 'admin', '2025-07-17 14:14:23', '2025-07-17 19:31:45', '::1'),
(229, 'alex', 'admin', '2025-07-17 14:14:38', '2025-07-17 16:36:26', '::1'),
(230, 'escall', 'admin', '2025-07-17 14:15:26', '2025-07-17 19:31:45', '::1'),
(231, 'alex', 'admin', '2025-07-17 16:05:41', '2025-07-17 16:36:26', '::1'),
(232, 'escall', 'admin', '2025-07-17 16:06:09', '2025-07-17 19:31:45', '::1'),
(233, 'alex', 'admin', '2025-07-17 16:08:13', '2025-07-17 16:36:26', '::1'),
(234, 'alex', 'admin', '2025-07-17 16:15:09', '2025-07-17 16:36:26', '::1'),
(235, 'escall', 'admin', '2025-07-17 16:15:27', '2025-07-17 19:31:45', '::1'),
(236, 'alex', 'admin', '2025-07-17 16:15:46', '2025-07-17 16:36:26', '::1'),
(237, 'alex', 'admin', '2025-07-17 16:23:28', '2025-07-17 16:36:26', '::1'),
(238, 'alex', 'admin', '2025-07-17 16:35:21', '2025-07-24 21:37:27', '::1'),
(239, 'alex', 'User', '2025-07-17 16:39:49', '2025-07-24 21:37:27', '::1'),
(240, 'alex', 'User', '2025-07-17 17:40:25', '2025-07-24 21:37:27', '::1'),
(241, 'escall', 'admin', '2025-07-17 18:11:55', '2025-07-17 19:31:45', '::1'),
(242, 'alex', 'admin', '2025-07-17 18:13:02', '2025-07-24 21:37:27', '::1'),
(243, 'escall', 'admin', '2025-07-17 18:13:55', '2025-07-17 19:31:45', '::1'),
(244, 'escall', 'User', '2025-07-17 18:34:00', '2025-07-17 18:34:44', '::1'),
(245, 'alex', 'admin', '2025-07-17 18:37:48', '2025-07-24 21:37:27', '::1'),
(246, 'escall', 'admin', '2025-07-17 18:38:34', '2025-07-17 19:31:45', '::1'),
(247, 'escall', 'User', '2025-07-17 18:38:58', '2025-07-17 21:51:05', '::1'),
(248, 'escall', 'admin', '2025-07-17 19:31:37', '2025-07-20 18:36:09', '::1'),
(249, 'escall', 'admin', '2025-07-17 20:27:22', '2025-07-20 18:36:09', '::1'),
(250, 'escall', 'admin', '2025-07-17 21:34:41', '2025-07-20 18:36:09', '::1'),
(251, 'ara', 'admin', '2025-07-18 12:52:46', NULL, '::1'),
(252, 'ara', 'admin', '2025-07-18 12:54:16', NULL, '::1'),
(253, 'ara', 'admin', '2025-07-20 18:25:17', NULL, '::1'),
(254, 'escall', 'admin', '2025-07-20 18:36:00', '2025-07-20 19:35:56', '::1'),
(255, 'escall', 'admin', '2025-07-20 19:34:56', '2025-07-24 16:33:10', '::1'),
(256, 'escall', 'admin', '2025-07-20 21:51:32', '2025-07-24 16:33:10', '::1'),
(257, 'ara', 'admin', '2025-07-21 10:06:39', NULL, '::1'),
(258, 'alex', 'admin', '2025-07-21 10:06:56', '2025-07-24 21:37:27', '::1'),
(259, 'escall', 'admin', '2025-07-21 10:08:06', '2025-07-24 16:33:10', '::1'),
(260, 'escall', 'User', '2025-07-21 10:08:37', '2025-07-21 11:34:17', '::1'),
(261, 'escall', 'User', '2025-07-21 11:34:37', '2025-07-21 11:34:40', '::1'),
(262, 'escall', 'admin', '2025-07-24 16:13:06', '2025-07-24 16:33:10', '::1'),
(263, 'escall', 'admin', '2025-07-24 16:28:50', '2025-07-27 15:20:36', '::1'),
(264, 'escall', 'admin', '2025-07-24 17:29:00', '2025-07-27 15:20:36', '::1'),
(265, 'escall', 'admin', '2025-07-24 17:30:36', '2025-07-27 15:20:36', '::1'),
(266, 'escall', 'admin', '2025-07-24 17:32:54', '2025-07-27 15:20:36', '::1'),
(267, 'escall', 'admin', '2025-07-24 17:33:03', '2025-07-27 15:20:36', '::1'),
(268, 'escall', 'admin', '2025-07-24 17:35:04', '2025-07-27 15:20:36', '::1'),
(269, 'escall', 'admin', '2025-07-24 17:41:44', '2025-07-27 15:20:36', '::1'),
(270, 'alex', 'admin', '2025-07-24 17:42:25', '2025-07-24 21:37:27', '::1'),
(271, 'escall', 'admin', '2025-07-24 17:48:38', '2025-07-27 15:20:36', '::1'),
(272, 'escall', 'admin', '2025-07-24 17:51:57', '2025-07-27 15:20:36', '::1'),
(273, 'escall', 'admin', '2025-07-24 18:03:33', '2025-07-27 15:20:36', '::1'),
(274, 'escall', 'User', '2025-07-24 18:13:13', '2025-07-27 15:20:36', '::1'),
(275, 'escall', 'admin', '2025-07-24 19:18:05', '2025-07-27 15:20:36', '::1'),
(276, 'escall', 'admin', '2025-07-24 19:21:47', '2025-07-27 15:20:36', '::1'),
(277, 'alex', 'admin', '2025-07-24 21:29:07', '2025-07-24 21:37:27', '::1'),
(278, 'escall', 'admin', '2025-07-24 21:35:13', '2025-07-27 15:20:36', '::1'),
(279, 'alex', 'admin', '2025-07-24 21:37:18', '2025-07-24 22:58:12', '::1'),
(280, 'escall', 'admin', '2025-07-24 21:40:58', '2025-07-27 15:20:36', '::1'),
(281, 'alex', 'admin', '2025-07-24 22:37:29', NULL, '::1'),
(282, 'escall', 'admin', '2025-07-25 00:47:40', '2025-07-27 15:20:36', '::1'),
(283, 'escall', 'admin', '2025-07-25 02:03:26', '2025-07-27 15:20:36', '::1'),
(284, 'escall', 'admin', '2025-07-25 02:17:26', '2025-07-27 15:20:36', '::1'),
(285, 'alex', 'admin', '2025-07-25 02:17:40', NULL, '::1'),
(286, 'escall', 'admin', '2025-07-27 15:02:55', '2025-07-27 15:20:36', '::1'),
(287, 'escall', 'admin', '2025-07-27 15:20:26', '2025-07-27 15:29:08', '::1'),
(288, 'alex', 'admin', '2025-07-27 15:21:32', NULL, '::1'),
(289, 'escall', 'admin', '2025-07-27 15:22:09', '2025-07-27 15:29:08', '::1'),
(290, 'escall', 'User', '2025-07-27 15:27:48', '2025-07-27 15:29:11', '::1'),
(291, 'alex', 'admin', '2025-07-27 15:28:58', NULL, '::1'),
(292, 'escall', 'admin', '2025-07-27 15:43:19', '2025-07-27 16:31:20', '::1'),
(293, 'escall', 'admin', '2025-07-27 15:47:17', '2025-07-27 16:31:20', '::1'),
(294, 'escall', 'admin', '2025-07-27 16:30:39', '2025-07-27 17:41:03', '::1'),
(295, 'alex', 'admin', '2025-07-27 17:28:33', NULL, '::1'),
(296, 'escall', 'admin', '2025-07-27 17:28:47', '2025-07-27 17:41:03', '::1'),
(297, 'alex', 'admin', '2025-07-27 17:30:28', NULL, '::1'),
(298, 'ara', 'admin', '2025-07-27 17:31:37', NULL, '::1'),
(299, 'escall', 'admin', '2025-07-27 17:31:55', NULL, '::1'),
(300, 'alex', 'admin', '2025-07-27 18:11:18', NULL, '::1'),
(301, 'escall', 'admin', '2025-07-27 22:02:22', NULL, '::1'),
(302, 'escall', 'admin', '2025-07-27 22:02:35', NULL, '::1'),
(303, 'escall', 'admin', '2025-07-27 22:06:59', NULL, '::1'),
(304, 'escall', 'admin', '2025-07-27 22:24:12', NULL, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `theme_passkeys`
--

CREATE TABLE `theme_passkeys` (
  `id` int(11) NOT NULL,
  `key_hash` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `school_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `used_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(23, '', 'joerenz.dev@gmail.com', 'escall', '$2y$10$xVW9ejlEgIDKNtnmi/qPDOEGsVUYk4fhAeJb7kE6LT7UPowejKLqO', 'uploads/profile_images/profile_1752690336.jpg', NULL, NULL, 'User', 'admin', 1),
(24, '', 'joerenzescallente027@gmail.com', 'alex', '$2y$10$KfySL3frEq2tuYLfwyNpHe4hzgeU/jo55aad4/gcpeki3nNItNsaa', 'uploads/profile_images/profile_1752694438_4af1d3abb596df2b.jpg', NULL, NULL, 'User', 'admin', 2),
(25, '', 'araranaydo@gmail.com', 'ara', '$2y$10$bVPF3jFW1CUaBxMMphjifuls1Vqo58iJzy4kx4iCbh42hDrlzbIt6', 'uploads/profile_images/profile_1752744870_40011e02deb1edad.jpg', NULL, NULL, 'User', 'admin', 1);

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
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_school_day_time` (`school_id`,`day_of_week`,`start_time`),
  ADD KEY `idx_room_time` (`room`,`day_of_week`,`start_time`);

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
-- Indexes for table `tbl_user_logs`
--
ALTER TABLE `tbl_user_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `theme_passkeys`
--
ALTER TABLE `theme_passkeys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `used_by` (`used_by`),
  ADD KEY `idx_expires_at` (`expires_at`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `tbl_user_logs`
--
ALTER TABLE `tbl_user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=305;

--
-- AUTO_INCREMENT for table `theme_passkeys`
--
ALTER TABLE `theme_passkeys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

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
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `system_logs_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `theme_passkeys`
--
ALTER TABLE `theme_passkeys`
  ADD CONSTRAINT `theme_passkeys_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `theme_passkeys_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `theme_passkeys_ibfk_3` FOREIGN KEY (`used_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
