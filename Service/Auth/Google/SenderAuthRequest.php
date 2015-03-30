<?php

namespace Access2Me\Service\Auth\Google;

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
        $this->requiredScopes = [\Google_Service_Plus::PLUS_LOGIN, \Google_Service_Plus::PLUS_ME];
    }
}
