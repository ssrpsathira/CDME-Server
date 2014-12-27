CREATE TABLE IF NOT EXISTS `cdme_location` (
`id` BIGINT NOT NULL AUTO_INCREMENT,
`longitude` DOUBLE NOT NULL,
`latitude` DOUBLE NOT NULL,
`admin_1_region_id` BIGINT NOT NULL,
`admin_2_region_id` BIGINT NOT NULL,
PRIMARY KEY(`id`))
ENGINE = INNODB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cdme_noise_data`(
`id` BIGINT NOT NULL AUTO_INCREMENT, 
`user_id` BIGINT,
`location_id` BIGINT NOT NULL, 
`noise_level` VARCHAR(100) NOT NULL, 
`date_time` TEXT,
PRIMARY KEY(`id`))
ENGINE = INNODB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cdme_user`(
`id` BIGINT NOT NULL AUTO_INCREMENT, 
`imei` TEXT,
`used_time` TEXT, 
`distance_traveled` TEXT, 
PRIMARY KEY(`id`))
ENGINE = INNODB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cdme_admin_2_region` (
`id` BIGINT NOT NULL AUTO_INCREMENT,
`name` TEXT NOT NULL,
PRIMARY KEY(`id`))
ENGINE = INNODB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cdme_admin_1_region` (
`id` BIGINT NOT NULL AUTO_INCREMENT,
`name` TEXT NOT NULL,
PRIMARY KEY(`id`))
ENGINE = INNODB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cdme_admin_2_region_statistics` (
`id` BIGINT NOT NULL AUTO_INCREMENT,
`region_id` BIGINT NOT NULL,
`mod` TEXT,
`median` TEXT,
`mean` TEXT,
`sd` TEXT,
`date_time` TEXT,
PRIMARY KEY(`id`))
ENGINE = INNODB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `cdme_admin_2_region_kml_templae_values` (
`id` BIGINT NOT NULL AUTO_INCREMENT,
`region_id` BIGINT NOT NULL,
`poligon_opacity` TEXT,
`poligon_color` TEXT,
`region_name` TEXT,
`polygons` LONGTEXT,
PRIMARY KEY(`id`))
ENGINE = INNODB DEFAULT CHARSET=utf8;

ALTER TABLE `cdme_location`
ADD CONSTRAINT FOREIGN KEY (`admin_1_region_id`) REFERENCES `cdme_admin_1_region`(`id`)
ON UPDATE CASCADE
ON DELETE CASCADE;

ALTER TABLE `cdme_location`
ADD CONSTRAINT FOREIGN KEY (`admin_2_region_id`) REFERENCES `cdme_admin_2_region`(`id`)
ON UPDATE CASCADE
ON DELETE CASCADE;

ALTER TABLE `cdme_noise_data`
ADD CONSTRAINT FOREIGN KEY (`location_id`) REFERENCES `cdme_location`(`id`)
ON UPDATE CASCADE
ON DELETE CASCADE;

ALTER TABLE `cdme_noise_data`
ADD CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `cdme_user`(`id`)
ON UPDATE CASCADE
ON DELETE CASCADE;

ALTER TABLE `cdme_admin_2_region_statistics`
ADD CONSTRAINT FOREIGN KEY (`region_id`) REFERENCES `cdme_admin_2_region`(`id`)
ON UPDATE CASCADE
ON DELETE CASCADE;

ALTER TABLE `cdme_admin_2_region_kml_templae_values`
ADD CONSTRAINT FOREIGN KEY (`region_id`) REFERENCES `cdme_admin_2_region`(`id`)
ON UPDATE CASCADE
ON DELETE CASCADE;
