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

echo "<pre>";

foreach($messages AS $message) {

    // filter out not suitable messages
    if (!Helper\Email::isSuitable($message)) {
        continue;
    }

    $current['messageId'] = (string) $message['header']->message_id;
    $current['subject']   = (string) $message['overview'][0]->subject;
    $current['to']        = (string) $message['overview'][0]->to;
    $current['from']      = (string) $message['overview'][0]->from;
    $current['fromEmail'] = (string) $message['header']->from[0]->mailbox .
                            '@' .
                            (string) $message['header']->from[0]->host;
    $current['body']      = (string) $message['body'];

    // parse headers to find Return-Path or From for reply_email
    // TODO: This parsing should be moved to IMAP class
    $headers = Helper\Email::parseHeaders($message['headerDetail']);
    $current['reply_email'] = isset($headers['return-path'])
        ? $headers['return-path'][0]['email'] : $headers['from'][0]['email'];

    $destination = trim($current['to']);

    $query = "SELECT `id` FROM `users` WHERE `mailbox` = '" . $destination . "' LIMIT 1;";
    $userId = $db->getArray($query);

    $current['userId'] = $userId[0]['id'];

    print_r($current);

    $db->insert(
        'messages',
        array(
            'message_id',
            'user_id',
            'from_name',
            'from_email',
            'reply_email',
            'subject',
            'body'
        ),
        array(
            $current['messageId'],
            $current['userId'],
            $current['from'],
            $current['fromEmail'],
            $current['reply_email'],
            $current['subject'],
            $current['body']
        ),
        true
    );

    echo "<br />";
}