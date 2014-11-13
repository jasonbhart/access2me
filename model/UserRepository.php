<?php

namespace Access2Me\Model;

class UserRepository
{
    const TABLE_NAME = 'users';

    /**
     * @var \Database
     */
    private $db;

    public function __construct(\Database $db)
    {
        $this->db = $db;
    }

    public function getById($userId)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `id` = :id';
        $users = $this->db->getArray($query, array('id' => $userId));
        return $users ? $users[0] : null;
    }

    public function findAll()
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '`';
        $users = $this->db->getArray($query);
        return $users ? $users : array();
    }
}
