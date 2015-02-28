<?php

namespace Access2Me\ProfileProvider;

use Access2Me\Service;
use Access2Me\Helper;

class Klout implements ProfileProviderInterface
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
    public function fetchProfile(\Access2Me\Model\Sender $sender)
    {
        try {
            
            if ($sender->getService() == Service\Service::TWITTER) {
                // get twitter profile data
                $twitter = new Helper\Twitter(Helper\Registry::$appConfig['services']['twitter']);
                $twitterData = $twitter->getUserRepresentation($sender->getOAuthKey());
                $twitterId = $twitterData['id'];
                
                $klout = new Service\Klout($this->serviceConfig);
                $profile = $klout->getScore($twitterId);

                return $profile;
            } else {
                return false;
            }
        } catch (Service\KloutException $ex) {
            throw new ProfileProviderException('Can\'t fetch profile', 0, $ex);
        }
    }
}
