<?php

namespace Access2Me\Helper;

use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\ProfileProvider;
use Access2Me\Service\Service;
use Access2Me\Data;

class Registry
{
    public static $appConfig;

    public static function setUp($appConfig)
    {
        self::$appConfig = $appConfig;
    }

    private static $profileProvider;

    /**
     * @return \Access2Me\Helper\SenderProfileProviderInterface
     */
    public static function getProfileProvider()
    {
        if (!self::$profileProvider) {
            $services = self::$appConfig['services'];
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
                ],
                Service::KLOUT => [
                    'authRequired' => false,
                    'provider' => new ProfileProvider\Klout($services['klout'])
                ],
                Service::GITHUB => [
                    'authRequired' => false,
                    'provider' => new ProfileProvider\GitHub(null)
                ]
            ];

            $db = new \Database();
            $profileProvider = new SenderProfileProvider($profileProviders);

            $cacheRepo = new Model\CacheRepository($db);
            $cache = new Helper\Cache($cacheRepo);
            $cached = new CachedSenderProfileProvider($cache, $profileProvider);

            self::$profileProvider = new NormalizedSenderProfileProvider($cached);
        }

        return self::$profileProvider;
    }

    public static function getUserStats($user)
    {
        $db = new \Database();
        $cache = new Helper\Cache(new Model\CacheRepository($db), 'PT2H');

        $userRepo = new Model\UserRepository($db);
        $authProvider = new Helper\GoogleAuthProvider(
            self::$appConfig['services']['gmail'],
            $userRepo
        );

        $stats = new Data\UserStats($user, $cache);
        $stats->addResource(new Data\UserStats\GmailContactsCount($authProvider));
        $stats->addResource(new Data\UserStats\VerifiedSendersCount(new Model\SenderRepository($db)));
        $stats->addResource(new Data\UserStats\FiltersCount($db));
        $stats->addResource(new Data\UserStats\GmailMessagesCount($authProvider));
        
        return $stats;
    }

    /**
     * @var \Twig_Environment
     */
    private static $twig = null;

    /**
     * @return \Twig_Environment
     */
    public static function getTwig()
    {
        if (!self::$twig) {
            $loader = new \Twig_Loader_Filesystem(self::$appConfig['projectPath'] . '/views');
            
            $env['cache'] = self::$appConfig['twigCache'] ? self::$appConfig['projectPath'] . '/tmp/cache/twig' : false;
            self::$twig = new \Twig_Environment($loader, $env);
            self::$twig->addFunction(new \Twig_SimpleFunction('gender_icon', ['\Access2Me\Helper\Template', 'getGenderIcon']));
            self::$twig->addFunction(new \Twig_SimpleFunction('messenger_icon', ['\Access2Me\Helper\Template', 'getMessengerIcon']));
            self::$twig->addFunction(new \Twig_SimpleFunction('format_money', ['\Access2Me\Helper\Template', 'formatMoney']));
            self::$twig->addFunction(new \Twig_SimpleFunction('service_icon', ['\Access2Me\Helper\Template', 'getServiceIcon']));
            self::$twig->addFunction(new \Twig_SimpleFunction('social_icon', ['\Access2Me\Helper\Template', 'getSocialIcon']));
            self::$twig->addFunction(new \Twig_SimpleFunction('twitter_profile_url', ['\Access2Me\Helper\Template', 'getTwitterProfileUrl']));
        }
        
        return self::$twig;
    }

    /**
     * @var Router
     */
    private static $router = null;

    /**
     * @return Router
     */
    public static function getRouter()
    {
        if (!self::$router) {
           self::$router = new Router(self::$appConfig);
        }

        return self::$router;
    }

    public static function getDefaultMailer()
    {
        $mailer = new \PHPMailer();
        $mailer->isSMTP();
        $mailer->Host = self::$appConfig['smtp']['host'];
        $mailer->SMTPAuth = self::$appConfig['smtp']['auth'];

        if ($mailer->SMTPAuth) {
            $mailer->Username = self::$appConfig['smtp']['username'];
            $mailer->Password = self::$appConfig['smtp']['password'];
        }

        $mailer->SMTPSecure = self::$appConfig['smtp']['encryption'];
        $mailer->Port = self::$appConfig['smtp']['port'];
        $mailer->Hostname = 'access2.me';

        $mailer->isHTML(true);

        $mailer->From = self::$appConfig['email']['no_reply'];
        $mailer->FromName = 'Access2.ME';

        return $mailer;
    }
}
