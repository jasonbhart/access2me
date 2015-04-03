<?php

// https://klout.com/s/developers/v2#identities

namespace Access2Me\Service;

use GuzzleHttp;

class KloutException extends \Exception {}


class Klout
{
    const NETWORK_TWITTER = 1;
    const NETWORK_GOOGLE = 2;

    private static $networkMapping = [
        self::NETWORK_TWITTER => 'tw',
        self::NETWORK_GOOGLE => 'gp'
    ];

    private $apiUrl = 'http://api.klout.com/v2/';
    
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
            throw new KloutException('Bad response', 0, $ex);
        }

        return $result;
    }

    public function getScore($kloutId)
    {
        $params = [
            'key' => $this->config['key']
        ];
        
        if ($kloutId) {
            return $this->fetchUrl('user.json/'.$kloutId.'/score', $params);
        } else {
            return false;
        }
    }
    
    public function getKloutId($id, $networkType)
    {
        if (!isset(self::$networkMapping[$networkType])) {
            return false;
        }

        $apiUrl = sprintf('identity.json/%s/%s', self::$networkMapping[$networkType], $id);
        $params = [
            'key' => $this->config['key']
        ];

        $data = $this->fetchUrl($apiUrl, $params);
        
        if (!empty($data['id'])) {
            return $data['id'];
        } else {
            return false;
        }
    }
}
