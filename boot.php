<?php

session_start();

date_default_timezone_set('America/Los_Angeles');

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/imap.php";
require_once __DIR__ . "/filter.php";
require_once __DIR__ . "/logging.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/helper/Auth.php";
require_once __DIR__ . "/helper/GmailImap.php";
require_once __DIR__ . "/helper/Email.php";
require_once __DIR__ . "/helper/Facebook.php";
require_once __DIR__ . "/helper/Linkedin.php";
require_once __DIR__ . "/helper/Twitter.php";
require_once __DIR__ . "/helper/ProfileCombiner.php";
require_once __DIR__ . "/helper/SenderAuthentication.php";
require_once __DIR__ . "/helper/SenderProfileProvider.php";
require_once __DIR__ . "/helper/Template.php";
require_once __DIR__ . "/model/MessageRepository.php";
require_once __DIR__ . "/model/SenderRepository.php";
require_once __DIR__ . "/model/Sender.php";
require_once __DIR__ . "/model/Profile/Profile.php";
require_once __DIR__ . "/model/Profile/Position.php";
require_once __DIR__ . "/ProfileProvider/ProfileProviderInterface.php";
require_once __DIR__ . "/ProfileProvider/Facebook.php";
require_once __DIR__ . "/ProfileProvider/Linkedin.php";
require_once __DIR__ . "/ProfileProvider/Twitter.php";

if (getenv('DOM_DEV_MACHINE')) {
    $localUrl = 'http://localhost/a2m';
} else {
    $localUrl = 'http://app.access2.me';
}

$facebookAuth = array(
    'appId'       => '325592287614687',
    'appSecret'   => 'e62adb004e674e52c2ab4039a973a97d',
    'redirect'    => $localUrl . '/facebook.php',
    'permissions' => array(
        'public_profile',
        'email',
//        'user_about_me',
//        'user_birthday',
//        'user_location',
//        'user_website',
//        'user_work_history'
    )
);

$linkedinAuth = array(
    'clientId'     => '75dl362rayg47t',
    'clientSecret' => 'eCxKfjOpunoO9rSj'
);

$twitterAuth = array(
    'consumer_key' => 'eMnDyDghuEznqBGUE9C48LINs',
    'consumer_secret' => 'MGbLx83IbgGeRcp5ColG8CcDY6KUgfK3xBmoXm1342aHj5rrmw',
    'callback_url' => 'http://app.access2.me/twitter.php',
    //'user_agent' => 'access2.me'
);

$profileProviders = array(
    Access2Me\Model\SenderRepository::SERVICE_FACEBOOK => new Access2Me\ProfileProvider\Facebook($facebookAuth),
    Access2Me\Model\SenderRepository::SERVICE_LINKEDIN => new Access2Me\ProfileProvider\Linkedin($linkedinAuth),
    Access2Me\Model\SenderRepository::SERVICE_TWITTER => new Access2Me\ProfileProvider\Twitter($twitterAuth)
);

$defaultProfileProvider = new Access2Me\Helper\SenderProfileProvider($profileProviders);
