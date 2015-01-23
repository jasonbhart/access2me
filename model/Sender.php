<?php

namespace Access2Me\Model;

/**
 * Description of Sender

 * @todo This needs to be renamed to SenderService
 *      as it actually links sender with authenticated services
 */
class Sender
{
    private $id;
    private $sender;
    private $service;
    private $oauth_key;
    private $created_at;
    private $expires_at;

    public function getId()
    {
        return $this->id;
    }
    
    public function getSender()
    {
        return $this->sender;
    }
    
    public function  setSender($sender)
    {
        $this->sender = $sender;
    }
    
    public function getService()
    {
        return $this->service;
    }
    
    public function setService($service)
    {
        $this->service = $service;
    }

    public function getOAuthKey()
    {
        return $this->oauth_key;
    }

    public function setOAuthKey($oauthKey)
    {
        $this->oauth_key = $oauthKey;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }
    
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }
    
    public function setExpiresAt($expiresAt)
    {
        $this->expires_at = $expiresAt;
    }
}
