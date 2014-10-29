<?php

namespace Access2Me\Helper;

class LinkedinException extends \Exception
{
    public function __toString()
    {
        return 'Linkedin exception: ' . $this->message;
    }
}

class Linkedin
{
    /**
     * @var array
     */
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getProfile($token)
    {
        $url = "https://api.linkedin.com/v1/people/~:(";
        $url .= "first-name,";
        $url .= "last-name,";
        $url .= "email-address,";
        $url .= "headline,";
        $url .= "industry,";
        $url .= "picture-url,";
        $url .= "site-standard-profile-request,";
        $url .= "num-connections,";
        $url .= "summary,";
        $url .= "specialties,";
        $url .= "associations,";
        $url .= "interests,";
        $url .= "num-recommenders,";
        $url .= "recommendations-received,";
        $url .= "phone-numbers,";
        $url .= "im-accounts,";
        $url .= "main-address,";
        $url .= "twitter-accounts,";
        $url .= "primary-twitter-account,";
        $url .= "group-memberships,";
        $url .= "positions,";
        $url .= "location:(name))";
        $url .= "?oauth2_access_token=" . urlencode($token);

        try {
            $cURL = curl_init();

            curl_setopt($cURL, CURLOPT_VERBOSE, true);
            curl_setopt($cURL, CURLOPT_URL, $url);
            curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($cURL, CURLOPT_HTTPGET, true);
            curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($cURL);
            curl_close($cURL);

            $xml = new \SimpleXMLElement($result);
            $person = $xml->xpath('/person');

            return isset($person[0]) ? $person[0] : false;

        } catch (\Exception $ex) {
            throw new LinkedinException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }
}
