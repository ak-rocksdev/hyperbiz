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