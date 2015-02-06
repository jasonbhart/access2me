<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;

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

    /**
     * @todo Refactor
     */
    public function get($userId)
    {
        $query = 'SELECT COUNT(1) cnt FROM `' . \Filter::TABLE_NAME . '` WHERE `user_id` = :user_id';
        $res = $this->db->getArray($query, ['user_id' => $userId]);
        
        return $res !== false ? (int)$res[0]['cnt'] : 0;
    }
}
