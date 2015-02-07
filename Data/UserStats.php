<?php

namespace Access2Me\Data;

use Access2Me\Helper\CacheInterface;


class UserStats
{
    const GMAIL_CONTACTS_COUNT = 1;
    const VERIFIED_SENDERS_COUNT = 2;
    const FILTERS_COUNT = 3;
    const GMAIL_MESSAGES_COUNT = 4;

    /**
     *
     * @var UserStats\ResourceInterface[]
     */
    protected $resources = [];

    protected $user;

    /**
     * @var CacheInterface
     */
    protected $cache;

    public function __construct($user, CacheInterface $cache = null)
    {
        $this->user = $user;
        $this->cache = $cache;
    }

    public function addResource(UserStats\ResourceInterface $resource)
    {
        $this->resources[$resource->getType()] = $resource;
    }

    protected function getCacheKey($type, $user)
    {
        return 'stats_' . $type . '_' . $user['id'];
    }

    public function get($type)
    {
        if (!isset($this->resources[$type])) {
            throw new \RuntimeException('Not implemented Stats type');
        }

        $resource = $this->resources[$type];
 
        if ($resource->isCacheable()) {
            $key = $this->getCacheKey($type, $this->user);
            if ($this->cache && $this->cache->exists($key)) {
                return $this->cache->get($key);
            }
        }

        $stats = $resource->get($this->user);

        if ($resource->isCacheable() && $this->cache) {
            $this->cache->set($key, $stats);
        }
        
        return $stats;
    }

    public function invalidate($type)
    {
        if ($this->cache) {
            $key = $this->getCacheKey($type, $this->user);
            $this->cache->delete($key);
        }
    }
}
