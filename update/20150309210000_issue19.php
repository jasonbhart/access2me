<?php

require_once __DIR__ . '/../boot.php';

$db = \Access2Me\Helper\Registry::getDatabase();

// add column that determines whether user want email header be attached
$query = 'ALTER TABLE `users` ADD `attach_email_header` TINYINT DEFAULT 1';
$db->execute($query);
