CREATE DATABASE `adoptify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `adoptify`;

CREATE TABLE `user` (
    `user_id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `gender` ENUM('M','F') NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `country_code` CHAR(2) NOT NULL,
    `fcm_token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_disabled` TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE `pet` (
    `pet_id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `type` ENUM('cat', 'dog'),
    `user_id` INT UNSIGNED NOT NULL,
    `breed` VARCHAR(50) NOT NULL,
    `gender` ENUM('M','F') NOT NULL,
    `image_count` TINYINT NOT NULL DEFAULT 0,
    `dob` DATE NOT NULL,
    `description` VARCHAR(2000) NOT NULL,
    `country_code` CHAR(2) NOT NULL,
    `contact_name` VARCHAR(50) NOT NULL,
    `contact_phone` VARCHAR(30) NOT NULL,
    `latitude` DECIMAL(22, 20) NOT NULL,
    `longitude` DECIMAL(23, 20) NOT NULL,
    `area_level_1` VARCHAR(100) NOT NULL,
    `area_level_2` VARCHAR(100),
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expiry_date` DATE NOT NULL,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE `pet_report` (
    `report_id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    `pet_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_resolved` TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE `country` (
    `country_code` CHAR(2) PRIMARY KEY
);

ALTER TABLE `user` ADD FOREIGN KEY (`country_code`) REFERENCES `country`(`country_code`);

ALTER TABLE `pet` ADD FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`);
ALTER TABLE `pet` ADD FOREIGN KEY (`country_code`) REFERENCES `country`(`country_code`);

ALTER TABLE `pet_report` ADD FOREIGN KEY (`pet_id`) REFERENCES `pet`(`pet_id`);
ALTER TABLE `pet_report` ADD FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`);

INSERT INTO `country`(`country_code`) VALUES ('AU');
INSERT INTO `country`(`country_code`) VALUES ('CA');
INSERT INTO `country`(`country_code`) VALUES ('CN');
INSERT INTO `country`(`country_code`) VALUES ('GB');
INSERT INTO `country`(`country_code`) VALUES ('HK');
INSERT INTO `country`(`country_code`) VALUES ('JP');
INSERT INTO `country`(`country_code`) VALUES ('KR');
INSERT INTO `country`(`country_code`) VALUES ('MO');
INSERT INTO `country`(`country_code`) VALUES ('MY');
INSERT INTO `country`(`country_code`) VALUES ('NZ');
INSERT INTO `country`(`country_code`) VALUES ('SG');
INSERT INTO `country`(`country_code`) VALUES ('TW');
INSERT INTO `country`(`country_code`) VALUES ('US');