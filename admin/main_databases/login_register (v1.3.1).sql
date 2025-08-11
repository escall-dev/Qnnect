-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 11, 2025 at 03:12 PM
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
(1, 'escall', 'uploads/profile_images/profile_1752690336.jpg', '2025-08-11 13:03:44', '2025-07-13 16:43:05', 1),
(13, 'ara', 'uploads/profile_images/profile_1752744870_40011e02deb1edad.jpg', '2025-08-11 13:03:34', '2025-07-13 16:53:40', 1),
(114, 'alex', 'uploads/profile_images/profile_1752694438_4af1d3abb596df2b.jpg', '2025-08-11 11:38:52', '2025-07-16 19:34:13', 2);

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
(84, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 14:24:12'),
(85, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 12:29:19'),
(86, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 12:55:02'),
(87, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:07:26'),
(88, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:15:04'),
(89, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:15:25'),
(90, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:15:51'),
(91, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:18:17'),
(92, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:18:30'),
(93, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 13:22:45'),
(94, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 14:08:11'),
(95, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 14:08:24'),
(96, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 14:08:57'),
(97, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:01:33'),
(98, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:13:34'),
(99, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:17:55'),
(100, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:18:20'),
(101, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:20:29'),
(102, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:23:10'),
(103, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:25:35'),
(104, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:27:35'),
(105, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:36:40'),
(106, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:47:52'),
(107, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:48:14'),
(108, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:49:00'),
(109, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:49:29'),
(110, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 15:57:36'),
(111, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:06:37'),
(112, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:07:51'),
(113, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:15:45'),
(114, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:17:38'),
(115, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:18:18'),
(116, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:20:02'),
(117, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:20:13'),
(118, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:21:59'),
(119, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:32:22'),
(120, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:33:06'),
(121, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:52:52'),
(122, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 16:53:28'),
(123, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:04:52'),
(124, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:08:13'),
(125, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:21:27'),
(126, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:23:58'),
(127, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:38:28'),
(128, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:38:46'),
(129, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:39:09'),
(130, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:42:39'),
(131, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:48:05'),
(132, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:48:41'),
(133, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:48:54'),
(134, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:49:18'),
(135, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:51:18'),
(136, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:53:24'),
(137, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:54:39'),
(138, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 17:54:59'),
(139, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 18:02:03'),
(140, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 18:02:19'),
(141, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 18:03:08'),
(142, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 18:10:37'),
(143, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 18:14:12'),
(144, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:02:50'),
(145, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:07:02'),
(146, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:10:24'),
(147, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:31:28'),
(148, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:31:41'),
(149, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:35:51'),
(150, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:41:24'),
(151, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 12:43:04'),
(152, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 13:14:51'),
(153, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 13:50:48'),
(154, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 15:31:39'),
(155, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 15:51:21'),
(156, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 16:22:55'),
(157, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-31 16:23:29'),
(158, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 02:25:00'),
(159, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 02:32:56'),
(160, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 02:34:12'),
(161, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 03:57:21'),
(162, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 04:39:19'),
(163, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 05:41:51'),
(164, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 05:45:22'),
(165, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 06:25:23'),
(166, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 13:41:40'),
(167, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 14:04:37'),
(168, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:07:44'),
(169, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:11:41'),
(170, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:19:48'),
(171, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:21:48'),
(172, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:22:04'),
(173, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:22:49'),
(174, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:23:04'),
(175, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:34:13'),
(176, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:34:47'),
(177, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:37:35'),
(178, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:52:24'),
(179, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:54:18'),
(180, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-01 17:57:08'),
(181, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 03:45:44'),
(182, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 03:46:03'),
(183, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 03:49:05'),
(184, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:05:26'),
(185, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:05:54'),
(186, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:06:14'),
(187, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:06:30'),
(188, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:06:56'),
(189, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 05:14:48'),
(190, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 06:15:14'),
(191, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 07:23:44'),
(192, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 09:10:56'),
(193, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 10:01:36'),
(194, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 10:25:44'),
(195, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 10:26:54'),
(196, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 10:40:56'),
(197, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:11:00'),
(198, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:14:44'),
(199, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:15:05'),
(200, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:16:48'),
(201, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:24:28'),
(202, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:31:18'),
(203, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:42:50'),
(204, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:45:17'),
(205, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:50:21'),
(206, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 11:58:26'),
(207, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 12:06:41'),
(208, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.102.3 Chrome/134.0.6998.205 Electron/35.6.0 Safari/537.36', '2025-08-02 12:11:00'),
(209, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.102.3 Chrome/134.0.6998.205 Electron/35.6.0 Safari/537.36', '2025-08-02 12:11:09'),
(210, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 12:26:19'),
(211, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 12:27:56'),
(212, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 12:28:13'),
(213, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:18:13'),
(214, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:27:43'),
(215, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:30:04'),
(216, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:30:10'),
(217, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:31:42'),
(218, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:32:09'),
(219, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:36:18'),
(220, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:39:10'),
(221, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:45:02'),
(222, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:54:40'),
(223, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:55:18'),
(224, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 13:56:41'),
(225, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 14:16:44'),
(226, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 14:38:42'),
(227, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 14:54:06'),
(228, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 15:16:10'),
(229, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 15:31:17'),
(230, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 15:56:11'),
(231, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:05:58'),
(232, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:06:54'),
(233, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:10:35'),
(234, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:10:54'),
(235, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:18:52'),
(236, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:22:55'),
(237, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:27:36'),
(238, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:27:57'),
(239, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:28:12'),
(240, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:28:27'),
(241, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 16:39:09'),
(242, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:00:47'),
(243, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:26:19'),
(244, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:27:31'),
(245, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:28:00'),
(246, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:30:05'),
(247, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:34:06'),
(248, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:48:40'),
(249, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:49:24'),
(250, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:50:10'),
(251, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:51:18'),
(252, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:51:49'),
(253, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 17:52:27'),
(254, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:12:42'),
(255, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:20:02'),
(256, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:20:13'),
(257, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:20:56'),
(258, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:27:26'),
(259, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:34:03'),
(260, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:35:54'),
(261, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:39:54'),
(262, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:45:35'),
(263, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:50:02'),
(264, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-02 18:54:35'),
(265, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-09 09:48:57'),
(266, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-09 09:56:51'),
(267, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-09 10:32:20'),
(268, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-09 10:35:08'),
(269, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-09 10:35:40'),
(270, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-10 11:38:35'),
(271, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-10 11:52:35');
INSERT INTO `system_logs` (`id`, `user_id`, `school_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(272, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-10 15:18:42'),
(273, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-10 16:07:33'),
(274, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-10 16:08:10'),
(275, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:03:13'),
(276, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:04:54'),
(277, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:06:50'),
(278, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:07:36'),
(279, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:08:28'),
(280, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:09:24'),
(281, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 03:25:38'),
(282, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 06:02:20'),
(283, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 06:03:07'),
(284, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 06:12:01'),
(285, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 06:12:11'),
(286, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 10:11:54'),
(287, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 10:13:21'),
(288, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 10:30:15'),
(289, 24, 2, 'USER_LOGIN', 'School: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 11:36:31'),
(290, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 11:39:03'),
(291, 25, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 11:40:16'),
(292, 23, 1, 'USER_LOGIN', 'School: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 13:03:44');

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
(1, 'escalliente', 'User', '2025-03-17 20:57:53', '2025-03-17 20:59:20', '::1', 1, 1),
(2, 'escalliente', 'User', '2025-03-17 20:59:33', '2025-03-17 21:00:20', '::1', 1, 1),
(3, 'escalliente', 'User', '2025-03-17 21:01:27', '2025-06-16 18:55:05', '::1', 1, 1),
(4, 'escalliente', 'User', '2025-03-17 21:06:32', '2025-06-16 18:55:05', '::1', 1, 1),
(5, 'escalliente', 'User', '2025-03-17 21:07:00', '2025-06-16 18:55:05', '::1', 1, 1),
(6, 'escalliente', 'User', '2025-03-17 21:07:58', '2025-03-17 21:19:23', '::1', 1, 1),
(7, 'escalliente', 'User', '2025-03-17 21:19:39', '2025-03-17 21:21:15', '::1', 1, 1),
(8, 'escalliente', 'User', '2025-03-17 21:21:29', '2025-03-17 21:23:32', '::1', 1, 1),
(9, 'escalliente', 'User', '2025-03-17 21:24:22', '2025-03-17 21:24:31', '::1', 1, 1),
(10, 'spcpc', 'User', '2025-03-17 21:25:17', '2025-03-17 21:44:59', '::1', 1, 1),
(11, 'escalliente', 'User', '2025-03-17 21:45:12', '2025-03-17 22:09:03', '::1', 1, 1),
(12, 'escalliente', 'User', '2025-03-17 22:09:18', '2025-06-16 18:55:05', '::1', 1, 1),
(13, 'escalliente', 'User', '2025-03-18 08:36:58', '2025-03-18 08:37:52', '::1', 1, 1),
(14, 'escalliente', 'User', '2025-03-18 08:41:51', '2025-06-16 18:55:05', '::1', 1, 1),
(15, 'escalliente', 'User', '2025-03-18 08:56:39', '2025-03-18 08:59:38', '::1', 1, 1),
(16, 'escalliente', 'User', '2025-03-18 08:59:49', '2025-03-18 08:59:57', '::1', 1, 1),
(17, 'escalliente', 'User', '2025-03-20 12:36:53', '2025-06-16 18:55:05', '::1', 1, 1),
(18, 'escalliente', 'User', '2025-03-20 12:51:32', '2025-06-16 18:55:05', '::1', 1, 1),
(19, 'escalliente', 'User', '2025-03-20 19:22:46', '2025-03-20 19:53:09', '::1', 1, 1),
(20, 'escalliente', 'User', '2025-03-20 19:53:22', '2025-06-16 18:55:05', '::1', 1, 1),
(21, 'escalliente', 'User', '2025-03-24 20:16:32', '2025-06-16 18:55:05', '::1', 1, 1),
(22, 'escall', 'User', '2025-03-24 20:25:18', '2025-03-24 20:33:04', '::1', 1, 1),
(23, 'escall', 'User', '2025-03-24 20:33:09', '2025-03-24 20:36:06', '::1', 1, 1),
(24, 'escall', 'User', '2025-03-24 20:37:24', '2025-06-16 18:54:47', '::1', 1, 1),
(25, 'escall', 'User', '2025-03-27 11:52:34', '2025-03-27 11:52:42', '::1', 1, 1),
(26, 'escall', 'User', '2025-03-27 13:11:48', '2025-06-16 18:54:47', '::1', 1, 1),
(27, 'escall', 'User', '2025-03-27 13:15:06', '2025-03-27 13:26:26', '::1', 1, 1),
(28, 'escall', 'User', '2025-03-27 13:26:32', '2025-06-16 18:54:47', '::1', 1, 1),
(29, 'escall', 'User', '2025-03-28 20:12:23', '2025-03-28 20:14:38', '::1', 1, 1),
(30, 'escall', 'User', '2025-03-28 20:14:43', '2025-03-28 20:15:54', '::1', 1, 1),
(31, 'escall', 'User', '2025-04-02 17:49:09', '2025-04-02 18:45:26', '::1', 1, 1),
(32, 'escall', 'User', '2025-04-02 18:45:32', '2025-06-16 18:54:47', '::1', 1, 1),
(33, 'escall', 'User', '2025-04-02 20:12:20', '2025-06-16 18:54:47', '::1', 1, 1),
(34, 'escall', 'User', '2025-04-02 20:55:03', '2025-04-02 20:55:14', '::1', 1, 1),
(35, 'escall', 'User', '2025-04-03 11:04:17', '2025-04-03 11:05:01', '::1', 1, 1),
(36, 'escall', 'User', '2025-04-03 11:24:00', '2025-06-16 18:54:47', '::1', 1, 1),
(37, 'escall', 'User', '2025-04-03 21:14:47', '2025-06-16 18:54:47', '::1', 1, 1),
(38, 'escall', 'User', '2025-04-04 09:34:30', '2025-06-16 18:54:47', '::1', 1, 1),
(39, 'escall', 'User', '2025-04-04 20:50:38', '2025-04-04 20:54:51', '::1', 1, 1),
(40, 'escall', 'User', '2025-04-04 20:55:00', '2025-06-16 18:54:47', '::1', 1, 1),
(41, 'escall', 'User', '2025-04-05 06:50:18', '2025-04-05 06:51:43', '::1', 1, 1),
(42, 'escall', 'User', '2025-04-05 07:01:54', '2025-06-16 18:54:47', '::1', 1, 1),
(43, 'escall', 'User', '2025-04-05 07:58:48', '2025-04-05 08:33:42', '::1', 1, 1),
(44, 'escall', 'User', '2025-04-05 09:28:12', '2025-04-05 10:07:50', '::1', 1, 1),
(45, 'escall', 'User', '2025-04-05 10:38:08', '2025-04-05 11:27:21', '::1', 1, 1),
(46, 'escall', 'User', '2025-04-05 12:56:28', '2025-04-05 12:57:14', '::1', 1, 1),
(47, 'escall', 'User', '2025-04-05 12:57:20', '2025-04-05 13:00:07', '::1', 1, 1),
(48, 'escall', 'User', '2025-04-05 13:00:17', '2025-04-05 14:41:15', '::1', 1, 1),
(49, 'escall', 'User', '2025-04-05 14:41:50', '2025-04-05 14:47:35', '::1', 1, 1),
(50, 'escall', 'User', '2025-04-05 14:47:53', '2025-04-05 14:55:03', '::1', 1, 1),
(51, 'escall', 'User', '2025-04-05 14:55:17', '2025-04-05 15:00:43', '::1', 1, 1),
(52, 'escall', 'User', '2025-04-05 15:00:53', '2025-04-05 15:22:15', '::1', 1, 1),
(53, 'escall', 'User', '2025-04-05 15:22:25', '2025-04-05 16:07:49', '::1', 1, 1),
(54, 'escall', 'User', '2025-04-05 16:08:14', '2025-04-05 16:42:34', '::1', 1, 1),
(55, 'escall', 'User', '2025-04-05 16:42:54', '2025-04-05 17:34:05', '::1', 1, 1),
(56, 'escall', 'User', '2025-04-05 17:34:11', '2025-06-16 18:54:47', '::1', 1, 1),
(57, 'escall', 'User', '2025-04-05 17:45:45', '2025-04-05 17:45:53', '::1', 1, 1),
(58, 'escall', 'User', '2025-04-05 17:45:59', '2025-04-05 18:10:51', '::1', 1, 1),
(59, 'escall', 'User', '2025-04-05 18:11:01', '2025-04-05 18:11:14', '::1', 1, 1),
(60, 'escall', 'User', '2025-04-08 08:21:17', '2025-04-08 08:25:15', '::1', 1, 1),
(61, 'escall', 'User', '2025-04-08 09:21:44', '2025-04-08 10:24:00', '::1', 1, 1),
(62, 'escall', 'User', '2025-04-09 19:08:27', '2025-04-09 20:42:49', '::1', 1, 1),
(63, 'escall', 'User', '2025-04-09 20:43:04', '2025-04-09 21:40:41', '::1', 1, 1),
(64, 'escall', 'User', '2025-04-09 21:40:50', '2025-04-09 21:44:54', '::1', 1, 1),
(65, 'escall', 'User', '2025-04-09 21:45:11', '2025-06-16 18:54:47', '::1', 1, 1),
(66, 'escall', 'User', '2025-04-13 21:55:39', '2025-04-13 21:58:41', '::1', 1, 1),
(67, 'escall', 'User', '2025-04-20 00:20:12', '2025-04-20 00:35:05', '::1', 1, 1),
(68, 'escall', 'User', '2025-04-20 00:35:13', '2025-04-20 00:42:41', '::1', 1, 1),
(69, 'escall', 'User', '2025-04-20 00:42:56', '2025-06-16 18:54:47', '::1', 1, 1),
(70, 'escall', 'User', '2025-04-22 13:57:42', '2025-04-22 14:00:37', '::1', 1, 1),
(71, 'escall', 'User', '2025-04-22 14:01:00', '2025-04-22 14:04:19', '::1', 1, 1),
(72, 'escall', 'User', '2025-04-24 20:16:01', '2025-04-24 21:11:53', '::1', 1, 1),
(73, 'escall', 'User', '2025-04-24 21:11:58', '2025-04-24 21:35:24', '::1', 1, 1),
(74, 'escall', 'User', '2025-04-24 21:40:53', '2025-04-24 23:05:48', '::1', 1, 1),
(75, 'escall', 'User', '2025-04-24 23:05:59', '2025-04-25 02:18:47', '::1', 1, 1),
(76, 'escall', 'User', '2025-04-25 02:19:31', '2025-04-25 03:07:26', '::1', 1, 1),
(77, 'escall', 'User', '2025-04-25 03:08:03', '2025-06-16 18:54:47', '::1', 1, 1),
(78, 'escall', 'User', '2025-04-25 20:28:03', '2025-06-16 18:54:47', '::1', 1, 1),
(79, 'escall', 'User', '2025-05-03 15:25:40', '2025-05-03 15:30:11', '::1', 1, 1),
(80, 'escall', 'User', '2025-05-03 15:45:39', '2025-05-03 15:50:25', '::1', 1, 1),
(81, 'escall', 'User', '2025-05-03 15:50:31', '2025-05-03 15:57:30', '::1', 1, 1),
(82, 'escall', 'User', '2025-05-03 15:57:45', '2025-05-03 16:09:03', '::1', 1, 1),
(83, 'escall', 'User', '2025-05-03 16:09:57', '2025-05-03 16:19:33', '::1', 1, 1),
(84, 'escall', 'User', '2025-05-03 16:20:20', '2025-06-16 18:54:47', '::1', 1, 1),
(85, 'escall', 'User', '2025-05-03 16:37:32', '2025-05-03 22:07:47', '::1', 1, 1),
(86, 'escall', 'User', '2025-05-03 22:08:04', '2025-05-03 22:26:06', '::1', 1, 1),
(87, 'escall', 'User', '2025-05-03 22:26:45', '2025-06-16 18:54:47', '::1', 1, 1),
(88, 'escall', 'User', '2025-05-14 17:28:46', '2025-05-14 17:29:25', '::1', 1, 1),
(89, 'escall', 'User', '2025-05-15 00:17:05', '2025-05-15 00:17:12', '::1', 1, 1),
(90, 'escall', 'User', '2025-05-15 01:38:58', '2025-05-15 01:39:05', '::1', 1, 1),
(91, 'escall', 'User', '2025-05-15 02:22:40', '2025-06-16 18:54:47', '::1', 1, 1),
(92, 'escall', 'User', '2025-05-15 02:23:30', '2025-05-15 02:23:34', '::1', 1, 1),
(93, 'escall', 'User', '2025-05-15 13:26:01', '2025-05-15 13:26:11', '::1', 1, 1),
(94, 'escall', 'User', '2025-05-19 00:33:07', '2025-05-19 00:33:19', '::1', 1, 1),
(95, 'escall', 'User', '2025-06-04 19:58:20', '2025-06-04 20:04:23', '::1', 1, 1),
(96, 'escall', 'User', '2025-06-05 17:01:35', '2025-06-05 17:17:11', '::1', 1, 1),
(97, 'escall', 'User', '2025-06-16 18:14:49', '2025-06-16 18:16:08', '::1', 1, 1),
(98, 'escall', 'User', '2025-06-16 18:16:15', '2025-06-16 18:17:01', '::1', 1, 1),
(99, 'escall', 'User', '2025-06-16 18:17:12', '2025-06-16 18:30:51', '::1', 1, 1),
(100, 'escall', 'User', '2025-06-16 18:30:57', '2025-06-16 18:54:47', '::1', 1, 1),
(101, 'escall', 'User', '2025-06-16 18:47:54', '2025-06-16 18:48:15', '::1', 1, 1),
(102, 'escall', 'User', '2025-06-16 18:48:38', '2025-06-16 18:54:47', '::1', 1, 1),
(103, 'escall', 'User', '2025-06-16 18:48:41', '2025-06-16 18:54:47', '::1', 1, 1),
(104, 'escall', 'User', '2025-06-16 18:50:41', '2025-06-16 18:50:41', '::1', 1, 1),
(105, 'escall', 'User', '2025-06-16 18:50:41', '2025-06-16 18:55:05', '::1', 1, 1),
(106, 'escall', 'User', '2025-06-16 18:55:05', '2025-06-16 19:00:38', '::1', 1, 1),
(107, 'escall', 'User', '2025-06-16 19:01:04', '2025-06-16 19:15:02', '::1', 1, 1),
(108, 'escall', 'User', '2025-06-16 19:01:07', '2025-06-16 19:15:02', '::1', 1, 1),
(109, 'escall', 'User', '2025-06-16 19:06:17', '2025-06-16 19:06:17', '::1', 1, 1),
(110, 'escall', 'User', '2025-06-16 19:06:17', '2025-06-16 19:06:29', '::1', 1, 1),
(111, 'escall', 'User', '2025-06-16 19:06:35', '2025-06-16 19:15:02', '::1', 1, 1),
(112, 'escall', 'User', '2025-06-16 19:06:37', '2025-06-16 19:06:54', '::1', 1, 1),
(113, 'escall', 'User', '2025-06-16 19:07:00', '2025-06-16 19:15:02', '::1', 1, 1),
(114, 'escall', 'User', '2025-06-16 19:07:03', '2025-06-16 19:07:33', '::1', 1, 1),
(115, 'escall', 'User', '2025-06-16 19:07:40', '2025-06-16 19:15:02', '::1', 1, 1),
(116, 'escall', 'User', '2025-06-16 19:07:42', '2025-06-16 19:11:26', '::1', 1, 1),
(117, 'escall', 'User', '2025-06-16 19:11:31', '2025-06-16 19:15:02', '::1', 1, 1),
(118, 'escall', 'User', '2025-06-16 19:11:36', '2025-06-16 19:15:02', '::1', 1, 1),
(119, 'escall', 'User', '2025-06-16 19:15:02', '2025-06-16 19:15:39', '::1', 1, 1),
(120, 'escall', 'User', '2025-06-16 19:15:45', '2025-06-16 19:15:57', '::1', 1, 1),
(121, 'escall', 'User', '2025-06-16 19:15:48', '2025-06-16 19:15:57', '::1', 1, 1),
(122, 'escall', 'User', '2025-06-16 19:15:57', '2025-06-16 19:29:45', '::1', 1, 1),
(123, 'escall', 'User', '2025-06-16 19:29:51', '2025-06-16 22:02:33', '::1', 1, 1),
(124, 'escall', 'User', '2025-06-17 12:30:23', '2025-06-21 13:40:52', '::1', 1, 1),
(125, 'escall', 'User', '2025-06-21 13:40:10', '2025-06-21 13:41:26', '127.0.0.1', 1, 1),
(126, 'ara', 'User', '2025-06-21 13:42:44', '2025-06-21 13:44:51', '127.0.0.1', 1, 1),
(127, 'ara', 'User', '2025-06-21 13:47:02', '2025-06-21 13:47:08', '127.0.0.1', 1, 1),
(128, 'ara', 'User', '2025-06-21 13:47:52', '2025-06-21 13:48:12', '127.0.0.1', 1, 1),
(129, 'ara', 'User', '2025-06-21 15:08:45', '2025-06-21 15:13:38', '127.0.0.1', 1, 1),
(130, 'ara', 'User', '2025-06-21 15:13:43', '2025-06-21 17:48:48', '127.0.0.1', 1, 1),
(131, 'ara', 'User', '2025-06-21 15:14:49', '2025-06-21 15:20:43', '127.0.0.1', 1, 1),
(132, 'escall', 'User', '2025-06-21 15:16:21', '2025-06-21 15:45:28', '::1', 1, 1),
(133, 'ara', 'User', '2025-06-21 15:20:49', '2025-06-21 17:48:48', '127.0.0.1', 1, 1),
(134, 'ara', 'User', '2025-06-21 15:22:44', '2025-06-21 17:48:48', '127.0.0.1', 1, 1),
(135, 'ara', 'User', '2025-06-21 15:24:21', '2025-06-21 17:48:48', '127.0.0.1', 1, 1),
(136, 'ara', 'User', '2025-06-21 15:37:52', '2025-06-21 17:48:48', '127.0.0.1', 1, 1),
(137, 'ara', 'User', '2025-06-21 15:42:37', '2025-06-21 17:48:48', '127.0.0.1', 1, 1),
(138, 'ara', 'User', '2025-06-21 15:46:19', '2025-06-21 17:48:48', '::1', 1, 1),
(139, 'ara', 'User', '2025-06-21 15:46:41', '2025-06-21 17:48:48', '::1', 1, 1),
(140, 'ara', 'User', '2025-06-21 15:47:53', '2025-06-21 17:48:48', '::1', 1, 1),
(141, 'ara', 'User', '2025-06-21 16:37:30', '2025-06-21 17:48:48', '::1', 1, 1),
(142, 'ara', 'User', '2025-06-21 16:50:48', '2025-06-21 16:51:07', '::1', 1, 1),
(143, 'ara', 'User', '2025-06-21 17:04:42', '2025-06-21 17:48:48', '::1', 1, 1),
(144, 'ara', 'User', '2025-06-21 17:05:58', '2025-06-21 17:48:48', '::1', 1, 1),
(145, 'ara', 'User', '2025-06-21 17:10:07', '2025-06-21 17:11:58', '::1', 1, 1),
(146, 'ara', 'User', '2025-06-21 17:12:03', '2025-06-21 18:28:28', '::1', 1, 1),
(147, 'ara', 'User', '2025-06-21 17:13:29', '2025-06-21 17:13:37', '127.0.0.1', 1, 1),
(148, 'ara', 'User', '2025-06-21 18:28:32', '2025-06-21 21:32:57', '::1', 1, 1),
(149, 'ara', 'User', '2025-06-21 20:42:51', '2025-06-21 20:43:47', '127.0.0.1', 1, 1),
(150, 'ara', 'User', '2025-06-21 21:58:44', '2025-06-21 22:38:42', '::1', 1, 1),
(151, 'ara', 'User', '2025-06-21 22:38:46', '2025-06-22 00:10:21', '::1', 1, 1),
(152, 'ara', 'User', '2025-06-22 00:10:36', '2025-06-22 01:38:09', '::1', 1, 1),
(153, 'ara', 'User', '2025-06-22 01:38:13', '2025-06-22 01:38:19', '::1', 1, 1),
(154, 'ara', 'User', '2025-06-22 13:25:21', '2025-06-22 13:49:33', '::1', 1, 1),
(155, 'ara', 'User', '2025-06-22 13:50:00', '2025-06-22 14:30:13', '::1', 1, 1),
(156, 'ara', 'User', '2025-06-23 19:45:31', '2025-06-23 19:52:30', '::1', 1, 1),
(157, 'ara', 'User', '2025-06-23 20:14:36', '2025-06-23 20:31:17', '::1', 1, 1),
(158, 'ara', 'User', '2025-06-23 20:14:42', '2025-06-23 20:14:57', '::1', 1, 1),
(159, 'ara', 'User', '2025-06-23 20:15:02', '2025-06-23 20:15:30', '::1', 1, 1),
(160, 'ara', 'User', '2025-06-23 20:31:13', '2025-06-23 20:31:28', '::1', 1, 1),
(161, 'spcpc', 'User', '2025-06-23 20:33:24', '2025-06-23 20:33:47', '::1', 1, 1),
(162, 'escall', 'User', '2025-06-24 22:27:55', '2025-07-13 22:16:40', '::1', 1, 1),
(163, 'ara', 'User', '2025-06-25 19:39:57', '2025-06-25 19:53:46', '::1', 1, 1),
(164, 'ara', 'User', '2025-06-25 19:53:58', '2025-06-25 19:56:36', '::1', 1, 1),
(165, 'ara', 'User', '2025-07-07 20:57:38', '2025-07-13 22:13:27', '::1', 1, 1),
(166, 'ara', 'User', '2025-07-13 22:13:12', '2025-07-13 22:13:36', '::1', 1, 1),
(167, 'escall', 'User', '2025-07-13 22:16:16', '2025-07-13 22:16:40', '::1', 1, 1),
(168, 'escall', 'User', '2025-07-13 22:16:38', '2025-07-17 02:03:34', '::1', 1, 1),
(169, 'escall', 'User', '2025-07-13 22:18:25', '2025-07-13 22:18:42', '::1', 1, 1),
(170, 'escall', 'User', '2025-07-13 22:19:11', '2025-07-13 22:19:14', '::1', 1, 1),
(171, 'escall', 'User', '2025-07-13 22:32:12', '2025-07-13 22:32:17', '::1', 1, 1),
(172, 'ara', 'User', '2025-07-13 22:32:20', '2025-07-13 22:32:23', '::1', 1, 1),
(173, 'escall', 'User', '2025-07-13 22:32:29', '2025-07-13 22:32:45', '::1', 1, 1),
(174, 'escall', 'User', '2025-07-13 22:32:50', '2025-07-13 22:32:54', '::1', 1, 1),
(175, 'escall', 'User', '2025-07-13 22:33:16', '2025-07-13 22:33:25', '::1', 1, 1),
(176, 'escall', 'User', '2025-07-13 23:02:37', '2025-07-13 23:02:40', '::1', 1, 1),
(177, 'escall', 'User', '2025-07-14 00:26:30', '2025-07-14 00:28:51', '::1', 1, 1),
(178, 'escall', 'User', '2025-07-14 00:43:05', '2025-07-17 02:03:34', '::1', 1, 1),
(179, 'escall', 'User', '2025-07-14 00:43:15', '2025-07-17 02:03:34', '::1', 1, 1),
(180, 'escall', 'User', '2025-07-14 00:50:04', '2025-07-17 02:03:34', '::1', 1, 1),
(181, 'ara', 'User', '2025-07-14 00:53:40', '2025-08-03 01:30:13', '::1', 1, 1),
(182, 'escall', 'User', '2025-07-14 01:02:08', '2025-07-17 02:03:34', '::1', 1, 1),
(183, 'ara', 'User', '2025-07-14 01:02:46', '2025-08-03 01:30:13', '::1', 1, 1),
(184, 'escall', 'User', '2025-07-14 01:04:39', '2025-07-17 02:03:34', '::1', 1, 1),
(185, 'escall', 'User', '2025-07-14 01:05:04', '2025-07-17 02:03:34', '::1', 1, 1),
(186, 'escall', 'User', '2025-07-14 01:08:22', '2025-07-17 02:03:34', '::1', 1, 1),
(187, 'ara', 'User', '2025-07-14 01:09:10', '2025-08-03 01:30:13', '::1', 1, 1),
(188, 'escall', 'User', '2025-07-14 01:09:32', '2025-07-17 02:03:34', '::1', 1, 1),
(189, 'ara', 'User', '2025-07-14 01:09:41', '2025-08-03 01:30:13', '::1', 1, 1),
(190, 'escall', 'User', '2025-07-14 01:24:14', '2025-07-17 02:03:34', '::1', 1, 1),
(191, 'ara', 'User', '2025-07-14 01:39:00', '2025-08-03 01:30:13', '::1', 1, 1),
(192, 'ara', 'User', '2025-07-14 01:46:14', '2025-08-03 01:30:13', '::1', 1, 1),
(193, 'ara', 'User', '2025-07-14 01:46:26', '2025-08-03 01:30:13', '::1', 1, 1),
(194, 'ara', 'User', '2025-07-14 01:50:34', '2025-08-03 01:30:13', '::1', 1, 1),
(195, 'escall', 'User', '2025-07-14 01:51:51', '2025-07-17 02:03:34', '::1', 1, 1),
(196, 'ara', 'User', '2025-07-14 01:53:49', '2025-08-03 01:30:13', '::1', 1, 1),
(197, 'ara', 'User', '2025-07-14 02:01:34', '2025-08-03 01:30:13', '::1', 1, 1),
(198, 'ara', 'User', '2025-07-14 02:01:48', '2025-08-03 01:30:13', '::1', 1, 1),
(199, 'ara', 'User', '2025-07-14 02:40:18', '2025-08-03 01:30:13', '::1', 1, 1),
(200, 'ara', 'User', '2025-07-14 02:40:37', '2025-08-03 01:30:13', '::1', 1, 1),
(201, 'escall', 'User', '2025-07-14 10:55:08', '2025-07-17 02:03:34', '::1', 1, 1),
(202, 'ara', 'User', '2025-07-14 10:55:18', '2025-07-14 11:00:12', '::1', 1, 1),
(203, 'ara', 'User', '2025-07-14 11:22:04', '2025-08-03 01:30:13', '::1', 1, 1),
(204, 'escall', 'User', '2025-07-14 11:24:52', '2025-07-14 11:29:14', '::1', 1, 1),
(205, 'ara', 'User', '2025-07-14 11:32:14', '2025-08-03 01:30:13', '::1', 1, 1),
(206, 'ara', 'User', '2025-07-14 11:54:25', '2025-07-14 11:56:40', '::1', 1, 1),
(207, 'ara', 'User', '2025-07-14 14:05:10', '2025-08-03 01:30:13', '::1', 1, 1),
(208, 'ara', 'User', '2025-07-14 14:33:51', '2025-08-03 01:30:13', '::1', 1, 1),
(209, 'ara', 'User', '2025-07-17 00:44:51', '2025-08-03 01:30:13', '::1', 1, 1),
(210, 'escall', 'admin', '2025-07-17 00:52:42', '2025-07-17 02:03:34', '::1', 1, 1),
(211, 'escall', 'admin', '2025-07-17 00:54:00', '2025-07-17 02:03:34', '::1', 1, 1),
(212, 'escall', 'admin', '2025-07-17 00:56:32', '2025-07-17 02:03:34', '::1', 1, 1),
(213, 'escall', 'admin', '2025-07-17 01:06:29', '2025-07-17 02:03:34', '::1', 1, 1),
(214, 'escall', 'User', '2025-07-17 01:06:45', '2025-07-17 02:03:34', '::1', 1, 1),
(215, 'escall', 'admin', '2025-07-17 02:01:46', '2025-07-17 02:03:34', '::1', 1, 1),
(216, 'escall', 'User', '2025-07-17 02:02:46', '2025-07-17 03:25:51', '::1', 1, 1),
(217, 'escall', 'User', '2025-07-17 02:04:22', '2025-07-17 03:25:51', '::1', 1, 1),
(218, 'escall', 'admin', '2025-07-17 02:20:39', '2025-07-17 03:25:51', '::1', 1, 1),
(219, 'escall', 'admin', '2025-07-17 02:23:05', '2025-07-17 03:25:51', '::1', 1, 1),
(220, 'escall', 'admin', '2025-07-17 02:25:52', '2025-07-17 03:25:51', '::1', 1, 1),
(221, 'escall', 'admin', '2025-07-17 03:20:03', '2025-07-17 03:25:51', '::1', 1, 1),
(222, 'escall', 'admin', '2025-07-17 03:20:40', '2025-07-17 03:25:51', '::1', 1, 1),
(223, 'escall', 'admin', '2025-07-17 03:22:17', '2025-07-17 12:26:22', '::1', 1, 1),
(224, 'alex', 'admin', '2025-07-17 03:34:13', '2025-07-17 16:36:26', '::1', 1, 1),
(225, 'escall', 'admin', '2025-07-17 03:38:11', '2025-07-17 12:26:22', '::1', 1, 1),
(226, 'alex', 'admin', '2025-07-17 03:39:17', '2025-07-17 16:36:26', '::1', 1, 1),
(227, 'escall', 'admin', '2025-07-17 12:25:02', '2025-07-17 14:14:25', '::1', 1, 1),
(228, 'escall', 'admin', '2025-07-17 14:14:23', '2025-07-17 19:31:45', '::1', 1, 1),
(229, 'alex', 'admin', '2025-07-17 14:14:38', '2025-07-17 16:36:26', '::1', 1, 1),
(230, 'escall', 'admin', '2025-07-17 14:15:26', '2025-07-17 19:31:45', '::1', 1, 1),
(231, 'alex', 'admin', '2025-07-17 16:05:41', '2025-07-17 16:36:26', '::1', 1, 1),
(232, 'escall', 'admin', '2025-07-17 16:06:09', '2025-07-17 19:31:45', '::1', 1, 1),
(233, 'alex', 'admin', '2025-07-17 16:08:13', '2025-07-17 16:36:26', '::1', 1, 1),
(234, 'alex', 'admin', '2025-07-17 16:15:09', '2025-07-17 16:36:26', '::1', 1, 1),
(235, 'escall', 'admin', '2025-07-17 16:15:27', '2025-07-17 19:31:45', '::1', 1, 1),
(236, 'alex', 'admin', '2025-07-17 16:15:46', '2025-07-17 16:36:26', '::1', 1, 1),
(237, 'alex', 'admin', '2025-07-17 16:23:28', '2025-07-17 16:36:26', '::1', 1, 1),
(238, 'alex', 'admin', '2025-07-17 16:35:21', '2025-07-24 21:37:27', '::1', 1, 1),
(239, 'alex', 'User', '2025-07-17 16:39:49', '2025-07-24 21:37:27', '::1', 1, 1),
(240, 'alex', 'User', '2025-07-17 17:40:25', '2025-07-24 21:37:27', '::1', 1, 1),
(241, 'escall', 'admin', '2025-07-17 18:11:55', '2025-07-17 19:31:45', '::1', 1, 1),
(242, 'alex', 'admin', '2025-07-17 18:13:02', '2025-07-24 21:37:27', '::1', 1, 1),
(243, 'escall', 'admin', '2025-07-17 18:13:55', '2025-07-17 19:31:45', '::1', 1, 1),
(244, 'escall', 'User', '2025-07-17 18:34:00', '2025-07-17 18:34:44', '::1', 1, 1),
(245, 'alex', 'admin', '2025-07-17 18:37:48', '2025-07-24 21:37:27', '::1', 1, 1),
(246, 'escall', 'admin', '2025-07-17 18:38:34', '2025-07-17 19:31:45', '::1', 1, 1),
(247, 'escall', 'User', '2025-07-17 18:38:58', '2025-07-17 21:51:05', '::1', 1, 1),
(248, 'escall', 'admin', '2025-07-17 19:31:37', '2025-07-20 18:36:09', '::1', 1, 1),
(249, 'escall', 'admin', '2025-07-17 20:27:22', '2025-07-20 18:36:09', '::1', 1, 1),
(250, 'escall', 'admin', '2025-07-17 21:34:41', '2025-07-20 18:36:09', '::1', 1, 1),
(251, 'ara', 'admin', '2025-07-18 12:52:46', '2025-08-03 01:30:13', '::1', 1, 1),
(252, 'ara', 'admin', '2025-07-18 12:54:16', '2025-08-03 01:30:13', '::1', 1, 1),
(253, 'ara', 'admin', '2025-07-20 18:25:17', '2025-08-03 01:30:13', '::1', 1, 1),
(254, 'escall', 'admin', '2025-07-20 18:36:00', '2025-07-20 19:35:56', '::1', 1, 1),
(255, 'escall', 'admin', '2025-07-20 19:34:56', '2025-07-24 16:33:10', '::1', 1, 1),
(256, 'escall', 'admin', '2025-07-20 21:51:32', '2025-07-24 16:33:10', '::1', 1, 1),
(257, 'ara', 'admin', '2025-07-21 10:06:39', '2025-08-03 01:30:13', '::1', 1, 1),
(258, 'alex', 'admin', '2025-07-21 10:06:56', '2025-07-24 21:37:27', '::1', 1, 1),
(259, 'escall', 'admin', '2025-07-21 10:08:06', '2025-07-24 16:33:10', '::1', 1, 1),
(260, 'escall', 'User', '2025-07-21 10:08:37', '2025-07-21 11:34:17', '::1', 1, 1),
(261, 'escall', 'User', '2025-07-21 11:34:37', '2025-07-21 11:34:40', '::1', 1, 1),
(262, 'escall', 'admin', '2025-07-24 16:13:06', '2025-07-24 16:33:10', '::1', 1, 1),
(263, 'escall', 'admin', '2025-07-24 16:28:50', '2025-07-27 15:20:36', '::1', 1, 1),
(264, 'escall', 'admin', '2025-07-24 17:29:00', '2025-07-27 15:20:36', '::1', 1, 1),
(265, 'escall', 'admin', '2025-07-24 17:30:36', '2025-07-27 15:20:36', '::1', 1, 1),
(266, 'escall', 'admin', '2025-07-24 17:32:54', '2025-07-27 15:20:36', '::1', 1, 1),
(267, 'escall', 'admin', '2025-07-24 17:33:03', '2025-07-27 15:20:36', '::1', 1, 1),
(268, 'escall', 'admin', '2025-07-24 17:35:04', '2025-07-27 15:20:36', '::1', 1, 1),
(269, 'escall', 'admin', '2025-07-24 17:41:44', '2025-07-27 15:20:36', '::1', 1, 1),
(270, 'alex', 'admin', '2025-07-24 17:42:25', '2025-07-24 21:37:27', '::1', 1, 1),
(271, 'escall', 'admin', '2025-07-24 17:48:38', '2025-07-27 15:20:36', '::1', 1, 1),
(272, 'escall', 'admin', '2025-07-24 17:51:57', '2025-07-27 15:20:36', '::1', 1, 1),
(273, 'escall', 'admin', '2025-07-24 18:03:33', '2025-07-27 15:20:36', '::1', 1, 1),
(274, 'escall', 'User', '2025-07-24 18:13:13', '2025-07-27 15:20:36', '::1', 1, 1),
(275, 'escall', 'admin', '2025-07-24 19:18:05', '2025-07-27 15:20:36', '::1', 1, 1),
(276, 'escall', 'admin', '2025-07-24 19:21:47', '2025-07-27 15:20:36', '::1', 1, 1),
(277, 'alex', 'admin', '2025-07-24 21:29:07', '2025-07-24 21:37:27', '::1', 1, 1),
(278, 'escall', 'admin', '2025-07-24 21:35:13', '2025-07-27 15:20:36', '::1', 1, 1),
(279, 'alex', 'admin', '2025-07-24 21:37:18', '2025-07-24 22:58:12', '::1', 1, 1),
(280, 'escall', 'admin', '2025-07-24 21:40:58', '2025-07-27 15:20:36', '::1', 1, 1),
(281, 'alex', 'admin', '2025-07-24 22:37:29', '2025-07-30 21:17:11', '::1', 1, 1),
(282, 'escall', 'admin', '2025-07-25 00:47:40', '2025-07-27 15:20:36', '::1', 1, 1),
(283, 'escall', 'admin', '2025-07-25 02:03:26', '2025-07-27 15:20:36', '::1', 1, 1),
(284, 'escall', 'admin', '2025-07-25 02:17:26', '2025-07-27 15:20:36', '::1', 1, 1),
(285, 'alex', 'admin', '2025-07-25 02:17:40', '2025-07-30 21:17:11', '::1', 1, 1),
(286, 'escall', 'admin', '2025-07-27 15:02:55', '2025-07-27 15:20:36', '::1', 1, 1),
(287, 'escall', 'admin', '2025-07-27 15:20:26', '2025-07-27 15:29:08', '::1', 1, 1),
(288, 'alex', 'admin', '2025-07-27 15:21:32', '2025-07-30 21:17:11', '::1', 1, 1),
(289, 'escall', 'admin', '2025-07-27 15:22:09', '2025-07-27 15:29:08', '::1', 1, 1),
(290, 'escall', 'User', '2025-07-27 15:27:48', '2025-07-27 15:29:11', '::1', 1, 1),
(291, 'alex', 'admin', '2025-07-27 15:28:58', '2025-07-30 21:17:11', '::1', 1, 1),
(292, 'escall', 'admin', '2025-07-27 15:43:19', '2025-07-27 16:31:20', '::1', 1, 1),
(293, 'escall', 'admin', '2025-07-27 15:47:17', '2025-07-27 16:31:20', '::1', 1, 1),
(294, 'escall', 'admin', '2025-07-27 16:30:39', '2025-07-27 17:41:03', '::1', 1, 1),
(295, 'alex', 'admin', '2025-07-27 17:28:33', '2025-07-30 21:17:11', '::1', 1, 1),
(296, 'escall', 'admin', '2025-07-27 17:28:47', '2025-07-27 17:41:03', '::1', 1, 1),
(297, 'alex', 'admin', '2025-07-27 17:30:28', '2025-07-30 21:17:11', '::1', 1, 1),
(298, 'ara', 'admin', '2025-07-27 17:31:37', '2025-08-03 01:30:13', '::1', 1, 1),
(299, 'escall', 'admin', '2025-07-27 17:31:55', '2025-07-31 00:53:05', '::1', 1, 1),
(300, 'alex', 'admin', '2025-07-27 18:11:18', '2025-07-30 21:17:11', '::1', 1, 1),
(301, 'escall', 'admin', '2025-07-27 22:02:22', '2025-07-31 00:53:05', '::1', 1, 1),
(302, 'escall', 'admin', '2025-07-27 22:02:35', '2025-07-31 00:53:05', '::1', 1, 1),
(303, 'escall', 'admin', '2025-07-27 22:06:59', '2025-07-31 00:53:05', '::1', 1, 1),
(304, 'escall', 'admin', '2025-07-27 22:24:12', '2025-07-31 00:53:05', '::1', 1, 1),
(305, 'alex', 'admin', '2025-07-30 20:29:19', '2025-07-30 21:17:11', '::1', 1, 1),
(306, 'escall', 'User', '2025-07-30 20:33:42', '2025-07-30 20:39:22', '::1', 1, 1),
(307, 'escall', 'admin', '2025-07-30 20:55:02', '2025-07-31 00:53:05', '::1', 1, 1),
(308, 'alex', 'admin', '2025-07-30 21:07:26', '2025-07-30 21:17:11', '::1', 1, 1),
(309, 'alex', 'admin', '2025-07-30 21:15:04', '2025-07-30 21:17:11', '::1', 1, 1),
(310, 'escall', 'admin', '2025-07-30 21:15:24', '2025-07-31 00:53:05', '::1', 1, 1),
(311, 'alex', 'admin', '2025-07-30 21:15:51', '2025-07-30 23:05:55', '::1', 1, 1),
(312, 'alex', 'admin', '2025-07-30 21:18:17', '2025-07-30 23:05:55', '::1', 1, 1),
(313, 'escall', 'admin', '2025-07-30 21:18:30', '2025-07-31 00:53:05', '::1', 1, 1),
(314, 'escall', 'admin', '2025-07-30 21:22:45', '2025-07-31 00:53:05', '::1', 1, 1),
(315, 'escall', 'User', '2025-07-30 21:59:58', '2025-07-30 22:07:48', '::1', 1, 1),
(316, 'escall', 'admin', '2025-07-30 22:08:11', '2025-07-31 00:53:05', '::1', 1, 1),
(317, 'alex', 'admin', '2025-07-30 22:08:24', '2025-07-30 23:05:55', '::1', 1, 1),
(318, 'ara', 'admin', '2025-07-30 22:08:57', '2025-08-03 01:30:13', '::1', 1, 1),
(319, 'alex', 'admin', '2025-07-30 23:01:33', '2025-07-31 00:53:30', '::1', 1, 1),
(320, 'escall', 'admin', '2025-07-30 23:13:34', '2025-07-31 00:53:05', '::1', 1, 1),
(321, 'escall', 'admin', '2025-07-30 23:17:55', '2025-07-31 00:53:05', '::1', 1, 1),
(322, 'escall', 'admin', '2025-07-30 23:18:20', '2025-07-31 00:53:05', '::1', 1, 1),
(323, 'escall', 'admin', '2025-07-30 23:20:29', '2025-07-31 00:53:05', '::1', 1, 1),
(324, 'escall', 'admin', '2025-07-30 23:23:10', '2025-07-31 00:53:05', '::1', 1, 1),
(325, 'escall', 'admin', '2025-07-30 23:25:35', '2025-07-31 00:53:05', '::1', 1, 1),
(326, 'escall', 'admin', '2025-07-30 23:27:35', '2025-07-31 00:53:05', '::1', 1, 1),
(327, 'escall', 'admin', '2025-07-30 23:36:40', '2025-07-31 00:53:05', '::1', 1, 1),
(328, 'alex', 'admin', '2025-07-30 23:47:52', '2025-07-31 00:53:30', '::1', 1, 1),
(329, 'escall', 'admin', '2025-07-30 23:48:14', '2025-07-31 00:53:05', '::1', 1, 1),
(330, 'alex', 'admin', '2025-07-30 23:49:00', '2025-07-31 00:53:30', '::1', 1, 1),
(331, 'ara', 'admin', '2025-07-30 23:49:29', '2025-08-03 01:30:13', '::1', 1, 1),
(332, 'escall', 'admin', '2025-07-30 23:57:36', '2025-07-31 00:53:05', '::1', 1, 1),
(333, 'alex', 'admin', '2025-07-31 00:06:37', '2025-07-31 00:53:30', '::1', 1, 1),
(334, 'ara', 'admin', '2025-07-31 00:07:51', '2025-08-03 01:30:13', '::1', 1, 1),
(335, 'alex', 'admin', '2025-07-31 00:15:45', '2025-07-31 00:53:30', '::1', 1, 1),
(336, 'escall', 'admin', '2025-07-31 00:17:38', '2025-07-31 00:53:05', '::1', 1, 1),
(337, 'ara', 'admin', '2025-07-31 00:18:18', '2025-08-03 01:30:13', '::1', 1, 1),
(338, 'escall', 'admin', '2025-07-31 00:20:02', '2025-07-31 00:53:05', '::1', 1, 1),
(339, 'alex', 'admin', '2025-07-31 00:20:13', '2025-07-31 00:53:30', '::1', 1, 1),
(340, 'ara', 'admin', '2025-07-31 00:21:59', '2025-08-03 01:30:13', '::1', 1, 1),
(341, 'escall', 'admin', '2025-07-31 00:32:21', '2025-07-31 00:53:05', '::1', 1, 1),
(342, 'alex', 'admin', '2025-07-31 00:33:06', '2025-07-31 00:53:30', '::1', 1, 1),
(343, 'escall', 'admin', '2025-07-31 00:52:52', '2025-07-31 22:37:21', '::1', 1, 1),
(344, 'alex', 'admin', '2025-07-31 00:53:28', '2025-07-31 01:13:02', '::1', 1, 1),
(345, 'escall', 'admin', '2025-07-31 01:04:52', '2025-07-31 22:37:21', '::1', 1, 1),
(346, 'alex', 'admin', '2025-07-31 01:08:13', '2025-07-31 23:48:20', '::1', 1, 1),
(347, 'escall', 'admin', '2025-07-31 01:21:27', '2025-07-31 22:37:21', '::1', 1, 1),
(348, 'alex', 'admin', '2025-07-31 01:23:58', '2025-07-31 23:48:20', '::1', 1, 1),
(349, 'escall', 'admin', '2025-07-31 01:38:28', '2025-07-31 22:37:21', '::1', 1, 1),
(350, 'alex', 'admin', '2025-07-31 01:38:46', '2025-07-31 23:48:20', '::1', 1, 1),
(351, 'ara', 'admin', '2025-07-31 01:39:09', '2025-08-03 01:30:13', '::1', 1, 1),
(352, 'alex', 'admin', '2025-07-31 01:42:39', '2025-07-31 23:48:20', '::1', 1, 1),
(353, 'escall', 'admin', '2025-07-31 01:48:05', '2025-07-31 22:37:21', '::1', 1, 1),
(354, 'alex', 'admin', '2025-07-31 01:48:41', '2025-07-31 23:48:20', '::1', 1, 1),
(355, 'escall', 'admin', '2025-07-31 01:48:54', '2025-07-31 22:37:21', '::1', 1, 1),
(356, 'alex', 'admin', '2025-07-31 01:49:18', '2025-07-31 23:48:20', '::1', 1, 1),
(357, 'escall', 'admin', '2025-07-31 01:51:18', '2025-07-31 22:37:21', '::1', 1, 1),
(358, 'ara', 'admin', '2025-07-31 01:53:24', '2025-08-03 01:30:13', '::1', 1, 1),
(359, 'escall', 'admin', '2025-07-31 01:54:39', '2025-07-31 22:37:21', '::1', 1, 1),
(360, 'alex', 'admin', '2025-07-31 01:54:59', '2025-07-31 23:48:20', '::1', 1, 1),
(361, 'escall', 'admin', '2025-07-31 02:02:03', '2025-07-31 22:37:21', '::1', 1, 1),
(362, 'ara', 'admin', '2025-07-31 02:02:19', '2025-08-03 01:30:13', '::1', 1, 1),
(363, 'ara', 'admin', '2025-07-31 02:03:08', '2025-08-03 01:30:13', '::1', 1, 1),
(364, 'alex', 'admin', '2025-07-31 02:10:37', '2025-07-31 23:48:20', '::1', 1, 1),
(365, 'alex', 'admin', '2025-07-31 02:14:12', '2025-07-31 23:48:20', '::1', 1, 1),
(366, 'alex', 'admin', '2025-07-31 20:02:50', '2025-07-31 23:48:20', '::1', 1, 1),
(367, 'alex', 'admin', '2025-07-31 20:07:02', '2025-07-31 23:48:20', '::1', 1, 1),
(368, 'ara', 'admin', '2025-07-31 20:10:24', '2025-08-03 01:30:13', '::1', 1, 1),
(369, 'alex', 'admin', '2025-07-31 20:31:28', '2025-07-31 23:48:20', '::1', 1, 1),
(370, 'escall', 'admin', '2025-07-31 20:31:41', '2025-07-31 22:37:21', '::1', 1, 1),
(371, 'alex', 'admin', '2025-07-31 20:35:51', '2025-07-31 23:48:20', '::1', 1, 1),
(372, 'alex', 'admin', '2025-07-31 20:41:24', '2025-07-31 23:48:20', '::1', 1, 1),
(373, 'escall', 'admin', '2025-07-31 20:43:04', '2025-07-31 22:37:21', '::1', 1, 1),
(374, 'alex', 'admin', '2025-07-31 21:14:51', '2025-07-31 23:48:20', '::1', 1, 1),
(375, 'escall', 'admin', '2025-07-31 21:50:48', '2025-07-31 23:53:46', '::1', 1, 1),
(376, 'escall', 'User', '2025-07-31 23:25:16', '2025-07-31 23:25:57', '::1', 1, 1),
(377, 'alex', 'admin', '2025-07-31 23:31:39', '2025-08-01 00:31:39', '::1', 1, 1),
(378, 'escall', 'User', '2025-07-31 23:32:24', '2025-07-31 23:53:46', '::1', 1, 1),
(379, 'escall', 'User', '2025-07-31 23:50:06', '2025-07-31 23:50:57', '::1', 1, 1),
(380, 'alex', 'admin', '2025-07-31 23:51:21', '2025-08-01 00:31:39', '::1', 1, 1),
(381, 'escall', 'User', '2025-07-31 23:53:03', '2025-07-31 23:53:46', '::1', 1, 1),
(382, 'escall', 'User', '2025-07-31 23:53:25', '2025-08-02 12:04:04', '::1', 1, 1),
(383, 'escall', 'admin', '2025-08-01 00:22:55', '2025-08-02 12:04:04', '::1', 1, 1),
(384, 'alex', 'admin', '2025-08-01 00:23:29', '2025-08-01 21:53:48', '::1', 1, 1),
(385, 'alex', 'admin', '2025-08-01 10:25:00', '2025-08-01 21:53:48', '::1', 1, 1),
(386, 'escall', 'admin', '2025-08-01 10:32:56', '2025-08-02 12:04:04', '::1', 1, 1),
(387, 'alex', 'admin', '2025-08-01 10:34:12', '2025-08-01 21:53:48', '::1', 1, 1),
(388, 'alex', 'admin', '2025-08-01 11:57:21', '2025-08-01 21:53:48', '::1', 1, 1),
(389, 'alex', 'admin', '2025-08-01 12:39:19', '2025-08-01 21:53:48', '::1', 1, 1),
(390, 'alex', 'admin', '2025-08-01 13:41:51', '2025-08-01 21:53:48', '::1', 1, 1),
(391, 'alex', 'admin', '2025-08-01 13:45:22', '2025-08-01 21:53:48', '::1', 1, 1),
(392, 'alex', 'admin', '2025-08-01 14:25:23', '2025-08-01 21:53:48', '::1', 1, 1),
(393, 'alex', 'admin', '2025-08-01 21:41:40', '2025-08-02 13:14:31', '::1', 1, 1),
(394, 'escall', 'admin', '2025-08-01 22:04:37', '2025-08-02 12:04:04', '::1', 1, 1),
(395, 'alex', 'admin', '2025-08-02 01:07:44', '2025-08-02 13:14:31', '::1', 1, 1),
(396, 'escall', 'admin', '2025-08-02 01:11:41', '2025-08-02 12:04:04', '::1', 1, 1),
(397, 'alex', 'admin', '2025-08-02 01:19:48', '2025-08-02 13:14:31', '::1', 1, 1),
(398, 'ara', 'admin', '2025-08-02 01:21:48', '2025-08-03 01:30:13', '::1', 1, 1),
(399, 'alex', 'admin', '2025-08-02 01:22:04', '2025-08-02 13:14:31', '::1', 1, 1),
(400, 'ara', 'admin', '2025-08-02 01:22:49', '2025-08-03 01:30:13', '::1', 1, 1),
(401, 'alex', 'admin', '2025-08-02 01:23:04', '2025-08-02 13:14:31', '::1', 1, 1),
(402, 'ara', 'admin', '2025-08-02 01:34:13', '2025-08-03 01:30:13', '::1', 1, 1),
(403, 'alex', 'admin', '2025-08-02 01:34:47', '2025-08-02 13:14:31', '::1', 1, 1),
(404, 'escall', 'admin', '2025-08-02 01:37:35', '2025-08-02 12:04:04', '::1', 1, 1),
(405, 'alex', 'admin', '2025-08-02 01:52:24', '2025-08-02 13:14:31', '::1', 1, 1),
(406, 'escall', 'admin', '2025-08-02 01:54:18', '2025-08-02 12:04:04', '::1', 1, 1),
(407, 'escall', 'admin', '2025-08-02 01:57:08', '2025-08-02 12:04:04', '::1', 1, 1),
(408, 'escall', 'User', '2025-08-02 01:58:42', '2025-08-02 12:04:04', '::1', 1, 1),
(409, 'alex', 'admin', '2025-08-02 11:45:44', '2025-08-02 13:14:31', '::1', 1, 1),
(410, 'escall', 'admin', '2025-08-02 11:46:03', '2025-08-02 12:04:04', '::1', 1, 1),
(411, 'escall', 'admin', '2025-08-02 11:49:05', '2025-08-02 15:35:37', '::1', 1, 1),
(412, 'alex', 'admin', '2025-08-02 13:05:26', '2025-08-02 13:14:31', '::1', 1, 1),
(413, 'escall', 'admin', '2025-08-02 13:05:54', '2025-08-02 15:35:37', '::1', 1, 1),
(414, 'alex', 'admin', '2025-08-02 13:06:14', '2025-08-02 13:14:31', '::1', 1, 1),
(415, 'ara', 'admin', '2025-08-02 13:06:30', '2025-08-03 01:30:13', '::1', 1, 1),
(416, 'alex', 'admin', '2025-08-02 13:06:56', '2025-08-02 15:22:04', '::1', 1, 1),
(417, 'ara', 'admin', '2025-08-02 13:14:48', '2025-08-03 01:30:13', '::1', 1, 1),
(418, 'alex', 'admin', '2025-08-02 14:15:14', '2025-08-02 19:46:08', '::1', 1, 1),
(419, 'escall', 'admin', '2025-08-02 15:23:44', '2025-08-02 21:54:42', '::1', 1, 1),
(420, 'escall', 'User', '2025-08-02 16:11:34', '2025-08-02 16:12:46', '::1', 1, 1),
(421, 'alex', 'admin', '2025-08-02 17:10:56', '2025-08-02 19:46:08', '::1', 1, 1),
(422, 'alex', 'admin', '2025-08-02 18:01:36', '2025-08-02 19:46:08', '::1', 1, 1),
(423, 'alex', 'admin', '2025-08-02 18:25:44', '2025-08-02 19:46:08', '::1', 1, 1),
(424, 'escall', 'admin', '2025-08-02 18:26:54', '2025-08-02 21:54:42', '::1', 1, 1),
(425, 'alex', 'admin', '2025-08-02 18:40:56', '2025-08-02 19:46:08', '::1', 1, 1),
(426, 'alex', 'admin', '2025-08-02 19:11:00', '2025-08-02 19:46:08', '::1', 1, 1),
(427, 'alex', 'admin', '2025-08-02 19:14:44', '2025-08-02 19:46:08', '::1', 1, 1),
(428, 'alex', 'admin', '2025-08-02 19:15:05', '2025-08-02 19:46:08', '::1', 1, 1),
(429, 'alex', 'admin', '2025-08-02 19:16:48', '2025-08-02 19:46:08', '::1', 1, 1),
(430, 'escall', 'User', '2025-08-02 19:18:03', '2025-08-02 19:19:20', '::1', 1, 1),
(431, 'alex', 'admin', '2025-08-02 19:24:28', '2025-08-02 19:46:08', '::1', 1, 1),
(432, 'alex', 'admin', '2025-08-02 19:31:18', '2025-08-02 19:46:08', '::1', 1, 1),
(433, 'alex', 'admin', '2025-08-02 19:42:50', '2025-08-02 19:46:08', '::1', 1, 1),
(434, 'alex', 'admin', '2025-08-02 19:45:17', '2025-08-02 19:50:48', '::1', 1, 1),
(435, 'alex', 'admin', '2025-08-02 19:50:21', '2025-08-02 21:18:23', '::1', 1, 1),
(436, 'alex', 'admin', '2025-08-02 19:58:26', '2025-08-02 21:18:23', '::1', 1, 1),
(437, 'alex', 'admin', '2025-08-02 20:06:41', '2025-08-02 21:18:23', '::1', 1, 1),
(438, 'alex', 'admin', '2025-08-02 20:11:00', '2025-08-02 21:18:23', '::1', 1, 1),
(439, 'alex', 'admin', '2025-08-02 20:11:09', '2025-08-02 21:18:23', '::1', 1, 1),
(440, 'alex', 'admin', '2025-08-02 20:26:19', '2025-08-02 21:18:23', '::1', 1, 1),
(441, 'escall', 'admin', '2025-08-02 20:27:56', '2025-08-02 21:54:42', '::1', 1, 1),
(442, 'alex', 'admin', '2025-08-02 20:28:13', '2025-08-02 21:18:23', '::1', 1, 1),
(443, 'alex', 'admin', '2025-08-02 21:18:13', '2025-08-02 21:30:45', '::1', 1, 1),
(444, 'alex', 'admin', '2025-08-02 21:27:43', '2025-08-02 21:30:45', '::1', 1, 1),
(445, 'alex', 'admin', '2025-08-02 21:30:04', '2025-08-02 21:30:45', '::1', 1, 1),
(446, 'alex', 'admin', '2025-08-02 21:30:10', '2025-08-03 00:38:56', '::1', 1, 1),
(447, 'escall', 'admin', '2025-08-02 21:31:42', '2025-08-02 21:54:42', '::1', 1, 1),
(448, 'alex', 'admin', '2025-08-02 21:32:09', '2025-08-03 00:38:56', '::1', 1, 1),
(449, 'alex', 'admin', '2025-08-02 21:36:18', '2025-08-03 00:38:56', '::1', 1, 1),
(450, 'alex', 'admin', '2025-08-02 21:39:10', '2025-08-03 00:38:56', '::1', 1, 1),
(451, 'alex', 'admin', '2025-08-02 21:45:02', '2025-08-03 00:38:56', '::1', 1, 1),
(452, 'escall', 'admin', '2025-08-02 21:54:40', '2025-08-03 00:12:09', '::1', 1, 1),
(453, 'ara', 'admin', '2025-08-02 21:55:18', '2025-08-03 01:30:13', '::1', 1, 1),
(454, 'alex', 'admin', '2025-08-02 21:56:41', '2025-08-03 00:38:56', '::1', 1, 1),
(455, 'alex', 'admin', '2025-08-02 22:16:44', '2025-08-03 00:38:56', '::1', 1, 1),
(456, 'alex', 'admin', '2025-08-02 22:38:42', '2025-08-03 00:38:56', '::1', 1, 1),
(457, 'alex', 'admin', '2025-08-02 22:54:06', '2025-08-03 00:38:56', '::1', 1, 1),
(458, 'alex', 'admin', '2025-08-02 23:16:10', '2025-08-03 00:38:56', '::1', 1, 1),
(459, 'alex', 'admin', '2025-08-02 23:31:17', '2025-08-03 00:38:56', '::1', 1, 1),
(460, 'alex', 'admin', '2025-08-02 23:56:11', '2025-08-03 00:38:56', '::1', 1, 1),
(461, 'alex', 'admin', '2025-08-03 00:05:58', '2025-08-03 00:38:56', '::1', 1, 1),
(462, 'alex', 'admin', '2025-08-03 00:06:54', '2025-08-03 00:38:56', '::1', 1, 1),
(463, 'ara', 'admin', '2025-08-03 00:10:35', '2025-08-03 01:30:13', '::1', 1, 1),
(464, 'escall', 'admin', '2025-08-03 00:10:54', '2025-08-03 00:27:45', '::1', 1, 1),
(465, 'alex', 'admin', '2025-08-03 00:18:52', '2025-08-03 00:38:56', '::1', 1, 1),
(466, 'alex', 'admin', '2025-08-03 00:22:55', '2025-08-03 00:38:56', '::1', 1, 1),
(467, 'escall', 'admin', '2025-08-03 00:27:36', '2025-08-03 00:39:23', '::1', 1, 1),
(468, 'alex', 'admin', '2025-08-03 00:27:57', '2025-08-03 00:38:56', '::1', 1, 1),
(469, 'escall', 'admin', '2025-08-03 00:28:12', '2025-08-03 00:39:23', '::1', 1, 1),
(470, 'alex', 'admin', '2025-08-03 00:28:27', '2025-08-03 01:00:57', '::1', 1, 1),
(471, 'escall', 'admin', '2025-08-03 00:39:09', NULL, '::1', 1, 1),
(472, 'alex', 'admin', '2025-08-03 01:00:47', '2025-08-03 01:28:49', '::1', 1, 1),
(473, 'alex', 'admin', '2025-08-03 01:26:19', '2025-08-03 01:28:49', '::1', 24, 2),
(474, 'escall', 'admin', '2025-08-03 01:27:31', NULL, '::1', 23, 1),
(475, 'alex', 'admin', '2025-08-03 01:28:00', '2025-08-03 02:50:10', '::1', 24, 2),
(476, 'ara', 'admin', '2025-08-03 01:30:05', NULL, '::1', 1, 1),
(477, 'ara', 'admin', '2025-08-03 01:34:06', NULL, '::1', 25, 1),
(478, 'ara', 'admin', '2025-08-03 01:48:40', NULL, '::1', 25, 1),
(479, 'alex', 'admin', '2025-08-03 01:49:24', '2025-08-03 02:50:10', '::1', 24, 2),
(480, 'ara', 'admin', '2025-08-03 01:50:10', NULL, '::1', 25, 1),
(481, 'escall', 'admin', '2025-08-03 01:51:18', NULL, '::1', 23, 1),
(482, 'alex', 'admin', '2025-08-03 01:51:49', '2025-08-03 02:50:10', '::1', 24, 2),
(483, 'escall', 'admin', '2025-08-03 01:52:27', NULL, '::1', 23, 1),
(484, 'alex', 'admin', '2025-08-03 02:12:42', '2025-08-03 02:50:10', '::1', 24, 2),
(485, 'alex', 'admin', '2025-08-03 02:20:02', '2025-08-03 02:50:10', '::1', 24, 2),
(486, 'escall', 'admin', '2025-08-03 02:20:13', NULL, '::1', 23, 1),
(487, 'ara', 'admin', '2025-08-03 02:20:56', NULL, '::1', 25, 1),
(488, 'alex', 'admin', '2025-08-03 02:27:26', '2025-08-03 02:50:10', '::1', 24, 2),
(489, 'alex', 'admin', '2025-08-03 02:34:02', '2025-08-03 02:50:10', '::1', 24, 2),
(490, 'ara', 'admin', '2025-08-03 02:35:54', NULL, '::1', 25, 1),
(491, 'alex', 'admin', '2025-08-03 02:39:54', '2025-08-03 02:50:10', '::1', 24, 2),
(492, 'alex', 'admin', '2025-08-03 02:45:35', '2025-08-03 02:50:10', '::1', 24, 2),
(493, 'alex', 'admin', '2025-08-03 02:50:02', '2025-08-11 18:13:30', '::1', 24, 2),
(494, 'alex', 'admin', '2025-08-03 02:54:35', '2025-08-11 18:13:30', '::1', 24, 2),
(495, 'ara', 'admin', '2025-08-09 17:48:57', NULL, '::1', 25, 1),
(496, 'alex', 'admin', '2025-08-09 17:56:51', '2025-08-11 18:13:30', '::1', 24, 2),
(497, 'ara', 'admin', '2025-08-09 18:32:20', NULL, '::1', 25, 1),
(498, 'alex', 'admin', '2025-08-09 18:35:08', '2025-08-11 18:13:30', '::1', 24, 2),
(499, 'escall', 'admin', '2025-08-09 18:35:40', NULL, '::1', 23, 1),
(500, 'alex', 'admin', '2025-08-10 19:38:35', '2025-08-11 18:13:30', '::1', 24, 2),
(501, 'alex', 'admin', '2025-08-10 19:52:35', '2025-08-11 18:13:30', '::1', 24, 2),
(502, 'alex', 'admin', '2025-08-10 23:18:42', '2025-08-11 18:13:30', '::1', 24, 2),
(503, 'alex', 'admin', '2025-08-11 00:07:33', '2025-08-11 18:13:30', '::1', 24, 2),
(504, 'alex', 'admin', '2025-08-11 00:08:10', '2025-08-11 18:13:30', '::1', 24, 2),
(505, 'alex', 'admin', '2025-08-11 11:03:13', '2025-08-11 18:13:30', '::1', 24, 2),
(506, 'alex', 'admin', '2025-08-11 11:04:54', '2025-08-11 18:13:30', '::1', 24, 2),
(507, 'escall', 'admin', '2025-08-11 11:06:50', NULL, '::1', 23, 1),
(508, 'escall', 'admin', '2025-08-11 11:07:36', NULL, '::1', 23, 1),
(509, 'alex', 'admin', '2025-08-11 11:08:28', '2025-08-11 18:13:30', '::1', 24, 2),
(510, 'alex', 'admin', '2025-08-11 11:09:24', '2025-08-11 18:13:30', '::1', 24, 2),
(511, 'alex', 'admin', '2025-08-11 11:25:38', '2025-08-11 18:13:30', '::1', 24, 2),
(512, 'alex', 'admin', '2025-08-11 14:02:20', '2025-08-11 18:13:30', '::1', 24, 2),
(513, 'alex', 'admin', '2025-08-11 14:03:07', '2025-08-11 18:13:30', '::1', 24, 2),
(514, 'alex', 'admin', '2025-08-11 14:12:01', '2025-08-11 18:13:30', '::1', 24, 2),
(515, 'alex', 'admin', '2025-08-11 14:12:11', '2025-08-11 18:13:30', '::1', 24, 2),
(516, 'alex', 'admin', '2025-08-11 18:11:54', '2025-08-11 18:13:30', '::1', 24, 2),
(517, 'alex', 'admin', '2025-08-11 18:13:21', '2025-08-11 19:36:40', '::1', 24, 2),
(518, 'alex', 'admin', '2025-08-11 18:30:15', '2025-08-11 19:36:40', '::1', 24, 2),
(519, 'alex', 'admin', '2025-08-11 19:36:31', NULL, '::1', 24, 2),
(520, 'escall', 'admin', '2025-08-11 19:39:03', NULL, '::1', 23, 1),
(521, 'ara', 'admin', '2025-08-11 19:40:16', NULL, '::1', 25, 1),
(522, 'escall', 'admin', '2025-08-11 21:03:44', NULL, '::1', 23, 1);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=605;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=293;

--
-- AUTO_INCREMENT for table `tbl_user_logs`
--
ALTER TABLE `tbl_user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=523;

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
