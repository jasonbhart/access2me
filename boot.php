<?php

session_start();

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/imap.php";
require_once __DIR__ . "/filter.php";
require_once __DIR__ . "/logging.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/helper/Email.php";
require_once __DIR__ . "/helper/Facebook.php";
require_once __DIR__ . "/helper/SenderAuthentication.php";
require_once __DIR__ . "/model/MessageRepository.php";
require_once __DIR__ . "/model/SenderRepository.php";

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
        // 'user_location'
    )
);

$linkedinAuth = array(
    'clientId'     => '75dl362rayg47t',
    'clientSecret' => 'eCxKfjOpunoO9rSj'
);
