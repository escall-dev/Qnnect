-- Database Backup for login_register - 2025-06-04 14:01:00

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
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('36','escall','User','2025-04-03 11:24:00',NULL,'::1'),
('37','escall','User','2025-04-03 21:14:47',NULL,'::1'),
('38','escall','User','2025-04-04 09:34:30',NULL,'::1'),
('39','escall','User','2025-04-04 20:50:38','2025-04-04 20:54:51','::1'),
('40','escall','User','2025-04-04 20:55:00',NULL,'::1'),
('41','escall','User','2025-04-05 06:50:18','2025-04-05 06:51:43','::1'),
('42','escall','User','2025-04-05 07:01:54',NULL,'::1'),
('43','escall','User','2025-04-05 07:58:48','2025-04-05 08:33:42','::1'),
('44','escall','User','2025-04-05 09:28:12','2025-04-05 10:07:50','::1'),
('45','escall','User','2025-04-05 10:38:08','2025-04-05 11:27:21','::1'),
('46','escall','User','2025-04-05 12:56:28','2025-04-05 12:57:14','::1'),
('47','escall','User','2025-04-05 12:57:20','2025-04-05 13:00:07','::1'),
('48','escall','User','2025-04-05 13:00:17','2025-04-05 14:41:15','::1'),
('49','escall','User','2025-04-05 14:41:50','2025-04-05 14:47:35','::1'),
('50','escall','User','2025-04-05 14:47:53','2025-04-05 14:55:03','::1'),
('51','escall','User','2025-04-05 14:55:17','2025-04-05 15:00:43','::1'),
('52','escall','User','2025-04-05 15:00:53','2025-04-05 15:22:15','::1'),
('53','escall','User','2025-04-05 15:22:25','2025-04-05 16:07:49','::1'),
('54','escall','User','2025-04-05 16:08:14','2025-04-05 16:42:34','::1'),
('55','escall','User','2025-04-05 16:42:54','2025-04-05 17:34:05','::1'),
('56','escall','User','2025-04-05 17:34:11',NULL,'::1'),
('57','escall','User','2025-04-05 17:45:45','2025-04-05 17:45:53','::1'),
('58','escall','User','2025-04-05 17:45:59','2025-04-05 18:10:51','::1'),
('59','escall','User','2025-04-05 18:11:01','2025-04-05 18:11:14','::1'),
('60','escall','User','2025-04-08 08:21:17','2025-04-08 08:25:15','::1'),
('61','escall','User','2025-04-08 09:21:44','2025-04-08 10:24:00','::1'),
('62','escall','User','2025-04-09 19:08:27','2025-04-09 20:42:49','::1'),
('63','escall','User','2025-04-09 20:43:04','2025-04-09 21:40:41','::1'),
('64','escall','User','2025-04-09 21:40:50','2025-04-09 21:44:54','::1'),
('65','escall','User','2025-04-09 21:45:11',NULL,'::1'),
('66','escall','User','2025-04-13 21:55:39','2025-04-13 21:58:41','::1'),
('67','escall','User','2025-04-20 00:20:12','2025-04-20 00:35:05','::1'),
('68','escall','User','2025-04-20 00:35:13','2025-04-20 00:42:41','::1'),
('69','escall','User','2025-04-20 00:42:56',NULL,'::1'),
('70','escall','User','2025-04-22 13:57:42','2025-04-22 14:00:37','::1'),
('71','escall','User','2025-04-22 14:01:00','2025-04-22 14:04:19','::1'),
('72','escall','User','2025-04-24 20:16:01','2025-04-24 21:11:53','::1'),
('73','escall','User','2025-04-24 21:11:58','2025-04-24 21:35:24','::1'),
('74','escall','User','2025-04-24 21:40:53','2025-04-24 23:05:48','::1'),
('75','escall','User','2025-04-24 23:05:59','2025-04-25 02:18:47','::1'),
('76','escall','User','2025-04-25 02:19:31','2025-04-25 03:07:26','::1'),
('77','escall','User','2025-04-25 03:08:03',NULL,'::1'),
('78','escall','User','2025-04-25 20:28:03',NULL,'::1'),
('79','escall','User','2025-05-03 15:25:40','2025-05-03 15:30:11','::1'),
('80','escall','User','2025-05-03 15:45:39','2025-05-03 15:50:25','::1'),
('81','escall','User','2025-05-03 15:50:31','2025-05-03 15:57:30','::1'),
('82','escall','User','2025-05-03 15:57:45','2025-05-03 16:09:03','::1'),
('83','escall','User','2025-05-03 16:09:57','2025-05-03 16:19:33','::1'),
('84','escall','User','2025-05-03 16:20:20',NULL,'::1'),
('85','escall','User','2025-05-03 16:37:32','2025-05-03 22:07:47','::1'),
('86','escall','User','2025-05-03 22:08:04','2025-05-03 22:26:06','::1'),
('87','escall','User','2025-05-03 22:26:45',NULL,'::1'),
('88','escall','User','2025-05-14 17:28:46','2025-05-14 17:29:25','::1'),
('89','escall','User','2025-05-15 00:17:05','2025-05-15 00:17:12','::1'),
('90','escall','User','2025-05-15 01:38:58','2025-05-15 01:39:05','::1'),
('91','escall','User','2025-05-15 02:22:40',NULL,'::1'),
('92','escall','User','2025-05-15 02:23:30','2025-05-15 02:23:34','::1'),
('93','escall','User','2025-05-15 13:26:01','2025-05-15 13:26:11','::1'),
('94','escall','User','2025-05-19 00:33:07','2025-05-19 00:33:19','::1'),
('95','escall','User','2025-06-04 19:58:20',NULL,'::1');



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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES
('11','Christian','cdbarcelona.spcpc@gmail.com',NULL,'$2y$10$065b5kEWbzvLC77UpHufMO5gWJi9rgSl2jmnc.CWDHSMV5pWkkGGS',NULL,'3fa1937769b27cfdb0ba48d5bfcef89e1923cc718766ebfb6f1a90505aea0d5f','2025-03-12 21:16:34','User'),
('18','','joerenzescallente027@gmail.com','spcpc','$2y$10$FzCxWGbN8vEvktfMFkfz.urUKZfhB6PdVfEoP4bCVCIZLC5YSG5Ae','uploads/profile_images/profile_1742217946.png',NULL,NULL,'User'),
('19','','escall.byte@gmail.com','escall','$2y$10$faT3LvvNtDEW19I3QZqITeOLYxhulD.v5XKPJ79oHc5I5WvkgivVe','uploads/profile_images/profile_1743053282.jpg',NULL,NULL,'User'),
('20','','dummydumb3100@gmail.com','joshuapogi','$2y$10$xN97pE.dstuIjI1px4uBFufxPzkB6juYm9XCta.MJD7hKb0DQGONe','image/SPCPC-logo-trans.png',NULL,NULL,'User');

SET FOREIGN_KEY_CHECKS=1;
