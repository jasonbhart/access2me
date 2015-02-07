<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;
use Access2Me\Service\Gmail;

class GmailMessagesCount extends GmailResource
{
    public function getType()
    {
        return UserStats::GMAIL_MESSAGES_COUNT;
    }

    protected function getFreshValue($user)
    {
        $googleAuth = $this->authProvider->getAuth($user['username']);
        $profile = Gmail::getProfile($googleAuth->client);
        return $profile['messagesTotal'];
    }

}
