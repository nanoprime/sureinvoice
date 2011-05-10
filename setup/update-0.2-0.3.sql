ALTER TABLE `user_rights` ADD UNIQUE `IDX_user_id_right` ( `user_id` , `user_right` );
ALTER TABLE `users` ADD `invoiced` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL AFTER `active` ;
ALTER TABLE `users` ADD `salary` INT( 11 ) UNSIGNED NOT NULL AFTER `password` ;
ALTER TABLE `expenses` ADD `company_id` INT( 11 ) UNSIGNED NOT NULL AFTER `id` ;
ALTER TABLE `expenses` ADD `description` VARCHAR( 255 ) NOT NULL AFTER `project_id` ;
ALTER TABLE `expenses` ADD `created_ts` INT( 11 ) UNSIGNED NOT NULL ;
ALTER TABLE `invoices` ADD `sent_ts` INT( 11 ) UNSIGNED NOT NULL ;

