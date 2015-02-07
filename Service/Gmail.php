<?php

namespace Access2Me\Service;

class Gmail
{
    public static function getProfile(\Google_Client $client, $gmailUserId = 'me')
    {
        $gmail = new \Google_Service_Gmail($client);
    
        $profile = new \Google_Service_Resource(
            $gmail, 'gmail', 'users', array(
                'methods' => array(
                    'profile' => array(
                        'path' => '{userId}/profile',
                        'httpMethod' => 'GET',
                        'parameters' => array(
                            'userId' => array(
                                'location' => 'path',
                                'type' => 'string',
                                'required' => true,
                            ),
                        ),
                    ),
                ),
            )
        );
        
        return $profile->call('profile', [['userId' => $gmailUserId]]);
    }
}
