<?php

namespace Access2Me\Data\UserStats;

use Access2Me\Data\UserStats;
use Access2Me\Helper\CacheInterface;
use Access2Me\Helper\GoogleAuthProvider;
use Access2Me\Service\Gmail;

abstract class GmailResource extends AbstractResource
{
    protected $isCacheable = false;

    /**
     * @var GoogleAuthProvider
     */
    protected $authProvider;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Cache for 60 seconds
     * @var string
     */
    protected $cacheTtl = 'PT60S';

    public function __construct(GoogleAuthProvider $authProvider, CacheInterface $cache = null)
    {
        $this->authProvider = $authProvider;
        $this->cache = $cache;
    }

    public function get($user)
    {
        $key = get_class($this) . '_' . $user['id'];
        if ($this->cache && $this->cache->exists($key)) {
            return $this->cache->get($key);
        }

        $value = $this->getFreshValue($user);

        if ($this->cache) {
            $this->cache->set($key, $value, $this->cacheTtl);
        }

        return $value;
    }

    abstract protected function getFreshValue($user);
}
