<?php

namespace Access2Me\ProfileProvider;

use Access2Me\Helper;

class Twitter implements ProfileProviderInterface
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
        $twitter = new Helper\Twitter($this->serviceConfig);
        try {
            $profile = $twitter->getProfile($sender->getOAuthKey());
            return $profile;
        } catch (Helper\TwitterException $ex) {
            \Logging::getLogger()->error(
                $ex->getMessage(),
                array('exception' => $ex)
            );

            return false;
        }
    }
}
