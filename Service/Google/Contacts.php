<?php

namespace Access2Me\Service\Google;

class Contacts extends \Google_Service
{
    public function __construct(\Google_Client $client)
    {
      parent::__construct($client);
      $this->servicePath = 'm8/feeds/contacts/';
      $this->version = '3.0';       // seems that this doesn't impact anything
      $this->serviceName = 'm8';
    }

    public static function getTotalCount(\Google_Client $client, $googleUserId)
    {
        $service = new self($client);
    
        $contacts = new \Google_Service_Resource(
            $service, 'm8', 'contacts', array(
                'methods' => array(
                    'getTotalCount' => array(
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
        
        $result = $contacts->call('getTotalCount', [['userId' => $googleUserId, 'alt' => 'json']]);
        return (int)$result['feed']['openSearch$totalResults']['$t'];
    }
}
