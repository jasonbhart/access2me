<?php

require_once 'boot.php';

$db = new Database();
$repo = new Access2Me\Model\SenderRepository($db);
$sender = $repo->getByEmailAndService(
    'Dell@dellhome.usa.dell.com',
    \Access2Me\Model\SenderRepository::SERVICE_LINKEDIN
);

if (!$sender) {
    exit('No such sender');
}

$linkedin = new \Access2Me\Helper\Linkedin($sender->getOAuthKey());
$profile = $linkedin->getProfile($sender->getOAuthKey(), $linkedinAuth);

var_dump($profile);

//echo '<img src="' . htmlentities($profile['picture_url']) . '" />';