-- 10.09.2012 
ALTER TABLE `wp_redirects`.`urls` ADD COLUMN `description` TEXT NULL AFTER `status`; 
ALTER TABLE `wp_redirects`.`urls` ADD COLUMN `site_category` INT(11) NULL AFTER `description`; 
ALTER TABLE `wp_redirects`.`urls` ADD COLUMN `site_id` INT(11) NULL AFTER `site_category`; 
ALTER TABLE `wp_redirects`.`urls` DROP COLUMN `enter_url`; 
ALTER TABLE `wp_redirects`.`urls` CHANGE `site_category` `site_category` TEXT NULL; 
-- 11.09.2012
ALTER TABLE `wp_redirects`.`urls` ADD COLUMN `wp_post_id` INT(11) NULL AFTER `site_id`; 
-- 14.09.2012
ALTER TABLE `wp_redirects`.`urls` ADD COLUMN `desc_logo` TEXT NULL AFTER `wp_post_id`; 
ALTER TABLE `wp_redirects`.`urls` CHANGE `desc_logo` `desc_logo_url` TEXT CHARSET latin1 COLLATE latin1_swedish_ci NULL, ADD COLUMN `desc_logo` TINYINT NULL AFTER `desc_logo_url`; 
-- 17.09.2012
ALTER TABLE `wp_redirects`.`urls_logs` ADD COLUMN `isp_name` VARCHAR(255) NULL AFTER `user_agent`, ADD COLUMN `country_code` VARCHAR(2) NULL AFTER `isp_name`, ADD COLUMN `start_ip` INT(11) NULL AFTER `country_code`, ADD COLUMN `end_ip` INT(11) NULL AFTER `start_ip`; 
-- 21.09.2012
ALTER TABLE `wp_redirects`.`site` ADD COLUMN `logo_data` TEXT NULL AFTER `wp_pass`; 
ALTER TABLE `wp_redirects`.`urls` ADD COLUMN `param_url` VARCHAR(255) NULL AFTER `desc_logo`; 
-- 26.09.2012
CREATE TABLE `wp_redirects`.`users`( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `email` VARCHAR(255) NOT NULL, `pass` VARCHAR(34) NOT NULL, KEY(`id`) ) ENGINE=MYISAM CHARSET=utf8 COLLATE=utf8_general_ci; 
INSERT INTO `wp_redirects`.`users` (`email`, `pass`) VALUES ('admin', 'ce788b0a7126a31baef65c7f15823eef'); -- pass webuser112233
/*------ 10/2/2012 5:47:11 PM --------*/
ALTER TABLE `urls` ADD INDEX `site_id` (`site_id`);
/*------ 10/2/2012 5:47:16 PM --------*/
ALTER TABLE `urls` ADD INDEX `wp_post_id` (`wp_post_id`);
/*------ 10/3/2012 4:51:01 PM --------*/
ALTER TABLE `users` ADD INDEX `email` (`email`);
-- 10.10.2012
ALTER TABLE `wp_redirects`.`site` ADD COLUMN `email` VARCHAR(255) NULL AFTER `logo_data`; 
-- 23.10.2012
CREATE TABLE `advertiser_category`( `urls_id` INT(11) UNSIGNED NOT NULL, `category_id` INT(11) UNSIGNED NOT NULL, PRIMARY KEY (`urls_id`, `category_id`) );
CREATE TABLE `categories`( `category_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(255), `parent_id` INT(11), `site_id` INT(11), PRIMARY KEY (`category_id`) ); 
ALTER TABLE `categories` CHANGE `category_id` `category_id` INT(11) UNSIGNED NOT NULL;
-- 29.10.2012
ALTER TABLE `urls` ADD COLUMN `published` BOOLEAN DEFAULT TRUE NULL AFTER `param_url`; 
-- 28.11,2012
ALTER TABLE `categories` CHANGE `site_id` `site_id` INT(11) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (`category_id`, `site_id`);
-- 4.12.2012
CREATE TABLE `blacklists`( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(255), `ips_data` TEXT, PRIMARY KEY (`id`) );
-- 6.12.2012
ALTER TABLE `blacklists` ADD COLUMN `type` ENUM('public','private') NULL AFTER `ips_data`;
CREATE TABLE `advertiser_blacklist`( `urls_id` INT(11) UNSIGNED NOT NULL COMMENT 'advertiser id', `blacklist_id` INT(11), PRIMARY KEY (`urls_id`) );
CREATE TABLE `site_blacklist`( `site_id` INT(11) UNSIGNED NOT NULL, `blacklist_id` INT(11), PRIMARY KEY (`site_id`) );
ALTER TABLE `blacklists` CHANGE `type` `type` ENUM('public','private') CHARSET utf8 COLLATE utf8_general_ci DEFAULT 'public' NULL; 
-- 10.12.2012
CREATE TABLE `templates`( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(255), PRIMARY KEY (`id`) ); 
CREATE TABLE `template_isp`( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `template_id` INT UNSIGNED NOT NULL, `isp_data_id` INT UNSIGNED NOT NULL, PRIMARY KEY (`id`) );
CREATE TABLE `isp_data`( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `country` VARCHAR(4) NOT NULL, `isp_data` TEXT, PRIMARY KEY (`id`) );
-- 12.12,2012
CREATE TABLE `advertiser_template`( `urls_id` INT(11) UNSIGNED NOT NULL, `template_id` INT(11) UNSIGNED NOT NULL, PRIMARY KEY (`urls_id`, `template_id`) );
-- 26.12.2012 
ALTER TABLE `urls` ADD COLUMN `featured_post` TINYINT(1) DEFAULT 0 NULL AFTER `published`; 
-- 25.01.2013
ALTER TABLE `blacklists` CHANGE `type` `type` ENUM('public','private','private_tu') CHARSET utf8 COLLATE utf8_general_ci DEFAULT 'public' NULL; 
ALTER TABLE `blacklists` ADD COLUMN `tu_data` TEXT NULL AFTER `type`;
-- 29.01.2013
ALTER TABLE `blacklists` CHANGE `ips_data` `ips_data` MEDIUMTEXT NULL;
ALTER TABLE `urls` ADD COLUMN `categories_tree` TEXT NULL AFTER `featured_post`; 
-- 18.02.2013
ALTER TABLE `site` ADD COLUMN `use_bl` TINYINT(1) DEFAULT 1 NULL COMMENT 'set use or not use black lists for that site' AFTER `email`; 
-- 17.04.2013
ALTER TABLE `urls` ADD COLUMN `exception_url2` VARCHAR(255) NULL AFTER `exception_url`;
-- 23.04.2013
CREATE TABLE `roles`( `id` INT(11) NOT NULL AUTO_INCREMENT, `type` ENUM('advertiser','article','placeholder'), PRIMARY KEY (`id`) ) ENGINE=MYISAM CHARSET=utf8 COLLATE=utf8_general_ci; 
INSERT INTO `roles` (`type`) VALUES ('advertiser'); 
INSERT INTO `roles` (`type`) VALUES ('article'); 
INSERT INTO `roles` (`type`) VALUES ('placeholder'); 
CREATE TABLE `advertiser_roles`( `urls_id` INT(11) UNSIGNED NOT NULL COMMENT 'advertiser_id', `role_id` INT(11), PRIMARY KEY (`urls_id`) ) ENGINE=MYISAM CHARSET=utf8 COLLATE=utf8_general_ci; 
-- set default value for all advertisers
INSERT INTO advertiser_roles (urls_id, role_id) SELECT urls.id AS urls_id, roles.id AS role_id FROM urls LEFT JOIN roles ON roles.`type` = 'advertiser';
-- 10.05.2013
CREATE TABLE `accounts`( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(255), `rule` TEXT, PRIMARY KEY (`id`) ) ENGINE=MYISAM CHARSET=utf8 COLLATE=utf8_general_ci; 
-- 21.05.2013
ALTER TABLE `site` ADD COLUMN `status` TINYINT(1) DEFAULT 1 NULL AFTER `use_bl`;
-- 24.05.2013
ALTER TABLE `site` ADD COLUMN `ftp_data` TEXT NULL AFTER `logo_data`;
-- 12.07.2013 
ALTER TABLE `site` ADD COLUMN `less_strict` TINYINT(1) DEFAULT 0 NULL AFTER `status`; 
-- 19.08.2013
ALTER TABLE `site` CHANGE `status` `status` TINYINT(1) DEFAULT 1 NULL COMMENT 'check work and security of remote mini sites', ADD COLUMN `curl_status` TINYINT(1) DEFAULT 1 NULL COMMENT 'check curl work in the remote mini sites' AFTER `status`;
-- 27.09.2013 
CREATE TABLE `settings`( `id` FLOAT(11) NOT NULL AUTO_INCREMENT, `key` VARCHAR(255), `value` TEXT, PRIMARY KEY (`id`) ) ENGINE=MYISAM CHARSET=utf8 COLLATE=utf8_general_ci;
-- 2.10.2013
ALTER TABLE `site` CHANGE `wp_login` `wp_login` VARCHAR(255) CHARSET utf8 COLLATE utf8_general_ci NOT NULL; 
-- 5.11.2013
CREATE TABLE `statistic`( `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `urls_logs_id` INT(11), `added_date` DATE, `name` VARCHAR(45), `url_id` INT(11), `redirect` INT(6), `exception` INT(6), PRIMARY KEY (`id`) ) ENGINE=MYISAM CHARSET=utf8 COLLATE=utf8_general_ci; 
ALTER TABLE `statistic` ADD COLUMN `site_id` INT(11) NULL AFTER `exception`;
-- 04.12.2013
CREATE TABLE `black_logos` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`site_id` INT(10) UNSIGNED NOT NULL,
	`url_id` INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
ROW_FORMAT=DEFAULT

-- 17.02.2014
ALTER TABLE `site` ADD COLUMN `project` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Project the site belongs to (1- Adwords, 2 - CPV)' AFTER `logo_size`;
CREATE TABLE `projects` (
	`id` TINYINT(1) UNSIGNED NOT NULL,
	`name` VARCHAR(30) NOT NULL
)
COLLATE='utf8_unicode_ci'
ENGINE=MyISAM;
INSERT INTO `wp_redirects`.`projects` (`id`, `name`) VALUES (1, 'Adwords');
INSERT INTO `wp_redirects`.`projects` (`id`, `name`) VALUES (2, 'CPV');
update site set project=2;
update site set project=1 where domain like 'dealsdisplay.com%' or domain like 'offergrabber.net%' or domain like 'shopo-holic.com%';