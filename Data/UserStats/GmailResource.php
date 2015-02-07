<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Helper\GoogleAuthProvider;

abstract class GmailResource implements ResourceInterface
{
    /**
     * @var GoogleAuthProvider
     */
    protected $authProvider;

    public function __construct(GoogleAuthProvider $authProvider)
    {
        $this->authProvider = $authProvider;
    }
}
