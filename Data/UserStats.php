<?php

namespace Access2Me\Data;

use Access2Me\Helper\CacheInterface;


class UserStats
{
    const CONTACTS_COUNT = 1;
    const INVITES_COUNT = 2;
    const FILTERS_COUNT = 3;
    const MESSAGES_COUNT = 4;

    protected $resources = [];

    protected $userId;

    /**
     * @var CacheInterface
     */
    protected $cache;

    public function __construct($userId, CacheInterface $cache = null)
    {
        $this->userId = $userId;
        $this->cache = $cache;
    }

    public function addResource(UserStats\ResourceInterface $resource)
    {
        $this->resources[$resource->getType()] = $resource;
    }

    protected function getCacheKey($type, $userId)
    {
        return 'stats_' . $type . '_' . $userId;
    }

    public function get($type)
    {
        $key = $this->getCacheKey($type, $this->userId);
        if ($this->cache && $this->cache->exists($key)) {
            return $this->cache->get($key);
        }

        $stats = $this->getFreshStats($type);

        if ($this->cache) {
            $this->cache->set($key, $stats);
        }
        
        return $stats;
    }

    protected function getFreshStats($type)
    {
        if (isset($this->resources[$type])) {
            return $this->resources[$type]->get($this->userId);
        }
        
        throw new \RuntimeException('Not implemented Stats type');
    }

    public function invalidate($type)
    {
        if ($this->cache) {
            $key = $this->getCacheKey($type, $this->userId);
            $this->cache->delete($key);
        }
    }
}
