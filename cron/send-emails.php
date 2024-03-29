<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Model;
use Access2Me\Helper;

$db = new Database;
$userRepo = new Model\UserRepository($db);
$mesgRepo = new Model\MessageRepository($db);

$authProvider = new Helper\GoogleAuthProvider($appConfig['services']['google'], $userRepo);

foreach ($userRepo->findAll() as $user) {
    try {
        $messages = $mesgRepo->findByUser($user['id']);

        if (!$messages) {
            continue;
        }

        // instantiate the following and pass them into MessageProcessor
        // Filter
        // Profileprovider

        $googleAuth = $authProvider->getAuth($user['username']);
        $storage = Helper\GmailImapStorage::getImapStorage($googleAuth);

        $userSenderRepo = new Model\UserSenderRepository($db);
        $userSendersList = new Helper\UserListProvider($user['id'], $userSenderRepo);

        // token generator for whitelisting
        $tokenManager = Helper\Registry::getUserListTokenManager();

        $filterRepo = new Model\FiltersRepository($db);
        $filters = $filterRepo->findByUserId($user['id']);

        $processor = new Helper\MessageProcessor($user, $db, $storage, $userSendersList, $tokenManager, $filters);
        $processor->process($messages);

        $storage->close();
    } catch (Exception $ex) {
        Logging::getLogger()->error('Processing messages of the user: ' . $user['id'], ['exception' => $ex]);
    }
}
