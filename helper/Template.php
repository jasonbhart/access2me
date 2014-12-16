<?php

namespace Access2Me\Helper;

use Access2Me\Service\Service;

class Template
{
    public static function getServiceImage($serviceId)
    {
        $images = array(
            Service::LINKEDIN => '16-linkedin.png',
            Service::FACEBOOK => '16-facebook.png',
            Service::TWITTER => '16-twitter.png'
        );

        if (!isset($images[$serviceId])) {
            throw new \Exception('Unknown service');
        }
        
        return $images[$serviceId];
    }
}
