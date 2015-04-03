<?php

namespace Access2Me\ProfileProvider;

use Access2Me\Helper;
use Access2Me\Service;

class AngelList implements ProfileProviderInterface
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
     * Loop through results trying to match company_url with email domain (gmail.com)
     * 
     * @param \Access2Me\Model\Sender $sender
     * @return array
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

            // make search query
            $domain = $email['domain'];
            $pos = strripos($domain, '.');
            $query = $pos === false ? $domain : substr($domain, 0, $pos);

            // search  in startups
            $angellist = new Service\AngelList($this->serviceConfig);
            $data = $angellist->search($query, Service\AngelListType::STARTUP);

            foreach ($data as $startup) {
                $info = $angellist->getStartupInfo($startup['id']);

                // match company url with domain name
                if (isset($info['company_url'])) {
                    $host = parse_url($info['company_url'], PHP_URL_HOST);

                    // matches ?
                    if ($host == $email['domain']) {
                        return $this->parseInfo($email['domain'], $info);
                    }
                }
            }
            
            return null;
        } catch (\Exception $ex) {
            throw new ProfileProviderException('Can\'t fetch profile', 0, $ex);
        }
    }

    protected function parseInfo($domain, $info)
    {
        $parsed = [
            'domain' => $domain,
            'name' => $info['name'],
            'company_url' => $info['company_url'],
            'fundraising' => null
        ];

        if (isset($info['fundraising']) && is_array($info['fundraising'])) {
            $parsed['fundraising'] = [
                'raising_amount' => (float)$info['fundraising']['raising_amount'],
                'raised_amount' => (float)$info['fundraising']['raised_amount']
            ];
        }

        return $parsed;
    }
}
