<?php

namespace Access2Me\Service\Google;

class Contacts extends \Google_Service
{
    public function __construct(\Google_Client $client)
    {
      parent::__construct($client);
      $this->servicePath = 'm8/feeds/contacts/';
      $this->version = 'v1';
      $this->serviceName = 'm8';
    }

    public static function getAll(\Google_Client $client, $googleUserId = 'me')
    {
        $gmail = new self($client);
    
        $contacts = new \Google_Service_Resource(
            $gmail, 'm8', 'contacts', array(
                'methods' => array(
                    'getAll' => array(
                        'path' => '{userId}/full',
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
        
        return $contacts->call('getAll', [['userId' => $googleUserId]]);
    }
}
