<?php

namespace Access2Me\Helper;

use Access2Me\Helper;

interface SenderProfileProviderInterface
{
    /**
     * Currently we store services and sender in one table.
     * That's why we have senderS here.
     * In the future we need to normalize this.
     * 
     * @param array $request [sender => Model\Sender, services => [id=>servicedata]]
     */
    function getProfiles($request);

    function getProfile($request, $serviceId);
}


class SenderProfileProvider implements SenderProfileProviderInterface
{
    /**
     * @var \Access2Me\ProfileProvider\ProfileProviderInterface[] 
     */
    private $providers;

    /**
     * 
     * @param array $providers map of serviceId => ['authRequired' => boolean, 'provider' => ProfileProviderInterface]
     */
    public function __construct($providers = array())
    {
        $this->providers = $providers;
    }

    protected function getServiceMap(array $services)
    {
        $serviceMap = [];
        foreach ($services as $service) {
            $serviceMap[$service->getService()] = $service;
        }

        return $serviceMap;
    }

    /**
     * Collect info about sender
     * 
     * @param array $request [sender => Model\Sender, services => [id=>servicedata]]
     * @return array
     */
    public function getProfiles($request)
    {
        $sender = $request['sender'];
        $services = $request['services'];

        $serviceMap = $this->getServiceMap($services);

        // collect profiles
        $result = [];
        foreach ($this->providers as $pid=>$provider) {

            // provider requires auth and we have corresponding auth
            if ($provider['authRequired']) {
                if (isset($serviceMap[$pid])) {
                    $result[$pid] = $provider['provider']->fetchProfile(
                        /*$sender,*/
                        $serviceMap[$pid]
                    );
                }
                // do not call provider if auth is not specified
            } else {
                $result[$pid] = $provider['provider']->fetchProfile($sender);
            }

        }

        return $result;
    }

    public function getProfile($request, $serviceId)
    {
        $sender = $request['sender'];
        $services = $request['services'];

        if (!isset($this->providers[$serviceId])) {
            throw new \Exception('Unknown service ' . $serviceId);
        }

        $serviceMap = $this->getServiceMap($services);
        $provider = $this->providers[$serviceId];
        $result = null;
        if ($provider['authRequired']) {
            if (isset($serviceMap[$serviceId])) {
                $result =  $provider['provider']->fetchProfile(
                    /*$sender,*/    // todo
                    $serviceMap[$serviceId]
                );
            } else {
                $msg = sprintf('Service (%d) requires auth but it is not present', $serviceId);
                throw new \Exception($msg);
            }
        } else {
            $result = $provider['provider']->fetchProfile($sender);
        }
        
        return $result;
    }
}


class CachedSenderProfileProvider implements SenderProfileProviderInterface
{
    /**
     * @var \Access2Me\Helper\Cache
     */
    private $cache;

    /**
     * @var string in \DateInterval format
     */
    private $cachingPeriod = 'P2W';

    /**
     * @var SenderProfileProviderInterface
     */
    private $profileProvider;

    public function __construct(Helper\Cache $cache, SenderProfileProviderInterface $profileProvider)
    {
        $this->cache = $cache;
        $this->profileProvider = $profileProvider;
    }

    protected function getKey($sender, $serviceId)
    {
        return $sender . '_profile_' . $serviceId;
    }

    public function getProfiles($request)
    {
        // todo: check cache
        $toFetch = [];
        $result = [];

        // check cache
        $sender = $request['sender'];
        $services = $request['services'];
        foreach ($services as $service) {
            $key = $this->getKey($sender->getSender(), $service->getService());

            try {
                $result[$service->getService()] = $this->cache->get($key);
            } catch (Helper\CacheException $ex) {
                $toFetch[] = $sender;
            }
        }

        // do we have something to fetch ?
        if ($toFetch) {
            $newRequest = [
                'sender' =>  $sender,
                'services' => $toFetch
            ];
            
            $fetched = $this->profileProvider->getProfiles($newRequest);

            // cache results
            foreach ($fetched as $pid=>$profile) {
                $key = $this->getKey($sender->getSender(), $pid);
                $this->cache->set($key, $profile, $this->cachingPeriod);
                $result[$pid] = $profile;
            }
        }

        return $result;
    }

    function getProfile($request, $serviceId)
    {
        $key = $this->getKey($request['sender']->getSender(), $serviceId);
        
        $result = null;
        try {
            $result = $this->cache->get($key);
        } catch (Helper\CacheException $ex) {
            $result = $this->profileProvider->getProfile($request, $serviceId);
            $this->cache->set($key, $result, $this->cachingPeriod);
        }

        return $result;
    }
}

/**
 * Normalizes array of senders to array of sender and services
 * [
 *  'sender' => sender,
 *  'services' => []
 * ]
 */
class NormalizedSenderProfileProvider implements SenderProfileProviderInterface
{
    /**
     * @var SenderProfileProviderInterface
     */
    private $profileProvider;

    public function __construct(SenderProfileProviderInterface $profileProvider)
    {
        $this->profileProvider = $profileProvider;
    }

    /**
     * Convert senders into the [sender, services]
     * 
     * @param \Access2Me\Model\Sender $senders
     * @return array
     */
    public static function normalizeSenders($senders)
    {
        $senders = (array)$senders;

        if (!$senders) {
            return null;
        }

        $result = [
            'sender' => $senders[0],
            'services' => []
        ];

        // map services to service data (auth)
        foreach ($senders as $sender) {
            // todo: only oauth is needed (not the whole sender)
            $result['services'][] = $sender; //$sender->getOAuth();
        }

        return $result;
    }


    public function getProfiles($request)
    {
        // normalize if not normalized
        if (!array_key_exists('sender', $request)) {
            $request = $this->normalizeSenders($request);
        }

        if (!$request) {
            throw new \InvalidArgumentException('request');
        }

        return $this->profileProvider->getProfiles($request);
    }

    function getProfile($request, $serviceId)
    {
        // normalize if not normalized
        if (!array_key_exists('sender', $request)) {
            $request = $this->normalizeSenders($request);
        }

        return $this->profileProvider->getProfile($request, $serviceId);
    }
}
