-- Database Backup for qr_attendance_db - 2025-04-04 14:50:59

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";



-- Table structure for table `tbl_attendance`

DROP TABLE IF EXISTS `tbl_attendance`;
CREATE TABLE `tbl_attendance` (
  `tbl_attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `tbl_student_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_out` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`tbl_attendance_id`),
  KEY `tbl_student_id` (`tbl_student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `tbl_attendance`
INSERT INTO `tbl_attendance` VALUES
('3','2','2025-03-13 19:23:38','2025-03-13 19:23:48'),
('4','2','2025-03-13 21:12:08','2025-03-13 21:22:22'),
('5','1','2025-03-13 22:02:31','2025-03-13 22:03:52'),
('6','4','2025-03-13 22:02:36','2025-03-13 22:03:55'),
('7','5','2025-03-13 22:03:46','2025-03-13 22:04:00'),
('8','1','2025-03-14 18:54:09','2025-03-14 19:01:56'),
('9','4','2025-03-14 18:54:21','2025-03-14 19:01:52'),
('10','2','2025-03-14 18:54:28','2025-03-14 19:01:43'),
('11','5','2025-03-14 20:09:55',NULL),
('12','7','2025-03-14 20:11:25',NULL),
('13','6','2025-03-14 20:11:32',NULL),
('15','4','2025-03-14 20:13:40',NULL),
('16','6','2025-03-16 20:34:04','2025-03-16 20:34:52'),
('17','5','2025-03-16 20:34:23','2025-03-16 20:35:04'),
('18','1','2025-03-16 20:34:29','2025-03-16 20:35:19'),
('19','4','2025-03-16 20:34:38','2025-03-16 20:36:10'),
('20','4','2025-03-16 20:36:27','2025-03-16 20:36:35'),
('21','2','2025-03-16 20:36:40','2025-03-16 22:28:58'),
('22','8','2025-03-18 08:44:33','2025-03-18 08:44:50'),
('24','7','2025-04-02 17:49:56','2025-04-02 20:09:22'),
('26','9','2025-04-03 11:30:36','2025-04-03 11:30:52'),
('27','9','2025-04-04 09:55:44',NULL);



-- Table structure for table `tbl_face_recognition_logs`

DROP TABLE IF EXISTS `tbl_face_recognition_logs`;
CREATE TABLE `tbl_face_recognition_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `recognition_status` enum('success','failed') NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `tbl_face_recognition_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `tbl_student` (`tbl_student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_face_recognition_logs`
INSERT INTO `tbl_face_recognition_logs` VALUES
('1','2','failed','2025-03-13 19:53:07','::1'),
('2','2','failed','2025-03-13 19:53:10','::1'),
('3','2','failed','2025-03-13 19:53:11','::1'),
('4','2','failed','2025-03-13 19:53:12','::1'),
('5','2','failed','2025-03-13 19:53:13','::1'),
('6','2','failed','2025-03-13 19:53:43','::1'),
('7','2','failed','2025-03-13 19:53:45','::1'),
('8','2','failed','2025-03-13 19:53:46','::1'),
('9','2','failed','2025-03-13 19:53:47','::1'),
('10','2','failed','2025-03-13 19:53:47','::1');



-- Table structure for table `tbl_face_verification_logs`

DROP TABLE IF EXISTS `tbl_face_verification_logs`;
CREATE TABLE `tbl_face_verification_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `student_name` varchar(255) NOT NULL,
  `status` enum('Success','Failed') NOT NULL,
  `verification_time` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `fk_verification_student` FOREIGN KEY (`student_id`) REFERENCES `tbl_student` (`tbl_student_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_face_verification_logs`
INSERT INTO `tbl_face_verification_logs` VALUES
('1',NULL,'Alexander Joerenz Escallente','Success','2025-03-13 19:22:51','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('2',NULL,'barney','Success','2025-03-13 20:32:41','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('3',NULL,'escall','Success','2025-03-13 21:43:05','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('4',NULL,'zeno','Success','2025-03-14 19:04:12','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('5',NULL,'CRYPTO','Success','2025-03-14 19:04:55','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('6',NULL,'joshua bayot','Success','2025-03-18 08:43:54','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('7',NULL,'melissa','Success','2025-04-03 11:28:49','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('8',NULL,'capstone 1','Success','2025-04-04 10:01:25','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration');



-- Table structure for table `tbl_student`

DROP TABLE IF EXISTS `tbl_student`;
CREATE TABLE `tbl_student` (
  `tbl_student_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(255) NOT NULL,
  `course_section` varchar(255) NOT NULL,
  `generated_code` varchar(255) NOT NULL,
  `face_image` varchar(255) DEFAULT NULL,
  `face_image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`tbl_student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `tbl_student`
INSERT INTO `tbl_student` VALUES
('1','Arnold','BSIT-301','KIYkAk6ZRV',NULL,NULL),
('2','Alexander Joerenz Escallente','BSIT-302','F9ZqkMhxdC',NULL,'face_1741864975_67d2c00fde99f.jpg'),
('4','barney','BSHRM-202','BGDaJwGfqF',NULL,'face_1741869166_67d2d06e39ee5.jpg'),
('5','escall','BSBA-301','AXNMyFOIO1',NULL,'face_1741873390_67d2e0eed6208.jpg'),
('6','zeno','BSTM-101','t3mXGjOiWL',NULL,'face_1741950258_67d40d3220a8d.jpg'),
('7','CRYPTO','BSTM-102','ZptdwLoYc8',NULL,'face_1741950299_67d40d5b2c277.jpg'),
('8','joshua bayot','BSIT-302','t59iFIrHee',NULL,'face_1742258642_67d8c1d2ae831.jpg'),
('9','melissa','BSIT-302','JifZShPEZF',NULL,'face_1743650938_67ee007a02a50.jpg'),
('10','capstone 1','BSIT-301','hcJIaoCQjb',NULL,'face_1743732089_67ef3d79a723e.jpg');

SET FOREIGN_KEY_CHECKS=1;
