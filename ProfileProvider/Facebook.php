<?php

namespace Access2Me\ProfileProvider;

use Facebook\FacebookSession;
use Facebook\FacebookRequestException;
use Access2Me\Helper;

class Facebook implements ProfileProviderInterface
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
        try {
            // initialize facebook session
            FacebookSession::setDefaultApplication(
                $this->serviceConfig['appId'],
                $this->serviceConfig['appSecret']
            );
            $facebook = new Helper\Facebook($sender->getOAuthKey());

            // validate session
            $facebook->validate();

            // get sender profile
            $profile = $facebook->getProfile();

            return $profile;

        } catch (FacebookRequestException $ex) {
            \Logging::getLogger()->error(
                $ex->getMessage(),
                array('exception' => $ex)
            );
            return false;
        }
    }
}
