<?php

namespace Access2Me\ProfileProvider;

use Facebook\FacebookSession;
use Facebook\FacebookRequestException;
use Access2Me\Helper;
use Access2Me\Model\Profile;

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
            $profile = $this->buildProfile($facebook);

            return $profile;

        } catch (FacebookRequestException $ex) {
            \Logging::getLogger()->error(
                $ex->getMessage(),
                array('exception' => $ex)
            );
            return false;
        }
    }

    protected function buildProfile($fbHelper)
    {
        // get profile data
        $gobject = $fbHelper->getProfile();

        $profile = new Profile\Profile();
        $profile->fullName = $gobject->getProperty('name');
        $profile->email = $gobject->getProperty('email');
        $profile->biography = $gobject->getProperty('bio');
        $profile->birthday = $gobject->getProperty('birthday');
        $profile->gender = $gobject->getProperty('gender');
        $profile->profileUrl = $gobject->getProperty('link');
        $profile->website = $gobject->getProperty('website');

        $profile->pictureUrl = $fbHelper->getPictureUrl();

        $location = $gobject->getProperty('location');
        if ($location) {
            $profile->location = Helper\Facebook::formatLocation($location);
        }
        
        $work = $gobject->getProperty('work');
        if ($work) {
            $position = new Profile\Position();
            $position->summary = $work;
            $profile->positions[] = $position;
        }
        
        return $profile;
    }
}
