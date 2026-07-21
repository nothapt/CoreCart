-- ==================================================
-- CoreCart Database Schema
-- ==================================================
-- Prefix: cc_ (CoreCart)
-- Engine: InnoDB for foreign key support
-- Charset: utf8mb4 for full Unicode support
-- ==================================================

-- 1. Products (hard data only: prices, stock, status)
CREATE TABLE IF NOT EXISTS `cc_product` (
    `product_id` INT AUTO_INCREMENT PRIMARY KEY,
    `model` VARCHAR(64) NOT NULL,
    `sku` VARCHAR(64) DEFAULT NULL,
    `price` DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
    `quantity` INT NOT NULL DEFAULT '0',
    `image` VARCHAR(255) DEFAULT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT '1',
    `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_date_added` (`date_added`),
    INDEX `idx_price` (`price`),
    INDEX `idx_sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Product descriptions (for multilingual: names, texts)
CREATE TABLE IF NOT EXISTS `cc_product_description` (
    `product_id` INT NOT NULL,
    `language_id` INT NOT NULL DEFAULT '1',
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    PRIMARY KEY (`product_id`, `language_id`),
    INDEX `idx_name` (`name`),
    FOREIGN KEY (`product_id`) REFERENCES `cc_product`(`product_id`) ON DELETE CASCADE,
    FOREIGN KEY (`language_id`) REFERENCES `cc_language`(`language_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Categories
CREATE TABLE IF NOT EXISTS `cc_category` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NOT NULL DEFAULT '0',
    `status` TINYINT(1) NOT NULL DEFAULT '1',
    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Category descriptions (multilingual names)
CREATE TABLE IF NOT EXISTS `cc_category_description` (
    `category_id` INT NOT NULL,
    `language_id` INT NOT NULL DEFAULT '1',
    `name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`category_id`, `language_id`),
    INDEX `idx_name` (`name`),
    FOREIGN KEY (`category_id`) REFERENCES `cc_category`(`category_id`) ON DELETE CASCADE,
    FOREIGN KEY (`language_id`) REFERENCES `cc_language`(`language_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Product-to-category relation (many-to-many)
CREATE TABLE IF NOT EXISTS `cc_product_to_category` (
    `product_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    PRIMARY KEY (`product_id`, `category_id`),
    INDEX `idx_category` (`category_id`),
    FOREIGN KEY (`product_id`) REFERENCES `cc_product`(`product_id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `cc_category`(`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Languages (must be before tables that reference it)
CREATE TABLE IF NOT EXISTS `cc_language` (
    `language_id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(8) NOT NULL,
    `name` VARCHAR(64) NOT NULL,
    `locale` VARCHAR(128) NOT NULL DEFAULT 'en_US.UTF-8'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Users (admin + customers)
CREATE TABLE IF NOT EXISTS `cc_user` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(64) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `group_id` INT UNSIGNED NOT NULL DEFAULT '1',
    `status` TINYINT(1) NOT NULL DEFAULT '1',
    `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Orders
CREATE TABLE IF NOT EXISTS `cc_order` (
    `order_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL DEFAULT '0',
    `status` TINYINT(1) NOT NULL DEFAULT '0',
    `total` DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
    `comment` TEXT,
    `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Settings (key-value store for configuration)
CREATE TABLE IF NOT EXISTS `cc_setting` (
    `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(128) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `setting_group` VARCHAR(64) NOT NULL DEFAULT 'config'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================
-- Seed data: default language (English + Russian)
-- ==================================================
INSERT IGNORE INTO `cc_language` (`language_id`, `code`, `name`, `locale`) VALUES
(1, 'en', 'English', 'en_US.UTF-8'),
(2, 'ru', 'Russian', 'ru_RU.UTF-8');
