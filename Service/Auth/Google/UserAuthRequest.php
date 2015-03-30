<?php

namespace Access2Me\Service\Auth\Google;

use Access2Me\Service\Auth;


class UserAuthRequest extends Auth\AbstractRequest
{
    public $userId;
    public $redirectUrl;

    public function __construct($userId, $redirectUrl)
    {
        $this->userId = $userId;
        $this->redirectUrl = $redirectUrl;
        $this->requiredScopes = [\Google_Service_Gmail::MAIL_GOOGLE_COM, Auth\Google::SCOPE_CONTACTS];
    }
}
