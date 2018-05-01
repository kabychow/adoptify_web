CREATE DATABASE `adoptify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `adoptify`;

CREATE TABLE `user` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `gender` ENUM('M','F') NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `country_code` ENUM('AU', 'CA', 'CN', 'GB', 'HK', 'JP', 'KR', 'MO', 'MY', 'NZ', 'SG', 'TW', 'US') NOT NULL,
    `fcm_token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_disabled` TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE `pet` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `type` ENUM('cat', 'dog'),
    `user_id` INT UNSIGNED NOT NULL,
    `breed` VARCHAR(50) NOT NULL,
    `gender` ENUM('M','F') NOT NULL,
    `image_count` TINYINT NOT NULL DEFAULT 0,
    `dob` DATE NOT NULL,
    `description` VARCHAR(2000) NOT NULL,
    `country_code` ENUM('AU', 'CA', 'CN', 'GB', 'HK', 'JP', 'KR', 'MO', 'MY', 'NZ', 'SG', 'TW', 'US') NOT NULL,
    `contact_name` VARCHAR(50) NOT NULL,
    `contact_phone` VARCHAR(30) NOT NULL,
    `contact_latitude` DECIMAL(10, 8) NOT NULL,
    `contact_longitude` DECIMAL(11, 8) NOT NULL,
    `contact_area_level_1` VARCHAR(100) NOT NULL,
    `contact_area_level_2` VARCHAR(100),
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expiry_date` DATE NOT NULL,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE `pet_report` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `pet_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_resolved` TINYINT(1) NOT NULL DEFAULT 0
);

ALTER TABLE `pet` ADD FOREIGN KEY (`user_id`) REFERENCES `user`(`id`);

ALTER TABLE `pet_report` ADD FOREIGN KEY (`pet_id`) REFERENCES `pet`(`id`);
ALTER TABLE `pet_report` ADD FOREIGN KEY (`user_id`) REFERENCES `user`(`id`);