-- Ujuzi Training full backup
-- generated 2026-11-21T21:15:15+00:00
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `admin_activity_log`;
CREATE TABLE `admin_activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int unsigned DEFAULT NULL,
  `admin_email` varchar(255) DEFAULT NULL,
  `admin_name` varchar(255) DEFAULT NULL,
  `is_owner` tinyint(1) NOT NULL DEFAULT '0',
  `method` varchar(8) NOT NULL,
  `path` varchar(512) NOT NULL,
  `action` varchar(190) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_time` (`created_at`),
  KEY `idx_activity_admin` (`admin_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('1','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin','Opened the dashboard','127.0.0.1','2026-10-29 13:46:41');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('2','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:46:41');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('3','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:46:41');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('4','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:46:41');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('5','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:46:41');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('6','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:46:46');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('7','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:47:13');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('8','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:47:13');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('9','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:47:13');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('10','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:47:13');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('11','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:47:22');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('12','1','uitest-admin@example.test','UI Test Admin','1','POST','/admin/navigation/trainer','Submitted in Navigation','127.0.0.1','2026-10-29 13:47:22');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('13','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:47:22');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('14','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/student-lookup','Viewed Student Lookup','127.0.0.1','2026-10-29 13:48:59');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('15','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/settings','Viewed Settings','127.0.0.1','2026-10-29 13:49:52');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('16','1','uitest-admin@example.test','UI Test Admin','1','POST','/admin/settings','Submitted in Settings','127.0.0.1','2026-10-29 13:49:52');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('17','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/settings','Viewed Settings','127.0.0.1','2026-10-29 13:49:52');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('18','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/organisations/1/edit','Opened a form in Organisations','127.0.0.1','2026-10-29 13:50:41');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('19','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/organisations/1/edit','Opened a form in Organisations','127.0.0.1','2026-10-29 13:50:41');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('20','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/registered-users','Viewed Registered Users','127.0.0.1','2026-10-29 13:52:28');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('21','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/registered-users','Viewed Registered Users','127.0.0.1','2026-10-29 13:52:28');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('22','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin','Opened the dashboard','127.0.0.1','2026-10-29 13:55:00');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('23','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/navigation','Viewed Navigation','127.0.0.1','2026-10-29 13:55:00');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('24','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/registered-users','Viewed Registered Users','127.0.0.1','2026-10-29 13:55:01');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('25','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/registered-users','Viewed Registered Users','127.0.0.1','2026-10-29 13:55:01');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('26','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/organisations','Viewed Organisations','127.0.0.1','2026-10-29 13:55:01');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('27','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/organisations/1/edit','Opened a form in Organisations','127.0.0.1','2026-10-29 13:55:01');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('28','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/organisations/create','Opened a form in Organisations','127.0.0.1','2026-10-29 13:55:01');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('29','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/settings','Viewed Settings','127.0.0.1','2026-10-29 13:55:01');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('30','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/course-approvals','Viewed Course Approvals','127.0.0.1','2026-10-29 13:55:01');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('31','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/student-lookup','Viewed Student Lookup','127.0.0.1','2026-10-29 13:55:01');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('32','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/reports','Viewed Reports','127.0.0.1','2026-10-29 13:55:01');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('33','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/finance','Viewed Finance','127.0.0.1','2026-10-29 13:55:01');
INSERT INTO `admin_activity_log` (`id`,`admin_id`,`admin_email`,`admin_name`,`is_owner`,`method`,`path`,`action`,`ip`,`created_at`) VALUES ('34','1','uitest-admin@example.test','UI Test Admin','1','GET','/admin/attachments','Viewed Attachments','127.0.0.1','2026-10-29 13:55:01');
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_owner` tinyint(1) NOT NULL DEFAULT '0',
  `permissions` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by_admin_id` int unsigned DEFAULT NULL,
  `session_token` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `admins` (`id`,`email`,`name`,`password_hash`,`created_at`,`is_owner`,`permissions`,`is_active`,`created_by_admin_id`,`session_token`) VALUES ('1','uitest-admin@example.test','UI Test Admin','$2y$12$c9ELvI7HOWieSEIdEgkf3ePucvoLQVh5439X4P3fqQMJflNieF5eK','2026-10-13 06:23:31','1',NULL,'1',NULL,'18246d481cfed49dd25d5b22b9bcf097');
DROP TABLE IF EXISTS `attachment_applications`;
CREATE TABLE `attachment_applications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_user_id` int unsigned NOT NULL,
  `course_id` int unsigned DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `provider_user_id` int unsigned NOT NULL,
  `branch_id` int unsigned DEFAULT NULL,
  `status` enum('pending','accepted','paused','completed','recommended','rejected') NOT NULL DEFAULT 'pending',
  `provider_note` text,
  `declined_provider_ids` varchar(255) DEFAULT NULL,
  `selected_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `recommended_at` timestamp NULL DEFAULT NULL,
  `letter_sent_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_attachment_student_course` (`student_user_id`,`course_id`),
  KEY `idx_attachment_provider_status` (`provider_user_id`,`status`),
  KEY `idx_attachment_branch_status` (`branch_id`,`status`),
  KEY `fk_attachment_course` (`course_id`),
  KEY `fk_attachment_category` (`category_id`),
  CONSTRAINT `fk_attachment_branch` FOREIGN KEY (`branch_id`) REFERENCES `organisation_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_attachment_category` FOREIGN KEY (`category_id`) REFERENCES `organisation_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_attachment_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attachment_provider` FOREIGN KEY (`provider_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attachment_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `attachment_assessments`;
CREATE TABLE `attachment_assessments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int unsigned NOT NULL,
  `items` json NOT NULL,
  `total_score` decimal(8,2) NOT NULL DEFAULT '0.00',
  `max_score` decimal(8,2) NOT NULL DEFAULT '0.00',
  `percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `remarks` text,
  `assessed_by_user_id` int unsigned DEFAULT NULL,
  `assessed_at` timestamp NULL DEFAULT NULL,
  `shared_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_assessment_application` (`application_id`),
  CONSTRAINT `fk_assessment_application` FOREIGN KEY (`application_id`) REFERENCES `attachment_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `attachment_messages`;
CREATE TABLE `attachment_messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int unsigned NOT NULL,
  `sender_user_id` int unsigned NOT NULL,
  `sender_side` enum('student','reviewer') NOT NULL,
  `body` text NOT NULL,
  `status_change` varchar(20) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attachment_messages_app` (`application_id`,`created_at`),
  KEY `fk_attachment_messages_sender` (`sender_user_id`),
  CONSTRAINT `fk_attachment_messages_app` FOREIGN KEY (`application_id`) REFERENCES `attachment_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attachment_messages_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `attachment_provider_audiences`;
CREATE TABLE `attachment_provider_audiences` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `provider_user_id` int unsigned NOT NULL,
  `organisation_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` int unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_audience_provider_category` (`provider_user_id`,`category_id`),
  KEY `idx_audience_category` (`category_id`),
  KEY `fk_audience_organisation` (`organisation_id`),
  CONSTRAINT `fk_audience_category` FOREIGN KEY (`category_id`) REFERENCES `organisation_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_audience_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_audience_provider` FOREIGN KEY (`provider_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `branch_deletion_requests`;
CREATE TABLE `branch_deletion_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int unsigned NOT NULL,
  `requested_by_user_id` int unsigned NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `organisation_approved_at` timestamp NULL DEFAULT NULL,
  `organisation_approved_by` int unsigned DEFAULT NULL,
  `admin_approved_at` timestamp NULL DEFAULT NULL,
  `admin_approved_by` int unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_branch_deletion_branch` (`branch_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_key` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_key` (`category_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `course_categories`;
CREATE TABLE `course_categories` (
  `course_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  PRIMARY KEY (`course_id`,`category_id`),
  KEY `fk_course_categories_category` (`category_id`),
  CONSTRAINT `fk_course_categories_category` FOREIGN KEY (`category_id`) REFERENCES `organisation_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_course_categories_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `course_categories` (`course_id`,`category_id`) VALUES ('1','1');
DROP TABLE IF EXISTS `course_completions`;
CREATE TABLE `course_completions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `course_id` int unsigned NOT NULL,
  `score` int unsigned DEFAULT NULL,
  `completed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_course_completion` (`user_id`,`course_id`),
  KEY `fk_completion_course` (`course_id`),
  CONSTRAINT `fk_completion_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_completion_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `course_enrollments`;
CREATE TABLE `course_enrollments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `course_id` int unsigned NOT NULL,
  `amount_ksh` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) NOT NULL DEFAULT 'KSH',
  `payment_provider` varchar(30) NOT NULL DEFAULT 'manual',
  `payment_status` varchar(30) NOT NULL DEFAULT 'paid',
  `payment_reference` varchar(100) DEFAULT NULL,
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_course_enrollment` (`user_id`,`course_id`),
  KEY `idx_course_enrollments_course` (`course_id`,`user_id`),
  CONSTRAINT `fk_course_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_course_enrollments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `course_enrollments` (`id`,`user_id`,`course_id`,`amount_ksh`,`currency`,`payment_provider`,`payment_status`,`payment_reference`,`enrolled_at`,`created_at`,`updated_at`) VALUES ('1','3','1','0.00','KSH','manual','paid',NULL,'2026-10-13 06:23:34','2026-10-13 06:23:34','2026-10-13 06:23:34');
DROP TABLE IF EXISTS `course_final_exam_progress`;
CREATE TABLE `course_final_exam_progress` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `course_id` int unsigned NOT NULL,
  `score` tinyint unsigned NOT NULL DEFAULT '0',
  `passed` tinyint(1) NOT NULL DEFAULT '0',
  `answers` json DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_course_final_exam` (`user_id`,`course_id`),
  KEY `idx_final_exam_course` (`course_id`,`user_id`),
  CONSTRAINT `fk_final_exam_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_final_exam_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `course_module_access`;
CREATE TABLE `course_module_access` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `course_id` int unsigned NOT NULL,
  `module_id` int unsigned NOT NULL,
  `price_ksh` decimal(12,2) NOT NULL DEFAULT '0.00',
  `from_wallet_ksh` decimal(12,2) NOT NULL DEFAULT '0.00',
  `unlocked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_access` (`user_id`,`module_id`),
  KEY `idx_module_access_course` (`user_id`,`course_id`),
  KEY `fk_module_access_module` (`module_id`),
  CONSTRAINT `fk_module_access_module` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_module_access_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `course_module_progress`;
CREATE TABLE `course_module_progress` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `course_id` int unsigned NOT NULL,
  `module_id` int unsigned NOT NULL,
  `score` tinyint unsigned NOT NULL DEFAULT '0',
  `passed` tinyint(1) NOT NULL DEFAULT '0',
  `answers` json DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_module_progress` (`user_id`,`module_id`),
  KEY `idx_progress_course` (`course_id`,`user_id`),
  KEY `fk_progress_module` (`module_id`),
  CONSTRAINT `fk_progress_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_module` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `course_modules`;
CREATE TABLE `course_modules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int unsigned NOT NULL,
  `title` varchar(191) NOT NULL,
  `description` text,
  `summary` text,
  `notes` text,
  `duration_minutes` smallint unsigned NOT NULL DEFAULT '10',
  `video_source` enum('upload','youtube') NOT NULL DEFAULT 'upload',
  `video_path` varchar(500) DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `materials` json DEFAULT NULL,
  `quiz_questions` json DEFAULT NULL,
  `pass_percent` tinyint unsigned NOT NULL DEFAULT '80',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_modules_course` (`course_id`),
  CONSTRAINT `fk_modules_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `course_modules` (`id`,`course_id`,`title`,`description`,`summary`,`notes`,`duration_minutes`,`video_source`,`video_path`,`video_url`,`materials`,`quiz_questions`,`pass_percent`,`sort_order`,`created_at`,`updated_at`) VALUES ('1','1','Module 1: Car controls and the very long title that wraps on phones','About module 1','Overview of module 1','Notes for module 1','15','youtube',NULL,'https://www.youtube.com/watch?v=dQw4w9WgXcQ',NULL,'[{\"type\": \"single_choice\", \"image\": \"\", \"correct\": 1, \"options\": [\"Brakes\", \"Disconnects engine from wheels\", \"Steers\"], \"question\": \"What does the clutch do?\", \"accepted_answers\": []}, {\"type\": \"single_choice\", \"image\": \"\", \"correct\": 0, \"options\": [\"Accelerator\", \"Brake\", \"Clutch\"], \"question\": \"Which pedal is on the right?\", \"accepted_answers\": []}]','50','1','2026-10-13 06:23:34','2026-10-13 06:23:34');
INSERT INTO `course_modules` (`id`,`course_id`,`title`,`description`,`summary`,`notes`,`duration_minutes`,`video_source`,`video_path`,`video_url`,`materials`,`quiz_questions`,`pass_percent`,`sort_order`,`created_at`,`updated_at`) VALUES ('2','1','Module 2: Car controls and the very long title that wraps on phones','About module 2','Overview of module 2','Notes for module 2','15','youtube',NULL,'https://www.youtube.com/watch?v=dQw4w9WgXcQ',NULL,'[{\"type\": \"single_choice\", \"image\": \"\", \"correct\": 1, \"options\": [\"Brakes\", \"Disconnects engine from wheels\", \"Steers\"], \"question\": \"What does the clutch do?\", \"accepted_answers\": []}, {\"type\": \"single_choice\", \"image\": \"\", \"correct\": 0, \"options\": [\"Accelerator\", \"Brake\", \"Clutch\"], \"question\": \"Which pedal is on the right?\", \"accepted_answers\": []}]','50','2','2026-10-13 06:23:34','2026-10-13 06:23:34');
INSERT INTO `course_modules` (`id`,`course_id`,`title`,`description`,`summary`,`notes`,`duration_minutes`,`video_source`,`video_path`,`video_url`,`materials`,`quiz_questions`,`pass_percent`,`sort_order`,`created_at`,`updated_at`) VALUES ('3','1','Module 3: Car controls and the very long title that wraps on phones','About module 3','Overview of module 3','Notes for module 3','15','youtube',NULL,'https://www.youtube.com/watch?v=dQw4w9WgXcQ',NULL,'[{\"type\": \"single_choice\", \"image\": \"\", \"correct\": 1, \"options\": [\"Brakes\", \"Disconnects engine from wheels\", \"Steers\"], \"question\": \"What does the clutch do?\", \"accepted_answers\": []}, {\"type\": \"single_choice\", \"image\": \"\", \"correct\": 0, \"options\": [\"Accelerator\", \"Brake\", \"Clutch\"], \"question\": \"Which pedal is on the right?\", \"accepted_answers\": []}]','50','3','2026-10-13 06:23:34','2026-10-13 06:23:34');
INSERT INTO `course_modules` (`id`,`course_id`,`title`,`description`,`summary`,`notes`,`duration_minutes`,`video_source`,`video_path`,`video_url`,`materials`,`quiz_questions`,`pass_percent`,`sort_order`,`created_at`,`updated_at`) VALUES ('4','1','Module 4: Car controls and the very long title that wraps on phones','About module 4','Overview of module 4','Notes for module 4','15','youtube',NULL,'https://www.youtube.com/watch?v=dQw4w9WgXcQ',NULL,'[{\"type\": \"single_choice\", \"image\": \"\", \"correct\": 1, \"options\": [\"Brakes\", \"Disconnects engine from wheels\", \"Steers\"], \"question\": \"What does the clutch do?\", \"accepted_answers\": []}, {\"type\": \"single_choice\", \"image\": \"\", \"correct\": 0, \"options\": [\"Accelerator\", \"Brake\", \"Clutch\"], \"question\": \"Which pedal is on the right?\", \"accepted_answers\": []}]','50','4','2026-10-13 06:23:34','2026-10-13 06:23:34');
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int unsigned NOT NULL,
  `branch_id` int unsigned DEFAULT NULL,
  `category_id` int unsigned NOT NULL,
  `trainer_user_id` int unsigned NOT NULL,
  `form_id` int unsigned DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `description` text,
  `cover_image` varchar(500) DEFAULT NULL,
  `materials` json DEFAULT NULL,
  `answers` json DEFAULT NULL,
  `final_exam_questions` json DEFAULT NULL,
  `final_pass_percent` tinyint unsigned NOT NULL DEFAULT '80',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `visibility` enum('strict','global') NOT NULL DEFAULT 'strict',
  `enrollment_fee_ksh` decimal(10,2) NOT NULL DEFAULT '0.00',
  `requires_attachment` tinyint(1) NOT NULL DEFAULT '0',
  `certificate_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `requires_full_registration` tinyint(1) NOT NULL DEFAULT '0',
  `introduction_title` varchar(191) DEFAULT NULL,
  `introduction_description` text,
  `introduction_video_source` enum('upload','youtube') NOT NULL DEFAULT 'youtube',
  `introduction_video_path` varchar(500) DEFAULT NULL,
  `introduction_video_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `featured_on_home` tinyint(1) NOT NULL DEFAULT '0',
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approval_note` varchar(500) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `final_paper_size` int unsigned DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `final_duration_minutes` int unsigned DEFAULT NULL,
  `requirements` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_course_code` (`course_code`),
  KEY `idx_courses_org` (`organisation_id`),
  KEY `idx_courses_trainer` (`trainer_user_id`),
  KEY `fk_courses_category` (`category_id`),
  KEY `fk_courses_form` (`form_id`),
  KEY `idx_course_branch` (`branch_id`),
  CONSTRAINT `fk_courses_category` FOREIGN KEY (`category_id`) REFERENCES `organisation_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_courses_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_courses_org` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_courses_trainer` FOREIGN KEY (`trainer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `courses` (`id`,`organisation_id`,`branch_id`,`category_id`,`trainer_user_id`,`form_id`,`title`,`description`,`cover_image`,`materials`,`answers`,`final_exam_questions`,`final_pass_percent`,`is_published`,`visibility`,`enrollment_fee_ksh`,`requires_attachment`,`certificate_enabled`,`requires_full_registration`,`introduction_title`,`introduction_description`,`introduction_video_source`,`introduction_video_path`,`introduction_video_url`,`created_at`,`updated_at`,`featured_on_home`,`approval_status`,`approval_note`,`approved_at`,`final_paper_size`,`course_code`,`final_duration_minutes`,`requirements`) VALUES ('1','1',NULL,'1','2',NULL,'Learn How to Drive Class 102 (Basic Car Controls Part 2)','Learn the basics.\nSecond line.',NULL,NULL,'[]','[{\"type\": \"single_choice\", \"image\": \"\", \"correct\": 1, \"options\": [\"Brakes\", \"Disconnects engine from wheels\", \"Steers\"], \"question\": \"What does the clutch do?\", \"accepted_answers\": []}, {\"type\": \"single_choice\", \"image\": \"\", \"correct\": 0, \"options\": [\"Accelerator\", \"Brake\", \"Clutch\"], \"question\": \"Which pedal is on the right?\", \"accepted_answers\": []}]','50','1','global','10000.00','1','1','0','Welcome','Intro text','youtube',NULL,NULL,'2026-10-13 06:23:34','2026-10-13 06:23:34','0','approved',NULL,NULL,NULL,NULL,NULL,NULL);
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `deletion_requests`;
CREATE TABLE `deletion_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(30) NOT NULL,
  `entity_id` int unsigned NOT NULL,
  `label` varchar(255) NOT NULL,
  `organisation_name` varchar(190) DEFAULT NULL,
  `requested_by_user_id` int unsigned NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `decided_by_admin_id` int unsigned DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_deletion_entity` (`entity_type`,`entity_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `earned_certificates`;
CREATE TABLE `earned_certificates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `certificate_no` varchar(40) NOT NULL,
  `user_id` int unsigned NOT NULL,
  `course_id` int unsigned NOT NULL,
  `branch_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_no` (`certificate_no`),
  UNIQUE KEY `uniq_user_course` (`user_id`,`course_id`),
  KEY `idx_course` (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `form_fields`;
CREATE TABLE `form_fields` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int unsigned NOT NULL,
  `label` varchar(191) NOT NULL,
  `field_key` varchar(191) NOT NULL,
  `field_type` varchar(40) NOT NULL DEFAULT 'text',
  `options` json DEFAULT NULL,
  `placeholder` varchar(191) DEFAULT NULL,
  `help_text` text,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_form_field_key` (`form_id`,`field_key`),
  CONSTRAINT `fk_form_fields_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9902 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('1','1','Business name','business_name','text',NULL,'e.g. Acme Skills Institute',NULL,'1','0','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('2','1','Email','email','email',NULL,NULL,NULL,'1','1','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('3','1','Phone number','phone','phone',NULL,'2547...',NULL,'1','2','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('4','1','Location','location','text',NULL,'e.g. Nairobi, Westlands',NULL,'1','3','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('5','1','Country of origin','country','country',NULL,NULL,NULL,'1','4','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('6','1','KRA pin','kra_pin','text',NULL,'Optional','Optional - leave blank if not available yet.','0','5','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('7','1','Branches','branches','branches',NULL,NULL,'Add every branch this organisation operates from, with its location.','0','6','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('8','2','Business name','business_name','text',NULL,'e.g. Acme Attachment Hub',NULL,'1','0','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('9','2','Email','email','email',NULL,NULL,NULL,'1','1','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('10','2','Phone number','phone','phone',NULL,'2547...',NULL,'1','2','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('11','2','Location','location','text',NULL,'e.g. Nairobi, Industrial Area',NULL,'1','3','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('12','2','Country of origin','country','country',NULL,NULL,NULL,'1','4','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('13','2','KRA pin','kra_pin','text',NULL,'Optional','Optional - leave blank if not available yet.','0','5','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('14','2','Branches','branches','branches',NULL,NULL,'Add every branch that can host attachees, with its location. Assign a branch admin from the Branches page.','0','6','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('20','4','Email address','email','email',NULL,NULL,'Automatically filled in if your organisation already added you.','1','0','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('21','4','Full name','full_name','name',NULL,NULL,'First name, last name, and any other names.','1','1','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('23','4','Country of origin','country','country',NULL,NULL,NULL,'1','3','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('24','4','Phone number','phone','phone',NULL,'2547...',NULL,'1','4','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('28','4','License / service number','license_no','text',NULL,'Optional',NULL,'0','8','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('29','3','Full name','full_name','name',NULL,NULL,'First name, last name, and any other names.','1','0','2026-09-18 15:51:45','2026-09-18 15:51:45');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('31','3','Phone number','phone','phone',NULL,'2547...',NULL,'1','2','2026-09-18 15:51:45','2026-09-18 15:51:45');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('32','3','Country of origin','country','country','{\"choices\": []}',NULL,NULL,'1','3','2026-09-18 15:51:45','2026-09-18 15:51:45');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('33','3','Email address','email','email',NULL,NULL,'Automatically filled from the account email.','1','4','2026-09-18 15:51:45','2026-09-18 15:51:45');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('34','5','Course title','course_title','text',NULL,'e.g. Introduction to First Aid',NULL,'1','0','2026-09-22 13:24:34','2026-09-22 13:24:34');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('35','5','Course description','course_description','paragraph',NULL,NULL,'What learners will gain from this course.','1','1','2026-09-22 13:24:34','2026-09-22 13:24:34');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('36','5','Cover image','course_cover','image',NULL,NULL,'Shown on the course card.','0','2','2026-09-22 13:24:34','2026-09-22 13:24:34');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('37','5','Course materials','course_materials','files',NULL,NULL,'Optional PDFs, images, or Word documents for the whole course.','0','3','2026-09-22 13:24:34','2026-09-22 13:24:34');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('38','5','Category','course_category','category',NULL,NULL,'Choose the category this course falls under, from your organisation\'s categories.','1','1','2026-09-22 13:24:35','2026-09-22 13:24:35');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('39','5','Course duration','course_duration','duration',NULL,NULL,'How long the course takes to complete.','0','3','2026-09-22 13:24:35','2026-09-22 13:24:35');
INSERT INTO `form_fields` (`id`,`form_id`,`label`,`field_key`,`field_type`,`options`,`placeholder`,`help_text`,`is_required`,`sort_order`,`created_at`,`updated_at`) VALUES ('40','2','Kind of attachment you offer','attachment_offered','paragraph',NULL,'e.g. Industrial attachment in electrical engineering, 3 months','Describe the attachment opportunities students can apply for. Students see this when choosing where to go.','1','4','2026-09-24 14:28:13','2026-09-24 14:28:13');
DROP TABLE IF EXISTS `form_responses`;
CREATE TABLE `form_responses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `answers` json DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_form_user` (`form_id`,`user_id`),
  KEY `fk_form_responses_user` (`user_id`),
  CONSTRAINT `fk_form_responses_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_form_responses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `form_responses` (`id`,`form_id`,`user_id`,`answers`,`submitted_at`,`created_at`,`updated_at`) VALUES ('1','1','1',NULL,NULL,'2026-10-13 06:23:32','2026-10-13 06:23:32');
INSERT INTO `form_responses` (`id`,`form_id`,`user_id`,`answers`,`submitted_at`,`created_at`,`updated_at`) VALUES ('2','3','2',NULL,NULL,'2026-10-13 06:23:32','2026-10-13 06:23:32');
INSERT INTO `form_responses` (`id`,`form_id`,`user_id`,`answers`,`submitted_at`,`created_at`,`updated_at`) VALUES ('3','4','3',NULL,NULL,'2026-10-13 06:23:33','2026-10-29 13:49:06');
INSERT INTO `form_responses` (`id`,`form_id`,`user_id`,`answers`,`submitted_at`,`created_at`,`updated_at`) VALUES ('4','2','4',NULL,NULL,'2026-10-13 06:23:33','2026-10-13 06:23:33');
DROP TABLE IF EXISTS `form_roles`;
CREATE TABLE `form_roles` (
  `form_id` int unsigned NOT NULL,
  `role_id` int unsigned NOT NULL,
  PRIMARY KEY (`form_id`,`role_id`),
  KEY `fk_form_roles_role` (`role_id`),
  CONSTRAINT `fk_form_roles_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_form_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `form_roles` (`form_id`,`role_id`) VALUES ('1','1');
INSERT INTO `form_roles` (`form_id`,`role_id`) VALUES ('2','2');
INSERT INTO `form_roles` (`form_id`,`role_id`) VALUES ('5','2');
INSERT INTO `form_roles` (`form_id`,`role_id`) VALUES ('3','3');
INSERT INTO `form_roles` (`form_id`,`role_id`) VALUES ('5','3');
INSERT INTO `form_roles` (`form_id`,`role_id`) VALUES ('4','4');
DROP TABLE IF EXISTS `forms`;
CREATE TABLE `forms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `description` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `purpose` varchar(20) NOT NULL DEFAULT 'profile',
  `created_by_admin_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_forms_admin` (`created_by_admin_id`),
  CONSTRAINT `fk_forms_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `forms` (`id`,`title`,`description`,`is_active`,`purpose`,`created_by_admin_id`,`created_at`,`updated_at`) VALUES ('1','Organisation providing Courses','Tell us about the organisation that will be publishing and running courses on the platform.','1','profile','1','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `forms` (`id`,`title`,`description`,`is_active`,`purpose`,`created_by_admin_id`,`created_at`,`updated_at`) VALUES ('2','Organisation providing Attachments','Tell us about the organisation that will be hosting students for attachment/industrial training. Branch admins can be assigned per branch from the Branches page to approve attachees.','1','profile','1','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `forms` (`id`,`title`,`description`,`is_active`,`purpose`,`created_by_admin_id`,`created_at`,`updated_at`) VALUES ('3','Trainer / registration','Trainer accounts are created by the organisation. This profile confirms the trainer\'s details.','1','profile','1','2026-09-18 15:34:16','2026-09-18 15:51:45');
INSERT INTO `forms` (`id`,`title`,`description`,`is_active`,`purpose`,`created_by_admin_id`,`created_at`,`updated_at`) VALUES ('4','Student registration','Complete your profile to access courses and attachment opportunities.','1','profile','1','2026-09-18 15:34:16','2026-09-18 15:34:16');
INSERT INTO `forms` (`id`,`title`,`description`,`is_active`,`purpose`,`created_by_admin_id`,`created_at`,`updated_at`) VALUES ('5','Create a course','Tutors use this form to create a course for the organisation that approved them.','1','course','1','2026-09-22 13:24:34','2026-09-22 13:24:34');
DROP TABLE IF EXISTS `issued_certificates`;
CREATE TABLE `issued_certificates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `certificate_no` varchar(40) NOT NULL,
  `batch_id` varchar(20) NOT NULL,
  `recipient_name` varchar(190) NOT NULL,
  `id_number` varchar(80) DEFAULT NULL,
  `skills` varchar(500) NOT NULL,
  `organisation_id` int unsigned DEFAULT NULL,
  `organisation_name` varchar(190) DEFAULT NULL,
  `issued_on` date NOT NULL,
  `issued_by_admin_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_no` (`certificate_no`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_name` (`recipient_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `applied_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('1','0001_create_admins_table','2026-08-16 21:34:08');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('2','0002_create_categories_table','2026-08-16 21:34:08');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('3','0003_create_products_table','2026-08-16 21:34:08');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('4','0004_create_customers_table','2026-08-16 21:34:08');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('5','0005_create_otp_codes_table','2026-08-16 21:34:08');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('6','0006_create_orders_table','2026-08-16 21:34:09');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('7','0007_create_order_items_table','2026-08-16 21:34:09');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('8','0008_create_seo_meta_table','2026-08-16 21:34:09');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('9','0009_add_offer_fields_to_products','2026-08-16 21:34:09');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('10','0010_create_store_settings_table','2026-08-16 21:34:09');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('11','0011_add_occasion_to_products','2026-08-16 21:34:09');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('12','0012_add_category_keys_to_products','2026-08-16 21:34:09');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('13','0013_create_roles_table','2026-08-16 22:21:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('14','0014_seed_lms_roles','2026-08-16 22:21:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('15','0015_create_organisations_table','2026-08-16 22:21:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('16','0016_create_users_table','2026-08-16 22:21:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('17','0017_create_forms_table','2026-08-16 22:21:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('18','0018_create_form_roles_table','2026-08-16 22:21:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('19','0019_create_form_fields_table','2026-08-16 22:21:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('20','0020_create_form_responses_table','2026-08-16 22:21:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('21','0021_create_user_otp_codes_table','2026-08-16 22:21:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('22','0022_add_name_to_admins','2026-08-16 22:21:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('23','0023_expand_form_field_types','2026-08-16 22:58:49');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('24','0024_add_user_passwords','2026-08-16 23:46:57');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('25','0025_create_organisation_admin_invites_table','2026-08-16 23:46:57');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('26','0026_create_organisation_memberships_table','2026-08-17 09:33:12');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('27','0027_seed_trainer_registration_form','2026-08-17 09:33:12');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('28','0028_create_courses_and_organisation_categories','2026-08-17 13:19:30');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('29','0029_course_visibility_branches_and_progress','2026-08-27 08:15:05');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('30','0030_attachment_trainers_and_certificates','2026-08-27 08:15:05');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('31','0031_branch_form_details','2026-08-27 11:03:20');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('32','0032_attachment_trainer_profile_form','2026-08-27 11:15:43');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('33','0033_ensure_organisation_admin_branches_field','2026-08-27 23:22:48');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('34','0034_branch_ownership_and_attachment_form','2026-09-07 12:13:47');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('35','0035_generic_branch_field_copy','2026-09-07 12:35:25');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('36','0036_duration_amount_unit','2026-09-07 12:53:57');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('37','0037_student_attachment_selection','2026-09-07 13:06:00');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('38','0038_user_account_status','2026-09-07 13:13:59');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('39','0039_standalone_user_branches','2026-09-07 20:48:26');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('40','0040_course_final_exam_and_80_percent','2026-09-09 03:37:15');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('41','0041_create_course_enrollments','2026-09-09 03:42:50');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('42','0042_course_fees_intro_and_payment_fields','2026-09-09 04:29:49');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('43','0043_attachment_course_certification','2026-09-15 15:14:36');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('44','0044_branch_admin_role','2026-09-15 16:31:14');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('45','0045_rename_role_display_labels','2026-09-15 16:31:14');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('46','0046_course_full_registration','2026-09-15 16:31:15');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('47','0047_reset_default_role_forms','2026-09-18 15:34:16');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('48','0048_rename_roles_and_tutor_form','2026-09-18 15:52:01');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('49','0049_move_organisation_attachment_to_nav','2026-09-22 13:24:34');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('50','0050_org_admin_scope_and_forced_password','2026-09-22 13:24:34');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('51','0051_name_field_includes_other_names','2026-09-22 13:24:34');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('52','0052_add_other_names_to_users','2026-09-22 13:24:34');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('53','0053_remove_course_category_requirement','2026-09-22 13:24:34');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('54','0054_ensure_default_course_form','2026-09-22 13:24:34');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('55','0055_organisation_visibility_and_categories','2026-09-22 13:24:35');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('56','0056_restore_course_category_field','2026-09-22 13:24:35');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('57','0057_course_duration_and_wording_fixes','2026-09-22 13:24:35');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('58','0058_category_open_flag_and_tutor_role_name','2026-09-24 14:28:13');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('59','0059_course_multi_category','2026-09-24 14:28:13');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('60','0060_course_branch_admin_role','2026-09-24 14:28:13');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('61','0061_repair_branch_admin_role_mismatch','2026-09-24 14:28:13');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('62','0062_attachment_independent_of_course','2026-09-24 14:28:13');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('63','0063_attachment_offered_field','2026-09-24 14:28:13');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('64','0064_create_wallet_transactions','2026-09-24 15:20:39');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('65','0065_attachment_audience_and_category','2026-09-25 17:39:19');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('66','0066_attachment_providers_leave_course_orgs','2026-09-25 17:39:19');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('67','0067_attachment_messages_and_pause','2026-09-25 17:39:19');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('68','0068_admin_permissions_and_homepage_courses','2026-10-04 12:54:26');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('69','0069_module_payments_and_registration_numbers','2026-10-04 12:54:26');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('70','0070_public_profiles_and_partner_approval','2026-10-04 12:54:26');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('71','0071_course_attachments_retakes_completions','2026-10-04 12:54:26');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('72','0072_single_session_and_course_approval','2026-10-04 12:54:26');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('73','0073_lock_duplicate_accounts','2026-10-13 06:23:03');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('74','0074_exam_papers_and_attachment_assessments','2026-10-13 06:23:03');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('75','0075_course_codes','2026-10-13 06:23:03');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('76','0076_receipts_deletion_requests_role_name','2026-10-13 06:23:03');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('77','0077_exam_duration_pass_mark','2026-10-13 06:23:03');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('78','0078_course_requirements_certificates','2026-10-13 06:23:03');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('79','0079_public_lookup_contact_links','2026-10-13 06:23:03');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('80','0080_newsletter_new_landing','2026-10-13 06:23:03');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('81','0081_profile_gender_radio_id_document','2026-10-13 06:23:03');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('82','0082_certificate_numbers_course_branches','2026-10-15 15:23:39');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('83','0083_typed_course_codes','2026-10-29 13:46:21');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('84','0084_detail_change_requests','2026-10-29 13:46:21');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('85','0085_system_integrity_baseline','2026-10-29 13:46:21');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('86','0086_security_patch_2_1','2026-10-29 13:46:22');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('87','0087_unify_collations','2026-10-29 13:46:22');
INSERT INTO `migrations` (`id`,`migration`,`applied_at`) VALUES ('88','0088_lookup_by_id_document','2026-10-29 13:48:38');
DROP TABLE IF EXISTS `newsletter_subscribers`;
CREATE TABLE `newsletter_subscribers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `product_id` int unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_image` varchar(500) DEFAULT NULL,
  `color_name` varchar(100) DEFAULT NULL,
  `size_label` varchar(100) DEFAULT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_ref` varchar(30) NOT NULL,
  `customer_id` int unsigned NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `currency` varchar(5) NOT NULL DEFAULT 'KSH',
  `payment_method` varchar(100) DEFAULT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` varchar(255) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_postal_code` varchar(20) DEFAULT NULL,
  `shipping_country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_ref` (`order_ref`),
  KEY `fk_orders_customer` (`customer_id`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `organisation_admin_invites`;
CREATE TABLE `organisation_admin_invites` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `used_by_user_id` int unsigned DEFAULT NULL,
  `created_by_admin_id` int unsigned NOT NULL,
  `emailed_to` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_oai_token_hash` (`token_hash`),
  KEY `idx_oai_org` (`organisation_id`),
  KEY `fk_oai_admin` (`created_by_admin_id`),
  KEY `fk_oai_user` (`used_by_user_id`),
  CONSTRAINT `fk_oai_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oai_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oai_user` FOREIGN KEY (`used_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `organisation_branches`;
CREATE TABLE `organisation_branches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int unsigned DEFAULT NULL,
  `owner_type` varchar(32) NOT NULL DEFAULT 'organisation',
  `owner_user_id` int unsigned DEFAULT NULL,
  `branch_admin_user_id` int unsigned DEFAULT NULL,
  `branch_admin_name` varchar(191) DEFAULT NULL,
  `branch_admin_email` varchar(191) DEFAULT NULL,
  `branch_admin_phone` varchar(50) DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `location` varchar(255) NOT NULL,
  `details` json DEFAULT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_org_branches_org` (`organisation_id`),
  KEY `idx_org_branches_owner` (`owner_type`,`owner_user_id`),
  CONSTRAINT `fk_org_branches_org` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `organisation_branches` (`id`,`organisation_id`,`owner_type`,`owner_user_id`,`branch_admin_user_id`,`branch_admin_name`,`branch_admin_email`,`branch_admin_phone`,`title`,`location`,`details`,`cover_image`,`sort_order`,`created_at`,`updated_at`) VALUES ('1','1','organisation',NULL,'5','Carl Branchadmin','uitest-cbadmin@example.test','0712000000','Nairobi CBD Branch','Moi Avenue, Nairobi','{\"Hours\": \"8am - 5pm\"}',NULL,'0','2026-10-13 06:23:34','2026-10-13 06:23:34');
INSERT INTO `organisation_branches` (`id`,`organisation_id`,`owner_type`,`owner_user_id`,`branch_admin_user_id`,`branch_admin_name`,`branch_admin_email`,`branch_admin_phone`,`title`,`location`,`details`,`cover_image`,`sort_order`,`created_at`,`updated_at`) VALUES ('2',NULL,'attachment_provider','4','6','Bella','uitest-badmin@example.test','0712000001','Mombasa Workshop','Mombasa',NULL,NULL,'0','2026-10-13 06:23:34','2026-10-13 06:23:34');
DROP TABLE IF EXISTS `organisation_categories`;
CREATE TABLE `organisation_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_open` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_org_category_slug` (`organisation_id`,`slug`),
  KEY `idx_org_cat_org` (`organisation_id`),
  CONSTRAINT `fk_org_cat_org` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `organisation_categories` (`id`,`organisation_id`,`name`,`slug`,`description`,`is_active`,`is_open`,`sort_order`,`created_at`,`updated_at`) VALUES ('1','1','Driving','driving','Driving courses','1','1','0','2026-10-13 06:23:31','2026-10-13 06:23:31');
DROP TABLE IF EXISTS `organisation_memberships`;
CREATE TABLE `organisation_memberships` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `organisation_id` int unsigned NOT NULL,
  `branch_id` int unsigned DEFAULT NULL,
  `category_ids` json DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` int unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_org_membership` (`user_id`,`organisation_id`),
  KEY `idx_om_org_status` (`organisation_id`,`status`),
  KEY `idx_om_user` (`user_id`),
  KEY `fk_om_reviewer` (`reviewed_by_user_id`),
  KEY `fk_om_branch` (`branch_id`),
  CONSTRAINT `fk_om_branch` FOREIGN KEY (`branch_id`) REFERENCES `organisation_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_om_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_om_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_om_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `organisation_memberships` (`id`,`user_id`,`organisation_id`,`branch_id`,`category_ids`,`status`,`reviewed_by_user_id`,`reviewed_at`,`created_at`,`updated_at`) VALUES ('1','2','1',NULL,NULL,'approved',NULL,'2026-10-13 06:23:34','2026-10-13 06:23:34','2026-10-13 06:23:34');
INSERT INTO `organisation_memberships` (`id`,`user_id`,`organisation_id`,`branch_id`,`category_ids`,`status`,`reviewed_by_user_id`,`reviewed_at`,`created_at`,`updated_at`) VALUES ('2','3','1',NULL,'[1]','approved',NULL,'2026-10-13 06:23:34','2026-10-13 06:23:34','2026-10-13 06:23:34');
DROP TABLE IF EXISTS `organisations`;
CREATE TABLE `organisations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `visible_to_students` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `location` varchar(190) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `course_code_from` int unsigned DEFAULT NULL,
  `course_code_to` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `organisations` (`id`,`name`,`slug`,`description`,`is_active`,`visible_to_students`,`created_at`,`updated_at`,`phone`,`email`,`location`,`website`,`logo_path`,`course_code_from`,`course_code_to`) VALUES ('1','UITest Driving School','uitest-driving','A test organisation for mobile UI checks.','1','1','2026-10-13 06:23:31','2026-10-13 06:23:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
DROP TABLE IF EXISTS `otp_codes`;
CREATE TABLE `otp_codes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int unsigned NOT NULL,
  `code` varchar(255) NOT NULL,
  `purpose` enum('login','password_reset') NOT NULL DEFAULT 'login',
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_otp_customer` (`customer_id`),
  CONSTRAINT `fk_otp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `payment_receipts`;
CREATE TABLE `payment_receipts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(40) NOT NULL,
  `user_id` int unsigned NOT NULL,
  `course_id` int unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount_ksh` decimal(12,2) NOT NULL,
  `kind` enum('deposit','course_payment') NOT NULL DEFAULT 'course_payment',
  `reference` varchar(100) DEFAULT NULL,
  `emailed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_receipt_no` (`receipt_no`),
  KEY `idx_receipts_user` (`user_id`,`created_at`),
  CONSTRAINT `fk_receipt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `category_key` varchar(50) NOT NULL,
  `category_keys` json DEFAULT NULL,
  `sub_category` varchar(150) DEFAULT NULL,
  `occasion` varchar(80) DEFAULT NULL,
  `description` text,
  `details` json DEFAULT NULL,
  `fabric` varchar(255) DEFAULT NULL,
  `fit` varchar(255) DEFAULT NULL,
  `sizes` json DEFAULT NULL,
  `colors` json DEFAULT NULL,
  `images` json DEFAULT NULL,
  `offer_image` varchar(255) DEFAULT NULL,
  `offer_ends_at` datetime DEFAULT NULL,
  `is_new` tinyint(1) NOT NULL DEFAULT '0',
  `is_best_seller` tinyint(1) NOT NULL DEFAULT '0',
  `is_sale` tinyint(1) NOT NULL DEFAULT '0',
  `in_stock` tinyint(1) NOT NULL DEFAULT '1',
  `featured_in_lookbook` tinyint(1) NOT NULL DEFAULT '0',
  `rating` decimal(2,1) NOT NULL DEFAULT '5.0',
  `review_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `fk_products_category` (`category_key`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_key`) REFERENCES `categories` (`category_key`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text,
  `has_admin_features` tinyint(1) NOT NULL DEFAULT '0',
  `is_under_organisation` tinyint(1) NOT NULL DEFAULT '1',
  `can_manage_users` tinyint(1) NOT NULL DEFAULT '0',
  `managed_role_slugs` json DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `roles` (`id`,`slug`,`name`,`description`,`has_admin_features`,`is_under_organisation`,`can_manage_users`,`managed_role_slugs`,`sort_order`,`created_at`,`updated_at`) VALUES ('1','organisation_admin','Organisations providing courses','Manages an organisation and the trainers, attachment trainers, and students under it. Has admin-like tools inside that organisation.','1','0','1','[\"trainer\", \"student\"]','10','2026-08-16 22:21:01','2026-09-22 13:24:34');
INSERT INTO `roles` (`id`,`slug`,`name`,`description`,`has_admin_features`,`is_under_organisation`,`can_manage_users`,`managed_role_slugs`,`sort_order`,`created_at`,`updated_at`) VALUES ('2','attachment_trainer','Organisation providing Attachment','Hosts workplace attachment after a student finishes their course. Profile form should include attachment duration (start and end dates). Students only see attachment trainers once they have completed a course.','0','1','0','[\"branch_admin\"]','20','2026-08-16 22:21:01','2026-09-18 15:52:01');
INSERT INTO `roles` (`id`,`slug`,`name`,`description`,`has_admin_features`,`is_under_organisation`,`can_manage_users`,`managed_role_slugs`,`sort_order`,`created_at`,`updated_at`) VALUES ('3','trainer','Tutors Creating Courses Portal','Delivers training. Lives under organisation power and does not have admin tools.','0','1','0','[]','30','2026-08-16 22:21:01','2026-09-24 14:28:13');
INSERT INTO `roles` (`id`,`slug`,`name`,`description`,`has_admin_features`,`is_under_organisation`,`can_manage_users`,`managed_role_slugs`,`sort_order`,`created_at`,`updated_at`) VALUES ('4','student','Student/Learner/Employee','Learner account. Lives under organisation power and fills assigned profile forms.','0','1','0','[]','40','2026-08-16 22:21:01','2026-09-22 13:24:35');
INSERT INTO `roles` (`id`,`slug`,`name`,`description`,`has_admin_features`,`is_under_organisation`,`can_manage_users`,`managed_role_slugs`,`sort_order`,`created_at`,`updated_at`) VALUES ('5','branch_admin','Branch Admin (Attachment)','Manages attachees at a single branch: accepts and marks their attachment complete. Assigned by an organisation admin or attachment provider.','0','1','0','[]','21','2026-09-15 16:31:14','2026-10-13 06:23:03');
INSERT INTO `roles` (`id`,`slug`,`name`,`description`,`has_admin_features`,`is_under_organisation`,`can_manage_users`,`managed_role_slugs`,`sort_order`,`created_at`,`updated_at`) VALUES ('6','course_branch_admin','Branch Admin (Courses)','Approves students at one branch of an organisation providing courses, and manages that organisation\'s course categories.','1','1','0','[]','25','2026-09-24 14:28:13','2026-09-24 14:28:13');
DROP TABLE IF EXISTS `seo_meta`;
CREATE TABLE `seo_meta` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `page_key` varchar(191) NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `meta_keywords` varchar(500) DEFAULT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_key` (`page_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `store_settings`;
CREATE TABLE `store_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `store_settings` (`setting_key`,`setting_value`,`updated_at`) VALUES ('final_grade_exam_percent','90','2026-10-29 13:50:00');
INSERT INTO `store_settings` (`setting_key`,`setting_value`,`updated_at`) VALUES ('hero_image','assets/uploads/settings/bff1efef50c886e7b792.jpg','2026-09-25 05:30:29');
INSERT INTO `store_settings` (`setting_key`,`setting_value`,`updated_at`) VALUES ('min_first_payment_percent','40','2026-09-25 17:39:19');
INSERT INTO `store_settings` (`setting_key`,`setting_value`,`updated_at`) VALUES ('nav_order_student','[\"courses\",\"course_organisations\",\"wallet\",\"attachment_providers\",\"certificate\"]','2026-09-25 05:39:26');
INSERT INTO `store_settings` (`setting_key`,`setting_value`,`updated_at`) VALUES ('platform_name','Ujuzi training','2026-08-17 13:46:18');
INSERT INTO `store_settings` (`setting_key`,`setting_value`,`updated_at`) VALUES ('sys_integrity_contact','admin@dazuhub.com','2026-10-29 13:46:21');
INSERT INTO `store_settings` (`setting_key`,`setting_value`,`updated_at`) VALUES ('sys_integrity_state','0','2026-10-29 13:46:21');
INSERT INTO `store_settings` (`setting_key`,`setting_value`,`updated_at`) VALUES ('sys_integrity_token','$2y$12$PGwiZtDFYjBLik9ELQlnNO72QIT/Zdhj9NfFEdVT.UVv/Z576L5d2','2026-10-29 13:46:22');
DROP TABLE IF EXISTS `user_change_requests`;
CREATE TABLE `user_change_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `changes` json NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` varchar(500) DEFAULT NULL,
  `decided_by` int unsigned DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_change_requests_status` (`status`,`created_at`),
  KEY `idx_change_requests_user` (`user_id`,`status`),
  CONSTRAINT `fk_change_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `user_otp_codes`;
CREATE TABLE `user_otp_codes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `code` varchar(255) NOT NULL,
  `purpose` enum('login','password_reset','password_change') NOT NULL DEFAULT 'login',
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_user_otp_user` (`user_id`),
  CONSTRAINT `fk_user_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned NOT NULL,
  `organisation_id` int unsigned DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `phone` varchar(30) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `other_names` varchar(191) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `account_status` enum('active','blocked','suspended') NOT NULL DEFAULT 'active',
  `dashboard_created_at` timestamp NULL DEFAULT NULL,
  `profile_created_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `registration_seq` int unsigned DEFAULT NULL,
  `registration_number` varchar(32) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `headline` varchar(160) DEFAULT NULL,
  `bio` text,
  `experience` text,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `social_url` varchar(255) DEFAULT NULL,
  `session_token` varchar(64) DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `lock_reason` varchar(255) DEFAULT NULL,
  `pending_email` varchar(255) DEFAULT NULL,
  `email_verify_token` varchar(64) DEFAULT NULL,
  `email_verify_expires` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `uniq_registration_seq` (`registration_seq`),
  UNIQUE KEY `uniq_registration_number` (`registration_number`),
  KEY `fk_users_role` (`role_id`),
  KEY `fk_users_organisation` (`organisation_id`),
  CONSTRAINT `fk_users_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `users` (`id`,`role_id`,`organisation_id`,`email`,`password_hash`,`must_change_password`,`phone`,`first_name`,`last_name`,`other_names`,`email_verified_at`,`is_active`,`account_status`,`dashboard_created_at`,`profile_created_at`,`last_login_at`,`created_at`,`updated_at`,`registration_seq`,`registration_number`,`photo_path`,`headline`,`bio`,`experience`,`linkedin_url`,`social_url`,`session_token`,`locked_at`,`lock_reason`,`pending_email`,`email_verify_token`,`email_verify_expires`) VALUES ('1','1','1','uitest-org@example.test','$2y$12$lqjeIqU5K3ppSQqnhgqlrOCbLn0kWYhGmqLSERPbI4sET0DZHjTTu','0','+254712374852','Olivia','Orgadmin',NULL,'2026-10-13 06:23:32','1','active','2026-10-13 06:23:32','2026-10-13 06:23:32','2026-10-13 06:36:24','2026-10-13 06:23:32','2026-10-29 13:55:01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9743b0120232e80a60dd3f7bac356bb5',NULL,NULL,NULL,NULL,NULL);
INSERT INTO `users` (`id`,`role_id`,`organisation_id`,`email`,`password_hash`,`must_change_password`,`phone`,`first_name`,`last_name`,`other_names`,`email_verified_at`,`is_active`,`account_status`,`dashboard_created_at`,`profile_created_at`,`last_login_at`,`created_at`,`updated_at`,`registration_seq`,`registration_number`,`photo_path`,`headline`,`bio`,`experience`,`linkedin_url`,`social_url`,`session_token`,`locked_at`,`lock_reason`,`pending_email`,`email_verify_token`,`email_verify_expires`) VALUES ('2','3','1','uitest-tutor@example.test','$2y$12$M8cZqtZttf7GKbx9OgAiFuMgxmAH6VfrY0YZ/npeOQwkE0ohdyuS6','0','+254712666511','Tom','Tutorson',NULL,'2026-10-13 06:23:32','1','active','2026-10-13 06:23:32','2026-10-13 06:23:32','2026-10-13 06:36:07','2026-10-13 06:23:32','2026-10-29 13:55:02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'6dc2d86c2dffd738f0c3704de7a1bac5',NULL,NULL,NULL,NULL,NULL);
INSERT INTO `users` (`id`,`role_id`,`organisation_id`,`email`,`password_hash`,`must_change_password`,`phone`,`first_name`,`last_name`,`other_names`,`email_verified_at`,`is_active`,`account_status`,`dashboard_created_at`,`profile_created_at`,`last_login_at`,`created_at`,`updated_at`,`registration_seq`,`registration_number`,`photo_path`,`headline`,`bio`,`experience`,`linkedin_url`,`social_url`,`session_token`,`locked_at`,`lock_reason`,`pending_email`,`email_verify_token`,`email_verify_expires`) VALUES ('3','4','1','uitest-student@example.test','$2y$12$BCqR0BZ7ArvKf7djvfW0Guhrb7NqH8K85UJtVQShgx6nv1.rignJS','0','+254712829335','Sarah','Studentwithaverylongname',NULL,'2026-10-13 06:23:33','1','active','2026-10-13 06:23:33','2026-10-13 06:23:33','2026-10-13 06:37:42','2026-10-13 06:23:33','2026-10-29 13:55:03','1','UJ0011310/26',NULL,NULL,NULL,NULL,NULL,NULL,'8fbd74fa609cc5c1d3b7a8433aaead57',NULL,NULL,NULL,NULL,NULL);
INSERT INTO `users` (`id`,`role_id`,`organisation_id`,`email`,`password_hash`,`must_change_password`,`phone`,`first_name`,`last_name`,`other_names`,`email_verified_at`,`is_active`,`account_status`,`dashboard_created_at`,`profile_created_at`,`last_login_at`,`created_at`,`updated_at`,`registration_seq`,`registration_number`,`photo_path`,`headline`,`bio`,`experience`,`linkedin_url`,`social_url`,`session_token`,`locked_at`,`lock_reason`,`pending_email`,`email_verify_token`,`email_verify_expires`) VALUES ('4','2',NULL,'uitest-provider@example.test','$2y$12$.ruHNfGI0fVljA6dIC2emun3ZSrVx2kzESR3F3Xa3Q6F38XAhLPcm','0','+254712360114','Peter','Provider',NULL,'2026-10-13 06:23:33','1','active','2026-10-13 06:23:33','2026-10-13 06:23:33','2026-10-13 06:36:37','2026-10-13 06:23:33','2026-10-29 13:55:03',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'50cb1bba97dbec1c9e106448f5ef405f',NULL,NULL,NULL,NULL,NULL);
INSERT INTO `users` (`id`,`role_id`,`organisation_id`,`email`,`password_hash`,`must_change_password`,`phone`,`first_name`,`last_name`,`other_names`,`email_verified_at`,`is_active`,`account_status`,`dashboard_created_at`,`profile_created_at`,`last_login_at`,`created_at`,`updated_at`,`registration_seq`,`registration_number`,`photo_path`,`headline`,`bio`,`experience`,`linkedin_url`,`social_url`,`session_token`,`locked_at`,`lock_reason`,`pending_email`,`email_verify_token`,`email_verify_expires`) VALUES ('5','6',NULL,'uitest-cbadmin@example.test','$2y$12$EC7DmiZZ9s2xG2O7cmEku.5DYy7odPcXPEwm9XVR8OmLLJ6v/bTI.','0','+254712777246','Carl','Branchadmin',NULL,'2026-10-13 06:23:33','1','active','2026-10-13 06:23:33','2026-10-13 06:23:33','2026-10-13 06:36:43','2026-10-13 06:23:33','2026-10-29 13:55:03',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'4d38ca6eaa8fb2b573bfd8b0a0be5e3c',NULL,NULL,NULL,NULL,NULL);
INSERT INTO `users` (`id`,`role_id`,`organisation_id`,`email`,`password_hash`,`must_change_password`,`phone`,`first_name`,`last_name`,`other_names`,`email_verified_at`,`is_active`,`account_status`,`dashboard_created_at`,`profile_created_at`,`last_login_at`,`created_at`,`updated_at`,`registration_seq`,`registration_number`,`photo_path`,`headline`,`bio`,`experience`,`linkedin_url`,`social_url`,`session_token`,`locked_at`,`lock_reason`,`pending_email`,`email_verify_token`,`email_verify_expires`) VALUES ('6','5',NULL,'uitest-badmin@example.test','$2y$12$uPsAWtOOm4G29rmifmGmUuE5YUzJKcqxyxziJrii4TDqkIsIwFPhi','0','+254712651328','Bella','Attachadmin',NULL,'2026-10-13 06:23:34','1','active','2026-10-13 06:23:34','2026-10-13 06:23:34','2026-10-13 06:36:50','2026-10-13 06:23:34','2026-10-29 13:55:04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'000efea1cd6fba4ef7244a75ed23e826',NULL,NULL,NULL,NULL,NULL);
DROP TABLE IF EXISTS `wallet_transactions`;
CREATE TABLE `wallet_transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `type` enum('deposit','course_payment','earning') NOT NULL,
  `amount_ksh` decimal(12,2) NOT NULL,
  `course_id` int unsigned DEFAULT NULL,
  `organisation_id` int unsigned DEFAULT NULL,
  `payer_user_id` int unsigned DEFAULT NULL,
  `provider` varchar(30) NOT NULL DEFAULT 'simulation',
  `reference` varchar(100) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wallet_user` (`user_id`,`type`),
  KEY `idx_wallet_course` (`course_id`),
  KEY `idx_wallet_org` (`organisation_id`,`type`),
  CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
SET FOREIGN_KEY_CHECKS=1;
