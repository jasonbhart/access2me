<?php

use Access2Me\Helper;

require_once __DIR__ . "/../boot.php";

$db = new Database;

$imap = new IMAP($appConfig['imap']);

echo "<pre>";

// get raw messages
$rawMessages = $imap->getInboxRaw();
$messages = array();

// parse raw messages
$parser = new \ezcMailParser();
foreach ($rawMessages as $raw) {
    $tmp = $raw['header'] . "\r\n" . $raw['body']; 
    $mail = $parser->parseMail(new ezcMailVariableSet($tmp));

    if (isset($mail[0])) {
        $messages[] = array(
            'raw_header' => $raw['header'],
            'raw_body' => $raw['body'],
            'mail' => $mail[0]
        );
    }   
}

// process messages and save them into the database
foreach($messages AS $message) {

    // filter out not suitable messages
    if (!Helper\Email::isSuitable($message)) {
        continue;
    }

    $record = Helper\Email::toDatabaseRecord($message);

    // TODO: don't store messages for not existing users
    $query = "SELECT `id` FROM `users` WHERE `mailbox` = '" . $record['to'] . "' LIMIT 1;";
    $userId = $db->getArray($query);

    $record['userId'] = $userId[0]['id'];

    print_r($record);

    $db->insert(
        'messages',
        array(
            'message_id',
            'user_id',
            'from_name',
            'from_email',
            'reply_email',
            'subject',
            'header',
            'body'
        ),
        array(
            $record['messageId'],
            $record['userId'],
            $record['from'],
            $record['fromEmail'],
            $record['replyEmail'],
            $record['subject'],
            $record['header'],
            $record['body']
        ),
        true
    );

    echo "<br />";
}