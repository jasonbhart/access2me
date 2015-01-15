<?php

namespace Access2Me\Service;

class Gmail
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public static function getProfile(\Google_Client $client, $userId = 'me')
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
        
        return $profile->call('profile', [['userId' => $userId]]);
    }
}
