<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\ProfileProvider;

try {

    $email = isset($_GET['email']) ? $_GET['email'] : null;
    
    if (!$email) {
        throw new \Exception('No such sender');
    }

    $db = new Database;
    $senderRepo = new Model\SenderRepository($db);

    // get all services for the sender
    $senders = $senderRepo->getByEmail($email);
    if (empty($senders)) {
        throw new Exception('No such sender');
    }

    // get all profiles of the sender
    $profiles = $defaultProfileProvider->getProfiles($senders);

    if ($profiles == null) {
        $errMsg = sprintf(
            'Can\'t retrieve profile of %s (message id: %d)',
            $message['email_from'],
            $message['id']
        );
        throw new \Exception($errMsg);
    }

    // save just fetched profiles
    $defaultProfileProvider->storeProfiles($senders, $profiles);

    // commit changes
    foreach ($senders as $sender) {
        $senderRepo->save($sender);
    }

    // show profile
    require_once '../views/sender_profile.html';

} catch (\Exception $ex) {
    Logging::getLogger()->error($ex->getMessage(), array('exception' => $ex));
    die(" Error : " . $ex->getMessage());
}

// $senderId

// get profile data
/*
    Phone Numbers
    Email Addresses     /me?fields={email}
    Physical Addresses  /me?fields={location}
    Social Network Profile URLs
    Skype
    GTalk
    AIM
    ICQ
    Yahoo!
    ...additional information when the sender is a customer as well, like:
    Websites    /me?fields={website}
 */
