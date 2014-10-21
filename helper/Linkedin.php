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

    public function parseProfileData($data)
    {
        $person = $data[0];
        $profile = array();
        
        $profile['first_name'] = isset($person->{'first-name'}) ? (string) $person->{'first-name'} : null;
        $profile['last_name'] = isset($person->{'last-name'}) ? (string) $person->{'last-name'} : null;
        $profile['email'] = isset($person->{'email-address'}) ? (string) $person->{'email-address'} : null;
        $profile['headline'] = isset($person->{'headline'}) ? (string) $person->{'headline'} : null;
        $profile['picture_url'] = isset($person->{'picture-url'}) ? (string) $person->{'picture-url'} : null;
        $profile['profile_url'] = isset($person->{'site-standard-profile-request'}->{'url'}) ? (string) $person->{'site-standard-profile-request'}->{'url'} : null;
        $profile['location'] = isset($person->{'location'}->{'name'}) ? (string) $person->{'location'}->{'name'} : null;
        $profile['industry'] = isset($person->{'industry'}) ? (string) $person->{'industry'} : null;
        $profile['self_summary'] = isset($person->{'summary'}) ? (string) $person->{'summary'} : null;
        $profile['specialties'] = isset($person->{'specialties'}) ? (string) $person->{'specialties'} : null;
        $profile['associations'] = isset($person->{'associations'}) ? (string) $person->{'associations'} : null;
        $profile['interests'] = isset($person->{'interests'}) ? (string) $person->{'interests'} : null;
        $profile['total_connections'] = isset($person->{'num-connections'}) ? (string) $person->{'num-connections'} : null;

        // parse positions
        $positions = array();
        if (isset($person->positions)
            && isset($person->positions->position)
        ) {
            foreach ($person->positions->position as $position) {
                $item = array();
                
                if (isset($position->company) && isset($position->company->name)) {
                    $item['company'] = (string)$position->company->name;
                }

                $item['title'] = isset($position->title) ? (string)$position->title : NULL;
                $item['summary'] = isset($position->summary) ? (string)$position->summary : NULL;
                $item['is_current'] = isset($position->{'is-current'})
                    ? (string)$position->{'is-current'} : false;
                
                $positions[] = $item;
            }
        }

        $profile['positions'] = $positions;

        return $profile;
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

        $profile = $this->parseProfileData($data);
        return $profile;
    }
}
