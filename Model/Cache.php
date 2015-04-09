<?php

namespace Access2Me\Model;

class Cache
{
    private $id;

    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var \DateTime 
     */
    private $expires_at;

    public function getId()
    {
        return $this->id;
    }
    
    public function getKey()
    {
        return $this->key;
    }
    
    public function setKey($key)
    {
        $this->key = $key;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function setValue($value)
    {
        $this->value = $value;
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
