<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Model;
use Access2Me\Helper;
use Access2Me\Model;

$db = new Database;

$query = "SELECT * FROM `messages` WHERE `status` = '2'";
$messages = $db->getArray($query);

if (empty($messages)) {
    die();
}

foreach ($messages AS $message) {
    $query = "SELECT `email` FROM `users` WHERE `id` = '" . $message['user_id'] . "' LIMIT 1";
    $to = $db->getArray($query);

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
    $contact = $profiles['services'][Model\SenderRepository::SERVICE_LINKEDIN]['profile'];

    $filter = new Filter($message['user_id'], $contact, $db);
    $filter->processFilters();
    if ($filter->status === true || true) {

        $mail = Helper\Email::buildVerifiedMessage($to[0], $profComb, $message);

        // send new message
        $smtp = new \ezcMailSmtpTransport(
            'mail.access2.me',
            'noreply@access2.me',
            'access123',
            587
        );

        $smtp->senderHost = 'access2.me';
        $smtp->options->connectionType = \ezcMailSmtpTransport::CONNECTION_TLS;

        try {
            $smtp->send($mail);
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
