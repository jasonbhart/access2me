<?php

namespace Access2Me\Service;

use GuzzleHttp;

class AngelList {

    private $apiUrl = 'https://api.angel.co/1/';

    public function __construct($config = null)
    {
        
    }

    protected function fetchUrl($url, $params)
    {
        $client = new GuzzleHttp\Client([
            'base_url' => $this->apiUrl
        ]);
        $res = $client->get(
            $url,
            ['query' => $params]
        );

        if ($res->getStatusCode() >= 400) {
            throw new \Exception($res->getReasonPhrase());
        }
        
        return $res->json();
    }

    public function search($query)
    {
        $params = [
            'query' => $query
        ];

        return $this->fetchUrl('search', $params);
    }
}
