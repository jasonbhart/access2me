<?php

namespace Access2Me\Service;

use GuzzleHttp;

class CrunchBaseException extends \Exception {}

class CrunchBaseSearchType
{
    const QUERY = 1;
    const NAME = 2;
    const DOMAIN_NAME = 3;
}

class CrunchBase {

    private $apiUrl = 'http://api.crunchbase.com/v/2/';
    private $userKey;
    
    public function __construct($config) {
        if (!isset($config['user_key'])) {
            throw new \InvalidArgumentException('user_key is required');
        }

        $this->userKey = $config['user_key'];
    }

    protected function fetchUrl($endpoint, $params = [])
    {
        $client = new GuzzleHttp\Client([
            'base_url' => $this->apiUrl
        ]);
        try {
            $res = $client->get(
                $endpoint,
                ['query' => $params]
            );
        } catch (\GuzzleHttp\Exception\BadResponseException $ex) {
            throw new CrunchBaseException('Bad response', 0, $ex);
        }

        return $res->json();
    }

    protected function getResult($endpoint, $params = [])
    {
        $params['user_key'] = $this->userKey;
        return $this->fetchUrl($endpoint, $params);
    }

    /**
     * Searches organizations by domain name
     * 
     * @param string $value
     * @return array
     */
    public function findOrganizations($value = null, $type = null)
    {
        if ($type == CrunchBaseSearchType::QUERY) {
            $params = ['query' => $value];
        }
        elseif ($type == CrunchBaseSearchType::NAME) {
            $params = ['name' => $value];
        }
        elseif ($type == CrunchBaseSearchType::DOMAIN_NAME) {
            $params = ['domain_name' => $value];
        }

        return $this->getResult('organizations', $params);
    }

    public function getOrganization($permalink)
    {
        if (substr_compare('organization/', $permalink, 0, 13) != 0) {
            $permalink = 'organization/' . $permalink;
        }

        return $this->getResult($permalink);
    }
}
