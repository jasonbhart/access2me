<?php

session_start();

date_default_timezone_set('America/Los_Angeles');

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/imap.php";
require_once __DIR__ . "/filter.php";
require_once __DIR__ . "/logging.php";
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/Data/UserStats.php";
require_once __DIR__ . "/Data/UserStats/ResourceInterface.php";
require_once __DIR__ . "/Data/UserStats/FiltersCount.php";
require_once __DIR__ . "/Data/UserStats/GmailResource.php";
require_once __DIR__ . "/Data/UserStats/GmailContactsCount.php";
require_once __DIR__ . "/Data/UserStats/GmailMessagesCount.php";
require_once __DIR__ . "/Data/UserStats/VerifiedSendersCount.php";
require_once __DIR__ . "/helper/Auth.php";
require_once __DIR__ . "/helper/AuthTokenManager.php";
require_once __DIR__ . "/helper/Cache.php";
require_once __DIR__ . "/helper/DataConverter.php";
require_once __DIR__ . "/helper/DateTime.php";
require_once __DIR__ . "/helper/GmailImap.php";
require_once __DIR__ . "/helper/Google.php";
require_once __DIR__ . "/helper/Email.php";
require_once __DIR__ . "/helper/Facebook.php";
require_once __DIR__ . "/helper/FlashMessages.php";
require_once __DIR__ . "/helper/Http.php";
require_once __DIR__ . "/helper/Linkedin.php";
require_once __DIR__ . "/helper/MessageProcessor.php";
require_once __DIR__ . "/helper/Twitter.php";
require_once __DIR__ . "/helper/Pager.php";
require_once __DIR__ . "/helper/ProfileCombiner.php";
require_once __DIR__ . "/helper/Registry.php";
require_once __DIR__ . "/helper/Router.php";
require_once __DIR__ . "/helper/SenderAuthentication.php";
require_once __DIR__ . "/helper/SenderProfileProvider.php";
require_once __DIR__ . "/helper/Template.php";
require_once __DIR__ . "/helper/UserListProvider.php";
require_once __DIR__ . "/helper/UserListTokenManager.php";
require_once __DIR__ . "/helper/Validator.php";
require_once __DIR__ . "/model/AbstractRepository.php";
require_once __DIR__ . "/model/AuthTokenRepository.php";
require_once __DIR__ . "/model/CacheRepository.php";
require_once __DIR__ . "/model/MessageRepository.php";
require_once __DIR__ . "/model/SenderRepository.php";
require_once __DIR__ . "/model/User/LinkedinToken.php";
require_once __DIR__ . "/model/UserRepository.php";
require_once __DIR__ . "/model/UserSenderRepository.php";
require_once __DIR__ . "/model/Cache.php";
require_once __DIR__ . "/model/Roles.php";
require_once __DIR__ . "/model/Sender.php";
require_once __DIR__ . "/model/Profile/Profile.php";
require_once __DIR__ . "/model/Profile/Position.php";
require_once __DIR__ . "/model/Profile/ProfileRepository.php";
require_once __DIR__ . "/ProfileProvider/ProfileProviderInterface.php";
require_once __DIR__ . "/ProfileProvider/ProfileProviderException.php";
require_once __DIR__ . "/ProfileProvider/AngelList.php";
require_once __DIR__ . "/ProfileProvider/CrunchBase.php";
require_once __DIR__ . "/ProfileProvider/Facebook.php";
require_once __DIR__ . "/ProfileProvider/FullContact.php";
require_once __DIR__ . "/ProfileProvider/GitHub.php";
require_once __DIR__ . "/ProfileProvider/Klout.php";
require_once __DIR__ . "/ProfileProvider/Aboutme.php";
require_once __DIR__ . "/ProfileProvider/Linkedin.php";
require_once __DIR__ . "/ProfileProvider/Twitter.php";
require_once __DIR__ . "/ProfileProvider/Profile/CrunchBase.php";
require_once __DIR__ . "/ProfileProvider/Profile/Facebook.php";
require_once __DIR__ . "/ProfileProvider/Profile/FullContact.php";
require_once __DIR__ . "/ProfileProvider/Profile/GitHub.php";
require_once __DIR__ . "/Service/Auth/Linkedin.php";
require_once __DIR__ . "/Service/Auth/AbstractHandler.php";
require_once __DIR__ . "/Service/Auth/Linkedin/AuthException.php";
require_once __DIR__ . "/Service/Auth/Linkedin/SenderAuthHandler.php";
require_once __DIR__ . "/Service/Auth/Linkedin/SenderAuthRequest.php";
require_once __DIR__ . "/Service/Auth/Linkedin/UserAuthHandler.php";
require_once __DIR__ . "/Service/Auth/Linkedin/UserAuthRequest.php";
require_once __DIR__ . "/Service/GitHub/Search.php";
require_once __DIR__ . "/Service/Google/Contacts.php";
require_once __DIR__ . "/Service/AngelList.php";
require_once __DIR__ . "/Service/CrunchBase.php";
require_once __DIR__ . "/Service/FullContact.php";
require_once __DIR__ . "/Service/Klout.php";
require_once __DIR__ . "/Service/Aboutme.php";
require_once __DIR__ . "/Service/Gmail.php";
require_once __DIR__ . "/Service/Service.php";
require_once __DIR__ . "/Service/TokenRefresher.php";


if (getenv('DOM_DEV_MACHINE')) {
    $localUrl = 'http://localhost/a2m';
} else {
    $localUrl = 'http://app.access2.me';
}

$facebookAuth = array(
    'appId'       => '325592287614687',
    'appSecret'   => 'e62adb004e674e52c2ab4039a973a97d',
    'redirect'    => $localUrl . '/facebook.php',
    'permissions' => array(
        'public_profile',
        'email',
//        'user_about_me',
//        'user_birthday',
//        'user_location',
//        'user_website',
//        'user_work_history'
    )
);

$linkedinAuth = [
    'clientId'     => '75dl362rayg47t',
    'clientSecret' => 'eCxKfjOpunoO9rSj',
    'permissions' => [
        'r_basicprofile',
        'r_contactinfo',
    ],
    'callback_url' => $localUrl . '/linkedin.php'
];

$twitterAuth = array(
    'consumer_key' => 'gEBjjVorzsmQy4Jar9TpM9NJ2',
    'consumer_secret' => 'Kkon0Upg19osKOOskjsSw8ZpCZDNLlp72hfVyXNpLEEvhZu9To',
    'callback_url' => 'http://app.access2.me/twitter.php',
    //'user_agent' => 'access2.me'
);

$appConfig = array(
    'projectRoot' => __DIR__,
    'projectUrl' => $localUrl,
    'imap' => [
        'host'     => 'mail.access2.me',
        'user'     => 'catchall@access2.me',
        'password' => 'catch123'
    ],
    'smtp' => [
        'host' => 'mail.access2.me',
        'port' => 587,
        'encryption' => 'tls',
        'auth' => true,
        'username' => 'noreply@access2.me',
        'password' => 'access123'
    ],
    'email' => [
        'no_reply' => 'noreply@access2.me'
    ],
    'services' => [
        'gmail' => [
            'client_id' => '523467224320-5evqo2ovdnqqntulu3531298cp8hfh12.apps.googleusercontent.com',
            'client_secret' => '8s74XEEucknNhYb6keO0yzBw'
        ],
        'linkedin' => $linkedinAuth,
        'facebook' => $facebookAuth,
        'twitter' => $twitterAuth,
        'crunchbase' => [
            'user_key' => '5591b541c1ff22d43b11695082f85cfb'
        ],
        'fullcontact' => [
            'api_key' => 'a552145e97bce5af'
        ],
        'klout' => [
            'key'   => 'w96zwz37wd9x6h2n8wfcpcam'
        ],
        'aboutme' => [
            'client_id' => '718b5491196ddd8a0fbb9aa3f759665ce9753b04'
        ]
    ],
    'secret' => 'rO2RL7H0mWeFs5.zRF1yOx9ITgFo9rwkTbx3Q6YuyA/7BjjH6t5AoSfsd32kL9h1UKs',
    'dateTimeFormat' => 'Y-m-d H:i:s',
    'twigCache' => true,
    'paging' => [
        'items_per_page' => 10
    ]
);


if (file_exists(__DIR__ . '/boot.local.php')) {
    require_once __DIR__ . '/boot.local.php';
}

Access2Me\Helper\Registry::setUp($appConfig);
