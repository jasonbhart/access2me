<?php

namespace Access2Me\Service\Auth\Linkedin;

class SenderAuthRequest
{
    /**
     * @var string
     */
    public $messageId;

    public function __construct($messageId)
    {
        $this->messageId = $messageId;
    }
}
