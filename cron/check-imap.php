<?php

use Access2Me\Helper;
use Access2Me\Model;

require_once __DIR__ . "/../boot.php";


function getMessageOwner(\ezcMail $mail, Model\UserRepository $usersRepo)
{
    // find owner (user) of this message among recipients
    $emails = Helper\Email::getTracedRecipients($mail);

    // transform all emails to lowercase
    foreach ($emails as &$email) {
        $email = strtolower($email);
    }

    $unique = array_unique($emails);
    $users = $usersRepo->findAllByMailboxes($unique);

    // map of users to their mailboxes
    $m2u = [];
    foreach ($users as $user) {
        $mailbox = strtolower($user['mailbox']);
        $m2u[$mailbox] = $user;
    }

    // find first recipient that is our user
    $user = null;
    foreach ($emails as $email) {
        if (isset($m2u[$email])) {
            $user = $m2u[$email];
            break;
        }
    }

    return $user;
}


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
            'raw_header' => $raw['header'],
            'raw_body' => $raw['body'],
            'mail' => $mail[0]
        );
    }   
}


$db = new Database;
$usersRepo = new Access2Me\Model\UserRepository($db);
$mesgRepo = new Access2Me\Model\MessageRepository($db);

// process messages and save them into the database
foreach($messages AS $message) {

    // filter out not suitable messages
    if (!Helper\Email::isSuitable($message)) {
        continue;
    }

    $user = getMessageOwner($message['mail'], $usersRepo);
    $record = Helper\Email::toDatabaseRecord($message);

    // no such user
    if ($user === null) {
        $msg = sprintf(
            'Can\'t find message owner: (%s) -> (%s)',
            $record['from_email'],
            $record['to_email']
        );
        Logging::getLogger()->addInfo($msg);
        continue;
    }

    $record['user_id'] = $user['id'];

    $mesgRepo->insert($record);
}
