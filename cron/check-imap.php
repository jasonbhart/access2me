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
print_r($messages);

foreach($messages AS $message) {
    $current['messageId'] = (string) $message['header']->message_id;
    $current['subject']   = (string) $message['overview'][0]->subject;
    $current['to']        = (string) $message['overview'][0]->to;
    $current['from']      = (string) $message['overview'][0]->from;
    $current['fromEmail'] = (string) $message['header']->from[0]->mailbox .
                            '@' .
                            (string) $message['header']->from[0]->host;
    $current['body']      = (string) $message['body'];

    $current['userId'] = 1;

    print_r($current);

    $db->insert(
        'messages',
        array(
            'id',
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