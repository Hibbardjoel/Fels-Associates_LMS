<?php


/* 5.3 Updater */

if (!function_exists('hm_query')) exit;

$core_version = 5.3;

/* Ensure Database is Updated */


hm_out('Updating Database...');




if (!hm_table_exists('config')) {
 hm_query("CREATE TABLE IF NOT EXISTS `config` (
  `config_id` int(8) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(127) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `config_value` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `config_desc` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `config_group` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`config_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;");
 hm_query("INSERT INTO `config` (`config_id`, `config_key`, `config_value`, `config_desc`, `config_group`) VALUES (NULL, 'core_version', '".$core_version."', '', '0');");
 $current_version = $core_version;

} else {
 $current_version = hm_result("SELECT config_value FROM config WHERE config_key='core_version' LIMIT 1;");
}

if (!hm_table_exists('certificates')) {
 hm_query("CREATE TABLE IF NOT EXISTS `certificates` (
  `cert_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `cert_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `cert_image` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`cert_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;");
 hm_query("INSERT INTO `certificates` (`cert_id`, `cert_name`, `cert_image`) VALUES (1, 'Fels', 'FA_Certificate_One_CEU.jpg');");
 hm_query("INSERT INTO `certificates` (`cert_id`, `cert_name`, `cert_image`) VALUES (2, 'ICC', 'Infection_Control_Certificate.png');");
 hm_query("INSERT INTO `certificates` (`cert_id`, `cert_name`, `cert_image`) VALUES (3, 'MedAide 5-Hour', 'MedAide_Certificate.png');");
 hm_query("INSERT INTO `certificates` (`cert_id`, `cert_name`, `cert_image`) VALUES (4, 'MedAide 10-Hour', 'MedAide_Certificate.png');");
}

if (!hm_table_exists('course_groupings')) {
hm_query("CREATE TABLE IF NOT EXISTS `course_groupings` (
  `cg_group_id` int(8) unsigned NOT NULL,
  `cg_course_id` int(8) unsigned NOT NULL,
  PRIMARY KEY (`cg_group_id`,`cg_course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
}

if (!hm_table_exists('course_groups')) {
hm_query("CREATE TABLE IF NOT EXISTS `course_groups` (
  `cg_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cg_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`cg_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;");
}

if (!hm_table_exists('series_groupings')) {
 hm_out('Updating Database: Adding new table `series_groupings`');
 hm_query("CREATE TABLE IF NOT EXISTS `series_groupings` (
  `sr_group_id` int(8) unsigned NOT NULL,
  `sr_course_id` int(8) unsigned NOT NULL,
  `sr_grouping_sort` mediumint(4) unsigned NOT NULL,
  PRIMARY KEY (`sr_group_id`,`sr_course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
}

if (!hm_table_exists('series_groups')) {
 hm_out('Updating Database: Adding new table `series_groups`');
 hm_query("CREATE TABLE IF NOT EXISTS `series_groups` (
  `sr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sr_parent_id` int(8) unsigned NOT NULL,
  `sr_sort` mediumint(4) unsigned NOT NULL,
  `sr_name` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `sr_certificate` INT(8) UNSIGNED NOT NULL,
  PRIMARY KEY (`sr_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;");
}

if (!mysqlFieldExists('Courses','certificate_type')) {
 hm_out('Updating Database: Adding new field `certificate_type` to Courses table');
 hm_query("ALTER TABLE `Courses` ADD `certificate_type` TINYINT(1) UNSIGNED NOT NULL AFTER `exefield`;");
}

if (hm_result("SELECT count(*) FROM Courses WHERE certificate_type < 1;",1) > 0) {
 $total_zeroes = hm_result("SELECT count(*) FROM Courses WHERE certificate_type = 0;",1);
 $total_fels = hm_result("SELECT count(*) FROM Courses WHERE certificate_type = 1;",1);
 if ($total_zeroes > $total_fels) {
  hm_out('Changing certificate_type in Courses, setting 0 - 97 to 1 - 98');
  hm_query("UPDATE `Courses` SET certificate_type = (certificate_type + 1) WHERE certificate_type < 98;");
  hm_query("UPDATE `Courses` SET certificate_type = '0' WHERE certificate_type = '99' LIMIT 1;");
 }
}

$check98 = hm_result("SELECT count(*) FROM Courses WHERE certificate_type > 90 and certificate_type < 100;",1);
if ($check98 < 1) {
 hm_out('Changing certificate_type in Courses, setting 99 to 0');
 hm_query("UPDATE `Courses` SET certificate_type = '0' WHERE certificate_type = '99' LIMIT 1;");
}


if (!mysqlFieldExists('Courses','ceus')) {
 hm_out('Updating Database: Adding new field `ceus` to Courses table');
 hm_query("ALTER TABLE `Courses` ADD `ceus` TINYINT(1) UNSIGNED NOT NULL AFTER `desc`;");
}
if (!mysqlFieldExists('Courses','grade')) {
 hm_out('Updating Database: Adding new field `grade` to Courses table');
 hm_query("ALTER TABLE `Courses` ADD `grade` VARCHAR(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `ceus`;");
}

$sites = getSiteIDs();

foreach ($sites as $site_id) {
 $site_suffix = siteSuffix($site_id);

 $check = hm_result("SELECT MAX(userlevel) FROM users$site_suffix LIMIT 1;");

 if ($check < 4) {
  hm_out('Updating Database: Shifting Users to new level format for site '.$site_id);
  hm_query("UPDATE users$site_suffix SET userlevel = '10' WHERE userlevel = '3';");
  hm_query("UPDATE users$site_suffix SET userlevel = '6' WHERE userlevel = '2';");
 }


 if (!mysqlFieldExists('Assignments'.$site_suffix,'notified')) {
  hm_out('Updating Database: Adding new field `notified` to Assignments table for site '.$site_id);
  hm_query("ALTER TABLE `Assignments$site_suffix` ADD `notified` TINYINT(1) UNSIGNED NOT NULL AFTER `active`;");
 }
 if (!mysqlFieldExists('Assignments'.$site_suffix,'series_sort')) {
  hm_out('Updating Database: Adding new field `series_sort` to Assignments table for site '.$site_id);
  hm_query("ALTER TABLE `Assignments$site_suffix` ADD `series_sort` TINYINT(1) UNSIGNED NOT NULL AFTER `active`;");
 }
 if (!mysqlFieldExists('Assignments'.$site_suffix,'series_id')) {
  hm_out('Updating Database: Adding new field `series_id` to Assignments table for site '.$site_id);
  hm_query("ALTER TABLE `Assignments$site_suffix` ADD `series_id` INT(8) UNSIGNED NOT NULL AFTER `course_id`;");
 }
 if (!mysqlFieldExists('Assignments'.$site_suffix,'subseries_id')) {
  hm_out('Updating Database: Adding new field `subseries_id` to Assignments table for site '.$site_id);
  hm_query("ALTER TABLE `Assignments$site_suffix` ADD `subseries_id` INT(8) UNSIGNED NOT NULL AFTER `series_id`;");
 }
 if (mysqlFieldExists('users'.$site_suffix,'manager')) {
  hm_out('Updating Database: Repurposing `manager` in users as `unsubscribed` table for site '.$site_id);
  hm_query("ALTER TABLE `users$site_suffix` CHANGE `manager` `unsubscribed` TINYINT(1) UNSIGNED NULL DEFAULT NULL;");
 }
 if (!mysqlFieldExists('users'.$site_suffix,'hash')) {
  hm_out('Updating Database: Adding "hash" in users table for site '.$site_id);
  hm_query("ALTER TABLE `users$site_suffix` ADD `hash` VARCHAR(24) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `unsubscribed`;");
 }
 if (!mysqlFieldExists('results'.$site_suffix,'certificate')) {
  hm_out('Updating Database: Adding new field `certificate` to results table for site '.$site_id);
  hm_query("ALTER TABLE results$site_suffix ADD `certificate` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `report`;");
 }
 if (!mysqlFieldExists('results'.$site_suffix,'subseries_id')) {
  hm_out('Updating Database: Adding new field `subseries_id` to results table for site '.$site_id);
  hm_query("ALTER TABLE results$site_suffix ADD `subseries_id` INT(8) UNSIGNED NOT NULL AFTER `courseid`;");
 }
 if (!mysqlFieldExists('results'.$site_suffix,'ceus')) {
  hm_out('Updating Database: Adding new field `ceus` to results table for site '.$site_id);
  hm_query("ALTER TABLE `results$site_suffix` ADD `ceus` TINYINT(1) UNSIGNED NOT NULL AFTER `duration`;");
  hm_query("UPDATE `results$site_suffix` SET ceus='1',passorfail='P' WHERE passorfail='P/1 CEU';");
  hm_query("UPDATE `results$site_suffix` SET ceus='2',passorfail='P' WHERE passorfail='P/2 CEUs';");
  hm_query("UPDATE `results$site_suffix` SET ceus='3',passorfail='P' WHERE passorfail='P/3 CEUs';");
  hm_query("UPDATE `results$site_suffix` SET passorfail='X' WHERE passorfail='F' and duration='0' and score='0' and datestarted > datecomplied;");
 }
 if (!mysqlFieldExists('results'.$site_suffix,'certificate_override')) {
  hm_out('Updating Database: Adding new field `certificate_override` to results table for site '.$site_id);
  hm_query("ALTER TABLE results$site_suffix ADD `certificate_override` INT(10) UNSIGNED NOT NULL AFTER `certificate`;");
 }
 if (!hm_table_exists('groups'.$site_suffix)) {
  hm_out('Updating Database: Adding new table `groups` for site '.$site_id);
  hm_query("CREATE TABLE IF NOT EXISTS `groups$site_suffix` (
  `g_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `g_name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`g_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;");

 }
 if (!mysqlFieldExists('groups'.$site_suffix,'g_parent_id')) {
  hm_out('Updating Database: Adding new field `g_parent_id` to groups table for site '.$site_id);
  hm_query("INSERT INTO `groups$site_suffix` (g_id,g_name) VALUES (NULL,'Default Parent');");
  $new_default_id = hm_insert_id();
  hm_query("ALTER TABLE `groups$site_suffix` ADD `g_parent_id` INT(8) UNSIGNED NOT NULL DEFAULT '$new_default_id' AFTER `g_id`;");
  hm_query("UPDATE `groups$site_suffix` SET g_parent_id='$new_default_id' WHERE g_parent_id < 1 and g_id != '$new_default_id';");
  hm_query("UPDATE `groups$site_suffix` SET g_parent_id='0' WHERE g_id='$new_default_id' LIMIT 1;");
 }

 $check_q = hm_query("SHOW COLUMNS FROM compliance$site_suffix LIKE 'compliance_period'");
 $check = hm_fetch($check_q);
 if ($check['Type'] != 'float unsigned') {
  hm_out('Updating Database: Changing field `compliance_period` in compliance table to FLOAT UNSIGNED for site '.$site_id);
  hm_query("ALTER TABLE `compliance$site_suffix` CHANGE `compliance_period` `compliance_period` FLOAT UNSIGNED NOT NULL;");
 }

 $q = hm_query("SELECT userid FROM users$site_suffix WHERE status=1 and userlevel=10 and LENGTH(hash) != 24;");
 $hashcnt = hm_cnt($q);

 if ($hashcnt > 0) {
  hm_out('Adding hashes to '.$hashcnt.' users');

  while ($r = hm_fetch($q)) {
   createUserAccess($r['userid'],$site_id);
  }
 }
}

function setupResourceFolders() {
 $sites = getSiteIDs();
 $char_list = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
 $chars = str_split($char_list);

 if (!file_exists(PROTECTED_ACCESS_FOLDER)) mkdir(PROTECTED_ACCESS_FOLDER);

 foreach ($sites as $side_id) {
  if (!file_exists(PROTECTED_ACCESS_FOLDER.'/'.$side_id)) mkdir(PROTECTED_ACCESS_FOLDER.'/'.$side_id);
  foreach ($chars as $char) {
   if (!file_exists(PROTECTED_ACCESS_FOLDER.'/'.$side_id.'/'.$char)) mkdir(PROTECTED_ACCESS_FOLDER.'/'.$side_id.'/'.$char);
  }
 }
}


hm_out('Setting up Protected Access Folders...');

setupResourceFolders();

hm_query("UPDATE config SET config_value='".$core_version."' WHERE config_key='core_version' LIMIT 1;");

hm_out('Update to version '.$core_version.' completed. Refresh to continue.');
exit;