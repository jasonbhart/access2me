<?php

require_once __DIR__ . "/boot.php";

use Facebook\FacebookSession;
use Facebook\FacebookRequestException;
use Facebook\FacebookRedirectLoginHelper;

use Access2Me\Helper;
use Access2Me\Model;

/*
 * TODO:
 * Message's id should be encoded in some way, so that user can't enumerate messages
 * Does sender of this message is already authenticated ?
 * Remove existing auth requests ?
 */

// make sure we have message_id
$messageId = isset($_GET['message_id']) ? intval($_GET['message_id']) : 0;

if ($messageId == 0) {
    die('Invalid request');
}

 // check taht specified message exist
$db = new Database;
$messageRepo = new Model\MessageRepository($db);
$message = $messageRepo->getById($messageId);

if (!$message) {
    die('No such message');
}

// initialize facebook default app
FacebookSession::setDefaultApplication($facebookAuth['appId'] , $facebookAuth['appSecret']);

$appResponseUrl = $facebookAuth['redirect'] . '?message_id=' . $messageId;
$helper = new FacebookRedirectLoginHelper($appResponseUrl);

try {
    // try to get facebook session
    $session = $helper->getSessionFromRedirect();

    // are we logged in to facebook ?
    if ($session) {
        $fbHelper = new Helper\Facebook($session);
        // validate permissions
        if (!$fbHelper->validatePermissions($facebookAuth['permissions'])) {
            throw new \Exception('Missing some required permissions!');
        }

        // extend auth token
        $session = $session->getLongLivedSession();

        // store auth token for the later use
        // create new or update existing sender
        $email = $message['from_email'];
        $senderRepo = new Model\SenderRepository($db);
        $sender = $senderRepo->getByEmailAndService($email, Model\SenderRepository::SERVICE_FACEBOOK);

        if ($sender == null) {
            $sender = new Model\Sender();
            $sender->setSender($email);
            $sender->setService(Model\SenderRepository::SERVICE_FACEBOOK);
        }
        
        $sender->setOAuthKey($session->getToken());

        // fetch user's profile
        $senders = array($sender);
        $profiles = $defaultProfileProvider->getProfiles($senders, false);
        $profile = $defaultProfileProvider->getProfileByServiceId(
            $profiles,
            Model\SenderRepository::SERVICE_FACEBOOK
        );

        if ($profile == null) {
            throw new \Exception('Can\'t retrieve profile');
        }
        
        $defaultProfileProvider->storeProfiles($senders, $profiles);

        $senderRepo->save($sender);
        
        // show user auth completed
        require_once 'views/auth_completed.html';

    } else {
        // not logged in - ask to login
        $login_url = $helper->getLoginUrl($facebookAuth['permissions']);
        header("Location: " . $login_url);
    }
} catch (FacebookRequestException $ex) {
    Logging::getLogger()->error($ex->getMessage(), array('exception' => $ex));
    die(" Error : " . $ex->getMessage());
} catch (\Exception $ex) {
    Logging::getLogger()->error($ex->getMessage(), array('exception' => $ex));
    die(" Error : " . $ex->getMessage());
}

