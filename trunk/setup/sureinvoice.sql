-- MySQL dump 10.9
--
-- Host: localhost    Database: sureinvoice
-- ------------------------------------------------------
-- Server version	4.1.10a-Debian_2ubuntu0.1-log
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO,MYSQL323' */;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
CREATE TABLE `attachments` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `project_id` int(11) unsigned NOT NULL default '0',
  `task_id` int(11) unsigned NOT NULL default '0',
  `activity_id` int(11) unsigned NOT NULL default '0',
  `path` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `check_transactions`
--

DROP TABLE IF EXISTS `check_transactions`;
CREATE TABLE `check_transactions` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `type` enum('COST','COMM') NOT NULL default 'COST',
  `check_id` int(11) unsigned NOT NULL default '0',
  `trans_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `checks`
--

DROP TABLE IF EXISTS `checks`;
CREATE TABLE `checks` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user_id` int(11) unsigned NOT NULL default '0',
  `type` enum('CHECK','EFT','CC') NOT NULL default 'CHECK',
  `number` int(10) unsigned NOT NULL default '0',
  `name` varchar(100) NOT NULL default '',
  `address1` varchar(100) NOT NULL default '',
  `address2` varchar(100) NOT NULL default '',
  `city` varchar(100) NOT NULL default '',
  `state` varchar(10) NOT NULL default '',
  `zip` varchar(10) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `amount` float(8,2) NOT NULL default '0.00',
  `timestamp` int(11) unsigned NOT NULL default '0',
  `trans_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `address1` varchar(100) NOT NULL default '',
  `address2` varchar(100) NOT NULL default '',
  `city` varchar(100) NOT NULL default '',
  `state` varchar(10) NOT NULL default '',
  `zip` varchar(11) NOT NULL default '',
  `phone` varchar(15) NOT NULL default '',
  `fax` varchar(15) NOT NULL default '',
  `hourly_rate` float(5,2) NOT NULL default '0.00',
  `created_ts` int(11) unsigned NOT NULL default '0',
  `updated_ts` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `company_transactions`
--

DROP TABLE IF EXISTS `company_transactions`;
CREATE TABLE `company_transactions` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `company_id` int(11) unsigned NOT NULL default '0',
  `timestamp` int(11) unsigned NOT NULL default '0',
  `amount` float(8,2) NOT NULL default '0.00',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `name` varchar(100) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`name`)
) TYPE=MyISAM;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `task_id` int(11) unsigned NOT NULL default '0',
  `project_id` int(11) unsigned NOT NULL default '0',
  `cost` float(8,2) NOT NULL default '0.00',
  `price` float(8,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `invoice_line_links`
--

DROP TABLE IF EXISTS `invoice_line_links`;
CREATE TABLE `invoice_line_links` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `invoice_line_id` int(11) unsigned NOT NULL default '0',
  `task_activity_id` int(10) unsigned NOT NULL default '0',
  `expense_id` int(10) unsigned NOT NULL default '0',
  `payment_schedule_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `invoice_lines`
--

DROP TABLE IF EXISTS `invoice_lines`;
CREATE TABLE `invoice_lines` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `invoice_id` int(11) unsigned NOT NULL default '0',
  `quantity` float(5,2) NOT NULL default '0.00',
  `description` varchar(255) NOT NULL default '',
  `unit_price` float(8,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `company_id` int(11) unsigned NOT NULL default '0',
  `address1` varchar(100) NOT NULL default '',
  `address2` varchar(100) NOT NULL default '',
  `city` varchar(100) NOT NULL default '',
  `state` varchar(10) NOT NULL default '',
  `zip` varchar(15) NOT NULL default '',
  `timestamp` int(11) unsigned NOT NULL default '0',
  `terms` enum('NET15','NET30','NET45') NOT NULL default 'NET15',
  `trans_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `issue_attachments`
--

DROP TABLE IF EXISTS `issue_attachments`;
CREATE TABLE `issue_attachments` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `issue_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned NOT NULL default '0',
  `file` varchar(255) NOT NULL default '',
  `created_ts` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `issue_comments`
--

DROP TABLE IF EXISTS `issue_comments`;
CREATE TABLE `issue_comments` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `issue_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned NOT NULL default '0',
  `text` text NOT NULL,
  `created_ts` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `issue_priorities`
--

DROP TABLE IF EXISTS `issue_priorities`;
CREATE TABLE `issue_priorities` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  `priority_level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `issue_statuses`
--

DROP TABLE IF EXISTS `issue_statuses`;
CREATE TABLE `issue_statuses` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  `closed` enum('Y','N') default 'N',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `issues`
--

DROP TABLE IF EXISTS `issues`;
CREATE TABLE `issues` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `project_id` int(11) unsigned NOT NULL default '0',
  `assigned_id` int(11) unsigned NOT NULL default '0',
  `priority_id` int(11) unsigned NOT NULL default '0',
  `status_id` int(11) unsigned NOT NULL default '0',
  `reported_by_id` int(11) unsigned NOT NULL default '0',
  `summary` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `issues_to_task`
--

DROP TABLE IF EXISTS `issues_to_task`;
CREATE TABLE `issues_to_task` (
  `issue_id` int(11) unsigned NOT NULL default '0',
  `task_id` int(11) unsigned NOT NULL default '0',
  UNIQUE KEY `PRIMARY_KEY` (`issue_id`,`task_id`)
) TYPE=MyISAM;

--
-- Table structure for table `payment_invoices`
--

DROP TABLE IF EXISTS `payment_invoices`;
CREATE TABLE `payment_invoices` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `payment_id` int(10) unsigned NOT NULL default '0',
  `invoice_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `payment_schedule`
--

DROP TABLE IF EXISTS `payment_schedule`;
CREATE TABLE `payment_schedule` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `project_id` int(10) unsigned NOT NULL default '0',
  `task_id` int(10) unsigned NOT NULL default '0',
  `amount` float(8,2) NOT NULL default '0.00',
  `due_ts` int(10) unsigned NOT NULL default '0',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `task_id` (`task_id`),
  KEY `project_id` (`project_id`)
) TYPE=MyISAM;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `company_id` int(10) unsigned NOT NULL default '0',
  `timestamp` int(11) unsigned NOT NULL default '0',
  `amount` float(8,2) NOT NULL default '0.00',
  `type` enum('CHECK','CC','CASH') NOT NULL default 'CHECK',
  `check_no` int(11) NOT NULL default '0',
  `auth_code` varchar(50) NOT NULL default '',
  `trans_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `phpnotify_notification_addresses`
--

DROP TABLE IF EXISTS `phpnotify_notification_addresses`;
CREATE TABLE `phpnotify_notification_addresses` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `notification_id` int(11) unsigned NOT NULL default '0',
  `address` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `phpnotify_notification_macros`
--

DROP TABLE IF EXISTS `phpnotify_notification_macros`;
CREATE TABLE `phpnotify_notification_macros` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `notification_id` int(11) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `notification_id` (`notification_id`)
) TYPE=MyISAM;

--
-- Table structure for table `phpnotify_notifications`
--

DROP TABLE IF EXISTS `phpnotify_notifications`;
CREATE TABLE `phpnotify_notifications` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `description` text NOT NULL,
  `from_address` varchar(255) NOT NULL default '',
  `subject` varchar(255) NOT NULL default '',
  `email` text NOT NULL,
  `active` enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `project_cc`
--

DROP TABLE IF EXISTS `project_cc`;
CREATE TABLE `project_cc` (
  `project_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`project_id`,`user_id`)
) TYPE=MyISAM;

--
-- Table structure for table `project_notes`
--

DROP TABLE IF EXISTS `project_notes`;
CREATE TABLE `project_notes` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `project_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned NOT NULL default '0',
  `text` text NOT NULL,
  `created_ts` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `project_priorities`
--

DROP TABLE IF EXISTS `project_priorities`;
CREATE TABLE `project_priorities` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  `priority_level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `project_statuses`
--

DROP TABLE IF EXISTS `project_statuses`;
CREATE TABLE `project_statuses` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  `completed` enum('Y','N') default 'N',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `owner_id` int(11) unsigned NOT NULL default '0',
  `company_id` int(11) unsigned NOT NULL default '0',
  `project_status_id` int(11) unsigned NOT NULL default '0',
  `project_priority_id` int(11) unsigned NOT NULL default '0',
  `name` varchar(150) NOT NULL default '',
  `description` text NOT NULL,
  `due_ts` int(11) unsigned NOT NULL default '0',
  `billable` enum('Y','N','S') NOT NULL default 'Y',
  `issue_tracking` enum('Y','N') NOT NULL default 'Y',
  `created_ts` int(11) unsigned NOT NULL default '0',
  `updated_ts` int(11) unsigned NOT NULL default '0',
  `sales_com` enum('Y','N') NOT NULL default 'N',
  `sales_com_type_id` int(11) unsigned NOT NULL default '0',
  `sales_com_user_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `sales_com_types`
--

DROP TABLE IF EXISTS `sales_com_types`;
CREATE TABLE `sales_com_types` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `type` enum('AMOUNT','PERCENT_NET','PERCENT_GROSS') NOT NULL default 'AMOUNT',
  `rate` float(7,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `task_activities`
--

DROP TABLE IF EXISTS `task_activities`;
CREATE TABLE `task_activities` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `task_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned NOT NULL default '0',
  `text` text NOT NULL,
  `start_ts` int(11) unsigned NOT NULL default '0',
  `end_ts` int(11) unsigned NOT NULL default '0',
  `hourly_cost` float(8,2) NOT NULL default '0.00',
  `hourly_rate` float(8,2) NOT NULL default '0.00',
  `cost_trans_id` int(11) unsigned NOT NULL default '0',
  `com_trans_id` int(11) unsigned NOT NULL default '0',
  `invoice_id` int(11) unsigned NOT NULL default '0',
  `sales_com_type_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `task_items`
--

DROP TABLE IF EXISTS `task_items`;
CREATE TABLE `task_items` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `task_id` int(11) unsigned NOT NULL default '0',
  `item` varchar(255) NOT NULL default '',
  `task_activity_id` int(11) unsigned NOT NULL default '0',
  `parent_id` int(11) unsigned NOT NULL default '0',
  `order_number` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `IDX_order_number` (`task_id`,`parent_id`,`order_number`),
  KEY `TASK_ID` (`task_id`),
  KEY `parent_id` (`parent_id`)
) TYPE=MyISAM;

--
-- Table structure for table `task_priorities`
--

DROP TABLE IF EXISTS `task_priorities`;
CREATE TABLE `task_priorities` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  `priority_level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `task_statuses`
--

DROP TABLE IF EXISTS `task_statuses`;
CREATE TABLE `task_statuses` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  `completed` enum('Y','N') default 'N',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `project_id` int(11) unsigned NOT NULL default '0',
  `name` varchar(150) NOT NULL default '',
  `description` text NOT NULL,
  `task_status_id` int(11) unsigned NOT NULL default '0',
  `task_priority_id` int(11) unsigned NOT NULL default '0',
  `due_ts` int(11) unsigned NOT NULL default '0',
  `type` enum('FREEFORM','ITEMIZED') NOT NULL default 'FREEFORM',
  `billable` enum('Y','N','D','S') NOT NULL default 'D',
  `created_ts` int(11) unsigned NOT NULL default '0',
  `updated_ts` int(11) unsigned NOT NULL default '0',
  `sales_com` enum('Y','N','D') NOT NULL default 'D',
  `sales_com_type_id` int(11) unsigned NOT NULL default '0',
  `sales_com_user_id` int(11) unsigned NOT NULL default '0',
  `amount` float(9,2) unsigned NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `user_project_rights`
--

DROP TABLE IF EXISTS `user_project_rights`;
CREATE TABLE `user_project_rights` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `project_id` int(10) unsigned NOT NULL default '0',
  `level` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`project_id`)
) TYPE=MyISAM;

--
-- Table structure for table `user_rights`
--

DROP TABLE IF EXISTS `user_rights`;
CREATE TABLE `user_rights` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user_id` int(11) unsigned NOT NULL default '0',
  `user_right` varchar(50) NOT NULL default '',
  `right_value` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `user_transactions`
--

DROP TABLE IF EXISTS `user_transactions`;
CREATE TABLE `user_transactions` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user_id` int(11) unsigned NOT NULL default '0',
  `timestamp` int(11) unsigned NOT NULL default '0',
  `amount` float(8,2) NOT NULL default '0.00',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `user_types`
--

DROP TABLE IF EXISTS `user_types`;
CREATE TABLE `user_types` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  `resource` enum('Y','N') NOT NULL default 'N',
  `start_page` varchar(255) NOT NULL default 'my_projects.php',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user_type_id` int(11) unsigned NOT NULL default '0',
  `first_name` varchar(50) NOT NULL default '',
  `last_name` varchar(50) NOT NULL default '',
  `company_id` int(11) unsigned NOT NULL default '0',
  `address1` varchar(200) NOT NULL default '',
  `address2` varchar(200) NOT NULL default '',
  `city` varchar(150) NOT NULL default '',
  `state` varchar(5) NOT NULL default '',
  `zip` varchar(11) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `password` varchar(150) NOT NULL default '',
  `hourly_rate` float(5,2) unsigned NOT NULL default '0.00',
  `rate_type` enum('SALARY','HOURLY','HALF_CUST_RATE') NOT NULL default 'SALARY',
  `active` enum('Y','N') NOT NULL default 'Y',
  `created_ts` int(11) unsigned default '0',
  `updated_ts` int(11) unsigned NOT NULL default '0',
  `last_login_ts` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Table structure for table `users_to_task`
--

DROP TABLE IF EXISTS `users_to_task`;
CREATE TABLE `users_to_task` (
  `id` int(11) unsigned NOT NULL default '0',
  `task_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned NOT NULL default '0',
  `estimated_minutes` int(11) unsigned NOT NULL default '0',
  `hourly_cost` float(5,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;

