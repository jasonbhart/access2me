<?php

session_start();

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/smtp.php";
require_once __DIR__ . "/imap.php";
require_once __DIR__ . "/logging.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/database.php";

$localUrl = 'http://localhost/a2m';

$facebookAuth = array(
    'appId'       => '325592287614687',
    'appSecret'   => 'e62adb004e674e52c2ab4039a973a97d',
    'redirect'    => $localUrl . '/a2m/fb.php'
);

$linkedinAuth = array(
    'clientId'     => '75dl362rayg47t',
    'clientSecret' => 'eCxKfjOpunoO9rSj'
);
