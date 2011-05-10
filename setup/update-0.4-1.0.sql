CREATE TABLE `company_prices` (
  `company_id` int(11) unsigned NOT NULL default '0',
  `item_code_id` int(11) unsigned NOT NULL default '0',
  `price` float(8,2) NOT NULL default '0.00',
  `tax_rate` float(5,4) NOT NULL default '0.0000',
  PRIMARY KEY  (`company_id`,`item_code_id`)
) TYPE=MyISAM;

CREATE TABLE `item_codes` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `code` varchar(25) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `cost` float(8,2) NOT NULL default '0.00',
  `price` float(8,2) NOT NULL default '0.00',
  `taxable` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

INSERT INTO `config` VALUES ('tax_rate','8.5');

ALTER TABLE `task_activities` ADD `item_code_id` INT( 11 ) DEFAULT '0' NOT NULL ;
ALTER TABLE `expenses` ADD `item_code_id` INT( 11 ) DEFAULT '0' NOT NULL ;
ALTER TABLE `payment_schedule` ADD `item_code_id` INT( 11 ) DEFAULT '0' NOT NULL ;
ALTER TABLE `invoice_lines` ADD `item_code_id` INT( 11 ) DEFAULT '0' NOT NULL ;
ALTER TABLE `projects` ADD `default_item_code_id` INT( 11 ) NOT NULL ;

ALTER TABLE `invoices` CHANGE `terms` `terms` ENUM( 'NET15', 'NET30', 'NET45', 'IMMEDIATE' ) DEFAULT 'NET15' NOT NULL;

-- 09/08/2005 Changes --
CREATE TABLE `accounts` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
`name` VARCHAR( 255 ) NOT NULL ,
`description` VARCHAR( 255 ) NOT NULL ,
`type` VARCHAR( 25 ) NOT NULL ,
PRIMARY KEY ( `id` )
);

ALTER TABLE `item_codes` ADD `income_account_id` INT( 11 ) NOT NULL ;
ALTER TABLE `item_codes` ADD `expense_account_id` INT( 11 ) NOT NULL ;
INSERT INTO `config` VALUES ('account_rec','0');
INSERT INTO `config` VALUES ('account_payment','0');

-- Add last update timestamp fields --
ALTER TABLE `invoices` ADD `updated_ts` INT( 11 ) NOT NULL ;
UPDATE invoices SET updated_ts = timestamp WHERE updated_ts = 0;
ALTER TABLE `item_codes` ADD `updated_ts` INT( 11 ) NOT NULL ;
ALTER TABLE `payments` ADD `updated_ts` INT( 11 ) NOT NULL ;
UPDATE payments SET updated_ts = timestamp WHERE updated_ts = 0;
ALTER TABLE `accounts` ADD `updated_ts` INT( 11 ) NOT NULL ;

-- 11/09/2005 Changes --
CREATE TABLE `rate_structures` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
`name` VARCHAR( 255 ) NOT NULL ,
`type` ENUM( 'MONTHLY', 'INVOICE' ) DEFAULT 'MONTHLY' NOT NULL ,
PRIMARY KEY ( `id` )
);

CREATE TABLE `rate_structure_lines` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
`rate_structure_id` INT UNSIGNED NOT NULL ,
`low` INT NOT NULL ,
`high` INT NOT NULL ,
`discount` FLOAT( 9, 2 ) NOT NULL ,
PRIMARY KEY ( `id` )
);

CREATE TABLE `rate_structure_item_codes` (
`rate_structure_id` INT UNSIGNED NOT NULL ,
`item_code_id` INT UNSIGNED NOT NULL ,
PRIMARY KEY ( `rate_structure_id` , `item_code_id` )
);

ALTER TABLE `rate_structures` ADD `discount_item_code_id` INT UNSIGNED NOT NULL ;
ALTER TABLE `companies` ADD `rate_structure_id` INT UNSIGNED NOT NULL AFTER `hourly_rate` ;

-- Index changes --
ALTER TABLE `check_transactions` ADD INDEX `check_id` ( `check_id` );
ALTER TABLE `check_transactions` ADD INDEX `trans_id` ( `trans_id` );
ALTER TABLE `checks` ADD INDEX `user_id` ( `user_id` );
ALTER TABLE `checks` ADD INDEX `trans_id` ( `trans_id` );
ALTER TABLE `companies` ADD INDEX `rate_structure_id` ( `rate_structure_id` );
ALTER TABLE `company_prices` ADD INDEX `item_code_id` ( `item_code_id` );
ALTER TABLE `company_prices` ADD INDEX `tax_rate` ( `tax_rate` );
ALTER TABLE `company_prices` ADD INDEX `price` ( `price` );
ALTER TABLE `company_transactions` ADD INDEX `company_id` ( `company_id` );
ALTER TABLE `company_transactions` ADD INDEX `timestamp` ( `timestamp` );
ALTER TABLE `expenses` ADD INDEX `company_id` ( `company_id` );
ALTER TABLE `expenses` ADD INDEX `task_id` ( `task_id` );
ALTER TABLE `expenses` ADD INDEX `project_id` ( `project_id` );
ALTER TABLE `expenses` ADD INDEX `item_code_id` ( `item_code_id` );
ALTER TABLE `invoice_line_links` ADD INDEX `invoice_line_id` ( `invoice_line_id` );
ALTER TABLE `invoice_line_links` ADD INDEX `task_activity_id` ( `task_activity_id` );
ALTER TABLE `invoice_line_links` ADD INDEX `expense_id` ( `expense_id` );
ALTER TABLE `invoice_line_links` ADD INDEX `payment_schedule_id` ( `payment_schedule_id` );
ALTER TABLE `invoice_lines` ADD INDEX `invoice_id` ( `invoice_id` );
ALTER TABLE `invoice_lines` ADD INDEX `item_code_id` ( `item_code_id` );
ALTER TABLE `invoices` ADD INDEX `company_id` ( `company_id` );
ALTER TABLE `invoices` ADD INDEX `trans_id` ( `trans_id` );
ALTER TABLE `invoices` ADD INDEX `sent_ts` ( `sent_ts` );
ALTER TABLE `item_codes` ADD INDEX `income_account_id` ( `income_account_id` );
ALTER TABLE `item_codes` ADD INDEX `expense_account_id` ( `expense_account_id` );
ALTER TABLE `payment_invoices` ADD INDEX `payment_id` ( `payment_id` );
ALTER TABLE `payment_invoices` ADD INDEX `invoice_id` ( `invoice_id` );
ALTER TABLE `payment_schedule` ADD INDEX `item_code_id` ( `item_code_id` );
ALTER TABLE `payments` ADD INDEX `company_id` ( `company_id` );
ALTER TABLE `payments` ADD INDEX `trans_id` ( `trans_id` );
ALTER TABLE `payments` ADD INDEX `timestamp` ( `timestamp` );
ALTER TABLE `projects` ADD INDEX `owner_id` ( `owner_id` );
ALTER TABLE `projects` ADD INDEX `company_id` ( `company_id` );
ALTER TABLE `projects` ADD INDEX `project_status_id` ( `project_status_id` );
ALTER TABLE `projects` ADD INDEX `project_priority_id` ( `project_priority_id` );
ALTER TABLE `projects` ADD INDEX `sales_com_type_id` ( `sales_com_type_id` );
ALTER TABLE `projects` ADD INDEX `sales_com_user_id` ( `sales_com_user_id` );
ALTER TABLE `projects` ADD INDEX `default_item_code_id` ( `default_item_code_id` );
ALTER TABLE `rate_structure_item_codes` ADD INDEX `item_code_id` ( `item_code_id` );
ALTER TABLE `rate_structure_lines` ADD INDEX `rate_structure_id` ( `rate_structure_id` );
ALTER TABLE `rate_structures` ADD INDEX `discount_item_code_id` ( `discount_item_code_id` );
ALTER TABLE `task_activities` ADD INDEX `task_id` ( `task_id` );
ALTER TABLE `task_activities` ADD INDEX `user_id` ( `user_id` );
ALTER TABLE `task_activities` ADD INDEX `start_ts` ( `start_ts` );
ALTER TABLE `task_activities` ADD INDEX `end_ts` ( `end_ts` );
ALTER TABLE `task_activities` ADD INDEX `cost_trans_id` ( `cost_trans_id` );
ALTER TABLE `task_activities` ADD INDEX `com_trans_id` ( `com_trans_id` );
ALTER TABLE `task_activities` ADD INDEX `invoice_id` ( `invoice_id` );
ALTER TABLE `task_activities` ADD INDEX `sales_com_type_id` ( `sales_com_type_id` );
ALTER TABLE `task_activities` ADD INDEX `item_code_id` ( `item_code_id` );
ALTER TABLE `tasks` ADD INDEX `project_id` ( `project_id` );
ALTER TABLE `tasks` ADD INDEX `task_status_id` ( `task_status_id` );
ALTER TABLE `tasks` ADD INDEX `task_priority_id` ( `task_priority_id` );
ALTER TABLE `tasks` ADD INDEX `due_ts` ( `due_ts` );
ALTER TABLE `tasks` ADD INDEX `created_ts` ( `created_ts` );
ALTER TABLE `tasks` ADD INDEX `sales_com_type_id` ( `sales_com_type_id` );
ALTER TABLE `tasks` ADD INDEX `sales_com_user_id` ( `sales_com_user_id` );
ALTER TABLE `user_transactions` ADD INDEX `user_id` ( `user_id` );
ALTER TABLE `user_transactions` ADD INDEX `timestamp` ( `timestamp` );
ALTER TABLE `users` ADD INDEX `user_type_id` ( `user_type_id` );
ALTER TABLE `users` ADD INDEX `company_id` ( `company_id` );
ALTER TABLE `users_to_task` ADD INDEX `task_id` ( `task_id` );
ALTER TABLE `users_to_task` ADD INDEX `user_id` ( `user_id` );

-- 2006-07-28 Changes --
ALTER TABLE `projects` ADD `deleted` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N';
ALTER TABLE `tasks` ADD `deleted` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N';
ALTER TABLE `users` ADD `deleted` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N';
ALTER TABLE `companies` ADD `deleted` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N';

-- 2006-08-01 Changes --
ALTER TABLE `invoice_lines` ADD `tax_amount` FLOAT( 5, 2 ) NOT NULL ;

