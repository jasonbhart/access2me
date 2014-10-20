<?php

namespace Access2Me\Model;

/**
 * Description of Sender
 *
 * @author yup
 */
class Sender
{
    private $sender;
    private $service;
    private $oauth_key;
    private $profile;

    /**
     * @var \DateTime
     */
    private $profile_date;

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

    public function getProfile()
    {
        return $this->profile;
    }

    public function setProfile($profile)
    {
        $this->profile = $profile;
    }

    /**
     * @return \DateTime
     */
    public function getProfileDate()
    {
        return $this->profile_date;
    }

    public function setProfileDate($profileDate)
    {
        $this->profile_date = $profileDate;
    }
}
