<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Model;
use Access2Me\Helper;

$db = new Database;

$query = "SELECT * FROM `messages` WHERE id in (9,10,11)"; //`status` = '2'";
$messages = $db->getArray($query);

if (empty($messages)) {
    die();
}

foreach ($messages AS $message) {
    $query = "SELECT `email`,`gmail_access_token` FROM `users` WHERE `id` = '" . $message['user_id'] . "' LIMIT 1";
    $to = $db->getArray($query)[0];
    

    if ($to === false) {
        $message = sprintf('No user exists for message id: %d', $message['id']);
        Logging::getLogger()->info($message);
        continue;
    }

    // get all service sender is authenticated with
    $repo = new Model\SenderRepository($db);
    $senders = $repo->getByEmail($message['from_email']);

    // get all sender's profiles
    $profiles = $defaultProfileProvider->getProfiles($senders);

    if ($profiles == null) {
        $errMsg = sprintf(
            'Can\'t retrieve profile of %s (message id: %d)',
            $message['from_email'],
            $message['id']
        );
        Logging::getLogger()->error($errMsg);
        continue;
    }

    $profComb = $defaultProfileProvider->getCombiner($profiles);
    
    // FIXME until Filter will be fixed
    $contact = new Model\Profile\Profile();

    $filter = new Filter($message['user_id'], $contact, $db);
    $filter->processFilters();
    if ($filter->status === true || true) {
        try {
            $mail = Helper\Email::buildVerifiedMessage($to, $profComb, $message);
                    
            // connect to gmail
            $email = $to['email'];
            $accessToken = $to['gmail_access_token'];
            $imap = new Helper\GmailImap('imap.gmail.com', '993', 'ssl');
            $imap->loginOAuth2($email, $accessToken);

            // append message to mailbox
            $storage = new \Zend\Mail\Storage\Imap($imap);
            $newMessage = $mail->generate();
            $storage->appendMessage($newMessage, null, array(\Zend\Mail\Storage::FLAG_RECENT));

            $db->updateOne('messages', 'status', '3', 'id', $message['id']);
        } catch (Exception $ex) {
            Logging::getLogger()->error(
                'Can\'t send message: ' . $message['id'], 
                array('exception' => $ex)
            );
        }

    } else {
        $db->updateOne('messages', 'status', '4', 'id', $message['id']);
    }
}
