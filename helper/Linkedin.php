<?php

namespace Access2Me\Helper;

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

        $cURL = curl_init();

        curl_setopt($cURL, CURLOPT_VERBOSE, true);
        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURL, CURLOPT_HTTPGET, true);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($cURL);
        curl_close($cURL);

        $xml = new \SimpleXMLElement($result);
        $data = $xml->xpath('/person');

        $profile = array();
        
        $profile['first_name'] = (isset($data[0]->{'first-name'})) ? (string) $data[0]->{'first-name'} : null;
        $profile['last_name'] = (isset($data[0]->{'last-name'})) ? (string) $data[0]->{'last-name'} : null;
        $profile['email'] = (isset($data[0]->{'email-address'})) ? (string) $data[0]->{'email-address'} : null;
        $profile['headline'] = (isset($data[0]->{'headline'})) ? (string) $data[0]->{'headline'} : null;
        $profile['picture_url'] = (isset($data[0]->{'picture-url'})) ? (string) $data[0]->{'picture-url'} : null;
        $profile['profile_url'] = (isset($data[0]->{'site-standard-profile-request'}->{'url'})) ? (string) $data[0]->{'site-standard-profile-request'}->{'url'} : null;
        $profile['location'] = (isset($data[0]->{'location'}->{'name'})) ? (string) $data[0]->{'location'}->{'name'} : null;
        $profile['industry'] = (isset($data[0]->{'industry'})) ? (string) $data[0]->{'industry'} : null;
        $profile['self_summary'] = (isset($data[0]->{'summary'})) ? (string) $data[0]->{'summary'} : null;
        $profile['specialties'] = (isset($data[0]->{'specialties'})) ? (string) $data[0]->{'specialties'} : null;
        $profile['associations'] = (isset($data[0]->{'associations'})) ? (string) $data[0]->{'associations'} : null;
        $profile['interests'] = (isset($data[0]->{'interests'})) ? (string) $data[0]->{'interests'} : null;
        $profile['total_connections'] = (isset($data[0]->{'num-connections'})) ? (string) $data[0]->{'num-connections'} : null;
        $profile['total_positions'] = (isset($data[0]->{'positions'}->attributes()['total'][0])) ? (string) $data[0]->{'positions'}->attributes()['total'][0] : null;

        for ($x = 0; $x < $profile['total_positions']; $x++) {
            $profile['positions'][$x]['company'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'company'}->{'name'};
            $profile['positions'][$x]['title'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'title'};
            $profile['positions'][$x]['summary'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'summary'};
            $profile['positions'][$x]['is_current'] = (string) $data[0]->{'positions'}->{'position'}[$x]->{'is-current'};
        }
        
        return $profile;
    }
}
