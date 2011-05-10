-- MySQL dump 9.10
--
-- Host: localhost    Database: sureinvoice
-- ------------------------------------------------------
-- Server version	4.0.18-Max

--
-- Dumping data for table `attachments`
--


--
-- Dumping data for table `check_transactions`
--


--
-- Dumping data for table `checks`
--


--
-- Dumping data for table `companies`
--

INSERT INTO `companies` VALUES (1,'Default Company','123 Main
St','','Mesa','AZ','85210','(999) 000-0000','(999) 000-0000',125.00,0,0,0,'N');

--
-- Dumping data for table `company_transactions`
--


--
-- Dumping data for table `config`
--

INSERT INTO `config` VALUES ('error_log','');
INSERT INTO `config` VALUES ('error_page','error.php');
INSERT INTO `config` VALUES ('debug','0');
INSERT INTO `config` VALUES ('attachment_dir','');
INSERT INTO `config` VALUES ('url','');
INSERT INTO `config` VALUES ('path','');

--
-- Dumping data for table `expenses`
--


--
-- Dumping data for table `invoice_line_links`
--


--
-- Dumping data for table `invoice_lines`
--


--
-- Dumping data for table `invoices`
--


--
-- Dumping data for table `issue_attachments`
--


--
-- Dumping data for table `issue_comments`
--


--
-- Dumping data for table `issue_priorities`
--


--
-- Dumping data for table `issue_statuses`
--


--
-- Dumping data for table `issues`
--


--
-- Dumping data for table `issues_to_task`
--


--
-- Dumping data for table `payment_invoices`
--


--
-- Dumping data for table `payment_schedule`
--


--
-- Dumping data for table `payments`
--



--
-- Dumping data for table `project_cc`
--

-- 
-- Dumping data for table `phpnotify_notification_addresses`
-- 

INSERT INTO `phpnotify_notification_addresses` (`id`, `notification_id`, `address`) VALUES (3, 1, '|project_ccs|');
INSERT INTO `phpnotify_notification_addresses` (`id`, `notification_id`, `address`) VALUES (4, 2, '|invoice_emails|');

-- 
-- Dumping data for table `phpnotify_notification_macros`
-- 

INSERT INTO `phpnotify_notification_macros` (`id`, `notification_id`, `name`, `description`) VALUES (1, 1, 'description', 'Project Description');
INSERT INTO `phpnotify_notification_macros` (`id`, `notification_id`, `name`, `description`) VALUES (2, 1, 'name', 'Project Name');
INSERT INTO `phpnotify_notification_macros` (`id`, `notification_id`, `name`, `description`) VALUES (3, 1, 'project_ccs', 'The email addresses of all the users setup in the project CC list');
INSERT INTO `phpnotify_notification_macros` (`id`, `notification_id`, `name`, `description`) VALUES (4, 2, 'company_name', 'The name of your company');
INSERT INTO `phpnotify_notification_macros` (`id`, `notification_id`, `name`, `description`) VALUES (5, 2, 'company_phone', 'The phone number of your company');
INSERT INTO `phpnotify_notification_macros` (`id`, `notification_id`, `name`, `description`) VALUES (6, 2, 'invoice_emails', 'All users for the destination company that have Receive Invoice set to yes');

-- 
-- Dumping data for table `phpnotify_notifications`
-- 

INSERT INTO `phpnotify_notifications` (`id`, `name`, `description`, `from_address`, `subject`, `email`, `active`) VALUES (1, 'ProjectUpdated', 'Sent when a project is updated', 'sure_invoice@uversainc.com', 'Project Updated - |name|', '|updater_first_name| |updater_last_name| has updated the |name| Project.\r\n\r\nProject Description:\r\n|description|\r\n\r\n', 'Y');
INSERT INTO `phpnotify_notifications` (`id`, `name`, `description`, `from_address`, `subject`, `email`, `active`) VALUES (2, 'InvoiceEmail', 'Notification sent when an invoice is emailed', 'myemail@mydomain.com', 'Invoice from |company_name|', 'Please find your most recent invoice attached from |company_name|. If you have any questions you can contact us at |company_phone|.', 'Y');
        

--
-- Dumping data for table `project_notes`
--


--
-- Dumping data for table `project_priorities`
--

INSERT INTO `project_priorities` VALUES (1,'High',10);
INSERT INTO `project_priorities` VALUES (2,'Medium',20);
INSERT INTO `project_priorities` VALUES (3,'Low',30);

--
-- Dumping data for table `project_statuses`
--

INSERT INTO `project_statuses` VALUES (1,'Completed','Y');
INSERT INTO `project_statuses` VALUES (2,'In Progress','N');
INSERT INTO `project_statuses` VALUES (3,'Canceled','Y');
INSERT INTO `project_statuses` VALUES (4,'Pending','N');

--
-- Dumping data for table `projects`
--


--
-- Dumping data for table `sales_com_types`
--

INSERT INTO `sales_com_types` VALUES (1,'30% of Net','PERCENT_NET',0.30);
INSERT INTO `sales_com_types` VALUES (2,'12% of Net','PERCENT_NET',0.12);

--
-- Dumping data for table `task_activities`
--


--
-- Dumping data for table `task_items`
--


--
-- Dumping data for table `task_priorities`
--

INSERT INTO `task_priorities` VALUES (1,'High',10);
INSERT INTO `task_priorities` VALUES (2,'Medium',20);
INSERT INTO `task_priorities` VALUES (3,'Low',30);

--
-- Dumping data for table `task_statuses`
--

INSERT INTO `task_statuses` VALUES (1,'Completed','Y');
INSERT INTO `task_statuses` VALUES (2,'Canceled','Y');
INSERT INTO `task_statuses` VALUES (3,'Pending','N');
INSERT INTO `task_statuses` VALUES (4,'In Progress','N');

--
-- Dumping data for table `tasks`
--


--
-- Dumping data for table `user_project_rights`
--


--
-- Dumping data for table `user_rights`
--

INSERT INTO `user_rights` VALUES (1,1,'admin',1);
INSERT INTO `user_rights` VALUES (2,1,'accounting',1);

--
-- Dumping data for table `user_transactions`
--


--
-- Dumping data for table `user_types`
--

INSERT INTO `user_types` VALUES (1,'Developer','Y','home_resource.php');
INSERT INTO `user_types` VALUES (2,'User','N','company_detail.php');

--
-- Dumping data for table `users`
--

INSERT INTO `users` VALUES (1,1,'Admin','Powers',1,'123 Main St.','','Mesa','AZ','85210','admin','21232f297a57a5a743894a0e4a801fc3',0,0.00,'SALARY','Y','Y',0,0,0,'N');

--
-- Dumping data for table `users_to_task`
--


