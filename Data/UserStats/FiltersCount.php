<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;

class FiltersCount extends AbstractResource
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
    public function get($user)
    {
        $query = 'SELECT COUNT(1) cnt FROM `' . \Filter::TABLE_NAME . '` WHERE `user_id` = :user_id';
        $res = $this->db->getArray($query, ['user_id' => $user['id']]);
        
        return $res !== false ? (int)$res[0]['cnt'] : 0;
    }
}
