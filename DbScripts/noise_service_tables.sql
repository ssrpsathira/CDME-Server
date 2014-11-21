CREATE TABLE IF NOT EXISTS `cdme_noise_data`(
`id` BIGINT NOT NULL AUTO_INCREMENT, 
`location_id` BIGINT NOT NULL, 
`noise_level` VARCHAR(100) NOT NULL, 
`date_time` VARCHAR(100) NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY(`id`))
ENGINE = INNODB DEFAULT CHARSET=utf8;