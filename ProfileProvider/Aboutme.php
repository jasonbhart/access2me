<?php

namespace Access2Me\ProfileProvider;

use Access2Me\Service;

class Aboutme implements ProfileProviderInterface
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
     * @return array|object
     */
    public function fetchProfile(\Access2Me\Model\Sender $sender, array $dependencies = [])
    {
        try {
            $email = $sender->getSender();

            $aboutme = new Service\Aboutme($this->serviceConfig);
            $profile = $aboutme->getAboutMeProfile($email);
            
            return $profile;
        } catch (Service\AboutmeException $ex) {
            throw new ProfileProviderException('Can\'t fetch profile', 0, $ex);
        }
    }
}
