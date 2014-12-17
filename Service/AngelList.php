<?php

namespace Access2Me\Service;

use GuzzleHttp;

class AngelListType
{
    const USER = 'User';
    const STARTUP = 'Startup';
    const MARKET_TAG = 'MarketTag';
    const LOCATION_TAG = 'LocationTag';
}

class AngelList
{

    private $apiUrl = 'https://api.angel.co/1/';

    public function __construct($config = null)
    {
        
    }

    protected function fetchUrl($url, $params = [])
    {
        $client = new GuzzleHttp\Client([
            'base_url' => $this->apiUrl
        ]);
        $res = $client->get(
            $url,
            ['query' => $params]
        );

//        if ($res->getStatusCode() >= 400) {
//            throw new \Exception($res->getReasonPhrase());
//        }
        
        return $res->json();
    }

    public function search($query, $type)
    {
        $params = [
            'query' => $query,
            'type' => $type
        ];

        return $this->fetchUrl('search', $params);
    }

    public function getStartupInfo($startupId)
    {
        $url = 'startups/' . $startupId;
        return $this->fetchUrl($url);
    }
}
