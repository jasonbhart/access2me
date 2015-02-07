<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;

class GmailContactsCount extends GmailResource
{
    public function getType()
    {
        return UserStats::GMAIL_CONTACTS_COUNT;
    }

    protected function getFreshValue($user)
    {
        
    }

}
