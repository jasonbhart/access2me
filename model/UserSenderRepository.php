<?php

namespace Access2Me\Model;


class UserSenderRepository
{
    const TYPE_DOMAIN = 1;
    const TYPE_EMAIL = 2;

    const ACCESS_ALLOWED = 1;
    const ACCESS_DENIED = 2;

    const TABLE_NAME = 'user_senders';

    /**
     * @var \Database
     */
    private $db;

    public function __construct(\Database $db)
    {
        $this->db = $db;
    }

    public function get($id)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `id` = :id LIMIT 1;';
        
        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':id', $id, \PDO::PARAM_INT);
        $st->execute();
        
        $entry = $st->fetch();
        
        $st->closeCursor();
        
        return $entry !== false ? $entry : null;
    }

    public function getByUserAndSender($userId, $sender)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '`'
            . ' WHERE `user_id` = :user_id AND `sender` = :sender'
            . ' LIMIT 1';
        $entries = $this->db->getArray($query, ['user_id' => $userId, 'sender' => $sender]);
        return $entries ? $entries[0] : null;
    }

    public function findByUserAndAccess($userId, $access)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '`'
            . ' WHERE `user_id` = :user_id AND `access` = :access';
        $entries = $this->db->getArray($query, ['user_id' => $userId, 'access' => $access]);
        return $entries;
    }

    /**
     * @param array $entry
     */
    public function insert($entry)
    {
        return $this->db->insert(
            self::TABLE_NAME,
            array(
                'user_id',
                'sender',
                'type',
                'access'
            ),
            array(
                $entry['user_id'],
                $entry['sender'],
                $entry['type'],
                $entry['access']
            ),
            true
        );
    }

    /**
     * @param array $entry
     */
    public function update($entry)
    {
        $query = 'UPDATE `' . self::TABLE_NAME . '`'
            . ' SET'
            . ' `user_id` = :user_id,'
            . ' `sender` = :sender,'
            . ' `type` = :type,'
            . ' `access` = :access'
            . ' WHERE id = :id';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':user_id', $entry['user_id'], \PDO::PARAM_INT);
        $st->bindValue(':sender', $entry['sender']);
        $st->bindValue(':type', $entry['type'], \PDO::PARAM_INT);
        $st->bindValue(':access', $entry['access'], \PDO::PARAM_INT);
        $st->bindValue(':id', $entry['id'], \PDO::PARAM_INT);
        $st->execute();
    }

    public function save($entry)
    {
        if (!isset($entry['id'])) {
            return $this->insert($entry);
        } else {
            $this->update($entry);
            return $entry['id'];
        }
    }

    /**
     * @param int id
     */
    public function delete($id)
    {
        $query = 'DELETE FROM `' . self::TABLE_NAME . '`'
            . ' WHERE `id` = :id';

        return $this->db->execute($query, ['id' => $id]);
    }
    
    public function updateAccessTypeOfRelatedSender($entry) {
        if ($entry['type'] == self::TYPE_DOMAIN) {
            $query = 'UPDATE `' . self::TABLE_NAME . '`'
            . ' SET `access` = :access'
            . ' WHERE `user_id` = :user_id'
            . ' AND `sender` LIKE :sender';

            $conn = $this->db->getConnection();
            $st = $conn->prepare($query);
            $st->bindValue(':user_id', $entry['user_id'], \PDO::PARAM_INT);
            $st->bindValue(':access', $entry['access'], \PDO::PARAM_INT);
            $st->bindValue(':sender', "%" . $entry['sender']);
            $st->execute();
        }
    }
}
