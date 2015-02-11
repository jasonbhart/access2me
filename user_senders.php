<?php
/**
 * Manage user's sender list from email (link for whitelisting email address is added to email HTML header)
 */

require_once __DIR__ . '/boot.php';

use Access2Me\Helper;
use Access2Me\Model;


$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;
$userId = isset($_REQUEST['uid']) ? $_REQUEST['uid'] : null;
$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : null;

// check input params
if (!$token || !Helper\Utils::isValidEmail($email)) {
    echo 'Invalid request';
    exit;
}

// check auth token
$db = new Database;
$tokenManager = new Helper\UserListTokenManager($appConfig['secret']);
if (!$tokenManager->isValid($token, $userId, $email)) {
    echo 'No such token';
    exit;
}

$splitted = Helper\Email::splitEmail($email);
$domain = $splitted['domain'];

// whitelist type
$sender = null;
$type = null;
if (isset($_POST['temail'])) {
    $sender = $email;
    $type = Model\UserSenderRepository::TYPE_EMAIL;
} elseif (isset($_POST['tdomain'])) {
    $sender = $domain;
    $type = Model\UserSenderRepository::TYPE_DOMAIN;
}

// store sender entry
if ($_POST && $type !== null) {
    
    $userSenderRepo = new Model\UserSenderRepository($db);
    $entry = $userSenderRepo->getByUserAndSender($userId, $sender);
    if (!$entry) {
        $entry = [
            'user_id' => $userId,
            'sender' => $sender
        ];
    }

    $entry['type'] = $type;
    $entry['access'] = Model\UserSenderRepository::ACCESS_ALLOWED;

    $userSenderRepo->save($entry);
    $whitelisted = true;
}

require 'views/whitelist_sender.html';
