<?php
/**
 * Manage user's sender list from email (link for whitelisting email address is added to email HTML header)
 */

require_once __DIR__ . '/boot.php';

use Access2Me\Helper;
use Access2Me\Model;


$token = isset($_GET['token']) ? $_GET['token'] : null;
$email = isset($_GET['email']) ? $_GET['email'] : null;
$domain = isset($_GET['domain']) ? $_GET['domain'] : null;

// check input params
$sender = null;
if ($email != null) {
    $sender = $email;
    $type = Model\UserSenderRepository::TYPE_EMAIL;
} elseif ($domain != null) {
    $sender = $domain;
    $type = Model\UserSenderRepository::TYPE_EMAIL;
}

if (!$token || $sender == null || Helper\UserListProvider::isAddressValid($sender, $type)) {
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

if ($tokenEntry['user_id'] == null || !in_array(Model\Roles::USER_LIST_MANAGER, $tokenEntry['roles'])) {
    echo 'Access denied';
    exit;
}

// store sender entry
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

echo 'Sender\'s address added to your whitelist.';
