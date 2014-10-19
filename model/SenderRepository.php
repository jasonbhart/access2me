<?php

namespace Access2Me\Model;

class SenderRepository
{
    const SERVICE_LINKEDIN = 1;
    const SERVICE_FACEBOOK = 2;
    const SERVICE_TWITTER  = 3;

    const TABLE_NAME = 'senders';

    private $db;

    public function __construct(\Database $db)
    {
        $this->db = $db;
    }

    /**
     * Returns list of sender authenticated services
     * 
     * @param string $email
     * @return array
     */
    public function getByEmail($email)
    {
        $query = "SELECT * FROM `" . self::TABLE_NAME ."` WHERE `sender` = :email ";
        
        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':email', $email);
        $st->execute();
        $sender = $st->fetchAll();
        
        return $sender;
    }

    /**
     * Returns sender or null 
     * 
     * @param string $email
     * @param SenderRepository::SERVICE_* $service
     * @return array|null
     */
    public function getByEmailAndService($email, $service)
    {
        $query = "SELECT * FROM `" . self::TABLE_NAME ."` WHERE `sender` = :email "
            . "AND `service` = :service LIMIT 1;";

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':email', $email);
        $st->bindValue(':service', $service, \PDO::PARAM_INT);
        $st->execute();
        $sender = $st->fetch();
        $st->closeCursor();
        
        return $sender !== false ? $sender : null;
    }

    public function insert($sender)
    {
        $this->db->insert(
            self::TABLE_NAME,
            array(
                'sender',
                'service',
                'oauth_key'
            ),
            array(
                $sender['sender'],
                $sender['service'],
                $sender['oauth_key']
            ),
            true
        );
    }
}
