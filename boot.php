<?php

session_start();

require_once __DIR__ . "/vendor/autoload.php";

// Facebook

$facebookAuth = array(
    'appId'       => '325592287614687',
    'appSecret'   => 'e62adb004e674e52c2ab4039a973a97d',
    'redirect'    => 'http://192.168.2.109/a2m/fb.php'
);

$linkedinAuth = array(
    'clientId'     => '75dl362rayg47t',
    'clientSecret' => 'eCxKfjOpunoO9rSj'
);