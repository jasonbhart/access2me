<?php

namespace Access2Me\Service;

use GuzzleHttp;

class KloutException extends \Exception {}


class Klout
{
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

    public function getScore($twitterId)
    {
        $params = [
            'key' => $this->config['key']
        ];
        
        $kloutId = $this->getKloutId($twitterId, $params);
        
        if ($kloutId) {
            return $this->fetchUrl('user.json/'.$kloutId.'/score', $params);
        } else {
            return false;
        }
    }
    
    private function getKloutId($twitterId, $params)
    {
        $data = $this->fetchUrl('identity.json/tw/'.$twitterId, $params);
        
        if (!empty($data['id'])) {
            return $data['id'];
        } else {
            return false;
        }
    }
}
