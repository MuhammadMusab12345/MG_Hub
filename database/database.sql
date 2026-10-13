-- ==========================================================
-- Mehtab Garments — Complete Database Schema
-- Compatible with MySQL 8.0+ / MariaDB 10.4+ / phpMyAdmin
-- Character Set: utf8mb4, Engine: InnoDB
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `mehtab_garments` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mehtab_garments`;

-- ----------------------------------------------------------
-- 1. Table structure for `users`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Table structure for `products`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `share_token` CHAR(32) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `design_number` VARCHAR(100) DEFAULT NULL,
  `retail_price` DECIMAL(12,2) DEFAULT NULL,
  `fabric_price` DECIMAL(12,2) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `private_details` TEXT DEFAULT NULL,
  `fabric1_name` VARCHAR(150) DEFAULT NULL,
  `fabric1_details` TEXT DEFAULT NULL,
  `fabric1_cost` DECIMAL(12,2) DEFAULT NULL,
  `fabric2_name` VARCHAR(150) DEFAULT NULL,
  `fabric2_details` TEXT DEFAULT NULL,
  `fabric2_cost` DECIMAL(12,2) DEFAULT NULL,
  `inner_back_cost` DECIMAL(12,2) DEFAULT NULL,
  `embroidery_cost` DECIMAL(12,2) DEFAULT NULL,
  `neck_rate` DECIMAL(12,2) DEFAULT NULL,
  `cutwork_cost` DECIMAL(12,2) DEFAULT NULL,
  `cutting_cost` DECIMAL(12,2) DEFAULT NULL,
  `stitching_cost` DECIMAL(12,2) DEFAULT NULL,
  `press_cost` DECIMAL(12,2) DEFAULT NULL,
  `elastic_cost` DECIMAL(12,2) DEFAULT NULL,
  `thread_cost` DECIMAL(12,2) DEFAULT NULL,
  `poly_bag_cost` DECIMAL(12,2) DEFAULT NULL,
  `pico_cost` DECIMAL(12,2) DEFAULT NULL,
  `main_image_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_products_share_token` (`share_token`),
  KEY `idx_products_user_id` (`user_id`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_name` (`name`),
  KEY `idx_products_design_number` (`design_number`),
  KEY `idx_products_fabric1_name` (`fabric1_name`),
  KEY `idx_products_fabric2_name` (`fabric2_name`),
  KEY `idx_products_created_at` (`created_at`),
  CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Table structure for `product_images`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_images_product_id` (`product_id`),
  CONSTRAINT `fk_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Table structure for `login_attempts`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_hash` CHAR(64) NOT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_attempted` (`ip_hash`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
