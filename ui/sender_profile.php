<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Helper;
use Access2Me\Model;

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

    $servicesConfig = array(
        Model\SenderRepository::SERVICE_LINKEDIN => $linkedinAuth,
        Model\SenderRepository::SERVICE_FACEBOOK => $facebookAuth,
        Model\SenderRepository::SERVICE_TWITTER => $twitterAuth
    );
    
    $provider = new Helper\SenderProfileProvider($servicesConfig);
    $profile = $provider->getProfile($senders);

    // save just fetched profiles

    if ($profile) {
        $map = array();
        foreach ($senders as $sender) {
            $map[$sender->getService()] = $sender;
        }
        
        foreach ($profile['services'] as $id => $service) {
            if ($service !== null && $service['cached'] == false) {
                $map[$id]->setProfile($service['profile']);
                $map[$id]->setProfileDate(new \DateTime());
                $senderRepo->update($map[$id]);
            }
        }
    }

    // show profile
    require_once '../views/sender_profile.php';

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
