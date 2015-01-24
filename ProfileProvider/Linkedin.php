<?php

namespace Access2Me\ProfileProvider;

use Access2Me\Helper;
use Access2Me\Model\Profile;
use Access2Me\Service;

class Linkedin implements ProfileProviderInterface
{
    private $serviceConfig;

    /**
     * @param array $serviceConfig
     */
    public function __construct($serviceConfig)
    {
        $this->serviceConfig = $serviceConfig;
    }

    /**
     * @param \Access2Me\Model\Sender $sender
     * @return array
     */
    public function fetchProfile(\Access2Me\Model\Sender $sender)
    {
        try {
            $linkedin = new Helper\Linkedin($this->serviceConfig);
            $xml = $linkedin->getProfile($sender->getOAuthKey());
            $profile = $this->parseProfileData($xml);
            return $profile;
        } catch (\Exception $ex) {
            throw new ProfileProviderException('Can\'t fetch profile', 0, $ex);
        }
    }

    public function parseProfileData($xml)
    {
        if (!$xml) {
            throw new ProfileProviderException('Invalid profile data');
        }

        $profile = new Profile\Profile();
        $profile->firstName = isset($xml->{'first-name'}) ? (string) $xml->{'first-name'} : null;
        $profile->lastName = isset($xml->{'last-name'}) ? (string) $xml->{'last-name'} : null;
        $profile->email = isset($xml->{'email-address'}) ? (string) $xml->{'email-address'} : null;
        $profile->headline = isset($xml->{'headline'}) ? (string) $xml->{'headline'} : null;
        $profile->pictureUrl = isset($xml->{'picture-url'}) ? (string) $xml->{'picture-url'} : null;
        $profile->industry = isset($xml->{'industry'}) ? (string) $xml->{'industry'} : null;
        $profile->summary = isset($xml->{'summary'}) ? (string) $xml->{'summary'} : null;
        $profile->specialties = isset($xml->{'specialties'}) ? (string) $xml->{'specialties'} : null;
        $profile->associations = isset($xml->{'associations'}) ? (string) $xml->{'associations'} : null;
        $profile->interests = isset($xml->{'interests'}) ? (string) $xml->{'interests'} : null;
        $profile->connections = isset($xml->{'num-connections'}) ? (string) $xml->{'num-connections'} : null;

        $profile->fullName = $profile->firstName;
        
        if (!empty($profile->lastName)) {
            if (mb_strlen($profile->fullName) > 0) {
                $profile->fullName .=  ' ';
            }
            
            $profile->fullName .= $profile->lastName;
        }
        
        if (isset($xml->{'location'})
            && isset($xml->{'location'}->{'name'})
        ) {
            $profile->location = (string)$xml->{'location'}->{'name'};
        }
        
        if (isset($xml->{'public-profile-url'})) {
            $profile->profileUrl = (string)$xml->{'public-profile-url'};
        }

        if (!$profile->profileUrl && isset($xml->{'site-standard-profile-request'})
            && isset($xml->{'site-standard-profile-request'}->{'url'})
        ) {
            $profile->profileUrl = (string)$xml->{'site-standard-profile-request'}->{'url'};
        }
        
        $profile->positions = $this->parsePositions($xml);

        return $profile;
    }

    protected function parsePositions($xml)
    {
        $positions = array();
        if (isset($xml->positions)
            && isset($xml->positions->position)
        ) {
            foreach ($xml->positions->position as $position) {
                $item = new Profile\Position();
                
                if (isset($position->company) && isset($position->company->name)) {
                    $item->company = (string)$position->company->name;
                }

                $item->title = isset($position->title) ? (string)$position->title : NULL;
                $item->summary = isset($position->summary) ? (string)$position->summary : NULL;
                $item->active = isset($position->{'is-current'})
                    ? (string)$position->{'is-current'} : false;
                
                $positions[] = $item;
            }
        }

        return $positions;
    }
}
