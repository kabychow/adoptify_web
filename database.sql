CREATE DATABASE `adoptify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `adoptify`;

CREATE TABLE `country` (
    `code` CHAR(2) PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL
);

CREATE TABLE `user` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `country_code` CHAR(2) NOT NULL,
    `fcm_token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_disabled` TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE `dog` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `breed` VARCHAR(50) NOT NULL,
    `gender` ENUM('M','F') NOT NULL,
    `dob` DATE NOT NULL,
    `description` TEXT NOT NULL,
    `country_code` CHAR(2) NOT NULL,
    `contact_name` VARCHAR(50) NOT NULL,
    `contact_phone` VARCHAR(50) NOT NULL,
    `contact_latitude` DECIMAL(10, 8) NOT NULL,
    `contact_longitude` DECIMAL(11, 8) NOT NULL,
    `contact_area_level_1` VARCHAR(100) NOT NULL,
    `contact_area_level_2` VARCHAR(100) NOT NULL,
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expiry_date` DATE NOT NULL,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE `dog_image` (
    `dog_id` INT UNSIGNED NOT NULL,
    `path` VARCHAR(512) UNIQUE NOT NULL
);

CREATE TABLE `dog_report` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `dog_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_resolved` TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE `cat` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `breed` VARCHAR(50) NOT NULL,
    `gender` ENUM('M','F') NOT NULL,
    `dob` DATE NOT NULL,
    `description` TEXT NOT NULL,
    `country_code` CHAR(2) NOT NULL,
    `contact_name` VARCHAR(50) NOT NULL,
    `contact_phone` VARCHAR(50) NOT NULL,
    `contact_latitude` DECIMAL(10, 8) NOT NULL,
    `contact_longitude` DECIMAL(11, 8) NOT NULL,
    `contact_area_level_1` VARCHAR(100) NOT NULL,
    `contact_area_level_2` VARCHAR(100) NOT NULL,
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expiry_date` DATE NOT NULL,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE `cat_image` (
    `cat_id` INT UNSIGNED NOT NULL,
    `path` VARCHAR(512) UNIQUE NOT NULL
);

CREATE TABLE `cat_report` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `cat_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_resolved` TINYINT(1) NOT NULL DEFAULT 0
);

ALTER TABLE `user` ADD FOREIGN KEY (`country_code`) REFERENCES `country`(`code`);

ALTER TABLE `dog` ADD FOREIGN KEY (`user_id`) REFERENCES `user`(`id`);
ALTER TABLE `dog` ADD FOREIGN KEY (`country_code`) REFERENCES `country`(`code`);

ALTER TABLE `dog_image` ADD FOREIGN KEY (`dog_id`) REFERENCES `dog`(`id`);

ALTER TABLE `dog_report` ADD FOREIGN KEY (`dog_id`) REFERENCES `dog`(`id`);
ALTER TABLE `dog_report` ADD FOREIGN KEY (`user_id`) REFERENCES `user`(`id`);

ALTER TABLE `cat` ADD FOREIGN KEY (`user_id`) REFERENCES `user`(`id`);
ALTER TABLE `cat` ADD FOREIGN KEY (`country_code`) REFERENCES `country`(`code`);

ALTER TABLE `cat_image` ADD FOREIGN KEY (`cat_id`) REFERENCES `cat`(`id`);

ALTER TABLE `cat_report` ADD FOREIGN KEY (`cat_id`) REFERENCES `cat`(`id`);
ALTER TABLE `cat_report` ADD FOREIGN KEY (`user_id`) REFERENCES `user`(`id`);