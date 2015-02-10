<?php

namespace Access2Me\Helper;

class AuthTokenManager
{
    /**
     * @var string in \DateInterval format
     */
    private $ttl = 'P1W';

    /**
     * @var \Access2Me\Model\AuthTokenRepository
     */
    private $repo;

    private $seed;
    
    public function __construct($repo, $seed)
    {
        $this->repo = $repo;
        $this->seed = $seed;
    }

    protected function generateTokenKey()
    {
        return sha1(microtime() . $this->seed . rand());
    }

    public function generateToken($userId, array $roles)
    {
        $expiresAt = new \DateTime();
        $expiresAt->add(new \DateInterval($this->ttl));  // expires after one week

        $token = [
            'token' => $this->generateTokenKey(),
            'user_id' => $userId,
            'roles' => $roles,
            'expires_at' => $expiresAt
        ];

        $this->repo->save($token);

        return $token;
    }
}
