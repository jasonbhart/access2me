<?php

namespace Access2Me\Helper;

use Access2Me\Helper\Twitter;
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
        '500px' => '500px.png',
        'aboutme' => 'aboutme.png',
        'adn' => 'adn.png',
        'aim' => 'aim.png',
        'amazon' => 'amazon.png',
        'angellist' => 'angellist.png',
        'bbcid' => 'bbcid.png',
        'behance' => 'behance.png',
        'creativecommons' => 'creativecommons.png',
        'default' => 'default.png',
        'delicious' => 'delicious.png',
        'deviantart' => 'deviantart.png',
        'digg' => 'digg.png',
        'dribbble' => 'dribbble.png',
        'email' => 'email.png',
        'etsy' => 'etsy.png',
        'facebook' => 'facebook.png',
        'feed' => 'feed.png',
        'ffffound' => 'ffffound.png',
        'flickr' => 'flickr.png',
        'forrst' => 'forrst.png',
        'foursquare' => 'foursquare.png',
        'geotag' => 'geotag.png',
        'getsatisfaction' => 'getsatisfaction.png',
        'github' => 'github.png',
        'goodreads' => 'goodreads.png',
        'googleplus' => 'googleplus.png',
        'googleprofile' => 'googleprofile.png',
        'gravatar' => 'gravatar.png',
        'huffduffer' => 'huffduffer.png',
        'identica' => 'identica.png',
        'imdb' => 'imdb.png',
        'instagram' => 'instagram.png',
        'klout' => 'klout.png',
        'lanyrd' => 'lanyrd.png',
        'lastfm' => 'lastfm.png',
        'linkedin' => 'linkedin.png',
        'meetup' => 'meetup.png',
        'microsoft' => 'microsoft.png',
        'myspace' => 'myspace.png',
        'newsvine' => 'newsvine.png',
        'nikeplus' => 'nikeplus.png',
        'orkut' => 'orkut.png',
        'picasa' => 'picasa.png',
        'pinboard' => 'pinboard.png',
        'pinterest' => 'pinterest.png',
        'quora' => 'quora.png',
        'rdio' => 'rdio.png',
        'readability' => 'readability.png',
        'readernaut' => 'readernaut.png',
        'reddit' => 'reddit.png',
        'share' => 'share.png',
        'skype' => 'skype.png',
        'slideshare' => 'slideshare.png',
        'soundcloud' => 'soundcloud.png',
        'speakerdeck' => 'speakerdeck.png',
        'spotify' => 'spotify.png',
        'stackoverflow' => 'stackoverflow.png',
        'stumbleupon' => 'stumbleupon.png',
        'thisismyjam' => 'thisismyjam.png',
        'tumblr' => 'tumblr.png',
        'twitter' => 'twitter.png',
        'vcard' => 'vcard.png',
        'vimeo' => 'vimeo.png',
        'website' => 'website.png',
        'wikipedia' => 'wikipedia.png',
        'xbox' => 'xbox.png',
        'xing' => 'xing.png',
        'yahoo' => 'yahoo.png',
        'yelp' => 'yelp.png',
        'youtube' => 'youtube.png',
        'zerply' => 'zerply.png',
    ];

    public static function getGenderIcon($gender)
    {
        $gender = strtolower($gender);
        if ($gender == 'male') {
            return self::$baseUrl . 'male.png';
        } else if ($gender == 'female') {
            return self::$baseUrl . 'female.png';
        }

        throw new \Exception('Unknown gender');
    }

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

    public static function getTwitterProfileUrl($userId)
    {
        return Twitter::getProfileUrl($userId);
    }

    /**
     * 
     * @deprecated please use `Registry::getTwig()->render()`
     * @param type $template
     * @param type $data
     * @return type
     */
    public static function generate($template, $data = null)
    {
        if ($data !== null) {
            extract($data);
        }

        ob_start();
        include __DIR__ . '/../views/' . $template;
        return ob_get_clean();
    }

    /**
     * Renders template using Twig
     *
     * @deprecated please use `Registry::getTwig()->render()`
     * @param string $template
     * @param array $data
     * @return string
     */
    public static function render($template, $data = null)
    {
        return Registry::getTwig()->render($template, $data);
    }
}
