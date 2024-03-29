<?php

namespace Access2Me\Service;

use GuzzleHttp;

class FullContactException extends \Exception {}

class FullContact
{
    const PERSON_TYPE_EMAIL = 1;

    private $apiUrl = 'https://api.fullcontact.com/v2/';
    
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    protected function fetchUrl($url, $params = [])
    {
        $client = new GuzzleHttp\Client([
            'base_url' => $this->apiUrl
        ]);
        
        try {
            $res = $client->get(
                $url,
                [
                    'query' => $params,
                    'timeout' => 5
                ]
            );

            $result = $res->json();
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            throw new FullContactException('Bad response', 0, $ex);
        }

        return $result;
    }

    public function getPerson($query, $type)
    {
        $params = [
            'apiKey' => $this->config['api_key']
        ];

        if ($type == self::PERSON_TYPE_EMAIL) {
            $params['email'] = $query;
        }

        return $this->fetchUrl('person.json', $params);
    }
}
