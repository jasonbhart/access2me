<?php

// https://developers.google.com/accounts/docs/OAuth2#expiration

require_once '../boot.php';

use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\Service;

class UserTokensRefresher
{
    /**
     * @var user entity
     */
    protected $user;

    /**
     * @var \Access2Me\Helper\GoogleAuthProvider
     */
    protected $googleAuthProvider;

    /**
     * @var \Access2Me\Service\TokenRefresher
     */
    protected $tokenRefresher;

    public function __construct($user, $googleAuthProvider, $tokenRefresher)
    {
        $this->user = $user;
        $this->googleAuthProvider = $googleAuthProvider;
        $this->tokenRefresher = $tokenRefresher;
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * Refreshes Google token
     */
    public function refreshGoogle()
    {
        try {
            if (!$this->user['gmail_access_token']) {
                return;
            }

            $client = $this->googleAuthProvider->getClient();
            $client->setAccessToken($this->user['gmail_access_token']);

            $token = json_decode($client->getAccessToken(), true);

            // check expiration
            if (isset($token['created']) && isset($token['expires_in'])) {
                // assume token is expired when more then 80% of lifetime past
                $expires_at = $token['created'] + $token['expires_in'] * 0.8;

                // not need to refresh if didn't expire
                if ($expires_at > time()) {
                    return;
                }
            }

            $client->refreshToken($client->getRefreshToken());
            $this->user['gmail_access_token'] = $client->getAccessToken();
        } catch (\Exception $ex) {
            // can't refresh token anymore, unset access token
            if ($ex instanceof Google_Auth_Exception) {
                $this->user['gmail_access_token'] = null;
                Logging::getLogger()->error(
                    sprintf('Can\'t refresh user\'s google token anymore, not valid refresh token (userId: %d)', $this->user['id'])
                );
            } else {
                Logging::getLogger()->error(
                    sprintf('Can\'t refresh google token for user: %d', $this->user['id']),
                    ['exception' => $ex]
                );
            }
        }
    }

    /**
     * Refreshes LinkedIn token
     */
    public function refreshLinkedIn()
    {
        try {
            if (!$this->user['linkedin_access_token']) {
                return;
            }

            $token = $this->user['linkedin_access_token'];

            // process only expiring tokens
            if ($this->tokenRefresher->isDueToExpire($token->getCreatedAt(), $token->getExpiresAt())) {

                // try extend lifetime of expiring token and save it to the storage
                $res = $this->tokenRefresher->extendLifetime(Service\Service::LINKEDIN, $token->getToken());
                if ($res) {
                    $token->setToken($res['token']);
                    $token->setCreatedAt($res['time']['created_at']);
                    $token->setExpiresAt($res['time']['expires_at']);
                } else {
                    // can't extend
                    $token = null;
                }

                $this->user['linkedin_access_token'] = $token;
            }
        } catch (\Exception $ex) {
            Logging::getLogger()->error(
                sprintf('Can\'t extend lifetime of users\'s linkedin access token: %d', $this->user['id']),
                ['exception' => $ex]
            );
        }
    }

    public function refresh()
    {
        $this->refreshGoogle();
        $this->refreshLinkedIn();
    }
}

$db = new Database();
$userRepo = new Model\UserRepository($db);

$authProvider = new Helper\GoogleAuthProvider($appConfig['services']['google'], $userRepo);
$tokenRefresher = new Service\TokenRefresher($appConfig);

// refresh access tokens for every user
foreach ($userRepo->findAll() as $user) {
    $refresher = new UserTokensRefresher($user, $authProvider, $tokenRefresher);
    $refresher->refresh();
    $userRepo->save($refresher->getUser());
}

// refresh sender's services tokens (Twitter etc.)
$senderRepo = new Model\SenderRepository($db);

foreach ($senderRepo->findAll() as $sender) {
    try {
        // process only expiring tokens
        if ($tokenRefresher->isDueToExpire($sender->getCreatedAt(), $sender->getExpiresAt())) {

            // try extend lifetime of expiring token and save it to the storage
            $res = $tokenRefresher->extendLifetime($sender->getService(), $sender->getOAuthKey());
            if ($res) {
                $sender->setOAuthKey($res['token']);
                $sender->setCreatedAt($res['time']['created_at']);
                $sender->setExpiresAt($res['time']['expires_at']);
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
