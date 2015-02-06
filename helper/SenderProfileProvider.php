<?php

namespace Access2Me\Helper;

use Access2Me\Helper;

interface SenderProfileProviderInterface
{
    function getProviders();

    /**
     * Currently we store services and sender in one table.
     * That's why we have senderS here.
     * In the future we need to normalize this.
     * 
     * @param array $request [sender => Model\Sender, services => [id=>servicedata]]
     */
    function getProfiles($request, array $providerIds=[]);

    function getProfile($request, $providerId);
}

class SenderProfileProvider implements SenderProfileProviderInterface
{
    /**
     * @var \Access2Me\ProfileProvider\ProfileProviderInterface[] 
     */
    protected $providers;

    /**
     * 
     * @param array $providers map of serviceId => ['authRequired' => boolean, 'provider' => ProfileProviderInterface]
     */
    public function __construct($providers = array())
    {
        $this->providers = $providers;
    }

    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Converts array of services into the indexed by serviceId array of services
     * @param array $services
     * @return array
     */
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
    public function getProfiles($request, array $providerIds=[])
    {
        $sender = $request['sender'];
        $services = $request['services'];

        $serviceMap = $this->getServiceMap($services);
        $providerIds = !empty($providerIds) ? $providerIds : array_keys($this->getProviders());

        // collect profiles
        $result = [];
        foreach ($providerIds as $pid) {

            if (!isset($this->providers[$pid])) {
                throw new \Exception('Unknown provider ' . $pid);
            }

            $provider = $this->providers[$pid];
            $profile = null;
            
            try {
                // provider requires auth
                if ($provider['authRequired']) {
                    // we have corresponding auth
                    if (isset($serviceMap[$pid])) {
                        $profile = $provider['provider']->fetchProfile(
                            /*$sender,*/
                            $serviceMap[$pid]
                        );
                    }
                    // do not call provider if auth is not specified
                } else {
                    $profile = $provider['provider']->fetchProfile($sender);
                }
            } catch (\Access2Me\ProfileProvider\ProfileProviderException $ex) {
                \Logging::getLogger()->debug($ex->getMessage(), array('exception' => $ex));
            }

            $result[$pid] = $profile;
        }

        return $result;
    }

    public function getProfile($request, $providerId)
    {
        $profile = $this->getProfiles($request, [$providerId]);
        return empty($profile) ? null : $profile[$providerId];
    }
}


class CachedSenderProfileProvider implements SenderProfileProviderInterface
{
    /**
     * @var \Access2Me\Helper\CacheInterface
     */
    private $cache;

    /**
     * @var string in \DateInterval format
     */
    private $cachingPeriod = 'P2W';
    private $cachingPeriodNegative = 'PT2H';        // for negative hits

    /**
     * @var SenderProfileProviderInterface
     */
    private $profileProvider;

    public function __construct(Helper\CacheInterface $cache, SenderProfileProviderInterface $profileProvider)
    {
        $this->cache = $cache;
        $this->profileProvider = $profileProvider;
    }

    public function getProviders()
    {
        return $this->profileProvider->getProviders();
    }

    protected function getKey($sender, $serviceId)
    {
        return $sender . '_profile_' . $serviceId;
    }

    public function getProfiles($request, array $providerIds=[])
    {
        // todo: check cache
        $toFetch = [];
        $result = [];

        $sender = $request['sender'];
        $providerIds = !empty($providerIds) ? $providerIds : array_keys($this->getProviders());

        // check cache
        foreach ($providerIds as $pid) {
            $key = $this->getKey($sender->getSender(), $pid);
            try {
                $result[$pid] = $this->cache->get($key);
            } catch (Helper\CacheException $ex) {
                $toFetch[] = $pid;
            }
        }

        // do we have something to fetch ?
        if ($toFetch) {
            
            $fetched = $this->profileProvider->getProfiles($request, $toFetch);

            // cache results
            foreach ($fetched as $pid=>$profile) {
                $key = $this->getKey($sender->getSender(), $pid);
                $cp = $profile === null ? $this->cachingPeriodNegative : $this->cachingPeriod;
                $this->cache->set($key, $profile, $cp);
                $result[$pid] = $profile;
            }
        }

        return $result;
    }

    function getProfile($request, $providerId)
    {
        $profile = $this->getProfiles($request, [$providerId]);
        return empty($profile) ? null : $profile[$providerId];
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

    public function getProviders()
    {
        return $this->profileProvider->getProviders();
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


    public function getProfiles($request, array $providerIds=[])
    {
        // normalize if not normalized
        if (!array_key_exists('sender', $request)) {
            $request = $this->normalizeSenders($request);
        }

        if (!$request) {
            throw new \InvalidArgumentException('request');
        }

        return $this->profileProvider->getProfiles($request, $providerIds);
    }

    function getProfile($request, $providerId)
    {
        // normalize if not normalized
        if (!array_key_exists('sender', $request)) {
            $request = $this->normalizeSenders($request);
        }

        return $this->profileProvider->getProfile($request, $providerId);
    }
}
