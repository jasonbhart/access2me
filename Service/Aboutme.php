<?php

namespace Access2Me\Service;

use GuzzleHttp;

class AboutmeException extends \Exception {}

class Aboutme
{
    private $apiUrl = 'https://api.about.me/api/v2/json/';
    
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
            throw new AboutmeException('Bad response', 0, $ex);
        }

        return $result;
    }

    public function getAboutMeProfile($email)
    {
        $params = [
            'client_id' => $this->config['client_id'],
            'email' => $email
        ];

        return $this->fetchUrl('users/search', $params);
    }
}
