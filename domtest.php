<?php

require_once __DIR__ . "/boot.php";

$db = new Database;

$params = array(
    'host'     => 'mail.access2.me',
    'user'     => 'catchall@access2.me',
    'password' => 'catch123'
);

$imap = new IMAP($params);

echo "<pre>";
$messages = $imap->getInboxAttachments();

print_r($messages);