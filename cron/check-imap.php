<?php

use Access2Me\Helper;
use Access2Me\Message;
use Access2Me\Model;

require_once __DIR__ . "/../boot.php";

$db = Helper\Registry::getDatabase();
$userRepo = new Model\UserRepository($db);
$userEmailRepo = new Model\UserEmailRepository($db);
$mesgRepo = new Model\MessageRepository($db);

$messageOwnerGuesser = new Message\OwnerGuesser($userRepo, $userEmailRepo);
$messageSaver = new Message\Saver($mesgRepo, $messageOwnerGuesser);

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
        $message = [
            'raw_header' => $raw['header'],
            'raw_body' => $raw['body'],
            'mail' => $mail[0]
        ];
        
        $messageSaver->save($message);
    }   
}
