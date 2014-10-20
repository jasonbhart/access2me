<?php

namespace Access2Me\ProfileProvider;

use Access2Me\Helper;

class Linkedin implements ProfileProviderInterface
{
    private $serviceConfig;

    /**
     * @param array $serviceConfig
     */
    public function __construct($serviceConfig)
    {
        $this->serviceConfig = $serviceConfig;
    }

    /**
     * @param \Access2Me\Model\Sender $sender
     * @return array
     */
    public function fetchProfile($sender)
    {
        $linkedin = new Helper\Linkedin($this->serviceConfig);
        $profile = $linkedin->getProfile($sender->getOAuthKey());
        
        return $profile;
    }
}
