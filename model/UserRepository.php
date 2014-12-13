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
        return $users ? $users : [];
    }

    public function findAllByEmails($emails)
    {
        $values = [];
        $conn = $this->db->getConnection();
        foreach ($emails as $email) {
            $values[] = $conn->quote($email);
        }

        $query = 'SELECT * FROM `' . self::TABLE_NAME . '`'
            . ' WHERE email IN (' . implode(',', $values) . ')';
        $users = $this->db->getArray($query);

        return $users ? $users : [];
    }
}
