<?php

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
    $current['messageId'] = (string) $message['header']->message_id;
    $current['subject']   = (string) $message['overview'][0]->subject;
    $current['to']        = (string) $message['overview'][0]->to;
    $current['from']      = (string) $message['overview'][0]->from;
    $current['fromEmail'] = (string) $message['header']->from[0]->mailbox .
                            '@' .
                            (string) $message['header']->from[0]->host;
    $current['body']      = (string) $message['body'];

    $destination = explode('@', $current['to']);

    $destination[0] = trim($destination[0], '"');

    $query = "SELECT `id` FROM `users` WHERE `mailbox` = '" . $destination[0] . "' LIMIT 1;";
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
            'subject',
            'body'
        ),
        array(
            $current['messageId'],
            $current['userId'],
            $current['from'],
            $current['fromEmail'],
            $current['subject'],
            $current['body']
        ),
        true
    );

    echo "<br />";
}