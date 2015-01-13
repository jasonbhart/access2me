<?php

namespace Access2Me\Service;

class CrunchBase {

    private $apiUrl = 'http://api.crunchbase.com/v/2';
    private $userKey;
    
    public function __construct($config) {
        if (!isset($config['user_key'])) {
            throw new \InvalidArgumentException('user_key is required');
        }

        $this->userKey = $config['user_key'];
    }

    protected function fetchUrl($url)
    {
        $cURL = curl_init();

        curl_setopt($cURL, CURLOPT_VERBOSE, true);
        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURL, CURLOPT_HTTPGET, true);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($cURL);

        if (curl_errno($cURL) != 0) {
            $exception = new \Exception(curl_error($cURL));
        } elseif (curl_getinfo($cURL, CURLINFO_HTTP_CODE) >= 400) {
            $exception = new \Exception($result);
        }

        curl_close($cURL);

        if (isset($exception)) {
            throw $exception;
        }

        return $result;
    }

    protected function getResult($url, $params)
    {
        $params['user_key'] = $this->userKey;
        $url = $this->apiUrl . $url . '?' . http_build_query($params);
        
        return $this->fetchUrl($url);
    }

    public function getOrganizations()
    {
        $params = ['page' => 1];
        return $this->getResult('/organizations', $params);
    }
}
