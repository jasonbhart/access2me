<?php

namespace Access2Me\ProfileProvider;

use Facebook\FacebookSession;
use Facebook\FacebookSDKException;
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
    public function fetchProfile(\Access2Me\Model\Sender $sender, array $dependencies = [])
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

        } catch (FacebookSDKException $ex) {
            throw new ProfileProviderException('Can\'t fetch profile', 0, $ex);
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
