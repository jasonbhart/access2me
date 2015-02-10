<?php
/**
 * Manage user's sender list from email (link for whitelisting email address is added to email HTML header)
 */

require_once __DIR__ . '/boot.php';

use Access2Me\Helper;
use Access2Me\Model;


$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;
$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : null;

// check input params
if (!$token || !Helper\Utils::isValidEmail($email)) {
    echo 'Invalid request';
    exit;
}

// check auth token
$db = new Database;
$tokenRepo = new Model\AuthTokenRepository($db);

$tokenEntry = $tokenRepo->getByToken($token);
if (!$tokenEntry) {
    echo 'No such token';
    exit;
}

if ($tokenEntry['expires_at'] < new \DateTime()) {
    echo 'Token expired';
    exit;
}

if ($tokenEntry['user_id'] == null || !in_array(Model\Roles::USER_LIST_MANAGER, $tokenEntry['roles'])) {
    echo 'Access denied';
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
    $entry = $userSenderRepo->getByUserAndSender($tokenEntry['user_id'], $sender);
    if (!$entry) {
        $entry = [
            'user_id' => $tokenEntry['user_id'],
            'sender' => $sender
        ];
    }

    $entry['type'] = $type;
    $entry['access'] = Model\UserSenderRepository::ACCESS_ALLOWED;

    $userSenderRepo->save($entry);
    $tokenRepo->delete($tokenEntry['id']);
    $whitelisted = true;
}

require 'views/whitelist_sender.html';
