<?php

use Access2Me\Helper;

require_once __DIR__ . "/../boot.php";

$db = new Database;

$params = array(
    'host'     => 'mail.access2.me',
    'user'     => 'catchall@access2.me',
    'password' => 'catch123'
);

$imap = new IMAP($params);

$messages = $imap->getInbox();

foreach($messages AS $message) {
    if (!Helper\Email::isSuitable($message)) {
        continue;
    }

    if (strpos((string) $message['overview'][0]->subject, 'Gmail Forwarding Confirmation') === false) {
        continue;
    }

    preg_match("/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/", $message['body'], $find);

    if (!empty($find[0])) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $find[0]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $output = curl_exec($ch);

        curl_close($ch);

        if (stripos($output, 'may now forward mail to') !== false) {
            $imap->deleteMessage($message['overview'][0]->uid);
        }
    }
}