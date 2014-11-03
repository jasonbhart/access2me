<?php
/**
 * TODO: seems we don't exchange auth code to auth token
 */

require_once __DIR__ . "/boot.php";

use Access2Me\Model;

$db = new Database;

if (!isset($_GET['code'])) {
    $params = array(
        'response_type' => 'code',
        'client_id' => $linkedinAuth['clientId'],
        'state' => 'ECEEFWF45453sdffef424',
        'redirect_uri' => $localUrl . '/linkedin.php?message_id=' . $_GET['message_id']
    );

    $query = http_build_query($params);
    header('Location: https://www.linkedin.com/uas/oauth2/authorization?' . $query);
} else {
    $params = array(
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => $localUrl . '/linkedin.php?message_id=' . $_GET['message_id'],
        'client_id' => $linkedinAuth['clientId'],
        'client_secret' => $linkedinAuth['clientSecret']
    );
    
    $query = http_build_query($params);
    $url = "https://www.linkedin.com/uas/oauth2/accessToken?" . $query;

    $cURL = curl_init();

    curl_setopt($cURL, CURLOPT_VERBOSE, true);
    curl_setopt($cURL, CURLOPT_URL, $url);
    curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($cURL, CURLOPT_HTTPGET, true);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json'
    ));

    $result = curl_exec($cURL);
    curl_close($cURL);
    $json = json_decode($result, true);

    $accessToken = (string) $json['access_token'];
    
    $mesgRepo = new Model\MessageRepository($db);
    $message = $mesgRepo->getById($_GET['message_id']);

    // store auth token for the later use
    // create new or update existing sender
    $email = $message['from_email'];
    $senderRepo = new Model\SenderRepository($db);
    $sender = $senderRepo->getByEmailAndService($email, Model\SenderRepository::SERVICE_LINKEDIN);

    if ($sender == null) {
        $sender = new Model\Sender();
        $sender->setSender($email);
        $sender->setService(Model\SenderRepository::SERVICE_LINKEDIN);
    }

    // we always have new token here whether user was authenticated before or not
    $sender->setOAuthKey($accessToken);

    // fetch user's profile
    $senders = array($sender);
    $profiles = $defaultProfileProvider->getProfiles($senders, false);
    $profile = $defaultProfileProvider->getProfileByServiceId(
        $profiles,
        Model\SenderRepository::SERVICE_LINKEDIN
    );

    if ($profile == null) {
        throw new \Exception('Can\'t retrieve profile');
    }

    $defaultProfileProvider->storeProfiles($senders, $profiles);
    
    // commit changes
    $senderRepo->save($sender);

    // sender is verified, mark message as allowed to be processed (filtering, sending to recipient)
    $db->updateOne('messages', 'status', '2', 'from_email', $email);
    
    require_once 'views/auth_completed.html';
}

