mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 8.0.40, for macos11.7 (x86_64)
--
-- Host: 127.0.0.1    Database: dimsum_pos
-- ------------------------------------------------------
-- Server version	8.0.40

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
-- Table structure for table `carousel_slides`
--

DROP TABLE IF EXISTS `carousel_slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carousel_slides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `badge_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_label_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_value_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carousel_slides`
--

LOCK TABLES `carousel_slides` WRITE;
/*!40000 ALTER TABLE `carousel_slides` DISABLE KEYS */;
INSERT INTO `carousel_slides` VALUES (2,'Menu Baru','Coba sekarang!','Tersedia mulai Februari 2026','carousel/sukqRkVH9u76h9QJJOxt6rBAR3tcjxHYqSeRkgPz.jpg','New','MENU TERBARU',NULL,NULL,NULL,NULL,NULL,2,1,'2026-02-06 04:14:23','2026-02-07 03:47:24'),(3,'Spesial Imlek','Gong Xi Fa Cai!','Promo Imlek: 10-25 Feb 2026','carousel/TY2dxxrOCdgMNLWwj3EaY4QhuZlICSc9lxpS4Zhh.jpg','Lucky Draw','HADIAH EMAS','Min. Order 100rb','GRATIS ANGPAO',NULL,'2026-02-04','2026-02-22',3,1,'2026-02-06 04:14:23','2026-02-06 04:23:29');
/*!40000 ALTER TABLE `carousel_slides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('percentage','fixed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `value` int NOT NULL,
  `min_purchase` int NOT NULL DEFAULT '0',
  `max_discount` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_addresses`
--

DROP TABLE IF EXISTS `customer_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Rumah',
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_addresses_customer_id_foreign` (`customer_id`),
  CONSTRAINT `customer_addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_addresses`
--

LOCK TABLES `customer_addresses` WRITE;
/*!40000 ALTER TABLE `customer_addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_coupons`
--

DROP TABLE IF EXISTS `customer_coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `coupon_id` bigint unsigned NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_coupons_customer_id_foreign` (`customer_id`),
  KEY `customer_coupons_coupon_id_foreign` (`coupon_id`),
  CONSTRAINT `customer_coupons_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_coupons_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_coupons`
--

LOCK TABLES `customer_coupons` WRITE;
/*!40000 ALTER TABLE `customer_coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_types`
--

DROP TABLE IF EXISTS `customer_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_types`
--

LOCK TABLES `customer_types` WRITE;
/*!40000 ALTER TABLE `customer_types` DISABLE KEYS */;
INSERT INTO `customer_types` VALUES (1,'Reguler','Regular',NULL,'2026-01-06 15:16:06'),(2,'Reseller','Reseller',NULL,'2026-01-06 14:37:37'),(3,'Keluarga','Keluarga',NULL,'2026-01-06 15:14:33');
/*!40000 ALTER TABLE `customer_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expires_at` timestamp NULL DEFAULT NULL,
  `type` enum('regular','vip') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `customer_type_id` bigint unsigned NOT NULL,
  `discount_percentage` int NOT NULL DEFAULT '0',
  `total_orders` int NOT NULL DEFAULT '0',
  `total_spent` int NOT NULL DEFAULT '0',
  `points` int NOT NULL DEFAULT '0',
  `last_order_date` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_phone_unique` (`phone`),
  KEY `customers_customer_type_id_foreign` (`customer_type_id`),
  CONSTRAINT `customers_customer_type_id_foreign` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (3,'Ahmad Wijaya','08345678901',NULL,NULL,'Jl. Gatot Subroto No. 789, Jakarta Barat',NULL,NULL,NULL,'regular',1,0,3,84000,0,'2026-01-06','Suka mix varian, biasanya pre-order',1,'2025-12-30 17:50:45','2026-01-06 15:25:40'),(4,'Amel','08456789012',NULL,'dewi.lestari@email.com','Jl. Thamrin No. 321, Jakarta Pusat',NULL,NULL,NULL,'vip',2,0,4,196000,0,'2026-01-07','Pelanggan korporat, sering order untuk acara kantor',1,'2025-12-30 17:50:45','2026-01-19 01:30:39'),(6,'Keenan','08999888777',NULL,'keenan@keetech.my.id',NULL,'$2y$12$KrSeT2rW6btkrmJxtZ36ju23S4MTuMeEQQO..QBFaLom7iKkJhHYW',NULL,NULL,'vip',3,0,1,206000,0,'2026-01-05',NULL,1,'2025-12-30 18:03:04','2026-01-28 07:16:27'),(7,'dewi','09921313',NULL,NULL,NULL,NULL,NULL,NULL,'regular',1,0,0,0,0,NULL,NULL,1,'2026-01-04 16:13:37','2026-01-04 16:13:37'),(8,'yap','09887665443',NULL,'kotak.melbu@gmail.com',NULL,NULL,'M6t8qEPQwBqwN0HEKHum30JwhatBUAYPDSnwZeWVpv8XVyFXNYhcXAaEYXgq87vW','2026-02-10 14:36:24','regular',2,0,7,218000,0,'2026-01-07',NULL,1,'2026-01-06 11:34:25','2026-02-10 13:36:24'),(9,'Winata','08112678444',NULL,NULL,NULL,NULL,NULL,NULL,'regular',2,0,1,177000,0,'2026-01-14',NULL,1,'2026-01-14 14:48:52','2026-01-15 04:51:20'),(10,'Yuli','086285747713732',NULL,'yuli@gmail.com',NULL,'$2y$12$OVNhBhblNTVVlPY/Oqg0POP7b9oKgzBlqOwIn8BN7ybcCGdPl3dlK',NULL,NULL,'regular',1,0,2,65000,2,'2026-02-07',NULL,1,'2026-01-15 12:49:40','2026-02-07 04:50:35'),(11,'Kevin','089676919989',NULL,NULL,NULL,NULL,NULL,NULL,'regular',1,0,3,187000,0,'2026-01-19',NULL,1,'2026-01-16 07:49:18','2026-01-19 01:56:49'),(12,'Yoga','085799410169',NULL,NULL,NULL,NULL,NULL,NULL,'regular',3,0,2,84000,1,'2026-02-09',NULL,1,'2026-01-24 03:08:16','2026-02-09 13:29:46');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('raw_material','packaging') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock` decimal(12,2) NOT NULL DEFAULT '0.00',
  `min_stock` decimal(12,2) NOT NULL DEFAULT '0.00',
  `price_per_unit` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_items_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_items`
--

LOCK TABLES `inventory_items` WRITE;
/*!40000 ALTER TABLE `inventory_items` DISABLE KEYS */;
INSERT INTO `inventory_items` VALUES (1,'Daging Ayam','BB-AYAM','raw_material','gram',1425.00,2000.00,0,1,'2025-12-30 15:09:31','2026-01-24 03:12:07'),(2,'Udang','BB-UDANG','raw_material','gram',5000.00,1000.00,0,1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(3,'Kulit Dimsum','BB-KULIT','raw_material','pcs',157.00,100.00,0,1,'2025-12-30 15:09:31','2026-01-24 03:12:07'),(4,'Keju','BB-KEJU','raw_material','gram',2000.00,500.00,0,1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(5,'Nori Sheet','BB-NORI','raw_material','lembar',200.00,50.00,0,1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(6,'Tinwall Besar','PKG-TW-B','packaging','pcs',148.00,50.00,0,1,'2025-12-30 15:09:31','2026-01-24 16:34:16'),(7,'Tinwall Kecil','PKG-TW-K','packaging','pcs',95.00,50.00,0,1,'2025-12-30 15:09:31','2026-01-24 16:34:16'),(8,'Plastik Besar','PKG-PL-B','packaging','pcs',300.00,100.00,0,1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(9,'Plastik Kecil','PKG-PL-K','packaging','pcs',300.00,100.00,0,1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(10,'Sumpit','PKG-SUMPIT','packaging','pcs',432.00,100.00,0,1,'2025-12-30 15:09:31','2026-01-24 16:34:16'),(11,'Sendok','PKG-SENDOK','packaging','pcs',500.00,100.00,0,1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(12,'Tisu','PKG-TISU','packaging','pcs',932.00,200.00,0,1,'2025-12-30 15:09:31','2026-01-24 16:34:16');
/*!40000 ALTER TABLE `inventory_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `type` enum('in','out','adjustment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `stock_before` decimal(12,2) NOT NULL,
  `stock_after` decimal(12,2) NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `inventory_transactions_user_id_foreign` (`user_id`),
  KEY `inventory_transactions_transaction_id_foreign` (`transaction_id`),
  CONSTRAINT `inventory_transactions_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=215 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transactions`
--

LOCK TABLES `inventory_transactions` WRITE;
/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
INSERT INTO `inventory_transactions` VALUES (1,10,1,NULL,'out',2.00,500.00,498.00,'Packaging: Sumpit per order','2025-12-30 19:29:24','2025-12-30 19:29:24'),(2,12,1,NULL,'out',2.00,1000.00,998.00,'Packaging: Tisu per order','2025-12-30 19:29:24','2025-12-30 19:29:24'),(3,1,1,NULL,'out',250.00,10000.00,9750.00,'Pemakaian untuk Ayam Original x 10','2026-01-04 12:10:54','2026-01-04 12:10:54'),(4,3,1,NULL,'out',10.00,500.00,490.00,'Pemakaian untuk Ayam Original x 10','2026-01-04 12:10:54','2026-01-04 12:10:54'),(5,6,1,NULL,'out',1.00,200.00,199.00,'Packaging: Tinwall besar per 10 pcs','2026-01-04 12:10:54','2026-01-04 12:10:54'),(6,7,1,NULL,'out',2.00,200.00,198.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-04 12:10:54','2026-01-04 12:10:54'),(7,10,1,NULL,'out',2.00,498.00,496.00,'Packaging: Sumpit per order','2026-01-04 12:10:54','2026-01-04 12:10:54'),(8,12,1,NULL,'out',2.00,998.00,996.00,'Packaging: Tisu per order','2026-01-04 12:10:54','2026-01-04 12:10:54'),(9,1,1,NULL,'out',250.00,9750.00,9500.00,'Pemakaian untuk Ayam Original x 10','2026-01-04 12:43:19','2026-01-04 12:43:19'),(10,3,1,NULL,'out',10.00,490.00,480.00,'Pemakaian untuk Ayam Original x 10','2026-01-04 12:43:19','2026-01-04 12:43:19'),(11,6,1,NULL,'out',1.00,199.00,198.00,'Packaging: Tinwall besar per 10 pcs','2026-01-04 12:43:19','2026-01-04 12:43:19'),(12,7,1,NULL,'out',2.00,198.00,196.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-04 12:43:19','2026-01-04 12:43:19'),(13,10,1,NULL,'out',2.00,496.00,494.00,'Packaging: Sumpit per order','2026-01-04 12:43:19','2026-01-04 12:43:19'),(14,12,1,NULL,'out',2.00,996.00,994.00,'Packaging: Tisu per order','2026-01-04 12:43:19','2026-01-04 12:43:19'),(15,1,1,NULL,'out',250.00,9500.00,9250.00,'Pemakaian untuk Ayam Original x 10','2026-01-04 12:47:40','2026-01-04 12:47:40'),(16,3,1,NULL,'out',10.00,480.00,470.00,'Pemakaian untuk Ayam Original x 10','2026-01-04 12:47:40','2026-01-04 12:47:40'),(17,6,1,NULL,'out',1.00,198.00,197.00,'Packaging: Tinwall besar per 10 pcs','2026-01-04 12:47:40','2026-01-04 12:47:40'),(18,7,1,NULL,'out',2.00,196.00,194.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-04 12:47:40','2026-01-04 12:47:40'),(19,10,1,NULL,'out',2.00,494.00,492.00,'Packaging: Sumpit per order','2026-01-04 12:47:40','2026-01-04 12:47:40'),(20,12,1,NULL,'out',2.00,994.00,992.00,'Packaging: Tisu per order','2026-01-04 12:47:40','2026-01-04 12:47:40'),(21,6,1,NULL,'out',1.00,197.00,196.00,'Packaging: Tinwall besar per 10 pcs','2026-01-04 16:05:28','2026-01-04 16:05:28'),(22,7,1,NULL,'out',2.00,194.00,192.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-04 16:05:28','2026-01-04 16:05:28'),(23,10,1,NULL,'out',2.00,492.00,490.00,'Packaging: Sumpit per order','2026-01-04 16:05:28','2026-01-04 16:05:28'),(24,12,1,NULL,'out',2.00,992.00,990.00,'Packaging: Tisu per order','2026-01-04 16:05:28','2026-01-04 16:05:28'),(25,1,1,NULL,'out',250.00,9250.00,9000.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:02:39','2026-01-06 11:02:39'),(26,3,1,NULL,'out',10.00,470.00,460.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:02:39','2026-01-06 11:02:39'),(27,1,1,NULL,'out',250.00,9000.00,8750.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:02:39','2026-01-06 11:02:39'),(28,3,1,NULL,'out',10.00,460.00,450.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:02:39','2026-01-06 11:02:39'),(29,1,1,NULL,'out',250.00,8750.00,8500.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:02:39','2026-01-06 11:02:39'),(30,3,1,NULL,'out',10.00,450.00,440.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:02:39','2026-01-06 11:02:39'),(31,1,1,NULL,'out',250.00,8500.00,8250.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:02:39','2026-01-06 11:02:39'),(32,3,1,NULL,'out',10.00,440.00,430.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:02:39','2026-01-06 11:02:39'),(33,6,1,NULL,'out',9.00,196.00,187.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 11:02:39','2026-01-06 11:02:39'),(34,7,1,NULL,'out',18.00,192.00,174.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 11:02:39','2026-01-06 11:02:39'),(35,10,1,NULL,'out',2.00,490.00,488.00,'Packaging: Sumpit per order','2026-01-06 11:02:39','2026-01-06 11:02:39'),(36,12,1,NULL,'out',2.00,990.00,988.00,'Packaging: Tisu per order','2026-01-06 11:02:39','2026-01-06 11:02:39'),(37,1,1,NULL,'out',250.00,8250.00,8000.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:39:32','2026-01-06 11:39:32'),(38,3,1,NULL,'out',10.00,430.00,420.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:39:32','2026-01-06 11:39:32'),(39,6,1,NULL,'out',1.00,187.00,186.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 11:39:32','2026-01-06 11:39:32'),(40,7,1,NULL,'out',2.00,174.00,172.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 11:39:32','2026-01-06 11:39:32'),(41,10,1,NULL,'out',2.00,488.00,486.00,'Packaging: Sumpit per order','2026-01-06 11:39:32','2026-01-06 11:39:32'),(42,12,1,NULL,'out',2.00,988.00,986.00,'Packaging: Tisu per order','2026-01-06 11:39:32','2026-01-06 11:39:32'),(43,1,1,NULL,'out',250.00,8000.00,7750.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:41:31','2026-01-06 11:41:31'),(44,3,1,NULL,'out',10.00,420.00,410.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:41:31','2026-01-06 11:41:31'),(45,6,1,NULL,'out',1.00,186.00,185.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 11:41:31','2026-01-06 11:41:31'),(46,7,1,NULL,'out',2.00,172.00,170.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 11:41:31','2026-01-06 11:41:31'),(47,10,1,NULL,'out',2.00,486.00,484.00,'Packaging: Sumpit per order','2026-01-06 11:41:31','2026-01-06 11:41:31'),(48,12,1,NULL,'out',2.00,986.00,984.00,'Packaging: Tisu per order','2026-01-06 11:41:31','2026-01-06 11:41:31'),(49,1,1,NULL,'out',250.00,7750.00,7500.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:45:33','2026-01-06 11:45:33'),(50,3,1,NULL,'out',10.00,410.00,400.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:45:33','2026-01-06 11:45:33'),(51,6,1,NULL,'out',1.00,185.00,184.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 11:45:33','2026-01-06 11:45:33'),(52,7,1,NULL,'out',2.00,170.00,168.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 11:45:33','2026-01-06 11:45:33'),(53,10,1,NULL,'out',2.00,484.00,482.00,'Packaging: Sumpit per order','2026-01-06 11:45:33','2026-01-06 11:45:33'),(54,12,1,NULL,'out',2.00,984.00,982.00,'Packaging: Tisu per order','2026-01-06 11:45:33','2026-01-06 11:45:33'),(55,1,1,NULL,'out',250.00,7500.00,7250.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:49:48','2026-01-06 11:49:48'),(56,3,1,NULL,'out',10.00,400.00,390.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 11:49:48','2026-01-06 11:49:48'),(57,6,1,NULL,'out',1.00,184.00,183.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 11:49:48','2026-01-06 11:49:48'),(58,7,1,NULL,'out',2.00,168.00,166.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 11:49:48','2026-01-06 11:49:48'),(59,10,1,NULL,'out',2.00,482.00,480.00,'Packaging: Sumpit per order','2026-01-06 11:49:48','2026-01-06 11:49:48'),(60,12,1,NULL,'out',2.00,982.00,980.00,'Packaging: Tisu per order','2026-01-06 11:49:48','2026-01-06 11:49:48'),(61,1,1,NULL,'out',125.00,7250.00,7125.00,'Pemakaian untuk Ayam Original x 5','2026-01-06 12:02:36','2026-01-06 12:02:36'),(62,3,1,NULL,'out',5.00,390.00,385.00,'Pemakaian untuk Ayam Original x 5','2026-01-06 12:02:36','2026-01-06 12:02:36'),(63,6,1,NULL,'out',1.00,183.00,182.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 12:02:36','2026-01-06 12:02:36'),(64,7,1,NULL,'out',2.00,166.00,164.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 12:02:36','2026-01-06 12:02:36'),(65,10,1,NULL,'out',2.00,480.00,478.00,'Packaging: Sumpit per order','2026-01-06 12:02:36','2026-01-06 12:02:36'),(66,12,1,NULL,'out',2.00,980.00,978.00,'Packaging: Tisu per order','2026-01-06 12:02:36','2026-01-06 12:02:36'),(67,1,1,NULL,'out',250.00,7125.00,6875.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 12:06:10','2026-01-06 12:06:10'),(68,3,1,NULL,'out',10.00,385.00,375.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 12:06:10','2026-01-06 12:06:10'),(69,6,1,NULL,'out',1.00,182.00,181.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 12:06:10','2026-01-06 12:06:10'),(70,7,1,NULL,'out',2.00,164.00,162.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 12:06:10','2026-01-06 12:06:10'),(71,10,1,NULL,'out',2.00,478.00,476.00,'Packaging: Sumpit per order','2026-01-06 12:06:10','2026-01-06 12:06:10'),(72,12,1,NULL,'out',2.00,978.00,976.00,'Packaging: Tisu per order','2026-01-06 12:06:10','2026-01-06 12:06:10'),(73,1,1,NULL,'out',125.00,6875.00,6750.00,'Pemakaian untuk Ayam Original x 5','2026-01-06 12:13:56','2026-01-06 12:13:56'),(74,3,1,NULL,'out',5.00,375.00,370.00,'Pemakaian untuk Ayam Original x 5','2026-01-06 12:13:56','2026-01-06 12:13:56'),(75,6,1,NULL,'out',1.00,181.00,180.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 12:13:56','2026-01-06 12:13:56'),(76,7,1,NULL,'out',2.00,162.00,160.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 12:13:56','2026-01-06 12:13:56'),(77,10,1,NULL,'out',2.00,476.00,474.00,'Packaging: Sumpit per order','2026-01-06 12:13:56','2026-01-06 12:13:56'),(78,12,1,NULL,'out',2.00,976.00,974.00,'Packaging: Tisu per order','2026-01-06 12:13:56','2026-01-06 12:13:56'),(79,1,1,NULL,'out',125.00,6750.00,6625.00,'Pemakaian untuk Ayam Original x 5','2026-01-06 12:17:36','2026-01-06 12:17:36'),(80,3,1,NULL,'out',5.00,370.00,365.00,'Pemakaian untuk Ayam Original x 5','2026-01-06 12:17:36','2026-01-06 12:17:36'),(81,6,1,NULL,'out',1.00,180.00,179.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 12:17:36','2026-01-06 12:17:36'),(82,7,1,NULL,'out',2.00,160.00,158.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 12:17:36','2026-01-06 12:17:36'),(83,10,1,NULL,'out',2.00,474.00,472.00,'Packaging: Sumpit per order','2026-01-06 12:17:36','2026-01-06 12:17:36'),(84,12,1,NULL,'out',2.00,974.00,972.00,'Packaging: Tisu per order','2026-01-06 12:17:36','2026-01-06 12:17:36'),(85,1,1,NULL,'out',250.00,6625.00,6375.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 15:20:04','2026-01-06 15:20:04'),(86,3,1,NULL,'out',10.00,365.00,355.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 15:20:04','2026-01-06 15:20:04'),(87,6,1,NULL,'out',1.00,179.00,178.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 15:20:04','2026-01-06 15:20:04'),(88,7,1,NULL,'out',2.00,158.00,156.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 15:20:04','2026-01-06 15:20:04'),(89,10,1,NULL,'out',2.00,472.00,470.00,'Packaging: Sumpit per order','2026-01-06 15:20:04','2026-01-06 15:20:04'),(90,12,1,NULL,'out',2.00,972.00,970.00,'Packaging: Tisu per order','2026-01-06 15:20:04','2026-01-06 15:20:04'),(91,1,1,NULL,'out',250.00,6375.00,6125.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 15:25:22','2026-01-06 15:25:22'),(92,3,1,NULL,'out',10.00,355.00,345.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 15:25:22','2026-01-06 15:25:22'),(93,6,1,NULL,'out',1.00,178.00,177.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 15:25:22','2026-01-06 15:25:22'),(94,7,1,NULL,'out',2.00,156.00,154.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 15:25:22','2026-01-06 15:25:22'),(95,10,1,NULL,'out',2.00,470.00,468.00,'Packaging: Sumpit per order','2026-01-06 15:25:22','2026-01-06 15:25:22'),(96,12,1,NULL,'out',2.00,970.00,968.00,'Packaging: Tisu per order','2026-01-06 15:25:22','2026-01-06 15:25:22'),(97,1,1,NULL,'out',250.00,6125.00,5875.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 15:25:40','2026-01-06 15:25:40'),(98,3,1,NULL,'out',10.00,345.00,335.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 15:25:40','2026-01-06 15:25:40'),(99,6,1,NULL,'out',1.00,177.00,176.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 15:25:40','2026-01-06 15:25:40'),(100,7,1,NULL,'out',2.00,154.00,152.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 15:25:40','2026-01-06 15:25:40'),(101,10,1,NULL,'out',2.00,468.00,466.00,'Packaging: Sumpit per order','2026-01-06 15:25:40','2026-01-06 15:25:40'),(102,12,1,NULL,'out',2.00,968.00,966.00,'Packaging: Tisu per order','2026-01-06 15:25:40','2026-01-06 15:25:40'),(103,1,1,NULL,'out',250.00,5875.00,5625.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 15:26:11','2026-01-06 15:26:11'),(104,3,1,NULL,'out',10.00,335.00,325.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 15:26:11','2026-01-06 15:26:11'),(105,6,1,NULL,'out',1.00,176.00,175.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 15:26:11','2026-01-06 15:26:11'),(106,7,1,NULL,'out',2.00,152.00,150.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 15:26:11','2026-01-06 15:26:11'),(107,10,1,NULL,'out',2.00,466.00,464.00,'Packaging: Sumpit per order','2026-01-06 15:26:11','2026-01-06 15:26:11'),(108,12,1,NULL,'out',2.00,966.00,964.00,'Packaging: Tisu per order','2026-01-06 15:26:11','2026-01-06 15:26:11'),(109,1,1,NULL,'out',125.00,5625.00,5500.00,'Pemakaian untuk Ayam Original x 5','2026-01-06 15:34:13','2026-01-06 15:34:13'),(110,3,1,NULL,'out',5.00,325.00,320.00,'Pemakaian untuk Ayam Original x 5','2026-01-06 15:34:13','2026-01-06 15:34:13'),(111,6,1,NULL,'out',1.00,175.00,174.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 15:34:13','2026-01-06 15:34:13'),(112,7,1,NULL,'out',2.00,150.00,148.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 15:34:13','2026-01-06 15:34:13'),(113,10,1,NULL,'out',2.00,464.00,462.00,'Packaging: Sumpit per order','2026-01-06 15:34:13','2026-01-06 15:34:13'),(114,12,1,NULL,'out',2.00,964.00,962.00,'Packaging: Tisu per order','2026-01-06 15:34:13','2026-01-06 15:34:13'),(115,1,1,NULL,'out',250.00,5500.00,5250.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 15:35:35','2026-01-06 15:35:35'),(116,3,1,NULL,'out',10.00,320.00,310.00,'Pemakaian untuk Ayam Original x 10','2026-01-06 15:35:35','2026-01-06 15:35:35'),(117,6,1,NULL,'out',1.00,174.00,173.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 15:35:35','2026-01-06 15:35:35'),(118,7,1,NULL,'out',2.00,148.00,146.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 15:35:35','2026-01-06 15:35:35'),(119,10,1,NULL,'out',2.00,462.00,460.00,'Packaging: Sumpit per order','2026-01-06 15:35:35','2026-01-06 15:35:35'),(120,12,1,NULL,'out',2.00,962.00,960.00,'Packaging: Tisu per order','2026-01-06 15:35:35','2026-01-06 15:35:35'),(121,1,1,NULL,'out',125.00,5250.00,5125.00,'Pemakaian untuk Ayam Original x 5','2026-01-06 15:36:24','2026-01-06 15:36:24'),(122,3,1,NULL,'out',5.00,310.00,305.00,'Pemakaian untuk Ayam Original x 5','2026-01-06 15:36:24','2026-01-06 15:36:24'),(123,6,1,NULL,'out',1.00,173.00,172.00,'Packaging: Tinwall besar per 10 pcs','2026-01-06 15:36:24','2026-01-06 15:36:24'),(124,7,1,NULL,'out',2.00,146.00,144.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-06 15:36:24','2026-01-06 15:36:24'),(125,10,1,NULL,'out',2.00,460.00,458.00,'Packaging: Sumpit per order','2026-01-06 15:36:24','2026-01-06 15:36:24'),(126,12,1,NULL,'out',2.00,960.00,958.00,'Packaging: Tisu per order','2026-01-06 15:36:24','2026-01-06 15:36:24'),(127,1,1,NULL,'out',250.00,5125.00,4875.00,'Pemakaian untuk Ayam Original x 10','2026-01-07 07:24:42','2026-01-07 07:24:42'),(128,3,1,NULL,'out',10.00,305.00,295.00,'Pemakaian untuk Ayam Original x 10','2026-01-07 07:24:42','2026-01-07 07:24:42'),(129,1,1,NULL,'out',250.00,4875.00,4625.00,'Pemakaian untuk Ayam Original x 10','2026-01-07 07:24:42','2026-01-07 07:24:42'),(130,3,1,NULL,'out',10.00,295.00,285.00,'Pemakaian untuk Ayam Original x 10','2026-01-07 07:24:42','2026-01-07 07:24:42'),(131,6,1,NULL,'out',2.00,172.00,170.00,'Packaging: Tinwall besar per 10 pcs','2026-01-07 07:24:42','2026-01-07 07:24:42'),(132,7,1,NULL,'out',4.00,144.00,140.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-07 07:24:42','2026-01-07 07:24:42'),(133,10,1,NULL,'out',2.00,458.00,456.00,'Packaging: Sumpit per order','2026-01-07 07:24:42','2026-01-07 07:24:42'),(134,12,1,NULL,'out',2.00,958.00,956.00,'Packaging: Tisu per order','2026-01-07 07:24:42','2026-01-07 07:24:42'),(135,1,1,NULL,'out',250.00,4625.00,4375.00,'Pemakaian untuk Ayam Original x 10','2026-01-07 07:55:57','2026-01-07 07:55:57'),(136,3,1,NULL,'out',10.00,285.00,275.00,'Pemakaian untuk Ayam Original x 10','2026-01-07 07:55:57','2026-01-07 07:55:57'),(137,6,1,NULL,'out',1.00,170.00,169.00,'Packaging: Tinwall besar per 10 pcs','2026-01-07 07:55:57','2026-01-07 07:55:57'),(138,7,1,NULL,'out',2.00,140.00,138.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-07 07:55:57','2026-01-07 07:55:57'),(139,10,1,NULL,'out',2.00,456.00,454.00,'Packaging: Sumpit per order','2026-01-07 07:55:57','2026-01-07 07:55:57'),(140,12,1,NULL,'out',2.00,956.00,954.00,'Packaging: Tisu per order','2026-01-07 07:55:57','2026-01-07 07:55:57'),(141,1,1,NULL,'out',250.00,4375.00,4125.00,'Pemakaian untuk Ayam Original x 10','2026-01-07 08:09:15','2026-01-07 08:09:15'),(142,3,1,NULL,'out',10.00,275.00,265.00,'Pemakaian untuk Ayam Original x 10','2026-01-07 08:09:15','2026-01-07 08:09:15'),(143,6,1,NULL,'out',1.00,169.00,168.00,'Packaging: Tinwall besar per 10 pcs','2026-01-07 08:09:15','2026-01-07 08:09:15'),(144,7,1,NULL,'out',2.00,138.00,136.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-07 08:09:15','2026-01-07 08:09:15'),(145,10,1,NULL,'out',2.00,454.00,452.00,'Packaging: Sumpit per order','2026-01-07 08:09:15','2026-01-07 08:09:15'),(146,12,1,NULL,'out',2.00,954.00,952.00,'Packaging: Tisu per order','2026-01-07 08:09:15','2026-01-07 08:09:15'),(147,1,1,NULL,'out',250.00,4125.00,3875.00,'Pemakaian untuk Ayam Original x 10','2026-01-08 07:24:11','2026-01-08 07:24:11'),(148,3,1,NULL,'out',10.00,265.00,255.00,'Pemakaian untuk Ayam Original x 10','2026-01-08 07:24:11','2026-01-08 07:24:11'),(149,6,1,NULL,'out',1.00,168.00,167.00,'Packaging: Tinwall besar per 10 pcs','2026-01-08 07:24:11','2026-01-08 07:24:11'),(150,7,1,NULL,'out',2.00,136.00,134.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-08 07:24:11','2026-01-08 07:24:11'),(151,10,1,NULL,'out',2.00,452.00,450.00,'Packaging: Sumpit per order','2026-01-08 07:24:11','2026-01-08 07:24:11'),(152,12,1,NULL,'out',2.00,952.00,950.00,'Packaging: Tisu per order','2026-01-08 07:24:11','2026-01-08 07:24:11'),(153,1,1,61,'out',250.00,3875.00,3625.00,'Pemakaian untuk Ayam Original x 10','2026-01-16 07:54:12','2026-01-16 07:54:12'),(154,3,1,61,'out',10.00,255.00,245.00,'Pemakaian untuk Ayam Original x 10','2026-01-16 07:54:12','2026-01-16 07:54:12'),(155,6,1,61,'out',1.00,167.00,166.00,'Packaging: Tinwall besar per 10 pcs','2026-01-16 07:54:12','2026-01-16 07:54:12'),(156,7,1,61,'out',2.00,134.00,132.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-16 07:54:12','2026-01-16 07:54:12'),(157,10,1,61,'out',2.00,450.00,448.00,'Packaging: Sumpit per order','2026-01-16 07:54:12','2026-01-16 07:54:12'),(158,12,1,61,'out',2.00,950.00,948.00,'Packaging: Tisu per order','2026-01-16 07:54:12','2026-01-16 07:54:12'),(159,1,1,62,'out',125.00,3625.00,3500.00,'Pemakaian untuk Ayam Original x 5','2026-01-16 08:07:36','2026-01-16 08:07:36'),(160,3,1,62,'out',5.00,245.00,240.00,'Pemakaian untuk Ayam Original x 5','2026-01-16 08:07:36','2026-01-16 08:07:36'),(161,1,1,62,'out',100.00,3500.00,3400.00,'Pemakaian untuk Ayam Original x 4','2026-01-16 08:07:36','2026-01-16 08:07:36'),(162,3,1,62,'out',4.00,240.00,236.00,'Pemakaian untuk Ayam Original x 4','2026-01-16 08:07:36','2026-01-16 08:07:36'),(163,6,1,62,'out',3.00,166.00,163.00,'Packaging: Tinwall besar per 10 pcs','2026-01-16 08:07:36','2026-01-16 08:07:36'),(164,7,1,62,'out',7.00,132.00,125.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-16 08:07:36','2026-01-16 08:07:36'),(165,10,1,62,'out',2.00,448.00,446.00,'Packaging: Sumpit per order','2026-01-16 08:07:36','2026-01-16 08:07:36'),(166,12,1,62,'out',2.00,948.00,946.00,'Packaging: Tisu per order','2026-01-16 08:07:36','2026-01-16 08:07:36'),(167,1,1,54,'out',250.00,3400.00,3150.00,'Pemakaian untuk Ayam Original x 10','2026-01-19 01:30:04','2026-01-19 01:30:04'),(168,3,1,54,'out',10.00,236.00,226.00,'Pemakaian untuk Ayam Original x 10','2026-01-19 01:30:04','2026-01-19 01:30:04'),(169,1,1,54,'out',250.00,3150.00,2900.00,'Pemakaian untuk Ayam Original x 10','2026-01-19 01:30:04','2026-01-19 01:30:04'),(170,3,1,54,'out',10.00,226.00,216.00,'Pemakaian untuk Ayam Original x 10','2026-01-19 01:30:04','2026-01-19 01:30:04'),(171,6,1,54,'out',2.00,163.00,161.00,'Packaging: Tinwall besar per 10 pcs','2026-01-19 01:30:04','2026-01-19 01:30:04'),(172,7,1,54,'out',4.00,125.00,121.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-19 01:30:04','2026-01-19 01:30:04'),(173,10,1,54,'out',2.00,446.00,444.00,'Packaging: Sumpit per order','2026-01-19 01:30:04','2026-01-19 01:30:04'),(174,12,1,54,'out',2.00,946.00,944.00,'Packaging: Tisu per order','2026-01-19 01:30:04','2026-01-19 01:30:04'),(175,1,1,53,'out',250.00,2900.00,2650.00,'Pemakaian untuk Ayam Original x 10','2026-01-19 01:30:39','2026-01-19 01:30:39'),(176,3,1,53,'out',10.00,216.00,206.00,'Pemakaian untuk Ayam Original x 10','2026-01-19 01:30:39','2026-01-19 01:30:39'),(177,1,1,53,'out',250.00,2650.00,2400.00,'Pemakaian untuk Ayam Original x 10','2026-01-19 01:30:39','2026-01-19 01:30:39'),(178,3,1,53,'out',10.00,206.00,196.00,'Pemakaian untuk Ayam Original x 10','2026-01-19 01:30:39','2026-01-19 01:30:39'),(179,6,1,53,'out',2.00,161.00,159.00,'Packaging: Tinwall besar per 10 pcs','2026-01-19 01:30:39','2026-01-19 01:30:39'),(180,7,1,53,'out',4.00,121.00,117.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-19 01:30:39','2026-01-19 01:30:39'),(181,10,1,53,'out',2.00,444.00,442.00,'Packaging: Sumpit per order','2026-01-19 01:30:39','2026-01-19 01:30:39'),(182,12,1,53,'out',2.00,944.00,942.00,'Packaging: Tisu per order','2026-01-19 01:30:39','2026-01-19 01:30:39'),(183,1,1,63,'out',250.00,2400.00,2150.00,'Pemakaian untuk Ayam Original x 10','2026-01-19 01:56:49','2026-01-19 01:56:49'),(184,3,1,63,'out',10.00,196.00,186.00,'Pemakaian untuk Ayam Original x 10','2026-01-19 01:56:49','2026-01-19 01:56:49'),(185,6,1,63,'out',1.00,159.00,158.00,'Packaging: Tinwall besar per 10 pcs','2026-01-19 01:56:49','2026-01-19 01:56:49'),(186,7,1,63,'out',2.00,117.00,115.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-19 01:56:49','2026-01-19 01:56:49'),(187,10,1,63,'out',2.00,442.00,440.00,'Packaging: Sumpit per order','2026-01-19 01:56:49','2026-01-19 01:56:49'),(188,12,1,63,'out',2.00,942.00,940.00,'Packaging: Tisu per order','2026-01-19 01:56:49','2026-01-19 01:56:49'),(189,1,1,64,'out',50.00,2150.00,2100.00,'Pemakaian untuk Ayam Original x 2','2026-01-22 19:30:50','2026-01-22 19:30:50'),(190,3,1,64,'out',2.00,186.00,184.00,'Pemakaian untuk Ayam Original x 2','2026-01-22 19:30:50','2026-01-22 19:30:50'),(191,1,1,64,'out',250.00,2100.00,1850.00,'Pemakaian untuk Ayam Original x 10','2026-01-22 19:30:50','2026-01-22 19:30:50'),(192,3,1,64,'out',10.00,184.00,174.00,'Pemakaian untuk Ayam Original x 10','2026-01-22 19:30:50','2026-01-22 19:30:50'),(193,1,1,64,'out',100.00,1850.00,1750.00,'Pemakaian untuk Ayam Original x 4','2026-01-22 19:30:50','2026-01-22 19:30:50'),(194,3,1,64,'out',4.00,174.00,170.00,'Pemakaian untuk Ayam Original x 4','2026-01-22 19:30:50','2026-01-22 19:30:50'),(195,1,1,64,'out',125.00,1750.00,1625.00,'Pemakaian untuk Ayam Original x 5','2026-01-22 19:30:50','2026-01-22 19:30:50'),(196,3,1,64,'out',5.00,170.00,165.00,'Pemakaian untuk Ayam Original x 5','2026-01-22 19:30:50','2026-01-22 19:30:50'),(197,6,1,64,'out',6.00,158.00,152.00,'Packaging: Tinwall besar per 10 pcs','2026-01-22 19:30:50','2026-01-22 19:30:50'),(198,7,1,64,'out',12.00,115.00,103.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-22 19:30:50','2026-01-22 19:30:50'),(199,10,1,64,'out',2.00,440.00,438.00,'Packaging: Sumpit per order','2026-01-22 19:30:50','2026-01-22 19:30:50'),(200,12,1,64,'out',2.00,940.00,938.00,'Packaging: Tisu per order','2026-01-22 19:30:50','2026-01-22 19:30:50'),(201,1,1,66,'out',200.00,1625.00,1425.00,'Pemakaian untuk Ayam Original x 8','2026-01-24 03:12:07','2026-01-24 03:12:07'),(202,3,1,66,'out',8.00,165.00,157.00,'Pemakaian untuk Ayam Original x 8','2026-01-24 03:12:07','2026-01-24 03:12:07'),(203,6,1,66,'out',2.00,152.00,150.00,'Packaging: Tinwall besar per 10 pcs','2026-01-24 03:12:07','2026-01-24 03:12:07'),(204,7,1,66,'out',4.00,103.00,99.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-24 03:12:07','2026-01-24 03:12:07'),(205,10,1,66,'out',2.00,438.00,436.00,'Packaging: Sumpit per order','2026-01-24 03:12:07','2026-01-24 03:12:07'),(206,12,1,66,'out',2.00,938.00,936.00,'Packaging: Tisu per order','2026-01-24 03:12:07','2026-01-24 03:12:07'),(207,6,1,70,'out',1.00,150.00,149.00,'Packaging: Tinwall besar per 10 pcs','2026-01-24 16:32:57','2026-01-24 16:32:57'),(208,7,1,70,'out',2.00,99.00,97.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-24 16:32:57','2026-01-24 16:32:57'),(209,10,1,70,'out',2.00,436.00,434.00,'Packaging: Sumpit per order','2026-01-24 16:32:57','2026-01-24 16:32:57'),(210,12,1,70,'out',2.00,936.00,934.00,'Packaging: Tisu per order','2026-01-24 16:32:57','2026-01-24 16:32:57'),(211,6,1,71,'out',1.00,149.00,148.00,'Packaging: Tinwall besar per 10 pcs','2026-01-24 16:34:16','2026-01-24 16:34:16'),(212,7,1,71,'out',2.00,97.00,95.00,'Packaging: Tinwall kecil per 5 pcs','2026-01-24 16:34:16','2026-01-24 16:34:16'),(213,10,1,71,'out',2.00,434.00,432.00,'Packaging: Sumpit per order','2026-01-24 16:34:16','2026-01-24 16:34:16'),(214,12,1,71,'out',2.00,934.00,932.00,'Packaging: Tisu per order','2026-01-24 16:34:16','2026-01-24 16:34:16');
/*!40000 ALTER TABLE `inventory_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2024_01_01_000001_create_users_table',1),(2,'2024_01_01_000002_create_price_categories_table',1),(3,'2024_01_01_000003_create_products_table',1),(4,'2024_01_01_000004_create_packages_table',1),(5,'2024_01_01_000005_create_package_combinations_table',1),(6,'2024_01_01_000006_create_package_combination_items_table',1),(7,'2024_01_01_000007_create_inventory_items_table',1),(8,'2024_01_01_000008_create_recipes_table',1),(9,'2024_01_01_000009_create_packaging_rules_table',1),(10,'2024_01_01_000010_create_transactions_table',1),(11,'2024_01_01_000011_create_transaction_items_table',1),(12,'2024_01_01_000012_create_inventory_transactions_table',1),(13,'2025_12_30_222247_create_personal_access_tokens_table',2),(14,'2024_01_01_000020_remove_price_categories',3),(15,'2024_01_01_000021_create_customers_table',4),(16,'2024_01_01_000022_add_customer_id_to_transactions',4),(17,'2024_01_01_000023_add_level_to_customers',5),(18,'2026_01_04_140901_add_image_to_packages_table',6),(19,'2026_01_04_152115_create_package_discounts_table',7),(20,'2026_01_04_181933_create_payment_methods_table',8),(21,'2026_01_04_181935_add_payment_method_id_to_transactions_table',8),(22,'2026_01_04_185001_add_account_details_to_payment_methods_table',9),(23,'2026_01_05_014051_create_settings_table',10),(24,'2026_01_06_175900_create_product_stock_transactions_table',11),(25,'2026_01_06_205755_create_customer_types_table',12),(26,'2026_01_06_210013_refactor_customer_levels',13),(27,'2026_01_07_130615_add_image_to_package_combinations_table',14),(28,'2026_01_26_120000_add_password_to_customers_table',15),(29,'2026_01_26_140609_add_points_to_customers_table',16),(30,'2026_01_26_140609_create_coupons_tables',16),(31,'2026_02_04_100000_add_dp_fields_to_transactions_table',17),(32,'2026_02_04_153500_make_phone_nullable_in_customers_table',18),(33,'2026_02_05_000001_create_tables_table',19),(34,'2026_02_05_000002_add_table_id_to_transactions_table',19),(35,'2026_02_05_085304_make_user_id_nullable_in_transactions_table',20),(36,'2026_02_05_124928_add_customer_name_to_transactions_table',21),(37,'2026_02_05_232500_make_user_id_nullable_in_stock_transactions',22),(38,'2026_02_06_000001_create_carousel_slides_table',22),(39,'2026_02_06_170000_create_customer_addresses_table',23),(40,'2026_02_09_000001_add_customer_phone_to_transactions_table',24),(41,'2026_02_09_111039_add_customer_phone_to_transactions_table',24),(42,'2026_02_10_090816_add_qr_code_image_to_payment_methods_table',24),(43,'2026_02_10_101222_add_ewallet_type_to_payment_methods_table',25),(44,'2026_02_10_130150_add_avatar_to_customers_and_users_table',26),(45,'2026_02_10_140000_add_password_reset_to_customers_table',27),(46,'2026_02_10_140001_add_email_settings_to_settings_table',27),(47,'2026_02_11_000001_add_finishing_status_to_tables',28),(48,'2026_02_17_003113_add_queue_number_to_transactions_table',29);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `package_combination_items`
--

DROP TABLE IF EXISTS `package_combination_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `package_combination_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `package_combination_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `package_combination_items_package_combination_id_foreign` (`package_combination_id`),
  KEY `package_combination_items_product_id_foreign` (`product_id`),
  CONSTRAINT `package_combination_items_package_combination_id_foreign` FOREIGN KEY (`package_combination_id`) REFERENCES `package_combinations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `package_combination_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_combination_items`
--

LOCK TABLES `package_combination_items` WRITE;
/*!40000 ALTER TABLE `package_combination_items` DISABLE KEYS */;
INSERT INTO `package_combination_items` VALUES (1,1,1,5,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(2,1,2,5,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(3,2,1,5,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(4,2,4,5,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(5,3,2,5,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(6,3,4,5,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(7,4,3,5,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(8,4,4,5,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(10,6,1,10,'2026-01-04 08:02:56','2026-01-04 08:02:56'),(12,8,10,20,'2026-01-04 11:28:46','2026-01-04 11:28:46'),(16,10,2,10,'2026-01-07 06:11:28','2026-01-07 06:11:28'),(17,11,4,10,'2026-01-07 06:16:13','2026-01-07 06:16:13'),(19,13,3,10,'2026-01-09 09:41:29','2026-01-09 09:41:29'),(20,14,7,10,'2026-01-09 09:42:26','2026-01-09 09:42:26'),(21,15,5,10,'2026-01-09 09:43:07','2026-01-09 09:43:07'),(22,16,6,10,'2026-01-09 09:43:34','2026-01-09 09:43:34'),(23,17,8,10,'2026-01-09 09:44:01','2026-01-09 09:44:01'),(24,18,9,10,'2026-01-09 09:44:36','2026-01-09 09:44:36'),(25,19,1,4,'2026-01-09 09:48:16','2026-01-09 09:48:16'),(26,19,4,3,'2026-01-09 09:48:16','2026-01-09 09:48:16'),(27,19,2,3,'2026-01-09 09:48:16','2026-01-09 09:48:16'),(28,20,10,40,'2026-01-09 09:49:28','2026-01-09 09:49:28'),(29,12,2,5,'2026-01-09 10:51:24','2026-01-09 10:51:24'),(30,21,3,5,'2026-01-09 10:51:54','2026-01-09 10:51:54'),(31,7,1,5,'2026-01-09 10:52:07','2026-01-09 10:52:07'),(32,22,4,5,'2026-01-09 10:52:44','2026-01-09 10:52:44'),(33,23,5,5,'2026-01-09 10:53:13','2026-01-09 10:53:13'),(34,24,6,5,'2026-01-09 10:53:33','2026-01-09 10:53:33'),(35,25,7,5,'2026-01-09 10:53:59','2026-01-09 10:53:59'),(37,27,9,5,'2026-01-09 10:54:44','2026-01-09 10:54:44'),(38,26,8,5,'2026-01-09 10:54:52','2026-01-09 10:54:52'),(39,28,1,2,'2026-01-09 11:47:02','2026-01-09 11:47:02'),(40,28,2,2,'2026-01-09 11:47:02','2026-01-09 11:47:02'),(41,28,4,2,'2026-01-09 11:47:02','2026-01-09 11:47:02'),(42,28,5,2,'2026-01-09 11:47:02','2026-01-09 11:47:02'),(43,28,6,2,'2026-01-09 11:47:02','2026-01-09 11:47:02'),(44,29,1,2,'2026-01-09 11:48:12','2026-01-09 11:48:12'),(45,29,2,2,'2026-01-09 11:48:12','2026-01-09 11:48:12'),(46,29,4,2,'2026-01-09 11:48:12','2026-01-09 11:48:12'),(47,29,5,2,'2026-01-09 11:48:12','2026-01-09 11:48:12'),(48,29,8,2,'2026-01-09 11:48:12','2026-01-09 11:48:12');
/*!40000 ALTER TABLE `package_combination_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `package_combinations`
--

DROP TABLE IF EXISTS `package_combinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `package_combinations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `package_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_price` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `package_combinations_package_id_foreign` (`package_id`),
  CONSTRAINT `package_combinations_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_combinations`
--

LOCK TABLES `package_combinations` WRITE;
/*!40000 ALTER TABLE `package_combinations` DISABLE KEYS */;
INSERT INTO `package_combinations` VALUES (1,4,'Ayam Original + Ayam Udang',34000,1,NULL,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(2,4,'Ayam Original + Ayam Keju',34000,1,NULL,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(3,4,'Ayam Udang + Ayam Keju',35000,1,NULL,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(4,4,'Ayam Pedas + Ayam Keju',35000,1,NULL,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(6,6,'Dimsum Ayam Original',32000,1,NULL,'2026-01-04 08:02:56','2026-01-04 08:02:56'),(7,2,'Dimsum Ayam Original',16000,1,NULL,'2026-01-04 08:12:38','2026-01-09 10:52:07'),(8,7,'Ciraso 20',15000,1,NULL,'2026-01-04 11:28:46','2026-01-04 11:28:46'),(10,6,'Dimsum Udang',34000,1,'combinations/NZiYpp0nbw9ugoR5vedZaUDCLQgDr6qQCluPpCT3.jpg','2026-01-07 06:07:00','2026-01-07 06:11:28'),(11,6,'Dimsum Keju',35000,1,'combinations/up88CB3ZAuCBp3q9SCu5C3ejuybjRb4YORJTNrqJ.jpg','2026-01-07 06:16:13','2026-01-07 06:16:13'),(12,2,'Dimsum Udang',19000,1,NULL,'2026-01-07 06:23:58','2026-01-09 10:51:24'),(13,6,'Dimsum Pedas',34000,1,NULL,'2026-01-09 09:41:29','2026-01-09 09:41:29'),(14,6,'Dimsum Kol',30000,1,NULL,'2026-01-09 09:42:26','2026-01-09 09:42:26'),(15,6,'Dimsum Nori',35000,1,NULL,'2026-01-09 09:43:07','2026-01-09 09:43:07'),(16,6,'Gyoza',34000,1,NULL,'2026-01-09 09:43:34','2026-01-09 09:43:34'),(17,6,'Udang Utuh',45000,1,NULL,'2026-01-09 09:44:01','2026-01-09 09:44:01'),(18,6,'Udang Utuh Pedas',47000,1,NULL,'2026-01-09 09:44:36','2026-01-09 09:44:36'),(19,9,'Ayam + Keju + Udang',35000,1,NULL,'2026-01-09 09:48:16','2026-01-09 09:48:16'),(20,7,'Ciraso 40',25000,1,NULL,'2026-01-09 09:49:28','2026-01-09 09:49:28'),(21,2,'Dimsum Ayam Pedas',19000,1,NULL,'2026-01-09 10:51:54','2026-01-09 10:51:54'),(22,2,'Dimsum Ayam Keju',20000,1,NULL,'2026-01-09 10:52:44','2026-01-09 10:52:44'),(23,2,'Dimsum Nori',20000,1,NULL,'2026-01-09 10:53:13','2026-01-09 10:53:13'),(24,2,'Gyoza',19000,1,NULL,'2026-01-09 10:53:33','2026-01-09 10:53:33'),(25,2,'Kol',16000,1,NULL,'2026-01-09 10:53:59','2026-01-09 10:53:59'),(26,2,'Udang Utuh',24000,1,NULL,'2026-01-09 10:54:19','2026-01-09 10:54:52'),(27,2,'Udang Utuh Pedas',25000,1,NULL,'2026-01-09 10:54:44','2026-01-09 10:54:44'),(28,5,'Mix 5 Varian G',38000,1,NULL,'2026-01-09 11:47:02','2026-01-09 11:47:02'),(29,5,'Mix 5 Varian Udang Utuh',42000,1,NULL,'2026-01-09 11:48:12','2026-01-09 11:48:12');
/*!40000 ALTER TABLE `package_combinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `package_discounts`
--

DROP TABLE IF EXISTS `package_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `package_discounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `package_id` bigint unsigned NOT NULL,
  `customer_type_id` bigint unsigned NOT NULL,
  `discount_amount` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `package_discounts_package_id_customer_type_id_unique` (`package_id`,`customer_type_id`),
  KEY `package_discounts_customer_type_id_foreign` (`customer_type_id`),
  CONSTRAINT `package_discounts_customer_type_id_foreign` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `package_discounts_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_discounts`
--

LOCK TABLES `package_discounts` WRITE;
/*!40000 ALTER TABLE `package_discounts` DISABLE KEYS */;
INSERT INTO `package_discounts` VALUES (5,4,2,4000,'2026-01-04 11:23:41','2026-01-04 11:23:41'),(6,4,3,6000,'2026-01-04 11:23:41','2026-01-04 11:23:41'),(9,6,1,0,'2026-01-07 07:01:55','2026-01-07 07:01:55'),(10,6,2,4000,'2026-01-07 07:01:55','2026-01-07 07:01:55'),(11,6,3,6000,'2026-01-07 07:01:55','2026-01-07 07:01:55'),(12,9,1,0,'2026-01-24 03:09:26','2026-01-24 03:09:26'),(13,9,2,3000,'2026-01-24 03:09:26','2026-01-24 03:09:26'),(14,9,3,7000,'2026-01-24 03:09:26','2026-01-24 03:09:26');
/*!40000 ALTER TABLE `package_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('bundle','mix') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_quantity` int NOT NULL,
  `variant_count` int DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `packages_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

LOCK TABLES `packages` WRITE;
/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
INSERT INTO `packages` VALUES (1,'Paket 2 pcs','PKT-2','bundle',2,NULL,NULL,NULL,1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(2,'Paket 5 pcs','PKT-5','bundle',5,NULL,NULL,NULL,1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(4,'Mix 2 Varian','MIX-2','mix',10,2,NULL,NULL,1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(5,'Mix 5 Varian','MIX-5','mix',10,5,NULL,NULL,1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(6,'Mix 1 Varian','MIX-1','mix',10,1,NULL,NULL,1,'2026-01-04 08:01:03','2026-01-04 08:01:03'),(7,'Ciraso','CR-20','bundle',40,2,NULL,NULL,1,'2026-01-04 11:28:16','2026-01-09 09:49:07'),(9,'Mix 3 Varian','MIX3','mix',10,3,NULL,NULL,1,'2026-01-09 09:46:59','2026-01-09 09:46:59');
/*!40000 ALTER TABLE `packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packaging_rules`
--

DROP TABLE IF EXISTS `packaging_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packaging_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint unsigned NOT NULL,
  `rule_type` enum('per_quantity','per_order') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger_quantity` int DEFAULT NULL,
  `usage_quantity` decimal(10,2) NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `packaging_rules_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `packaging_rules_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packaging_rules`
--

LOCK TABLES `packaging_rules` WRITE;
/*!40000 ALTER TABLE `packaging_rules` DISABLE KEYS */;
INSERT INTO `packaging_rules` VALUES (1,6,'per_quantity',10,1.00,'Tinwall besar per 10 pcs',1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(2,7,'per_quantity',5,1.00,'Tinwall kecil per 5 pcs',1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(3,10,'per_order',NULL,2.00,'Sumpit per order',1,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(4,12,'per_order',NULL,2.00,'Tisu per order',1,'2025-12-30 15:09:31','2025-12-30 15:09:31');
/*!40000 ALTER TABLE `packaging_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('cash','transfer','qris','ewallet','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `account_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'Cash','cash',NULL,NULL,NULL,1,'2026-01-04 11:44:33','2026-02-07 03:45:55'),(2,'QRIS','qris',NULL,NULL,NULL,1,'2026-01-04 11:44:33','2026-01-04 11:44:33'),(4,'Mandiri','transfer','Yoga Aris P','1360032592054',NULL,1,'2026-01-04 11:44:33','2026-02-10 03:20:27'),(5,'BCA','transfer','Yoga Aris P','123456789',NULL,1,'2026-02-10 03:19:17','2026-02-10 03:19:17');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=186 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'pos-token','51370f40f9020fb7bab6c82f4615bb0f8335a997f5b254602d599fb45cc04c94','[\"*\"]','2025-12-30 15:24:47',NULL,'2025-12-30 15:23:21','2025-12-30 15:24:47'),(2,'App\\Models\\User',1,'pos-token','bd6cc9cf67298ccd52140ccf316362f96b23381b82e60e8187ee47caab8fde0c','[\"*\"]',NULL,NULL,'2025-12-30 15:26:21','2025-12-30 15:26:21'),(3,'App\\Models\\User',1,'pos-token','99c5613a741f65321fb101f13c6c00097921948096a29f599cf095561ecceb22','[\"*\"]',NULL,NULL,'2025-12-30 15:31:59','2025-12-30 15:31:59'),(4,'App\\Models\\User',1,'pos-token','25924777286d609bf7e2ffbb50a0fc6002093f4713249cd3405eddd0814535b1','[\"*\"]',NULL,NULL,'2025-12-30 15:32:13','2025-12-30 15:32:13'),(5,'App\\Models\\User',1,'pos-token','2066b47c1103fb2c393be14201abf9bce2903ff5b9b5a97ce72a126370d08083','[\"*\"]',NULL,NULL,'2025-12-30 15:34:15','2025-12-30 15:34:15'),(6,'App\\Models\\User',1,'pos-token','13fe37f80201cf23dc36d0b4ccdf74b85c677a0952fc597092659b1ade208b06','[\"*\"]',NULL,NULL,'2025-12-30 15:37:13','2025-12-30 15:37:13'),(7,'App\\Models\\User',1,'pos-token','3fa6eb3b4166cf183a43b0d96ae2c7d57d40235952c62e41b3610436ca56ba55','[\"*\"]',NULL,NULL,'2025-12-30 15:37:41','2025-12-30 15:37:41'),(8,'App\\Models\\User',2,'pos-token','4cd6e2ba0080709a57d4a08eec132784f0538fab040b938a82d02a3085918496','[\"*\"]',NULL,NULL,'2025-12-30 15:37:50','2025-12-30 15:37:50'),(9,'App\\Models\\User',1,'pos-token','a5a8ec1e4341e0fa6a09cea06cdfd2477bb0789c8c302c7ea519a59039a0d86a','[\"*\"]',NULL,NULL,'2025-12-30 15:38:35','2025-12-30 15:38:35'),(10,'App\\Models\\User',2,'pos-token','131ce535fa386c18b64fa7539ae6368012c6e1d0d7b9f317e910425f0ea427ea','[\"*\"]',NULL,NULL,'2025-12-30 15:40:11','2025-12-30 15:40:11'),(11,'App\\Models\\User',1,'pos-token','872afafa4441e3c068ee978d343037ec7d42ac9cc3a091108cbaaa710fe343c8','[\"*\"]',NULL,NULL,'2025-12-30 15:40:21','2025-12-30 15:40:21'),(12,'App\\Models\\User',1,'pos-token','5bf9fe148f69bcce90941f55cfa13fded4348ddbbb26eef91cad88a461b74c6c','[\"*\"]',NULL,NULL,'2025-12-30 15:42:41','2025-12-30 15:42:41'),(13,'App\\Models\\User',2,'pos-token','c38c3eb49cbe88c2ba5e021618821a73565de7f5e5ab369ff80d18299d4a74e9','[\"*\"]',NULL,NULL,'2025-12-30 15:43:36','2025-12-30 15:43:36'),(14,'App\\Models\\User',3,'pos-token','db0d0a61cb783d45b93e9c5c5b27f88b4f29be9ac8a208a204972be3c1176aec','[\"*\"]',NULL,NULL,'2025-12-30 15:43:47','2025-12-30 15:43:47'),(15,'App\\Models\\User',1,'pos-token','b188b1548cf08b59e94746aacb3411763644193da2a1c096bce13cd83d90feb9','[\"*\"]',NULL,NULL,'2025-12-30 15:45:07','2025-12-30 15:45:07'),(16,'App\\Models\\User',2,'pos-token','7ed8e162fbe1337eac6015f23806f301647381b4e1a6eb9463fb94106bb73e5c','[\"*\"]',NULL,NULL,'2025-12-30 15:45:19','2025-12-30 15:45:19'),(17,'App\\Models\\User',3,'pos-token','a30de471dfa08437eda3f5b90ff212966bbb8a6c8e113e27a294cc9b92b7ac2d','[\"*\"]',NULL,NULL,'2025-12-30 15:45:30','2025-12-30 15:45:30'),(18,'App\\Models\\User',1,'pos-token','4b6ee011445116a272b7da73e2a2529d37556a1935885b569eab119c016b5907','[\"*\"]','2025-12-30 15:45:40',NULL,'2025-12-30 15:45:40','2025-12-30 15:45:40'),(19,'App\\Models\\User',1,'pos-token','f3df800a5e9a6122d7272f0ec1d5c32f52dc5515538c9c326076f04a9b76854c','[\"*\"]','2026-01-04 18:34:13',NULL,'2025-12-30 15:46:09','2026-01-04 18:34:13'),(20,'App\\Models\\User',1,'pos-token','d91cc2d410051e18c6c2217f9410256e95ee60ffa5f94d8fe14b9e4956adbb89','[\"*\"]',NULL,NULL,'2025-12-30 17:01:33','2025-12-30 17:01:33'),(21,'App\\Models\\User',2,'pos-token','b36f169f0fb1939e37236c97b5ef1852652b86dfb069f850f5972b3ccaec3488','[\"*\"]',NULL,NULL,'2025-12-30 17:01:34','2025-12-30 17:01:34'),(22,'App\\Models\\User',3,'pos-token','52aa5805dc72c14b338caef8787206dd063cfcf98f3b262a0953feba7f60ae23','[\"*\"]',NULL,NULL,'2025-12-30 17:01:34','2025-12-30 17:01:34'),(23,'App\\Models\\User',1,'pos-token','a1747a6802c8118a11d4b5e7910c995341ccce8b198a06e41e77e82839866eb0','[\"*\"]',NULL,NULL,'2025-12-30 17:18:48','2025-12-30 17:18:48'),(24,'App\\Models\\User',1,'pos-token','b157f3fda6bc4253b4437e2eeb56a6774f1e12f0d81d89603b1fbd8e56786409','[\"*\"]','2025-12-30 17:19:12',NULL,'2025-12-30 17:19:12','2025-12-30 17:19:12'),(25,'App\\Models\\User',2,'pos-token','19cdacf6ba05428e48318a43e14bd2801885baa281e73b5bedf0b1ef8f1eb29f','[\"*\"]','2025-12-30 17:23:32',NULL,'2025-12-30 17:23:32','2025-12-30 17:23:32'),(26,'App\\Models\\User',1,'pos-token','0ca70693fc55604b585dd00237ce6f5e6964b7ebfc456e8db62989d045c125ed','[\"*\"]','2026-01-04 19:36:33',NULL,'2025-12-30 17:44:38','2026-01-04 19:36:33'),(27,'App\\Models\\User',1,'pos-token','cee45efad0a2517d436923e2bdfe317b905490945bf3799246475f2e2eec5486','[\"*\"]','2025-12-30 17:52:21',NULL,'2025-12-30 17:52:21','2025-12-30 17:52:21'),(28,'App\\Models\\User',1,'pos-token','914af5f8c39a3d8b91dbe7f664a8ce5ddb6d1d0c3ee54e2bf63307c299065938','[\"*\"]','2025-12-30 17:52:40',NULL,'2025-12-30 17:52:40','2025-12-30 17:52:40'),(29,'App\\Models\\User',2,'pos-token','00621617d16b0595e2a2ba527e759c53f53f0e5469fa45909b906665c3658fce','[\"*\"]','2025-12-30 17:53:22',NULL,'2025-12-30 17:53:22','2025-12-30 17:53:22'),(30,'App\\Models\\User',1,'pos-token','9f06a305877e1dd636fc153ad7c2f016c96d8830aa5f7a1595662fb9909efc54','[\"*\"]','2025-12-30 18:01:00',NULL,'2025-12-30 18:01:00','2025-12-30 18:01:00'),(31,'App\\Models\\User',1,'pos-token','8021553da2c70fea178891c501efca2f349a2d8b576fadd18fe401b7dfbdb777','[\"*\"]','2025-12-30 18:03:04',NULL,'2025-12-30 18:03:04','2025-12-30 18:03:04'),(32,'App\\Models\\User',2,'pos-token','096680285d724f9d8edd483e423712babe656213c6b9a7f79a5941089699800d','[\"*\"]','2025-12-30 18:09:11',NULL,'2025-12-30 18:09:11','2025-12-30 18:09:11'),(33,'App\\Models\\User',1,'test','ff261519fc966aa1cb66ed8fb28bf35a82b7e5faa192ea1950743619fe1d305f','[\"*\"]','2026-01-04 15:27:25',NULL,'2026-01-04 15:27:25','2026-01-04 15:27:25'),(34,'App\\Models\\User',1,'test','031e3a67d6a850ccf9ca1b55756fd96cada742cf42b2aff9f8d7de8776d730bd','[\"*\"]',NULL,NULL,'2026-01-04 17:41:21','2026-01-04 17:41:21'),(35,'App\\Models\\User',1,'pos-token','c0d61b883a9d29858fd3e6bbed01fb51526fc270a0ac630bf53fb9abcb3b7b00','[\"*\"]','2026-01-04 18:00:49',NULL,'2026-01-04 17:55:50','2026-01-04 18:00:49'),(36,'App\\Models\\User',1,'pos-token','062066bff534c0e55e20b0a2445aa8cab772ff5d122f7eca26ff579c0e0f34d3','[\"*\"]','2026-01-04 19:16:56',NULL,'2026-01-04 18:06:40','2026-01-04 19:16:56'),(37,'App\\Models\\User',1,'pos-token','2d437236392579e8f79529e82de05b50588a5cc292824112a0188ebba90bd570','[\"*\"]','2026-01-06 10:50:45',NULL,'2026-01-04 18:35:04','2026-01-06 10:50:45'),(40,'App\\Models\\User',1,'pos-token','6c1b8b9d856dd7e10f85b30deb6179f936a6c3ee6836560940cd37329e0b9431','[\"*\"]','2026-01-06 15:49:30',NULL,'2026-01-06 09:54:06','2026-01-06 15:49:30'),(42,'App\\Models\\User',1,'pos-token','3ebe2b5096c587e08c010dc42bf34828b6712d1438e4dcf94f5c0e6893c83a4d','[\"*\"]','2026-01-06 16:50:25',NULL,'2026-01-06 13:48:18','2026-01-06 16:50:25'),(43,'App\\Models\\User',1,'pos-token','78320faf8936bddc434063bbb778c8155a1975422be3aaada6d852b40d738a5b','[\"*\"]','2026-01-06 16:06:13',NULL,'2026-01-06 16:00:41','2026-01-06 16:06:13'),(45,'App\\Models\\User',1,'pos-token','9ed0affb7bb218027196f344992e7690dd3a185b469ce03a4110f5cf7b0e2382','[\"*\"]','2026-01-15 12:33:29',NULL,'2026-01-07 05:22:39','2026-01-15 12:33:29'),(46,'App\\Models\\User',1,'pos-token','45c9a05d5830935bb159c94945042c7adf683f03e0ce9b2793185f95abcb5dc6','[\"*\"]','2026-01-09 03:49:21',NULL,'2026-01-07 05:23:24','2026-01-09 03:49:21'),(47,'App\\Models\\User',1,'pos-token','702c865d1959707f557fa304342bdf2ed23577cff4f69f8b6b48a71f597fd064','[\"*\"]','2026-01-09 02:04:29',NULL,'2026-01-09 02:04:29','2026-01-09 02:04:29'),(48,'App\\Models\\User',1,'pos-token','6bf19ebff95a539944ec0845f51f900023d7590e8532c440e6925d9e7ad1a928','[\"*\"]','2026-01-08 08:02:36',NULL,'2026-01-08 06:48:29','2026-01-08 08:02:36'),(49,'App\\Models\\User',1,'pos-token','f7b892f1ca156b8c76334b6bec1fdbd10d63e9910a0a76bd429bafae641faa57','[\"*\"]','2026-01-17 00:37:25',NULL,'2026-01-14 15:57:42','2026-01-17 00:37:25'),(51,'App\\Models\\User',1,'pos-token','543a8aead7bd966328aea0e922cd3f40a3b810ba158a3c4fb2f5917da711da26','[\"*\"]','2026-01-26 11:28:52',NULL,'2026-01-18 08:01:28','2026-01-26 11:28:52'),(52,'App\\Models\\User',1,'pos-token','977dee16695f184c7042f4ce5b132e90c7b327737e584c21359a4107bb34678a','[\"*\"]','2026-01-18 12:52:54',NULL,'2026-01-18 10:02:21','2026-01-18 12:52:54'),(54,'App\\Models\\User',1,'pos-token','575b2da9e66edfc8166946e4508b514442d78c970f47dee6f3680bc3a4973903','[\"*\"]','2026-01-19 01:12:23',NULL,'2026-01-18 11:46:10','2026-01-19 01:12:23'),(58,'App\\Models\\User',1,'pos-token','40066f32c34f345f60d4b9e62aa3204df2dcd8f076c73f420eb0608fce34d630','[\"*\"]','2026-01-21 04:24:25',NULL,'2026-01-19 13:28:30','2026-01-21 04:24:25'),(62,'App\\Models\\User',1,'pos-token','946e446fdccc75002f0afb1d0c80bb8a614dd78dcf17785ea42d4c4e70aec37c','[\"*\"]','2026-01-21 08:36:14',NULL,'2026-01-21 08:32:33','2026-01-21 08:36:14'),(63,'App\\Models\\User',1,'pos-token','862498c9d6fa069aaa0a07dd80ea375cd76aa104ea64b8e6bc521ffffd37b60e','[\"*\"]','2026-01-26 04:34:15',NULL,'2026-01-21 08:34:43','2026-01-26 04:34:15'),(64,'App\\Models\\User',1,'pos-token','aa0afc9878efa270e43b4a971f32fbb4d4e67adcd03b3293d96192287a0fe42c','[\"*\"]','2026-01-21 08:36:47',NULL,'2026-01-21 08:36:38','2026-01-21 08:36:47'),(65,'App\\Models\\User',1,'pos-token','64d89f592045dcd480f0d84cd0e80228e3674f66746c9df329eff7f19b40442e','[\"*\"]','2026-01-21 09:08:07',NULL,'2026-01-21 09:06:11','2026-01-21 09:08:07'),(66,'App\\Models\\User',1,'pos-token','78f26761a0940b115eaccee3f9c12190c66e7d40c47f2501c9f737f0de585f7b','[\"*\"]','2026-01-21 09:55:41',NULL,'2026-01-21 09:55:27','2026-01-21 09:55:41'),(67,'App\\Models\\User',1,'pos-token','cd26d9a72d5ad6be646a8ce8aa3702bb1dd9e67d7116ec9217700402c14e059e','[\"*\"]','2026-01-21 11:48:52',NULL,'2026-01-21 11:48:40','2026-01-21 11:48:52'),(68,'App\\Models\\User',1,'pos-token','774425946f4118eb6462e75dc0d7fc62d390fcd67dce26529827f054fea97b51','[\"*\"]','2026-01-21 12:01:00',NULL,'2026-01-21 12:00:02','2026-01-21 12:01:00'),(70,'App\\Models\\User',1,'pos-token','59d942cdfe7ab7b8af714c9b83692d061c3ca2ea7818dab92a8c5350a21ffd73','[\"*\"]','2026-01-21 15:21:55',NULL,'2026-01-21 15:21:42','2026-01-21 15:21:55'),(71,'App\\Models\\User',1,'pos-token','880a63427b7b436ac449077a7b7a04d5efa534f171cc2760953e5ad588d418c9','[\"*\"]','2026-01-24 23:25:10',NULL,'2026-01-22 12:40:45','2026-01-24 23:25:10'),(72,'App\\Models\\User',1,'pos-token','c2a90c6b8c8f52637740c0fb2ce7788d4dbcf783a2c873a50b58dda3e67e728f','[\"*\"]',NULL,NULL,'2026-01-22 12:41:38','2026-01-22 12:41:38'),(73,'App\\Models\\User',1,'pos-token','25814aad2f29f6be1a7ef9c2e6b6abf1f5f1877136e29347f8641569ddb1c942','[\"*\"]','2026-01-22 13:36:39',NULL,'2026-01-22 13:36:37','2026-01-22 13:36:39'),(74,'App\\Models\\User',1,'pos-token','41e556a75e565e55de2e80a03a871cbaa3e1ec893383fce6520e22700c412d17','[\"*\"]','2026-01-22 18:53:58',NULL,'2026-01-22 18:53:48','2026-01-22 18:53:58'),(78,'App\\Models\\User',1,'pos-token','f83b28086374322d23855605ff340b1a1321eea2d2cc0a12ca8b3ba49b097db9','[\"*\"]','2026-01-24 23:24:50',NULL,'2026-01-23 15:53:10','2026-01-24 23:24:50'),(79,'App\\Models\\User',1,'pos-token','28a67956232ef2b9c219860559be43cfcd9252ff24c10b794ba972da4811ea30','[\"*\"]','2026-01-23 15:56:58',NULL,'2026-01-23 15:53:28','2026-01-23 15:56:58'),(80,'App\\Models\\User',1,'pos-token','277303b1d16152551b06591807052e578ab9ced964aa6821e9493455369b6901','[\"*\"]','2026-01-24 02:18:57',NULL,'2026-01-23 16:01:16','2026-01-24 02:18:57'),(81,'App\\Models\\User',1,'pos-token','925f346d843b8a10a4ff64ce0b983260cd5cf093135d3c582c5f3c09b200adf8','[\"*\"]','2026-01-24 04:48:38',NULL,'2026-01-24 04:48:21','2026-01-24 04:48:38'),(82,'App\\Models\\User',1,'pos-token','51f8d25507bb18d1cdba5aeeab9bcb575ad18403d6a22ff1271713ebc2303674','[\"*\"]','2026-01-24 16:34:26',NULL,'2026-01-24 16:24:57','2026-01-24 16:34:26'),(84,'App\\Models\\Customer',10,'customer-token','073ce2ca8bd49001eddc5df69f1c98e31804c51366781a5f6c7da6cde4575d6f','[\"*\"]',NULL,NULL,'2026-01-28 04:38:31','2026-01-28 04:38:31'),(95,'App\\Models\\Customer',6,'customer-token','7a304862a6295f6e3cea64b037a942c3b2694598422f7551835f519f822edea5','[\"*\"]','2026-01-31 03:24:35',NULL,'2026-01-30 16:54:43','2026-01-31 03:24:35'),(102,'App\\Models\\Customer',10,'customer-token','16c907ab095fbee489656fbae87603705a9fbf81cc9c8149684f80cab43c6463','[\"*\"]','2026-02-04 08:51:00',NULL,'2026-02-04 08:50:59','2026-02-04 08:51:00'),(114,'App\\Models\\User',1,'pos-token','9e77d0ef8823af2c7f938336947b495e7f4849e789fedfce7e4ba3509f0a79ec','[\"*\"]','2026-02-11 12:01:00',NULL,'2026-02-05 01:38:20','2026-02-11 12:01:00'),(119,'App\\Models\\User',1,'pos-token','31496ed0b1fcd24341da5f1c473a577a56631c99ace70c436d48170a3d60378e','[\"*\"]','2026-02-05 07:14:14',NULL,'2026-02-05 04:07:57','2026-02-05 07:14:14'),(121,'App\\Models\\Customer',10,'customer-token','c0a9686fb73822ccc0a22c99898a0f03aaa576522dccf2cff5ceac71734c5ae8','[\"*\"]','2026-02-05 06:09:42',NULL,'2026-02-05 05:14:23','2026-02-05 06:09:42'),(122,'App\\Models\\User',1,'pos-token','f8408e94e1427003769572ea35e429cc04d990abdf1dc4d227cd7053f974f02f','[\"*\"]','2026-02-05 07:29:45',NULL,'2026-02-05 06:19:00','2026-02-05 07:29:45'),(123,'App\\Models\\Customer',10,'customer-token','83c1f278af676e31abf6d07a7d7096f34e367374318edd097bacfc2d27f51099','[\"*\"]','2026-02-05 08:11:14',NULL,'2026-02-05 07:47:50','2026-02-05 08:11:14'),(124,'App\\Models\\Customer',10,'customer-token','3460d8e9442aa7055fbecf5651d24113beb1ca8705560334850c8a8821d645c0','[\"*\"]','2026-02-05 08:18:50',NULL,'2026-02-05 08:13:19','2026-02-05 08:18:50'),(125,'App\\Models\\Customer',10,'customer-token','6d31652afdb1286e3f85ea4f00807b5afd7b20272cf92008c749562ac423fa8a','[\"*\"]','2026-02-06 04:08:14',NULL,'2026-02-05 08:22:12','2026-02-06 04:08:14'),(130,'App\\Models\\User',1,'pos-token','6dcfbe3e25b599275276297d360d8fc910626666fe21521d09642fdd3c3aa4a2','[\"*\"]','2026-02-06 22:15:39',NULL,'2026-02-06 04:11:38','2026-02-06 22:15:39'),(139,'App\\Models\\User',1,'pos-token','7508c47a52aa7c4844a64aeb2224e27ad8c15727cbbf98013f1534f61118da7b','[\"*\"]','2026-02-07 02:57:53',NULL,'2026-02-06 22:15:55','2026-02-07 02:57:53'),(144,'App\\Models\\Customer',10,'customer-token','4275547df15042e385255ac41569a5016cd6b0cf7a974fef893fbae19a3c475d','[\"*\"]','2026-02-11 03:00:16',NULL,'2026-02-09 03:08:19','2026-02-11 03:00:16'),(147,'App\\Models\\User',1,'pos-token','6bc3827ca36b7cfeb40465233ada22b2805c678b2d0bda4568e38b604bab417b','[\"*\"]','2026-02-09 15:25:51',NULL,'2026-02-09 13:25:18','2026-02-09 15:25:51'),(150,'App\\Models\\User',1,'pos-token','3b97a8c0dbd53e00939df7fe274a2dd26641f36bab8a6c264dcdaf9251e0e2ec','[\"*\"]','2026-02-10 04:17:32',NULL,'2026-02-10 03:08:17','2026-02-10 04:17:32'),(163,'App\\Models\\User',1,'pos-token','a72b1715a0af0feb0d2e74d5d7ded91c72dd1c0d1dcfd93896e912bb97d5847a','[\"*\"]','2026-02-10 11:56:48',NULL,'2026-02-10 11:56:37','2026-02-10 11:56:48'),(166,'App\\Models\\User',1,'pos-token','727525944f4687c95b86c6f62ffc42a47a01d64c3c4ed521182058ff7d914849','[\"*\"]','2026-02-11 15:36:04',NULL,'2026-02-11 03:05:09','2026-02-11 15:36:04'),(172,'App\\Models\\User',1,'pos-token','99c79c6630d90d75cf442d8f384761fee14b6cddd137772de26b1e24651e9a9c','[\"*\"]','2026-02-11 16:07:15',NULL,'2026-02-11 14:58:07','2026-02-11 16:07:15'),(180,'App\\Models\\User',1,'pos-token','df2537ba24ddf083dbf8879155c9bd4450019bee524dcd3c089220113eb21968','[\"*\"]','2026-02-14 03:16:14',NULL,'2026-02-12 08:00:11','2026-02-14 03:16:14'),(181,'App\\Models\\User',1,'pos-token','ba48486a300a2572832799d4f28ad7eca760f13f99b69787850b5b0e5118a083','[\"*\"]','2026-02-14 03:11:59',NULL,'2026-02-14 02:43:52','2026-02-14 03:11:59'),(185,'App\\Models\\User',1,'pos-token','84752613debc280e749d80d2bd8e01f69ab890420eb4da91ddc5de2bc0dece47','[\"*\"]','2026-02-21 12:23:07',NULL,'2026-02-21 08:57:28','2026-02-21 12:23:07');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_stock_transactions`
--

DROP TABLE IF EXISTS `product_stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_stock_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `type` enum('in','out','adjustment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `stock_before` int NOT NULL,
  `stock_after` int NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_stock_transactions_product_id_foreign` (`product_id`),
  KEY `product_stock_transactions_user_id_foreign` (`user_id`),
  KEY `product_stock_transactions_transaction_id_foreign` (`transaction_id`),
  CONSTRAINT `product_stock_transactions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stock_transactions_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_stock_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_stock_transactions`
--

LOCK TABLES `product_stock_transactions` WRITE;
/*!40000 ALTER TABLE `product_stock_transactions` DISABLE KEYS */;
INSERT INTO `product_stock_transactions` VALUES (1,4,1,NULL,'in',10,125,135,'Penyesuaian stok manual','2026-01-06 11:08:46','2026-01-06 11:08:46'),(2,1,1,NULL,'in',200,30,230,'Penyesuaian stok manual','2026-01-06 11:47:57','2026-01-06 11:47:57'),(3,1,1,NULL,'in',300,150,450,'Penyesuaian stok manual','2026-01-06 15:34:41','2026-01-06 15:34:41'),(4,7,NULL,87,'out',10,980,970,'Penjualan - DS0602261641','2026-02-06 11:20:15','2026-02-06 11:20:15'),(5,4,NULL,NULL,'out',10,86,76,'Penjualan - DS0702261037','2026-02-07 04:50:35','2026-02-07 04:50:35'),(6,1,NULL,89,'out',4,287,283,'Penjualan - DS0902262029','2026-02-09 13:29:46','2026-02-09 13:29:46'),(7,4,NULL,89,'out',3,76,73,'Penjualan - DS0902262029','2026-02-09 13:29:46','2026-02-09 13:29:46'),(8,2,NULL,89,'out',3,51,48,'Penjualan - DS0902262029','2026-02-09 13:29:46','2026-02-09 13:29:46'),(9,7,NULL,NULL,'out',10,970,960,'Penjualan - DS1102261848','2026-02-11 11:49:04','2026-02-11 11:49:04'),(10,4,NULL,NULL,'out',1,73,72,'Penjualan - DS1102262235','2026-02-11 15:35:33','2026-02-11 15:35:33'),(11,3,NULL,NULL,'out',10,80,70,'Penjualan - DS1102262307','2026-02-11 16:07:15','2026-02-11 16:07:15');
/*!40000 ALTER TABLE `product_stock_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_price` int NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `min_stock` int NOT NULL DEFAULT '10',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Ayam Original','AYM-ORI',4000,283,10,NULL,'products/ayam-original-1767695471.jpg',1,'2025-12-30 15:09:31','2026-02-21 09:08:55'),(2,'Ayam Udang','AYM-UDG',4500,48,10,NULL,'products/ayam-udang-1767708223.jpg',1,'2025-12-30 15:09:31','2026-02-09 13:29:46'),(3,'Ayam Pedas','AYM-PDS',4000,70,10,NULL,'products/ayam-pedas-1767695526.JPG',1,'2025-12-30 15:09:31','2026-02-11 16:07:15'),(4,'Ayam Keju','AYM-KJU',4500,72,10,NULL,'products/ayam-keju-1767695511.jpg',1,'2025-12-30 15:09:31','2026-02-11 15:35:33'),(5,'Nori','NORI',5000,98,10,NULL,NULL,1,'2025-12-30 15:09:31','2026-01-22 19:30:50'),(6,'Gyoza','GYOZA',5000,48,10,NULL,NULL,1,'2025-12-30 15:09:31','2026-01-22 19:30:50'),(7,'Kol','KOL',3500,960,10,NULL,'products/kol-1767708248.jpg',1,'2025-12-30 15:09:31','2026-02-11 11:49:04'),(8,'Udang Utuh','UDG-UTH',6000,49,10,NULL,'products/udang-utuh-1767708266.jpg',1,'2025-12-30 15:09:31','2026-01-06 14:04:26'),(9,'Udang Utuh Pedas','UDG-PDS',6000,50,10,NULL,'products/udang-utuh-pedas-1767708278.jpg',1,'2025-12-30 15:09:31','2026-01-06 14:04:38'),(10,'Ciraso','CIRASO',4000,40,10,NULL,NULL,1,'2025-12-30 15:09:31','2026-01-06 11:02:39');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `inventory_item_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipes_product_id_inventory_item_id_unique` (`product_id`,`inventory_item_id`),
  KEY `recipes_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `recipes_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recipes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
INSERT INTO `recipes` VALUES (1,1,1,25.00,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(2,1,3,1.00,'2025-12-30 15:09:31','2025-12-30 15:09:31');
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'store_name','Dimsum Sarang','2026-01-04 18:43:37','2026-01-04 18:53:29'),(2,'store_logo','logos/tmNAETpu3MqFPBwzcedety7CPTePSx2MbiWHp52W.png','2026-01-04 18:43:37','2026-01-06 13:48:00'),(3,'store_address',NULL,'2026-01-04 18:43:37','2026-01-04 18:43:37'),(4,'store_phone','085799410169','2026-01-04 18:43:37','2026-02-06 11:39:39'),(5,'store_favicon','favicons/jQqFQJybENnQh3rs68GscwwnEmL0gJXrOvhz268p.png','2026-01-06 10:18:22','2026-01-06 10:23:18'),(6,'mail_host','smtp.gmail.com',NULL,'2026-02-10 10:06:33'),(7,'mail_port','587',NULL,'2026-02-10 13:14:43'),(8,'mail_username','dinsum.sarang@gmail.com',NULL,'2026-02-10 13:12:09'),(9,'mail_password','oooyotlkqfwveajj',NULL,'2026-02-10 13:19:16'),(10,'mail_encryption','tls',NULL,'2026-02-10 13:14:43'),(11,'mail_from_address','dinsum.sarang@gmail.com',NULL,'2026-02-10 13:12:09'),(12,'mail_from_name','Dimsum Sarang',NULL,'2026-02-10 10:06:33');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tables`
--

DROP TABLE IF EXISTS `tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('available','occupied','reserved','finishing') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `capacity` int NOT NULL DEFAULT '4',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tables_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tables`
--

LOCK TABLES `tables` WRITE;
/*!40000 ALTER TABLE `tables` DISABLE KEYS */;
INSERT INTO `tables` VALUES (1,'1','DS-Table1','available',4,'2026-02-05 01:19:46','2026-02-11 11:49:04'),(3,'2','ds-table2','available',4,'2026-02-05 07:14:14','2026-02-05 07:14:14');
/*!40000 ALTER TABLE `tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_items`
--

DROP TABLE IF EXISTS `transaction_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `item_type` enum('product','package') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `package_id` bigint unsigned DEFAULT NULL,
  `package_combination_id` bigint unsigned DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` int NOT NULL,
  `subtotal` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_items_transaction_id_foreign` (`transaction_id`),
  KEY `transaction_items_product_id_foreign` (`product_id`),
  KEY `transaction_items_package_id_foreign` (`package_id`),
  KEY `transaction_items_package_combination_id_foreign` (`package_combination_id`),
  CONSTRAINT `transaction_items_package_combination_id_foreign` FOREIGN KEY (`package_combination_id`) REFERENCES `package_combinations` (`id`),
  CONSTRAINT `transaction_items_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`),
  CONSTRAINT `transaction_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `transaction_items_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=186 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_items`
--

LOCK TABLES `transaction_items` WRITE;
/*!40000 ALTER TABLE `transaction_items` DISABLE KEYS */;
INSERT INTO `transaction_items` VALUES (94,37,'package',NULL,6,6,1,26000,26000,'2026-01-04 18:33:24','2026-01-04 18:33:24'),(95,37,'package',NULL,4,2,1,28000,28000,'2026-01-04 18:33:24','2026-01-04 18:33:24'),(96,35,'package',NULL,7,8,1,15000,15000,'2026-01-06 11:02:39','2026-01-06 11:02:39'),(97,35,'package',NULL,6,6,1,26000,26000,'2026-01-06 11:02:39','2026-01-06 11:02:39'),(98,35,'package',NULL,6,6,1,26000,26000,'2026-01-06 11:02:39','2026-01-06 11:02:39'),(99,35,'package',NULL,4,3,1,29000,29000,'2026-01-06 11:02:39','2026-01-06 11:02:39'),(100,35,'package',NULL,4,3,1,29000,29000,'2026-01-06 11:02:39','2026-01-06 11:02:39'),(101,35,'package',NULL,6,6,1,26000,26000,'2026-01-06 11:02:39','2026-01-06 11:02:39'),(102,35,'package',NULL,6,6,1,26000,26000,'2026-01-06 11:02:39','2026-01-06 11:02:39'),(103,35,'package',NULL,4,4,1,29000,29000,'2026-01-06 11:02:39','2026-01-06 11:02:39'),(104,38,'package',NULL,6,6,1,32000,32000,'2026-01-06 11:39:32','2026-01-06 11:39:32'),(105,39,'package',NULL,6,6,1,32000,32000,'2026-01-06 11:41:31','2026-01-06 11:41:31'),(106,40,'package',NULL,6,6,1,32000,32000,'2026-01-06 11:45:32','2026-01-06 11:45:32'),(107,41,'package',NULL,6,6,1,32000,32000,'2026-01-06 11:49:48','2026-01-06 11:49:48'),(108,42,'package',NULL,4,2,1,34000,34000,'2026-01-06 12:02:36','2026-01-06 12:02:36'),(109,43,'package',NULL,6,6,1,32000,32000,'2026-01-06 12:06:09','2026-01-06 12:06:09'),(110,44,'package',NULL,4,2,1,34000,34000,'2026-01-06 12:13:56','2026-01-06 12:13:56'),(111,45,'package',NULL,4,1,1,34000,34000,'2026-01-06 12:17:36','2026-01-06 12:17:36'),(112,46,'package',NULL,6,6,1,28000,28000,'2026-01-06 15:20:04','2026-01-06 15:20:04'),(113,47,'package',NULL,6,6,1,28000,28000,'2026-01-06 15:25:22','2026-01-06 15:25:22'),(114,48,'package',NULL,6,6,1,28000,28000,'2026-01-06 15:25:40','2026-01-06 15:25:40'),(115,49,'package',NULL,6,6,1,32000,32000,'2026-01-06 15:26:11','2026-01-06 15:26:11'),(116,50,'package',NULL,4,1,1,34000,34000,'2026-01-06 15:34:13','2026-01-06 15:34:13'),(117,51,'package',NULL,6,6,1,28000,28000,'2026-01-06 15:35:35','2026-01-06 15:35:35'),(118,52,'package',NULL,4,1,1,34000,34000,'2026-01-06 15:36:24','2026-01-06 15:36:24'),(123,55,'package',NULL,6,6,1,28000,28000,'2026-01-07 07:24:42','2026-01-07 07:24:42'),(124,55,'package',NULL,6,6,1,28000,28000,'2026-01-07 07:24:42','2026-01-07 07:24:42'),(125,56,'package',NULL,6,6,1,28000,28000,'2026-01-07 07:55:57','2026-01-07 07:55:57'),(126,57,'package',NULL,6,6,1,28000,28000,'2026-01-07 08:09:14','2026-01-07 08:09:14'),(131,60,'package',NULL,6,6,3,28000,84000,'2026-01-15 04:51:20','2026-01-15 04:51:20'),(132,60,'package',NULL,6,11,3,31000,93000,'2026-01-15 04:51:20','2026-01-15 04:51:20'),(133,61,'package',NULL,6,6,1,32000,32000,'2026-01-16 07:54:12','2026-01-16 07:54:12'),(134,62,'package',NULL,4,2,1,34000,34000,'2026-01-16 08:07:36','2026-01-16 08:07:36'),(135,62,'package',NULL,9,19,1,35000,35000,'2026-01-16 08:07:36','2026-01-16 08:07:36'),(136,62,'package',NULL,2,21,1,19000,19000,'2026-01-16 08:07:36','2026-01-16 08:07:36'),(137,62,'package',NULL,4,4,1,35000,35000,'2026-01-16 08:07:36','2026-01-16 08:07:36'),(138,54,'package',NULL,6,6,1,28000,28000,'2026-01-19 01:30:04','2026-01-19 01:30:04'),(139,54,'package',NULL,6,6,1,28000,28000,'2026-01-19 01:30:04','2026-01-19 01:30:04'),(140,53,'package',NULL,6,6,1,28000,28000,'2026-01-19 01:30:38','2026-01-19 01:30:38'),(141,53,'package',NULL,6,6,1,28000,28000,'2026-01-19 01:30:38','2026-01-19 01:30:38'),(142,63,'package',NULL,6,6,1,32000,32000,'2026-01-19 01:56:48','2026-01-19 01:56:48'),(146,64,'package',NULL,5,28,1,38000,38000,'2026-01-22 19:30:49','2026-01-22 19:30:49'),(147,64,'package',NULL,6,11,1,35000,35000,'2026-01-22 19:30:49','2026-01-22 19:30:49'),(148,64,'package',NULL,6,10,1,34000,34000,'2026-01-22 19:30:49','2026-01-22 19:30:49'),(149,64,'package',NULL,6,6,1,32000,32000,'2026-01-22 19:30:49','2026-01-22 19:30:49'),(150,64,'package',NULL,9,19,1,35000,35000,'2026-01-22 19:30:49','2026-01-22 19:30:49'),(151,64,'package',NULL,2,21,1,19000,19000,'2026-01-22 19:30:49','2026-01-22 19:30:49'),(152,64,'package',NULL,2,7,1,16000,16000,'2026-01-22 19:30:49','2026-01-22 19:30:49'),(153,65,'package',NULL,4,2,1,34000,34000,'2026-01-22 19:38:45','2026-01-22 19:38:45'),(155,67,'package',NULL,9,19,2,28000,56000,'2026-01-24 03:10:50','2026-01-24 03:10:50'),(156,66,'package',NULL,9,19,2,28000,56000,'2026-01-24 03:12:07','2026-01-24 03:12:07'),(157,68,'package',NULL,5,28,1,38000,38000,'2026-01-24 16:25:53','2026-01-24 16:25:53'),(158,69,'package',NULL,5,28,1,38000,38000,'2026-01-24 16:29:54','2026-01-24 16:29:54'),(159,70,'package',NULL,6,14,1,30000,30000,'2026-01-24 16:32:56','2026-01-24 16:32:56'),(160,71,'package',NULL,6,14,1,30000,30000,'2026-01-24 16:34:15','2026-01-24 16:34:15'),(172,85,'package',NULL,6,6,1,32000,32000,'2026-02-05 16:13:27','2026-02-05 16:13:27'),(175,87,'package',NULL,6,14,1,30000,30000,'2026-02-06 11:20:15','2026-02-06 11:20:15'),(178,89,'package',NULL,9,19,1,28000,28000,'2026-02-09 13:29:46','2026-02-09 13:29:46'),(185,95,'product',8,NULL,NULL,1,6000,6000,'2026-02-12 07:47:30','2026-02-12 07:47:30');
/*!40000 ALTER TABLE `transaction_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_number` int DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `table_id` bigint unsigned DEFAULT NULL,
  `order_type` enum('instant','scheduled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `subtotal` int NOT NULL,
  `discount` int NOT NULL DEFAULT '0',
  `total` int NOT NULL,
  `paid_amount` int NOT NULL DEFAULT '0',
  `change_amount` int NOT NULL DEFAULT '0',
  `dp_amount` int NOT NULL DEFAULT '0',
  `remaining_amount` int NOT NULL DEFAULT '0',
  `payment_status` enum('unpaid','dp','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `dp_paid_at` timestamp NULL DEFAULT NULL,
  `fully_paid_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payment_method_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_invoice_number_unique` (`invoice_number`),
  KEY `transactions_user_id_foreign` (`user_id`),
  KEY `transactions_customer_id_foreign` (`customer_id`),
  KEY `transactions_payment_method_id_foreign` (`payment_method_id`),
  KEY `transactions_table_id_foreign` (`table_id`),
  KEY `transactions_customer_phone_index` (`customer_phone`),
  CONSTRAINT `transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_table_id_foreign` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (35,'DS0501260101',NULL,1,6,NULL,NULL,NULL,'instant',NULL,NULL,206000,0,206000,206000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-04 18:01:30','2026-01-06 11:02:39',NULL),(37,'DS0501260128',NULL,1,6,NULL,NULL,NULL,'instant',NULL,NULL,54000,0,54000,0,0,0,0,'unpaid',NULL,NULL,'pending',NULL,'2026-01-04 18:28:48','2026-01-04 18:33:24',NULL),(38,'DS0601261839',NULL,1,8,NULL,NULL,NULL,'instant',NULL,NULL,32000,0,32000,32000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 11:39:32','2026-01-06 11:39:32',NULL),(39,'DS0601261841',NULL,1,8,NULL,NULL,NULL,'instant',NULL,NULL,32000,0,32000,32000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 11:41:31','2026-01-06 11:41:31',NULL),(40,'DS0601261845',NULL,1,8,NULL,NULL,NULL,'instant',NULL,NULL,32000,0,32000,32000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 11:45:32','2026-01-06 11:45:33',NULL),(41,'DS0601261849',NULL,1,8,NULL,NULL,NULL,'instant',NULL,NULL,32000,0,32000,32000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 11:49:48','2026-01-06 11:49:48',NULL),(42,'DS0601261902',NULL,1,8,NULL,NULL,NULL,'instant',NULL,NULL,34000,0,34000,34000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 12:02:36','2026-01-06 12:02:36',NULL),(43,'DS0601261906',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,32000,0,32000,32000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 12:06:09','2026-01-06 12:06:10',NULL),(44,'DS0601261913',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,34000,0,34000,34000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 12:13:56','2026-01-06 12:13:56',NULL),(45,'DS0601261917',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,34000,0,34000,34000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 12:17:36','2026-01-06 12:17:36',NULL),(46,'DS0601262220',NULL,1,3,NULL,NULL,NULL,'instant',NULL,NULL,28000,0,28000,28000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 15:20:04','2026-01-06 15:20:04',NULL),(47,'DS0601262225',NULL,1,3,NULL,NULL,NULL,'instant',NULL,NULL,28000,0,28000,28000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 15:25:22','2026-01-06 15:25:22',NULL),(48,'DS060126222540',NULL,1,3,NULL,NULL,NULL,'instant',NULL,NULL,28000,0,28000,28000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 15:25:40','2026-01-06 15:25:40',NULL),(49,'DS0601262226',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,32000,0,32000,32000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 15:26:11','2026-01-06 15:26:11',NULL),(50,'DS0601262234',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,34000,0,34000,34000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 15:34:13','2026-01-06 15:34:13',NULL),(51,'DS0601262235',NULL,1,8,NULL,NULL,NULL,'instant',NULL,NULL,28000,0,28000,28000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 15:35:35','2026-01-06 15:35:35',NULL),(52,'DS0601262236',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,34000,0,34000,34000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-06 15:36:24','2026-01-06 15:36:24',1),(53,'DS0701261420',NULL,1,4,NULL,NULL,NULL,'instant',NULL,NULL,56000,0,56000,56000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-07 07:20:23','2026-01-19 01:30:39',1),(54,'DS070126142031',NULL,1,4,NULL,NULL,NULL,'instant',NULL,NULL,56000,0,56000,56000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-07 07:20:31','2026-01-19 01:30:04',1),(55,'DS0701261424',NULL,1,4,NULL,NULL,NULL,'instant',NULL,NULL,56000,0,56000,56000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-07 07:24:42','2026-01-07 07:24:42',2),(56,'DS0701261455',NULL,1,4,NULL,NULL,NULL,'instant',NULL,NULL,28000,0,28000,28000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-07 07:55:57','2026-01-07 07:55:57',2),(57,'DS0701261509',NULL,1,8,NULL,NULL,NULL,'instant',NULL,NULL,28000,0,28000,28000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-07 08:09:14','2026-01-07 08:09:14',1),(60,'DS1401262153',NULL,1,9,NULL,NULL,NULL,'scheduled','2026-01-14','10:00:00',177000,0,177000,177000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-14 14:53:14','2026-01-15 04:51:20',1),(61,'DS1601261454',NULL,1,11,NULL,NULL,NULL,'instant',NULL,NULL,32000,0,32000,32000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-16 07:54:12','2026-01-16 07:54:12',1),(62,'DS1601261507',NULL,1,11,NULL,NULL,NULL,'instant',NULL,NULL,123000,0,123000,123000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-16 08:07:36','2026-01-16 08:07:36',1),(63,'DS1901260856',NULL,1,11,NULL,NULL,NULL,'instant',NULL,NULL,32000,0,32000,32000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-19 01:56:48','2026-01-19 01:56:48',1),(64,'DS2301260226',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,209000,0,209000,209000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-22 19:26:10','2026-01-22 19:30:49',1),(65,'DS2301260238',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,34000,0,34000,0,0,0,0,'unpaid',NULL,NULL,'pending',NULL,'2026-01-22 19:38:45','2026-01-22 19:38:45',NULL),(66,'DS2401261010',NULL,1,12,NULL,NULL,NULL,'instant',NULL,NULL,56000,0,56000,56000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-24 03:10:36','2026-01-24 03:12:07',1),(67,'DS240126101050',NULL,1,12,NULL,NULL,NULL,'instant',NULL,NULL,56000,0,56000,0,0,0,0,'unpaid',NULL,NULL,'pending',NULL,'2026-01-24 03:10:50','2026-01-24 03:10:50',NULL),(68,'DS2401262325',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,38000,0,38000,0,0,0,0,'unpaid',NULL,NULL,'pending',NULL,'2026-01-24 16:25:53','2026-01-24 16:25:53',NULL),(69,'DS2401262329',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,38000,0,38000,0,0,0,0,'unpaid',NULL,NULL,'pending',NULL,'2026-01-24 16:29:54','2026-01-24 16:29:54',NULL),(70,'DS2401262332',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,30000,0,30000,30000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-24 16:32:56','2026-01-24 16:32:57',1),(71,'DS2401262334',NULL,1,NULL,NULL,NULL,NULL,'instant',NULL,NULL,30000,0,30000,30000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-01-24 16:34:15','2026-01-24 16:34:16',1),(85,'DS0502262313',NULL,NULL,NULL,NULL,NULL,NULL,'instant',NULL,NULL,32000,0,32000,0,0,0,0,'unpaid',NULL,NULL,'pending',NULL,'2026-02-05 16:13:27','2026-02-05 16:13:27',NULL),(87,'DS0602261641',NULL,NULL,10,NULL,NULL,NULL,'instant',NULL,NULL,30000,0,30000,30000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-02-06 09:41:06','2026-02-06 11:20:15',1),(89,'DS0902262029',NULL,NULL,12,NULL,NULL,NULL,'instant',NULL,NULL,28000,0,28000,28000,0,0,0,'unpaid',NULL,NULL,'completed',NULL,'2026-02-09 13:29:46','2026-02-09 13:29:46',1),(95,'DS1202261447',NULL,NULL,10,NULL,NULL,NULL,'instant',NULL,NULL,6000,0,6000,0,0,0,0,'unpaid',NULL,NULL,'pending',NULL,'2026-02-12 07:47:30','2026-02-12 07:47:30',NULL);
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('owner','kasir','gudang') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kasir',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Keenan','owner@dimsum.com',NULL,'$2y$12$Jv4U2WPq77B4f/6NohDczuk6p5brP96oZd5xiUUjxvUSWcBS.3agK','owner',1,NULL,'2025-12-30 15:09:31','2026-01-06 09:53:40'),(2,'Kasir 1','kasir@dimsum.com',NULL,'$2y$12$ntU/.uJn6lVZwshKK7CQwe1gcU4DuvRr1WKasZglOYjSFn8srHqsG','kasir',1,NULL,'2025-12-30 15:09:31','2025-12-30 15:09:31'),(3,'Staff Gudang','gudang@dimsum.com',NULL,'$2y$12$8KQqxRR0kdW1sxoWBPmGn.IPx6B6eS4FPKYqCsrs7KR1CxCkgbyBq','gudang',1,NULL,'2025-12-30 15:09:31','2025-12-30 15:09:31');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'dimsum_pos'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-21 19:30:33
