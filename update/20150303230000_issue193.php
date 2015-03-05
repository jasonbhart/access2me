<?php

require_once __DIR__ . '/../boot.php';

$db = new Database();

// add column to store linked in access token
$query = 'ALTER TABLE `users` ADD `linkedin_access_token` text NULL';
$db->execute($query);
