ALTER TABLE `companies` DROP `hourly_rate`;

INSERT INTO `config` ( `name` , `value` )
VALUES (
'cc_interface_class', 'AuthorizeNet'
);

INSERT INTO `config` ( `name` , `value` )
VALUES (
'cc_username', ''
);

INSERT INTO `config` ( `name` , `value` )
VALUES (
'cc_password', ''
);

INSERT INTO `config` ( `name` , `value` )
VALUES (
'cc_testmode', '1'
);

INSERT INTO `config` ( `name` , `value` )
VALUES (
'my_company_id', '1'
);

INSERT INTO `config` ( `name` , `value` )
VALUES (
'invoice_logo', ''
);

INSERT INTO `config` ( `name` , `value` )
VALUES (
'invoice_note', ''
);

INSERT INTO `config` ( `name` , `value` )
VALUES (
'invoice_terms', 'NET15'
);

INSERT INTO `config` ( `name` , `value` )
VALUES (
'bundled_pear', '0'
);

ALTER TABLE `users` ADD `show_menu` INT( 1 ) NOT NULL DEFAULT '1',
ADD `show_timers` INT( 1 ) NOT NULL DEFAULT '1';

CREATE TABLE `timers` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`user_id` INT UNSIGNED NOT NULL ,
`status` ENUM( 'RUNNING', 'PAUSED' ) NOT NULL DEFAULT 'PAUSED',
`last_start_ts` INT UNSIGNED NOT NULL ,
`previous_total` INT UNSIGNED NOT NULL ,
INDEX ( `user_id` )
) ENGINE = MYISAM ;

ALTER TABLE `timers` ADD `name` VARCHAR( 255 ) NOT NULL AFTER `user_id` ;

CREATE TABLE `timer_events` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`timer_id` INT UNSIGNED NOT NULL ,
`start_ts` INT NOT NULL ,
`end_ts` INT NOT NULL,
INDEX ( `timer_id` )
) ENGINE = MYISAM ;

# 02-28-2007 Changes
INSERT INTO `config` ( `name` , `value` )
VALUES (
'default_project_status_id', '2'
), (
'default_project_priority_id', '2'
);

INSERT INTO `config` ( `name` , `value` )
VALUES (
'default_task_status_id', '4'
), (
'default_task_priority_id', '2'
);

# 07-18-2007 Changes
ALTER TABLE `payments` CHANGE `auth_code` `auth_code` VARCHAR( 255 ) NOT NULL;
