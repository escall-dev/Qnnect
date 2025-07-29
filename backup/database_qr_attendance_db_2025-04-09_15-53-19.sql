-- Database Backup for qr_attendance_db - 2025-04-09 15:53:19

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";



-- Table structure for table `activity_logs`

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action_type` enum('attendance_scan','settings_change','file_action','user_action','system_change','data_export','offline_sync') NOT NULL,
  `action_description` text NOT NULL,
  `affected_table` varchar(50) DEFAULT NULL,
  `affected_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;





-- Table structure for table `offline_data`

DROP TABLE IF EXISTS `offline_data`;
CREATE TABLE `offline_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL,
  `action_type` enum('insert','update','delete') NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `status` enum('pending','synced','failed') DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `synced_at` datetime DEFAULT NULL,
  `sync_attempts` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;





-- Table structure for table `school_info`

DROP TABLE IF EXISTS `school_info`;
CREATE TABLE `school_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `school_info`
INSERT INTO `school_info` VALUES
('1','Your School Name','School Address','Contact Number','school@email.com','http://www.school.com','admin/image/school-logo.png','School Motto','School Vision','School Mission','2025-04-09 21:20:46','2025-04-09 21:29:56');



-- Table structure for table `tbl_attendance`

DROP TABLE IF EXISTS `tbl_attendance`;
CREATE TABLE `tbl_attendance` (
  `tbl_attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `tbl_student_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  `time_out` timestamp NULL DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`tbl_attendance_id`),
  KEY `tbl_student_id` (`tbl_student_id`),
  KEY `fk_instructor_id` (`instructor_id`),
  KEY `fk_subject_id` (`subject_id`),
  CONSTRAINT `fk_instructor_id` FOREIGN KEY (`instructor_id`) REFERENCES `tbl_instructors` (`instructor_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_subject_id` FOREIGN KEY (`subject_id`) REFERENCES `tbl_subjects` (`subject_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `tbl_attendance`
INSERT INTO `tbl_attendance` VALUES
('3','2','2025-03-13 19:23:38','Late','2025-03-13 19:23:48',NULL,NULL),
('4','2','2025-03-13 21:12:08','Late','2025-03-13 21:22:22',NULL,NULL),
('6','4','2025-03-13 22:02:36','Late','2025-03-13 22:03:55',NULL,NULL),
('7','5','2025-03-13 22:03:46','Late','2025-03-13 22:04:00',NULL,NULL),
('9','4','2025-03-14 18:54:21','Late','2025-03-14 19:01:52',NULL,NULL),
('10','2','2025-03-14 18:54:28','Late','2025-03-14 19:01:43',NULL,NULL),
('17','5','2025-03-16 20:34:23','Late','2025-03-16 20:35:04',NULL,NULL),
('19','4','2025-03-16 20:34:38','Late','2025-03-16 20:36:10',NULL,NULL),
('20','4','2025-03-16 20:36:27','Late','2025-03-16 20:36:35',NULL,NULL),
('21','2','2025-03-16 20:36:40','Late','2025-03-16 22:28:58',NULL,NULL),
('22','8','2025-03-18 08:44:33','Late','2025-03-18 08:44:50',NULL,NULL),
('24','7','2025-04-02 17:49:56','Late','2025-04-02 20:09:22',NULL,NULL),
('26','9','2025-04-03 11:30:36','Late','2025-04-03 11:30:52',NULL,NULL),
('33','11','2025-04-05 09:28:36','Late','2025-04-05 09:35:47',NULL,NULL),
('34','10','2025-04-05 09:35:56','Late','2025-04-05 09:40:28',NULL,NULL),
('35','2','2025-04-05 09:43:13','Late',NULL,NULL,NULL),
('36','4','2025-04-05 13:12:47','On Time',NULL,NULL,NULL),
('37','7','2025-04-05 13:13:16','On Time',NULL,NULL,NULL),
('38','6','2025-04-05 13:18:58','Late',NULL,NULL,NULL),
('39','13','2025-04-05 15:12:49','On Time',NULL,NULL,NULL),
('40','13','2025-04-05 17:41:49','Late',NULL,'1','2'),
('41','11','2025-04-05 17:42:31','Late',NULL,'1','2'),
('42','10','2025-04-05 17:42:48','On Time',NULL,'1','2'),
('43','10','2025-04-05 17:44:29','On Time',NULL,'1','1'),
('44','10','2025-04-05 17:55:57','Late',NULL,'4','6'),
('45','7','2025-04-05 18:01:50','Late',NULL,'4','6'),
('46','6','2025-04-05 18:03:08','Late',NULL,'4','6'),
('47','2','2025-04-05 18:03:50','Late',NULL,'4','6'),
('48','7','2025-04-05 18:08:04','On Time',NULL,'3','4'),
('49','7','2025-04-05 18:09:29','On Time',NULL,'4','7'),
('50','6','2025-04-05 18:09:54','Late',NULL,'4','7'),
('51','13','2025-04-09 19:09:47','Late',NULL,NULL,NULL),
('52','13','2025-04-09 20:40:35','Late',NULL,NULL,NULL),
('53','13','2025-04-09 20:41:17','On Time',NULL,'1','1'),
('54','11','2025-04-09 20:41:23','On Time',NULL,'1','1'),
('55','10','2025-04-09 20:41:29','On Time',NULL,'1','1');



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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_face_verification_logs`
INSERT INTO `tbl_face_verification_logs` VALUES
('1',NULL,'Alexander Joerenz Escallente','Success','2025-03-13 19:22:51','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('2',NULL,'barney','Success','2025-03-13 20:32:41','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('3',NULL,'escall','Success','2025-03-13 21:43:05','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('4',NULL,'zeno','Success','2025-03-14 19:04:12','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('5',NULL,'CRYPTO','Success','2025-03-14 19:04:55','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('6',NULL,'joshua bayot','Success','2025-03-18 08:43:54','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('7',NULL,'melissa','Success','2025-04-03 11:28:49','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('8',NULL,'capstone 1','Success','2025-04-04 10:01:25','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('9',NULL,'atom nucleus','Success','2025-04-04 21:18:34','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('10',NULL,'melissa lucenecio','Success','2025-04-05 10:40:26','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('11',NULL,'CAP1','Success','2025-04-05 15:12:21','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('12',NULL,'alucard','Success','2025-04-05 16:36:35','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration'),
('13',NULL,'chou','Success','2025-04-05 16:37:13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36','Face captured during registration');



-- Table structure for table `tbl_instructor_subjects`

DROP TABLE IF EXISTS `tbl_instructor_subjects`;
CREATE TABLE `tbl_instructor_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instructor_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_instructor_subject` (`instructor_id`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `tbl_instructor_subjects_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `tbl_instructors` (`instructor_id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_instructor_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `tbl_subjects` (`subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_instructor_subjects`
INSERT INTO `tbl_instructor_subjects` VALUES
('1','1','1','2025-04-05 15:30:20'),
('2','1','2','2025-04-05 15:30:49'),
('5','3','4','2025-04-05 15:55:44'),
('7','4','6','2025-04-05 16:14:20'),
('8','4','7','2025-04-05 16:14:20');



-- Table structure for table `tbl_instructors`

DROP TABLE IF EXISTS `tbl_instructors`;
CREATE TABLE `tbl_instructors` (
  `instructor_id` int(11) NOT NULL AUTO_INCREMENT,
  `instructor_name` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`instructor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_instructors`
INSERT INTO `tbl_instructors` VALUES
('1','Arnold Aranaydo','Capstone 1','2025-04-05 15:21:22'),
('3','Wency Trapago','','2025-04-05 15:55:44'),
('4','Mr. Valdez','','2025-04-05 16:14:20');



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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `tbl_student`
INSERT INTO `tbl_student` VALUES
('2','Alexander Joerenz Escallente','BSIT-302','F9ZqkMhxdC',NULL,'face_1741864975_67d2c00fde99f.jpg'),
('4','barney','BSHRM-202','BGDaJwGfqF',NULL,'face_1741869166_67d2d06e39ee5.jpg'),
('5','escall','BSBA-301','AXNMyFOIO1',NULL,'face_1741873390_67d2e0eed6208.jpg'),
('6','zeno','BSTM-101','t3mXGjOiWL',NULL,'face_1741950258_67d40d3220a8d.jpg'),
('7','CRYPTO','BSTM-102','ZptdwLoYc8',NULL,'face_1741950299_67d40d5b2c277.jpg'),
('8','joshua bayot','BSIT-302','t59iFIrHee',NULL,'face_1742258642_67d8c1d2ae831.jpg'),
('9','melissa','BSIT-302','JifZShPEZF',NULL,'face_1743650938_67ee007a02a50.jpg'),
('10','capstone 1','BSIT-301','hcJIaoCQjb',NULL,'face_1743732089_67ef3d79a723e.jpg'),
('11','atom nucleus','BSIT-301','tU7xeQLoqy',NULL,'face_1743772719_67efdc2fe402b.jpg'),
('12','melissa lucenecio','BSIT-302','fWyLB4j8yM',NULL,'face_1743820862_67f0983e14c71.jpg'),
('13','CAP1','BSIT-302','ySscHVEbVs',NULL,'face_1743837145_67f0d7d966a46.jpg'),
('14','alucard','BSIT-302','XWJCtfrFi4',NULL,'face_1743842202_67f0eb9a4841c.jpg'),
('15','chou','BSIS-301','PPtdm28Iwi',NULL,'face_1743842237_67f0ebbdee328.jpg');



-- Table structure for table `tbl_subjects`

DROP TABLE IF EXISTS `tbl_subjects`;
CREATE TABLE `tbl_subjects` (
  `subject_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `subject_name` (`subject_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_subjects`
INSERT INTO `tbl_subjects` VALUES
('1','System Admin & Maintenance','2025-04-05 15:30:20'),
('2','Capstone 1','2025-04-05 15:30:49'),
('3','System Information Assurance 1','2025-04-05 15:47:54'),
('4','Information Assurance Security 1','2025-04-05 15:49:01'),
('5','Casptone 2','2025-04-05 15:56:01'),
('6','Social Professional Issues','2025-04-05 16:14:20'),
('7','Application Development','2025-04-05 16:14:20');



-- Table structure for table `user_settings`

DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `school_year` varchar(10) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `user_settings`
INSERT INTO `user_settings` VALUES
('1','escall.byte@gmail.com','2024-2025','2nd Semester','2025-04-05 14:54:58','2025-04-09 21:20:55');

SET FOREIGN_KEY_CHECKS=1;
