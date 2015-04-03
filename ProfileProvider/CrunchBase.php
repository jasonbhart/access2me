<?php

namespace Access2Me\ProfileProvider;

use Access2Me\Helper;
use Access2Me\Service;

class CrunchBase implements ProfileProviderInterface
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
     * Search for email domain with stripped last part
     * Ex.: bos@gmail.com --> gmail
     * 
     * @param \Access2Me\Model\Sender $sender
     * @return Profile\CrunchBase|null
     */
    public function fetchProfile(\Access2Me\Model\Sender $sender, array $dependencies = [])
    {
        try {
            $address = $sender->getSender();

            // parse address
            $email = Helper\Email::splitEmail($address);
            if ($email === false) {
                throw new \Exception('Can\'t parse email address: ' . $address);
            }

            // search  in startups
            $crunchBase = new Service\CrunchBase($this->serviceConfig);
            $data = $crunchBase->findOrganizations($email['domain'], Service\CrunchBaseSearchType::DOMAIN_NAME);

            // no organizations found
            if (count($data['data']['items']) == 0) {
                return null;
            }

            $item = $data['data']['items'][0];
            if (!isset($item['path'])) {
                return null;
            }

            $org = $crunchBase->getOrganization($item['path']);
            
            $profile = $this->parseOrganization($org);
            return $profile;
        } catch (Service\CrunchBaseException $ex) {
            throw new ProfileProviderException('Can\'t fetch profile', 0, $ex);
        }
    }

    /**
     * Parses Crunchbase organization into profile
     * @param array $org
     * @return Profile\CrunchBase
     */
    protected function parseOrganization($org)
    {
        if (!isset($org['data']) || !isset($org['data']['properties'])) {
            return null;
        }

        $data = $org['data'];
        $properties = $org['data']['properties'];

        $profile = new Profile\CrunchBase();
        
        if (isset($properties['name'])) {
            $profile->name = $properties['name'];
        }

        if (isset($properties['short_description'])) {
            $profile->description = $properties['short_description'];
        }

        if (isset($properties['homepage_url'])) {
            $profile->homepageUrl = $properties['homepage_url'];
        }

        if (isset($properties['total_funding_usd'])) {
            $profile->totalFunding = (float)$properties['total_funding_usd'];
        }

        return $profile;
    }
}
