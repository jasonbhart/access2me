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
    public function fetchProfile(\Access2Me\Model\Sender $sender, array $dependencies = [])
    {
        try {
            $klout = new Service\Klout($this->serviceConfig);
            
            if (isset($dependencies[Service\Service::TWITTER])) {
                $servId = $dependencies[Service\Service::TWITTER]->id;
                $kloutId = $klout->getKloutId($servId, Service\Klout::NETWORK_TWITTER);
            } elseif (isset($dependencies[Service\Service::GOOGLE])) {
                $servId = $dependencies[Service\Service::GOOGLE]->id;
                $kloutId = $klout->getKloutId($servId, Service\Klout::NETWORK_GOOGLE);
            }
            
            if (isset($kloutId)) {
                $score = $klout->getScore($kloutId);
                $profile = $this->convertToProfile($kloutId, $score);
                return $profile;
            }

            return null;
        } catch (Service\KloutException $ex) {
            throw new ProfileProviderException('Can\'t fetch profile', 0, $ex);
        }
    }
    
    protected function convertToProfile($kloutId, $score)
    {
        $profile = new Profile\Klout();
        $profile->id = $kloutId;
        $profile->score = $score['score'];
        $profile->scoreDelta = $score['scoreDelta'];
        
        return $profile;
    }
}
