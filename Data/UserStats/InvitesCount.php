<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;

class InvitesCount implements ResourceInterface
{

    public function __construct()
    {
    }

    public function getType()
    {
        return UserStats::INVITES_COUNT;
    }

    /**
     * @todo Not implemented
     * 
     * @param int $userId
     * @return int
     */
    public function get($userId)
    {
        return 0;
    }
}
