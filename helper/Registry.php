<?php

namespace Access2Me\Helper;

use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\ProfileProvider;
use Access2Me\Service\Service;
use Access2Me\Data;

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
            Service::CRUNCHBASE => [
                'authRequired' => false,
                'provider' => new ProfileProvider\CrunchBase($services['crunchbase'])
            ],
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

    public static function getUserStats($userId)
    {
        $db = new \Database();
        $cache = new Helper\Cache(new Model\CacheRepository($db));
        $stats = new Data\UserStats($userId, $cache);
        $stats->addResource(new Data\UserStats\ContactsCount(new Model\SenderRepository($db)));
        $stats->addResource(new Data\UserStats\InvitesCount());
        $stats->addResource(new Data\UserStats\FiltersCount($db));
        $stats->addResource(new Data\UserStats\MessagesCount(new Model\MessageRepository($db)));
        
        return $stats;
    }
}
