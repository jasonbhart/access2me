<?php

namespace Access2Me\Helper;

use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\ProfileProvider;
use Access2Me\Service\Service;

class Registry
{

    private static $profileProvider;

    public static function setUp($appConfig)
    {
        $services = $appConfig['services'];
        $profileProviders = [
            Service::LINKEDIN => [
                'authRequired' => true,
                'provider' => new ProfileProvider\Linkedin($services['linkedin'])
            ],
            Service::FACEBOOK => [
                'authRequired' => true,
                'provider' => new ProfileProvider\Facebook($services['facebook'])
            ],
            Service::TWITTER => [
                'authRequired' => true,
                'provider' => new ProfileProvider\Twitter($services['twitter'])
            ],
            /*
             * Service::CRUNCHBASE => [
             *  'authRequired' => false
             * ],
             */
            Service::ANGELLIST => [
                'authRequired' => false,
                'provider' => new ProfileProvider\AngelList(null)
            ],
            Service::FULLCONTACT => [
                'authRequired' => false,
                'provider' => new ProfileProvider\FullContact($services['fullcontact'])
            ]
        ];

        $db = new \Database();
        $profileProvider = new SenderProfileProvider($profileProviders);
        
        $cacheRepo = new Model\CacheRepository($db);
        $cache = new Helper\Cache($cacheRepo);
        $cached = new CachedSenderProfileProvider($cache, $profileProvider);

        self::$profileProvider = new NormalizedSenderProfileProvider($cached);
    }

    /**
     * @return \Access2Me\Helper\SenderProfileProviderInterface
     */
    public static function getProfileProvider()
    {
        return self::$profileProvider;
    }
}
