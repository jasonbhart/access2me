<?php

require_once __DIR__ . "/boot.php";

use Facebook\GraphUser;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookRedirectLoginHelper;

FacebookSession::setDefaultApplication($facebookAuth['appId'] , $facebookAuth['appSecret']);

$helper = new FacebookRedirectLoginHelper($facebookAuth['redirect']);

try {
  $session = $helper->getSessionFromRedirect();
} catch(FacebookRequestException $ex) {
    die(" Error : " . $ex->getMessage());
} catch(\Exception $ex) {
    die(" Error : " . $ex->getMessage());
}

if ($session) {
    $user_profile = (new FacebookRequest($session, 'GET', '/me'))->execute()->getGraphObject(GraphUser::className());

    echo '<pre>';
    print_r($user_profile);
    echo '</pre>';

    echo $user_profile->getProperty('first_name');
    echo " ";
    echo $user_profile->getProperty('last_name');

$request = new FacebookRequest(
  $session,
  'GET',
  '/me/picture',
  array (
    'redirect' => false,
    'height' => '200',
    'type' => 'normal',
    'width' => '200',
  )
);
$response = $request->execute();
$graphObject = $response->getGraphObject();

echo '<br /><img src="' . $graphObject->getProperty('url') . '">';

$query = "SELECT `from_email` FROM `messages` WHERE `id` = '" . $_GET['message_id'] . "' LIMIT 1;";
$message = $db->getArray($query);

} else {
    $login_url = $helper->getLoginUrl();
    header("Location: " . $login_url);
}
