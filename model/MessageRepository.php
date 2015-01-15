<?php

namespace Access2Me\Model;

class MessageRepository
{
    // this concers to a sender, to be refactored
    const STATUS_NOT_VERIFIED = 0;
    const STATUS_VERIFY_REQUESTED = 1;
    const STATUS_VERIFIED = 2;

    const STATUS_FILTER_PASSED = 3;
    const STATUS_FILTER_FAILED = 4;

    const STATUS_SENDER_WHITELISTED = 5;
    const STATUS_SENDER_BLACKLISTED = 6;

    const TABLE_NAME = 'messages';

    /**
     * @var \Database
     */
    private $db;

    public function __construct(\Database $db)
    {
        $this->db = $db;
    }

    public function getById($id)
    {
        $query = "SELECT * FROM `" . self::TABLE_NAME ."` WHERE `id` = :id LIMIT 1;";
        
        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':id', $id, \PDO::PARAM_INT);
        $st->execute();
        
        $message = $st->fetch();
        
        $st->closeCursor();
        
        return $message !== false ? $message : null;
    }

    public function findByUserAndSender($userId, $sender)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `user_id` = :user_id AND `from_email` = :sender';
        $messages = $this->db->getArray($query, array('user_id' => $userId, 'sender' => $sender));
        return $messages ? $messages : array();
    }

    public function findByUser($userId, $limit = -1, $offset = -1)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `user_id` = :user_id';
        $params = ['user_id' => $userId];

        if ($limit != -1) {
            $query .= ' LIMIT :limit';
            $params['limit'] = $limit;

            if ($offset != -1) {
                $query .= ' OFFSET :offset';
                $params['offset'] = $offset;
            }
        }

        $messages = $this->db->getArray($query, $params);
        return $messages ? $messages : array();
    }
}
