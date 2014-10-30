<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Model;
use Access2Me\Helper;

$db = new Database;

$query = "SELECT * FROM `messages` WHERE `status` = '2'";
$messages = $db->getArray($query);

if (empty($messages)) {
    die();
}

foreach ($messages AS $message) {
    $query = "SELECT `id`, `mailbox`,`gmail_access_token`, `gmail_refresh_token` FROM `users` WHERE `id` = '" . $message['user_id'] . "' LIMIT 1";
    $tmp = $db->getArray($query);
    
    if ($tmp === false) {
        $message = sprintf('No user exists for message id: %d', $message['id']);
        Logging::getLogger()->info($message);
        continue;
    }

    $to = $tmp[0];

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
            $mailbox = $to['mailbox'];
            $accessToken = $to['gmail_access_token'];
            try {
                $imap = Helper\GmailImap::getImap($mailbox, $accessToken);
            } catch (\Exception $ex) {
                // check that token is valid
                // if not try to refresh it
                if (!Helper\Google::isTokenValid($accessToken)) {
                    $accessToken = Helper\Google::requestAuthToken($to['gmail_refresh_token']);
                    
                    // save token back to user
                    $db->updateOne('users', 'gmail_access_token', $accessToken, 'id', $to['id']);

                    // try again
                    $imap = Helper\GmailImap::getImap($mailbox, $accessToken);
                }
            }

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
