<?php

namespace Access2Me\Service\Auth\Linkedin;

class UserAuthRequest
{
    public $userId;
    public $redirectUrl;

    public function __construct($userId, $redirectUrl)
    {
        $this->userId = $userId;
        $this->redirectUrl = $redirectUrl;
    }
}