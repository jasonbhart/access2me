<?php

// https://developers.google.com/google-apps/contacts/v3/reference

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;
use Access2Me\Service\Google\Contacts;

class GmailContactsCount extends GmailResource
{
    public function getType()
    {
        return UserStats::GMAIL_CONTACTS_COUNT;
    }

    public function get($user)
    {
        $googleAuth = $this->authProvider->getAuth($user['username']);
        $count = Contacts::getTotalCount($googleAuth->client, $googleAuth->username);
        return $count;
    }
}
