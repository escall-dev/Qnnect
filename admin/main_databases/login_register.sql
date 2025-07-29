-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 17, 2025 at 03:07 PM
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
(3, 'escalliente', 'User', '2025-03-17 21:01:27', NULL, '::1'),
(4, 'escalliente', 'User', '2025-03-17 21:06:32', NULL, '::1'),
(5, 'escalliente', 'User', '2025-03-17 21:07:00', NULL, '::1'),
(6, 'escalliente', 'User', '2025-03-17 21:07:58', '2025-03-17 21:19:23', '::1'),
(7, 'escalliente', 'User', '2025-03-17 21:19:39', '2025-03-17 21:21:15', '::1'),
(8, 'escalliente', 'User', '2025-03-17 21:21:29', '2025-03-17 21:23:32', '::1'),
(9, 'escalliente', 'User', '2025-03-17 21:24:22', '2025-03-17 21:24:31', '::1'),
(10, 'spcpc', 'User', '2025-03-17 21:25:17', '2025-03-17 21:44:59', '::1'),
(11, 'escalliente', 'User', '2025-03-17 21:45:12', NULL, '::1');

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
  `user_type` varchar(20) DEFAULT 'User'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `username`, `password`, `profile_image`, `reset_token_hash`, `reset_token_hash_expires_at`, `user_type`) VALUES
(11, 'Christian', 'cdbarcelona.spcpc@gmail.com', NULL, '$2y$10$065b5kEWbzvLC77UpHufMO5gWJi9rgSl2jmnc.CWDHSMV5pWkkGGS', NULL, '3fa1937769b27cfdb0ba48d5bfcef89e1923cc718766ebfb6f1a90505aea0d5f', '2025-03-12 21:16:34', 'User'),
(17, '', 'escall.byte@gmail.com', 'escalliente', '$2y$10$daFnFMhe9BSmKe.BAX4evO61NwC5q/FrDX9jAyEN23a/pKPVMWs6q', 'uploads/profile_images/profile_1742209089.jpg', '52cdd34f5de092de16a8fa011b07deeff325141315da37706b781201e11b9902', '2025-03-17 19:23:39', 'User'),
(18, '', 'joerenzescallente027@gmail.com', 'spcpc', '$2y$10$FzCxWGbN8vEvktfMFkfz.urUKZfhB6PdVfEoP4bCVCIZLC5YSG5Ae', 'uploads/profile_images/profile_1742217946.png', NULL, NULL, 'User');

--
-- Indexes for dumped tables
--

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
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_user_logs`
--
ALTER TABLE `tbl_user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
