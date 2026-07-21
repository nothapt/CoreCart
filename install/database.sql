-- ==================================================
-- CoreCart Database Schema
-- ==================================================
-- Prefix: cc_ (CoreCart)
-- Engine: InnoDB for foreign key support
-- Charset: utf8mb4 for full Unicode support
-- ==================================================

-- 1. Languages (must be first, referenced by other tables)
CREATE TABLE IF NOT EXISTS `cc_language` (
    `language_id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(8) NOT NULL,
    `name` VARCHAR(64) NOT NULL,
    `locale` VARCHAR(128) NOT NULL DEFAULT 'en_US.UTF-8'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Admin users
CREATE TABLE IF NOT EXISTS `cc_admin_user` (
    `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(64) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT '1',
    `last_login` DATETIME DEFAULT NULL,
    `last_ip` VARCHAR(45) DEFAULT NULL,
    `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Login attempts (for rate limiting)
CREATE TABLE IF NOT EXISTS `cc_login_attempt` (
    `attempt_id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `login` VARCHAR(255) NOT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT '0',
    `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip` (`ip_address`),
    INDEX `idx_login` (`login`),
    INDEX `idx_date` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Products (hard data only: prices, stock, status)
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

-- 5. Product descriptions (multilingual)
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

-- 6. Categories
CREATE TABLE IF NOT EXISTS `cc_category` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NOT NULL DEFAULT '0',
    `status` TINYINT(1) NOT NULL DEFAULT '1',
    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Category descriptions
CREATE TABLE IF NOT EXISTS `cc_category_description` (
    `category_id` INT NOT NULL,
    `language_id` INT NOT NULL DEFAULT '1',
    `name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`category_id`, `language_id`),
    INDEX `idx_name` (`name`),
    FOREIGN KEY (`category_id`) REFERENCES `cc_category`(`category_id`) ON DELETE CASCADE,
    FOREIGN KEY (`language_id`) REFERENCES `cc_language`(`language_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Product-to-category (many-to-many)
CREATE TABLE IF NOT EXISTS `cc_product_to_category` (
    `product_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    PRIMARY KEY (`product_id`, `category_id`),
    INDEX `idx_category` (`category_id`),
    FOREIGN KEY (`product_id`) REFERENCES `cc_product`(`product_id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `cc_category`(`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Customers
CREATE TABLE IF NOT EXISTS `cc_customer` (
    `customer_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(64) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT '1',
    `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Customer addresses
CREATE TABLE IF NOT EXISTS `cc_address` (
    `address_id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `firstname` VARCHAR(128) NOT NULL,
    `lastname` VARCHAR(128) NOT NULL,
    `address_1` VARCHAR(255) NOT NULL,
    `address_2` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(128) NOT NULL,
    `postcode` VARCHAR(10) NOT NULL,
    `country` VARCHAR(64) NOT NULL,
    `zone` VARCHAR(128) NOT NULL,
    `default` TINYINT(1) NOT NULL DEFAULT '0',
    FOREIGN KEY (`customer_id`) REFERENCES `cc_customer`(`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Orders
CREATE TABLE IF NOT EXISTS `cc_order` (
    `order_id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT DEFAULT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT '0',
    `total` DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
    `comment` TEXT,
    `customer_email` VARCHAR(255) DEFAULT NULL,
    `customer_phone` VARCHAR(32) DEFAULT NULL,
    `shipping_firstname` VARCHAR(128) DEFAULT NULL,
    `shipping_lastname` VARCHAR(128) DEFAULT NULL,
    `shipping_address_1` VARCHAR(255) DEFAULT NULL,
    `shipping_address_2` VARCHAR(255) DEFAULT NULL,
    `shipping_city` VARCHAR(128) DEFAULT NULL,
    `shipping_postcode` VARCHAR(10) DEFAULT NULL,
    `shipping_country` VARCHAR(64) DEFAULT NULL,
    `shipping_zone` VARCHAR(128) DEFAULT NULL,
    `currency_code` VARCHAR(3) DEFAULT 'USD',
    `currency_value` DECIMAL(15,8) DEFAULT 1.00000000,
    `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_date_added` (`date_added`),
    FOREIGN KEY (`customer_id`) REFERENCES `cc_customer`(`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Order products
CREATE TABLE IF NOT EXISTS `cc_order_product` (
    `order_product_id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `quantity` INT NOT NULL DEFAULT '0',
    `price` DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
    FOREIGN KEY (`order_id`) REFERENCES `cc_order`(`order_id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `cc_product`(`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Cart
CREATE TABLE IF NOT EXISTS `cc_cart` (
    `cart_id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT DEFAULT NULL,
    `session_id` VARCHAR(128) DEFAULT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT '1',
    `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_session_product` (`session_id`, `product_id`),
    UNIQUE KEY `uq_customer_product` (`customer_id`, `product_id`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_session` (`session_id`),
    FOREIGN KEY (`customer_id`) REFERENCES `cc_customer`(`customer_id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `cc_product`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Settings
CREATE TABLE IF NOT EXISTS `cc_setting` (
    `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(128) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `setting_group` VARCHAR(64) NOT NULL DEFAULT 'config'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================
-- Seed data
-- ==================================================
INSERT IGNORE INTO `cc_language` (`language_id`, `code`, `name`, `locale`) VALUES
(1, 'en', 'English', 'en_US.UTF-8'),
(2, 'ru', 'Russian', 'ru_RU.UTF-8');

-- Default admin user (password: admin123)
INSERT IGNORE INTO `cc_admin_user` (`admin_id`, `username`, `email`, `password`, `status`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$YourHashedPasswordHere', 1);
