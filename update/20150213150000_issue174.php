<?php

require_once __DIR__ . '/../boot.php';

$db = new Database();

// change column to allow inserting message ids instead of booleans
$query = 'ALTER TABLE `messages` MODIFY `appended_to_unverified` varchar(120) NULL';
$db->execute($query);

$query = 'UPDATE `messages` SET `appended_to_unverified`=NULL';
$db->execute($query);
