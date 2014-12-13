<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Model;
use Access2Me\Helper;

$db = new Database;
$userRepo = new Model\UserRepository($db);
$mesgRepo = new Model\MessageRepository($db);

foreach ($userRepo->findAll() as $user) {
    try {
        $messages = $mesgRepo->findByUser($user['id']);

        if (!$messages) {
            continue;
        }

        $storage = Helper\GmailImap::getImapStorage($user, $db);
        if ($storage == null) {
            Logging::getLogger()->info('Can\'t get storage handle for user id: ' . $user['id']);
            continue;
        }

        $processor = new Helper\MessageProcessor($user, $db, $storage);
        $processor->process($messages);

        $storage->close();
    } catch (Exception $ex) {
        Logging::getLogger()->error('Processing messages of the user: ' . $user['id'], ['exception' => $ex]);
    }
}
