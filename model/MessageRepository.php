<?php

namespace Access2Me\Model;

class MessageRepository
{
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

    public function getByUserAndSender($userId, $sender)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `user_id` = :user_id AND `from_email` = :sender';
        $messages = $this->db->getArray($query, array('user_id' => $userId, 'sender' => $sender));
        return $messages ? $messages : array();
    }
}
