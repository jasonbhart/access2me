<?php

require_once __DIR__ . "/boot.php";
require_once __DIR__ . '/imap-new.php';

$db = new Database;


$paramters = array(
    'host' => 'mail.access2.me',
    'user' => 'catchall@access2.me',
    'pass' => 'catch123'
);

$imap = new IMAP_New($paramters);
$imap->connect();

//$imap->createFolder($imap->connection, 'a2mverified');
$imap->moveMessage(imap_uid($imap->connection, 2), $imap->connection, 'A2M_Verified');

/*

echo "<pre>";
//print_r($imap->getMessageSubject(1));
//print_r($imap->getMessageTo(1));
//print_r($imap->getMessageFrom(1));

echo $imap->getBodyNew(imap_uid($imap->connection, 1), $imap->connection);

$params = array(
    'host'     => 'mail.access2.me',
    'user'     => 'catchall@access2.me',
    'password' => 'catch123'
);

$imap = new IMAP($params);

$messages = $imap->getInbox();

echo (string) $messages[4]['body'];
 */
