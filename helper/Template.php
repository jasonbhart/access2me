<?php

namespace Access2Me\Helper;

use Access2Me\Model\SenderRepository;

class Template
{
    public static function getServiceImage($serviceId)
    {
        $images = array(
            SenderRepository::SERVICE_LINKEDIN => '16-linkedin.png',
            SenderRepository::SERVICE_FACEBOOK => '16-facebook.png',
            SenderRepository::SERVICE_TWITTER => '16-twitter.png'
        );

        if (!isset($images[$serviceId])) {
            throw new \Exception('Unknown service');
        }
        
        return $images[$serviceId];
    }
}
