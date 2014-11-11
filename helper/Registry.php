<?php

namespace Access2Me\Helper;

use Access2Me\Model;
use Access2Me\ProfileProvider;

class Registry
{

    private static $profileProvider;

    public static function setUp($appConfig)
    {
        $services = $appConfig['services'];
        $profileProviders = array(
            Model\SenderRepository::SERVICE_LINKEDIN => new ProfileProvider\Linkedin($services['linkedin']),
            Model\SenderRepository::SERVICE_FACEBOOK => new ProfileProvider\Facebook($services['facebook']),
            Model\SenderRepository::SERVICE_TWITTER => new ProfileProvider\Twitter($services['twitter'])
        );

        self::$profileProvider = new SenderProfileProvider($profileProviders);
    }
    
    public static function getProfileProvider()
    {
        return self::$profileProvider;
    }
}
