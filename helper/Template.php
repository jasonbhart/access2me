<?php

namespace Access2Me\Helper;

use Access2Me\Service\Service;

class Template
{
    private static $baseUrl = 'http://app.access2.me/images/';
    
    private static $serviceIcons = [
        Service::LINKEDIN => '16-linkedin.png',
        Service::FACEBOOK => '16-facebook.png',
        Service::TWITTER => '16-twitter.png'
    ];
    
    private static $messengerIcons = [
        'gtalk' => 'gtalk.png',
        'skype' => 'skype.png'
    ];

    private static $socialIcons = [
        'googleplus' => 'googleplus.png',
        'googleprofile' => 'googleprofile.png',
        'foursquare' => 'foursquare.png',
        'flickr' => 'flickr.png',
        'picasa' => 'picasa.png',
        'klout' => 'klout.png'
    ];

    public static function getServiceIcon($serviceId)
    {
        if (!isset(self::$serviceIcons[$serviceId])) {
            throw new \Exception('Unknown service');
        }

        return self::$baseUrl . self::$serviceIcons[$serviceId];
    }

    public static function getMessengerIcon($messenger)
    {
        $icon = isset(self::$messengerIcons[$messenger])
            ? self::$messengerIcons[$messenger] : 'default.png';
        
        return self::$baseUrl . 'messengers/' . $icon;
    }

    public static function getSocialIcon($social)
    {
        $icon = isset(self::$socialIcons[$social])
            ? self::$socialIcons[$social] : 'default.png';
        
        return self::$baseUrl . 'socials/' . $icon;
    }

    public static function formatMoney($money)
    {
        $suffixes = ['', 'K', 'M', 'B', 'T'];
        $base = 1000;

        $l = (int)log($money, $base);
        
        if ($l >= 0 && $l < count($suffixes)) {
            $money /= pow($base, $l);
            
            $result = round($money, 2);
            $result .= $suffixes[$l];
        } else {
            $result = $money;
        }

        return (string)$result;
    }
}
