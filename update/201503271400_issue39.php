<?php

require_once __DIR__ . '/../boot.php';

use Access2Me\Helper;

$db = Helper\Registry::getDatabase();

// change `oauth_key` to text type
$query = 'ALTER TABLE `senders` MODIFY `oauth_key` TEXT NOT NULL';
$db->execute($query);
