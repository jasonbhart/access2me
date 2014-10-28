<?php

namespace Access2Me\Helper;

use Access2Me\Model\SenderRepository;

class Template
{
    public static function getServiceImage($serviceId)
    {
        $images = array(
            SenderRepository::SERVICE_LINKEDIN => 'linkedin-22x17-bw.gif',
            SenderRepository::SERVICE_FACEBOOK => 'facebook.png',
            SenderRepository::SERVICE_TWITTER => 'twitter.png',
        );

        if (!isset($images[$serviceId])) {
            throw new \Exception('Unknown service');
        }
        
        return $images[$serviceId];
    }
}
