<?php

use Access2Me\Helper;

require_once __DIR__ . "/../boot.php";

$db = new Database;

$imap = new IMAP($appConfig['imap']);

// get raw messages
$rawMessages = $imap->getInboxRaw();
$messages = array();

// parse raw messages
$parser = new \ezcMailParser();
foreach ($rawMessages as $raw) {
    $tmp = $raw['header']
        . ezcMailTools::lineBreak()
        . ezcMailTools::lineBreak()
        . $raw['body']; 
    $mail = $parser->parseMail(new ezcMailVariableSet($tmp));

    if (isset($mail[0])) {
        $messages[] = array(
            'header' => $raw['header'],
            'body' => $raw['body'],
            'overview' => $raw['overview'],
            'mail' => $mail[0]
        );
    }   
}


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