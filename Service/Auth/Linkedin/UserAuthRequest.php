<?php

namespace Access2Me\Service\Auth\Linkedin;

use Access2Me\Service\Auth;


class UserAuthRequest extends Auth\AbstractRequest
{
    public $userId;
    public $redirectUrl;

    public function __construct($userId, $redirectUrl)
    {
        $this->userId = $userId;
        $this->redirectUrl = $redirectUrl;
        $this->requiredScopes = [Auth\Linkedin::SCOPE_BASIC_PROFILE, Auth\Linkedin::SCOPE_CONTACT_INFO];
    }
}
