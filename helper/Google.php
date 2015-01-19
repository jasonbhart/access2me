<?php

namespace Access2Me\Helper;

class GoogleAuth
{
    /**
     * @var string Google username
     */
    public $username;

    /**
     * @var \Google_Client
     */
    public $client;

    /**
     * @var array
     */
    public $token;

    public function __construct($username, \Google_Client $client, array $token)
    {
        $this->username = $username;
        $this->client = $client;
        $this->token = $token;
    }
}

/**
 * Provides google access for specified user.
 * Handles caching and token expiration.
 */
class GoogleAuthProvider
{
    private $config;

    /**
     * @var \Access2Me\Model\UserRepository
     */
    private $userRepo;

    public function __construct($config, $userRepo)
    {
        $this->config = $config;
        $this->userRepo = $userRepo;
    }

    /**
     * @return \Google_Client not authenticated client
     */
    public function getClient()
    {
        $client = new \Google_Client();
        $client->setClientId($this->config['client_id']);
        $client->setClientSecret($this->config['client_secret']);
        
        return $client;
    }

    /**
     * Refreshes token if it has expired
     * 
     * @param \Google_Client $client
     * @param string $token
     * @return bool
     */
    protected function refreshToken(\Google_Client $client, $token)
    {
        $client->setAccessToken($token);

        // check if token is valid
        if (!$client->isAccessTokenExpired()) {
            return false;
        }

        // refresh token
        $client->refreshToken($client->getRefreshToken());
        
        return true;
    }

    /**
     * @param string $username
     * @return GoogleAuth
     */
    public function getAuth($username)
    {
        $client = $this->getClient();

        // load token from storage
        $user = $this->userRepo->getByUsername($username);
        $refreshed = $this->refreshToken($client, $user['gmail_access_token']);
        
        if ($refreshed) {
            $user['gmail_access_token'] = $client->getAccessToken();
            $this->userRepo->save($user);
        }

        $token = json_decode($client->getAccessToken(), true);

        return new GoogleAuth(
            $user['mailbox'],
            $client,
            $token
        );
    }
}
