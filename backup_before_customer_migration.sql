-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: bkpi
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('abaaa00209760ebd755ba1b900aa615b','i:1;',1769572934),('abaaa00209760ebd755ba1b900aa615b:timer','i:1769572934;',1769572934),('spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:31:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:10:\"roles.view\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:12:\"roles.create\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:10:\"roles.edit\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"roles.delete\";s:1:\"c\";s:3:\"web\";}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:10:\"users.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:12:\"users.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:10:\"users.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:7;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:12:\"users.delete\";s:1:\"c\";s:3:\"web\";}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:14:\"customers.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:16:\"customers.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:14:\"customers.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:11;a:3:{s:1:\"a\";i:12;s:1:\"b\";s:16:\"customers.delete\";s:1:\"c\";s:3:\"web\";}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:17:\"transactions.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:19:\"transactions.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:17:\"transactions.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:15;a:3:{s:1:\"a\";i:16;s:1:\"b\";s:19:\"transactions.delete\";s:1:\"c\";s:3:\"web\";}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:13:\"products.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:15:\"products.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:13:\"products.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:19;a:3:{s:1:\"a\";i:20;s:1:\"b\";s:15:\"products.delete\";s:1:\"c\";s:3:\"web\";}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:11:\"brands.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:13:\"brands.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:11:\"brands.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:23;a:3:{s:1:\"a\";i:24;s:1:\"b\";s:13:\"brands.delete\";s:1:\"c\";s:3:\"web\";}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:23:\"product-categories.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:25:\"product-categories.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:23:\"product-categories.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:27;a:3:{s:1:\"a\";i:28;s:1:\"b\";s:25:\"product-categories.delete\";s:1:\"c\";s:3:\"web\";}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:12:\"company.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:29;a:3:{s:1:\"a\";i:30;s:1:\"b\";s:12:\"company.edit\";s:1:\"c\";s:3:\"web\";}i:30;a:3:{s:1:\"a\";i:31;s:1:\"b\";s:9:\"logs.view\";s:1:\"c\";s:3:\"web\";}}s:5:\"roles\";a:2:{i:0;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:5:\"staff\";s:1:\"c\";s:3:\"web\";}}}',1769591656);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_settings`
--

DROP TABLE IF EXISTS `company_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_settings_company_id_setting_key_unique` (`company_id`,`setting_key`),
  CONSTRAINT `company_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `mst_company` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_settings`
--

LOCK TABLES `company_settings` WRITE;
/*!40000 ALTER TABLE `company_settings` DISABLE KEYS */;
INSERT INTO `company_settings` VALUES (1,1,'costing_method','fifo','Inventory costing method: fifo, lifo, average, or none','2026-01-27 06:30:27','2026-01-27 06:30:27'),(2,1,'default_currency','IDR','Default currency code for transactions','2026-01-27 06:30:27','2026-01-27 06:30:27'),(3,1,'default_tax_enabled','false','Whether tax is enabled by default on new transactions','2026-01-27 06:30:27','2026-01-27 06:30:27'),(4,1,'default_tax_name','PPN','Default tax name (e.g., PPN, VAT)','2026-01-27 06:30:27','2026-01-27 06:30:27'),(5,1,'default_tax_percentage','11','Default tax percentage','2026-01-27 06:30:27','2026-01-27 06:30:27'),(6,1,'default_payment_terms','Net 30','Default payment terms for transactions','2026-01-27 06:30:27','2026-01-27 06:30:27'),(7,1,'po_number_prefix','PO','Prefix for Purchase Order numbers','2026-01-27 06:30:27','2026-01-27 06:30:27'),(8,1,'so_number_prefix','SO','Prefix for Sales Order numbers','2026-01-27 06:30:27','2026-01-27 06:30:27'),(9,1,'low_stock_alert_enabled','true','Enable low stock alerts based on min_stock_level','2026-01-27 06:30:27','2026-01-27 06:30:27');
/*!40000 ALTER TABLE `company_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_movements`
--

DROP TABLE IF EXISTS `inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `movement_date` timestamp NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `movement_type` enum('purchase_in','sales_out','purchase_return','sales_return','adjustment_in','adjustment_out','opening_stock') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `quantity_before` decimal(15,3) NOT NULL,
  `quantity_after` decimal(15,3) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_movements_created_by_foreign` (`created_by`),
  KEY `inventory_movements_product_id_movement_date_index` (`product_id`,`movement_date`),
  KEY `inventory_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `inventory_movements_movement_type_index` (`movement_type`),
  CONSTRAINT `inventory_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `mst_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_movements`
--

LOCK TABLES `inventory_movements` WRITE;
/*!40000 ALTER TABLE `inventory_movements` DISABLE KEYS */;
INSERT INTO `inventory_movements` VALUES (1,'2026-01-27 06:30:36',1,'opening_stock','initialization',NULL,29.000,80000.00,0.000,29.000,'Opening stock from legacy mst_products.stock_quantity','2026-01-27 06:30:36','2026-01-27 06:30:36',NULL),(2,'2026-01-27 06:30:36',2,'opening_stock','initialization',NULL,1100.000,81500.00,0.000,1100.000,'Opening stock from legacy mst_products.stock_quantity','2026-01-27 06:30:36','2026-01-27 06:30:36',NULL),(3,'2026-01-27 06:30:36',3,'opening_stock','initialization',NULL,830.000,110000.00,0.000,830.000,'Opening stock from legacy mst_products.stock_quantity','2026-01-27 06:30:36','2026-01-27 06:30:36',NULL),(4,'2026-01-27 06:30:36',4,'opening_stock','initialization',NULL,10.000,55000.00,0.000,10.000,'Opening stock from legacy mst_products.stock_quantity','2026-01-27 06:30:36','2026-01-27 06:30:36',NULL),(5,'2026-01-27 06:30:36',5,'opening_stock','initialization',NULL,70.000,80000.00,0.000,70.000,'Opening stock from legacy mst_products.stock_quantity','2026-01-27 06:30:36','2026-01-27 06:30:36',NULL),(6,'2026-01-27 06:30:36',6,'opening_stock','initialization',NULL,200.000,81750.00,0.000,200.000,'Opening stock from legacy mst_products.stock_quantity','2026-01-27 06:30:36','2026-01-27 06:30:36',NULL),(7,'2026-01-27 06:30:36',8,'opening_stock','initialization',NULL,900.000,80000.00,0.000,900.000,'Opening stock from legacy mst_products.stock_quantity','2026-01-27 06:30:36','2026-01-27 06:30:36',NULL),(8,'2026-01-27 11:19:59',8,'purchase_in','purchase_receiving',1,50.000,80000.00,900.000,950.000,'Received from PO: PO-2026-00001','2026-01-27 11:19:59','2026-01-27 11:19:59',1),(9,'2026-01-27 11:19:59',6,'purchase_in','purchase_receiving',1,1000.000,81750.00,200.000,1200.000,'Received from PO: PO-2026-00001','2026-01-27 11:19:59','2026-01-27 11:19:59',1),(10,'2026-01-27 12:40:40',1,'sales_out','sales_order',3,-8.000,80000.00,29.000,21.000,'Sold via SO: SO-2026-00001','2026-01-27 12:40:40','2026-01-27 12:40:40',1);
/*!40000 ALTER TABLE `inventory_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_stock`
--

DROP TABLE IF EXISTS `inventory_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `quantity_on_hand` decimal(15,3) NOT NULL DEFAULT '0.000',
  `quantity_reserved` decimal(15,3) NOT NULL DEFAULT '0.000',
  `quantity_available` decimal(15,3) NOT NULL DEFAULT '0.000',
  `reorder_level` decimal(15,3) NOT NULL DEFAULT '0.000',
  `last_cost` decimal(15,2) DEFAULT NULL,
  `average_cost` decimal(15,2) DEFAULT NULL,
  `last_movement_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_stock_product_id_unique` (`product_id`),
  CONSTRAINT `inventory_stock_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `mst_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_stock`
--

LOCK TABLES `inventory_stock` WRITE;
/*!40000 ALTER TABLE `inventory_stock` DISABLE KEYS */;
INSERT INTO `inventory_stock` VALUES (1,1,21.000,20.000,1.000,0.000,80000.00,80000.00,'2026-01-27 12:40:40','2026-01-27 06:30:36','2026-01-27 12:40:40'),(2,2,1100.000,0.000,1100.000,0.000,81500.00,81500.00,'2026-01-27 06:30:36','2026-01-27 06:30:36','2026-01-27 06:30:36'),(3,3,830.000,20.000,810.000,0.000,110000.00,110000.00,'2026-01-27 06:30:36','2026-01-27 06:30:36','2026-01-27 10:56:43'),(4,4,10.000,0.000,10.000,0.000,55000.00,55000.00,'2026-01-27 06:30:36','2026-01-27 06:30:36','2026-01-27 06:30:36'),(5,5,70.000,0.000,70.000,0.000,80000.00,80000.00,'2026-01-27 06:30:36','2026-01-27 06:30:36','2026-01-27 06:30:36'),(6,6,1200.000,0.000,1200.000,0.000,81750.00,81750.00,'2026-01-27 11:19:59','2026-01-27 06:30:36','2026-01-27 11:19:59'),(7,7,0.000,0.000,0.000,0.000,85000.00,85000.00,'2026-01-27 06:30:36','2026-01-27 06:30:36','2026-01-27 06:30:36'),(8,8,950.000,0.000,950.000,0.000,80000.00,80000.00,'2026-01-27 11:19:59','2026-01-27 06:30:36','2026-01-27 11:19:59');
/*!40000 ALTER TABLE `inventory_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_11_29_122850_add_two_factor_columns_to_users_table',1),(5,'2024_11_29_122910_create_personal_access_tokens_table',1),(6,'2024_11_29_122911_create_teams_table',1),(7,'2024_11_29_122912_create_team_user_table',1),(8,'2024_11_29_122913_create_team_invitations_table',1),(9,'2024_12_01_000001_create_mst_client_type_table',1),(10,'2024_12_01_000002_create_mst_address_table',1),(11,'2024_12_01_000003_create_mst_client_table',1),(35,'2024_12_24_153257_create_mst_product_categories_table',2),(36,'2024_12_24_153534_create_mst_brands_table',2),(37,'2024_12_24_153745_create_mst_product_table',2),(49,'2024_12_24_164444_create_transactions_table',3),(50,'2024_12_27_231306_create_transaction_details_table',3),(51,'2024_12_28_215223_create_transactions_log_table',4),(52,'2024_12_24_054444_add_transaction_type_to_transactions_table',5),(54,'2023_10_10_000000_create_companies_table',6),(56,'2025_01_04_085527_create_system_logs_table',7),(57,'2025_01_27_001041_create_permission_tables',8),(58,'2026_01_26_230852_add_is_active_to_users_table',9),(59,'2026_01_26_231630_add_created_by_updated_by_to_users_table',10),(63,'2026_01_27_100001_create_mst_currencies_table',11),(64,'2026_01_27_100002_create_mst_uom_table',11),(65,'2026_01_27_100003_add_transaction_flags_to_mst_client_type_table',11),(66,'2026_01_27_100004_create_company_settings_table',12),(67,'2026_01_27_100005_add_uom_to_mst_products_table',12),(68,'2026_01_27_100006_create_inventory_stock_table',12),(69,'2026_01_27_100007_create_inventory_movements_table',12),(70,'2026_01_27_100008_create_purchase_orders_table',12),(71,'2026_01_27_100009_create_purchase_order_items_table',12),(72,'2026_01_27_100010_create_purchase_receivings_table',12),(73,'2026_01_27_100011_create_sales_orders_table',12),(74,'2026_01_27_100012_create_sales_order_items_table',12),(75,'2026_01_27_100013_create_sales_shipments_table',12),(76,'2026_01_27_100014_create_payments_table',12),(77,'2026_01_27_100015_create_purchase_returns_table',12),(78,'2026_01_27_100016_create_sales_returns_table',12),(79,'2026_01_27_161234_add_reorder_level_to_inventory_stock_table',13);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',2),(3,'App\\Models\\User',61),(2,'App\\Models\\User',62);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_address`
--

DROP TABLE IF EXISTS `mst_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_address` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_address`
--

LOCK TABLES `mst_address` WRITE;
/*!40000 ALTER TABLE `mst_address` DISABLE KEYS */;
INSERT INTO `mst_address` VALUES (1,'319 Dietrich Knolls Apt. 905','87712','Jakarta Selatan','DKI Jakarta','82459','49564','Indonesia','2024-12-03 14:37:15','2024-12-23 15:08:36','Seeder',NULL),(2,'6993 Prohaska Stream','47718','Zariaborough','Ohio','75571','27508','Slovenia','2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(3,'8303 Sylvan Ports Apt. 2100','53720','West Scottieshire','Wisconsin','20118','13547','Dominican Republic','2024-12-03 14:37:15','2024-12-23 14:48:08','Seeder',NULL),(4,'32767 McLaughlin Inlet Apt. 128','41629','Gorczanyton','Iowa','81050','95531','Gabon','2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(5,'446 Shanel Key','35757','Stephanfort','New Mexico','59557','40569','Guinea','2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(6,'319 Weimann Plain Suite 869','80352','Turcottefurt','Wyoming','32513','32927','Tanzania','2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(7,'134 Green Meadows','59102','East Garlandberg','Montana','43301','69132','Oman','2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(8,'8648 Samantha Drive Apt. 210','66341','O\'Connerland','Massachusetts','46665','89175','Papua New Guinea','2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(9,'1282 Ansley Highway','65692','East Aaron','Maine','83805','65449','Mauritania','2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(10,'2401 Reva Keys Apt. 211','90332','Keyonstad','Connecticut','39761','57236','Nigeria','2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(11,'8871 Maud Loaf','99622','Port Angelinaton','Utah','65082','81814','Iceland','2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(12,'2453 Tillman Mountains','52127','Lake Addison','Florida','11803','77966','Equatorial Guinea','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(13,'15361 Zachariah Coves Suite 502','74987','Sterlingville','Minnesota','69876','87442','Czech Republic','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(14,'43078 Gislason Circles','43530','South Mayfort','Kansas','36804','93172','United States of America','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(15,'16664 Fadel Mall Suite 796','29970','Domingoton','Idaho','72944','54923','Azerbaijan','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(16,'1466 Queen Valley','42175','North Rasheedfort','South Carolina','10557','65518','Myanmar','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(17,'3092 Ratke Manors','11720','South Melba','South Carolina','19501','48306','Cuba','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(18,'87950 Stroman Green Suite 830','53768','North Rosemary','Minnesota','83598','46208','Andorra','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(19,'23807 Micheal Highway','97705','New Taurean','Rhode Island','13745','58446','Bosnia and Herzegovina','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(20,'37599 Evangeline Ridge','17426','Olaland','Alaska','26931','95372','Egypt','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(21,'8242 Beahan Shoal','61219','Bernardmouth','Massachusetts','45173','64232','Montserrat','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(22,'256 Jake Junctions Apt. 933','58334','West Adalbertochester','New Hampshire','45304','64718','Lesotho','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(23,'3734 Alex Gardens Apt. 580','75129','Shanahanport','South Dakota','88105','51799','Honduras','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(24,'7952 Steve Oval Suite 960','89485','Rashadburgh','Wyoming','71626','68899','Turks and Caicos Islands','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(25,'443 Crona Parks Suite 739','15215','East Yadiramouth','Indiana','69113','52523','Niger','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(26,'40446 Jacobs Corners Suite 727','37172','Alishahaven','Oklahoma','55615','47825','Ecuador','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(27,'298 Reilly Village Suite 961','14441','West Leone','Alabama','58548','93605','Liberia','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(28,'4308 Johnathon Junction Apt. 763','68526','Port Dellville','California','64173','35604','Japan','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(29,'12839 Maureen Mountains Suite 422','90040','Manuelaberg','North Carolina','27497','12667','Bosnia and Herzegovina','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(30,'9608 Denis Valleys Apt. 087','39950','Jerdemouth','Rhode Island','63009','39128','Niger','2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(31,'Jalan Ibu Pertiwi No. 16a Gurawan, Kec. Pasar Kliwon',NULL,'Solo','Jawa Tengah',NULL,NULL,'Indonesia','2024-12-23 15:16:24','2024-12-23 15:16:59','1','1'),(32,'Jalan Ibu Pertiwi No. 16a Gurawan, Kec. Pasar Kliwon',NULL,'Solo','Jawa Tengah',NULL,NULL,'Indonesia','2025-01-02 03:41:59','2025-01-02 03:41:59','1',NULL);
/*!40000 ALTER TABLE `mst_address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_brands`
--

DROP TABLE IF EXISTS `mst_brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_brands`
--

LOCK TABLES `mst_brands` WRITE;
/*!40000 ALTER TABLE `mst_brands` DISABLE KEYS */;
INSERT INTO `mst_brands` VALUES (1,'Chalk Chicken','2024-12-25 15:21:49','2024-12-25 15:21:49',1,NULL),(2,'Goodwins','2025-01-05 14:50:42','2025-01-05 14:50:42',1,NULL),(3,'AJ Food','2025-01-05 14:53:22','2025-01-05 14:53:22',1,NULL),(4,'Ralphs Meat','2025-01-05 14:55:13','2025-01-05 14:55:13',1,NULL),(5,'Short Ribs','2025-01-05 14:58:20','2025-01-05 14:58:20',1,NULL),(6,'Mitsubishi','2025-01-18 13:09:46','2025-01-18 13:09:46',1,NULL);
/*!40000 ALTER TABLE `mst_brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_client`
--

DROP TABLE IF EXISTS `mst_client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_client` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mst_address_id` bigint unsigned DEFAULT NULL,
  `client_phone_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person_phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mst_client_type_id` bigint unsigned NOT NULL,
  `is_customer` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mst_client_mst_address_id_foreign` (`mst_address_id`),
  KEY `mst_client_mst_client_type_id_foreign` (`mst_client_type_id`),
  CONSTRAINT `mst_client_mst_address_id_foreign` FOREIGN KEY (`mst_address_id`) REFERENCES `mst_address` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mst_client_mst_client_type_id_foreign` FOREIGN KEY (`mst_client_type_id`) REFERENCES `mst_client_type` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_client`
--

LOCK TABLES `mst_client` WRITE;
/*!40000 ALTER TABLE `mst_client` DISABLE KEYS */;
INSERT INTO `mst_client` VALUES (1,'PT. Udipta Dirgantaras',1,'(021) 7355988976','nola.turner@example.net','Mr. Rigoberto Hammes DVM','979-453-4024',5,1,'2024-12-03 14:37:15','2025-01-04 03:28:53','Seeder','1'),(2,'Krajcik PLC',2,'727-821-3588','monty.jones@example.org','Tyrese Feil','+1-757-576-9502',7,0,'2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(3,'Jaskolski, Kilback and Lubowitzd',3,'+1-803-741-8114','ardith.lueilwitz@example.net','Margarete Hegmann','(309) 271-7086',5,0,'2024-12-03 14:37:15','2024-12-23 09:15:14','Seeder',NULL),(4,'Bernier, Ferry and Collins',4,'603-426-5041','paula.gulgowski@example.org','Isobel Hermann','(520) 603-0035',4,0,'2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(5,'Nikolaus, Dooley and Zboncak',5,'(704) 322-3489','elinor47@example.org','Prof. Kris Stroman II','801-777-7807',7,0,'2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(6,'Weber, Torphy and Kerluke',6,'650.354.3138','hauck.keon@example.org','Kallie Rippin II','+1-520-959-1599',1,0,'2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(7,'O\'Connell, Abshire and Blanda',7,'+1-712-449-7268','jewell.kerluke@example.org','Zackery Reichel V','+1.936.569.0681',6,0,'2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(8,'Jast Group',8,'323.643.6731','hodkiewicz.caesar@example.com','Barney Heidenreich','682.685.9213',3,0,'2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(9,'Simonis-Skiles',9,'+1.430.853.0167','blair52@example.net','Dr. Eugenia Koch III','618-367-9642',2,0,'2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(10,'Howe-Macejkovic',10,'323.620.7527','mdubuque@example.net','Prof. Walker Casper III','+1-309-685-2239',1,0,'2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(11,'Wehner, VonRueden and McCullough',11,'1-610-553-3995','terry.terrell@example.net','Hanna Kerluke DVM','(520) 312-4416',2,0,'2024-12-03 14:37:15','2024-12-03 14:37:15','Seeder',NULL),(12,'White-Hyatt',12,'+1-713-504-8272','sadie.pfannerstill@example.org','Karelle Johnson','757-771-2980',3,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(13,'Blanda-Lueilwitz',13,'(760) 328-1618','minerva34@example.org','Dariana Ziemann DVM','+19713120696',5,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(14,'Borer, Walker and Lemke',14,'539-267-3125','jnolan@example.com','Ena Abernathy','+1 (941) 797-6593',7,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(15,'Bauch-O\'Conner',15,'660-721-5273','mckenzie.aubree@example.net','Lorenz Carroll','(270) 351-3966',1,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(16,'Schaefer-Frami',16,'+1.530.771.8627','weber.jazmyne@example.org','Assunta Littel','(678) 525-8907',2,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(17,'Gleason-Mohr',17,'(640) 986-5308','mohr.makayla@example.com','Kyleigh Blick','(979) 470-6491',6,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(18,'Berge Group',18,'+1-828-805-0446','sean40@example.net','Brandi Lockman','+1-512-499-4357',2,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(19,'Kuhn, Goodwin and Wuckert',19,'+1 (270) 739-8152','maryjane52@example.com','Ruben Gerhold','(360) 869-5791',3,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(20,'Nikolaus, Feest and Abernathy',20,'478-569-9099','vada73@example.org','Mrs. Lisette Halvorson V','601.925.0207',1,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(21,'Wilkinson-Friesen',21,'+1-458-498-2449','luisa.rosenbaum@example.net','Aniyah Batz IV','713.571.0039',5,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(22,'Schaden, Dicki and Swaniawski',22,'+1-231-523-6799','franz87@example.com','Isaac Feeney','(971) 536-1451',6,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(23,'Mayert-McCullough',23,'213-425-4026','christiansen.meda@example.org','Keshawn McGlynn II','+1 (820) 439-6503',7,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(24,'Kris, Grimes and Gleason',24,'1-804-233-7808','balistreri.brisa@example.org','Victoria Bailey','+1.618.610.0308',3,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(25,'Stracke-Jones',25,'(586) 451-8676','jayne.spencer@example.net','Mrs. Priscilla Jones IV','315.819.3090',3,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(26,'Smitham, O\'Keefe and Stracke',26,'(763) 982-9726','jarred.conn@example.com','Nicholas Cole','442-951-3132',5,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(27,'Runolfsdottir, Sporer and Waters',27,'1-805-932-9983','ycremin@example.net','Dayana Mann','1-952-720-2417',5,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(28,'Considine Group',28,'+1-218-958-0957','bergstrom.wyman@example.org','Louisa Goldner','+1-541-601-1410',7,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(29,'Schneider, Goldner and Okuneva',29,'863-307-8609','eveline.langworth@example.org','Demarcus Bahringer','(508) 662-7090',4,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(30,'Weber Inc',30,'775-937-7055','charlotte24@example.org','Prof. Adelle Deckow V','414.300.8871',6,0,'2024-12-03 14:37:16','2024-12-03 14:37:16','Seeder',NULL),(31,'Testing',31,'+1 (176) 318-6726','leqyrus@mailinator.com','Minus dolorem tempor','+1 (723) 232-5681',6,0,'2024-12-18 04:24:16','2024-12-23 15:16:24','1','1'),(32,'Alana Terry',NULL,'+1 (477) 754-4785','gegubyz@mailinator.com','Dolor officia rerum','+1 (913) 406-1177',5,0,'2024-12-23 15:09:06','2024-12-23 15:09:06','1',NULL),(33,'Deborah Warner',NULL,'+1 (758) 134-5959','xynice@mailinator.com','Sit qui commodo expl','+1 (453) 166-9648',3,0,'2024-12-23 15:09:19','2024-12-23 15:09:19','1',NULL),(34,'Stephanie Cantrell',NULL,'+1 (261) 185-3088','suzoluw@mailinator.com','Aliquid aut delectus','+1 (401) 489-3398',4,0,'2024-12-30 10:16:15','2024-12-30 10:16:15','1',NULL),(35,'Alan Bowen',NULL,'+1 (911) 109-9418','parodegi@mailinator.com','Cillum voluptatem an','+1 (559) 337-9693',1,0,'2024-12-30 10:18:30','2024-12-30 10:18:30','1',NULL),(36,'Winifred Newton',NULL,'+1 (854) 585-1054','kusibira@mailinator.com','Mollitia sapiente oc','+1 (783) 441-2508',7,0,'2024-12-30 10:19:08','2024-12-30 10:19:08','1',NULL),(37,'Jane Clay',NULL,'+1 (396) 231-6748','gofyto@mailinator.com','Rerum natus sint cor','+1 (223) 175-4913',6,0,'2024-12-30 10:21:21','2024-12-30 10:21:21','1',NULL),(38,'Iris Parsons',NULL,'+1 (929) 315-1422','vodogunu@mailinator.com','Sed atque ullamco be','+1 (523) 272-9863',7,0,'2024-12-30 10:22:55','2024-12-30 10:22:55','1',NULL),(39,'Ainsley Bright',NULL,'+1 (223) 507-4342','xada@mailinator.com','Ut aperiam est Nam f','+1 (921) 709-5163',3,0,'2024-12-30 10:24:08','2024-12-30 10:24:08','1',NULL),(40,'Hilel Stanton',NULL,'+1 (746) 731-4418','cotegaho@mailinator.com','Delectus aut volupt','+1 (338) 646-8878',7,0,'2024-12-30 10:25:49','2024-12-30 10:25:49','1',NULL),(41,'Denton Frye',NULL,'+1 (406) 329-2478','melub@mailinator.com','Doloremque eius id','+1 (692) 263-7158',6,0,'2024-12-30 10:26:01','2024-12-30 10:26:01','1',NULL),(42,'Hasad Sheppard',32,'+1 (719) 416-1131','gelowan@mailinator.com','Dolor nihil quod ear','+1 (174) 457-9926',7,1,'2025-01-02 03:41:33','2025-01-02 07:26:03','1','1');
/*!40000 ALTER TABLE `mst_client` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_client_type`
--

DROP TABLE IF EXISTS `mst_client_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_client_type` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_purchase` tinyint(1) NOT NULL DEFAULT '0',
  `can_sell` tinyint(1) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_client_type`
--

LOCK TABLES `mst_client_type` WRITE;
/*!40000 ALTER TABLE `mst_client_type` DISABLE KEYS */;
INSERT INTO `mst_client_type` VALUES (1,'Importir',1,0,'Importers bring goods from overseas. Used for Purchase Orders.','2024-12-03 14:37:15','2026-01-27 06:30:24','Seeder',NULL),(2,'Distributor',1,1,'Distributors handle product distribution. Two-way trading common.','2024-12-03 14:37:15','2026-01-27 06:30:24','Seeder',NULL),(3,'Retailer',0,1,'Retailers sell to end consumers. Used for Sales Orders.','2024-12-03 14:37:15','2026-01-27 06:30:24','Seeder',NULL),(4,'Wholesaler',1,1,'Wholesalers buy/sell in bulk. Two-way trading possible.','2024-12-03 14:37:15','2026-01-27 06:30:24','Seeder',NULL),(5,'Agent',1,1,'Agents act as intermediaries. Can facilitate both directions.','2024-12-03 14:37:15','2026-01-27 06:30:24','Seeder',NULL),(6,'Supplier',1,0,'Suppliers provide goods/materials. Used for Purchase Orders.','2024-12-03 14:37:15','2026-01-27 06:30:24','Seeder',NULL),(7,'Manufacturer',1,1,'Manufacturers produce goods. Can be both supplier and customer.','2024-12-03 14:37:15','2026-01-27 06:30:24','Seeder',NULL),(8,'End Customer',0,1,'Final consumers. Used for Sales Orders only.','2026-01-27 06:30:24','2026-01-27 06:30:24','system',NULL);
/*!40000 ALTER TABLE `mst_client_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_company`
--

DROP TABLE IF EXISTS `mst_company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_company` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_company_email_unique` (`email`),
  KEY `mst_company_created_by_foreign` (`created_by`),
  KEY `mst_company_updated_by_foreign` (`updated_by`),
  CONSTRAINT `mst_company_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mst_company_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_company`
--

LOCK TABLES `mst_company` WRITE;
/*!40000 ALTER TABLE `mst_company` DISABLE KEYS */;
INSERT INTO `mst_company` VALUES (1,'PT. Perusahaan Contoh','123 Example Street','321654987','info@tokosaya.com','https://www.mycompany.com','company/BNpzRO1zRdEuAoeD8Lemb90kQYjK0AQF5i5XW366.png',1,1,'2025-01-01 08:07:46','2026-01-27 04:51:39');
/*!40000 ALTER TABLE `mst_company` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_currencies`
--

DROP TABLE IF EXISTS `mst_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exchange_rate` decimal(15,6) NOT NULL DEFAULT '1.000000',
  `is_base` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_currencies_code_unique` (`code`),
  KEY `mst_currencies_created_by_foreign` (`created_by`),
  KEY `mst_currencies_updated_by_foreign` (`updated_by`),
  CONSTRAINT `mst_currencies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mst_currencies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_currencies`
--

LOCK TABLES `mst_currencies` WRITE;
/*!40000 ALTER TABLE `mst_currencies` DISABLE KEYS */;
INSERT INTO `mst_currencies` VALUES (1,'IDR','Indonesian Rupiah','Rp',1.000000,1,1,'2026-01-27 06:30:18','2026-01-27 06:30:18',NULL,NULL),(2,'USD','US Dollar','$',16000.000000,0,1,'2026-01-27 06:30:18','2026-01-27 06:30:18',NULL,NULL),(3,'SGD','Singapore Dollar','S$',12000.000000,0,1,'2026-01-27 06:30:18','2026-01-27 06:30:18',NULL,NULL),(4,'EUR','Euro','€',17500.000000,0,1,'2026-01-27 06:30:18','2026-01-27 06:30:18',NULL,NULL),(5,'AUD','Australian Dollar','A$',10500.000000,0,1,'2026-01-27 06:30:18','2026-01-27 06:30:18',NULL,NULL),(6,'CNY','Chinese Yuan','¥',2200.000000,0,1,'2026-01-27 06:30:18','2026-01-27 06:30:18',NULL,NULL);
/*!40000 ALTER TABLE `mst_currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_product_categories`
--

DROP TABLE IF EXISTS `mst_product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_product_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mst_product_categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `mst_product_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `mst_product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_product_categories`
--

LOCK TABLES `mst_product_categories` WRITE;
/*!40000 ALTER TABLE `mst_product_categories` DISABLE KEYS */;
INSERT INTO `mst_product_categories` VALUES (1,'Daging Sapi Impor',NULL,'2024-12-25 15:22:03','2024-12-25 15:22:03',1,NULL),(2,'Daging Ayam Impor',NULL,'2025-01-05 15:30:25','2025-01-18 13:13:46',1,1),(3,'India Source',1,'2026-01-28 04:37:30','2026-01-28 04:37:53',1,1);
/*!40000 ALTER TABLE `mst_product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_products`
--

DROP TABLE IF EXISTS `mst_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cost_price` decimal(10,2) DEFAULT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `stock_quantity` int DEFAULT '0',
  `min_stock_level` int NOT NULL DEFAULT '0',
  `uom_id` bigint unsigned DEFAULT NULL,
  `mst_product_category_id` bigint unsigned NOT NULL,
  `mst_brand_id` bigint unsigned DEFAULT NULL,
  `mst_client_id` bigint unsigned DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `dimensions` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_products_sku_unique` (`sku`),
  UNIQUE KEY `mst_products_barcode_unique` (`barcode`),
  KEY `mst_products_mst_product_category_id_foreign` (`mst_product_category_id`),
  KEY `mst_products_mst_brand_id_foreign` (`mst_brand_id`),
  KEY `mst_products_mst_client_id_foreign` (`mst_client_id`),
  KEY `mst_products_uom_id_foreign` (`uom_id`),
  CONSTRAINT `mst_products_mst_brand_id_foreign` FOREIGN KEY (`mst_brand_id`) REFERENCES `mst_brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mst_products_mst_client_id_foreign` FOREIGN KEY (`mst_client_id`) REFERENCES `mst_client` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mst_products_mst_product_category_id_foreign` FOREIGN KEY (`mst_product_category_id`) REFERENCES `mst_product_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mst_products_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `mst_uom` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_products`
--

LOCK TABLES `mst_products` WRITE;
/*!40000 ALTER TABLE `mst_products` DISABLE KEYS */;
INSERT INTO `mst_products` VALUES (1,'Daging Sapi Australia','Daging Sapi Australia, premium dan berkualitas tinggi. Ditenak di padang rumput alami dengan standar ketat, daging ini lembut, kaya rasa, dan sempurna. Cocok untuk steak, rendang, hingga grill. Pilihan tepat untuk protein terbaik di meja Anda.',NULL,NULL,89000.00,80000.00,'IDR',29,0,NULL,1,1,1,1.00,NULL,NULL,1,'2024-12-25 15:22:55','2025-01-04 02:02:39',1,1),(2,'Daging Sapi Australia',NULL,NULL,NULL,90000.00,81500.00,'IDR',1100,0,NULL,1,1,2,1.00,NULL,NULL,1,'2024-12-25 15:26:02','2025-01-24 12:17:28',1,NULL),(3,'Daging Sapi Wagyu',NULL,'123123456','123123456',120000.00,110000.00,'IDR',830,0,NULL,1,1,1,1.00,NULL,NULL,1,'2024-12-27 03:48:49','2025-01-04 02:02:39',1,NULL),(4,'Kerbau India','Cupiditate ipsum iru','453264789','453264789',75000.00,55000.00,'IDR',10,1,NULL,1,1,1,1.00,NULL,NULL,0,'2024-12-27 04:19:38','2024-12-29 15:56:22',1,1),(5,'New Zealandd','Natus labore anim ei','123123123','123123123',85000.00,80000.00,'IDR',70,1,NULL,1,1,1,1.00,NULL,NULL,1,'2024-12-27 04:24:31','2025-01-06 16:25:08',1,1),(6,'Chuck Grade A','Chuck Grade A adalah potongan daging sapi berkualitas tinggi dari bagian bahu, dikenal dengan tekstur empuk dan rasa yang kaya, cocok untuk steik, semur, atau olahan panggang.',NULL,NULL,91500.00,81750.00,'IDR',200,0,NULL,1,2,2,1.00,NULL,NULL,1,'2025-01-05 15:19:31','2025-01-06 04:10:25',1,1),(7,'Rib Eye Circle','Rib Eye Circle adalah potongan daging sapi premium dengan tekstur lembut dan marbling sempurna, ideal untuk hidangan steak berkualitas restoran. Dipotong dari bagian ribeye, produk ini memberikan rasa juicy dan kaya yang memuaskan pecinta daging. Cocok untuk dipanggang, dibakar, atau dimasak sesuai selera Anda, menjadikannya pilihan terbaik untuk santapan istimewa.',NULL,NULL,95000.00,85000.00,'IDR',0,0,NULL,1,3,2,1.00,NULL,NULL,1,'2025-01-05 15:23:05','2025-01-05 15:23:05',1,NULL),(8,'ABC lamb',NULL,NULL,NULL,85000.00,80000.00,'IDR',900,0,NULL,1,2,1,1.00,NULL,NULL,1,'2025-01-13 12:19:44','2026-01-27 15:12:32',1,1);
/*!40000 ALTER TABLE `mst_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_uom`
--

DROP TABLE IF EXISTS `mst_uom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_uom` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_uom_code_unique` (`code`),
  KEY `mst_uom_created_by_foreign` (`created_by`),
  KEY `mst_uom_updated_by_foreign` (`updated_by`),
  CONSTRAINT `mst_uom_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mst_uom_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_uom`
--

LOCK TABLES `mst_uom` WRITE;
/*!40000 ALTER TABLE `mst_uom` DISABLE KEYS */;
INSERT INTO `mst_uom` VALUES (1,'KG','Kilogram','Unit of mass/weight',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(2,'G','Gram','Unit of mass/weight (1/1000 kg)',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(3,'TON','Metric Ton','Unit of mass (1000 kg)',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(4,'LB','Pound','Unit of mass (imperial)',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(5,'PCS','Pieces','Individual items/units',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(6,'UNIT','Unit','Single unit',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(7,'SET','Set','Set/collection of items',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(8,'PAIR','Pair','Two items together',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(9,'DZN','Dozen','12 pieces',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(10,'LTR','Liter','Unit of volume',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(11,'ML','Milliliter','Unit of volume (1/1000 liter)',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(12,'GAL','Gallon','Unit of volume (imperial)',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(13,'MTR','Meter','Unit of length',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(14,'CM','Centimeter','Unit of length (1/100 meter)',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(15,'MM','Millimeter','Unit of length (1/1000 meter)',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(16,'FT','Feet','Unit of length (imperial)',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(17,'IN','Inch','Unit of length (imperial)',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(18,'BOX','Box','Boxed packaging',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(19,'CTN','Carton','Carton packaging',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(20,'PACK','Pack','Pack/package',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(21,'BAG','Bag','Bag/sack packaging',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(22,'ROLL','Roll','Roll packaging',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL),(23,'PALLET','Pallet','Pallet load',1,'2026-01-27 06:30:21','2026-01-27 06:30:21',NULL,NULL);
/*!40000 ALTER TABLE `mst_uom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES ('frenchfriespeople@gmail.com','$2y$12$WPrmmZxUzR73YgGDiF9DAuXZTq1OpBSqWRqF1Jzo/2tUrseVhjXXi','2025-01-03 23:03:09');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_type` enum('purchase','sales') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` bigint unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `currency_code` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `exchange_rate` decimal(15,6) NOT NULL DEFAULT '1.000000',
  `amount` decimal(15,2) NOT NULL,
  `amount_in_base` decimal(15,2) DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer','credit_card','debit_card','cheque','giro','e_wallet','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'confirmed',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_payment_number_unique` (`payment_number`),
  KEY `payments_created_by_foreign` (`created_by`),
  KEY `payments_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `payments_payment_date_index` (`payment_date`),
  KEY `payments_payment_type_index` (`payment_type`),
  KEY `payments_status_index` (`status`),
  CONSTRAINT `payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,'PAY-2026-00001','purchase','purchase_order',8,'2026-01-27','IDR',1.000000,161750000.00,NULL,'bank_transfer',NULL,NULL,NULL,'cancelled',NULL,'2026-01-27 10:59:16','2026-01-27 11:22:32',1);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'roles.view','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(2,'roles.create','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(3,'roles.edit','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(4,'roles.delete','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(5,'users.view','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(6,'users.create','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(7,'users.edit','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(8,'users.delete','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(9,'customers.view','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(10,'customers.create','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(11,'customers.edit','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(12,'customers.delete','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(13,'transactions.view','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(14,'transactions.create','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(15,'transactions.edit','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(16,'transactions.delete','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(17,'products.view','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(18,'products.create','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(19,'products.edit','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(20,'products.delete','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(21,'brands.view','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(22,'brands.create','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(23,'brands.edit','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(24,'brands.delete','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(25,'product-categories.view','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(26,'product-categories.create','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(27,'product-categories.edit','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(28,'product-categories.delete','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(29,'company.view','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(30,'company.edit','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(31,'logs.view','web','2026-01-26 15:13:35','2026-01-26 15:13:35');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
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

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `quantity_received` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_cost` decimal(15,2) NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_order_items_uom_id_foreign` (`uom_id`),
  KEY `purchase_order_items_product_id_index` (`product_id`),
  CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `mst_products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `mst_uom` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (1,1,1,NULL,500.000,500.000,89000.00,0.00,44500000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(2,1,3,NULL,5000.000,5000.000,120000.00,0.00,600000000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(3,2,1,NULL,2.000,2.000,89000.00,0.00,178000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(4,2,3,NULL,9000.000,9000.000,120000.00,0.00,1080000000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(5,3,1,NULL,50.000,50.000,89000.00,0.00,4450000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(6,4,6,NULL,100.000,100.000,91500.00,0.00,9150000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(7,5,5,NULL,70.000,70.000,85000.00,0.00,5950000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(8,6,8,NULL,900.000,900.000,86000.00,0.00,77400000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(9,7,2,NULL,1000.000,1000.000,90000.00,0.00,90000000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(14,8,8,NULL,1000.000,50.000,80000.00,0.00,80000000.00,NULL,'2026-01-27 10:58:58','2026-01-27 11:19:59'),(15,8,6,NULL,1000.000,1000.000,81750.00,0.00,81750000.00,NULL,'2026-01-27 10:58:58','2026-01-27 11:19:59');
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `status` enum('draft','confirmed','partial','received','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `currency_code` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `exchange_rate` decimal(15,6) NOT NULL DEFAULT '1.000000',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_type` enum('percentage','fixed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `tax_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_terms` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('unpaid','partial','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_orders_created_by_foreign` (`created_by`),
  KEY `purchase_orders_updated_by_foreign` (`updated_by`),
  KEY `purchase_orders_order_date_index` (`order_date`),
  KEY `purchase_orders_status_index` (`status`),
  KEY `purchase_orders_payment_status_index` (`payment_status`),
  CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `mst_client` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_orders_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'PO-LEGACY-U243KS',1,'2024-12-27',NULL,'received','IDR',1.000000,644500000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,645000000.00,NULL,'paid',645000000.00,'Migrated from legacy transaction: U243KS','2026-01-27 09:04:39','2026-01-27 09:04:39',NULL,1),(2,'PO-LEGACY-9JA4WY',1,'2024-12-27',NULL,'confirmed','IDR',1.000000,1080178000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,1080178000.00,NULL,'paid',1080178000.00,'Migrated from legacy transaction: 9JA4WY','2026-01-27 09:04:39','2026-01-27 12:32:12',NULL,1),(3,'PO-LEGACY-Q0U1I5',1,'2024-12-28',NULL,'received','IDR',1.000000,4450000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,4450000.00,NULL,'paid',4450000.00,'Migrated from legacy transaction: Q0U1I5','2026-01-27 09:04:39','2026-01-27 09:04:39',NULL,1),(4,'PO-LEGACY-7N15QL',2,'2024-12-24',NULL,'draft','IDR',1.000000,9150000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,9150000.00,NULL,'paid',9150000.00,'Migrated from legacy transaction: 7N15QL','2026-01-27 09:04:39','2026-01-27 09:04:39',NULL,1),(5,'PO-LEGACY-ZGVB75',1,'2025-01-07',NULL,'received','IDR',1.000000,5950000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,5950000.00,NULL,'paid',5950000.00,'Migrated from legacy transaction: ZGVB75','2026-01-27 09:04:39','2026-01-27 09:04:39',NULL,1),(6,'PO-LEGACY-9XTRDO',1,'2025-01-10',NULL,'received','IDR',1.000000,77300000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,77400000.00,NULL,'paid',77400000.00,'Migrated from legacy transaction: 9XTRDO','2026-01-27 09:04:39','2026-01-27 09:04:39',NULL,1),(7,'PO-LEGACY-6PM02J',2,'2025-01-24',NULL,'draft','IDR',1.000000,90000000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,90500000.00,NULL,'paid',90500000.00,'Migrated from legacy transaction: 6PM02J','2026-01-27 09:04:39','2026-01-27 09:04:39',NULL,NULL),(8,'PO-2026-00001',2,'2026-01-27',NULL,'partial','IDR',1.000000,161750000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,161750000.00,NULL,'unpaid',0.00,NULL,'2026-01-27 10:32:41','2026-01-27 11:22:32',1,1);
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_receiving_items`
--

DROP TABLE IF EXISTS `purchase_receiving_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_receiving_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_receiving_id` bigint unsigned NOT NULL,
  `purchase_order_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity_received` decimal(15,3) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_receiving_items_purchase_receiving_id_foreign` (`purchase_receiving_id`),
  KEY `purchase_receiving_items_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `purchase_receiving_items_product_id_foreign` (`product_id`),
  CONSTRAINT `purchase_receiving_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `mst_products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_receiving_items_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_receiving_items_purchase_receiving_id_foreign` FOREIGN KEY (`purchase_receiving_id`) REFERENCES `purchase_receivings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_receiving_items`
--

LOCK TABLES `purchase_receiving_items` WRITE;
/*!40000 ALTER TABLE `purchase_receiving_items` DISABLE KEYS */;
INSERT INTO `purchase_receiving_items` VALUES (1,1,14,8,50.000,80000.00,NULL,'2026-01-27 11:19:59','2026-01-27 11:19:59'),(2,1,15,6,1000.000,81750.00,NULL,'2026-01-27 11:19:59','2026-01-27 11:19:59');
/*!40000 ALTER TABLE `purchase_receiving_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_receivings`
--

DROP TABLE IF EXISTS `purchase_receivings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_receivings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receiving_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchase_order_id` bigint unsigned NOT NULL,
  `receiving_date` date NOT NULL,
  `status` enum('draft','confirmed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_receivings_receiving_number_unique` (`receiving_number`),
  KEY `purchase_receivings_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_receivings_created_by_foreign` (`created_by`),
  KEY `purchase_receivings_receiving_date_index` (`receiving_date`),
  CONSTRAINT `purchase_receivings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_receivings_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_receivings`
--

LOCK TABLES `purchase_receivings` WRITE;
/*!40000 ALTER TABLE `purchase_receivings` DISABLE KEYS */;
INSERT INTO `purchase_receivings` VALUES (1,'RCV-2026-00001',8,'2026-01-27','confirmed',NULL,'2026-01-27 11:19:59','2026-01-27 11:19:59',1);
/*!40000 ALTER TABLE `purchase_receivings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_return_items`
--

DROP TABLE IF EXISTS `purchase_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_return_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_return_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_return_items_purchase_return_id_foreign` (`purchase_return_id`),
  KEY `purchase_return_items_product_id_foreign` (`product_id`),
  KEY `purchase_return_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `purchase_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `mst_products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_return_items_purchase_return_id_foreign` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_return_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `mst_uom` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_return_items`
--

LOCK TABLES `purchase_return_items` WRITE;
/*!40000 ALTER TABLE `purchase_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_returns`
--

DROP TABLE IF EXISTS `purchase_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchase_order_id` bigint unsigned DEFAULT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `return_date` date NOT NULL,
  `currency_code` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `exchange_rate` decimal(15,6) NOT NULL DEFAULT '1.000000',
  `status` enum('draft','confirmed','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_returns_return_number_unique` (`return_number`),
  KEY `purchase_returns_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_returns_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_returns_created_by_foreign` (`created_by`),
  KEY `purchase_returns_updated_by_foreign` (`updated_by`),
  KEY `purchase_returns_return_date_index` (`return_date`),
  KEY `purchase_returns_status_index` (`status`),
  CONSTRAINT `purchase_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_returns_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_returns_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `mst_client` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_returns_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_returns`
--

LOCK TABLES `purchase_returns` WRITE;
/*!40000 ALTER TABLE `purchase_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (5,2),(6,2),(7,2),(9,2),(10,2),(11,2),(13,2),(14,2),(15,2),(17,2),(18,2),(19,2),(21,2),(22,2),(23,2),(25,2),(26,2),(27,2),(29,2),(9,3),(13,3),(14,3),(17,3),(21,3),(25,3);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'superadmin','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(2,'admin','web','2026-01-26 15:13:35','2026-01-26 15:13:35'),(3,'staff','web','2026-01-26 15:13:35','2026-01-26 15:13:35');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_order_items`
--

DROP TABLE IF EXISTS `sales_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `quantity_shipped` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_price` decimal(15,2) NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_order_items_sales_order_id_foreign` (`sales_order_id`),
  KEY `sales_order_items_uom_id_foreign` (`uom_id`),
  KEY `sales_order_items_product_id_index` (`product_id`),
  CONSTRAINT `sales_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `mst_products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sales_order_items_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_order_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `mst_uom` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_order_items`
--

LOCK TABLES `sales_order_items` WRITE;
/*!40000 ALTER TABLE `sales_order_items` DISABLE KEYS */;
INSERT INTO `sales_order_items` VALUES (1,1,5,NULL,50.000,50.000,85000.00,NULL,0.00,4250000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(2,1,3,NULL,50.000,50.000,120000.00,NULL,0.00,6000000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(3,2,1,NULL,20.000,20.000,89000.00,NULL,0.00,1780000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(4,2,3,NULL,20.000,20.000,120000.00,NULL,0.00,2400000.00,NULL,'2026-01-27 09:04:39','2026-01-27 09:04:39'),(13,3,1,NULL,8.000,8.000,95000.00,80000.00,0.00,760000.00,NULL,'2026-01-27 11:41:14','2026-01-27 12:40:40');
/*!40000 ALTER TABLE `sales_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_orders`
--

DROP TABLE IF EXISTS `sales_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `so_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `order_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('draft','confirmed','processing','partial','shipped','delivered','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `currency_code` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `exchange_rate` decimal(15,6) NOT NULL DEFAULT '1.000000',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_type` enum('percentage','fixed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `tax_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `shipping_fee` decimal(15,2) NOT NULL DEFAULT '0.00',
  `shipping_address` text COLLATE utf8mb4_unicode_ci,
  `grand_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_terms` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('unpaid','partial','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_orders_so_number_unique` (`so_number`),
  KEY `sales_orders_customer_id_foreign` (`customer_id`),
  KEY `sales_orders_created_by_foreign` (`created_by`),
  KEY `sales_orders_updated_by_foreign` (`updated_by`),
  KEY `sales_orders_order_date_index` (`order_date`),
  KEY `sales_orders_status_index` (`status`),
  KEY `sales_orders_payment_status_index` (`payment_status`),
  CONSTRAINT `sales_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `mst_client` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sales_orders_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_orders`
--

LOCK TABLES `sales_orders` WRITE;
/*!40000 ALTER TABLE `sales_orders` DISABLE KEYS */;
INSERT INTO `sales_orders` VALUES (1,'SO-LEGACY-5Q39Z1',1,'2024-12-17',NULL,'delivered','IDR',1.000000,10250000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,120000.00,NULL,10370000.00,NULL,'paid',10370000.00,'Migrated from legacy transaction: 5Q39Z1','2026-01-27 09:04:39','2026-01-27 09:04:39',NULL,1),(2,'SO-LEGACY-6BKF04',1,'2025-01-04',NULL,'confirmed','IDR',1.000000,4180000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,150000.00,NULL,4330000.00,NULL,'paid',4330000.00,'Migrated from legacy transaction: 6BKF04','2026-01-27 09:04:39','2026-01-27 10:56:43',NULL,1),(3,'SO-2026-00001',11,'2026-01-27',NULL,'delivered','IDR',1.000000,760000.00,NULL,0.00,0.00,0,NULL,0.00,0.00,50000.00,'8871 Maud Loaf',810000.00,NULL,'unpaid',0.00,NULL,'2026-01-27 10:35:37','2026-01-27 12:40:40',1,1);
/*!40000 ALTER TABLE `sales_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_return_items`
--

DROP TABLE IF EXISTS `sales_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_return_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_return_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `uom_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition` enum('good','damaged','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'good',
  `restock` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_return_items_sales_return_id_foreign` (`sales_return_id`),
  KEY `sales_return_items_product_id_foreign` (`product_id`),
  KEY `sales_return_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `sales_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `mst_products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sales_return_items_sales_return_id_foreign` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_return_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `mst_uom` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_return_items`
--

LOCK TABLES `sales_return_items` WRITE;
/*!40000 ALTER TABLE `sales_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_returns`
--

DROP TABLE IF EXISTS `sales_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sales_order_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `return_date` date NOT NULL,
  `currency_code` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `exchange_rate` decimal(15,6) NOT NULL DEFAULT '1.000000',
  `status` enum('draft','confirmed','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `refund_method` enum('credit_note','cash_refund','replacement','none') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_returns_return_number_unique` (`return_number`),
  KEY `sales_returns_sales_order_id_foreign` (`sales_order_id`),
  KEY `sales_returns_customer_id_foreign` (`customer_id`),
  KEY `sales_returns_created_by_foreign` (`created_by`),
  KEY `sales_returns_updated_by_foreign` (`updated_by`),
  KEY `sales_returns_return_date_index` (`return_date`),
  KEY `sales_returns_status_index` (`status`),
  CONSTRAINT `sales_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_returns_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `mst_client` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sales_returns_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_returns_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_returns`
--

LOCK TABLES `sales_returns` WRITE;
/*!40000 ALTER TABLE `sales_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_shipment_items`
--

DROP TABLE IF EXISTS `sales_shipment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_shipment_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sales_shipment_id` bigint unsigned NOT NULL,
  `sales_order_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity_shipped` decimal(15,3) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_shipment_items_sales_shipment_id_foreign` (`sales_shipment_id`),
  KEY `sales_shipment_items_sales_order_item_id_foreign` (`sales_order_item_id`),
  KEY `sales_shipment_items_product_id_foreign` (`product_id`),
  CONSTRAINT `sales_shipment_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `mst_products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sales_shipment_items_sales_order_item_id_foreign` FOREIGN KEY (`sales_order_item_id`) REFERENCES `sales_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_shipment_items_sales_shipment_id_foreign` FOREIGN KEY (`sales_shipment_id`) REFERENCES `sales_shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_shipment_items`
--

LOCK TABLES `sales_shipment_items` WRITE;
/*!40000 ALTER TABLE `sales_shipment_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_shipment_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_shipments`
--

DROP TABLE IF EXISTS `sales_shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_shipments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sales_order_id` bigint unsigned NOT NULL,
  `shipment_date` date NOT NULL,
  `courier` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','shipped','in_transit','delivered') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_shipments_shipment_number_unique` (`shipment_number`),
  KEY `sales_shipments_sales_order_id_foreign` (`sales_order_id`),
  KEY `sales_shipments_created_by_foreign` (`created_by`),
  KEY `sales_shipments_shipment_date_index` (`shipment_date`),
  KEY `sales_shipments_status_index` (`status`),
  CONSTRAINT `sales_shipments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_shipments_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_shipments`
--

LOCK TABLES `sales_shipments` WRITE;
/*!40000 ALTER TABLE `sales_shipments` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_shipments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `system_logs_chk_1` CHECK (json_valid(`changed_fields`))
) ENGINE=InnoDB AUTO_INCREMENT=144 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,'App\\Models\\Brand',2,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 14:50:42','2025-01-05 14:50:42'),(2,'App\\Models\\Brand',3,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 14:53:22','2025-01-05 14:53:22'),(3,'App\\Models\\Brand',4,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 14:55:13','2025-01-05 14:55:13'),(4,'App\\Models\\Brand',5,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 14:58:20','2025-01-05 14:58:20'),(5,'App\\Models\\Product',6,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 15:15:12','2025-01-05 15:15:12'),(6,'App\\Models\\Product',7,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 15:15:18','2025-01-05 15:15:18'),(7,'App\\Models\\Product',8,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 15:19:31','2025-01-05 15:19:31'),(8,'App\\Models\\Product',7,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 15:23:05','2025-01-05 15:23:05'),(9,'App\\Models\\User',1,1,'updated','\"{\\\"name\\\":{\\\"original\\\":\\\"Abdul Kadir Syahabz\\\",\\\"updated\\\":\\\"Abdul Kadir Syahab\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-04T03:09:33.000000Z\\\",\\\"updated\\\":\\\"2025-01-05 22:24:28\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 15:24:28','2025-01-05 15:24:28'),(10,'App\\Models\\ProductCategory',2,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 15:30:25','2025-01-05 15:30:25'),(11,'App\\Models\\ProductCategory',2,1,'updated','\"{\\\"name\\\":{\\\"original\\\":\\\"Daging Ayam\\\",\\\"updated\\\":\\\"Daging Ayams\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-05T15:30:25.000000Z\\\",\\\"updated\\\":\\\"2025-01-05 22:49:09\\\"},\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 15:49:09','2025-01-05 15:49:09'),(12,'App\\Models\\ProductCategory',2,1,'updated','\"{\\\"name\\\":{\\\"original\\\":\\\"Daging Ayams\\\",\\\"updated\\\":\\\"Daging Ayam Impor\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-05T15:49:09.000000Z\\\",\\\"updated\\\":\\\"2025-01-05 22:49:46\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 15:49:46','2025-01-05 15:49:46'),(13,'App\\Models\\Product',6,1,'updated','\"{\\\"weight\\\":{\\\"original\\\":null,\\\"updated\\\":1},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-05T15:19:31.000000Z\\\",\\\"updated\\\":\\\"2025-01-05 22:51:33\\\"},\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 15:51:33','2025-01-05 15:51:33'),(14,'App\\Models\\ProductCategory',2,1,'updated','\"{\\\"name\\\":{\\\"original\\\":\\\"Daging Ayam Impor\\\",\\\"updated\\\":\\\"Daging Ayam Import\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-05T15:49:46.000000Z\\\",\\\"updated\\\":\\\"2025-01-05 22:52:53\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 15:52:53','2025-01-05 15:52:53'),(15,'App\\Models\\ProductCategory',2,1,'updated','\"{\\\"name\\\":{\\\"original\\\":\\\"Daging Ayam Import\\\",\\\"updated\\\":\\\"Daging Ayam Impor\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-05T15:52:53.000000Z\\\",\\\"updated\\\":\\\"2025-01-05 23:31:10\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 16:31:11','2025-01-05 16:31:11'),(16,'App\\Models\\ProductCategory',2,1,'updated','\"{\\\"parent_id\\\":{\\\"original\\\":null,\\\"updated\\\":2},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-05T16:31:10.000000Z\\\",\\\"updated\\\":\\\"2025-01-05 23:31:18\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 16:31:18','2025-01-05 16:31:18'),(17,'App\\Models\\ProductCategory',2,1,'updated','\"{\\\"parent_id\\\":{\\\"original\\\":2,\\\"updated\\\":null},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-05T16:31:18.000000Z\\\",\\\"updated\\\":\\\"2025-01-05 23:31:24\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 16:31:24','2025-01-05 16:31:24'),(18,'App\\Models\\Transaction',10,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 16:42:24','2025-01-05 16:42:24'),(19,'App\\Models\\TransactionDetail',89,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 16:42:24','2025-01-05 16:42:24'),(21,'App\\Models\\Product',6,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":-100,\\\"updated\\\":0}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 16:43:36','2025-01-05 16:43:36'),(22,'App\\Models\\Transaction',10,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"9150000.00\\\",\\\"updated\\\":9150000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2024-12-15 00:00:00\\\",\\\"updated\\\":\\\"2024-12-15\\\"},\\\"status\\\":{\\\"original\\\":\\\"pending\\\",\\\"updated\\\":\\\"completed\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-05T16:42:24.000000Z\\\",\\\"updated\\\":\\\"2025-01-05 23:43:36\\\"},\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 16:43:36','2025-01-05 16:43:36'),(23,'App\\Models\\TransactionDetail',90,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 16:43:36','2025-01-05 16:43:36'),(24,'App\\Models\\TransactionLog',16,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 16:43:36','2025-01-05 16:43:36'),(25,'App\\Models\\Product',6,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":0,\\\"updated\\\":-100}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 02:17:31','2025-01-06 02:17:31'),(26,'App\\Models\\Transaction',10,1,'deleted','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 02:17:31','2025-01-06 02:17:31'),(27,'App\\Models\\TransactionLog',17,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 02:17:31','2025-01-06 02:17:31'),(28,'App\\Models\\Transaction',11,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 02:18:27','2025-01-06 02:18:27'),(29,'App\\Models\\TransactionDetail',91,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 02:18:27','2025-01-06 02:18:27'),(30,'App\\Models\\Product',6,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":0,\\\"updated\\\":100}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 02:18:27','2025-01-06 02:18:27'),(31,'App\\Models\\Product',6,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":100,\\\"updated\\\":200}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 03:08:35','2025-01-06 03:08:35'),(32,'App\\Models\\Product',6,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":200,\\\"updated\\\":50}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 03:08:35','2025-01-06 03:08:35'),(33,'App\\Models\\Transaction',11,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"9150000.00\\\",\\\"updated\\\":4575000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2024-12-24 00:00:00\\\",\\\"updated\\\":\\\"2024-12-24\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-06T02:18:27.000000Z\\\",\\\"updated\\\":\\\"2025-01-06 10:08:35\\\"},\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 03:08:35','2025-01-06 03:08:35'),(34,'App\\Models\\TransactionDetail',92,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 03:08:35','2025-01-06 03:08:35'),(35,'App\\Models\\TransactionLog',18,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 03:08:35','2025-01-06 03:08:35'),(44,'App\\Models\\Product',6,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":50,\\\"updated\\\":100}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:10:25','2025-01-06 04:10:25'),(45,'App\\Models\\Product',6,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":100,\\\"updated\\\":200}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:10:25','2025-01-06 04:10:25'),(46,'App\\Models\\Transaction',11,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"4575000.00\\\",\\\"updated\\\":9150000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2024-12-24 00:00:00\\\",\\\"updated\\\":\\\"2024-12-24\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-06T03:08:35.000000Z\\\",\\\"updated\\\":\\\"2025-01-06 11:10:25\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:10:25','2025-01-06 04:10:25'),(47,'App\\Models\\TransactionDetail',95,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:10:25','2025-01-06 04:10:25'),(48,'App\\Models\\Transaction',12,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:11:43','2025-01-06 04:11:43'),(49,'App\\Models\\TransactionDetail',96,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:11:43','2025-01-06 04:11:43'),(50,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":0,\\\"updated\\\":100}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:11:43','2025-01-06 04:11:43'),(51,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":100,\\\"updated\\\":200}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:12:35','2025-01-06 04:12:35'),(52,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":200,\\\"updated\\\":280}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:12:35','2025-01-06 04:12:35'),(53,'App\\Models\\Transaction',12,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"8500000.00\\\",\\\"updated\\\":6800000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2025-01-03 00:00:00\\\",\\\"updated\\\":\\\"2025-01-03\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-06T04:11:43.000000Z\\\",\\\"updated\\\":\\\"2025-01-06 11:12:35\\\"},\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:12:35','2025-01-06 04:12:35'),(54,'App\\Models\\TransactionDetail',97,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 04:12:35','2025-01-06 04:12:35'),(55,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":80,\\\"updated\\\":160}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 10:59:48','2025-01-06 10:59:48'),(56,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":160,\\\"updated\\\":230}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 10:59:48','2025-01-06 10:59:48'),(57,'App\\Models\\Transaction',12,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"6800000.00\\\",\\\"updated\\\":5950000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2025-01-03 00:00:00\\\",\\\"updated\\\":\\\"2025-01-03\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-06T04:12:35.000000Z\\\",\\\"updated\\\":\\\"2025-01-06 17:59:48\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 10:59:48','2025-01-06 10:59:48'),(58,'App\\Models\\TransactionDetail',98,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 10:59:48','2025-01-06 10:59:48'),(59,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":230,\\\"updated\\\":160}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 11:00:19','2025-01-06 11:00:19'),(60,'App\\Models\\Transaction',12,1,'deleted','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 11:00:19','2025-01-06 11:00:19'),(61,'App\\Models\\TransactionLog',19,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 11:00:19','2025-01-06 11:00:19'),(62,'App\\Models\\Transaction',13,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 11:00:53','2025-01-06 11:00:53'),(63,'App\\Models\\TransactionDetail',99,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 11:00:53','2025-01-06 11:00:53'),(64,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":0,\\\"updated\\\":100}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 11:00:53','2025-01-06 11:00:53'),(65,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":100,\\\"updated\\\":200}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:15:18','2025-01-06 16:15:18'),(66,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":200,\\\"updated\\\":280}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:15:18','2025-01-06 16:15:18'),(67,'App\\Models\\Transaction',13,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"8500000.00\\\",\\\"updated\\\":6800000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2025-01-07 00:00:00\\\",\\\"updated\\\":\\\"2025-01-07\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-06T11:00:53.000000Z\\\",\\\"updated\\\":\\\"2025-01-06 23:15:18\\\"},\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:15:18','2025-01-06 16:15:18'),(68,'App\\Models\\TransactionDetail',100,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:15:18','2025-01-06 16:15:18'),(69,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":80,\\\"updated\\\":160}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:17:04','2025-01-06 16:17:04'),(70,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":160,\\\"updated\\\":230}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:17:04','2025-01-06 16:17:04'),(71,'App\\Models\\Transaction',13,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"6800000.00\\\",\\\"updated\\\":5950000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2025-01-07 00:00:00\\\",\\\"updated\\\":\\\"2025-01-07\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-06T16:15:18.000000Z\\\",\\\"updated\\\":\\\"2025-01-06 23:17:04\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:17:04','2025-01-06 16:17:04'),(72,'App\\Models\\TransactionDetail',101,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:17:04','2025-01-06 16:17:04'),(73,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":230,\\\"updated\\\":240}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:23:33','2025-01-06 16:23:33'),(74,'App\\Models\\Transaction',13,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"5950000.00\\\",\\\"updated\\\":6800000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2025-01-07 00:00:00\\\",\\\"updated\\\":\\\"2025-01-07\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-06T16:17:04.000000Z\\\",\\\"updated\\\":\\\"2025-01-06 23:23:33\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:23:33','2025-01-06 16:23:33'),(75,'App\\Models\\TransactionDetail',102,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:23:33','2025-01-06 16:23:33'),(76,'App\\Models\\Product',5,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":80,\\\"updated\\\":70}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:23:54','2025-01-06 16:23:54'),(77,'App\\Models\\Transaction',13,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"6800000.00\\\",\\\"updated\\\":5950000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2025-01-07 00:00:00\\\",\\\"updated\\\":\\\"2025-01-07\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-06T16:23:33.000000Z\\\",\\\"updated\\\":\\\"2025-01-06 23:23:54\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:23:54','2025-01-06 16:23:54'),(78,'App\\Models\\TransactionDetail',103,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:23:54','2025-01-06 16:23:54'),(79,'App\\Models\\Product',5,1,'updated','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:25:08','2025-01-06 16:25:08'),(80,'App\\Models\\Transaction',13,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"5950000.00\\\",\\\"updated\\\":5950000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2025-01-07 00:00:00\\\",\\\"updated\\\":\\\"2025-01-07\\\"},\\\"status\\\":{\\\"original\\\":\\\"pending\\\",\\\"updated\\\":\\\"completed\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-06T16:23:54.000000Z\\\",\\\"updated\\\":\\\"2025-01-06 23:25:08\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:25:08','2025-01-06 16:25:08'),(81,'App\\Models\\TransactionDetail',104,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 16:25:08','2025-01-06 16:25:08'),(82,'App\\Models\\Product',8,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-13 12:19:44','2025-01-13 12:19:44'),(83,'App\\Models\\Transaction',14,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-13 12:24:29','2025-01-13 12:24:29'),(84,'App\\Models\\TransactionDetail',105,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-13 12:24:29','2025-01-13 12:24:29'),(85,'App\\Models\\Product',8,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":0,\\\"updated\\\":1000},\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-13 12:24:29','2025-01-13 12:24:29'),(86,'App\\Models\\Product',8,1,'updated','\"{\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-13 12:24:43','2025-01-13 12:24:43'),(87,'App\\Models\\Transaction',14,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"86100000.00\\\",\\\"updated\\\":86000000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2025-01-10 00:00:00\\\",\\\"updated\\\":\\\"2025-01-10\\\"},\\\"status\\\":{\\\"original\\\":\\\"pending\\\",\\\"updated\\\":\\\"completed\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-13T12:24:29.000000Z\\\",\\\"updated\\\":\\\"2025-01-13 19:24:43\\\"},\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-13 12:24:43','2025-01-13 12:24:43'),(88,'App\\Models\\TransactionDetail',106,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-13 12:24:43','2025-01-13 12:24:43'),(89,'App\\Models\\Product',8,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":1000,\\\"updated\\\":900},\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-13 12:25:41','2025-01-13 12:25:41'),(90,'App\\Models\\Transaction',14,1,'updated','\"{\\\"grand_total\\\":{\\\"original\\\":\\\"86000000.00\\\",\\\"updated\\\":77400000},\\\"transaction_date\\\":{\\\"original\\\":\\\"2025-01-10 00:00:00\\\",\\\"updated\\\":\\\"2025-01-10\\\"},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-13T12:24:43.000000Z\\\",\\\"updated\\\":\\\"2025-01-13 19:25:41\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-13 12:25:41','2025-01-13 12:25:41'),(91,'App\\Models\\TransactionDetail',107,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-13 12:25:41','2025-01-13 12:25:41'),(92,'App\\Models\\Brand',6,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-18 13:09:46','2025-01-18 13:09:46'),(93,'App\\Models\\ProductCategory',2,1,'updated','\"{\\\"parent_id\\\":{\\\"original\\\":null,\\\"updated\\\":1},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-05T16:31:24.000000Z\\\",\\\"updated\\\":\\\"2025-01-18 20:13:40\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-18 13:13:40','2025-01-18 13:13:40'),(94,'App\\Models\\ProductCategory',2,1,'updated','\"{\\\"parent_id\\\":{\\\"original\\\":1,\\\"updated\\\":null},\\\"updated_at\\\":{\\\"original\\\":\\\"2025-01-18T13:13:40.000000Z\\\",\\\"updated\\\":\\\"2025-01-18 20:13:46\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-18 13:13:46','2025-01-18 13:13:46'),(95,'App\\Models\\Transaction',15,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36','2025-01-24 12:17:28','2025-01-24 12:17:28'),(96,'App\\Models\\TransactionDetail',108,1,'created','\"[]\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36','2025-01-24 12:17:28','2025-01-24 12:17:28'),(97,'App\\Models\\Product',2,1,'updated','\"{\\\"stock_quantity\\\":{\\\"original\\\":100,\\\"updated\\\":1100},\\\"updated_by\\\":{\\\"original\\\":null,\\\"updated\\\":1}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36','2025-01-24 12:17:28','2025-01-24 12:17:28'),(98,'App\\Models\\User',1,1,'updated','\"{\\\"remember_token\\\":{\\\"original\\\":\\\"KPcdi6Y56Kik9LWjlW22xH9zemmBxRlSOjyZCC5vMU0Rz08nIdhFyGEOddCc\\\",\\\"updated\\\":\\\"JLIe5WuVkjCT2xilIiLKDStV2dRddIjnAjUX1qGq1p66BJiQ6KzegtUyuWy1\\\"}}\"','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 14:42:03','2026-01-26 14:42:03'),(99,'App\\Models\\User',61,1,'updated','{\"old\":{\"is_active\":true,\"updated_at\":\"2025-01-04T02:40:56.000000Z\"},\"new\":{\"is_active\":false,\"updated_at\":\"2026-01-26 23:12:40\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 16:12:41','2026-01-26 16:12:41'),(100,'App\\Models\\User',61,1,'updated','{\"old\":{\"is_active\":false,\"updated_at\":\"2026-01-26T16:12:40.000000Z\"},\"new\":{\"is_active\":true,\"updated_at\":\"2026-01-26 23:12:47\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 16:12:47','2026-01-26 16:12:47'),(101,'App\\Models\\User',61,1,'updated','{\"old\":{\"email\":\"46\",\"updated_by\":null,\"updated_at\":\"2026-01-26T16:12:47.000000Z\"},\"new\":{\"email\":\"mail@email.test\",\"updated_by\":1,\"updated_at\":\"2026-01-26 23:21:50\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 16:21:50','2026-01-26 16:21:50'),(102,'App\\Models\\User',62,1,'created','{\"old\":null,\"new\":{\"name\":\"Yow\",\"email\":\"yow@email.com\",\"password\":\"$2y$12$A0vHDW3FeRWrzLm2Ne1c\\/eqkCJ2uktRPbvVcEcpCtm7yNjnaJmZMa\",\"is_active\":true,\"created_by\":1,\"updated_at\":\"2026-01-26 23:25:16\",\"created_at\":\"2026-01-26 23:25:16\",\"id\":62}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 16:25:16','2026-01-26 16:25:16'),(103,'App\\Models\\User',62,1,'updated','{\"old\":{\"name\":\"Yow\",\"updated_by\":null,\"updated_at\":\"2026-01-26T16:25:16.000000Z\"},\"new\":{\"name\":\"Yownga\",\"updated_by\":1,\"updated_at\":\"2026-01-26 23:29:20\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 16:29:20','2026-01-26 16:29:20'),(104,'App\\Models\\User',62,1,'updated','{\"old\":{\"email\":\"yow@email.com\",\"updated_at\":\"2026-01-26T16:29:20.000000Z\"},\"new\":{\"email\":\"yownga@email.com\",\"updated_at\":\"2026-01-26 23:29:30\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 16:29:30','2026-01-26 16:29:30'),(105,'App\\Models\\User',60,1,'deleted','{\"old\":{\"id\":60,\"name\":\"Coba palsu 2\",\"email\":\"23\",\"is_active\":1,\"email_verified_at\":null,\"password\":\"$2y$12$Y1eXgutHPi8YfQnYmv7iZutyH7ZiwWMxybhn\\/1ubEW..BNAd9GoLG\",\"two_factor_secret\":null,\"two_factor_recovery_codes\":null,\"two_factor_confirmed_at\":null,\"remember_token\":null,\"created_by\":null,\"updated_by\":null,\"current_team_id\":null,\"profile_photo_path\":null,\"created_at\":\"2025-01-04 09:35:19\",\"updated_at\":\"2025-01-04 09:35:19\"},\"new\":null}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 16:29:44','2026-01-26 16:29:44'),(106,'App\\Models\\User',57,1,'deleted','{\"old\":{\"id\":57,\"name\":\"Coba palsu 2\",\"email\":\"1\",\"is_active\":1,\"email_verified_at\":null,\"password\":\"$2y$12$HzZ1dG5AoAxL4g1kfQpGCuuXhlwV9oZkhC6kWx2sksSD9sT2Q6126\",\"two_factor_secret\":null,\"two_factor_recovery_codes\":null,\"two_factor_confirmed_at\":null,\"remember_token\":null,\"created_by\":null,\"updated_by\":null,\"current_team_id\":null,\"profile_photo_path\":null,\"created_at\":\"2025-01-04 09:27:10\",\"updated_at\":\"2025-01-04 09:27:10\"},\"new\":null}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 16:29:52','2026-01-26 16:29:52'),(107,'App\\Models\\User',59,1,'updated','{\"old\":{\"is_active\":true,\"updated_at\":\"2025-01-04T02:31:03.000000Z\"},\"new\":{\"is_active\":false,\"updated_at\":\"2026-01-27 00:07:34\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 17:07:34','2026-01-26 17:07:34'),(108,'App\\Models\\User',59,1,'updated','{\"old\":{\"is_active\":false,\"updated_at\":\"2026-01-26T17:07:34.000000Z\"},\"new\":{\"is_active\":true,\"updated_at\":\"2026-01-27 00:07:38\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-26 17:07:38','2026-01-26 17:07:38'),(109,'App\\Models\\Company',1,1,'updated','{\"old\":{\"name\":\"BKPI\",\"updated_at\":\"2025-01-01T16:07:58.000000Z\"},\"new\":{\"name\":\"PT. Perusahaan Contoh\",\"updated_at\":\"2026-01-27 11:27:47\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 04:27:47','2026-01-27 04:27:47'),(110,'App\\Models\\Company',1,1,'updated','{\"old\":{\"email\":\"info@mycompany.com\",\"updated_at\":\"2026-01-27T04:27:47.000000Z\"},\"new\":{\"email\":\"info@tokosaya.com\",\"updated_at\":\"2026-01-27 11:28:08\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 04:28:08','2026-01-27 04:28:08'),(111,'App\\Models\\Company',1,1,'updated','{\"old\":{\"phone\":\"123-456-7890\",\"updated_at\":\"2026-01-27T04:28:08.000000Z\"},\"new\":{\"phone\":\"321654987\",\"updated_at\":\"2026-01-27 11:51:39\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 04:51:39','2026-01-27 04:51:39'),(112,'App\\Models\\PurchaseOrder',1,NULL,'created','{\"old\":null,\"new\":{\"po_number\":\"PO-LEGACY-U243KS\",\"supplier_id\":1,\"order_date\":\"2024-12-27 00:00:00\",\"status\":\"received\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"subtotal\":644500000,\"grand_total\":\"645000000.00\",\"payment_status\":\"paid\",\"amount_paid\":\"645000000.00\",\"notes\":\"Migrated from legacy transaction: U243KS\",\"created_by\":null,\"updated_by\":1,\"updated_at\":\"2026-01-27 16:04:39\",\"created_at\":\"2026-01-27 16:04:39\",\"id\":1}}','127.0.0.1','Symfony','2026-01-27 09:04:39','2026-01-27 09:04:39'),(113,'App\\Models\\PurchaseOrder',2,NULL,'created','{\"old\":null,\"new\":{\"po_number\":\"PO-LEGACY-9JA4WY\",\"supplier_id\":1,\"order_date\":\"2024-12-27 00:00:00\",\"status\":\"draft\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"subtotal\":1080178000,\"grand_total\":\"1080178000.00\",\"payment_status\":\"paid\",\"amount_paid\":\"1080178000.00\",\"notes\":\"Migrated from legacy transaction: 9JA4WY\",\"created_by\":null,\"updated_by\":1,\"updated_at\":\"2026-01-27 16:04:39\",\"created_at\":\"2026-01-27 16:04:39\",\"id\":2}}','127.0.0.1','Symfony','2026-01-27 09:04:39','2026-01-27 09:04:39'),(114,'App\\Models\\PurchaseOrder',3,NULL,'created','{\"old\":null,\"new\":{\"po_number\":\"PO-LEGACY-Q0U1I5\",\"supplier_id\":1,\"order_date\":\"2024-12-28 00:00:00\",\"status\":\"received\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"subtotal\":4450000,\"grand_total\":\"4450000.00\",\"payment_status\":\"paid\",\"amount_paid\":\"4450000.00\",\"notes\":\"Migrated from legacy transaction: Q0U1I5\",\"created_by\":null,\"updated_by\":1,\"updated_at\":\"2026-01-27 16:04:39\",\"created_at\":\"2026-01-27 16:04:39\",\"id\":3}}','127.0.0.1','Symfony','2026-01-27 09:04:39','2026-01-27 09:04:39'),(115,'App\\Models\\SalesOrder',1,NULL,'created','{\"old\":null,\"new\":{\"so_number\":\"SO-LEGACY-5Q39Z1\",\"customer_id\":1,\"order_date\":\"2024-12-17 00:00:00\",\"status\":\"delivered\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"subtotal\":10250000,\"shipping_fee\":\"120000.00\",\"grand_total\":\"10370000.00\",\"payment_status\":\"paid\",\"amount_paid\":\"10370000.00\",\"notes\":\"Migrated from legacy transaction: 5Q39Z1\",\"created_by\":null,\"updated_by\":1,\"updated_at\":\"2026-01-27 16:04:39\",\"created_at\":\"2026-01-27 16:04:39\",\"id\":1}}','127.0.0.1','Symfony','2026-01-27 09:04:39','2026-01-27 09:04:39'),(116,'App\\Models\\SalesOrder',2,NULL,'created','{\"old\":null,\"new\":{\"so_number\":\"SO-LEGACY-6BKF04\",\"customer_id\":1,\"order_date\":\"2025-01-04 00:00:00\",\"status\":\"draft\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"subtotal\":4180000,\"shipping_fee\":\"150000.00\",\"grand_total\":\"4330000.00\",\"payment_status\":\"paid\",\"amount_paid\":\"4330000.00\",\"notes\":\"Migrated from legacy transaction: 6BKF04\",\"created_by\":null,\"updated_by\":null,\"updated_at\":\"2026-01-27 16:04:39\",\"created_at\":\"2026-01-27 16:04:39\",\"id\":2}}','127.0.0.1','Symfony','2026-01-27 09:04:39','2026-01-27 09:04:39'),(117,'App\\Models\\PurchaseOrder',4,NULL,'created','{\"old\":null,\"new\":{\"po_number\":\"PO-LEGACY-7N15QL\",\"supplier_id\":2,\"order_date\":\"2024-12-24 00:00:00\",\"status\":\"draft\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"subtotal\":9150000,\"grand_total\":\"9150000.00\",\"payment_status\":\"paid\",\"amount_paid\":\"9150000.00\",\"notes\":\"Migrated from legacy transaction: 7N15QL\",\"created_by\":null,\"updated_by\":1,\"updated_at\":\"2026-01-27 16:04:39\",\"created_at\":\"2026-01-27 16:04:39\",\"id\":4}}','127.0.0.1','Symfony','2026-01-27 09:04:39','2026-01-27 09:04:39'),(118,'App\\Models\\PurchaseOrder',5,NULL,'created','{\"old\":null,\"new\":{\"po_number\":\"PO-LEGACY-ZGVB75\",\"supplier_id\":1,\"order_date\":\"2025-01-07 00:00:00\",\"status\":\"received\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"subtotal\":5950000,\"grand_total\":\"5950000.00\",\"payment_status\":\"paid\",\"amount_paid\":\"5950000.00\",\"notes\":\"Migrated from legacy transaction: ZGVB75\",\"created_by\":null,\"updated_by\":1,\"updated_at\":\"2026-01-27 16:04:39\",\"created_at\":\"2026-01-27 16:04:39\",\"id\":5}}','127.0.0.1','Symfony','2026-01-27 09:04:39','2026-01-27 09:04:39'),(119,'App\\Models\\PurchaseOrder',6,NULL,'created','{\"old\":null,\"new\":{\"po_number\":\"PO-LEGACY-9XTRDO\",\"supplier_id\":1,\"order_date\":\"2025-01-10 00:00:00\",\"status\":\"received\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"subtotal\":77300000,\"grand_total\":\"77400000.00\",\"payment_status\":\"paid\",\"amount_paid\":\"77400000.00\",\"notes\":\"Migrated from legacy transaction: 9XTRDO\",\"created_by\":null,\"updated_by\":1,\"updated_at\":\"2026-01-27 16:04:39\",\"created_at\":\"2026-01-27 16:04:39\",\"id\":6}}','127.0.0.1','Symfony','2026-01-27 09:04:39','2026-01-27 09:04:39'),(120,'App\\Models\\PurchaseOrder',7,NULL,'created','{\"old\":null,\"new\":{\"po_number\":\"PO-LEGACY-6PM02J\",\"supplier_id\":2,\"order_date\":\"2025-01-24 00:00:00\",\"status\":\"draft\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"subtotal\":90000000,\"grand_total\":\"90500000.00\",\"payment_status\":\"paid\",\"amount_paid\":\"90500000.00\",\"notes\":\"Migrated from legacy transaction: 6PM02J\",\"created_by\":null,\"updated_by\":null,\"updated_at\":\"2026-01-27 16:04:39\",\"created_at\":\"2026-01-27 16:04:39\",\"id\":7}}','127.0.0.1','Symfony','2026-01-27 09:04:39','2026-01-27 09:04:39'),(121,'App\\Models\\PurchaseOrder',8,1,'created','{\"old\":null,\"new\":{\"supplier_id\":2,\"order_date\":\"2026-01-27 00:00:00\",\"expected_date\":null,\"status\":\"draft\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"discount_type\":null,\"discount_value\":0,\"tax_enabled\":false,\"tax_name\":null,\"tax_percentage\":0,\"payment_terms\":null,\"notes\":null,\"created_by\":1,\"po_number\":\"PO-2026-00001\",\"updated_at\":\"2026-01-27 17:32:41\",\"created_at\":\"2026-01-27 17:32:41\",\"id\":8}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 10:32:41','2026-01-27 10:32:41'),(122,'App\\Models\\PurchaseOrder',8,1,'updated','{\"old\":{\"subtotal\":null,\"discount_amount\":null,\"tax_amount\":null,\"grand_total\":null,\"updated_by\":null},\"new\":{\"subtotal\":161750000,\"discount_amount\":\"0.00\",\"tax_amount\":0,\"grand_total\":161750000,\"updated_by\":1}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 10:32:41','2026-01-27 10:32:41'),(123,'App\\Models\\SalesOrder',3,1,'created','{\"old\":null,\"new\":{\"customer_id\":11,\"order_date\":\"2026-01-27 00:00:00\",\"due_date\":null,\"status\":\"draft\",\"currency_code\":\"IDR\",\"exchange_rate\":1,\"discount_type\":null,\"discount_value\":0,\"tax_enabled\":false,\"tax_name\":null,\"tax_percentage\":0,\"shipping_fee\":0,\"shipping_address\":\"8871 Maud Loaf\",\"payment_terms\":null,\"notes\":null,\"created_by\":1,\"so_number\":\"SO-2026-00001\",\"updated_at\":\"2026-01-27 17:35:37\",\"created_at\":\"2026-01-27 17:35:37\",\"id\":3}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 10:35:37','2026-01-27 10:35:37'),(124,'App\\Models\\SalesOrder',3,1,'updated','{\"old\":{\"subtotal\":null,\"discount_amount\":null,\"tax_amount\":null,\"grand_total\":null,\"updated_by\":null},\"new\":{\"subtotal\":985000,\"discount_amount\":\"0.00\",\"tax_amount\":0,\"grand_total\":985000,\"updated_by\":1}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 10:35:37','2026-01-27 10:35:37'),(125,'App\\Models\\SalesOrder',3,1,'updated','{\"old\":{\"subtotal\":\"985000.00\",\"grand_total\":\"985000.00\",\"updated_at\":\"2026-01-27T10:35:37.000000Z\"},\"new\":{\"subtotal\":1045000,\"grand_total\":1045000,\"updated_at\":\"2026-01-27 17:55:51\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 10:55:51','2026-01-27 10:55:51'),(126,'App\\Models\\SalesOrder',2,1,'updated','{\"old\":{\"status\":\"draft\",\"updated_at\":\"2026-01-27T09:04:39.000000Z\",\"updated_by\":null},\"new\":{\"status\":\"confirmed\",\"updated_at\":\"2026-01-27 17:56:43\",\"updated_by\":1}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 10:56:43','2026-01-27 10:56:43'),(127,'App\\Models\\SalesOrder',3,1,'updated','{\"old\":{\"subtotal\":\"1045000.00\",\"grand_total\":\"1045000.00\",\"updated_at\":\"2026-01-27T10:55:51.000000Z\"},\"new\":{\"subtotal\":950000,\"grand_total\":950000,\"updated_at\":\"2026-01-27 17:57:24\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 10:57:24','2026-01-27 10:57:24'),(128,'App\\Models\\PurchaseOrder',8,1,'updated','{\"old\":{\"status\":\"draft\",\"updated_at\":\"2026-01-27T10:32:41.000000Z\"},\"new\":{\"status\":\"confirmed\",\"updated_at\":\"2026-01-27 17:58:58\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 10:58:58','2026-01-27 10:58:58'),(129,'App\\Models\\PurchaseOrder',8,1,'updated','{\"old\":{\"payment_status\":\"unpaid\",\"amount_paid\":\"0.00\",\"updated_at\":\"2026-01-27T10:58:58.000000Z\"},\"new\":{\"payment_status\":\"paid\",\"amount_paid\":\"161750000.00\",\"updated_at\":\"2026-01-27 17:59:16\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 10:59:16','2026-01-27 10:59:16'),(130,'App\\Models\\PurchaseOrder',8,1,'updated','{\"old\":{\"payment_status\":\"unpaid\",\"amount_paid\":\"0.00\",\"updated_at\":\"2026-01-27T10:58:58.000000Z\"},\"new\":{\"payment_status\":\"paid\",\"amount_paid\":\"161750000.00\",\"updated_at\":\"2026-01-27 17:59:16\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 10:59:16','2026-01-27 10:59:16'),(131,'App\\Models\\PurchaseOrder',8,1,'updated','{\"old\":{\"status\":\"confirmed\",\"updated_at\":\"2026-01-27T10:59:16.000000Z\"},\"new\":{\"status\":\"partial\",\"updated_at\":\"2026-01-27 18:19:59\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 11:19:59','2026-01-27 11:19:59'),(132,'App\\Models\\PurchaseOrder',8,1,'updated','{\"old\":{\"payment_status\":\"paid\",\"amount_paid\":\"161750000.00\",\"updated_at\":\"2026-01-27T11:19:59.000000Z\"},\"new\":{\"payment_status\":\"unpaid\",\"amount_paid\":0,\"updated_at\":\"2026-01-27 18:22:32\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 11:22:32','2026-01-27 11:22:32'),(133,'App\\Models\\SalesOrder',3,1,'updated','{\"old\":{\"shipping_fee\":\"0.00\",\"updated_at\":\"2026-01-27T10:57:24.000000Z\"},\"new\":{\"shipping_fee\":50000,\"updated_at\":\"2026-01-27 18:36:00\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 11:36:00','2026-01-27 11:36:00'),(134,'App\\Models\\SalesOrder',3,1,'updated','{\"old\":{\"grand_total\":\"950000.00\"},\"new\":{\"grand_total\":1000000}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 11:36:00','2026-01-27 11:36:00'),(135,'App\\Models\\SalesOrder',3,1,'updated','{\"old\":{\"subtotal\":\"950000.00\",\"grand_total\":\"1000000.00\",\"updated_at\":\"2026-01-27T11:38:15.000000Z\"},\"new\":{\"subtotal\":760000,\"grand_total\":810000,\"updated_at\":\"2026-01-27 18:41:14\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 11:41:14','2026-01-27 11:41:14'),(136,'App\\Models\\SalesOrder',3,1,'updated','{\"old\":{\"status\":\"draft\",\"updated_at\":\"2026-01-27T11:41:14.000000Z\"},\"new\":{\"status\":\"confirmed\",\"updated_at\":\"2026-01-27 18:41:15\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 11:41:15','2026-01-27 11:41:15'),(137,'App\\Models\\PurchaseOrder',2,1,'updated','{\"old\":{\"status\":\"draft\",\"updated_at\":\"2026-01-27T09:04:39.000000Z\"},\"new\":{\"status\":\"confirmed\",\"updated_at\":\"2026-01-27 19:32:12\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 12:32:12','2026-01-27 12:32:12'),(138,'App\\Models\\SalesOrder',3,1,'updated','{\"old\":{\"status\":\"confirmed\",\"updated_at\":\"2026-01-27T11:41:15.000000Z\"},\"new\":{\"status\":\"shipped\",\"updated_at\":\"2026-01-27 19:36:09\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 12:36:09','2026-01-27 12:36:09'),(139,'App\\Models\\SalesOrder',3,1,'updated','{\"old\":{\"status\":\"shipped\",\"updated_at\":\"2026-01-27T12:36:09.000000Z\"},\"new\":{\"status\":\"delivered\",\"updated_at\":\"2026-01-27 19:40:40\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 12:40:40','2026-01-27 12:40:40'),(140,'App\\Models\\Product',8,1,'updated','{\"old\":{\"price\":\"85000.02\",\"updated_at\":\"2025-01-13T12:25:41.000000Z\",\"updated_by\":null},\"new\":{\"price\":85000.06,\"updated_at\":\"2026-01-27 22:12:22\",\"updated_by\":1}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 15:12:22','2026-01-27 15:12:22'),(141,'App\\Models\\Product',8,1,'updated','{\"old\":{\"price\":\"85000.06\",\"updated_at\":\"2026-01-27T15:12:22.000000Z\"},\"new\":{\"price\":85000,\"updated_at\":\"2026-01-27 22:12:32\"}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 15:12:32','2026-01-27 15:12:32'),(142,'App\\Models\\ProductCategory',3,1,'created','{\"old\":null,\"new\":{\"name\":\"India\",\"parent_id\":1,\"created_by\":1,\"updated_at\":\"2026-01-28 11:37:30\",\"created_at\":\"2026-01-28 11:37:30\",\"id\":3}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-28 04:37:30','2026-01-28 04:37:30'),(143,'App\\Models\\ProductCategory',3,1,'updated','{\"old\":{\"name\":\"India\",\"updated_at\":\"2026-01-28T04:37:30.000000Z\",\"updated_by\":null},\"new\":{\"name\":\"India Source\",\"updated_at\":\"2026-01-28 11:37:53\",\"updated_by\":1}}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-28 04:37:53','2026-01-28 04:37:53');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team_invitations`
--

DROP TABLE IF EXISTS `team_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_invitations_team_id_email_unique` (`team_id`,`email`),
  CONSTRAINT `team_invitations_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team_invitations`
--

LOCK TABLES `team_invitations` WRITE;
/*!40000 ALTER TABLE `team_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `team_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team_user`
--

DROP TABLE IF EXISTS `team_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_user_team_id_user_id_unique` (`team_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team_user`
--

LOCK TABLES `team_user` WRITE;
/*!40000 ALTER TABLE `team_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `team_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_team` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teams_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teams`
--

LOCK TABLES `teams` WRITE;
/*!40000 ALTER TABLE `teams` DISABLE KEYS */;
/*!40000 ALTER TABLE `teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_details`
--

DROP TABLE IF EXISTS `transaction_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `mst_product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_details_transaction_id_foreign` (`transaction_id`),
  KEY `transaction_details_mst_product_id_foreign` (`mst_product_id`),
  CONSTRAINT `transaction_details_mst_product_id_foreign` FOREIGN KEY (`mst_product_id`) REFERENCES `mst_products` (`id`),
  CONSTRAINT `transaction_details_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_details`
--

LOCK TABLES `transaction_details` WRITE;
/*!40000 ALTER TABLE `transaction_details` DISABLE KEYS */;
INSERT INTO `transaction_details` VALUES (75,1,1,500,89000.00,'2024-12-30 03:20:44','2024-12-30 03:20:44'),(76,1,3,5000,120000.00,'2024-12-30 03:20:44','2024-12-30 03:20:44'),(77,3,1,50,89000.00,'2024-12-30 04:22:09','2024-12-30 04:22:09'),(80,2,1,2,89000.00,'2024-12-30 05:08:55','2024-12-30 05:08:55'),(81,2,3,9000,120000.00,'2024-12-30 05:08:55','2024-12-30 05:08:55'),(84,7,5,50,85000.00,'2024-12-30 05:42:16','2024-12-30 05:42:16'),(85,7,3,50,120000.00,'2024-12-30 05:42:16','2024-12-30 05:42:16'),(86,8,1,20,89000.00,'2025-01-04 02:02:39','2025-01-04 02:02:39'),(87,8,3,20,120000.00,'2025-01-04 02:02:39','2025-01-04 02:02:39'),(95,11,6,100,91500.00,'2025-01-06 04:10:25','2025-01-06 04:10:25'),(104,13,5,70,85000.00,'2025-01-06 16:25:08','2025-01-06 16:25:08'),(107,14,8,900,86000.00,'2025-01-13 12:25:41','2025-01-13 12:25:41'),(108,15,2,1000,90000.00,'2025-01-24 12:17:28','2025-01-24 12:17:28');
/*!40000 ALTER TABLE `transaction_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_code` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mst_client_id` bigint unsigned NOT NULL,
  `grand_total` decimal(15,2) NOT NULL,
  `expedition_fee` decimal(15,2) DEFAULT NULL,
  `transaction_date` timestamp NULL DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_transaction_code_unique` (`transaction_code`),
  KEY `transactions_mst_client_id_foreign` (`mst_client_id`),
  KEY `transactions_created_by_foreign` (`created_by`),
  KEY `transactions_updated_by_foreign` (`updated_by`),
  CONSTRAINT `transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_mst_client_id_foreign` FOREIGN KEY (`mst_client_id`) REFERENCES `mst_client` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,'U243KS','purchase',1,645000000.00,500000.00,'2024-12-26 17:00:00','completed','2024-12-27 16:58:48','2024-12-30 03:20:44',1,1),(2,'9JA4WY','purchase',1,1080178000.00,0.00,'2024-12-26 17:00:00','pending','2024-12-27 17:02:25','2024-12-30 05:08:55',1,1),(3,'Q0U1I5','purchase',1,4450000.00,0.00,'2024-12-27 17:00:00','processing','2024-12-27 17:07:20','2024-12-30 04:22:09',1,1),(7,'5Q39Z1','sell',1,10370000.00,120000.00,'2024-12-16 17:00:00','paid','2024-12-30 05:41:45','2024-12-30 05:42:16',1,1),(8,'6BKF04','sell',1,4330000.00,150000.00,'2025-01-03 17:00:00','pending','2025-01-04 02:02:39','2025-01-04 02:02:39',1,NULL),(11,'7N15QL','purchase',2,9150000.00,0.00,'2024-12-23 17:00:00','pending','2025-01-06 02:18:27','2025-01-06 04:10:25',1,1),(13,'ZGVB75','purchase',1,5950000.00,0.00,'2025-01-06 17:00:00','completed','2025-01-06 11:00:53','2025-01-06 16:25:08',1,1),(14,'9XTRDO','purchase',1,77400000.00,100000.00,'2025-01-09 17:00:00','completed','2025-01-13 12:24:29','2025-01-13 12:25:41',1,1),(15,'6PM02J','purchase',2,90500000.00,500000.00,'2025-01-23 17:00:00','pending','2025-01-24 12:17:27','2025-01-24 12:17:27',1,NULL);
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions_log`
--

DROP TABLE IF EXISTS `transactions_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `action` enum('create','read','update','delete') COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `user_id` bigint unsigned NOT NULL,
  `actor_role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `action_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `transactions_log_transaction_id_foreign` (`transaction_id`),
  KEY `transactions_log_user_id_foreign` (`user_id`),
  CONSTRAINT `transactions_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_log_chk_1` CHECK (json_valid(`changed_fields`))
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions_log`
--

LOCK TABLES `transactions_log` WRITE;
/*!40000 ALTER TABLE `transactions_log` DISABLE KEYS */;
INSERT INTO `transactions_log` VALUES (1,2,'update','{\"transaction_changes\":{\"grand_total\":{\"old\":\"1080089000.00\",\"new\":1080178000},\"transaction_date\":{\"old\":\"2024-12-27 00:00:00\",\"new\":\"2024-12-27\"},\"updated_at\":{\"old\":\"2024-12-28T14:38:24.000000Z\",\"new\":\"2024-12-28T15:10:13.000000Z\"}},\"detail_changes\":[{\"product_id\":1,\"field\":\"quantity\",\"old\":1,\"new\":2}]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-28 15:10:13','2024-12-28 15:10:13','2024-12-28 15:10:13','Transaction updated with changes logged.'),(2,2,'update','{\"transaction_changes\":{\"transaction_date\":{\"old\":\"2024-12-27 00:00:00\",\"new\":\"2024-12-27\"},\"updated_at\":{\"old\":\"2024-12-28T15:10:13.000000Z\",\"new\":\"2024-12-28T15:11:18.000000Z\"}},\"detail_changes\":[]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-28 15:11:18','2024-12-28 15:11:18','2024-12-28 15:11:18','Transaction updated with changes logged.'),(3,6,'update','{\"transaction_changes\":{\"grand_total\":{\"old\":\"5000000.00\",\"new\":550000},\"expedition_fee\":{\"old\":\"0.00\",\"new\":50000},\"transaction_date\":{\"old\":\"2024-12-29 00:00:00\",\"new\":\"2024-12-29\"},\"updated_at\":{\"old\":\"2024-12-29T08:16:48.000000Z\",\"new\":\"2024-12-29T09:03:11.000000Z\"},\"updated_by\":{\"old\":null,\"new\":1}},\"detail_changes\":[{\"product_id\":5,\"field\":\"quantity\",\"old\":50,\"new\":5},{\"product_id\":4,\"field\":\"quantity\",\"old\":10,\"new\":1}]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-29 09:03:11','2024-12-29 09:03:11','2024-12-29 09:03:11','Transaction updated with changes logged.'),(7,4,'delete','{\"transaction_code\":\"RTVDF0\",\"transaction_type\":\"purchase\",\"grand_total\":\"89000.00\",\"expedition_fee\":\"0.00\",\"products\":[{\"mst_product_id\":1,\"quantity\":1,\"cost_price\":\"80000.00\",\"price\":\"89000.00\"}]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-29 15:44:42','2024-12-29 15:44:42','2024-12-29 15:44:42','Transaction and associated details deleted successfully.'),(8,6,'delete','{\"transaction_code\":\"23EXQS\",\"transaction_type\":\"sell\",\"grand_total\":\"550000.00\",\"expedition_fee\":\"50000.00\",\"products\":[{\"mst_product_id\":5,\"quantity\":5,\"cost_price\":\"80000.00\",\"price\":\"85000.00\"},{\"mst_product_id\":4,\"quantity\":1,\"cost_price\":\"55000.00\",\"price\":\"75000.00\"}]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-29 15:56:22','2024-12-29 15:56:22','2024-12-29 15:56:22','Transaction and associated details deleted successfully.'),(9,1,'update','{\"transaction_changes\":{\"transaction_date\":{\"old\":\"2024-12-27 00:00:00\",\"new\":\"2024-12-27\"},\"updated_at\":{\"old\":\"2024-12-28T14:37:58.000000Z\",\"new\":\"2024-12-30T03:20:21.000000Z\"}},\"detail_changes\":[]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-30 03:20:22','2024-12-30 03:20:22','2024-12-30 03:20:22','Transaction updated with changes logged.'),(10,1,'update','{\"transaction_changes\":{\"transaction_date\":{\"old\":\"2024-12-27 00:00:00\",\"new\":\"2024-12-27\"},\"updated_at\":{\"old\":\"2024-12-30T03:20:21.000000Z\",\"new\":\"2024-12-30T03:20:44.000000Z\"}},\"detail_changes\":[]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-30 03:20:44','2024-12-30 03:20:44','2024-12-30 03:20:44','Transaction updated with changes logged.'),(11,3,'update','{\"transaction_changes\":{\"transaction_date\":{\"old\":\"2024-12-28 00:00:00\",\"new\":\"2024-12-28\"},\"status\":{\"old\":\"pending\",\"new\":\"processing\"},\"updated_at\":{\"old\":\"2024-12-28T14:38:43.000000Z\",\"new\":\"2024-12-30T04:22:09.000000Z\"}},\"detail_changes\":[]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-30 04:22:09','2024-12-30 04:22:09','2024-12-30 04:22:09','Transaction updated with changes logged.'),(12,2,'update','{\"transaction_changes\":{\"transaction_date\":{\"old\":\"2024-12-27 00:00:00\",\"new\":\"2024-12-27\"},\"status\":{\"old\":\"pending\",\"new\":\"approved\"},\"updated_at\":{\"old\":\"2024-12-28T15:11:18.000000Z\",\"new\":\"2024-12-30T05:08:00.000000Z\"}},\"detail_changes\":[]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-30 05:08:00','2024-12-30 05:08:00','2024-12-30 05:08:00','Transaction updated with changes logged.'),(13,2,'update','{\"transaction_changes\":{\"transaction_date\":{\"old\":\"2024-12-27 00:00:00\",\"new\":\"2024-12-27\"},\"status\":{\"old\":\"approved\",\"new\":\"pending\"},\"updated_at\":{\"old\":\"2024-12-30T05:08:00.000000Z\",\"new\":\"2024-12-30T05:08:55.000000Z\"}},\"detail_changes\":[]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-30 05:08:55','2024-12-30 05:08:55','2024-12-30 05:08:55','Transaction updated with changes logged.'),(14,7,'update','{\"transaction_changes\":{\"transaction_date\":{\"old\":\"2024-12-17 00:00:00\",\"new\":\"2024-12-17\"},\"status\":{\"old\":\"pending\",\"new\":\"paid\"},\"updated_at\":{\"old\":\"2024-12-30T05:41:45.000000Z\",\"new\":\"2024-12-30T05:42:16.000000Z\"},\"updated_by\":{\"old\":null,\"new\":1}},\"detail_changes\":[]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2024-12-30 05:42:16','2024-12-30 05:42:16','2024-12-30 05:42:16','Transaction updated with changes logged.'),(15,9,'delete','{\"transaction_code\":\"IRWEJM\",\"transaction_type\":\"sell\",\"grand_total\":\"90000.00\",\"expedition_fee\":\"0.00\",\"products\":[{\"mst_product_id\":2,\"quantity\":1,\"cost_price\":\"81500.00\",\"price\":\"90000.00\"}]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-04 03:15:41','2025-01-04 03:15:41','2025-01-04 03:15:41','Transaction and associated details deleted successfully.'),(16,10,'update','{\"transaction_changes\":{\"transaction_date\":{\"old\":\"2024-12-15 00:00:00\",\"new\":\"2024-12-15\"},\"status\":{\"old\":\"pending\",\"new\":\"completed\"},\"updated_at\":{\"old\":\"2025-01-05T16:42:24.000000Z\",\"new\":\"2025-01-05T16:43:36.000000Z\"},\"updated_by\":{\"old\":null,\"new\":1}},\"detail_changes\":[]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-05 16:43:36','2025-01-05 16:43:36','2025-01-05 16:43:36','Transaction updated with changes logged.'),(17,10,'delete','{\"transaction_code\":\"35IS8G\",\"transaction_type\":\"purchase\",\"grand_total\":\"9150000.00\",\"expedition_fee\":\"0.00\",\"products\":[{\"mst_product_id\":6,\"quantity\":100,\"cost_price\":\"81750.00\",\"price\":\"91500.00\"}]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 02:17:31','2025-01-06 02:17:31','2025-01-06 02:17:31','Transaction and associated details deleted successfully.'),(18,11,'update','{\"transaction_changes\":{\"grand_total\":{\"old\":\"9150000.00\",\"new\":4575000},\"transaction_date\":{\"old\":\"2024-12-24 00:00:00\",\"new\":\"2024-12-24\"},\"updated_at\":{\"old\":\"2025-01-06T02:18:27.000000Z\",\"new\":\"2025-01-06T03:08:35.000000Z\"},\"updated_by\":{\"old\":null,\"new\":1}},\"detail_changes\":[{\"product_id\":6,\"field\":\"quantity\",\"old\":100,\"new\":50}]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 03:08:35','2025-01-06 03:08:35','2025-01-06 03:08:35','Transaction updated with changes logged.'),(19,12,'delete','{\"transaction_code\":\"G8NS1M\",\"transaction_type\":\"purchase\",\"grand_total\":\"5950000.00\",\"expedition_fee\":\"0.00\",\"products\":[{\"mst_product_id\":5,\"quantity\":70,\"cost_price\":\"80000.00\",\"price\":\"85000.00\"}]}',1,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','2025-01-06 11:00:19','2025-01-06 11:00:19','2025-01-06 11:00:19','Transaction and associated details deleted successfully.');
/*!40000 ALTER TABLE `transactions_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `current_team_id` bigint unsigned DEFAULT NULL,
  `profile_photo_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_created_by_foreign` (`created_by`),
  KEY `users_updated_by_foreign` (`updated_by`),
  CONSTRAINT `users_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Abdul Kadir Syahab','frenchfriespeople@gmail.com',1,'2024-12-03 14:37:16','$2y$12$sN.pt6UBamL2Sadz1d/5.uR530H5LhQ6RWaYoxowQEMZaWHIoErQW',NULL,NULL,NULL,'JLIe5WuVkjCT2xilIiLKDStV2dRddIjnAjUX1qGq1p66BJiQ6KzegtUyuWy1',NULL,NULL,NULL,'profile-photos/JqfBFtHEa8wdkqHxtL3VkhEJyKfO3hBCUuIbuDwi.jpg','2024-12-03 14:37:16','2025-01-05 15:24:28'),(2,'Austyn Dooley','lulu67@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'m6Z5wpyP2C',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(3,'Delores Thiel','daphney84@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'CKkpqUEvf0',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(4,'Guy Pagac Jr.','zakary85@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'TvPADzopY1',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(5,'Eliza Medhurst','wfadel@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'DKfZeznGPD',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(6,'Asia Homenick','sim.dubuque@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'Rmqo5JZEoh',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(7,'Alanna Senger','monahan.royce@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'f21j1QGue7',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(8,'Vesta Haley III','fgerhold@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'nmwCFdJ3TW',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(9,'Ms. Agustina Paucek','funk.lucy@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'nnTwzLfc5l',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(10,'Emory McClure','kgibson@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'FGxfuIeGXi',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(11,'Walton Glover','cormier.wallace@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'msW9GlEqgB',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(12,'Kiana Bergnaum','reva.ebert@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'mQR7XOB8Xg',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(13,'Javier Cronin','rowena.medhurst@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'yVIuszYs7N',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(14,'Justyn Wiza','brooklyn82@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'683IcEcieQ',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(15,'Dr. Zetta Mills DVM','bernhard.minnie@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'cNQkxufGaU',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(16,'Jamey Jacobson','jacobson.alanna@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'NZ8IOg9rtC',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(17,'Enrique Blanda','evalyn35@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'pJjocuDngb',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(18,'Harley Schultz','kmccullough@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'qYRkjvr5Kf',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(19,'Mathilde Heathcote','hans05@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'PBB1VpxMH2',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(20,'Dr. Kris Kautzer','rfeeney@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'cG8sLJnDtM',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(21,'Dr. Rosalyn Kerluke III','reilly.lelah@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'983OIRrgWw',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(22,'Lamont Hagenes','hills.mattie@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'UrBEqwvDhY',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(23,'Shana Ankunding Jr.','arely37@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'mNEKteuXie',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(24,'Kailey Nolan','grover.lemke@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'pLTX1E8tlZ',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(25,'Olin Rutherford','foconner@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'fGcaSRdqjr',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(26,'Ms. Kali Brekke','little.maggie@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'IziTuPrcO6',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(27,'Mr. Selmer Borer DDS','gjones@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'8oKmEWqxnh',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(28,'Hillary Rowe','orn.claudia@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'u9XbnukVR0',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(29,'Valentin Lubowitz','zola.paucek@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'yQx1eztgeY',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(30,'Mr. Dillan Shields IV','welch.duane@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'HWbml2Bd02',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(31,'Jude Corwin','cristopher19@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'Q6BNA3V395',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(32,'Mr. Jace Marks','casper.johnson@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'9bI062BbuH',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(33,'Jazmin Keeling II','rachelle17@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'2s3iJ9KRJM',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(34,'Prof. Retta Bogisich PhD','carli.medhurst@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'EwE8sH0qj3',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(35,'Isabel Wilderman','kaitlin70@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'L81q6NS65x',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(36,'Dr. Edgar Heathcote DDS','jfritsch@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'B0SosBUdCi',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(37,'Alverta Wolff','annabelle.schimmel@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'UX8J35rorQ',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(38,'Luna Lindgren','saul.langosh@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'fFngVn1S6X',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(39,'Hanna Blanda','olin.kunde@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'YAVvw1YJK0',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(40,'Jazmyn Stracke','mathew35@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'psRHKja8xa',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(41,'Jaylen Schuppe','garfield.schumm@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'imhf3wol5Q',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(42,'Daryl Kassulke','maxine01@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'A2xh4nrdu9',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(43,'Prof. Elbert Kiehn','trystan18@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'JuS5nhMvcE',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(44,'Mr. Rahul Thompson','devan.witting@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'mCy70ieXfw',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(45,'Lucio Williamson','camille.barrows@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'YptKa0V5Jj',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(46,'Troy Hermann','ykozey@example.com',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'UzsuCUMb39',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(47,'Otha Hagenes','pascale92@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'fxt7Qsg8D2',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(48,'Ally Hill','jabari86@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'KfWey0Ld4E',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(49,'Mrs. Lilyan Johnston V','desiree.connelly@example.org',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'QBYxRdcPA6',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(50,'Hosea Huel','oconnell.darian@example.net',1,'2024-12-03 14:37:16','$2y$12$LMkQWmBfLYjAG1HMdoiRsuTQ0VL1XzzFop369nbSlHouYQSozmbTO',NULL,NULL,NULL,'udoL9Ien3B',NULL,NULL,NULL,NULL,'2024-12-03 14:37:16','2024-12-03 14:37:16'),(51,'{name}','{email}',1,NULL,'$2y$12$VLEoNq/jbzvx9cQ55sgRE.Mx2lO.qCT.DuDJ.3bOi4ADV9NdO38om',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-01-04 02:18:34','2025-01-04 02:18:34'),(52,'Coba palsu','email@email.com',1,NULL,'$2y$12$XB68XUBS8VSPcpzuMPCRiueImKQlK5KiI2YVsGr/N2LT3jmEdvnNK',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-01-04 02:19:20','2025-01-04 02:19:20'),(54,'Coba palsu 2','email@email.con',1,NULL,'$2y$12$oBFTCXuFNXBukh7cDcXjaeyxkqMO3six/Twa/HW2pKYj9sR4T4ZE6',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-01-04 02:22:47','2025-01-04 02:22:47'),(56,'Coba palsu 2','email@email.cob',1,NULL,'$2y$12$ahVFqAxA.2pmgqHMHZEAbeVrTXigsyoX2fZXSL6jwtVQlIBUyZ5U2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-01-04 02:25:44','2025-01-04 02:25:44'),(58,'zzzzzzz','cucuk',1,NULL,'$2y$12$ixFKXGWepjIaoKPeas5KoONdpQQCxhHfkLLWFrtxyfjTecIXbux26',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-01-04 02:28:10','2025-01-04 02:44:46'),(59,'Coba palsu 2','3',1,NULL,'$2y$12$INbq4XS56YoLtbevhvGpn.U/frOAhm3LU4bYE3FTC6Kltr4QulJqW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-01-04 02:31:03','2026-01-26 17:07:38'),(61,'Coba palsu 2','mail@email.test',1,NULL,'$2y$12$h15nmMXuDnvNHIY6.24xOOHChLNWr/o7jQyNyRBxHpH2Ev6Q4o58W',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'2025-01-04 02:40:56','2026-01-26 16:21:50'),(62,'Yownga','yownga@email.com',1,NULL,'$2y$12$A0vHDW3FeRWrzLm2Ne1c/eqkCJ2uktRPbvVcEcpCtm7yNjnaJmZMa',NULL,NULL,NULL,NULL,1,1,NULL,NULL,'2026-01-26 16:25:16','2026-01-26 16:29:30');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-28 12:04:43
