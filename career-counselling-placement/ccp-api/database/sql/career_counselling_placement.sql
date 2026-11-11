/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `advisor_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `advisor_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `advisor_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `started_at` timestamp NOT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `active_student_id` bigint(20) unsigned GENERATED ALWAYS AS (case when `status` = 'active' then `student_id` else NULL end) STORED,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `advisor_assignments_active_student_id_unique` (`active_student_id`),
  KEY `advisor_assignments_student_id_foreign` (`student_id`),
  KEY `advisor_assignments_advisor_id_foreign` (`advisor_id`),
  KEY `advisor_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `advisor_assignments_status_index` (`status`),
  CONSTRAINT `advisor_assignments_advisor_id_foreign` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `advisor_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `advisor_assignments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `advisor_assignments` DISABLE KEYS */;
INSERT INTO `advisor_assignments` VALUES
(1,9,2,1,'active','2026-10-29 11:56:24',NULL,9,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(2,10,2,1,'active','2026-10-29 11:56:24',NULL,10,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(3,11,2,1,'active','2026-10-29 11:56:25',NULL,11,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(4,12,2,1,'active','2026-10-29 11:56:25',NULL,12,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(5,13,2,1,'active','2026-10-29 11:56:25',NULL,13,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(6,14,2,1,'active','2026-10-29 11:56:25',NULL,14,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(7,15,2,1,'active','2026-10-29 11:56:26',NULL,15,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(8,16,2,1,'active','2026-10-29 11:56:26',NULL,16,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(9,17,2,1,'active','2026-10-29 11:56:26',NULL,17,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(10,18,2,1,'active','2026-10-29 11:56:26',NULL,18,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(11,19,2,1,'active','2026-10-29 11:56:27',NULL,19,'2026-10-29 11:56:27','2026-10-29 11:56:27');
/*!40000 ALTER TABLE `advisor_assignments` ENABLE KEYS */;
DROP TABLE IF EXISTS `advisor_expertise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `advisor_expertise` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `advisor_id` bigint(20) unsigned NOT NULL,
  `tag` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `advisor_expertise_advisor_id_tag_unique` (`advisor_id`,`tag`),
  CONSTRAINT `advisor_expertise_advisor_id_foreign` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `advisor_expertise` DISABLE KEYS */;
INSERT INTO `advisor_expertise` VALUES
(1,2,'Software engineering','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(2,8,'Software engineering','2026-10-29 11:56:24','2026-10-29 11:56:24');
/*!40000 ALTER TABLE `advisor_expertise` ENABLE KEYS */;
DROP TABLE IF EXISTS `advisor_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `advisor_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `advisor_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `advisor_notes_advisor_id_foreign` (`advisor_id`),
  KEY `advisor_notes_student_id_foreign` (`student_id`),
  CONSTRAINT `advisor_notes_advisor_id_foreign` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `advisor_notes_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `advisor_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `advisor_notes` ENABLE KEYS */;
DROP TABLE IF EXISTS `advisor_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `advisor_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `advisor_id` bigint(20) unsigned NOT NULL,
  `rank` tinyint(3) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `advisor_preferences_student_id_rank_unique` (`student_id`,`rank`),
  UNIQUE KEY `advisor_preferences_student_id_advisor_id_unique` (`student_id`,`advisor_id`),
  KEY `advisor_preferences_advisor_id_foreign` (`advisor_id`),
  KEY `advisor_preferences_status_index` (`status`),
  CONSTRAINT `advisor_preferences_advisor_id_foreign` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `advisor_preferences_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `advisor_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `advisor_preferences` ENABLE KEYS */;
DROP TABLE IF EXISTS `advisor_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `advisor_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `capacity` int(10) unsigned NOT NULL DEFAULT 20,
  `is_accepting` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `advisor_profiles_user_id_unique` (`user_id`),
  CONSTRAINT `advisor_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `advisor_profiles` DISABLE KEYS */;
INSERT INTO `advisor_profiles` VALUES
(1,2,'Career Advisor','Fictional advisor for local demonstrations.',20,1,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(2,8,'Career Advisor','Fictional advisor for local demonstrations.',20,1,'2026-10-29 11:56:24','2026-10-29 11:56:24');
/*!40000 ALTER TABLE `advisor_profiles` ENABLE KEYS */;
DROP TABLE IF EXISTS `alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alerts_user_id_foreign` (`user_id`),
  CONSTRAINT `alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `alerts` ENABLE KEYS */;
DROP TABLE IF EXISTS `application_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_status_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(255) DEFAULT NULL,
  `to_status` varchar(255) NOT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_status_history_application_id_foreign` (`application_id`),
  KEY `application_status_history_actor_id_foreign` (`actor_id`),
  CONSTRAINT `application_status_history_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `application_status_history_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `application_status_history` DISABLE KEYS */;
INSERT INTO `application_status_history` VALUES
(1,1,NULL,'submitted',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(2,2,NULL,'corrections_requested',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(3,3,NULL,'rejected_by_university',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(4,4,NULL,'forwarded',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(5,5,NULL,'shortlisted',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(6,6,NULL,'interview',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(7,7,NULL,'offer',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(8,8,NULL,'offer_accepted',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(9,9,NULL,'offer_declined',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(10,10,NULL,'rejected_by_company',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(11,11,NULL,'withdrawn',1,'Demo fixture, not a live workflow event.','2026-10-29 11:56:27','2026-10-29 11:56:27');
/*!40000 ALTER TABLE `application_status_history` ENABLE KEYS */;
DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `opportunity_id` bigint(20) unsigned NOT NULL,
  `cv_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Applied',
  `admin_review` varchar(255) NOT NULL DEFAULT 'pending',
  `forwarded_at` timestamp NULL DEFAULT NULL,
  `cover_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `eligibility_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`eligibility_snapshot`)),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `workflow_status` varchar(255) DEFAULT NULL,
  `admin_decision` varchar(255) DEFAULT NULL,
  `admin_reason` text DEFAULT NULL,
  `admin_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `admin_reviewed_at` timestamp NULL DEFAULT NULL,
  `forwarded_by` bigint(20) unsigned DEFAULT NULL,
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applications_user_id_opportunity_id_unique` (`user_id`,`opportunity_id`),
  UNIQUE KEY `applications_student_id_opportunity_id_unique` (`student_id`,`opportunity_id`),
  KEY `applications_opportunity_id_foreign` (`opportunity_id`),
  KEY `applications_cv_id_foreign` (`cv_id`),
  KEY `applications_admin_reviewed_by_foreign` (`admin_reviewed_by`),
  KEY `applications_forwarded_by_foreign` (`forwarded_by`),
  KEY `applications_status_index` (`status`),
  KEY `applications_workflow_status_index` (`workflow_status`),
  CONSTRAINT `applications_admin_reviewed_by_foreign` FOREIGN KEY (`admin_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_cv_id_foreign` FOREIGN KEY (`cv_id`) REFERENCES `cvs` (`id`),
  CONSTRAINT `applications_forwarded_by_foreign` FOREIGN KEY (`forwarded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `applications` DISABLE KEYS */;
INSERT INTO `applications` VALUES
(1,9,2,1,'Applied','pending',NULL,NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24',9,NULL,'2026-10-29 11:56:24','submitted',NULL,NULL,NULL,NULL,NULL,NULL),
(2,10,2,2,'Applied','pending',NULL,NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24',10,NULL,'2026-10-29 11:56:24','corrections_requested',NULL,NULL,NULL,NULL,NULL,NULL),
(3,11,2,3,'Rejected','rejected',NULL,NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25',11,NULL,'2026-10-29 11:56:25','rejected_by_university',NULL,NULL,NULL,NULL,NULL,NULL),
(4,12,2,4,'Applied','approved','2026-10-29 11:56:25',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25',12,NULL,'2026-10-29 11:56:25','forwarded',NULL,NULL,NULL,NULL,1,NULL),
(5,13,2,5,'Shortlisted','approved','2026-10-29 11:56:25',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25',13,NULL,'2026-10-29 11:56:25','shortlisted',NULL,NULL,NULL,NULL,1,NULL),
(6,14,2,6,'Interview','approved','2026-10-29 11:56:25',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25',14,NULL,'2026-10-29 11:56:25','interview',NULL,NULL,NULL,NULL,1,NULL),
(7,15,2,7,'Offer','approved','2026-10-29 11:56:26',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26',15,NULL,'2026-10-29 11:56:26','offer',NULL,NULL,NULL,NULL,1,NULL),
(8,16,2,8,'Offer','approved','2026-10-29 11:56:26',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26',16,NULL,'2026-10-29 11:56:26','offer_accepted',NULL,NULL,NULL,NULL,1,NULL),
(9,17,2,9,'Offer','approved','2026-10-29 11:56:26',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26',17,NULL,'2026-10-29 11:56:26','offer_declined',NULL,NULL,NULL,NULL,1,NULL),
(10,18,2,10,'Rejected','approved','2026-10-29 11:56:26',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26',18,NULL,'2026-10-29 11:56:26','rejected_by_company',NULL,NULL,NULL,NULL,1,NULL),
(11,19,2,11,'Applied','approved','2026-10-29 11:56:27',NULL,'2026-10-29 11:56:27','2026-10-29 11:56:27',19,NULL,'2026-10-29 11:56:27','withdrawn',NULL,NULL,NULL,NULL,1,NULL);
/*!40000 ALTER TABLE `applications` ENABLE KEYS */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_actor_id_foreign` (`actor_id`),
  KEY `audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `audit_logs_action_index` (`action`),
  CONSTRAINT `audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
DROP TABLE IF EXISTS `career_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `career_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `advisor_id` bigint(20) unsigned NOT NULL,
  `target_role` varchar(255) NOT NULL,
  `strengths` text DEFAULT NULL,
  `skill_gaps` text DEFAULT NULL,
  `advisor_comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `career_plans_student_id_unique` (`student_id`),
  KEY `career_plans_advisor_id_foreign` (`advisor_id`),
  CONSTRAINT `career_plans_advisor_id_foreign` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `career_plans_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `career_plans` DISABLE KEYS */;
INSERT INTO `career_plans` VALUES
(1,9,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(2,10,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(3,11,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(4,12,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(5,13,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(6,14,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(7,15,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(8,16,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(9,17,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(10,18,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(11,19,2,'Software developer',NULL,NULL,NULL,'2026-10-29 11:56:27','2026-10-29 11:56:27');
/*!40000 ALTER TABLE `career_plans` ENABLE KEYS */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `industry` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_verified_by_foreign` (`verified_by`),
  CONSTRAINT `companies_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES
(1,'Demo Cedar Software',NULL,1,'2026-10-29 11:56:24','2026-10-29 11:56:24','Software','Fictional local demonstration company.',NULL,NULL,NULL),
(2,'Demo Maple Systems',NULL,0,'2026-10-29 11:56:24','2026-10-29 11:56:24',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
DROP TABLE IF EXISTS `counselling_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `counselling_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `advisor_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `mode` varchar(255) NOT NULL,
  `location_or_link` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `student_note` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `counselling_sessions_student_id_foreign` (`student_id`),
  KEY `counselling_sessions_cancelled_by_foreign` (`cancelled_by`),
  KEY `counselling_sessions_advisor_id_scheduled_at_index` (`advisor_id`,`scheduled_at`),
  KEY `counselling_sessions_scheduled_at_index` (`scheduled_at`),
  KEY `counselling_sessions_status_index` (`status`),
  CONSTRAINT `counselling_sessions_advisor_id_foreign` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `counselling_sessions_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `counselling_sessions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `counselling_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `counselling_sessions` ENABLE KEYS */;
DROP TABLE IF EXISTS `cv_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cv_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cv_id` bigint(20) unsigned NOT NULL,
  `advisor_id` bigint(20) unsigned NOT NULL,
  `decision` varchar(255) NOT NULL,
  `strengths` text NOT NULL,
  `improvements` text NOT NULL,
  `placement_recommendation` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cv_reviews_cv_id_foreign` (`cv_id`),
  KEY `cv_reviews_advisor_id_foreign` (`advisor_id`),
  CONSTRAINT `cv_reviews_advisor_id_foreign` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cv_reviews_cv_id_foreign` FOREIGN KEY (`cv_id`) REFERENCES `cvs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cv_reviews` DISABLE KEYS */;
INSERT INTO `cv_reviews` VALUES
(1,1,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(2,2,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(3,3,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(4,4,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(5,5,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(6,6,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(7,7,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(8,8,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(9,9,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(10,10,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(11,11,2,'approved','Clear structure','Continue practising','ready','2026-10-29 11:56:27','2026-10-29 11:56:27');
/*!40000 ALTER TABLE `cv_reviews` ENABLE KEYS */;
DROP TABLE IF EXISTS `cvs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cvs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `source` varchar(255) NOT NULL DEFAULT 'uploaded',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content`)),
  `file_path` varchar(255) DEFAULT NULL,
  `mime` varchar(255) DEFAULT NULL,
  `size` int(10) unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cvs_user_id_version_unique` (`user_id`,`version`),
  KEY `cvs_reviewed_by_foreign` (`reviewed_by`),
  KEY `cvs_status_index` (`status`),
  CONSTRAINT `cvs_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cvs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cvs` DISABLE KEYS */;
INSERT INTO `cvs` VALUES
(1,9,1,'',NULL,'approved',NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24','Demo structured CV','builder','{\"name\":\"Demo Stage 1\",\"email\":\"stage1@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:24',NULL),
(2,10,1,'',NULL,'approved',NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24','Demo structured CV','builder','{\"name\":\"Demo Stage 2\",\"email\":\"stage2@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:24',NULL),
(3,11,1,'',NULL,'approved',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25','Demo structured CV','builder','{\"name\":\"Demo Stage 3\",\"email\":\"stage3@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:25',NULL),
(4,12,1,'',NULL,'approved',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25','Demo structured CV','builder','{\"name\":\"Demo Stage 4\",\"email\":\"stage4@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:25',NULL),
(5,13,1,'',NULL,'approved',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25','Demo structured CV','builder','{\"name\":\"Demo Stage 5\",\"email\":\"stage5@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:25',NULL),
(6,14,1,'',NULL,'approved',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25','Demo structured CV','builder','{\"name\":\"Demo Stage 6\",\"email\":\"stage6@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:25',NULL),
(7,15,1,'',NULL,'approved',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','Demo structured CV','builder','{\"name\":\"Demo Stage 7\",\"email\":\"stage7@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:26',NULL),
(8,16,1,'',NULL,'approved',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','Demo structured CV','builder','{\"name\":\"Demo Stage 8\",\"email\":\"stage8@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:26',NULL),
(9,17,1,'',NULL,'approved',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','Demo structured CV','builder','{\"name\":\"Demo Stage 9\",\"email\":\"stage9@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:26',NULL),
(10,18,1,'',NULL,'approved',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','Demo structured CV','builder','{\"name\":\"Demo Stage 10\",\"email\":\"stage10@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:26',NULL),
(11,19,1,'',NULL,'approved',NULL,'2026-10-29 11:56:27','2026-10-29 11:56:27','Demo structured CV','builder','{\"name\":\"Demo Stage 11\",\"email\":\"stage11@demo.test\",\"summary\":\"Fictional CV fixture.\",\"education\":\"BSc in Computer Science\",\"skills\":\"React, Communication\",\"experience\":\"Academic portfolio\"}',NULL,NULL,NULL,NULL,2,'2026-10-29 11:56:27',NULL);
/*!40000 ALTER TABLE `cvs` ENABLE KEYS */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
DROP TABLE IF EXISTS `interview_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `interview_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `interview_id` bigint(20) unsigned NOT NULL,
  `recruiter_id` bigint(20) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `recommendation` varchar(255) NOT NULL,
  `comments` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interview_feedback_interview_id_unique` (`interview_id`),
  KEY `interview_feedback_recruiter_id_foreign` (`recruiter_id`),
  CONSTRAINT `interview_feedback_interview_id_foreign` FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interview_feedback_recruiter_id_foreign` FOREIGN KEY (`recruiter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `interview_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `interview_feedback` ENABLE KEYS */;
DROP TABLE IF EXISTS `interviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `interviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint(20) unsigned NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `mode` varchar(255) NOT NULL DEFAULT 'Video call',
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `scheduled_by` bigint(20) unsigned DEFAULT NULL,
  `duration_min` smallint(5) unsigned NOT NULL DEFAULT 30,
  `type` varchar(255) NOT NULL DEFAULT 'Technical',
  `interviewer` varchar(255) DEFAULT NULL,
  `link_or_location` varchar(255) DEFAULT NULL,
  `prep_notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  PRIMARY KEY (`id`),
  UNIQUE KEY `interviews_application_id_unique` (`application_id`),
  KEY `interviews_scheduled_by_foreign` (`scheduled_by`),
  KEY `interviews_scheduled_at_index` (`scheduled_at`),
  KEY `interviews_status_index` (`status`),
  CONSTRAINT `interviews_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interviews_scheduled_by_foreign` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `interviews` DISABLE KEYS */;
INSERT INTO `interviews` VALUES
(1,6,'2026-11-03 17:56:25','On-site',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25',NULL,30,'Technical',NULL,'Demo office',NULL,'scheduled'),
(2,7,'2026-11-03 17:56:26','On-site',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26',NULL,30,'Technical',NULL,'Demo office',NULL,'scheduled'),
(3,8,'2026-11-03 17:56:26','On-site',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26',NULL,30,'Technical',NULL,'Demo office',NULL,'scheduled'),
(4,9,'2026-11-03 17:56:26','On-site',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26',NULL,30,'Technical',NULL,'Demo office',NULL,'scheduled');
/*!40000 ALTER TABLE `interviews` ENABLE KEYS */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_10_25_000001_create_career_tables',1),
(5,'2026_10_25_075108_create_personal_access_tokens_table',1),
(6,'2026_10_29_000001_extend_accounts',1),
(7,'2026_10_29_000002_create_profiles',1),
(8,'2026_10_29_000003_create_advisor_foundations',1),
(9,'2026_10_29_000004_extend_placement_schema',1),
(10,'2026_10_29_000005_create_support_tables',1),
(11,'2026_10_29_000006_convert_legacy_data',1),
(12,'2026_10_30_000001_offer_start_date',1),
(13,'2026_10_31_000001_complete_platform',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `private` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notes_user_id_foreign` (`user_id`),
  KEY `notes_student_id_foreign` (`student_id`),
  CONSTRAINT `notes_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
DROP TABLE IF EXISTS `notification_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_key` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_events_event_key_unique` (`event_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `notification_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_events` ENABLE KEYS */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
DROP TABLE IF EXISTS `offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint(20) unsigned NOT NULL,
  `details` text NOT NULL,
  `expires_at` timestamp NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `offers_application_id_unique` (`application_id`),
  KEY `offers_expires_at_index` (`expires_at`),
  KEY `offers_status_index` (`status`),
  CONSTRAINT `offers_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `offers` DISABLE KEYS */;
INSERT INTO `offers` VALUES
(1,7,'Fictional offer fixture.','2026-11-08 11:56:26','pending',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','2026-11-28'),
(2,8,'Fictional offer fixture.','2026-11-08 11:56:26','accepted',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','2026-11-28'),
(3,9,'Fictional offer fixture.','2026-11-08 11:56:26','declined',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','2026-11-28');
/*!40000 ALTER TABLE `offers` ENABLE KEYS */;
DROP TABLE IF EXISTS `opportunities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `opportunities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'Internship',
  `location` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `deadline` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `work_mode` varchar(255) NOT NULL DEFAULT 'On-site',
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `stipend_min` int(10) unsigned DEFAULT NULL,
  `stipend_max` int(10) unsigned DEFAULT NULL,
  `stipend_label` varchar(255) DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `min_cgpa` decimal(3,2) DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `opportunities_company_id_foreign` (`company_id`),
  KEY `opportunities_posted_by_foreign` (`posted_by`),
  KEY `opportunities_approved_by_foreign` (`approved_by`),
  KEY `opportunities_status_deadline_index` (`status`,`deadline`),
  KEY `opportunities_type_index` (`type`),
  KEY `opportunities_location_index` (`location`),
  KEY `opportunities_work_mode_index` (`work_mode`),
  FULLTEXT KEY `opportunities_title_description_fulltext` (`title`,`description`),
  CONSTRAINT `opportunities_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opportunities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `opportunities_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `opportunities` DISABLE KEYS */;
INSERT INTO `opportunities` VALUES
(1,1,'Demo Internship opportunity','Internship','Dhaka','Fictional opportunity used to test the local platform.','[\"React\",\"Laravel\"]','2026-11-28','open','2026-10-29 11:56:24','2026-10-29 11:56:24',NULL,'On-site',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(2,1,'Demo Job opportunity','Job','Dhaka','Fictional opportunity used to test the local platform.','[\"React\",\"Laravel\"]','2026-11-28','open','2026-10-29 11:56:24','2026-10-29 11:56:24',NULL,'On-site',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(3,1,'Demo Training opportunity','Training','Dhaka','Fictional opportunity used to test the local platform.','[\"React\",\"Laravel\"]','2026-11-28','open','2026-10-29 11:56:24','2026-10-29 11:56:24',NULL,'On-site',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(4,1,'Demo Part-time opportunity','Part-time','Dhaka','Fictional opportunity used to test the local platform.','[\"React\",\"Laravel\"]','2026-11-28','open','2026-10-29 11:56:24','2026-10-29 11:56:24',NULL,'On-site',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `opportunities` ENABLE KEYS */;
DROP TABLE IF EXISTS `opportunity_skill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `opportunity_skill` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `opportunity_id` bigint(20) unsigned NOT NULL,
  `skill_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `opportunity_skill_opportunity_id_skill_id_unique` (`opportunity_id`,`skill_id`),
  KEY `opportunity_skill_skill_id_foreign` (`skill_id`),
  CONSTRAINT `opportunity_skill_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `opportunity_skill_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `opportunity_skill` DISABLE KEYS */;
INSERT INTO `opportunity_skill` VALUES
(1,1,2,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(2,1,1,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(3,2,2,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(4,2,1,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(5,3,2,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(6,3,1,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(7,4,2,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(8,4,1,'2026-10-29 11:56:24','2026-10-29 11:56:24');
/*!40000 ALTER TABLE `opportunity_skill` ENABLE KEYS */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
DROP TABLE IF EXISTS `placement_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `placement_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `role_title` varchar(255) NOT NULL,
  `job_type` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placement_records_application_id_unique` (`application_id`),
  KEY `placement_records_student_id_foreign` (`student_id`),
  KEY `placement_records_company_id_foreign` (`company_id`),
  KEY `placement_records_recorded_by_foreign` (`recorded_by`),
  KEY `placement_records_start_date_index` (`start_date`),
  CONSTRAINT `placement_records_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `placement_records_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `placement_records_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `placement_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `placement_records` DISABLE KEYS */;
INSERT INTO `placement_records` VALUES
(1,8,16,1,'Demo Developer','Job','2026-11-28',1,'2026-10-29 11:56:26','2026-10-29 11:56:26');
/*!40000 ALTER TABLE `placement_records` ENABLE KEYS */;
DROP TABLE IF EXISTS `plan_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan_milestones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'not_started',
  `due_date` date DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plan_milestones_plan_id_foreign` (`plan_id`),
  KEY `plan_milestones_status_index` (`status`),
  KEY `plan_milestones_due_date_index` (`due_date`),
  CONSTRAINT `plan_milestones_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `career_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `plan_milestones` DISABLE KEYS */;
INSERT INTO `plan_milestones` VALUES
(1,1,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(2,2,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(3,3,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(4,4,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(5,5,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(6,6,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(7,7,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(8,8,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(9,9,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(10,10,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(11,11,'Prepare portfolio','completed',NULL,0,'2026-10-29 11:56:27','2026-10-29 11:56:27');
/*!40000 ALTER TABLE `plan_milestones` ENABLE KEYS */;
DROP TABLE IF EXISTS `profile_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `profile_certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `org` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `profile_certificates_user_id_foreign` (`user_id`),
  CONSTRAINT `profile_certificates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `profile_certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `profile_certificates` ENABLE KEYS */;
DROP TABLE IF EXISTS `profile_experiences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `profile_experiences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `org` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `profile_experiences_user_id_foreign` (`user_id`),
  CONSTRAINT `profile_experiences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `profile_experiences` DISABLE KEYS */;
/*!40000 ALTER TABLE `profile_experiences` ENABLE KEYS */;
DROP TABLE IF EXISTS `reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `remindable_type` varchar(255) NOT NULL,
  `remindable_id` bigint(20) unsigned NOT NULL,
  `remind_at` timestamp NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `interval_days` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reminders_user_id_foreign` (`user_id`),
  KEY `reminders_remindable_type_remindable_id_index` (`remindable_type`,`remindable_id`),
  KEY `reminders_remind_at_index` (`remind_at`),
  CONSTRAINT `reminders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `reminders` ENABLE KEYS */;
DROP TABLE IF EXISTS `saved_opportunities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_opportunities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `opportunity_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saved_opportunities_user_id_opportunity_id_unique` (`user_id`,`opportunity_id`),
  KEY `saved_opportunities_opportunity_id_foreign` (`opportunity_id`),
  CONSTRAINT `saved_opportunities_opportunity_id_foreign` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_opportunities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `saved_opportunities` DISABLE KEYS */;
/*!40000 ALTER TABLE `saved_opportunities` ENABLE KEYS */;
DROP TABLE IF EXISTS `session_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned NOT NULL,
  `advisor_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `next_step` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_feedback_session_id_unique` (`session_id`),
  KEY `session_feedback_advisor_id_foreign` (`advisor_id`),
  CONSTRAINT `session_feedback_advisor_id_foreign` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `session_feedback_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `counselling_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `session_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `session_feedback` ENABLE KEYS */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'demo_mode','true','2026-10-29 11:56:27','2026-10-29 11:56:27');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
DROP TABLE IF EXISTS `skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `skills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `skills_name_unique` (`name`),
  KEY `skills_type_index` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `skills` DISABLE KEYS */;
INSERT INTO `skills` VALUES
(1,'React','technical','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(2,'Laravel','technical','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(3,'Python','technical','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(4,'SQL','technical','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(5,'Communication','soft','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(6,'Teamwork','soft','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(7,'Problem solving','soft','2026-10-29 11:56:24','2026-10-29 11:56:24');
/*!40000 ALTER TABLE `skills` ENABLE KEYS */;
DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `student_no` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `batch` varchar(255) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `ssc_institution` varchar(255) DEFAULT NULL,
  `ssc_board` varchar(255) DEFAULT NULL,
  `hsc_institution` varchar(255) DEFAULT NULL,
  `hsc_board` varchar(255) DEFAULT NULL,
  `ssc_year` smallint(5) unsigned DEFAULT NULL,
  `hsc_year` smallint(5) unsigned DEFAULT NULL,
  `ssc_gpa` decimal(3,2) DEFAULT NULL,
  `hsc_gpa` decimal(3,2) DEFAULT NULL,
  `career_objective` text DEFAULT NULL,
  `interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interests`)),
  `has_experience` tinyint(1) DEFAULT NULL,
  `has_certificates` tinyint(1) DEFAULT NULL,
  `completion_percent` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `wizard_step` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `verification_status` varchar(255) NOT NULL DEFAULT 'pending',
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_profiles_user_id_unique` (`user_id`),
  UNIQUE KEY `student_profiles_student_no_unique` (`student_no`),
  KEY `student_profiles_verified_by_foreign` (`verified_by`),
  KEY `student_profiles_department_index` (`department`),
  KEY `student_profiles_verification_status_index` (`verification_status`),
  CONSTRAINT `student_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_profiles_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `student_profiles` DISABLE KEYS */;
INSERT INTO `student_profiles` VALUES
(1,4,NULL,'CSE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'pending',NULL,NULL,NULL,'2026-10-29 11:56:23','2026-10-29 11:56:23'),
(2,5,NULL,'CSE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'pending',NULL,NULL,NULL,'2026-10-29 11:56:23','2026-10-29 11:56:23'),
(3,6,NULL,'CSE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'rejected',NULL,NULL,'Academic information needs correction.','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(4,7,NULL,'CSE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'pending',NULL,NULL,NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(5,9,'DEMO-1','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:24',NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24'),
(6,10,'DEMO-2','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:24',NULL,'2026-10-29 11:56:24','2026-10-29 11:56:25'),
(7,11,'DEMO-3','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:25',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(8,12,'DEMO-4','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:25',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(9,13,'DEMO-5','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:25',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(10,14,'DEMO-6','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:25',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25'),
(11,15,'DEMO-7','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:26',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(12,16,'DEMO-8','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:26',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(13,17,'DEMO-9','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:26',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(14,18,'DEMO-10','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:26',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26'),
(15,19,'DEMO-11','CSE','Demo 2026',3.50,'Demo School','Demo Board','Demo College','Demo Board',2019,2021,4.50,4.50,'Develop useful software.','[\"Software engineering\"]',0,0,100,8,'verified',1,'2026-10-29 11:56:27',NULL,'2026-10-29 11:56:27','2026-10-29 11:56:27');
/*!40000 ALTER TABLE `student_profiles` ENABLE KEYS */;
DROP TABLE IF EXISTS `student_skill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_skill` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `skill_id` bigint(20) unsigned NOT NULL,
  `level` varchar(255) NOT NULL DEFAULT 'beginner',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_skill_user_id_skill_id_unique` (`user_id`,`skill_id`),
  KEY `student_skill_skill_id_foreign` (`skill_id`),
  CONSTRAINT `student_skill_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_skill_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `student_skill` DISABLE KEYS */;
INSERT INTO `student_skill` VALUES
(1,9,5,'beginner','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(2,9,1,'beginner','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(3,10,5,'beginner','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(4,10,1,'beginner','2026-10-29 11:56:24','2026-10-29 11:56:24'),
(5,11,5,'beginner','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(6,11,1,'beginner','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(7,12,5,'beginner','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(8,12,1,'beginner','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(9,13,5,'beginner','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(10,13,1,'beginner','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(11,14,5,'beginner','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(12,14,1,'beginner','2026-10-29 11:56:25','2026-10-29 11:56:25'),
(13,15,5,'beginner','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(14,15,1,'beginner','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(15,16,5,'beginner','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(16,16,1,'beginner','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(17,17,5,'beginner','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(18,17,1,'beginner','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(19,18,5,'beginner','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(20,18,1,'beginner','2026-10-29 11:56:26','2026-10-29 11:56:26'),
(21,19,5,'beginner','2026-10-29 11:56:27','2026-10-29 11:56:27'),
(22,19,1,'beginner','2026-10-29 11:56:27','2026-10-29 11:56:27');
/*!40000 ALTER TABLE `student_skill` ENABLE KEYS */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'student',
  `advisor_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `phone` varchar(30) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_advisor_id_foreign` (`advisor_id`),
  KEY `users_company_id_foreign` (`company_id`),
  KEY `users_role_index` (`role`),
  KEY `users_status_index` (`status`),
  CONSTRAINT `users_advisor_id_foreign` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Demo Admin','admin@demo.test','2026-10-29 11:56:22','$2y$12$aBT0/FFeBFBpF8vj2kZOj.rQmb/l0kW/y.rTDiWQk0f0zIOmp3HaS',NULL,'2026-10-29 11:56:22','2026-10-29 11:56:22','admin',NULL,NULL,NULL,NULL,'active',NULL,NULL,0,NULL,NULL),
(2,'Demo Advisor','advisor@demo.test','2026-10-29 11:56:22','$2y$12$1320PYk/KjINXELMxAIzC.R86R1z6gLBhhgpEcB0xD1e4gL.0Y6o2',NULL,'2026-10-29 11:56:23','2026-10-29 11:56:23','advisor',NULL,NULL,NULL,NULL,'active',NULL,NULL,0,NULL,NULL),
(3,'Demo Recruiter','recruiter@demo.test','2026-10-29 11:56:23','$2y$12$24wzUxxHRtykpXpAANqnfumJmmHjHFvzkh6IukBtFBas5U38kLgBe',NULL,'2026-10-29 11:56:23','2026-10-29 11:56:24','recruiter',NULL,1,NULL,NULL,'active',NULL,NULL,0,NULL,NULL),
(4,'Demo Student','student@demo.test','2026-10-29 11:56:23','$2y$12$BDymKNKAygjdwwQEGNdl5u6lBIXiWsPeOQt7rAYMJ8pkyvElSAyRe',NULL,'2026-10-29 11:56:23','2026-10-29 11:56:23','student',NULL,NULL,NULL,NULL,'active',NULL,NULL,0,NULL,NULL),
(5,'Demo Pending Student','pending@demo.test','2026-10-29 11:56:23','$2y$12$uPtL2rufX1I8XUNkurDmQeaM22aTE9Z4L2vxoVBsN6Xsdvq8Q6SJS',NULL,'2026-10-29 11:56:23','2026-10-29 11:56:23','student',NULL,NULL,NULL,NULL,'active',NULL,NULL,0,NULL,NULL),
(6,'Demo Rejected Student','rejected@demo.test','2026-10-29 11:56:23','$2y$12$eaEF88wLev9t.1RA1G63xuVK5k3e017yeF9tL5rz23w37ZKZQiAa2',NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24','student',NULL,NULL,NULL,NULL,'active',NULL,NULL,0,NULL,NULL),
(7,'Demo Unverified Student','unverified@demo.test',NULL,'$2y$12$p4/OfDZSaWn6s6slhiz/aO03hay8p1tvcB4AuFpEdY3WGRR.pGzUS',NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24','student',NULL,NULL,NULL,NULL,'active',NULL,NULL,0,NULL,NULL),
(8,'Demo Temporary Advisor','temporary@demo.test','2026-10-29 11:56:24','$2y$12$jOeiJ8CS0u5LNa/UBPQHt.nAXM/LsoMLtF7N1yGo1ekCoq4zJ5Q9m',NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24','advisor',NULL,NULL,NULL,NULL,'active',NULL,NULL,1,NULL,NULL),
(9,'Demo Stage 1','stage1@demo.test','2026-10-29 11:56:24','$2y$12$E4aWMs5oNIos9tROqtx6wuK5P5ElUCNWt44oNcShO7KfVpXjUVlmi',NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL),
(10,'Demo Stage 2','stage2@demo.test','2026-10-29 11:56:24','$2y$12$327Yxu9rc7eAJyDmQgJHPOXiJIJiWUZDqbN5B7bJUE5veKDxMuswG',NULL,'2026-10-29 11:56:24','2026-10-29 11:56:24','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL),
(11,'Demo Stage 3','stage3@demo.test','2026-10-29 11:56:25','$2y$12$qxG8TCOPXHNP.Cg8oa5z3.tERQmI2EmiPDC1lovQURC2TrfQdQdnq',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL),
(12,'Demo Stage 4','stage4@demo.test','2026-10-29 11:56:25','$2y$12$dGp6XHieFU4.vgxDH0M56O0RuPNkacrvGRvd1VazuU.FIRJ5XE9y.',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL),
(13,'Demo Stage 5','stage5@demo.test','2026-10-29 11:56:25','$2y$12$iesLoIQhqz1d6aSgUe4YBOT2OmPBcawdwecRjXdOOYyRb6WGsEXg.',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL),
(14,'Demo Stage 6','stage6@demo.test','2026-10-29 11:56:25','$2y$12$fgoEVnNxU44eNzc1jgH4n.moUHuEVJlcDS5r3BphsKSseaoSPUjpW',NULL,'2026-10-29 11:56:25','2026-10-29 11:56:25','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL),
(15,'Demo Stage 7','stage7@demo.test','2026-10-29 11:56:25','$2y$12$tv8fC6DQT6zv69ogtfCIz.cyWgbJvb7bgfvpcGut.9h1s5Q01p6lm',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL),
(16,'Demo Stage 8','stage8@demo.test','2026-10-29 11:56:26','$2y$12$FgPjDZ6r8sGqcHBntzEntuhv0KJTHc/Mtv2/i5OrQuyIP4jHirbtW',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL),
(17,'Demo Stage 9','stage9@demo.test','2026-10-29 11:56:26','$2y$12$z8APeRnpG7oBYeyWTjVQD.Ryke7odYsF3BqoGFQ/stSElfMeG31aW',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL),
(18,'Demo Stage 10','stage10@demo.test','2026-10-29 11:56:26','$2y$12$UMjL2mo7LpZVDHoCNri8COqEySuWMCAWn9eUV4cExcd04fTYPPmx6',NULL,'2026-10-29 11:56:26','2026-10-29 11:56:26','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL),
(19,'Demo Stage 11','stage11@demo.test','2026-10-29 11:56:26','$2y$12$79w/DTFAcQrnnUE2.dSb3eKYJcH6H1bASlCTKzWTKRPt5XsI6Hpn.',NULL,'2026-10-29 11:56:27','2026-10-29 11:56:27','student',2,NULL,'CSE',NULL,'active','+0000000000',NULL,0,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

