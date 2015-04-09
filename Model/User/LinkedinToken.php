<?php

namespace Access2Me\Model\User;


class LinkedinToken
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $expiresAt;

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token string
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param $createdAt string
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @param $expiresAt string
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;
    }
}
