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
        // validate permissions
        if (!Helper\Facebook::validatePermissions($session, $facebookAuth['permissions'])) {
            throw new \Exception('Missing some required permissions!');
        }

        // extend auth token
        $session = $session->getLongLivedSession();
        
        // store auth token for the later use
        $sender['sender'] = $message['from_email'];
        $sender['service'] = Model\SenderRepository::SERVICE_FACEBOOK;
        $sender['oauth_key'] = $session->getToken();

        $senderRepo = new Model\SenderRepository($db);
        $senderRepo->insert($sender);

        // get data for congratulation page
        $contact = Helper\Facebook::getContactInfo($session);
        
        // show user auth completed
        require_once 'views/auth_complete.html';

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

