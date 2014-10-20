<?php

require_once __DIR__ . "/boot.php";

use Access2Me\Helper;
use Access2Me\Model;

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

// authenticate using twitter
try {
    $twitter = new Helper\Twitter($twitterAuth);

    // twitter may respond with this 
    if (isset($_GET['denied'])) {
        throw new \Exception('You didn\'t grant access to the application');
    }

    // check if user granted access to app
    if ($twitter->isAuthResponse($_GET) && isset($_SESSION['oauth'])) {

        $tempToken = $_SESSION['oauth'];
        unset($_SESSION['oauth']);
        
        // validate and get verification code
        $valid = $twitter->isValidResponse($_GET, $tempToken);
        $verifier = $twitter->getVerificationCode($_GET);
        if (!$valid || !$verifier) {
            throw new \Exception('Auth is not valid');
        }

        // upgrade token to Access token
        $authToken = $twitter->upgradeToAccessToken(
            $tempToken,
            $verifier
        );

        // store auth token for the later use
        $sender = new Model\Sender();
        $sender->setSender($message['from_email']);
        $sender->setService(Model\SenderRepository::SERVICE_TWITTER);
        $sender->setOAuthKey($authToken);

        $senderRepo = new Model\SenderRepository($db);
        $senderRepo->insert($sender);

        // get data for congratulation page
        $contact = $twitter->getContactInfo($authToken);
        
        // show user auth completed
        require_once 'views/auth_completed.html';

    } else {
        if (isset($_SESSION['oauth'])) {
            unset($_SESSION['oauth']);
        }

        // get request token for authentication
        $appResponseUrl = $twitterAuth['callback_url'] . '?message_id=' . $messageId;
        $requestToken = $twitter->getRequestToken($appResponseUrl);

        // store request token for later use
        $_SESSION['oauth'] = $requestToken;

        // send user to authentication
        header('Location: ' . $twitter->getAuthUrl($requestToken));
    }
} catch (Helper\TwitterException $ex) {
    Logging::getLogger()->error($ex->getMessage(), array('exception' => $ex));
    die(" Error : " . $ex->getMessage());
} catch (\Exception $ex) {
    Logging::getLogger()->error($ex->getMessage(), array('exception' => $ex));
    die(" Error : " . $ex->getMessage());
}
