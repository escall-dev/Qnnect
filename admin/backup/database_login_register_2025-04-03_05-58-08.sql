-- Database Backup for login_register - 2025-04-03 05:58:08

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";



-- Table structure for table `tbl_user_logs`

DROP TABLE IF EXISTS `tbl_user_logs`;
CREATE TABLE `tbl_user_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `user_type` varchar(20) DEFAULT 'User',
  `log_in_time` datetime DEFAULT current_timestamp(),
  `log_out_time` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_user_logs`
INSERT INTO `tbl_user_logs` VALUES
('1','escalliente','User','2025-03-17 20:57:53','2025-03-17 20:59:20','::1'),
('2','escalliente','User','2025-03-17 20:59:33','2025-03-17 21:00:20','::1'),
('3','escalliente','User','2025-03-17 21:01:27',NULL,'::1'),
('4','escalliente','User','2025-03-17 21:06:32',NULL,'::1'),
('5','escalliente','User','2025-03-17 21:07:00',NULL,'::1'),
('6','escalliente','User','2025-03-17 21:07:58','2025-03-17 21:19:23','::1'),
('7','escalliente','User','2025-03-17 21:19:39','2025-03-17 21:21:15','::1'),
('8','escalliente','User','2025-03-17 21:21:29','2025-03-17 21:23:32','::1'),
('9','escalliente','User','2025-03-17 21:24:22','2025-03-17 21:24:31','::1'),
('10','spcpc','User','2025-03-17 21:25:17','2025-03-17 21:44:59','::1'),
('11','escalliente','User','2025-03-17 21:45:12','2025-03-17 22:09:03','::1'),
('12','escalliente','User','2025-03-17 22:09:18',NULL,'::1'),
('13','escalliente','User','2025-03-18 08:36:58','2025-03-18 08:37:52','::1'),
('14','escalliente','User','2025-03-18 08:41:51',NULL,'::1'),
('15','escalliente','User','2025-03-18 08:56:39','2025-03-18 08:59:38','::1'),
('16','escalliente','User','2025-03-18 08:59:49','2025-03-18 08:59:57','::1'),
('17','escalliente','User','2025-03-20 12:36:53',NULL,'::1'),
('18','escalliente','User','2025-03-20 12:51:32',NULL,'::1'),
('19','escalliente','User','2025-03-20 19:22:46','2025-03-20 19:53:09','::1'),
('20','escalliente','User','2025-03-20 19:53:22',NULL,'::1'),
('21','escalliente','User','2025-03-24 20:16:32',NULL,'::1'),
('22','escall','User','2025-03-24 20:25:18','2025-03-24 20:33:04','::1'),
('23','escall','User','2025-03-24 20:33:09','2025-03-24 20:36:06','::1'),
('24','escall','User','2025-03-24 20:37:24',NULL,'::1'),
('25','escall','User','2025-03-27 11:52:34','2025-03-27 11:52:42','::1'),
('26','escall','User','2025-03-27 13:11:48',NULL,'::1'),
('27','escall','User','2025-03-27 13:15:06','2025-03-27 13:26:26','::1'),
('28','escall','User','2025-03-27 13:26:32',NULL,'::1'),
('29','escall','User','2025-03-28 20:12:23','2025-03-28 20:14:38','::1'),
('30','escall','User','2025-03-28 20:14:43','2025-03-28 20:15:54','::1'),
('31','escall','User','2025-04-02 17:49:09','2025-04-02 18:45:26','::1'),
('32','escall','User','2025-04-02 18:45:32',NULL,'::1'),
('33','escall','User','2025-04-02 20:12:20',NULL,'::1'),
('34','escall','User','2025-04-02 20:55:03','2025-04-02 20:55:14','::1'),
('35','escall','User','2025-04-03 11:04:17','2025-04-03 11:05:01','::1'),
('36','escall','User','2025-04-03 11:24:00',NULL,'::1');



-- Table structure for table `users`

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(128) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `reset_token_hash` varchar(255) DEFAULT NULL,
  `reset_token_hash_expires_at` datetime DEFAULT NULL,
  `user_type` varchar(20) DEFAULT 'User',
  PRIMARY KEY (`id`),
  UNIQUE KEY `reset_token_hash` (`reset_token_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES
('11','Christian','cdbarcelona.spcpc@gmail.com',NULL,'$2y$10$065b5kEWbzvLC77UpHufMO5gWJi9rgSl2jmnc.CWDHSMV5pWkkGGS',NULL,'3fa1937769b27cfdb0ba48d5bfcef89e1923cc718766ebfb6f1a90505aea0d5f','2025-03-12 21:16:34','User'),
('18','','joerenzescallente027@gmail.com','spcpc','$2y$10$FzCxWGbN8vEvktfMFkfz.urUKZfhB6PdVfEoP4bCVCIZLC5YSG5Ae','uploads/profile_images/profile_1742217946.png',NULL,NULL,'User'),
('19','','escall.byte@gmail.com','escall','$2y$10$EkCR3Q0IMxeF0AjVEjSRYexGAMnz7F0.psz1Jb0odrDsIW5dSBsaG','uploads/profile_images/profile_1743053282.jpg','7af578be8c3bbc25230719cc2c9192a5a95d41970ac43de3768ccc5f676da174','2025-04-03 11:49:35','User');

SET FOREIGN_KEY_CHECKS=1;
