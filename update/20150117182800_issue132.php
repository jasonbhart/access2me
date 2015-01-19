<?php

require_once '../boot.php';

$db = new Database();

// add lifetime related columns
$query = 'ALTER TABLE `senders` ADD `created_at` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\'';
$db->execute($query);

$query = 'ALTER TABLE `senders` ADD `expires_at` datetime DEFAULT \'0000-00-00 00:00:00\'';
$db->execute($query);
