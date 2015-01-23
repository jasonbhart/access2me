<?php

// https://developers.google.com/accounts/docs/OAuth2#expiration

require_once '../boot.php';

use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\Service;


$db = new Database();
$userRepo = new Model\UserRepository($db);

$authProvider = new Helper\GoogleAuthProvider($appConfig['services']['gmail'], $userRepo);

// refresh gmail access token for every user
foreach ($userRepo->findAll() as $user) {
    if (!$user['gmail_access_token']) {
        continue;
    }

    try {

        $client = $authProvider->getClient();
        $client->setAccessToken($user['gmail_access_token']);

        $token = json_decode($client->getAccessToken(), true);

        // check expiration
        if (isset($token['created']) && isset($token['expires_in'])) {
            // assume token is expired when more then 80% of lifetime past
            $expires_at = $token['created'] + $token['expires_in'] * 0.8;

            if ($expires_at > time()) {
                continue;
            }
        }

        $client->refreshToken($client->getRefreshToken());
        $user['gmail_access_token'] = $client->getAccessToken();
        $userRepo->save($user);

    } catch (\Exception $ex) {
        // can't refresh token anymore, unset access token
        if ($ex instanceof Google_Auth_Exception) {
            $user['gmail_access_token'] = null;
            $userRepo->save($user);
            Logging::getLogger()->error(
                sprintf('Can\'t refresh token anymore, not valid refresh token (userId: %d)', $user['id'])
            );
        } else {
            Logging::getLogger()->error(
                sprintf('Can\'t refresh token for user: %d', $user['id']),
                ['exception' => $ex]
            );
        }
    }
}

// refresh service tokens (Twitter etc.)
$senderRepo = new Model\SenderRepository($db);
$tokenRefresher = new Service\TokenRefresher($appConfig);

foreach ($senderRepo->findAll() as $sender) {
    try {
        // process only expiring tokens
        if ($tokenRefresher->isDueToExpire($sender)) {

            // try extend lifetime of expiring token and save it to the storage
            if ($tokenRefresher->extendLifetime($sender)) {
                $senderRepo->save($sender);
            } else {
                $senderRepo->delete($sender->getId());
            }
        }
    } catch (\Exception $ex) {
        Logging::getLogger()->error(
            sprintf('Can\'t extend lifetime of sender\'s access token: %d', $sender->getId()),
            ['exception' => $ex]
        );
    }
}
