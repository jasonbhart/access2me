<?php

require_once __DIR__ . '/../boot.php';

$db = new Database();

// change column to allow inserting message ids instead of booleans
$query = 'ALTER TABLE `messages` MODIFY `appended_to_unverified` varchar(120) NULL';
$db->execute($query);

// ensure unverified messages will not be pushed twice
$query = <<<'EOT'
UPDATE `messages`
    SET
        `appended_to_unverified`='QWERTY invalid message id'
    WHERE `appended_to_unverified` != 0
EOT;
$db->execute($query);

