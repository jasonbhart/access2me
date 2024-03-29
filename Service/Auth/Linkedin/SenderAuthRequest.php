<?php

namespace Access2Me\Service\Auth\Linkedin;

use Access2Me\Service\Auth;


class SenderAuthRequest extends Auth\AbstractRequest
{
    /**
     * @var string
     */
    public $messageId;

    public function __construct($messageId)
    {
        $this->messageId = $messageId;
        $this->requiredScopes = [Auth\Linkedin::SCOPE_BASIC_PROFILE, Auth\Linkedin::SCOPE_CONTACT_INFO];
    }
}
