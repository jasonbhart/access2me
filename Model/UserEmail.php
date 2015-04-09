<?php

namespace Access2Me\Model;


class UserEmail
{
    private $id;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var string
     */
    private $email;

    public function getId()
    {
        return $this->id;
    }
    
    public function getUserId()
    {
        return $this->user_id;
    }
    
    public function  setUserId($userId)
    {
        $this->user_id = $userId;
    }
    
    public function getEmail()
    {
        return $this->email;
    }
    
    public function setEmail($email)
    {
        $this->email = $email;
    }
}
