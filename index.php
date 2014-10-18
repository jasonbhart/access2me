<?php

require_once 'boot.php';

$db = new Database();
$repo = new Access2Me\Model\SenderRepository($db);
$sender = $repo->getByEmailAndService(
    'Dell@dellhome.usa.dell.com',
    \Access2Me\Model\SenderRepository::SERVICE_FACEBOOK
);

Facebook\FacebookSession::setDefaultApplication($facebookAuth['appId'], $facebookAuth['appSecret']);
$fbHelper = new \Access2Me\Helper\Facebook($sender->getOAuthKey());
$profile = $fbHelper->getProfile();

var_dump($profile);

echo '<img src="' . htmlentities($profile['picture_url']) . '" />';