-- Create MySQL script for 'mst_client' table:

-- base columns that are common to all tables:
-- id

-- created_at
-- created_by
-- updated_at
-- updated_by

CREATE TABLE `mst_client` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_name` VARCHAR(255) NOT NULL,
    `mst_address_id` INT NOT NULL,
    `client_phone_number` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `contact_person` VARCHAR(255) NOT NULL,
    `contact_person_phone_number` VARCHAR(255) NOT NULL,
    `mst_client_type_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` VARCHAR(255) NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_by` VARCHAR(255) NOT NULL
);

-- mst_address
CREATE TABLE `mst_address` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `address` VARCHAR(255) NOT NULL,
    `city_id` VARCHAR(255) NOT NULL,
    `city_name` VARCHAR(255) NOT NULL,
    `state_name` VARCHAR(255) NOT NULL,
    `state_id` VARCHAR(255) NULL,
    `country_id` VARCHAR(255) NOT NULL,
    `country_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` VARCHAR(255) NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_by` VARCHAR(255) NOT NULL
);

-- mst_client_type
CREATE TABLE `mst_client_type` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_type` VARCHAR(255) NOT NULL, -- e.g. 'Importir', 'Distibutor', 'Retailer', 'Wholesaler', 'Agent', 'Supplier', 'Manufacturer'
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` VARCHAR(255) NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_by` VARCHAR(255) NOT NULL
);

CREATE TABLE `mst_brands` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` VARCHAR(255) NOT NULL,
    `updated_by` VARCHAR(255) NOT NULL
);

CREATE TABLE `mst_product_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `parent_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` VARCHAR(255) NOT NULL,
    `updated_by` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`parent_id`) REFERENCES `mst_product_categories`(`id`) ON DELETE SET NULL
);

CREATE TABLE `mst_products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `sku` VARCHAR(100) UNIQUE,
    `barcode` VARCHAR(50) UNIQUE,
    `price` DECIMAL(10, 2) DEFAULT 0.00,
    `cost_price` DECIMAL(10, 2) DEFAULT NULL,
    `currency` CHAR(3) DEFAULT 'USD',
    `stock_quantity` INT DEFAULT 0,
    `min_stock_level` INT DEFAULT 0,
    `mst_product_category_id` INT NOT NULL,
    `mst_brand_id` INT DEFAULT NULL,
    `mst_client_id` INT DEFAULT NULL,
    `weight` DECIMAL(10, 2) DEFAULT NULL,
    `dimensions` VARCHAR(100) DEFAULT NULL,
    `image_url` TEXT DEFAULT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` VARCHAR(255) NOT NULL,
    `updated_by` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`mst_product_category_id`) REFERENCES `mst_product_categories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`mst_brand_id`) REFERENCES `mst_brands`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`mst_client_id`) REFERENCES `mst_client`(`id`) ON DELETE SET NULL
);

CREATE TABLE `transaction_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` BIGINT UNSIGNED NULL,
    `action` ENUM('create', 'read', 'update', 'delete') NOT NULL,
    `changed_fields` JSON NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `actor_role` VARCHAR(50) NULL, -- Nullable to account for missing role management
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `action_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `remarks` TEXT NULL,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
