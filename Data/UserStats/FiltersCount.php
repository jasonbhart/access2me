<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;
use Access2Me\Model\FiltersRepository;

class FiltersCount implements ResourceInterface 
{
    /**
     * @var \Database
     */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getType()
    {
        return UserStats::FILTERS_COUNT;
    }

    public function get($user)
    {
        $repo = new FiltersRepository($this->db);
        return $repo->getCountByUser($user['id']);
    }
}
