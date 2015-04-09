<?php

namespace Access2Me\Model;


class UserEmailRepository extends AbstractRepository
{
    const TABLE_NAME = 'user_emails';

    private $db;

    public function __construct(\Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $id
     * @return UserEmail|null
     */
    public function getById($id)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `id` = :id';

        $records = $this->db->getArray($query, ['id' => $id], '\\Access2Me\\Model\\UserEmail');
        return $records ? $records[0] : null;
    }

    public function getByUserIdAndEmail($userId, $email)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '`'
            . ' WHERE `user_id` = :user_id AND `email` = :email';
        
        $records = $this->db->getArray(
            $query,
            [
                'user_id' => $userId,
                'email' => $email
            ],
            '\\Access2Me\\Model\\UserEmail'
        );
        return $records ? $records[0] : null;
    }

    /**
     * Returns list of user email that belong to certain user
     * 
     * @param int $userId
     * @return UserEmail[]
     */
    public function findByUserId($userId)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `user_id` = :user_id';
        
        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->setFetchMode(\PDO::FETCH_CLASS, '\\Access2Me\\Model\\UserEmail');
        $st->bindValue(':user_id', $userId);
        $st->execute();
        $records = $st->fetchAll();

        return $records;
    }

    /**
     * @param UserEmail $userEmail
     */
    public function insert($userEmail)
    {
        return $this->db->insert(
            self::TABLE_NAME,
            array(
                'user_id',
                'email'
            ),
            array(
                $userEmail->getUserId(),
                $userEmail->getEmail()
            ),
            true
        );
    }

    /**
     * @param UserEmail $userEmail
     */
    public function update($userEmail)
    {
        $query = 'UPDATE `' . self::TABLE_NAME .'`'
            . ' SET'
            . ' `user_id` = :user_id,'
            . ' `email` = :email'
            . ' WHERE id = :id';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':user_id', $userEmail->getUserId(), \PDO::PARAM_INT);
        $st->bindValue(':email', $userEmail->getEmail());
        $st->bindValue(':id', $userEmail->getId(), \PDO::PARAM_INT);
        $st->execute();
    }

    /**
     * @param UserEmail $userEmail
     * @return int
     */
    public function save($userEmail)
    {
        if ($userEmail->getId() === null) {
            return $this->insert($userEmail);
        } else {
            $this->update($userEmail);
            return $userEmail->getId();
        }
    }

    /**
     * @param int $id
     */
    public function delete($id)
    {
        $query = 'DELETE FROM `' . self::TABLE_NAME . '`' . ' WHERE `id` = :id';
        return $this->db->execute($query, ['id' => $id]);
    }
}
