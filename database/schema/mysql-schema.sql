/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_log_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activity_log_id` bigint unsigned NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_log_subjects_activity_log_id_foreign` (`activity_log_id`),
  KEY `activity_log_subjects_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  CONSTRAINT `activity_log_subjects_activity_log_id_foreign` FOREIGN KEY (`activity_log_id`) REFERENCES `activity_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `actor_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `properties` json NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `activity_logs_actor_type_actor_id_index` (`actor_type`,`actor_id`),
  KEY `activity_logs_event_index` (`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `address_pool_to_node`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address_pool_to_node` (
  `address_pool_id` bigint unsigned NOT NULL,
  `node_id` bigint unsigned NOT NULL,
  KEY `address_pool_to_node_address_pool_id_foreign` (`address_pool_id`),
  KEY `address_pool_to_node_node_id_foreign` (`node_id`),
  CONSTRAINT `address_pool_to_node_address_pool_id_foreign` FOREIGN KEY (`address_pool_id`) REFERENCES `address_pools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `address_pool_to_node_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `address_pools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address_pools` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `server_id` bigint unsigned NOT NULL,
  `is_successful` tinyint(1) NOT NULL DEFAULT '0',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `backups_uuid_unique` (`uuid`),
  KEY `backups_server_id_foreign` (`server_id`),
  CONSTRAINT `backups_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ip_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `address_pool_id` bigint unsigned NOT NULL,
  `server_id` bigint unsigned DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cidr` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gateway` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mac_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_addresses_address_pool_id_foreign` (`address_pool_id`),
  KEY `ip_addresses_server_id_foreign` (`server_id`),
  CONSTRAINT `ip_addresses_address_pool_id_foreign` FOREIGN KEY (`address_pool_id`) REFERENCES `address_pools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ip_addresses_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `iso_library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `iso_library` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `node_id` bigint unsigned NOT NULL,
  `is_successful` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `iso_library_uuid_unique` (`uuid`),
  KEY `iso_library_node_id_foreign` (`node_id`),
  CONSTRAINT `iso_library_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `short_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_short_code_unique` (`short_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nodes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cluster` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proxmox',
  `fqdn` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` int DEFAULT NULL,
  `token_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `memory` int unsigned NOT NULL,
  `memory_overallocate` int NOT NULL DEFAULT '0',
  `disk` int unsigned NOT NULL,
  `disk_overallocate` int NOT NULL DEFAULT '0',
  `vm_storage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `backup_storage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iso_storage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `network` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coterm_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `coterm_tls_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `coterm_fqdn` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coterm_port` int NOT NULL DEFAULT '443',
  `coterm_token_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coterm_token` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nodes_coterm_token_id_unique` (`coterm_token_id`),
  KEY `nodes_location_id_foreign` (`location_id`),
  CONSTRAINT `nodes_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uuid_short` char(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `node_id` bigint unsigned NOT NULL,
  `vmid` bigint unsigned NOT NULL,
  `hostname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpu` int unsigned NOT NULL,
  `memory` int unsigned NOT NULL,
  `disk` int unsigned NOT NULL,
  `bandwidth_usage` int NOT NULL DEFAULT '0',
  `snapshot_limit` int unsigned DEFAULT NULL,
  `backup_limit` int unsigned DEFAULT NULL,
  `bandwidth_limit` int unsigned DEFAULT NULL,
  `hydrated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `servers_uuid_unique` (`uuid`),
  UNIQUE KEY `servers_uuidshort_unique` (`uuid_short`),
  KEY `servers_user_id_foreign` (`user_id`),
  KEY `servers_node_id_foreign` (`node_id`),
  CONSTRAINT `servers_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `servers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ssh_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ssh_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_key` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ssh_keys_user_id_foreign` (`user_id`),
  CONSTRAINT `ssh_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `template_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `template_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `node_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `order_column` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_groups_uuid_unique` (`uuid`),
  KEY `template_groups_node_id_foreign` (`node_id`),
  CONSTRAINT `template_groups_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_group_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vmid` bigint unsigned NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `order_column` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `templates_uuid_unique` (`uuid`),
  KEY `templates_template_group_id_foreign` (`template_group_id`),
  CONSTRAINT `templates_template_group_id_foreign` FOREIGN KEY (`template_group_id`) REFERENCES `template_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `root_admin` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` VALUES (3,'2014_10_12_200000_add_two_factor_columns_to_users_table',1);
INSERT INTO `migrations` VALUES (4,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` VALUES (5,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` VALUES (6,'2022_07_14_232223_nodes_table',1);
INSERT INTO `migrations` VALUES (7,'2022_07_14_232304_servers_table',1);
INSERT INTO `migrations` VALUES (8,'2022_07_22_004345_add_ip_address_column_to_servers_table',1);
INSERT INTO `migrations` VALUES (9,'2022_07_23_233402_remove_old_columns_from_servers_table',1);
INSERT INTO `migrations` VALUES (10,'2022_07_24_023016_add_make_template_visible_column_to_servers_table',1);
INSERT INTO `migrations` VALUES (11,'2022_07_25_015911_add_is_installing_column_to_servers_table',1);
INSERT INTO `migrations` VALUES (12,'2022_07_25_025600_create_templates_table',1);
INSERT INTO `migrations` VALUES (13,'2022_07_25_025928_remove_template_columns_from_servers_table',1);
INSERT INTO `migrations` VALUES (14,'2022_07_25_030214_remove_is_template_column_from_templates_table',1);
INSERT INTO `migrations` VALUES (15,'2022_07_25_030354_rename_make_templates_visible_column_in_templates_table',1);
INSERT INTO `migrations` VALUES (16,'2022_07_26_003508_create_i_p_addresses_table',1);
INSERT INTO `migrations` VALUES (17,'2022_07_26_004509_remove_ip_address_column_from_servers_table',1);
INSERT INTO `migrations` VALUES (18,'2022_07_26_005058_fix_ip_addresses_table_columns',1);
INSERT INTO `migrations` VALUES (19,'2022_07_27_001056_clear_server_id_on_server_delete_in_ip_tables',1);
INSERT INTO `migrations` VALUES (20,'2022_07_28_010809_change_subnet_column_to_cid_in_ip_addresses_table',1);
INSERT INTO `migrations` VALUES (21,'2022_07_31_152015_create_sso_tokens_table',1);
INSERT INTO `migrations` VALUES (22,'2022_08_02_042345_replace_is_installing_column_with_installing_in_servers_table',1);
INSERT INTO `migrations` VALUES (23,'2022_08_04_052834_add_api_key_columns_to_nodes_table',1);
INSERT INTO `migrations` VALUES (24,'2022_08_09_003453_remove_username_password_columns_from_nodes_table',1);
INSERT INTO `migrations` VALUES (25,'2022_08_09_003757_remove_auth_type_column_from_nodes_table',1);
INSERT INTO `migrations` VALUES (26,'2022_08_09_194438_add_mac_address_column_to_ip_addresses_table',1);
INSERT INTO `migrations` VALUES (27,'2022_09_08_031709_create_activity_logs_table',1);
INSERT INTO `migrations` VALUES (28,'2022_09_09_214058_create_activity_log_subjects_table',1);
INSERT INTO `migrations` VALUES (29,'2022_09_25_133215_add_limits_to_servers_table',1);
INSERT INTO `migrations` VALUES (30,'2022_09_25_135328_add_network_and_storage_columns_to_nodes_table',1);
INSERT INTO `migrations` VALUES (31,'2022_10_08_035450_add_uuid_columns_to_servers_table',1);
INSERT INTO `migrations` VALUES (32,'2022_10_16_134515_add_bandwidth_metrics_to_servers_table',1);
INSERT INTO `migrations` VALUES (33,'2022_10_20_215932_add_default_value_to_bandwidth_usage_column_in_servers_table',1);
INSERT INTO `migrations` VALUES (34,'2022_10_30_165927_rename_uuid_short_column_in_servers_table',1);
INSERT INTO `migrations` VALUES (35,'2022_11_02_223634_refactor_activity_logs_table',1);
INSERT INTO `migrations` VALUES (36,'2022_11_13_154340_create_backups_table',1);
INSERT INTO `migrations` VALUES (37,'2022_11_13_203213_add_uuid_column_to_backups_table',1);
INSERT INTO `migrations` VALUES (38,'2022_11_23_155133_add_storage_and_specification_columns_to_nodes_table',1);
INSERT INTO `migrations` VALUES (39,'2022_11_23_180140_rename_columns_in_backups_table',1);
INSERT INTO `migrations` VALUES (40,'2022_11_23_221039_add_soft_deletes_to_backups_table',1);
INSERT INTO `migrations` VALUES (41,'2022_11_24_205004_add_hostname_column_to_servers_table',1);
INSERT INTO `migrations` VALUES (42,'2022_12_06_233100_move_network_column_in_nodes_table',1);
INSERT INTO `migrations` VALUES (43,'2022_12_07_003704_create_locations_table',1);
INSERT INTO `migrations` VALUES (44,'2022_12_07_004256_add_location_id_column_to_nodes_table',1);
INSERT INTO `migrations` VALUES (45,'2022_12_07_014225_remove_nullable_from_foreign_keys',1);
INSERT INTO `migrations` VALUES (46,'2022_12_16_030651_rename_hostname_column_in_nodes_table',1);
INSERT INTO `migrations` VALUES (47,'2022_12_22_174238_create_iso_library_table',1);
INSERT INTO `migrations` VALUES (48,'2022_12_22_182930_add_uuid_column_to_iso_library_table',1);
INSERT INTO `migrations` VALUES (49,'2022_12_22_194029_add_iso_storage_column_to_nodes_table',1);
INSERT INTO `migrations` VALUES (50,'2022_12_25_005721_create_template_groups_table',1);
INSERT INTO `migrations` VALUES (51,'2022_12_25_005722_fix_templates_table',1);
INSERT INTO `migrations` VALUES (52,'2022_12_25_011046_add_node_id_column_to_template_groups_table',1);
INSERT INTO `migrations` VALUES (53,'2022_12_25_011208_fix_node_id_column_order_in_template_groups_table',1);
INSERT INTO `migrations` VALUES (54,'2022_12_25_011355_make_node_id_not_nullable_in_template_groups_table',1);
INSERT INTO `migrations` VALUES (55,'2022_12_25_011632_add_uuid_column_to_templates_table',1);
INSERT INTO `migrations` VALUES (56,'2023_01_15_153139_add_soft_deletes_column_to_servers_table',1);
INSERT INTO `migrations` VALUES (57,'2023_01_21_041004_add_soft_deletes_to_users_table',1);
INSERT INTO `migrations` VALUES (58,'2023_01_21_043348_add_type_to_personal_access_tokens_table',1);
INSERT INTO `migrations` VALUES (59,'2023_02_20_053349_create_ssh_keys_table',1);
INSERT INTO `migrations` VALUES (60,'2023_03_06_212114_create_job_batches_table',1);
INSERT INTO `migrations` VALUES (62,'2023_04_07_014605_remove_soft_deletes_from_users_table',2);
INSERT INTO `migrations` VALUES (63,'2023_04_07_015233_remove_soft_deletes_from_servers_table',3);
INSERT INTO `migrations` VALUES (64,'2023_04_28_000000_add_expires_at_to_personal_access_tokens_table',4);
INSERT INTO `migrations` VALUES (65,'2023_04_28_000000_rename_password_resets_table',4);
INSERT INTO `migrations` VALUES (66,'2023_05_11_005040_add_coterm_to_nodes_table',4);
INSERT INTO `migrations` VALUES (67,'2023_05_11_041215_add_token_id_column_to_nodes_table',5);
INSERT INTO `migrations` VALUES (68,'2023_05_11_042803_make_node_secret_column_a_text_type_in_nodes_table',6);
INSERT INTO `migrations` VALUES (69,'2023_05_11_042804_make_node_secret_encrypted_in_nodes_table',6);
INSERT INTO `migrations` VALUES (70,'2023_05_11_043101_make_coterm_token_id_unique_in_nodes_table',7);
INSERT INTO `migrations` VALUES (71,'2023_05_20_200644_add_coterm_enabled_column_to_nodes_table',8);
INSERT INTO `migrations` VALUES (72,'2023_05_21_015205_move_port_column_to_behind_fqdn_in_nodes_table',9);
INSERT INTO `migrations` VALUES (73,'2023_05_28_025503_create_address_pools_table',10);
INSERT INTO `migrations` VALUES (74,'2023_05_28_031255_create_address_pool_to_node_table',10);
INSERT INTO `migrations` VALUES (76,'2023_05_28_032248_add_uuid_column_to_users_table',11);
INSERT INTO `migrations` VALUES (78,'2023_05_28_133425_make_uuid_unique_and_nonnullable_in_users_table',12);
INSERT INTO `migrations` VALUES (99,'2023_05_28_151803_drop_sso_tokens_table',13);
INSERT INTO `migrations` VALUES (100,'2023_06_04_153504_create_address_table_that_uses_ip_pools',13);
