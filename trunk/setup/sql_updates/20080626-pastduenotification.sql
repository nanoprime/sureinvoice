-- Add past due notification
INSERT INTO `phpnotify_notifications` (`id`, `name`, `description`, `from_address`, `subject`, `email`, `active`) VALUES(3, 'InvoicePastDue', 'This email is sent for past due invoices.', 'sureinvoice@sureinvoice.com', 'Past due invoice |invoice_num| from |company_name|', 'The attached invoice from |company_name| is now past due. If you have any questions you can contact us at |company_phone|.', 'Y');
INSERT INTO `phpnotify_notification_addresses` (`id`, `notification_id`, `address`) VALUES(NULL, 3, '|invoice_emails|');
INSERT INTO `phpnotify_notification_macros` (`id`, `notification_id`, `name`, `description`) VALUES (NULL, 3, 'company_name', 'The name of your company');
INSERT INTO `phpnotify_notification_macros` (`id`, `notification_id`, `name`, `description`) VALUES (NULL, 3, 'company_phone', 'The phone number of your company');
INSERT INTO `phpnotify_notification_macros` (`id`, `notification_id`, `name`, `description`) VALUES (NULL, 3, 'invoice_emails', 'All users for the destination company that have Receive Invoice set to yes');
INSERT INTO `phpnotify_notification_macros` (`id`, `notification_id`, `name`, `description`) VALUES (NULL, 3, 'invoice_num', 'The invoice number');
