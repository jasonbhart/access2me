<?php

namespace Access2Me\Helper;

class UserListTokenManager
{
    private $salt;
    
    public function __construct($salt)
    {
        $this->salt = $salt;
    }

    public function generateToken($userId, $email)
    {
        $token = sha1($this->salt . $userId . $email);
        return $token;
    }

    public function isValid($token, $userId, $email)
    {
        return $token == $this->generateToken($userId, $email);
    }
}
