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
     * OAuthKey is stored in json format because
     * Facebook's auth token is string and Twitter's token consists of
     * two values so it is array.
     * 
     * @param string|array $oauthKey
     */
    protected function encodeOAuthKey($oauthKey)
    {
        // store null values as NULL in databases instead of json "null"
        if ($oauthKey === null) {
            return null;
        }

        return json_encode($oauthKey);
    }

    protected function decodeOAuthKey($encodedOAuthKey)
    {
        return json_decode($encodedOAuthKey, true);
    }

     /**
     * Encodes profile to be stored in database
     * 
     * @param string|array $profile
     */
    protected function encodeProfile($profile)
    {
        // store null values as NULL in databases instead of json "null"
        if ($profile === null) {
            return null;
        }

        return serialize($profile);
    }

    protected function decodeProfile($profile)
    {
        $data = unserialize($profile);
        return $data === false ? null : $data;
    }

    protected function encodeProfileDate($dt)
    {
        return $dt instanceof \DateTime ? $dt->format('Y-m-d H:i:s') : null;
    }

    protected function decodeProfileDate($dt)
    {
        try {
            return !empty($dt) ? new \DateTime($dt) : null;
        } catch (\Exception $ex) {
            return null;
        }
    }
    
    /**
     * Helper method to decode values in all passed objects
     * 
     * @param Sender|Sender[] $senders
     */
    protected function decodeSenders($senders)
    {
        if ($senders === false) {
            return;
        }

        if (is_object($senders)) {
            $senders->setOAuthKey($this->decodeOAuthKey($senders->getOAuthKey()));
            $senders->setProfile($this->decodeProfile($senders->getProfile()));
            $senders->setProfileDate($this->decodeProfileDate($senders->getProfileDate()));
        } else {
            foreach ($senders as $sender) {
                $sender->setOAuthKey($this->decodeOAuthKey($sender->getOAuthKey()));
                $sender->setProfile($this->decodeProfile($sender->getProfile()));
                $sender->setProfileDate($this->decodeProfileDate($sender->getProfileDate()));
            }
        }
    }

    /**
     * Returns list of sender authenticated services
     * 
     * @param string $email
     * @return Sender[]
     */
    public function getByEmail($email)
    {
        $query = "SELECT * FROM `" . self::TABLE_NAME ."` WHERE `sender` = :email ";
        
        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->setFetchMode(\PDO::FETCH_CLASS, '\\Access2Me\\Model\\Sender');
        $st->bindValue(':email', $email);
        $st->execute();
        $senders = $st->fetchAll();

        $this->decodeSenders($senders);
        
        return $senders;
    }

    /**
     * Returns sender or null 
     * 
     * @param string $email
     * @param SenderRepository::SERVICE_* $service
     * @return Sender|null
     */
    public function getByEmailAndService($email, $service)
    {
        $query = "SELECT * FROM `" . self::TABLE_NAME ."` WHERE `sender` = :email "
            . "AND `service` = :service LIMIT 1;";

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->setFetchMode(\PDO::FETCH_CLASS, '\\Access2Me\\Model\\Sender');
        $st->bindValue(':email', $email);
        $st->bindValue(':service', $service, \PDO::PARAM_INT);
        $st->execute();
        $sender = $st->fetch();
        $st->closeCursor();
        
        $this->decodeSenders($sender);

        return $sender !== false ? $sender : null;
    }

    /**
     * @param Sender $sender
     */
    public function insert($sender)
    {
        $this->db->insert(
            self::TABLE_NAME,
            array(
                'sender',
                'service',
                'oauth_key',
                'profile',
                'profile_date'
            ),
            array(
                $sender->getSender(),
                $sender->getService(),
                $this->encodeOAuthKey($sender->getOAuthKey()),
                $this->encodeProfile($sender->getProfile()),
                $this->encodeProfileDate($sender->getProfileDate())
            ),
            true
        );
    }

    /**
     * @param Sender $sender
     */
    public function update($sender)
    {
        $query = "UPDATE `" . self::TABLE_NAME ."`"
            . ' SET'
            . ' sender = :sender,'
            . ' service = :service,'
            . ' oauth_key = :oauth_key,'
            . ' profile = :profile,'
            . ' profile_date = :profile_date'
            . ' WHERE id = :id';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':id', $sender->getId(), \PDO::PARAM_INT);
        $st->bindValue(':oauth_key', $this->encodeOAuthKey($sender->getOAuthKey()));
        $st->bindValue(':profile', $this->encodeProfile($sender->getProfile()));
        $st->bindValue(':profile_date', $this->encodeProfileDate($sender->getProfileDate()));
        $st->bindValue(':sender', $sender->getSender());
        $st->bindValue(':service', $sender->getService(), \PDO::PARAM_INT);
        $st->execute();
    }

    public function save($sender)
    {
        if ($sender->getId() === null) {
            $this->insert($sender);
        } else {
            $this->update($sender);
        }
    }
}
